#!/bin/sh
# H5ERP 容器入口：适配 blitz.cloud（PORT 由平台注入，数据库是平台托管的外部 MariaDB）
set -eu

PORT="${PORT:-8080}"
echo "[entrypoint] PORT=${PORT}（平台没给就用 8080）"
echo "[entrypoint] 容器里可见的环境变量名：$(env | cut -d= -f1 | sort | tr '\n' ' ')"

# blitz.cloud 用 PORT 告诉应用监听哪个端口（默认 8080）；镜像按 8080 构建，这里按实际值改
if [ "$PORT" != "8080" ]; then
    sed -ri "s/^Listen 8080$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/:8080>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf
    echo "[entrypoint] Apache 改听 ${PORT}"
fi

# /data 是持久卷：首次启动把镜像自带的示例上传文件铺进去
mkdir -p /data/upfile /data/backup
if [ -z "$(ls -A /data/upfile 2>/dev/null || true)" ] && [ -d /opt/erp-seed/upfile ]; then
    cp -a /opt/erp-seed/upfile/. /data/upfile/ 2>/dev/null || true
fi
chown -R www-data:www-data /data

# 等库就绪 + 空库时导入 erp.sql（见 db-init.php）
php /usr/local/bin/db-init.php

exec "$@"