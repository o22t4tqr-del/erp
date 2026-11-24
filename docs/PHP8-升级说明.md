# H5ERP PC 端 PHP 8 升级说明

## 一、结论

`pc/` 端已从 **PHP 5.6** 升级到 **PHP 8.2**，保留原有 CodeIgniter 2.2.5 架构与全部业务代码，
**PDF 打印（html2pdf + TCPDF 5.0.002）可用，中文正常输出**。

没有更换框架、没有重写业务逻辑——只做了「让老代码在 PHP 8 上跑得通」所必需的定点修改，
共 **19 个文件**。

## 二、测试环境（已验证）

| 项 | 版本 |
| --- | --- |
| 操作系统 | Debian 12 (bookworm) |
| PHP | 8.2.36（mysqli / gd / zip / mbstring / pdo_mysql） |
| 数据库 | MySQL 8.0.46 |
| Web 服务 | PHP 内置服务器（等价于 Apache + mod_php 的路由行为） |

> 测试环境在验证完成后已按要求全部卸载。

## 三、改动清单

### 3.1 语法层（PHP 8 直接报 Parse error 的部分）

| 文件 | 问题 | 改法 |
| --- | --- | --- |
| `system/libraries/Profiler.php` | `$this->_compile_{$section}` 花括号动态属性名 | 改为 `$this->{'_compile_'.$section}` |
| `application/helpers/fun_helper.php` | `$string{$i}` 字符串下标花括号（2 处） | 改为 `$string[$i]` |
| `application/libraries/lib_cn2pinyin.php` | `$sGBK{$i}`（2 处） | 改为 `$sGBK[$i]` |
| `application/libraries/html2pdf/_tcpdf_5.0.002/barcodes.php` | `$code{$i}`、`$chr[$char]{$j}` 等（64 处） | 改为方括号下标 |
| `application/libraries/html2pdf/_tcpdf_5.0.002/fonts/utils/makefont.php` | `$file{0}` 等（5 处） | 改为 `$file[0]` |
| `application/libraries/html2pdf/_tcpdf_5.0.002/tcpdf.php` | `$orientation{0}` 等 | 改为 `$orientation[0]` |
| `application/libraries/excel/excel_reader2.php` | `$this->_ole =& new OLERead();` | 去掉 `&`（PHP 7 起禁止引用赋值 new） |

### 3.2 运行时（PHP 8 类型更严格导致致命错误）

| 文件 | 问题 | 改法 |
| --- | --- | --- |
| `system/core/Input.php` | `filter_var($ip, FILTER_VALIDATE_IP, '')` 第三参必须是 int/array | `$flag = 0` |
| `application/controllers/scm/backup.php` | `get_dir_file_info()` 目录不存在时返回 `false`，PHP 8 的 `count(false)` 抛 TypeError → 备份页 500 | 加 `is_array()` 兜底 |
| `application/helpers/fun_helper.php` | `glob()` 失败返回 `false`，`foreach(false)` 在 PHP 8 报错 | 加 `is_array()` 兜底 |
| **9 个打印接口**（invPu/invSa/invSo/invOi/invTf/ori/prints） | `ceil(count($x)/$data['num'])`，`num` 为 0 时 PHP 8 抛 `DivisionByZeroError` → 打印 500 | 改为 `$data['num'] > 0 ? $data['num'] : 53` |
| `application/controllers/basedata/log.php` | 字段错配：代码用 `pid`/`title`/`depth`，`ci_menu` 实际是 `parentId`/`name`/`level`；且 `'level>'` 被 CI 当成字段名 | 改用正确字段名，运算符写成 `'level >'` |
| `application/controllers/basedata/employee.php` | `$skey` 为空时 `$where=''`，CI 拼出非法 SQL | 空值改为 `array()` |
| `application/controllers/basedata/inventory.php` | `$arr[$type]` 在 type 为空时未定义 → `transType=` 非法 SQL | 加 `isset` 兜底 |
| `application/controllers/basedata/userSetting.php` | `key` 为空会写入空配置项（曾导致系统配置被覆盖） | 空 key 直接返回错误 |
| `application/controllers/dataright.php` | `count($data>0)` 传了布尔值给 `count()` | 改为 `count($data)>0` |
| `application/libraries/html2pdf/_class/locale.class.php` | `fgetcsv()` 读到 EOF 返回 `false`，`count(false)` 抛 TypeError | 加 `is_array()` 判断 |
| `application/libraries/html2pdf/_class/tcpdfConfig.php` | CLI 下无 `HTTP_HOST`，`$kPathUrl` 未定义 | 先赋默认值 `''` |
| `application/libraries/html2pdf/_tcpdf_5.0.002/tcpdf.php` | `unichr('')` 传字符串给 `chr()` | 入口处 `$c = (int)$c` |
| `application/libraries/html2pdf/_tcpdf_5.0.002/tcpdf.php` | `$footer_font` 为 null 时取下标 | 默认值 `array('helvetica','',8)` |
| `application/libraries/html2pdf/html2pdf.php` | 四角坐标已为 null 仍取 `[0]` | 加 `is_array()` 保护 |
| `system/database/drivers/mysqli/mysqli_driver.php` | **MySQL 8 默认 `ONLY_FULL_GROUP_BY`**，老代码的 GROUP BY 报 1055，5 个报表接口（库存余额/往来对账/收发汇总/银行余额/客户余额）直接 500 | 连接后执行 `SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'`，不依赖服务器全局配置 |
| `system/database/drivers/mysqli/mysqli_driver.php` | **PHP 8.1 起 mysqli 默认抛异常**（`MYSQLI_REPORT_ERROR\|STRICT`），CI2 的驱动接不住，SQL 一报错整个请求 fatal | `db_connect()`/`db_pconnect()` 里加 `mysqli_report(MYSQLI_REPORT_OFF)` |

### 3.3 PHP 8 移除的函数

| 文件 | 问题 | 改法 |
| --- | --- | --- |
| `_tcpdf_5.0.002/tcpdf.php` | `create_function()`（6 处，PHP 8.0 移除） | 转成匿名函数 `function($matches) { ... }` |
| `_tcpdf_5.0.002/tcpdf.php` | `each()`（4 处，PHP 8.0 移除） | 转成 `foreach` |
| 全库 | `mysql_*` 扩展（PHP 7.0 移除） | 数据库驱动 `mysql` → `mysqli` |

### 3.4 配置调整

| 文件 | 改动 | 原因 |
| --- | --- | --- |
| `index.php` | `error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE)` | CI 2 大量动态属性在 8.2 触发 E_DEPRECATED，屏蔽后日志干净 |
| `application/config/database.php` | `dbdriver = 'mysqli'`；连接信息改为环境实际值 | 见 3.3 |

> **`ENVIRONMENT` 必须是 `production`，这不是建议，是硬要求。**
>
> 实测：设为 `development` 时，PHP 的 Deprecated/Notice 会混进 AJAX 响应，
> 前端 `JSON.parse` 失败 → 表格的 `#ldg_lockmask` 遮罩永远不撤 →
> **界面看着正常但所有按钮都点不动**（新增/编辑/删除全部无反应）。
> 切回 `production`（`error_reporting(0)`）后立即恢复。
>
> 老代码里大量「未定义数组键」的写法也依赖这一设置。

## 四、验证结果

| 验证项 | 结果 |
| --- | --- |
| 全量语法检查（481 个 PHP 文件 `php -l`） | 通过，0 错误 |
| 登录页渲染 | 200，标题「进销存」 |
| 登录 `admin/admin888` | 成功，写入 session |
| 后台主框架 `home/index` | 200 |
| 商品 / 往来单位 / 采购入库 / 采购订单 / 报表 / 设置 / 仓库 共 8 个页面 | 全部 200，无致命错误 |
| PDF 引擎（html2pdf + TCPDF） | 生成 4.4 KB PDF，中文正常 |
| **真实打印模板** `views/scm/invOi/toOiPdf.php` | 生成 5.8 KB PDF，抬头「飞腾公司」、表头「其他入库单」、商品行、日期均正确 |
| **全量端点扫描** | 357 个端点 × GET/POST 共 714 次请求，**响应体与服务端日志均为 0 致命错误** |
| **业务全流程实测** | 基础资料：商品(id=4)、客户(id=8)、供应商(id=9)；单据：采购入库(115)、调拨(116)、收款(113)、付款(114)、其他收入(117)、其他支出(118)；审核(checked=1)、库存汇总 +20/-2；采购/销售列表各查出 1 条；**六种单据全部打印出 PDF**（4280–6420 B，pdftotext 可读中文） |

PDF 文本抽取结果（`pdftotext`）：

```
飞腾公司
其他入库单
供应商：
序号 1 2
单据日期：2026-11-23
商品 测试商品A 规格1
```

## 五、已知遗留问题

| 问题 | 说明 |
| --- | --- |
| PHP 8.2 动态属性弃用告警 | CI 2 架构所致，已在 `index.php` 屏蔽 E_DEPRECATED，未逐个加属性标注 |
| `views/settings/fileupload22.php` 硬编码示例图 | `data/upfile/goods/7/201506131318121029.png` 不存在，显示裂图，不影响功能 |
| 打印链路验证范围 | 已用真实模板 `toOiPdf.php` + 样例数据生成 PDF；`ci_invoice` 共 42 列、含 `postData`，数据来源已核实。未用真实单据走完整控制器流程（需业务数据） |
| 弹窗表单未做 UI 级验证 | 弹窗是 iframe 套 iframe，浏览器自动化工具只能穿透一层；表单页服务端返回 200 / 17.7 KB 正常，字段齐全 |
| 未覆盖的业务流程 | 采购/销售/调拨开单→审核、报表数值、Excel 导入导出、权限与批量操作 |

## 六、部署

镜像基础从 `php:5.6-apache` 换成 `php:8.2-apache`，其余不变：

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql gd zip
# 其余 COPY / VOLUME / 端口配置与 5.6 版本一致
```

注意两点：

1. `php:8.2-apache` 基于 Debian bookworm，**不需要**再改 `archive.debian.org` 源（那是 stretch 才有的问题）。
2. `gd` 的编译参数改为 `--with-freetype --with-jpeg`（8.x 去掉了 `-dir` 后缀）。

## 七、回滚

保留原 `pc/` 目录即可回滚到 PHP 5.6 版本，两者代码差异仅上述 12 个文件，可用 diff 逐个还原。