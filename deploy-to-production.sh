#!/bin/bash
# =============================================================
# 资产管理系统 - 内网服务器部署脚本
# =============================================================
# 用法: ./deploy-to-production.sh
#
# 自动识别内网数据库状态，选择最安全的迁移路径：
#   - 全新空库: 提示导入完整快照(一键复刻) 或 deploy:migrate 建空结构
#   - 已有数据: deploy:migrate 增量迁移（自动备份，不丢数据）
#
# 前提条件（内网服务器无需联网）:
#   1. PHP 8.2+（含 pdo_mysql、mbstring、bcmath 扩展）
#   2. MySQL / MariaDB 已运行
#   3. mysql / mysqldump 可用（MySQL 自带，用于备份与导入）
#   4. 代码已复制到服务器，包括 vendor/ 目录
# =============================================================

set -e
cd "$(dirname "$0")"

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║     资产管理系统 - 内网部署脚本           ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# ---- 加载 .env 数据库配置 ----
export $(grep -E "^DB_HOST|^DB_PORT|^DB_DATABASE|^DB_USERNAME|^DB_PASSWORD|^MYSQL_BIN_DIR" .env 2>/dev/null | xargs 2>/dev/null || true)

DB_NAME="${DB_DATABASE:-zcgl}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USERNAME:-root}"
DB_PASS="${DB_PASSWORD:-}"

# 自动查找 mysql / mysqldump
MYSQL=""
MYSQLDUMP=""
if [ -n "$MYSQL_BIN_DIR" ] && [ -x "${MYSQL_BIN_DIR}/mysql" ]; then
    MYSQL="${MYSQL_BIN_DIR}/mysql"
    MYSQLDUMP="${MYSQL_BIN_DIR}/mysqldump"
elif command -v mysql &>/dev/null; then
    MYSQL=$(command -v mysql)
    [ -x "$(command -v mysqldump)" ] && MYSQLDUMP=$(command -v mysqldump)
elif [ -x /Applications/XAMPP/xamppfiles/bin/mysql ]; then
    MYSQL=/Applications/XAMPP/xamppfiles/bin/mysql
    [ -x /Applications/XAMPP/xamppfiles/bin/mysqldump ] && MYSQLDUMP=/Applications/XAMPP/xamppfiles/bin/mysqldump
elif [ -x /usr/local/mysql/bin/mysql ]; then
    MYSQL=/usr/local/mysql/bin/mysql
    [ -x /usr/local/mysql/bin/mysqldump ] && MYSQLDUMP=/usr/local/mysql/bin/mysqldump
fi

# 临时配置文件传密码，避免命令行泄漏与警告
CNF_FILE=$(mktemp)
cat > "$CNF_FILE" <<CNF
[client]
user=${DB_USER}
password=${DB_PASS}
default-character-set=utf8mb4
CNF
trap "rm -f $CNF_FILE" EXIT

# ---- 前置检查 ----
ERRORS=0

if ! command -v php &>/dev/null; then
    echo "✗ PHP 未安装"; ERRORS=1
else
    echo "✓ PHP $(php -r 'echo PHP_VERSION;')"
    for ext in pdo_mysql mbstring bcmath; do
        if php -m | grep -qi "^${ext}$"; then
            echo "  ✓ 扩展 ${ext}"
        else
            echo "  ✗ 缺少扩展 ${ext}"; ERRORS=1
        fi
    done
fi

[ -d vendor ] && [ -f vendor/autoload.php ] && echo "✓ vendor/ 目录存在" || { echo "✗ vendor/ 缺失"; ERRORS=1; }
[ -f .env ] && echo "✓ .env 文件存在" || { echo "✗ .env 不存在"; ERRORS=1; }

[ -n "$MYSQL" ] && echo "✓ mysql: ${MYSQL}" || { echo "⚠ mysql 未找到（无法导入快照/备份，但仍可 migrate）"; }
[ -n "$MYSQLDUMP" ] && echo "✓ mysqldump: ${MYSQLDUMP}" || echo "⚠ mysqldump 未找到（自动备份将跳过）"
echo "  数据库: ${DB_NAME} @ ${DB_HOST}:${DB_PORT}"
echo ""

if [ $ERRORS -ne 0 ]; then
    echo "══════════════════════════════════════════"
    echo "  ✗ 前置检查未通过，请先修复后再运行。"
    echo "══════════════════════════════════════════"
    exit 1
fi

# ---- 设置目录权限 ----
echo "▸ 设置目录权限..."
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
echo ""

# ---- 生成应用密钥（首次） ----
grep -q "^APP_KEY=.\+" .env || { echo "▸ 生成应用密钥..."; php artisan key:generate --force; echo ""; }

# ---- 检测数据库状态 ----
echo "▸ 检测数据库状态..."
if [ -z "$MYSQL" ]; then
    echo "  ⚠ 无 mysql 客户端，无法检测库状态，直接执行迁移。"
    DB_MODE="unknown"
else
    # 库是否存在
    DB_EXISTS=$("$MYSQL" --defaults-extra-file="$CNF_FILE" -h "$DB_HOST" -P "$DB_PORT" -BN -e "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}'" 2>/dev/null | head -1)
    if [ -z "$DB_EXISTS" ]; then
        echo "  • 数据库 ${DB_NAME} 不存在，将创建空库。"
        "$MYSQL" --defaults-extra-file="$CNF_FILE" -h "$DB_HOST" -P "$DB_PORT" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" 2>/dev/null
        TABLE_COUNT=0
    else
        TABLE_COUNT=$("$MYSQL" --defaults-extra-file="$CNF_FILE" -h "$DB_HOST" -P "$DB_PORT" -BN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'" 2>/dev/null)
        TABLE_COUNT=${TABLE_COUNT:-0}
    fi

    if [ "$TABLE_COUNT" -eq 0 ] 2>/dev/null; then
        DB_MODE="fresh"
        echo "  • 数据库为空（0 张表）→ 全新部署模式"
    else
        DB_MODE="existing"
        echo "  • 数据库已有 ${TABLE_COUNT} 张表 → 增量迁移模式"
    fi
fi
echo ""

# ---- 全新库：可选择导入完整快照一键复刻 ----
if [ "$DB_MODE" = "fresh" ]; then
    SNAPSHOT_DIR="storage/app/deploy_snapshot"
    LATEST_FULL=$(ls -1t "${SNAPSHOT_DIR}"/zcgl_full_*.sql 2>/dev/null | head -1)
    if [ -n "$LATEST_FULL" ] && [ -n "$MYSQL" ]; then
        echo "──────────────────────────────────────────"
        echo "  检测到打包时导出的完整数据库快照："
        echo "    $(basename "$LATEST_FULL") ($(du -h "$LATEST_FULL" | cut -f1))"
        echo ""
        echo "  全新空库有两种建库方式："
        echo "    [1] 导入完整快照：结构+数据一键复刻，与开发环境完全一致（推荐）"
        echo "    [2] 用 migrations 建空结构：不含任何数据，干净起步"
        echo "──────────────────────────────────────────"
        read -p "选择建库方式 [1/2]（默认 1）: " CHOICE
        CHOICE=${CHOICE:-1}
        if [ "$CHOICE" = "1" ]; then
            echo "▸ 导入完整快照到 ${DB_NAME} ..."
            "$MYSQL" --defaults-extra-file="$CNF_FILE" -h "$DB_HOST" -P "$DB_PORT" "$DB_NAME" < "$LATEST_FULL"
            echo "✓ 完整快照导入完成，结构与数据已复刻。"
            echo ""
            echo "▸ 校验结构一致性..."
            php artisan deploy:diff
            echo ""
            echo "▸ 清除应用缓存..."
            php artisan cache:clear 2>/dev/null || true
            php artisan view:clear 2>/dev/null || true
            php artisan config:clear 2>/dev/null || true
            echo ""
            echo "══════════════════════════════════════════"
            echo "  ✓ 部署完成！已从快照复刻完整环境。"
            echo "══════════════════════════════════════════"
            exit 0
        else
            echo "▸ 将使用 migrations 建空结构。"
        fi
    else
        echo "  ⚠ 未找到完整快照（storage/app/deploy_snapshot/zcgl_full_*.sql），将用 migrations 建结构。"
    fi
fi

# ---- 执行迁移（增量/全新均安全） ----
echo "▸ 查看数据库迁移状态..."
php artisan deploy:status 2>/dev/null || php artisan migrate:status
echo ""

if [ "$DB_MODE" = "existing" ]; then
    echo "▸ 检测结构漂移（对比实际库 vs migrations）..."
    php artisan deploy:diff || true
    echo ""
fi

echo "▸ 执行数据库迁移（自动备份）..."
php artisan deploy:migrate --force
echo ""

# ---- 清除缓存 ----
echo "▸ 清除应用缓存..."
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
echo ""

# ---- 优化 ----
echo "▸ 优化应用..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
echo ""

echo "══════════════════════════════════════════"
echo "  ✓ 部署完成！"
echo "══════════════════════════════════════════"
echo ""
echo "数据库迁移备份位置: storage/app/backups/deploy/"
echo "结构漂移检测: php artisan deploy:diff"
echo ""
