#!/bin/bash
# =============================================================
# 资产管理系统 - 内网服务器部署脚本
# =============================================================
# 用法: ./deploy-to-production.sh
#
# 此脚本用于将代码部署到内网服务器，并安全地更新数据库结构。
# 不会丢失内网已有的数据。
#
# 前提条件（内网服务器无需联网）:
#   1. PHP 8.2+（含 pdo_mysql、mbstring、bcmath 扩展）
#   2. MySQL / MariaDB 已运行
#   3. mysqldump 可用（用于自动备份，MySQL 自带）
#   4. 代码已复制到服务器，包括 vendor/ 目录
# =============================================================

set -e
cd "$(dirname "$0")"

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║     资产管理系统 - 内网部署脚本           ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# ---- 前置检查 ----
ERRORS=0

# PHP
if ! command -v php &>/dev/null; then
    echo "✗ PHP 未安装"
    ERRORS=1
else
    PHP_VER=$(php -r 'echo PHP_VERSION;')
    echo "✓ PHP {$PHP_VER}"
    # 扩展检查
    for ext in pdo_mysql mbstring bcmath; do
        if php -m | grep -qi "^${ext}$"; then
            echo "  ✓ 扩展 {$ext}"
        else
            echo "  ✗ 缺少扩展 {$ext}"
            ERRORS=1
        fi
    done
fi

# vendor 目录
if [ -d vendor ] && [ -f vendor/autoload.php ]; then
    echo "✓ vendor/ 目录存在（无需联网安装依赖）"
else
    echo "✗ vendor/ 目录缺失"
    echo "  请从开发机复制 vendor/ 目录到服务器，或在有网环境运行 composer install"
    ERRORS=1
fi

# .env
if [ ! -f .env ]; then
    echo "✗ .env 文件不存在"
    echo "  请复制 .env.example 并修改数据库连接配置"
    ERRORS=1
else
    echo "✓ .env 文件存在"
fi

# MySQL 连接（尝试检测）
DB_NAME=$(grep -E "^DB_DATABASE=" .env 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'" || true)
DB_HOST=$(grep -E "^DB_HOST=" .env 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'" || echo "127.0.0.1")
DB_PORT=$(grep -E "^DB_PORT=" .env 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'" || echo "3306")

if [ -n "$DB_NAME" ]; then
    echo "  数据库: ${DB_NAME} @ ${DB_HOST}:${DB_PORT}"
fi

# mysqldump
MYSQL_BIN_DIR=$(grep -E "^MYSQL_BIN_DIR=" .env 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'" || true)
if [ -n "$MYSQL_BIN_DIR" ] && [ -x "${MYSQL_BIN_DIR}/mysqldump" ]; then
    echo "✓ mysqldump: ${MYSQL_BIN_DIR}/mysqldump"
elif command -v mysqldump &>/dev/null; then
    echo "✓ mysqldump: $(which mysqldump)"
else
    echo "⚠ mysqldump 未找到（自动备份将跳过，不影响迁移）"
    echo "  如需自动备份，请在 .env 中设置 MYSQL_BIN_DIR=/path/to/mysql/bin"
fi

echo ""

if [ $ERRORS -ne 0 ]; then
    echo "══════════════════════════════════════════"
    echo "  ✗ 上述检查未通过，请先修复后再运行。"
    echo "══════════════════════════════════════════"
    exit 1
fi

# ---- 开始部署 ----

# 1. 安装/更新依赖（内网环境下 vendor/ 已存在则秒完成）
echo "▸ 校验 Composer 依赖..."
if [ -f composer.phar ]; then
    php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
elif command -v composer &>/dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
else
    echo "  ⚠ Composer 不可用，跳过依赖校验（vendor/ 已存在则不影响运行）"
fi
echo ""

# 2. 设置目录权限
echo "▸ 设置目录权限..."
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
echo ""

# 3. 生成应用密钥（首次部署时）
if ! grep -q "^APP_KEY=.\+" .env; then
    echo "▸ 生成应用密钥..."
    php artisan key:generate --force
    echo ""
fi

# 4. 查看迁移状态
echo "▸ 检查数据库迁移状态..."
php artisan deploy:status
echo ""

# 5. 执行迁移（安全，自动备份）
echo "▸ 执行数据库迁移（自动备份）..."
php artisan deploy:migrate --force
echo ""

# 6. 清除缓存
echo "▸ 清除应用缓存..."
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
echo ""

# 7. 优化
echo "▸ 优化应用..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
echo ""

echo "══════════════════════════════════════════"
echo "  ✓ 部署完成！"
echo "══════════════════════════════════════════"
echo ""
echo "备份位置: storage/app/backups/deploy/"
echo ""
