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
require '/var/www/html/application/config/database.php';

$cfg  = $db['default'];
$host = $cfg['hostname'];
$port = (int) ($cfg['port'] ? $cfg['port'] : 3306);
$user = $cfg['username'];
$pass = $cfg['password'];
$name = $cfg['database'];

function erp_log($msg)
{
    fwrite(STDOUT, '[db-init] ' . $msg . "\n");
}

if (getenv('AUTO_INIT_SCHEMA') === '0') {
    erp_log('AUTO_INIT_SCHEMA=0，跳过表结构初始化');
    exit(0);
}

// ---- 1. 等待数据库 ----
$wait     = (int) (getenv('WAIT_DB_SECONDS') ? getenv('WAIT_DB_SECONDS') : 120);
$deadline = time() + $wait;
$mysqli   = null;
$lastErr  = '';

while (true) {
    $mysqli = @new mysqli($host, $user, $pass, $name, $port);
    if (!$mysqli->connect_errno) {
        break;
    }
    $lastErr = $mysqli->connect_error;
    if (time() >= $deadline) {
        fwrite(STDERR, '[db-init] 无法连接数据库 ' . $user . '@' . $host . ':' . $port . '/' . $name
            . '，等了 ' . $wait . ' 秒：' . $lastErr . "\n");
        exit(1);
    }
    sleep(2);
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
    exit(1);
}

$sql   = file_get_contents($sqlFile);
$stmts = erp_split_sql($sql);
erp_log('空库，导入 erp.sql（' . count($stmts) . ' 条语句）');

$n = 0;
foreach ($stmts as $stmt) {
    if ($mysqli->query($stmt) === false) {
        fwrite(STDERR, '[db-init] 第 ' . ($n + 1) . " 条语句失败: " . $mysqli->error . "\n"
            . substr($stmt, 0, 300) . "\n");
        exit(1);
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