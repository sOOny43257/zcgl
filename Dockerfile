FROM php:8.2-apache

# 换国内 Debian 源加速
RUN sed -i 's/deb.debian.org/mirrors.ustc.edu.cn/g' /etc/apt/sources.list.d/debian.sources 2>/dev/null || \
    sed -i 's/deb.debian.org/mirrors.ustc.edu.cn/g' /etc/apt/sources.list 2>/dev/null || true

# 安装系统依赖 + PHP 扩展
RUN apt-get update && apt-get install -y libonig-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring bcmath fileinfo \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# 设置工作目录
WORKDIR /var/www/html

# 复制应用代码
COPY . /var/www/html/

# 复制 entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# 创建必要目录并设置权限
RUN mkdir -p storage/framework/{cache,views,sessions} \
    storage/framework/cache/data \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 删除安装标记（新环境需走安装向导）
RUN rm -f storage/app/installed

# Apache 配置
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/zcgl.conf \
    && a2enconf zcgl

# 设置 Apache 根目录
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
