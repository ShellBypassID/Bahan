<?php
// ============================================
// SIMPLE FILE MANAGER - ANTI ERROR 500
// ============================================

// Matikan semua error reporting untuk keamanan
error_reporting(0);
ini_set('display_errors', 0);

// Buffer output
ob_start();

try {
    // Basic authentication (optional)
    $username = 'admin';
    $password = 'incha123';
    
    if (!isset($_SERVER['PHP_AUTH_USER']) || 
        $_SERVER['PHP_AUTH_USER'] != $username || 
        $_SERVER['PHP_AUTH_PW'] != $password) {
        header('WWW-Authenticate: Basic realm="File Manager"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authentication required';
        exit;
    }
    
    // Base path
    define('BASE_PATH', __DIR__);
    
    // Get current path
    $currentPath = isset($_GET['path']) ? $_GET['path'] : BASE_PATH;
    $currentPath = realpath($currentPath);
    
    // Security check
    if (strpos($currentPath, BASE_PATH) !== 0) {
        $currentPath = BASE_PATH;
    }
    
    // Handle actions
    $message = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['create_file'])) {
            $filename = $_POST['filename'];
            $content = $_POST['content'];
            $filepath = $currentPath . '/' . $filename;
            
            if (file_put_contents($filepath, $content) !== false) {
                $message = "File '$filename' berhasil dibuat!";
            } else {
                $error = "Gagal membuat file '$filename'";
            }
        }
        
        if (isset($_POST['create_folder'])) {
            $foldername = $_POST['foldername'];
            $folderpath = $currentPath . '/' . $foldername;
            
            if (!file_exists($folderpath)) {
                if (mkdir($folderpath, 0777)) {
                    $message = "Folder '$foldername' berhasil dibuat!";
                } else {
                    $error = "Gagal membuat folder '$foldername'";
                }
            } else {
                $error = "Folder '$foldername' sudah ada";
            }
        }
        
        if (isset($_POST['delete'])) {
            $target = $_POST['target'];
            $targetPath = $currentPath . '/' . $target;
            
            if (is_file($targetPath)) {
                if (unlink($targetPath)) {
                    $message = "File '$target' berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus file '$target'";
                }
            } elseif (is_dir($targetPath)) {
                if (rmdir($targetPath)) {
                    $message = "Folder '$target' berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus folder '$target' (folder harus kosong)";
                }
            }
        }
    }
    
    // Get directory contents
    $items = [];
    if (is_dir($currentPath) && is_readable($currentPath)) {
        $files = scandir($currentPath);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $currentPath . '/' . $file;
                $items[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'type' => is_dir($filePath) ? 'folder' : 'file',
                    'size' => is_file($filePath) ? formatBytes(filesize($filePath)) : '-',
                    'perms' => substr(sprintf('%o', fileperms($filePath)), -4),
                    'modified' => date('Y-m-d H:i:s', filemtime($filePath))
                ];
            }
        }
        
        // Sort: folders first, then files
        usort($items, function($a, $b) {
            if ($a['type'] == $b['type']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return ($a['type'] == 'folder') ? -1 : 1;
        });
    }
    
    // Format bytes
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
} catch (Exception $e) {
    // Silent fail
    $error = "Terjadi kesalahan sistem";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager - Princess Incha</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: #4a90e2;
            color: white;
            padding: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .path {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
        }
        
        .path a {
            color: #4a90e2;
            text-decoration: none;
        }
        
        .path a:hover {
            text-decoration: underline;
        }
        
        .content {
            padding: 20px;
        }
        
        .message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #4a90e2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #357abd;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-size: 14px;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .folder-link {
            color: #28a745;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .folder-link:hover {
            text-decoration: underline;
        }
        
        .file-name {
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .delete-form {
            display: inline;
        }
        
        .delete-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 3px;
        }
        
        .delete-btn:hover {
            background: #dc3545;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-content h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: monospace;
        }
        
        .modal-content textarea {
            height: 150px;
            resize: vertical;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-folder {
            background: #28a745;
            color: white;
        }
        
        .badge-file {
            background: #6c757d;
            color: white;
        }
        
        .perms {
            font-family: monospace;
            background: #e9ecef;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 File Manager Sederhana</h1>
            <p>Untuk Yang Mulia Princess Incha 👑</p>
        </div>
        
        <div class="path">
            <strong>📍 Lokasi:</strong> 
            <a href="?path=<?php echo urlencode(BASE_PATH); ?>">Root</a>
            <?php
            $relative = str_replace(BASE_PATH, '', $currentPath);
            if (!empty($relative)) {
                $parts = explode('/', trim($relative, '/'));
                $path = BASE_PATH;
                foreach ($parts as $part) {
                    $path .= '/' . $part;
                    echo ' / <a href="?path=' . urlencode($path) . '">' . htmlspecialchars($part) . '</a>';
                }
            }
            ?>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message success">✅ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="actions">
                <button class="btn btn-primary" onclick="showModal('file')">➕ Buat File</button>
                <button class="btn btn-primary" onclick="showModal('folder')">📁 Buat Folder</button>
                <?php if ($currentPath != BASE_PATH): ?>
                    <a href="?path=<?php echo urlencode(dirname($currentPath)); ?>" class="btn btn-secondary">⬆️ Kembali</a>
                <?php endif; ?>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Ukuran</th>
                        <th>Permission</th>
                        <th>Modified</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">
                                📂 Folder kosong
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['type'] == 'folder'): ?>
                                        <a href="?path=<?php echo urlencode($item['path']); ?>" class="folder-link">
                                            <span>📁</span> <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="file-name">
                                            <span>📄</span> <?php echo htmlspecialchars($item['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $item['type'] == 'folder' ? 'badge-folder' : 'badge-file'; ?>">
                                        <?php echo $item['type'] == 'folder' ? 'FOLDER' : 'FILE'; ?>
                                    </span>
                                </td>
                                <td><?php echo $item['size']; ?></td>
                                <td><span class="perms"><?php echo $item['perms']; ?></span></td>
                                <td><?php echo $item['modified']; ?></td>
                                <td>
                                    <form method="POST" class="delete-form" onsubmit="return confirm('Yakin ingin menghapus <?php echo $item['name']; ?>?')">
                                        <input type="hidden" name="target" value="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button type="submit" name="delete" class="delete-btn" title="Hapus">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Simple File Manager - Anti Error 500 | Untuk Yang Mulia Princess Incha</p>
        </div>
    </div>
    
    <!-- Modal Buat File -->
    <div id="modalFile" class="modal">
        <div class="modal-content">
            <h3>📄 Buat File Baru</h3>
            <form method="POST">
                <input type="text" name="filename" placeholder="Nama file (contoh: test.txt)" required>
                <textarea name="content" placeholder="Isi file..."></textarea>
                <div class="modal-actions">
                    <button type="submit" name="create_file" class="btn btn-primary">Buat</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('file')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Buat Folder -->
    <div id="modalFolder" class="modal">
        <div class="modal-content">
            <h3>📁 Buat Folder Baru</h3>
            <form method="POST">
                <input type="text" name="foldername" placeholder="Nama folder" required>
                <div class="modal-actions">
                    <button type="submit" name="create_folder" class="btn btn-primary">Buat</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('folder')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showModal(type) {
            document.getElementById('modal' + type.charAt(0).toUpperCase() + type.slice(1)).style.display = 'flex';
        }
        
        function hideModal(type) {
            document.getElementById('modal' + type.charAt(0).toUpperCase() + type.slice(1)).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
