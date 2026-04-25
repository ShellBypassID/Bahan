<?php
// Turn off error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Set timeout
set_time_limit(60);

// Target URL
$url = "https://www.backlinkku.id/menu/vip-v2/script.txt";

// Output sebagai plain text
header('Content-Type: text/plain; charset=utf-8');

echo "==================================\n";
echo "PHP SCRIPT CHECKER - PHP 5.3 COMPAT\n";
echo "==================================\n";
echo "URL: " . $url . "\n";
echo "Waktu: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "==================================\n\n";

// ============================================
// CEK FUNGSI YANG TERSEDIA
// ============================================
echo "[CEK FUNGSI TERSEDIA]\n";
echo "----------------------------\n";

$curl = function_exists('curl_init') ? '✓ Tersedia' : '✗ Tidak tersedia';
$fopen = function_exists('fopen') ? '✓ Tersedia' : '✗ Tidak tersedia';
$file_get = function_exists('file_get_contents') ? '✓ Tersedia' : '✗ Tidak tersedia';
$fsock = function_exists('fsockopen') ? '✓ Tersedia' : '✗ Tidak tersedia';
$allow_url_fopen = ini_get('allow_url_fopen') ? '✓ On' : '✗ Off';

echo "cURL: $curl\n";
echo "fopen: $fopen\n";
echo "file_get_contents: $file_get\n";
echo "fsockopen: $fsock\n";
echo "allow_url_fopen: $allow_url_fopen\n";
echo "----------------------------\n\n";

// ============================================
// TEST KONEKSI DASAR
// ============================================
echo "[TEST KONEKSI DASAR]\n";
echo "----------------------------\n";

$host = parse_url($url, PHP_URL_HOST);
$host = $host ? $host : 'www.backlinkku.id'; // Fallback jika parse_url gagal

echo "Host: " . $host . "\n";

// Cek DNS dengan gethostbyname
$ip = @gethostbyname($host);
if ($ip != $host) {
    echo "✓ DNS Resolve: $host -> $ip\n";
} else {
    echo "✗ DNS Gagal: $host\n";
}

// Cek port 443
$errno = 0;
$errstr = '';
$fp = @fsockopen($host, 443, $errno, $errstr, 5);
if ($fp) {
    echo "✓ Port 443 (HTTPS) terbuka\n";
    @fclose($fp);
} else {
    echo "✗ Port 443 tertutup: $errstr ($errno)\n";
}

// Cek port 80
$fp = @fsockopen($host, 80, $errno, $errstr, 5);
if ($fp) {
    echo "✓ Port 80 (HTTP) terbuka\n";
    @fclose($fp);
} else {
    echo "✗ Port 80 tertutup: $errstr ($errno)\n";
}
echo "\n";

// ============================================
// METHOD 1: cURL (Paling direkomendasikan)
// ============================================
echo "[METHOD 1] cURL:\n";
echo "----------------------------\n";

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
    
    // Untuk debug
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($content !== false && $httpCode == 200) {
        echo "✓ BERHASIL!\n";
        echo "HTTP Code: " . $httpCode . "\n";
        echo "Panjang: " . strlen($content) . " karakter\n";
        echo "Preview (100 karakter):\n---\n" . substr($content, 0, 100) . "\n---\n";
    } else {
        echo "✗ GAGAL\n";
        echo "HTTP Code: " . $httpCode . "\n";
        if ($error) echo "cURL Error: " . $error . "\n";
    }
} else {
    echo "✗ Fungsi cURL tidak tersedia\n";
}
echo "\n";

// ============================================
// METHOD 2: file_get_contents
// ============================================
echo "[METHOD 2] file_get_contents:\n";
echo "----------------------------\n";

if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
    $opts = array(
        'http' => array(
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' . "\r\n",
            'timeout' => 10
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    );
    
    $context = stream_context_create($opts);
    $content = @file_get_contents($url, false, $context);
    
    if ($content !== false) {
        echo "✓ BERHASIL!\n";
        echo "Panjang: " . strlen($content) . " karakter\n";
        echo "Preview (100 karakter):\n---\n" . substr($content, 0, 100) . "\n---\n";
    } else {
        echo "✗ GAGAL\n";
    }
} else {
    echo "✗ Fungsi tidak tersedia atau allow_url_fopen=Off\n";
}
echo "\n";

// ============================================
// METHOD 3: fopen + fread
// ============================================
echo "[METHOD 3] fopen + fread:\n";
echo "----------------------------\n";

if (function_exists('fopen') && function_exists('stream_get_contents')) {
    $opts = array(
        'http' => array(
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0' . "\r\n",
            'timeout' => 10
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    );
    
    $context = stream_context_create($opts);
    $handle = @fopen($url, 'r', false, $context);
    
    if ($handle) {
        $content = '';
        while (!feof($handle)) {
            $content .= fread($handle, 8192);
        }
        fclose($handle);
        
        if (!empty($content)) {
            echo "✓ BERHASIL!\n";
            echo "Panjang: " . strlen($content) . " karakter\n";
            echo "Preview (100 karakter):\n---\n" . substr($content, 0, 100) . "\n---\n";
        } else {
            echo "✗ GAGAL (konten kosong)\n";
        }
    } else {
        echo "✗ GAGAL (fopen)\n";
    }
} else {
    echo "✗ Fungsi tidak tersedia\n";
}
echo "\n";

// ============================================
// METHOD 4: fsockopen
// ============================================
echo "[METHOD 4] fsockopen:\n";
echo "----------------------------\n";

if (function_exists('fsockopen')) {
    $host = parse_url($url, PHP_URL_HOST);
    $path = parse_url($url, PHP_URL_PATH) ?: '/';
    $query = parse_url($url, PHP_URL_QUERY);
    if ($query) {
        $path .= '?' . $query;
    }
    
    $fp = @fsockopen("ssl://" . $host, 443, $errno, $errstr, 10);
    
    if ($fp) {
        $out = "GET " . $path . " HTTP/1.0\r\n";
        $out .= "Host: " . $host . "\r\n";
        $out .= "User-Agent: Mozilla/5.0\r\n";
        $out .= "Connection: Close\r\n\r\n";
        
        fwrite($fp, $out);
        
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 1024);
        }
        fclose($fp);
        
        // Cari posisi body
        $pos = strpos($response, "\r\n\r\n");
        if ($pos !== false) {
            $body = substr($response, $pos + 4);
            echo "✓ BERHASIL!\n";
            echo "Panjang: " . strlen($body) . " karakter\n";
            echo "Preview (100 karakter):\n---\n" . substr($body, 0, 100) . "\n---\n";
        } else {
            echo "✗ GAGAL (format response)\n";
        }
    } else {
        echo "✗ GAGAL: $errstr ($errno)\n";
    }
} else {
    echo "✗ Fungsi fsockopen tidak tersedia\n";
}
echo "\n";

// ============================================
// TEST DENGAN URL ALTERNATIF
// ============================================
echo "[TEST URL ALTERNATIF]\n";
echo "----------------------------\n";

$testUrls = array(
    "http://www.google.com",
    "http://www.example.com",
    "http://httpbin.org/get"
);

foreach ($testUrls as $testUrl) {
    echo "Mencoba: $testUrl\n";
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($content !== false && $httpCode == 200) {
            echo "  ✓ BERHASIL (cURL) - HTTP $httpCode\n";
        } else {
            echo "  ✗ GAGAL (cURL) - HTTP $httpCode\n";
        }
    } else {
        echo "  ✗ cURL tidak tersedia\n";
    }
}
echo "\n";

// ============================================
// INFORMASI PHP.INI
// ============================================
echo "[INFORMASI PHP.INI]\n";
echo "----------------------------\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'On' : 'Off') . "\n";
echo "allow_url_include: " . (ini_get('allow_url_include') ? 'On' : 'Off') . "\n";
echo "disable_functions: " . (ini_get('disable_functions') ?: 'none') . "\n";
echo "open_basedir: " . (ini_get('open_basedir') ?: 'none') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "default_socket_timeout: " . ini_get('default_socket_timeout') . "\n";

echo "\n==================================\n";
echo "KESIMPULAN:\n";
echo "==================================\n";
echo "Jika semua metode gagal, kemungkinan:\n";
echo "1. URL target tidak aktif atau diblokir\n";
echo "2. Firewall server memblokir koneksi keluar\n";
echo "3. IP server Anda diblokir oleh target\n";
echo "4. Suhosin patch memblokir fungsi tertentu\n";
echo "\n";
echo "Coba verifikasi manual:\n";
echo "1. Buka di browser: https://www.backlinkku.id/menu/vip-v2/script.txt\n";
echo "2. Dari server, coba: curl -I https://www.backlinkku.id/menu/vip-v2/script.txt\n";
echo "==================================\n";
?>