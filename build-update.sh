#!/bin/bash
# 系统更新包构建脚本
# 用法: ./build-update.sh "v1.0.1" "更新说明"
# 会生成更新包到桌面

set -e
cd "$(dirname "$0")"

VERSION="${1:-v1.0.0}"
DESC="${2:-系统更新}"
TMPDIR=$(mktemp -d)
PACKAGE="update-${VERSION}.tar.gz"
DESKTOP="/Users/xiang/Desktop"

echo "=== 构建更新包 ${VERSION} ==="

# 1. 创建 manifest
cat > "$TMPDIR/manifest.json" << MANIFEST
{
  "version": "${VERSION}",
  "desc": "${DESC}",
  "date": "$(date +%Y-%m-%d)"
}
MANIFEST

# 2. 复制变更文件（手动指定，或使用 git diff）
mkdir -p "$TMPDIR/files"
rsync -a --exclude='.git' --exclude='node_modules' --exclude='storage/logs/*.log' \
      --exclude='storage/framework/cache' --exclude='storage/framework/views' \
      --exclude='*.tar.gz' --exclude='.DS_Store' \
      app routes resources config database public \
      artisan composer.json \
      "$TMPDIR/files/"

# 3. 复制新迁移文件
mkdir -p "$TMPDIR/migrations"
if [ -d "database/migrations" ]; then
  cp database/migrations/*.php "$TMPDIR/migrations/" 2>/dev/null || true
fi

# 4. 打包
tar -czf "$PACKAGE" -C "$TMPDIR" manifest.json files/ migrations/
mv "$PACKAGE" "$DESKTOP/"
rm -rf "$TMPDIR"

echo "=== 完成：${DESKTOP}/${PACKAGE} ==="
ls -lh "$DESKTOP/$PACKAGE"
