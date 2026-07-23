#!/bin/bash
# =============================================================
# 资产管理系统 - 打包迁移脚本（含数据库快照）
# =============================================================
# 用法: ./package.sh
#
# 打包项目代码 + vendor/ + 前端构建产物 + 数据库快照，
# 方便迁移到内网服务器（无需联网下载依赖）。
#
# 数据库快照会导出到 storage/app/deploy_snapshot/，包含：
#   - zcgl_full_<日期>.sql      结构+数据（全新内网一键导入复刻）
#   - zcgl_structure_<日期>.sql  仅结构（已有数据的内网参考比对）
#
# 可选参数:
#   --no-db     跳过数据库快照导出（只打代码包）
#   --data-only 仅导出系统数据（print_templates/department_codes）
# =============================================================

set -e
cd "$(dirname "$0")"

DESKTOP="/Users/$(whoami)/Desktop"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
OUTPUT="${DESKTOP}/zcgl_${TIMESTAMP}.tar.gz"

EXPORT_DB=1
DATA_ONLY=0
for arg in "$@"; do
    case "$arg" in
        --no-db) EXPORT_DB=0 ;;
        --data-only) DATA_ONLY=1; EXPORT_DB=0 ;;
    esac
done

echo ""
echo "╔════════════════════════════════════════╗"
echo "║     资产管理系统 - 打包迁移（含数据库快照）║"
echo "╚════════════════════════════════════════╝"
echo ""

# ---- 前置校验 ----
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "✗ vendor/ 目录不存在！"
    echo "  请先运行 composer install --no-dev"
    exit 1
fi
if [ -d public/build ]; then
    echo "✓ public/build/ 前端构建产物已就绪"
else
    echo "⚠ public/build/ 不存在，内网可能无法正常显示页面（建议先 npm run build）"
fi
if [ ! -f .env ]; then
    echo "✗ .env 文件不存在，无法读取数据库配置"
    exit 1
fi
echo ""

# ---- 数据库快照导出 ----
SNAPSHOT_DIR="storage/app/deploy_snapshot"
if [ "$EXPORT_DB" = "1" ]; then
    # 加载数据库配置
    export $(grep -E "^DB_HOST|^DB_PORT|^DB_DATABASE|^DB_USERNAME|^DB_PASSWORD|^MYSQL_BIN_DIR" .env | xargs 2>/dev/null)

    # 自动查找 mysqldump
    MYSQLDUMP=""
    if [ -n "$MYSQL_BIN_DIR" ] && [ -x "${MYSQL_BIN_DIR}/mysqldump" ]; then
        MYSQLDUMP="${MYSQL_BIN_DIR}/mysqldump"
    elif command -v mysqldump &>/dev/null; then
        MYSQLDUMP=$(command -v mysqldump)
    elif [ -x /Applications/XAMPP/xamppfiles/bin/mysqldump ]; then
        MYSQLDUMP=/Applications/XAMPP/xamppfiles/bin/mysqldump
    elif [ -x /usr/local/mysql/bin/mysqldump ]; then
        MYSQLDUMP=/usr/local/mysql/bin/mysqldump
    else
        echo "✗ 未找到 mysqldump，跳过数据库快照导出"
        echo "  请在 .env 中设置 MYSQL_BIN_DIR=/path/to/mysql/bin"
        EXPORT_DB=0
    fi
fi

if [ "$EXPORT_DB" = "1" ]; then
    mkdir -p "$SNAPSHOT_DIR"
    # 清理 7 天前的旧快照
    find "$SNAPSHOT_DIR" -name '*.sql' -mtime +7 -delete 2>/dev/null || true

    DB_NAME="${DB_DATABASE:-zcgl}"
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"
    DB_USER="${DB_USERNAME:-root}"
    DB_PASS="${DB_PASSWORD:-}"

    # 临时配置文件传密码，避免命令行密码泄漏与警告
    CNF_FILE=$(mktemp)
    cat > "$CNF_FILE" <<CNF
[client]
user=${DB_USER}
password=${DB_PASS}
default-character-set=utf8mb4
CNF

    FULL_SQL="$SNAPSHOT_DIR/zcgl_full_${TIMESTAMP}.sql"
    STRUCT_SQL="$SNAPSHOT_DIR/zcgl_structure_${TIMESTAMP}.sql"

    echo "▸ 导出数据库快照（结构+数据）..."
    "$MYSQLDUMP" --defaults-extra-file="$CNF_FILE" \
        -h "$DB_HOST" -P "$DB_PORT" \
        --single-transaction --triggers --skip-comments \
        --default-character-set=utf8mb4 \
        "$DB_NAME" > "$FULL_SQL" 2>/dev/null || true

    echo "▸ 导出仅结构快照..."
    "$MYSQLDUMP" --defaults-extra-file="$CNF_FILE" \
        -h "$DB_HOST" -P "$DB_PORT" \
        --no-data --skip-comments --skip-add-drop-table \
        --default-character-set=utf8mb4 \
        "$DB_NAME" > "$STRUCT_SQL" 2>/dev/null || true

    rm -f "$CNF_FILE"

    if [ -s "$FULL_SQL" ]; then
        SIZE=$(du -h "$FULL_SQL" | cut -f1)
        echo "✓ 完整快照: ${FULL_SQL} (${SIZE})"
    else
        echo "✗ 完整快照导出失败，请检查数据库连接"
        rm -f "$FULL_SQL" "$STRUCT_SQL"
        exit 1
    fi
    [ -s "$STRUCT_SQL" ] && echo "✓ 结构快照: ${STRUCT_SQL}"
    echo ""
fi

if [ "$DATA_ONLY" = "1" ]; then
    echo "▸ 仅导出系统数据模式，运行 export-system-data.sh..."
    bash export-system-data.sh || true
    exit 0
fi

# ---- 打包 ----
echo "▸ 正在打包（排除 .git/node_modules/日志/缓存/历史备份）..."
echo "  输出文件: ${OUTPUT}"
echo "  数据库快照: ${EXPORT_DB}（1=已导出并打包）"
echo ""

# 注意：storage/app/deploy_snapshot 保留（含快照），其余备份/缓存/日志排除
tar -czf "$OUTPUT" \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor/bin' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/app/backups' \
    --exclude='storage/app/update_tmp' \
    --exclude='storage/backups' \
    --exclude='.phpunit.result.cache' \
    --exclude='composer.phar' \
    --exclude='*.tar.gz' \
    --exclude='.DS_Store' \
    -C "$(pwd)" .

SIZE=$(du -h "$OUTPUT" | cut -f1)
echo ""
echo "══════════════════════════════════════════"
echo "  ✓ 打包完成！"
echo "══════════════════════════════════════════"
echo ""
echo "   文件: ${OUTPUT}"
echo "   大小: ${SIZE}"
echo ""
echo "内网部署步骤:"
echo "  1. 将 tar.gz 复制到内网服务器"
echo "  2. 解压: tar -xzf ${OUTPUT##*/} -C /var/www/zcgl"
echo "  3. 配置 .env（数据库连接）"
echo "  4. 运行: cd /var/www/zcgl && bash deploy-to-production.sh"
echo ""
echo "数据库快照已包含在包内 storage/app/deploy_snapshot/"
echo "  - 全新内网: deploy 脚本会提示导入 zcgl_full_*.sql 一键复刻"
echo "  - 已有数据内网: 用 deploy:migrate 增量迁移（不丢数据）"
echo "  - 迁移后运行 php artisan deploy:diff 校验结构一致性"
echo ""
