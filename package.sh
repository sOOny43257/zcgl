#!/bin/bash
# =============================================================
# 资产管理系统 - 打包迁移脚本
# =============================================================
# 用法: ./package.sh
#
# 将网站打包为 tar.gz，包含 vendor/ 和部署脚本，
# 方便迁移到内网服务器（无需联网下载依赖）。
# =============================================================

set -e
cd "$(dirname "$0")"

DESKTOP="/Users/$(whoami)/Desktop"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
OUTPUT="${DESKTOP}/zcgl_${TIMESTAMP}.tar.gz"

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║     资产管理系统 - 打包迁移               ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# 校验关键目录
if [ ! -d vendor ]; then
    echo "✗ vendor/ 目录不存在！"
    echo "  请先运行 composer install --no-dev"
    exit 1
fi

if [ ! -d public/build ]; then
    echo "⚠ public/build/ 不存在，将跳过前端构建产物"
    echo "  内网可能无法正常显示页面"
else
    echo "✓ public/build/ 前端构建产物已就绪"
fi

if [ -f deploy-to-production.sh ]; then
    echo "✓ deploy-to-production.sh 部署脚本已就绪"
fi

echo ""
echo "▸ 正在打包（排除 .git、node_modules、日志、缓存）..."
echo "  输出文件: ${OUTPUT}"
echo ""

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
    --exclude='.phpunit.result.cache' \
    --exclude='composer.phar' \
    --exclude='*.tar.gz' \
    --exclude='.DS_Store' \
    -C "$(pwd)" .

echo ""
SIZE=$(du -h "$OUTPUT" | cut -f1)
echo "══════════════════════════════════════════"
echo "  ✓ 打包完成！"
echo "══════════════════════════════════════════"
echo ""
echo "   文件: ${OUTPUT}"
echo "   大小: ${SIZE}"
echo ""
echo "内网部署步骤:"
echo "  1. 将 tar.gz 文件复制到内网服务器"
echo "  2. 解压: tar -xzf ${OUTPUT##*/} -C /var/www/zcgl"
echo "  3. 配置 .env"
echo "  4. 运行: cd /var/www/zcgl && bash deploy-to-production.sh"
echo ""
