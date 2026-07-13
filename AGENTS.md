# 项目协作约定 (AGENTS.md)

本文件供所有协作代理（Codex / Claude / 其他 AI）在该项目中永久遵循。

## 数据库结构修改规则（强制）

**任何涉及数据库结构变更的操作，必须使用 Laravel Migration 完成，不得直接手动改表结构。**

- 新增字段：`php artisan make:migration add_xxx_to_yyy_table --table=yyy`
- 新建表：`php artisan make:migration create_xxx_table --create=xxx`
- 迁移文件放在 `database/migrations/`，文件名带时间戳前缀
- 迁移必须同时实现 `up()` 和 `down()`
- 字段需写中文 `comment()` 说明用途
- 执行：`php artisan migrate`
- 回滚：`php artisan migrate:rollback`

## 项目技术栈

- Laravel 10 + Blade + Tailwind CSS + Alpine.js
- 数据库：MySQL（本地 XAMPP，生产可切换）
- 前端构建：Vite
- 权限：`auth()->user()->isAdmin()` 区分管理员

## 本地环境注意

- XAMPP 的 Apache 以 `daemon` 用户运行，CLI 以 `xiang` 运行
- `public/index.php` 和 `artisan` 已加 `umask(0000)`，避免交叉写入权限冲突
- storage 目录需保持可写（777 或 daemon 可写）

## 资产管理模块核心概念

- **自有编码 (asset_code)**：资产主键标识，批量导入以此匹配已有数据
- **财务编码 (financial_code)**：可后期补充，纯财务编码变更不生成调拨单
- **调拨单 (TransferOrder)**：记录资产字段变更，`draft_data` 存 asset_ids/original/changes 快照
- **AssetLog**：字段级变更历史，`reference_no` 关联调拨单号
- **ImportLog**：批量导入操作日志，记录 changed_details/errors/关联调拨单
