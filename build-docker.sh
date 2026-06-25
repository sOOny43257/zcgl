#!/bin/bash
# 在有 Docker + 互联网的机器上执行此脚本
# 会构建镜像并导出为离线 tar 包

set -e
cd "$(dirname "$0")"

echo "=== 1. 构建应用镜像 ==="
docker build -t zcgl-app:latest .

echo "=== 2. 拉取 MySQL 5.7 镜像 ==="
docker pull mysql:5.7

echo "=== 3. 导出镜像为 tar ==="
docker save -o zcgl-app.tar zcgl-app:latest
docker save -o mysql-5.7.tar mysql:5.7

echo "=== 4. 打包 ==="
PACKAGE="zcgl-docker-$(date +%Y%m%d).tar.gz"
tar -czf "$PACKAGE" \
    zcgl-app.tar \
    mysql-5.7.tar \
    docker-compose.yml \
    docker-entrypoint.sh

rm -f zcgl-app.tar mysql-5.7.tar
echo "=== 完成：$PACKAGE ==="
ls -lh "$PACKAGE"
echo ""
echo "将此文件 + 项目源码复制到目标服务器，然后执行："
echo "  tar -xzf $PACKAGE"
echo "  docker load -i zcgl-app.tar"
echo "  docker load -i mysql-5.7.tar"
echo "  docker-compose up -d"
