<?php
// ============================================
// SIMPLE FILE MANAGER - ULTRA SIMPLE VERSION
// ============================================

// Aktifkan semua error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ============================================
// KONFIGURASI MUDAH
// ============================================
$CONFIG = [
    'username' => 'admin',           // Ganti dengan username yang diinginkan
    'password' => 'admin123',        // Ganti dengan password yang diinginkan
    'base_dir' => __DIR__,           // Direktori root (bisa diganti)
    'site_title' => 'File Manager'
];

// ============================================
// CEK KONFIGURASI DASAR
// ============================================
echo "<!-- Debug: Script dimulai -->\n";

if (!is_dir($CONFIG['base_dir'])) {
    die("<h1>ERROR: Direktori '{$CONFIG['base_dir']}' tidak ditemukan!</h1>");
}

echo "<!-- Debug: Direktori valid -->\n";

// ============================================
// SESSION SEDERHANA
// ============================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<!-- Debug: Session started -->\n";

// ============================================
// FUNGSI BANTU SEDERHANA
// ============================================
function h($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function getSafePath($base, $path) {
    // Bersihkan path dari karakter berbahaya
    $path = str_replace(['..', "\0", "\\"], '', $path);
    $path = trim($path, '/');
    
    // Gabungkan dengan base directory
    $fullPath = rtrim($base, '/') . '/' . $path;
    
    // Pastikan masih dalam base directory
    $realBase = realpath($base);
    $realFull = realpath($fullPath);
    
    if ($realFull && strpos($realFull, $realBase) === 0) {
        return $realFull;
    }
    
    return $realBase; // Kembalikan base directory jika tidak aman
}

// ============================================
// SISTEM LOGIN SUPER SEDERHANA
// ============================================
function checkLogin() {
    global $CONFIG;
    
    // Jika logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: ?');
        exit;
    }
    
    // Jika sudah login
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    
    // Proses login
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        
        if ($user == $CONFIG['username'] && $pass == $CONFIG['password']) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user;
            header('Location: ?');
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    }
    
    // Tampilkan form login
    showLoginForm($error ?? '');
    exit;
}

function showLoginForm($error = '') {
    global $CONFIG;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - <?php echo h($CONFIG['site_title']); ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f0f2f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                width: 350px;
            }
            .login-box h2 {
                text-align: center;
                color: #333;
                margin-bottom: 30px;
            }
            .input-group {
                margin-bottom: 20px;
            }
            .input-group input {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
            }
            .btn {
                width: 100%;
                padding: 12px;
                background: #4CAF50;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
            }
            .btn:hover {
                background: #45a049;
            }
            .error {
                color: #f44336;
                text-align: center;
                margin-bottom: 15px;
                padding: 10px;
                background: #ffebee;
                border-radius: 5px;
            }
            .info {
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔐 Login File Manager</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required value="admin">
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required value="admin123">
                </div>
                <button type="submit" name="login" class="btn">MASUK</button>
            </form>
            
            <div class="info">
                Default: admin / admin123<br>
                Ubah di kode sumber untuk keamanan
            </div>
        </div>
    </body>
    </html>
    <?php
}

// ============================================
// CEK LOGIN
// ============================================
checkLogin();

echo "<!-- Debug: Login berhasil -->\n";

// ============================================
// SETUP VARIABEL
// ============================================
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$base_path = $CONFIG['base_dir'];
$full_path = getSafePath($base_path, $current_dir);

// Pastikan itu direktori
if (!is_dir($full_path)) {
    $full_path = $base_path;
    $current_dir = '';
}

echo "<!-- Debug: Full path: $full_path -->\n";

// ============================================
// HANDLE ACTION
// ============================================
$message = '';
$error = '';

// Upload file
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    if ($file['error'] == 0) {
        $target = $full_path . '/' . basename($file['name']);
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $message = "File '{$file['name']}' berhasil diupload!";
        } else {
            $error = "Gagal mengupload file.";
        }
    }
}

// Hapus file/folder
if (isset($_GET['delete'])) {
    $to_delete = getSafePath($base_path, $_GET['delete']);
    
    if (file_exists($to_delete)) {
        if (is_dir($to_delete)) {
            // Coba hapus folder kosong
            if (@rmdir($to_delete)) {
                $message = "Folder berhasil dihapus.";
            } else {
                $error = "Folder tidak kosong atau tidak bisa dihapus.";
            }
        } else {
            if (@unlink($to_delete)) {
                $message = "File berhasil dihapus.";
            } else {
                $error = "Gagal menghapus file.";
            }
        }
    }
    
    header('Location: ?dir=' . urlencode($current_dir));
    exit;
}

// Buat folder baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_folder'])) {
    $folder_name = trim($_POST['folder_name']);
    
    if ($folder_name && preg_match('/^[a-z0-9_-]+$/i', $folder_name)) {
        $new_folder = $full_path . '/' . $folder_name;
        
        if (!file_exists($new_folder)) {
            mkdir($new_folder, 0755);
            $message = "Folder '$folder_name' berhasil dibuat.";
        } else {
            $error = "Folder sudah ada.";
        }
    } else {
        $error = "Nama folder tidak valid. Gunakan huruf, angka, underscore, dan dash saja.";
    }
}

// ============================================
// TAMPILKAN FILE MANAGER
// ============================================
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($CONFIG['site_title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: #2d3748;
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .user-info {
            background: #4a5568;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .nav {
            background: #f7fafc;
            padding: 15px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .breadcrumb {
            color: #4a5568;
        }
        
        .breadcrumb a {
            color: #4299e1;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .messages {
            padding: 15px 25px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .alert.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .actions {
            padding: 20px 25px;
            background: #f7fafc;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #4299e1;
            color: white;
        }
        
        .btn-success {
            background: #48bb78;
            color: white;
        }
        
        .btn-danger {
            background: #f56565;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .file-list {
            padding: 0;
        }
        
        .file-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .file-table th {
            background: #edf2f7;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .file-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .file-table tr:hover {
            background: #f7fafc;
        }
        
        .folder-icon {
            color: #f6ad55;
            margin-right: 10px;
        }
        
        .file-icon {
            color: #4299e1;
            margin-right: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        
        @media (max-width: 768px) {
            .container {
                border-radius: 0;
                margin: -20px;
            }
            
            body {
                padding: 0;
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 <?php echo h($CONFIG['site_title']); ?></h1>
            <div class="user-info">
                👤 <?php echo h($_SESSION['username']); ?> 
                <a href="?logout=1" style="color: #a0aec0; margin-left: 10px;">Logout</a>
            </div>
        </div>
        
        <div class="nav">
            <div class="breadcrumb">
                <a href="?">Root</a>
                <?php
                $parts = explode('/', trim($current_dir, '/'));
                $path = '';
                foreach ($parts as $part) {
                    if ($part) {
                        $path .= '/' . $part;
                        echo ' / <a href="?dir=' . urlencode(ltrim($path, '/')) . '">' . h($part) . '</a>';
                    }
                }
                ?>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="messages">
            <div class="alert success">✅ <?php echo h($message); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="messages">
            <div class="alert error">❌ <?php echo h($error); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <button class="btn btn-primary" onclick="showUpload()">
                📤 Upload File
            </button>
            <button class="btn btn-success" onclick="showNewFolder()">
                📁 Buat Folder
            </button>
            <a href="?" class="btn">🔄 Refresh</a>
        </div>
        
        <div class="file-list">
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Ukuran</th>
                        <th>Terakhir Diubah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Parent directory link
                    if ($current_dir) {
                        $parent = dirname($current_dir);
                        if ($parent == '.') $parent = '';
                        ?>
                        <tr>
                            <td colspan="4">
                                <a href="?dir=<?php echo urlencode($parent); ?>">
                                    <span class="folder-icon">📁</span> .. (Parent Directory)
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                    
                    // List files and folders
                    $items = scandir($full_path);
                    if ($items) {
                        foreach ($items as $item) {
                            if ($item == '.' || $item == '..') continue;
                            
                            $item_path = $full_path . '/' . $item;
                            $is_dir = is_dir($item_path);
                            $size = $is_dir ? '-' : number_format(filesize($item_path) / 1024, 2) . ' KB';
                            $modified = date('Y-m-d H:i:s', filemtime($item_path));
                            $icon = $is_dir ? '📁' : '📄';
                            $icon_class = $is_dir ? 'folder-icon' : 'file-icon';
                            $item_url = $current_dir ? $current_dir . '/' . $item : $item;
                            
                            ?>
                            <tr>
                                <td>
                                    <span class="<?php echo $icon_class; ?>"><?php echo $icon; ?></span>
                                    <?php if ($is_dir): ?>
                                        <a href="?dir=<?php echo urlencode($item_url); ?>">
                                            <?php echo h($item); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo h($item); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $size; ?></td>
                                <td><?php echo $modified; ?></td>
                                <td>
                                    <?php if (!$is_dir): ?>
                                        <a href="?dir=<?php echo urlencode($current_dir); ?>&download=<?php echo urlencode($item); ?>" 
                                           class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">
                                            📥 Download
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($item_url); ?>" 
                                       class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;"
                                       onclick="return confirm('Hapus <?php echo h($item); ?>?')">
                                        🗑️ Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    
                    if (count($items) <= 2) {
                        ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #a0aec0;">
                                📭 Folder kosong
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Simple File Manager v1.0 | <?php echo date('Y'); ?> | 
            <a href="javascript:void(0)" onclick="alert('Username: <?php echo h($CONFIG['username']); ?}\nPassword: <?php echo h($CONFIG['password']); ?>')">
                Lihat Kredensial
            </a></p>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px;">📤 Upload File</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="file">Pilih File:</label>
                    <input type="file" name="file" id="file" class="form-control" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Upload</button>
                    <button type="button" class="btn" onclick="hideUpload()">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- New Folder Modal -->
    <div id="folderModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px;">📁 Buat Folder Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="folder_name">Nama Folder:</label>
                    <input type="text" name="folder_name" id="folder_name" class="form-control" 
                           placeholder="contoh: folder_baru" required pattern="[a-zA-Z0-9_-]+">
                    <small style="color: #718096; font-size: 12px;">Hanya huruf, angka, underscore, dan dash</small>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="new_folder" class="btn btn-success">Buat</button>
                    <button type="button" class="btn" onclick="hideNewFolder()">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showUpload() {
            document.getElementById('uploadModal').style.display = 'flex';
        }
        
        function hideUpload() {
            document.getElementById('uploadModal').style.display = 'none';
        }
        
        function showNewFolder() {
            document.getElementById('folderModal').style.display = 'flex';
        }
        
        function hideNewFolder() {
            document.getElementById('folderModal').style.display = 'none';
        }
        
        // Tutup modal ketika klik di luar
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Download handler
        document.addEventListener('DOMContentLoaded', function() {
            const downloadLinks = document.querySelectorAll('a[href*="download="]');
            downloadLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Biarkan link berjalan normal
                });
            });
        });
    </script>
</body>
</html>
