<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| 连接参数由 application/config/db_env.php 从环境变量自动识别：
|
|   DATABASE_URL / MYSQL_URL / DB_URL / CLEARDB_DATABASE_URL / JAWSDB_URL
|   DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD（含常见别名）
|   MYSQL_HOST / MYSQL_PORT / MYSQL_DATABASE / MYSQL_USER / MYSQL_PASSWORD
|
| 一个都没设置时用 localhost / 3306 / erp / root。
| 每个字段最终取自哪个变量，启动日志 [db-init] 会打出来。
|
| dbdriver 用 mysqli（PHP 8 已移除 mysql 扩展）。
| pconnect 关掉：容器重启后残留连接会占满 max_connections。
*/

$active_group = 'default';
$active_record = TRUE;

require_once dirname(__FILE__).'/db_env.php';

$erp_db = erp_db_config();

$db['default']['hostname'] = $erp_db['hostname'];
$db['default']['port']     = $erp_db['port'];
$db['default']['username'] = $erp_db['username'];
$db['default']['password'] = $erp_db['password'];
$db['default']['database'] = $erp_db['database'];

$db['default']['dbdriver'] = 'mysqli';
$db['default']['dbprefix'] = 'ci_';
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = (getenv('DB_DEBUG') === '0') ? FALSE : TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = './data/cache/';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;

/* End of file database.php */
/* Location: ./application/config/database.php */