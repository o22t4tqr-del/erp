<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default';
$active_record = TRUE;

/*
 * 连接参数优先取环境变量（blitz.cloud / Docker 注入），否则用下面的默认值。
 *
 * blitz.cloud 给托管 MariaDB 注入的是 DATABASE_URL=mysql://user:pass@host:3306/name，
 * 也可能按约定的名字注入 MYSQL_HOST / MYSQL_DATABASE / MYSQL_USER / MYSQL_PASSWORD。
 * 两种都认，另外也认 DB_HOST / DB_PORT / DB_USERNAME / DB_PASSWORD / DB_DATABASE。
 */
$erp_pick_env = function ($names, $default = '') {
	foreach ((array) $names as $n) {
		$v = getenv($n);
		if ($v !== false && $v !== '') {
			return $v;
		}
	}
	return $default;
};

$erp_url  = $erp_pick_env(array('DATABASE_URL', 'MYSQL_URL', 'DB_URL'));
$erp_urlp = array();
if ($erp_url !== '' && preg_match('#^[a-z][a-z0-9+.-]*://#i', $erp_url)) {
	$erp_urlp = parse_url($erp_url);
	if (!is_array($erp_urlp)) {
		$erp_urlp = array();
	}
}

$db['default']['hostname'] = isset($erp_urlp['host'])
	? $erp_urlp['host']
	: $erp_pick_env(array('DB_HOST', 'MYSQL_HOST'), 'localhost');
$db['default']['port']     = isset($erp_urlp['port'])
	? $erp_urlp['port']
	: $erp_pick_env(array('DB_PORT', 'MYSQL_PORT'), '');
$db['default']['username'] = isset($erp_urlp['user'])
	? rawurldecode($erp_urlp['user'])
	: $erp_pick_env(array('DB_USERNAME', 'DB_USER', 'MYSQL_USER'), 'root');
$db['default']['password'] = isset($erp_urlp['pass'])
	? rawurldecode($erp_urlp['pass'])
	: $erp_pick_env(array('DB_PASSWORD', 'MYSQL_PASSWORD'), 'root');
$db['default']['database'] = isset($erp_urlp['path'])
	? rawurldecode(ltrim($erp_urlp['path'], '/'))
	: $erp_pick_env(array('DB_DATABASE', 'DB_NAME', 'MYSQL_DATABASE', 'MYSQL_DB'), 'erp');

$db['default']['dbdriver'] = 'mysqli';
$db['default']['dbprefix'] = 'ci_';
$db['default']['pconnect'] = FALSE; // 容器部署用短连接，避免重启后残留连接占满 max_connections
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