# 资产管理系统 - 内网部署指南

## 环境要求

- PHP 8.2+（需启用 pdo_mysql、mbstring、bcmath 扩展）
- Nginx + PHP-FPM
- MySQL 5.7+ 或 MariaDB 10.6+
- Composer
- Node.js（仅编译前端资源时需要，可选）

## 部署步骤

### 1. 复制项目文件

将项目文件复制到内网服务器目标目录，排除以下目录：

```bash
rsync -av --exclude='node_modules' --exclude='vendor' --exclude='.git' \
      --exclude='storage/logs/*.log' --exclude='storage/framework/cache/*' \
      --exclude='storage/framework/views/*' \
      /path/to/zcgl/ user@server:/var/www/zcgl/
```

### 2. 安装依赖

```bash
cd /var/www/zcgl
composer install --no-dev --optimize-autoloader
```

### 3. 编译前端资源（如服务器无 Node.js，在本机执行）

```bash
npm install
npm run build
```

将生成的 `public/build/` 目录复制到服务器。

### 4. 配置环境变量

```bash
cp .env.example .env
```

编辑 `.env`，修改以下关键配置：

```env
APP_NAME=资产管理系统
APP_ENV=production
APP_KEY=（运行 php artisan key:generate 自动生成）
APP_DEBUG=false
APP_URL=http://你的内网IP或域名

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zcgl
DB_USERNAME=你的数据库用户名
DB_PASSWORD=你的数据库密码

# MySQL 客户端工具路径（备份/还原功能需要）
# Docker 容器内通常无需配置（已在 PATH 中）
# Linux 标准路径：
MYSQL_BIN_DIR=/usr/bin
# XAMPP：
# MYSQL_BIN_DIR=/opt/lampp/bin
```

生成应用密钥：

```bash
php artisan key:generate
```

### 5. 设置文件权限

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. 配置 Nginx

将 `nginx.conf.example` 复制到 Nginx 配置目录：

```bash
cp nginx.conf.example /etc/nginx/sites-available/zcgl
ln -s /etc/nginx/sites-available/zcgl /etc/nginx/sites-enabled/
nginx -t          # 测试配置
systemctl reload nginx
```

根据实际情况修改 `server_name` 和 `root` 路径。

### 7. 运行安装向导

访问网站，系统会自动跳转到 `/install` 安装向导：

1. 输入 MySQL 连接信息（主机、端口、数据库名、用户名、密码）
2. 系统自动建表并创建数据库结构
3. 创建管理员账号（默认角色为 admin）
4. 安装完成，跳转到登录页

### 8. 验证

- 用管理员账号登录
- 检查系统管理 → 数据库状态页面
- 测试各模块功能（资产、耗材、打印、备份等）

## 数据字典

安装完成后，以下字典数据会自动创建：

- **部门**：财务科、人事科、综合税、所得税、流转税、稽查局、信息中心、办公室
- **资产类别**：台式计算机（国产/非国产）、打印机、交换机、显示器、服务器、路由器、其他
- **资产状态**：在用、闲置、维修、借用、待报废、报废
- **耗材分类**：办公文具、IT配件、清洁用品、办公耗材、劳保用品、其他
- **耗材单位**：个、支、包、箱、瓶、卷、套、台、件、根、盒
- **供应商**：京东、淘宝、文达办公、华夏商城、其他供应商

## 演示数据（可选）

如需创建耗材演示数据（含入库单、领用记录、盘点单），运行：

```bash
php artisan db:seed --class=ConsumableDemoSeeder
```

## 备份与还原

系统管理页面提供数据库备份/还原功能。需确保：

1. 服务器已安装 `mysqldump` 和 `mysql` 命令行工具
2. 在 `.env` 中配置 `MYSQL_BIN_DIR`（如果不在系统 PATH 中）
3. 备份文件存储在 `storage/backups/` 目录

## 常见问题

### 安装向导跳不出来

检查 `storage/app/installed` 文件是否存在。如果存在，删除后重新访问：

```bash
rm -f storage/app/installed
```

### 备份失败：找不到 mysqldump

在 `.env` 中配置 `MYSQL_BIN_DIR`，例如：

```env
MYSQL_BIN_DIR=/usr/bin
```

然后清除配置缓存：

```bash
php artisan config:clear
```

### 页面样式丢失

确保已运行 `npm run build` 生成 `public/build/` 目录，或检查 Nginx 静态资源配置。

## 内网迁移部署（已有数据）

如果你的内网服务器已有运行中的系统和数据，迁移新版本不会丢失数据。

### 工作原理

Laravel 使用 `migrations` 数据库表记录已执行的迁移。部署时 `php artisan migrate` 只会执行数据库中尚未记录的新迁移，不会重复执行已有的。

### 迁移部署步骤

1. **复制代码**到内网服务器（不覆盖 `storage/app/version.json` 和 `.env`）
2. **安装依赖**：`composer install --no-dev --optimize-autoloader`
3. **查看迁移状态**：`php artisan deploy:status`
4. **执行迁移**：`php artisan deploy:migrate --force`（自动备份后执行）
5. **清除缓存**：`php artisan cache:clear && php artisan config:clear`

或一键执行：

```bash
./deploy-to-production.sh
```

### 新建迁移的安全写法

新增迁移时，使用幂等检查确保在已有数据的数据库上安全执行：

```php
public function up()
{
    // 安全添加列：不存在时才添加
    Schema::table('assets', function (Blueprint $table) {
        if (!Schema::hasColumn('assets', 'new_field')) {
            $table->string('new_field')->nullable();
        }
    });

    // 安全创建表：不存在时才创建
    if (!Schema::hasTable('new_table')) {
        Schema::create('new_table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
}
```

### 安全回滚

迁移前会自动备份到 `storage/app/backups/deploy/`。如需恢复：

```bash
mysql -u root -p zcgl < storage/app/backups/deploy/zcgl_2026-07-10_123456.sql
```
