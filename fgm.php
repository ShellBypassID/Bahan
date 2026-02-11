<?php
declare(strict_types=1);

// =========================
// KONFIGURASI SEDERHANA
// =========================
$BASE_DIR = __DIR__; // Atau ganti dengan path yang diinginkan
$AUTH_USER = 'admin';
$AUTH_PASS_PLAIN = 'admin123'; // Password dalam teks biasa

// Ekstensi yang diizinkan untuk edit
$EDIT_ALLOW_EXT = ['txt','md','log','json','xml','csv','ini','conf','php','html','js','css','sql','py','sh'];

// Ekstensi yang diblokir saat upload
$UPLOAD_BLOCK_EXT = ['user.ini','htaccess','php5','phtml','phar'];

// Batasan ukuran
$MAX_UPLOAD_BYTES = 100 * 1024 * 1024; // 100 MB
$MAX_EDIT_BYTES   = 2 * 1024 * 1024;   // 2 MB

// =========================
// INISIALISASI AMAN
// =========================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Pastikan BASE_DIR valid
if (!is_dir($BASE_DIR)) {
    die("<h3>Error: BASE_DIR tidak valid. Buat folder terlebih dahulu.</h3>");
}

// Session sederhana
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// =========================
// FUNGSI BANTU
// =========================
function h(string $s): string { 
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
}

function clean_path(string $path): string {
    $path = str_replace(['\\', "\0"], ['/', ''], $path);
    $path = trim($path, '/');
    
    $parts = explode('/', $path);
    $result = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') {
            if (!empty($result)) array_pop($result);
            continue;
        }
        $result[] = $part;
    }
    
    return implode('/', $result);
}

function resolve_safe_path(string $base, string $path): string {
    $clean = clean_path($path);
    $full = rtrim($base, '/') . '/' . ($clean ? $clean : '');
    
    // Security: pastikan masih dalam base directory
    $real_base = realpath($base);
    $real_full = realpath(dirname($full));
    
    if ($real_base === false || $real_full === false) {
        return '';
    }
    
    if (strpos($real_full, $real_base) !== 0) {
        return '';
    }
    
    return $full;
}

function format_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function get_file_perms(string $path): string {
    if (!file_exists($path)) return '---------';
    
    $perms = fileperms($path);
    $symbolic = '';
    
    // Type
    $symbolic .= (($perms & 0x4000) ? 'd' : (($perms & 0xA000) ? 'l' : '-'));
    
    // Owner
    $symbolic .= (($perms & 0x0100) ? 'r' : '-');
    $symbolic .= (($perms & 0x0080) ? 'w' : '-');
    $symbolic .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    
    // Group
    $symbolic .= (($perms & 0x0020) ? 'r' : '-');
    $symbolic .= (($perms & 0x0010) ? 'w' : '-');
    $symbolic .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    
    // Others
    $symbolic .= (($perms & 0x0004) ? 'r' : '-');
    $symbolic .= (($perms & 0x0002) ? 'w' : '-');
    $symbolic .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    
    return $symbolic;
}

// =========================
// SISTEM LOGIN SEDERHANA
// =========================
function require_simple_login(): void {
    // Logout handler
    if (isset($_GET['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Cek jika sudah login
    if (isset($_SESSION['fm_logged_in']) && $_SESSION['fm_logged_in'] === true) {
        return;
    }
    
    // Proses login
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        global $AUTH_USER, $AUTH_PASS_PLAIN;
        
        if ($username === $AUTH_USER && $password === $AUTH_PASS_PLAIN) {
            $_SESSION['fm_logged_in'] = true;
            $_SESSION['fm_user'] = $username;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    }
    
    // Tampilkan form login
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login File Manager</title>
        <style>
            body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; }
            .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); width: 350px; }
            h2 { text-align: center; color: #333; margin-bottom: 30px; }
            input[type="text"], input[type="password"] { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #5a67d8; }
            .error { color: #e53e3e; text-align: center; margin-top: 10px; }
            .info { color: #666; font-size: 12px; text-align: center; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔐 File Manager Login</h2>';
            
    if ($error) {
        echo '<div class="error">' . h($error) . '</div>';
    }
    
    echo '<form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Masuk</button>
            </form>
            <div class="info">
                Default: admin / admin123<br>
                Edit di konfigurasi untuk mengganti
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// =========================
// PROSES LOGIN
// =========================
require_simple_login();

// =========================
// VARIABEL UTAMA
// =========================
$current_path = isset($_GET['path']) ? clean_path($_GET['path']) : '';
$full_path = resolve_safe_path($BASE_DIR, $current_path);

if ($full_path === '' || !is_dir($full_path)) {
    $current_path = '';
    $full_path = $BASE_DIR;
}

$message = '';
$error = '';

// =========================
// HANDLE ACTION
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload') {
        // Handle upload file
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['file']['name']);
            $target = rtrim($full_path, '/') . '/' . $filename;
            
            // Cek ekstensi yang diblokir
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $UPLOAD_BLOCK_EXT) || in_array($filename, $UPLOAD_BLOCK_EXT)) {
                $error = "Ekstensi file '$filename' diblokir untuk keamanan.";
            } elseif ($_FILES['file']['size'] > $MAX_UPLOAD_BYTES) {
                $error = "File terlalu besar. Maksimal: " . format_size($MAX_UPLOAD_BYTES);
            } elseif (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                chmod($target, 0644);
                $message = "File '$filename' berhasil diupload.";
            } else {
                $error = "Gagal mengupload file.";
            }
        }
    }
    
    elseif ($action === 'delete') {
        $file_to_delete = $_POST['file'] ?? '';
        if ($file_to_delete) {
            $target = resolve_safe_path($full_path, $file_to_delete);
            if ($target && file_exists($target)) {
                if (is_dir($target)) {
                    // Hapus folder jika kosong
                    if (count(scandir($target)) <= 2) {
                        rmdir($target);
                        $message = "Folder '$file_to_delete' berhasil dihapus.";
                    } else {
                        $error = "Folder tidak kosong!";
                    }
                } else {
                    unlink($target);
                    $message = "File '$file_to_delete' berhasil dihapus.";
                }
            } else {
                $error = "File tidak ditemukan atau akses ditolak.";
            }
        }
    }
    
    elseif ($action === 'new_folder') {
        $folder_name = trim($_POST['folder_name'] ?? '');
        if ($folder_name && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $folder_name)) {
            $target = rtrim($full_path, '/') . '/' . $folder_name;
            if (!file_exists($target)) {
                mkdir($target, 0755, true);
                $message = "Folder '$folder_name' berhasil dibuat.";
            } else {
                $error = "Folder sudah ada.";
            }
        } else {
            $error = "Nama folder tidak valid. Hanya huruf, angka, underscore, dan titik.";
        }
    }
    
    elseif ($action === 'save_file') {
        $filename = $_POST['filename'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if ($filename) {
            $target = resolve_safe_path($full_path, $filename);
            if ($target && strlen($content) <= $MAX_EDIT_BYTES) {
                file_put_contents($target, $content);
                $message = "File '$filename' berhasil disimpan.";
            } else {
                $error = "Gagal menyimpan file atau konten terlalu besar.";
            }
        }
    }
}

// =========================
// HANDLE DOWNLOAD
// =========================
if (isset($_GET['download'])) {
    $file = clean_path($_GET['download']);
    $target = resolve_safe_path($full_path, $file);
    
    if ($target && file_exists($target) && is_file($target)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($target) . '"');
        header('Content-Length: ' . filesize($target));
        readfile($target);
        exit;
    }
}

// =========================
// TAMPILKAN FILE MANAGER
// =========================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple File Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; }
        
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin-bottom: 10px; }
        
        .user-info { float: right; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; }
        
        .breadcrumb { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .breadcrumb a { color: #667eea; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        
        .messages { margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 10px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .actions-bar { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-success { background: #38a169; color: white; }
        .btn:hover { opacity: 0.9; }
        
        .file-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #e9ecef; }
        td { padding: 12px 15px; border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        
        .file-icon { margin-right: 8px; }
        .folder { color: #f6ad55; }
        .file { color: #4299e1; }
        
        .upload-form, .new-folder-form { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .editor { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .editor textarea { width: 100%; min-height: 400px; font-family: 'Consolas', monospace; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Simple File Manager</h1>
            <p>Mengelola file dan folder dengan mudah</p>
            <div class="user-info">
                👤 <?php echo h($_SESSION['fm_user'] ?? 'Admin'); ?> | 
                <a href="?logout=1" style="color: white; text-decoration: underline;">Logout</a>
            </div>
        </div>
        
        <div class="breadcrumb">
            <a href="?">🏠 Root</a>
            <?php
            $path_parts = [];
            $accumulated = '';
            if ($current_path) {
                foreach (explode('/', $current_path) as $part) {
                    $accumulated .= ($accumulated ? '/' : '') . $part;
                    $path_parts[] = '<a href="?path=' . urlencode($accumulated) . '">' . h($part) . '</a>';
                }
                echo ' / ' . implode(' / ', $path_parts);
            }
            ?>
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
        
        <div class="actions-bar">
            <button onclick="showUploadForm()" class="btn btn-primary">📤 Upload File</button>
            <button onclick="showNewFolderForm()" class="btn btn-success">📁 Folder Baru</button>
            <button onclick="location.reload()" class="btn">🔄 Refresh</button>
        </div>
        
        <div id="uploadForm" class="upload-form" style="display: none;">
            <h3>Upload File</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="form-group">
                    <label for="file">Pilih file:</label>
                    <input type="file" name="file" id="file" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Upload</button>
                <button type="button" onclick="hideUploadForm()" class="btn">Batal</button>
            </form>
        </div>
        
        <div id="newFolderForm" class="new-folder-form" style="display: none;">
            <h3>Buat Folder Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="new_folder">
                <div class="form-group">
                    <label for="folder_name">Nama Folder:</label>
                    <input type="text" name="folder_name" id="folder_name" class="form-control" required pattern="[a-zA-Z0-9_\-\.]+">
                </div>
                <button type="submit" class="btn btn-success">Buat</button>
                <button type="button" onclick="hideNewFolderForm()" class="btn">Batal</button>
            </form>
        </div>
        
        <div class="file-table">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Ukuran</th>
                        <th>Permission</th>
                        <th>Modifikasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Navigasi ke parent folder
                    if ($current_path) {
                        $parent = dirname($current_path);
                        if ($parent === '.') $parent = '';
                        echo '<tr>
                                <td colspan="5">
                                    <a href="?path=' . urlencode($parent) . '">📂 .. (Parent Folder)</a>
                                </td>
                              </tr>';
                    }
                    
                    // Tampilkan konten folder
                    $items = scandir($full_path);
                    if ($items) {
                        foreach ($items as $item) {
                            if ($item === '.' || $item === '..') continue;
                            
                            $item_path = rtrim($full_path, '/') . '/' . $item;
                            $item_url = ($current_path ? $current_path . '/' : '') . $item;
                            $is_dir = is_dir($item_path);
                            $size = $is_dir ? '-' : format_size(filesize($item_path));
                            $perms = get_file_perms($item_path);
                            $modified = date('Y-m-d H:i:s', filemtime($item_path));
                            $icon = $is_dir ? '📁' : '📄';
                            $class = $is_dir ? 'folder' : 'file';
                            
                            echo '<tr>
                                    <td>
                                        <span class="file-icon ' . $class . '">' . $icon . '</span>';
                            
                            if ($is_dir) {
                                echo '<a href="?path=' . urlencode($item_url) . '">' . h($item) . '</a>';
                            } else {
                                echo h($item);
                            }
                            
                            echo '</td>
                                    <td>' . $size . '</td>
                                    <td><code>' . $perms . '</code></td>
                                    <td>' . $modified . '</td>
                                    <td>';
                            
                            if (!$is_dir) {
                                echo '<a href="?path=' . urlencode($current_path) . '&download=' . urlencode($item) . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Download</a> ';
                            }
                            
                            echo '<form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file" value="' . h($item) . '">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm(\'Hapus ' . h($item) . '?\')">Hapus</button>
                                  </form>
                                    </td>
                                  </tr>';
                        }
                    }
                    
                    if (count($items) <= 2) {
                        echo '<tr><td colspan="5" style="text-align: center; padding: 30px;">📭 Folder kosong</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php
        // Editor file
        if (isset($_GET['edit'])) {
            $edit_file = clean_path($_GET['edit']);
            $edit_path = resolve_safe_path($full_path, $edit_file);
            
            if ($edit_path && file_exists($edit_path) && is_file($edit_path)) {
                $content = file_get_contents($edit_path);
                $file_size = filesize($edit_path);
                
                if ($file_size <= $MAX_EDIT_BYTES) {
                    echo '<div class="editor">
                            <h3>✏️ Edit: ' . h($edit_file) . ' (' . format_size($file_size) . ')</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="save_file">
                                <input type="hidden" name="filename" value="' . h($edit_file) . '">
                                <div class="form-group">
                                    <textarea name="content" class="form-control">' . h($content) . '</textarea>
                                </div>
                                <button type="submit" class="btn btn-success">Simpan</button>
                                <a href="?path=' . urlencode($current_path) . '" class="btn">Batal</a>
                            </form>
                          </div>';
                } else {
                    echo '<div class="alert error">File terlalu besar untuk diedit. Maksimal: ' . format_size($MAX_EDIT_BYTES) . '</div>';
                }
            }
        }
        ?>
        
        <div class="footer">
            <p>Simple File Manager v1.0 | Password: <code><?php echo h($AUTH_PASS_PLAIN); ?></code> | 
            <a href="javascript:void(0)" onclick="alert('Untuk mengganti password, edit variabel $AUTH_PASS_PLAIN di kode.')">Ganti Password</a></p>
        </div>
    </div>
    
    <script>
        function showUploadForm() {
            document.getElementById('uploadForm').style.display = 'block';
            document.getElementById('newFolderForm').style.display = 'none';
        }
        
        function hideUploadForm() {
            document.getElementById('uploadForm').style.display = 'none';
        }
        
        function showNewFolderForm() {
            document.getElementById('newFolderForm').style.display = 'block';
            document.getElementById('uploadForm').style.display = 'none';
        }
        
        function hideNewFolderForm() {
            document.getElementById('newFolderForm').style.display = 'none';
        }
        
        // Edit file dengan double click
        document.addEventListener('DOMContentLoaded', function() {
            const fileRows = document.querySelectorAll('.file-table tr');
            fileRows.forEach(row => {
                row.addEventListener('dblclick', function() {
                    const link = this.querySelector('a');
                    if (link && !link.textContent.includes('..')) {
                        const filename = link.textContent;
                        const currentPath = '<?php echo urlencode($current_path); ?>';
                        window.location.href = `?path=${currentPath}&edit=${encodeURIComponent(filename)}`;
                    }
                });
            });
        });
    </script>
</body>
</html>
