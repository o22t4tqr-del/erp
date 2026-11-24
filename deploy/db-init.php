<?php
/**
 * 容器启动时准备数据库（blitz.cloud 的托管数据库没有公网端口，只能在容器内导入）
 *
 *  1. 连接参数来自 application/config/database.php（DATABASE_URL / DB_* / MYSQL_* 同一套解析）
 *  2. 等数据库可连接，最多 WAIT_DB_SECONDS 秒（默认 120）
 *  3. 库里没有 ci_admin 表时导入 erp.sql；已有表则跳过，绝不动现有数据
 *
 * 环境变量：
 *   AUTO_INIT_SCHEMA=0   关闭自动导入（库由你自己导入好时用）
 *   WAIT_DB_SECONDS=120  等待数据库就绪的秒数
 */

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

define('BASEPATH', '/var/www/html/system/');
require '/var/www/html/application/config/db_env.php';

$erp_cfg = erp_db_config();

$host = $erp_cfg['hostname'];
$port = (int) ($erp_cfg['port'] ? $erp_cfg['port'] : 3306);
$user = $erp_cfg['username'];
$pass = $erp_cfg['password'];
$name = $erp_cfg['database'];
$src  = $erp_cfg['source'];

function erp_log($msg)
{
    fwrite(STDOUT, '[db-init] ' . $msg . "\n");
}

// 把识别到的连接参数打出来（不打印密码），方便在 blitz 的 Logs 里核对
erp_log('连接参数 host=' . $host . ':' . $port . ' db=' . $name . ' user=' . $user
    . ' 密码=' . ($pass !== '' ? '已设置' : '(空)'));
erp_log('变量来源 host←' . $src['hostname'] . ' port←' . $src['port']
    . ' db←' . $src['database'] . ' user←' . $src['username'] . ' pass←' . $src['password']);

if ( ! empty($erp_cfg['conflict']))
{
    erp_log('注意：连接串和手填变量同时存在（' . implode(', ', $erp_cfg['conflict'])
        . '），已用手填的值；要改用平台托管库，把 DB_*/MYSQL_* 这几个变量删掉再重启');
}

$erp_names = array();
foreach (array_keys((array) getenv()) as $erp_key)
{
    if (preg_match('/^(DB_|MYSQL_|DATABASE_|CLEARDB_|JAWSDB_)/i', $erp_key))
    {
        $erp_names[] = $erp_key;
    }
}
if ($erp_names)
{
    sort($erp_names);
    erp_log('环境里可见的数据库相关变量：' . implode(', ', $erp_names));
}
else
{
    erp_log('环境里没有任何 DB_/MYSQL_/DATABASE_ 变量');
}

if (erp_opt('AUTO_INIT_SCHEMA', '1') === '0') {
    erp_log('AUTO_INIT_SCHEMA=0，跳过表结构初始化');
    exit(0);
}

// ---- 1. 等待数据库 ----
// PHP 8.1+ 默认 MYSQLI_REPORT_ERROR|STRICT：new mysqli() 失败会抛 mysqli_sql_exception，
// 直接 fatal，重试和降级逻辑都不会执行。这里必须先关掉。
mysqli_report(MYSQLI_REPORT_OFF);

$wait     = (int) erp_opt('WAIT_DB_SECONDS', '120');
$deadline = time() + $wait;
$mysqli   = null;
$lastErr  = '';
$lastNo   = 0;
$gaveUp   = false;

while (true) {
    $mysqli = null;
    try {
        $mysqli = @new mysqli($host, $user, $pass, $name, $port);
    } catch (Throwable $e) {
        $lastErr = $e->getMessage();
    }

    if ($mysqli !== null && !$mysqli->connect_errno) {
        break;
    }
    if ($mysqli !== null) {
        $lastErr = $mysqli->connect_error;
        $lastNo  = (int) $mysqli->connect_errno;
    }

    // 1044 没权限 / 1045 账号密码错 / 1049 库不存在：等下去不会变好，直接收工
    if (in_array($lastNo, array(1044, 1045, 1049), true)) {
        fwrite(STDERR, '[db-init] 连接被拒绝（' . $lastNo . '）：' . $lastErr . "\n");
        erp_log('这是账号/密码/库名/来源 IP 的问题，重试无用，停止等待');
        $gaveUp = true;
        break;
    }

    if (time() >= $deadline) {
        fwrite(STDERR, '[db-init] 无法连接数据库 ' . $user . '@' . $host . ':' . $port . '/' . $name
            . '，等了 ' . $wait . ' 秒：' . $lastErr . "\n");
        $gaveUp = true;
        break;
    }
    sleep(2);
}

if ($gaveUp || $mysqli === null || $mysqli->connect_errno) {
    if (erp_opt('DB_INIT_STRICT', '0') === '1') {
        fwrite(STDERR, "[db-init] DB_INIT_STRICT=1，退出\n");
        exit(1);
    }
    erp_log('数据库不可用，仍然启动应用；页面会提示数据库连接失败');
    exit(0);
}

$mysqli->set_charset('utf8');

$res     = $mysqli->query("SHOW TABLES LIKE 'ci_admin'");
$exists  = $res && $res->num_rows > 0;

if ($exists) {
    erp_log('检测到已有表结构，跳过导入');
    exit(0);
}

// ---- 2. 导入表结构 ----
$sqlFile = '/usr/local/share/erp.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "[db-init] 找不到 $sqlFile\n");
    if (erp_opt('DB_INIT_STRICT', '0') === '1') {
        exit(1);
    }
    erp_log('没有表结构文件，继续启动（库里必须已经有表）');
    exit(0);
}

$sql   = file_get_contents($sqlFile);
$stmts = erp_split_sql($sql);
erp_log('空库，导入 erp.sql（' . count($stmts) . ' 条语句）');

$n = 0;
foreach ($stmts as $stmt) {
    if ($mysqli->query($stmt) === false) {
        fwrite(STDERR, '[db-init] 第 ' . ($n + 1) . " 条语句失败: " . $mysqli->error . "\n"
            . substr($stmt, 0, 300) . "\n");
        if (erp_opt('DB_INIT_STRICT', '0') === '1') {
            exit(1);
        }
        erp_log('导入中断，继续启动；修好库后重启会重新导入');
        exit(0);
    }
    $n++;
}

$res   = $mysqli->query('SHOW TABLES');
$total = $res ? $res->num_rows : 0;
erp_log('导入完成：' . $total . ' 张表，管理员账号 admin 已就绪');
exit(0);

/**
 * 按分号切分 SQL，忽略引号、反引号与注释里的分号。
 * erp.sql 里没有存储过程/触发器，不需要 DELIMITER。
 */
function erp_split_sql($sql)
{
    $out   = array();
    $buf   = '';
    $len   = strlen($sql);
    $quote = '';
    $lineComment  = false;
    $blockComment = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $nx = ($i + 1 < $len) ? $sql[$i + 1] : '';

        if ($lineComment) {
            $buf .= $ch;
            if ($ch === "\n") {
                $lineComment = false;
            }
            continue;
        }

        if ($blockComment) {
            $buf .= $ch;
            if ($ch === '*' && $nx === '/') {
                $buf .= '/';
                $i++;
                $blockComment = false;
            }
            continue;
        }

        if ($quote !== '') {
            $buf .= $ch;
            if ($ch === '\\' && $quote !== '`') {
                // 反斜杠转义
                if ($nx !== '') {
                    $buf .= $nx;
                    $i++;
                }
                continue;
            }
            if ($ch === $quote) {
                if ($nx === $quote) { // '' 或 "" 转义
                    $buf .= $nx;
                    $i++;
                    continue;
                }
                $quote = '';
            }
            continue;
        }

        if ($ch === '-' && $nx === '-' && ($i + 2 >= $len || strpos(" \t\r\n", $sql[$i + 2]) !== false)) {
            $lineComment = true;
            $buf .= $ch;
            continue;
        }
        if ($ch === '#') {
            $lineComment = true;
            $buf .= $ch;
            continue;
        }
        if ($ch === '/' && $nx === '*') {
            $blockComment = true;
            $buf .= $ch . $nx;
            $i++;
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $buf .= $ch;
            continue;
        }
        if ($ch === ';') {
            $stmt = trim($buf);
            if ($stmt !== '' && erp_stmt_has_sql($stmt)) {
                $out[] = $stmt;
            }
            $buf = '';
            continue;
        }

        $buf .= $ch;
    }

    $stmt = trim($buf);
    if ($stmt !== '' && erp_stmt_has_sql($stmt)) {
        $out[] = $stmt;
    }

    return $out;
}

/**
 * 纯注释的片段（例如文件末尾 "-- Records of ci_warehouse"）不是 SQL，执行会报
 * "Query was empty"，这里过滤掉。/*! ... *\/ 这类可执行注释要保留。
 */
function erp_stmt_has_sql($stmt)
{
    $s = preg_replace('#/\*(?!!).*?\*/#s', '', $stmt);
    $s = preg_replace('/^[ \t]*--.*$/m', '', $s);
    $s = preg_replace('/^[ \t]*#.*$/m', '', $s);
    return trim($s) !== '';
}