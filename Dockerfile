# H5ERP PC 端（进销存管理后台）—— PHP 8.2 + Apache，适配 blitz.cloud 单容器部署
#
# 这个镜像只跑应用，数据库是外部的（blitz.cloud 托管 MariaDB，或你自己的 MySQL/MariaDB）：
#   连接参数由平台注入：DATABASE_URL=mysql://user:pass@host:3306/db
#   首次启动若库是空的，db-init.php 会自动导入 erp.sql；库里已有 ci_admin 表则跳过。
#
# 本地跑：docker build -t h5erp . && docker run -p 8080:8080 \
#         -e DATABASE_URL=mysql://root:pass@host.docker.internal:3306/erp h5erp
FROM php:8.2-apache

ENV TZ=Asia/Shanghai
# 监听端口：平台（blitz.cloud）按 PORT / EXPOSE 识别，两处都指明 8080
ENV PORT=8080

# 扩展：gd（图片/缩略图）、mysqli + pdo_mysql（数据库）、zip（Excel 导入导出）
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip; \
    # 官方镜像已带 mbstring；万一以后不带，这里补上（代码里有 mb_convert_encoding）
    if ! php -m | grep -qi '^mbstring$'; then \
        apt-get update; \
        apt-get install -y --no-install-recommends libonig-dev; \
        docker-php-ext-install mbstring; \
    fi; \
    rm -rf /var/lib/apt/lists/*

# 站点根 = pc/，index.php 落在 /var/www/html/index.php
COPY pc/ /var/www/html/
COPY deploy/prepend.php /var/www/html/prepend.php
COPY deploy/download.htaccess /var/www/html/data/download/.htaccess
COPY deploy/deny.htaccess /var/www/html/application/.htaccess
COPY deploy/deny.htaccess /var/www/html/system/.htaccess
COPY deploy/deny.htaccess /var/www/html/application/cache/.htaccess

# 启动脚本：等库、空库导入 erp.sql
COPY erp.sql /usr/local/share/erp.sql
COPY deploy/db-init.php /usr/local/bin/db-init.php
COPY deploy/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN set -eux; \
    chmod +x /usr/local/bin/docker-entrypoint.sh; \
    # 上传目录 / 数据库备份目录搬到 /data（VOLUME，容器重建不丢），站点里只留软链
    mkdir -p /data/upfile/goods /data/backup /opt/erp-seed \
             /var/www/html/application/cache /var/www/html/application/logs; \
    cp -a /var/www/html/data/upfile /opt/erp-seed/upfile; \
    rm -rf /var/www/html/data/upfile; \
    ln -s /data/upfile /var/www/html/data/upfile; \
    ln -s /data/backup /var/www/html/data/backup; \
    chown -R www-data:www-data /data /opt/erp-seed /var/www/html; \
    # PHP 运行参数
    { \
      echo 'date.timezone=Asia/Shanghai'; \
      echo 'upload_max_filesize=64M'; \
      echo 'post_max_size=64M'; \
      echo 'max_execution_time=120'; \
      echo 'memory_limit=256M'; \
      echo 'display_errors=Off'; \
      echo 'log_errors=On'; \
      echo 'auto_prepend_file=/var/www/html/prepend.php'; \
    } > /usr/local/etc/php/conf.d/zz-erp.ini; \
    # .htaccess 必须生效：挡住 application/、system/ 源码与 data/download/*.sql
    sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf; \
    a2enmod rewrite; \
    a2dismod -f autoindex status >/dev/null 2>&1 || true; \
    # 单容器内存有限（blitz 免费套餐 128MB 起），把 prefork 压到最小可用
    { \
      echo '<IfModule mpm_prefork_module>'; \
      echo '    StartServers             2'; \
      echo '    MinSpareServers          1'; \
      echo '    MaxSpareServers          3'; \
      echo '    MaxRequestWorkers       12'; \
      echo '    MaxConnectionsPerChild 300'; \
      echo '</IfModule>'; \
    } > /etc/apache2/mods-available/mpm_prefork.conf; \
    # blitz.cloud 用 PORT 注入端口，默认 8080
    sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf; \
    sed -ri 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf; \
    echo 'ServerName localhost' > /etc/apache2/conf-enabled/zz-erp.conf; \
    echo 'ServerTokens Prod' >> /etc/apache2/conf-enabled/zz-erp.conf

VOLUME ["/data"]
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]