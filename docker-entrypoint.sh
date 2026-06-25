#!/bin/bash
set -e

cd /var/www/html

# 如果 .env 不存在，从模板创建
if [ ! -f .env ]; then
    cp .env.example .env
    php -r "
        \$env = file_get_contents('.env');
        \$env = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=', \$env);
        \$env = preg_replace('/^DB_HOST=.*/m', 'DB_HOST=' . (getenv('DB_HOST') ?: '127.0.0.1'), \$env);
        \$env = preg_replace('/^DB_PORT=.*/m', 'DB_PORT=' . (getenv('DB_PORT') ?: '3306'), \$env);
        \$env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=' . (getenv('DB_DATABASE') ?: 'zcgl'), \$env);
        \$env = preg_replace('/^DB_USERNAME=.*/m', 'DB_USERNAME=' . (getenv('DB_USERNAME') ?: 'root'), \$env);
        \$env = preg_replace('/^DB_PASSWORD=.*/m', 'DB_PASSWORD=' . (getenv('DB_PASSWORD') ?: ''), \$env);
        file_put_contents('.env', \$env);
    "
    php artisan key:generate --force
fi

# 启动 Apache
exec apache2-foreground
