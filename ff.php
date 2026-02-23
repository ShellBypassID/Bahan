<?php
// ============================================
// SUPER SIMPLE FILE MANAGER
// Untuk Yang Mulia Princess Incha
// ============================================

error_reporting(0);
ini_set('display_errors', 0);

// Base directory
$root = __DIR__;
$current = isset($_GET['dir']) ? $_GET['dir'] : $root;
$current = realpath($current);
if (strpos($current, $root) !== 0) $current = $root;

// Handle actions
$message = '';

// Delete
if (isset($_GET['delete'])) {
    $target = $current . '/' . $_GET['delete'];
    if (is_file($target)) unlink($target);
    if (is_dir($target)) rmdir($target);
    header('Location: ?dir=' . urlencode($current));
    exit;
}

// Rename
if (isset($_POST['rename'])) {
    $old = $current . '/' . $_POST['old'];
    $new = $current . '/' . $_POST['new'];
    if (file_exists($old) && !file_exists($new)) {
        rename($old, $new);
        $message = "Berhasil rename!";
    }
    header('Location: ?dir=' . urlencode($current));
    exit;
}

// Save edit
if (isset($_POST['save'])) {
    $file = $current . '/' . $_POST['file'];
    file_put_contents($file, $_POST['content']);
    $message = "File berhasil disimpan!";
    header('Location: ?dir=' . urlencode($current));
    exit;
}

// Get file list
$items = [];
if (is_dir($current)) {
    $files = scandir($current);
    foreach ($files as $f) {
        if ($f != '.' && $f != '..') {
            $path = $current . '/' . $f;
            $items[] = [
                'name' => $f,
                'path' => $path,
                'is_dir' => is_dir($path),
                'size' => is_file($path) ? filesize($path) : 0,
                'modified' => date('Y-m-d H:i', filemtime($path))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager - Princess Incha</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #9b59b6;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.9; }
        .path {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            font-family: monospace;
            font-size: 14px;
        }
        .path a {
            color: #9b59b6;
            text-decoration: none;
        }
        .path a:hover { text-decoration: underline; }
        .content { padding: 20px; }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #ddd;
            color: #333;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover { background: #f5f5f5; }
        .folder {
            color: #e67e22;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .file {
            color: #2980b9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            margin: 0 2px;
        }
        .btn-edit { background: #f39c12; color: white; }
        .btn-rename { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-back { background: #95a5a6; color: white; padding: 8px 15px; margin-bottom: 15px; display: inline-block; }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
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
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-content h3 { margin-bottom: 20px; color: #333; }
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
            height: 300px;
            font-size: 14px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-primary {
            background: #9b59b6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .size {
            font-family: monospace;
            color: #7f8c8d;
        }
        .actions {
            white-space: nowrap;
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
            <a href="?dir=<?php echo urlencode($root); ?>">Root</a>
            <?php
            $rel = str_replace($root, '', $current);
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
            <?php if ($message): ?>
                <div class="message">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($current != $root): ?>
                <a href="?dir=<?php echo urlencode(dirname($current)); ?>" class="btn btn-back">⬆️ Kembali</a>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Ukuran</th>
                        <th>Terakhir diubah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['is_dir']): ?>
                                    <a href="?dir=<?php echo urlencode($item['path']); ?>" class="folder">
                                        📁 <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="file">
                                        📄 <?php echo htmlspecialchars($item['name']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="size">
                                <?php echo $item['is_dir'] ? '-' : number_format($item['size']) . ' bytes'; ?>
                            </td>
                            <td><?php echo $item['modified']; ?></td>
                            <td class="actions">
                                <?php if (!$item['is_dir']): ?>
                                    <button class="btn btn-edit" onclick="editFile('<?php echo addslashes($item['name']); ?>')">✏️ Edit</button>
                                <?php endif; ?>
                                <button class="btn btn-rename" onclick="renameItem('<?php echo addslashes($item['name']); ?>', '<?php echo $item['is_dir'] ? 'folder' : 'file'; ?>')">📝 Rename</button>
                                <a href="?dir=<?php echo urlencode($current); ?>&delete=<?php echo urlencode($item['name']); ?>" 
                                   class="btn btn-delete" 
                                   onclick="return confirm('Hapus <?php echo $item['name']; ?>?')">🗑️ Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Edit File -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <h3>✏️ Edit File</h3>
            <form method="POST">
                <input type="hidden" name="file" id="editFilename">
                <textarea name="content" id="editContent" placeholder="Isi file..."></textarea>
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
            // Load file content via AJAX
            fetch('load.php?file=' + encodeURIComponent(filename) + '&dir=<?php echo urlencode($current); ?>')
                .then(r => r.text())
                .then(content => {
                    document.getElementById('editContent').value = content;
                    document.getElementById('modalEdit').style.display = 'flex';
                });
        }
        
        function renameItem(oldName, type) {
            document.getElementById('renameOld').value = oldName;
            document.getElementById('renameNew').value = oldName;
            document.getElementById('modalRename').style.display = 'flex';
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
// Create load.php for ajax file loading
file_put_contents('load.php', '<?php
error_reporting(0);
$file = $_GET["dir"] . "/" . $_GET["file"];
if (file_exists($file) && is_file($file)) {
    echo file_get_contents($file);
}');
?>
