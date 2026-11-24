# Docker 运行说明

> 线上部署请看 **[DEPLOY-blitz.md](DEPLOY-blitz.md)**（blitz.cloud 单容器 + 平台托管 MariaDB）。
> 本文件只讲本地怎么把镜像跑起来。

镜像里的应用**只跑 PHP + Apache**，数据库永远是外部的（blitz 托管库 / 你自己的 MySQL）。

## 只跑应用（和线上一致）

```bash
docker build -t h5erp .
docker run -d --name h5erp -p 8080:8080 \
  -e DATABASE_URL='mysql://root:pass@host.docker.internal:3306/erp' \
  -v h5erp-data:/data \
  h5erp
```

- `DATABASE_URL` 也可以换成 `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD`
- 空库时启动脚本会自动导入 `erp.sql`；已有 `ci_admin` 表则跳过
- 上传文件在 `/data` 卷里
- 访问 <http://localhost:8080>，admin / admin888

## 应用 + MySQL 8（仅本地调试）

```bash
docker compose up -d --build
```

compose 会起一个 MySQL 8 并导入 `erp.sql`，应用连它。
`sql_mode` 里没有 `ONLY_FULL_GROUP_BY`（老代码的 GROUP BY 写法需要）。

改数据库密码：

```bash
DB_PASSWORD=你的密码 docker compose up -d
```

（只在 `db-data` 卷第一次创建时生效。）

## 排查

```bash
docker logs -f h5erp          # 启动日志里有 [db-init] 字样
docker exec -it h5erp php -v  # PHP 版本
```

- 启动日志停在 `[db-init]`：数据库地址/账号不对，或库不存在
- 页面 500：设 `DB_DEBUG=1` 让 SQL 错误显示出来
- 目录列表/源码泄露：`AllowOverride` 没生效（镜像已设为 All，自建环境要自己开）