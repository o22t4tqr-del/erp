# H5ERP 接口文档（PC 端）

> 版本：PHP 8 升级版　|　框架：CodeIgniter 2.2.5（已适配 PHP 8.2）　|　数据库：MySQL 8

共 **43 个控制器、355 个公开接口**（其中 7 个核心流程已实测，见第二节）。私有方法与内部回调（`validform`、`my_filter` 等）不计入。

## 一、通用约定

| 项 | 说明 |
| --- | --- |
| 基地址 | `http(s)://<域名>/index.php/` |
| 路由格式 | `/index.php/<目录>/<控制器>/<方法>`，根目录控制器无「目录」段 |
| 认证 | 先 `POST login` 登录，服务端写 `ci_session` Cookie；后续请求携带该 Cookie |
| CSRF | 登录接口需带 `token`，值取自登录页下发的 `token` Cookie |
| 请求体 | 多数为 `application/x-www-form-urlencoded`；部分接口读 JSON，见说明列 |
| 响应 | 成功多为 JSON 或纯文本 `1`；失败为纯文本错误提示 |
| 编码 | UTF-8 |

## 二、已实测的业务流程（PHP 8.2 + MySQL 8）

以下每条都在真实环境中跑通，附实测结果，可直接作为 skill 的调用样例。

### 2.1 新增商品

```
POST /index.php/basedata/inventory/add?action=add
Content-Type: application/x-www-form-urlencoded
```

| 字段 | 必填 | 说明 |
| --- | --- | --- |
| number | 是 | 商品编号 |
| name | 是 | 商品名称 |
| categoryId | 是 | 商品类别 id（ci_category 中存在） |
| baseUnitId | 是 | 基本单位 id（ci_unit 中存在） |
| spec | 否 | 规格型号 |
| purPrice / salePrice / wholesalePrice / vipPrice | 否 | 采购价 / 零售价 / 批发价 / 会员价 |
| discountRate1 / discountRate2 | 否 | 折扣率 |
| lowQty / highQty | 否 | 库存下限 / 上限 |
| barCode | 否 | 条码 |
| propertys | 否 | 属性 JSON，默认 `[]` |
| sonGoods | 否 | 组合商品 JSON，默认 `[]` |
| warehouseWarning / warehouseWarningSku | 否 | 默认 0 |
| remark | 否 | 备注 |

实测：`{"status":200,"msg":"success","data":{"id":4}}`

### 2.2 新增客户 / 供应商

```
POST /index.php/basedata/contact/add?action=add&type=-10   ← 客户
POST /index.php/basedata/contact/add?action=add&type=10    ← 供应商
```

| 字段 | 必填 | 说明 |
| --- | --- | --- |
| name | 是 | 名称 |
| number | 是 | 编号 |
| cCategory | 是 | 类别 id，必须 ≥1 |
| type | 是 | **query 参数**，-10 客户 / 10 供应商 |
| cLevel | 否 | 等级，默认 1 |
| taxRate / periodMoney / amount | 否 | 税率 / 期初应付 / 期初余额 |
| linkMans | 否 | 联系人 JSON，默认 `[]` |
| beginDate | 否 | 默认当天 |

实测：客户 id=8（type=-10）、供应商 id=9（type=10）

### 2.3 采购入库单

```
POST /index.php/scm/invPu/add?action=add
表单只有 postData 一个字段，值为 JSON 字符串
```

必填（JSON 内）：`billNo`、`transType`(150501)、`date`、`buId`(供应商 id)、`accId`、`amount`、`arrears`、`disRate`、`disAmount`、`rpAmount`、`totalQty`、`totalAmount`、`entries[]`

`entries[]` 每项：`invId`、`qty`、`price`、`amount`、`discountRate`、`deduction`、`mainUnit`、`unitId`、`locationId`、`locationName`、`invNumber`、`invName`、`invSpec`

**前置条件**：`ci_storage` 必须存在 `disable=0` 的仓库，否则报「主仓不存在或不可用」

实测：`{"status":200,"data":{"id":113}}`，明细 `ci_invoice_info.qty=+10`

### 2.4 采购单审核

```
POST /index.php/scm/invPu/checkInvPu?action=check
```

必须传**完整的 postData**（含 `id`），只传 `id` 会报「提交的数据不能为空」。

实测：`checked=1`

### 2.5 销售出库单

```
POST /index.php/scm/invSa/add?action=add
```

必填（JSON 内）：`billNo`、`transType`(150601)、`date`、`buId`(客户 id)、`accId`、`salesId`、`amount`、`arrears`、`disRate`、`disAmount`、`rpAmount`、`totalQty`、`totalAmount`、`serialno`、`description`、`entries[]`

实测：`{"status":200,"data":{"id":114}}`，明细 `qty=-2`（出库记负数），库存净额 10-2=8

### 2.6 打印单据（PDF）

```
GET /index.php/scm/invPu/toPdf?id=113    → application/pdf 4529 B
GET /index.php/scm/invSa/toPdf?id=114    → application/pdf 4748 B
```

实测 `pdftotext` 提取内容正常：含抬头、供应商/客户、单据日期、商品行。
`entrysPerNote` 不传时按 **53 条/页**分页。

### 2.7 查询单据

```
POST /index.php/scm/invPu/purList?action=list   必传 transType=150501
POST /index.php/scm/invSa/saleList?action=list  必传 transType=150601
```

**不传 `transType` 会返回空列表**（不是报错）——这是最容易踩的坑。

实测：采购单 total=1（PUR20261123002, 10, 100, checked=1）、销售单 total=1（SALE20261123001, 2, 40）


### 2.8 调拨单

```
POST /index.php/scm/invTf/add?action=add
```

必填（JSON 内）：`billNo`、`date`、`totalQty`、`entries[]`

`entries[]` 每项：`invId`、`qty`、`price`、`amount`、**`outLocationId`**、**`inLocationId`**、`mainUnit`、`unitId`

- `outLocationId` 与 `inLocationId` 必须都存在且**不能相同**，否则报「请选择调出仓库」/「调出仓库不能与调入仓库相同」
- 调出仓库必须有足够库存，否则报「XX库存不足」

实测：`id=116`，打印 PDF 4280 B

### 2.9 收款单

```
POST /index.php/scm/receipt/add?action=add
```

必填（JSON 内）：`date`、`buId`、`accounts[]`（每项含 `payment`）

- `billNo` **自动生成**（`SKD` 前缀），不用传
- `entries` 可为空数组
- 少传 `accounts` 会报「提交的是空数据」

实测：`id=113`（SKD20261123...），打印 PDF 6417 B

### 2.10 付款单

同收款单，接口换成 `/index.php/scm/payment/add`，`billNo` 前缀 `FKD`。

实测：`id=114`，打印 PDF 6420 B

### 2.11 其他收入 / 其他支出

```
POST /index.php/scm/ori/addInc    → 其他收入（transType 153401）
POST /index.php/scm/ori/addExp    → 其他支出（transType 153402）
```

必填（JSON 内）：`date`、`buId`、`accId`、`totalAmount`、`entries[]`（至少 1 条）

实测：收入 `id=117`、支出 `id=118`

### 2.12 各类型打印方式（不一样，容易踩）

| 单据 | 打印方式 |
| --- | --- |
| 采购 / 销售 / 调拨 | `GET /scm/{invPu,invSa,invTf}/toPdf?id=xxx` |
| 收款 / 付款 | **`POST`** `/scm/{receipt,payment}/toPdf`，表单带 `id`（GET 返回 0 字节） |
| 其他收支 | `GET /scm/ori/toPdf?id=xxx&transType=153401或153402`（**必须带 transType**，否则报「单据不存在」） |

## 三、接口清单

**必填** 依据源码校验逻辑推断（`strlen()<1`、`!$x` 等），未做校验的标为「选填」。

### basedata/assist.php　（类 `Assist`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | skey, typeNumber |  |
| `getAssistType` | GET | — | — |  |
| `add` | POST | — | name, typeNumber |  |
| `update` | POST | — | id, name, typeNumber |  |
| `delete` | POST | — | id, typeNumber, name |  |

### basedata/assistSku.php　（类 `Assistsku`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | — | page, rows, skuClassId |  |
| `add` | POST | — | skuAssistId, skuClassId, skuName, skuId |  |
| `delete` | POST | — | id, skuName |  |

### basedata/assistType.php　（类 `Assisttype`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `add` | POST | name | type, id |  |
| `update` | POST | name | id |  |
| `delete` | POST | — | id, name |  |

### basedata/contact.php　（类 `Contact`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | — | categoryId, page, rows, skey, type |  |
| `getNextNo` | POST | — | skey, type |  |
| `checkName` | POST | — | id, name |  |
| `getRecentlyContact` | POST | — | billType, transType, id, linkMans, name |  |
| `getLinkMen` | GET | — | linkMans, buId |  |
| `query` | POST | — | id, type, amount, beginDate, cCategory, cLevel, difMoney, linkMans, name, number, periodMoney, pinYin, remark, taxRate |  |
| `add` | POST | — | cCategory, id, linkMans, name, number, type |  |
| `update` | POST | — | cCategory, cCategoryName, customerType, id, linkMans, name, number, type |  |
| `delete` | POST | — | id, type |  |
| `disable` | POST | — | contactIds, disable |  |

### basedata/customer.php　（类 `Customer`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `exporter` | POST | — | categoryId, list, skey |  |

### basedata/deliveryAddr.php　（类 `Deliveryaddr`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `add` | POST | — | id, shortName |  |
| `update` | POST | — | id, shortName |  |
| `delete` | POST | — | id, shortname |  |
| `query` | POST | — | id, address, area, city, isdefault, linkman, mobile, phone, postalcode, province, shortName |  |

### basedata/employee.php　（类 `Employee`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `add` | POST | — | id, name, number |  |
| `update` | POST | — | id, name, number |  |
| `delete` | POST | — | id, name |  |
| `disable` | POST | — | disable, employeeIds, name |  |
| `findByNumberOrName` | POST | — | page, rows, skey |  |

### basedata/import.php　（类 `Import`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `downloadtemplate1` | GET | — | — |  |
| `downloadtemplate2` | GET | — | — |  |
| `downloadtemplate3` | GET | — | — |  |
| `upload` | GET | — | — |  |
| `uploadExcel` | GET | — | — |  |

### basedata/inventory.php　（类 `Inventory`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | — | assistId, barCode, page, rows, skey |  |
| `listBySelected` | POST | — | contactId, ids, type |  |
| `query` | POST | — | id |  |
| `getNextNo` | POST | — | skey |  |
| `checkBarCode` | POST | — | barCode |  |
| `checkSpec` | POST | — | spec |  |
| `checkname` | POST | — | barCode |  |
| `getImagesById` | POST | — | id |  |
| `uploadImages` | GET | — | — |  |
| `addImagesToInv` | POST | — | postData, files, id |  |
| `getImage` | POST | — | pid, name |  |
| `add` | POST | — | dopey, id, name, number, propertys, sonGoods, warehousePropertys |  |
| `update` | POST | locationId | dopey, id, name, number, propertys, sonGoods, warehousePropertys |  |
| `delete` | POST | — | id |  |
| `exporter` | POST | — | assistId, barCode, skey, list, storage |  |
| `disable` | POST | — | disable, invIds |  |
| `listinventoryqtywarning` | POST | — | assistId, locationId, page, rows, skey, warnType |  |
| `warningExporter` | POST | — | assistId, locationId, skey, warnType, list |  |

### basedata/invlocation.php　（类 `Invlocation`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `add` | POST | — | id, name |  |
| `update` | POST | — | id, locationId, name |  |
| `delete` | POST | — | locationId, name |  |
| `disable` | POST | — | disable, locationId, name |  |

### basedata/log.php　（类 `Log`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | — | fromDate, page, rows, skey, toDate, user |  |
| `initloglist` | GET | — | — |  |
| `queryAllUser` | GET | — | — |  |
| `queryAllOperateType` | GET | — | — |  |

### basedata/resultInfo.php　（类 `ResultInfo`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |

### basedata/settAcct.php　（类 `Settacct`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `query` | POST | — | id, amount, date, name, number, type |  |
| `findAmountOver` | POST | — | ids |  |
| `add` | POST | — | id, name, number |  |
| `update` | POST | — | id, name, number |  |
| `delete` | POST | — | id, name |  |

### basedata/supplier.php　（类 `Supplier`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `exporter` | POST | — | categoryId, skey, list |  |

### basedata/systemProfile.php　（类 `Systemprofile`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `generateDocNo` | POST | — | billType |  |
| `update` | POST | — | （整个 POST 体） |  |
| `changeSysSkin` | POST | — | skin |  |

### basedata/unit.php　（类 `Unit`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | — | unitTypeId |  |
| `add` | POST | — | id, name |  |
| `update` | POST | — | id, name |  |
| `delete` | POST | — | id, name |  |

### basedata/unitType.php　（类 `Unittype`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `add` | POST | — | name, id |  |
| `update` | POST | — | id, name, entries, guid |  |
| `delete` | POST | — | id, name |  |

### basedata/userSetting.php　（类 `UserSetting`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `update` | POST | — | key, value |  |
| `delete` | POST | — | key, value |  |

### basedata/warranty.php　（类 `Warranty`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `getAdvancedList` | GET | — | data, msg, status |  |

### dataright.php　（类 `Dataright`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `ar` | POST | — | rightid, userName |  |
| `dt` | POST | — | userName |  |
| `update` | POST | — | rights, type, userName |  |
| `query` | POST | — | skey, type, userName, data, msg, status |  |

### home.php　（类 `Home`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | name, rights, roleid, system, uid, username |  |
| `main` | GET | — | — |  |
| `set_password` | POST | — | buyerMobile, buyerName, newPassword |  |
| `set_password2` | POST | — | password, userId |  |
| `Services` | GET | — | — |  |

### import.php　（类 `Import`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |

### inventory.php　（类 `Inventory`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |

### login.php　（类 `Login`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | username, userpwd | ispwd, jxcsys |  |
| `out` | GET | — | — |  |
| `code` | GET | — | — |  |

### noteprinttemp.php　（类 `Noteprinttemp`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |

### prints.php　（类 `Prints`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `print_settings_voucher` | GET | — | — |  |

### report.php　（类 `Report`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | data, msg, status, totalsize |  |
| `pu_detail_new` | POST | — | beginDate, endDate |  |
| `puDetail_detail` | POST | — | beginDate, customerNo, endDate, goodsNo, page, rows, storageNo, data, msg, status |  |
| `puDetail_detailExporter` | POST | — | beginDate, customerNo, endDate, goodsNo, storageNo, list |  |
| `pu_summary_new` | POST | — | beginDate, endDate |  |
| `puDetail_inv` | POST | — | beginDate, customerNo, endDate, goodsNo, page, rows, storageNo, data, msg, status |  |
| `puDetail_invExporter` | POST | — | beginDate, customerNo, endDate, goodsNo, storageNo, list |  |
| `pu_summary_supply_new` | POST | — | beginDate, endDate |  |
| `puDetail_supply` | POST | — | beginDate, customerNo, endDate, goodsNo, page, rows, storageNo, data, msg, status |  |
| `puDetail_supplyExporter` | POST | — | beginDate, customerNo, endDate, goodsNo, storageNo, list |  |
| `sales_detail` | GET | — | — |  |
| `salesDetail_detail` | POST | — | beginDate, customerNo, endDate, goodsNo, page, profit, rows, salesId, storageNo, data, msg, status |  |
| `salesDetail_detailExporter` | POST | — | beginDate, customerNo, endDate, goodsNo, salesId, storageNo, list |  |
| `sales_summary` | GET | — | — |  |
| `sales_profit` | GET | — | — |  |
| `salesDetail_inv` | POST | — | beginDate, customerNo, endDate, goodsNo, page, profit, rows, salesId, storageNo, data, msg, status |  |
| `salesDetail_invExporter` | POST | — | beginDate, customerNo, endDate, goodsNo, profit, salesId, storageNo, info, list |  |
| `salesProfit_inv` | POST | — | beginDate, customerNo, endDate, page, rows, salesId, salesNo, data, msg, status |  |
| `salesProfit_invExporter` | POST | — | beginDate, customerNo, endDate, salesId, salesNo, info, list |  |
| `sales_summary_customer_new` | GET | — | — |  |
| `salesDetail_customer` | POST | — | beginDate, customerNo, endDate, goodsNo, page, profit, rows, salesId, storageNo, data, msg, status |  |
| `salesDetail_customerExporter` | POST | — | beginDate, customerNo, endDate, goodsNo, profit, salesId, storageNo, info, list |  |
| `contact_debt_new` | GET | — | — |  |
| `contactDebt_detail` | POST | — | customer, matchCon, page, rows, supplier, data, msg, status |  |
| `contactDebt_exporter` | POST | — | customer, matchCon, supplier, list |  |
| `goods_balance` | GET | — | — |  |
| `invBalance` | POST | — | catId, endDate, goodsNo, storageNo |  |
| `invBalance_exporter` | POST | — | beginDate, catId, endDate, goodsNo, storageNo, list, profit, storage |  |
| `goods_flow_detail` | GET | — | — |  |
| `deliverDetail` | POST | — | beginDate, endDate, goodsNo, page, rows, storageNo, data, msg, status |  |
| `deliverDetail_exporter` | POST | — | beginDate, endDate, goodsNo, storageNo, list1, list2 |  |
| `goods_flow_summary` | GET | — | — |  |
| `deliverSummary` | POST | — | beginDate, endDate, goodsNo, page, rows, storageNo |  |
| `deliverSummary_exporter` | POST | — | beginDate, endDate, goodsNo, storageNo, list |  |
| `cash_bank_journal_new` | POST | — | accountNo, beginDate, endDate |  |
| `bankBalance_detail` | POST | — | accountNo, beginDate, endDate, page, rows |  |
| `bankBalance_exporter` | POST | — | accountNo, beginDate, endDate, list1, list2 |  |
| `account_pay_detail_new` | POST | — | accountNo, beginDate, endDate |  |
| `fundBalance_detailSupplier` | POST | — | accountNo, beginDate, endDate, page, rows, type |  |
| `fundBalance_exporterSupplier` | POST | — | accountNo, beginDate, endDate, list1, list2 |  |
| `account_proceeds_detail_new` | POST | — | accountNo, beginDate, endDate |  |
| `fundBalance_detail` | POST | — | accountNo, beginDate, endDate, page, rows, type, data, msg, status |  |
| `fundBalance_exporter` | POST | — | accountNo, beginDate, endDate, list1, list2 |  |
| `customers_reconciliation_new` | POST | — | beginDate, customerId, customerName, endDate |  |
| `customerBalance_detail` | POST | — | beginDate, customerId, customerName, endDate, page, rows, showDetail, data, msg, status |  |
| `customerBalance_exporter` | POST | — | beginDate, customerId, customerName, endDate, showDetail, list1, list2 |  |
| `suppliers_reconciliation_new` | POST | — | beginDate, endDate, supplierId, supplierName |  |
| `supplierBalance_detail` | POST | — | beginDate, endDate, page, rows, showDetail, supplierId, supplierName, data, msg, status |  |
| `supplierBalance_exporter` | POST | — | beginDate, endDate, showDetail, supplierId, supplierName, list1, list2 |  |
| `other_income_expense_detail` | POST | — | beginDate, endDate, supplierId |  |
| `oriDetail_detail` | POST | — | beginDate, endDate, page, rows, transType, typeName, data, msg, status |  |
| `oriDetail_export` | POST | — | beginDate, endDate, transType, typeName, list |  |

### right.php　（类 `Right`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `isMaxShareUser` | GET | — | — |  |
| `queryAllUser` | GET | — | — |  |
| `queryUserByName` | POST | — | userName, mobile, uid, username |  |
| `adduser` | POST | password, userNumber | userMobile, userName |  |
| `addrights2Outuser` | POST | — | rightid, userName |  |
| `queryalluserright` | POST | — | userName, lever |  |
| `auth2UserCancel` | POST | — | userName |  |
| `auth2User` | POST | — | userName |  |

### sales.php　（类 `Sales`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `sales_search` | GET | — | — |  |

### scm/backup.php　（类 `Backup`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | createTime, dbid, fid, filename, size, username |  |
| `queryBackupFile` | GET | — | data, msg, status, totalsize |  |
| `deleteBackupFile` | POST | — | id |  |
| `recover` | POST | — | id |  |
| `download` | POST | — | fid |  |
| `create_folders` | GET | — | — |  |

### scm/invOi.php　（类 `InvOi`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | action, type |  |
| `listIn` | POST | — | beginDate, endDate, locationId, matchCon, page, rows, transTypeId |  |
| `exportInvOi` | POST | — | beginDate, endDate, locationId, matchCon, transTypeId, list |  |
| `queryTransType` | POST | — | type |  |
| `add` | POST | — | postData, billNo |  |
| `addnew` | GET | — | — |  |
| `updateOi` | POST | — | postData, billNo, id |  |
| `updateIn` | POST | — | id, billDate, billNo, billType, buId, checked, contactName, createTime, description, modifyTime, totalAmount, totalQty, transType, userName |  |
| `toOiPdf` | GET | — | countpage, list, num, postData, system, id |  |
| `deletein` | GET | — | billNo, id |  |
| `listout` | POST | — | beginDate, endDate, locationId, matchCon, page, rows, transTypeId |  |
| `exportInvOo` | POST | — | beginDate, endDate, locationId, matchCon, transTypeId, list |  |
| `addOo` | POST | — | postData, billNo |  |
| `addnewOo` | GET | — | — |  |
| `updateOo` | POST | — | postData, billNo, id |  |
| `updateOut` | POST | — | id |  |
| `toOoPdf` | GET | — | countpage, list, num, postData, system, id |  |
| `deleteOut` | GET | — | billNo, id |  |
| `listCbtz` | POST | — | beginDate, endDate, locationId, matchCon, page, rows |  |
| `exportInvCadj` | POST | — | beginDate, endDate, locationId, matchCon, list |  |
| `addCADJ` | POST | — | postData, billNo |  |
| `addNewCADJ` | GET | — | — |  |
| `updateCbtz` | POST | — | id, billDate, billNo, billType, checked, createTime, description, modifyTime, totalAmount, totalQty, transType, userName |  |
| `updateCADJ` | POST | — | postData, billNo, id |  |
| `toCBTZPdf` | GET | — | countpage, list, num, system, id |  |
| `deleteCbtz` | GET | — | billNo, id |  |
| `queryToPD` | POST | — | categoryId, goods, locationId, page, rows, showZero |  |
| `exportToPD` | POST | — | categoryId, goods, locationId, showZero, list |  |
| `generatorPD` | POST | — | postData, entries |  |

### scm/invPu.php　（类 `InvPu`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | — | id, billNo, action |  |
| `purList` | POST | — | beginDate, endDate, matchCon, page, rows, sidx, sord, transType |  |
| `exportInvPu` | POST | — | beginDate, endDate, matchCon, sidx, sord, transType, list |  |
| `findUnhxList` | POST | — | beginDate, billNo, buId, endDate, page, rows |  |
| `add` | POST | — | postData |  |
| `addnew` | GET | — | — |  |
| `updateInvPu` | POST | — | postData, billNo, id |  |
| `update` | POST | — | id, accId, amount, arrears, billDate, billNo, billStatus, billType, buId, checkName, checked, contactName, createTime, description, disAmount, disRate, modifyTime, rpAmount, serialno, totalAmount, totalDiscount, totalQty, totalTax, totalTaxAmount, transType, userName |  |
| `toPdf` | POST | — | entrysPerNote, countpage, list, num, postData, system, id |  |
| `delete` | POST | — | id |  |
| `delete1` | GET | — | accId, billNo, checked, id |  |
| `checkInvPu` | POST | — | postData, billNo, checkName, checked, id |  |
| `batchCheckInvPu` | POST | — | id |  |
| `rsBatchCheckInvPu` | POST | — | id |  |
| `getImagesById` | POST | — | id |  |
| `uploadImages` | GET | — | — |  |
| `addImagesToInv` | POST | — | postData, files, id |  |
| `getImage` | POST | — | pid, name |  |

### scm/invSa.php　（类 `InvSa`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | POST | — | id, billNo, action |  |
| `saleList` | POST | — | beginDate, endDate, hxState, matchCon, page, rows, salesId, sidx, sord, transType |  |
| `exportInvSa` | POST | — | beginDate, endDate, hxState, matchCon, salesId, sidx, sord, transType, list |  |
| `invSaleList` | GET | — | — |  |
| `findUnhxList` | POST | — | beginDate, billNo, buId, endDate, page, rows |  |
| `add` | POST | — | postData, billNo |  |
| `addNew` | GET | — | — |  |
| `updateInvSa` | POST | — | postData, billNo, id |  |
| `update` | POST | — | id, accId, amount, arrears, billDate, billNo, billType, buId, checkName, checked, contactName, createTime, customerFree, description, disAmount, disRate, modifyTime, rpAmount, salesId, totalAmount, totalDiscount, totalQty, transType, udf01, udf02, udf03, userName |  |
| `checkInvSa` | POST | — | postData, billNo, checkName, checked, id, srcOrderId |  |
| `revsCheckInvSa` | POST | — | postData, billNo, id, srcOrderId |  |
| `batchCheckInvSa` | POST | — | id |  |
| `rsBatchCheckInvSa` | POST | — | id |  |
| `toPdf` | POST | — | entrysPerNote, countpage, list, num, postData, system, id |  |
| `delete` | GET | — | accId, billNo, checked, id |  |
| `justIntimeInv` | POST | — | invId, page, rows |  |
| `findNearSaEmp` | GET | — | — |  |
| `log` | GET | — | — |  |

### scm/invSo.php　（类 `InvSo`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | action |  |
| `soList` | POST | — | beginDate, endDate, hxState, matchCon, page, rows, salesId, sidx, sord, transType, data, msg, status |  |
| `exportInvSo` | POST | — | beginDate, endDate, hxState, matchCon, salesId, sidx, sord, transType, list |  |
| `add` | POST | — | postData, billNo |  |
| `addNew` | GET | — | — |  |
| `updateinvSo` | POST | — | postData, billNo, id |  |
| `update` | POST | — | id, accId, amount, arrears, billDate, billNo, billStatus, billType, buId, checkName, checked, contactName, customerFree, deliveryDate, description, disAmount, disRate, modifyTime, rpAmount, salesId, totalAmount, totalDiscount, totalQty, transType, userName |  |
| `queryDetails` | POST | — | id, accId, amount, arrears, billDate, billNo, billStatus, billType, buId, checkName, checked, contactName, customerFree, deliveryDate, disAmount, disRate, modifyTime, rpAmount, salesId, totalAmount, totalDiscount, totalQty, transType, userName |  |
| `toPdf` | GET | — | countpage, list, num, postData, system, id |  |
| `delete` | GET | — | billNo, checked, id |  |
| `batchClose` | GET | — | — |  |
| `batchcheckinvSo` | POST | — | id |  |
| `rsbatchcheckinvSo` | POST | — | id |  |
| `checkInvSo` | POST | — | postData, billNo, checkName, checked, id |  |
| `revsCheckInvSo` | POST | — | postData, billNo, id |  |
| `findNearSoEmp` | GET | — | — |  |

### scm/invTf.php　（类 `InvTf`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | action |  |
| `tfList` | POST | — | beginDate, endDate, inLocationId, matchCon, outLocationId, page, rows |  |
| `exportInvTf` | POST | — | beginDate, endDate, inLocationId, matchCon, outLocationId, list |  |
| `add` | POST | — | postData |  |
| `addNew` | GET | — | — |  |
| `update` | POST | — | id, billDate, billNo, checkName, checked, createTime, description, modifyTime, postData, totalQty, userName |  |
| `updateInvTf` | POST | — | postData, billNo, id |  |
| `toPdf` | GET | — | countpage, list, num, postData, system, id |  |
| `delete` | GET | — | billNo, id |  |

### scm/ori.php　（类 `Ori`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | action |  |
| `listInc` | POST | — | beginDate, endDate, matchCon, page, rows, transTypeId |  |
| `exportInc` | POST | — | beginDate, endDate, matchCon, transTypeId, category, list |  |
| `addInc` | POST | — | postData, billNo |  |
| `addNewInc` | GET | — | — |  |
| `updateInc` | POST | — | postData, billNo, id |  |
| `getIncDetail` | POST | — | id, accId, billDate, billNo, buId, contactName, totalAmount, userName |  |
| `deleteInc` | GET | — | billNo, id |  |
| `listExp` | POST | — | beginDate, endDate, matchCon, page, rows |  |
| `exportExp` | POST | — | beginDate, endDate, matchCon, transTypeId, category, list |  |
| `addExp` | POST | — | postData, billNo |  |
| `addNewExp` | GET | — | — |  |
| `updateExp` | POST | — | postData, billNo, id |  |
| `getExpDetail` | POST | — | id, accId, amount, billDate, billNo, buId, contactName, userName |  |
| `deleteExp` | GET | — | billNo, id |  |
| `toPdf` | GET | — | countpage, list, num, system, id, transType |  |

### scm/payment.php　（类 `Payment`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | action |  |
| `payList` | POST | — | beginDate, endDate, matchCon, page, rows |  |
| `export` | POST | — | beginDate, endDate, locationId, matchCon, account, category, list |  |
| `add` | POST | — | postData, billNo, entries, payment |  |
| `addNew` | GET | — | — |  |
| `updatePayment` | POST | — | postData, billNo, entries, id, payment |  |
| `update` | POST | — | id, billDate, billNo, buId, checkName, checked, contactName, createTime, description, discount, modifyTime, payment, userName |  |
| `delete` | GET | — | billNo, id |  |
| `toPdf` | POST | — | id, account, category, list, num, system |  |

### scm/pdImport.php　（类 `PdImport`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |

### scm/prints.php　（类 `Prints`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `getOrderPrintData` | POST | — | id, type, amount, arrears, billDate, billNo, contactName, countpage, deliveryDate, description, disAmount, list, num, postData, salesName, system, totalAmount, totalQty, udf01, udf02, udf03, userName |  |

### scm/receipt.php　（类 `Receipt`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | action |  |
| `receiptList` | POST | — | beginDate, endDate, matchCon, page, rows |  |
| `exportReceipt` | POST | — | beginDate, endDate, locationId, matchCon, account, category, list |  |
| `add` | POST | — | postData, billNo, entries, payment |  |
| `addnew` | GET | — | — |  |
| `updateReceipt` | POST | — | postData, billNo, entries, id, payment |  |
| `update` | POST | — | id, billDate, billNo, buId, checkName, checked, contactName, createTime, description, discount, modifyTime, payment, userName |  |
| `delete` | GET | — | billNo, id |  |
| `toPdf` | POST | — | id, account, category, list, num, system |  |

### service.php　（类 `Service`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `index` | GET | — | — |  |
| `recover` | GET | — | — |  |

### settings.php　（类 `Settings`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `Contract` | GET | — | — |  |
| `customer_list` | GET | — | — |  |
| `customer_manage` | GET | — | — |  |
| `vendor_list` | GET | — | — |  |
| `vendor_manage` | GET | — | — |  |
| `addressmanage` | GET | — | — |  |
| `goods_list` | GET | — | — |  |
| `storage_list` | GET | — | — |  |
| `storage_manage` | GET | — | — |  |
| `staff_list` | GET | — | — |  |
| `staff_manage` | GET | — | — |  |
| `shippingaddress` | GET | — | — |  |
| `shippingaddressmanage` | GET | — | — |  |
| `settlement_account` | GET | — | — |  |
| `settlementaccount_manager` | GET | — | — |  |
| `system_parameter` | GET | — | — |  |
| `unit_list` | GET | — | — |  |
| `unit_manage` | GET | — | — |  |
| `unitgroup_manage` | GET | — | — |  |
| `backup` | GET | — | — |  |
| `settlement_category_list` | GET | — | — |  |
| `settlement_category_manager` | GET | — | — |  |
| `category_list` | GET | — | typeNumber |  |
| `choose_account` | GET | — | — |  |
| `inventory_warning` | GET | — | — |  |
| `log` | GET | — | — |  |
| `authority` | GET | — | — |  |
| `authority_new` | GET | — | — |  |
| `authority_setting` | GET | — | — |  |
| `authority_setting_data` | GET | — | — |  |
| `goods_manage` | GET | — | — |  |
| `fileupload` | GET | — | — |  |
| `assistingprop` | GET | — | — |  |
| `prop_list` | GET | — | — |  |
| `propmanage` | GET | — | — |  |
| `import` | GET | — | — |  |
| `select_customer` | GET | — | — |  |
| `goods_batch` | GET | — | — |  |
| `addedServiceList` | GET | — | — |  |
| `assistingProp_batch` | GET | — | — |  |
| `assistingPropGroupManage` | GET | — | — |  |
| `storage_batch` | GET | — | — |  |
| `saler_batch` | GET | — | — |  |
| `customer_batch` | GET | — | — |  |
| `supplier_batch` | GET | — | — |  |
| `settlementAccount_batch` | GET | — | — |  |
| `print_templates` | GET | — | — |  |
| `print_templates_manage` | GET | — | — |  |

### storage.php　（类 `Storage`）

| 方法 | HTTP | 参数（必填） | 参数（选填） | 说明 |
| --- | --- | --- | --- | --- |
| `other_search` | GET | — | — |  |
| `inventory` | GET | — | — |  |
| `transfers_search` | GET | — | — |  |
| `import` | GET | — | — |  |
