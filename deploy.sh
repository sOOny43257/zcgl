#!/bin/bash
# 资产管理系统 - 快速提交脚本
# 用法: ./deploy.sh "提交说明"
# 如果不传参数，会使用默认说明

cd "$(dirname "$0")"

MESSAGE="${1:-更新: $(date '+%Y-%m-%d %H:%M:%S')}"

echo "📦 正在暂存所有更改..."
git add .

echo "📝 正在提交: $MESSAGE"
git commit -m "$MESSAGE"

# push 由 post-commit hook 自动完成
