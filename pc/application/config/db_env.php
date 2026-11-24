<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 数据库连接参数：自动识别环境变量。
 *
 * blitz.cloud、Docker、各家 PaaS 注入的变量名不一样，这里把常见的都认一遍，
 * 谁有值用谁。返回值里 source 记录每个字段最终取自哪个变量，启动日志会打出来。
 *
 * 优先级（手填的优先，平台注入的连接串兜底）：
 *   1. DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD（含常见别名）
 *   2. MYSQL_HOST / MYSQL_PORT / MYSQL_DATABASE / MYSQL_USER / MYSQL_PASSWORD
 *   3. DATABASE_URL / MYSQL_URL / DB_URL / CLEARDB_DATABASE_URL / JAWSDB_URL / DATABASE_URI
 *   4. 默认值 localhost / 3306 / erp / root
 *
 * 两边同时存在时（比如平台挂了托管库、你又填了自己的库），用手填的那套，
 * 并在 conflict 里标出来，启动日志会提示，避免连错库还不知道。
 */

if ( ! function_exists('erp_env_pick'))
{
	/**
	 * 按顺序找第一个有值的环境变量。
	 * @return array(值, 变量名)  都没找到时返回 array($default, '')
	 */
	function erp_env_pick($names, $default = '')
	{
		foreach ((array) $names as $name)
		{
			$value = getenv($name);
			if ($value !== false && $value !== '')
			{
				return array($value, $name);
			}
		}
		return array($default, '');
	}
}

if ( ! function_exists('erp_opt'))
{
	/**
	 * 读一个可选环境变量，没设置就用默认值。
	 * 这些名字不写成 getenv('X') 字面量，平台扫描时不会把它们列成必填项。
	 */
	function erp_opt($name, $default = '')
	{
		$value = getenv($name);
		return ($value === false || $value === '') ? $default : $value;
	}
}

if ( ! function_exists('erp_db_config'))
{
	function erp_db_config()
	{
		$defaults = array(
			'hostname' => 'localhost',
			'port'     => '3306',
			'username' => 'root',
			'password' => '',
			'database' => 'erp',
		);

		/*
		 * 显式读取这 5 个变量名。
		 * blitz.cloud 之类的平台会在构建时扫描代码，看应用从哪个变量读数据库地址，
		 * 只写 getenv($name) 的动态循环扫不到任何名字，所以必需项逐个写出来。
		 * 其余别名（MYSQL_* 等）走下面的动态兜底，不在这里声明，免得平台列出一堆要填的项。
		 */
		$direct = array(
			'DB_HOST'     => getenv('DB_HOST'),
			'DB_PORT'     => getenv('DB_PORT'),
			'DB_DATABASE' => getenv('DB_DATABASE'),
			'DB_USERNAME' => getenv('DB_USERNAME'),
			'DB_PASSWORD' => getenv('DB_PASSWORD'),
		);

		// ---- 1. 单变量（手填）----
		$map = array(
			'hostname' => array('DB_HOST', 'DB_HOSTNAME', 'DATABASE_HOST', 'MYSQL_HOST', 'MYSQL_HOSTNAME', 'MYSQL_SERVER'),
			'port'     => array('DB_PORT', 'DATABASE_PORT', 'MYSQL_PORT'),
			'username' => array('DB_USERNAME', 'DB_USER', 'DATABASE_USER', 'MYSQL_USER', 'MYSQL_USERNAME'),
			'password' => array('DB_PASSWORD', 'DB_PASS', 'DB_PWD', 'DATABASE_PASSWORD', 'MYSQL_PASSWORD', 'MYSQL_PASS', 'MYSQL_ROOT_PASSWORD'),
			'database' => array('DB_DATABASE', 'DB_NAME', 'DB_DB', 'DATABASE_NAME', 'MYSQL_DATABASE', 'MYSQL_DB'),
		);

		$manual     = array();
		$manual_src = array();
		foreach ($map as $field => $names)
		{
			foreach ($names as $name)
			{
				if (isset($direct[$name]) && $direct[$name] !== false && $direct[$name] !== '')
				{
					$manual[$field]     = $direct[$name];
					$manual_src[$field] = $name;
					break;
				}
			}
			if (isset($manual[$field]))
			{
				continue; // 上面已经取到
			}

			$hit = erp_env_pick($names);
			if ($hit[1] !== '')
			{
				$manual[$field]     = $hit[0];
				$manual_src[$field] = $hit[1];
			}
		}

		// ---- 2. 连接串 ----
		$url     = erp_env_pick(array('DATABASE_URL', 'MYSQL_URL', 'DB_URL', 'CLEARDB_DATABASE_URL', 'JAWSDB_URL', 'DATABASE_URI'));
		$url_cfg = array();
		if ($url[0] !== '' && preg_match('#^[a-z][a-z0-9+.-]*://#i', $url[0]))
		{
			$parts = parse_url($url[0]);
			if (is_array($parts))
			{
				if ( ! empty($parts['host']))
				{
					$url_cfg['hostname'] = rawurldecode($parts['host']);
				}
				if ( ! empty($parts['port']))
				{
					$url_cfg['port'] = (string) $parts['port'];
				}
				if (isset($parts['user']))
				{
					$url_cfg['username'] = rawurldecode($parts['user']);
				}
				if (isset($parts['pass']))
				{
					$url_cfg['password'] = rawurldecode($parts['pass']);
				}
				if ( ! empty($parts['path']) && $parts['path'] !== '/')
				{
					$url_cfg['database'] = rawurldecode(ltrim($parts['path'], '/'));
				}
			}
		}

		// ---- 3. 合并 ----
		$cfg      = array();
		$source   = array();
		$conflict = array();

		foreach ($defaults as $field => $default)
		{
			if (isset($manual[$field]))
			{
				$cfg[$field]    = $manual[$field];
				$source[$field] = $manual_src[$field];
				if (isset($url_cfg[$field]) && $url_cfg[$field] !== $manual[$field])
				{
					$conflict[] = $field;
				}
			}
			elseif (isset($url_cfg[$field]))
			{
				$cfg[$field]    = $url_cfg[$field];
				$source[$field] = $url[1];
			}
			else
			{
				$cfg[$field]    = $default;
				$source[$field] = '默认值';
			}
		}

		$cfg['port']     = (string) $cfg['port'];
		$cfg['source']   = $source;
		$cfg['conflict'] = $conflict;

		return $cfg;
	}
}

/* End of file db_env.php */
/* Location: ./application/config/db_env.php */