<?php
// ============================================
// Simple File Manager with __Dir__ Protection
// Anti Error 500 & Security Enhanced
// ============================================

class SimpleFileManager {
    private $basePath;
    private $currentPath;
    private $error messages;
    
    public function __construct($basePath = null) {
        // Set base path dengan proteksi
        $this->basePath = $basePath ?? __DIR__;
        $this->currentPath = $this->basePath;
        $this->errorMessages = [];
        
        // Validasi awal
        $this->validatePath();
    }
    
    // Validasi path untuk mencegah error
    private function validatePath() {
        if (!is_dir($this->basePath)) {
            $this->setError("Base directory tidak ditemukan!");
            return false;
        }
        
        if (!is_readable($this->basePath)) {
            $this->setError("Base directory tidak bisa dibaca!");
            return false;
        }
        
        return true;
    }
    
    // Set error message
    private function setError($message) {
        $this->errorMessages[] = $message;
        error_log("FileManager Error: " . $message);
    }
    
    // Get error messages
    public function getErrors() {
        return $this->errorMessages;
    }
    
    // Navigasi ke direktori
    public function changeDirectory($dir) {
        try {
            $newPath = realpath($this->currentPath . DIRECTORY_SEPARATOR . $dir);
            
            // Validasi path
            if ($newPath === false) {
                $this->setError("Path tidak valid: $dir");
                return false;
            }
            
            // Cek apakah masih dalam base path
            if (strpos($newPath, $this->basePath) !== 0) {
                $this->setError("Akses ditolak: Di luar base directory!");
                return false;
            }
            
            // Cek apakah direktori
            if (!is_dir($newPath)) {
                $this->setError("Bukan sebuah direktori: $dir");
                return false;
            }
            
            // Cek readable
            if (!is_readable($newPath)) {
                $this->setError("Direktori tidak bisa dibaca: $dir");
                return false;
            }
            
            $this->currentPath = $newPath;
            return true;
            
        } catch (Exception $e) {
            $this->setError("Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Mendapatkan daftar file dan direktori
    public function getDirectoryList() {
        $items = [];
        
        try {
            $handle = opendir($this->currentPath);
            if (!$handle) {
                $this->setError("Tidak bisa membuka direktori");
                return [];
            }
            
            while (($item = readdir($handle)) !== false) {
                if ($item != '.' && $item != '..') {
                    $fullPath = $this->currentPath . DIRECTORY_SEPARATOR . $item;
                    
                    $items[] = [
                        'name' => $item,
                        'path' => $fullPath,
                        'type' => is_dir($fullPath) ? 'dir' : 'file',
                        'size' => is_file($fullPath) ? $this->formatSize(filesize($fullPath)) : '-',
                        'permission' => $this->getPermissions($fullPath),
                        'modified' => date("Y-m-d H:i:s", filemtime($fullPath))
                    ];
                }
            }
            
            closedir($handle);
            
            // Sort: direktori dulu, baru file
            usort($items, function($a, $b) {
                if ($a['type'] == $b['type']) {
                    return strcasecmp($a['name'], $b['name']);
                }
                return ($a['type'] == 'dir') ? -1 : 1;
            });
            
        } catch (Exception $e) {
            $this->setError("Error membaca direktori: " . $e->getMessage());
        }
        
        return $items;
    }
    
    // Format ukuran file
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    // Mendapatkan permission dalam format Unix
    private function getPermissions($file) {
        $perms = fileperms($file);
        
        if (($perms & 0xC000) == 0xC000) {
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) {
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            $info = 'p';
        } else {
            $info = 'u';
        }
        
        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
        
        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
        
        // Other
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
        
        return $info;
    }
    
    // Membuat file baru
    public function createFile($filename, $content = '') {
        try {
            $fullPath = $this->currentPath . DIRECTORY_SEPARATOR . $filename;
            
            // Validasi path
            if (strpos(realpath(dirname($fullPath)), $this->basePath) !== 0) {
                $this->setError("Akses ditolak!");
                return false;
            }
            
            if (file_exists($fullPath)) {
                $this->setError("File sudah ada!");
                return false;
            }
            
            if (file_put_contents($fullPath, $content) === false) {
                $this->setError("Gagal membuat file!");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->setError("Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Membuat direktori baru
    public function createDirectory($dirname) {
        try {
            $fullPath = $this->currentPath . DIRECTORY_SEPARATOR . $dirname;
            
            // Validasi path
            if (strpos(realpath(dirname($fullPath)), $this->basePath) !== 0) {
                $this->setError("Akses ditolak!");
                return false;
            }
            
            if (file_exists($fullPath)) {
                $this->setError("Direktori sudah ada!");
                return false;
            }
            
            if (!mkdir($fullPath, 0755, true)) {
                $this->setError("Gagal membuat direktori!");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->setError("Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Mendapatkan path saat ini
    public function getCurrentPath() {
        return $this->currentPath;
    }
    
    // Mendapatkan base path
    public function getBasePath() {
        return $this->basePath;
    }
    
    // Mendapatkan path relatif dari base
    public function getRelativePath() {
        return str_replace($this->basePath, '', $this->currentPath);
    }
}

// ============================================
// Contoh Penggunaan dengan HTML Output
// ============================================

// Inisialisasi dengan proteksi error
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan display error untuk keamanan

// Buffer output untuk mencegah error 500
ob_start();

try {
    // Buat instance file manager
    $fm = new SimpleFileManager(__DIR__);
    
    // Handle navigasi
    if (isset($_GET['dir']) && !empty($_GET['dir'])) {
        $fm->changeDirectory($_GET['dir']);
    }
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create_file'])) {
            $fm->createFile($_POST['filename'], $_POST['content'] ?? '');
        }
        if (isset($_POST['create_dir'])) {
            $fm->createDirectory($_POST['dirname']);
        }
    }
    
} catch (Exception $e) {
    // Log error tapi jangan tampilkan
    error_log("FileManager Fatal Error: " . $e->getMessage());
    die("Terjadi kesalahan sistem. Silakan coba lagi.");
}

// Output HTML
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple File Manager - Yang Mulia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .path-nav {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #dee2e6;
            font-family: monospace;
            word-break: break-all;
        }
        
        .path-nav a {
            color: #667eea;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .path-nav a:hover {
            background: #e9ecef;
        }
        
        .content {
            padding: 30px;
        }
        
        .actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
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
            border-radius: 15px;
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
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
        }
        
        .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .file-icon {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .file-name {
            color: #495057;
            text-decoration: none;
            font-weight: 500;
        }
        
        .file-name:hover {
            color: #667eea;
        }
        
        .dir-name {
            color: #28a745;
            font-weight: 600;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 15px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Simple File Manager</h1>
            <p>Untuk Yang Mulia Princess Incha</p>
        </div>
        
        <div class="path-nav">
            <strong>📍 Path:</strong> 
            <a href="?dir=<?php echo urlencode($fm->getBasePath()); ?>">Root</a>
            <?php
            $relativePath = $fm->getRelativePath();
            if (!empty($relativePath)) {
                $parts = explode(DIRECTORY_SEPARATOR, trim($relativePath, DIRECTORY_SEPARATOR));
                $current = $fm->getBasePath();
                foreach ($parts as $part) {
                    $current .= DIRECTORY_SEPARATOR . $part;
                    echo ' / <a href="?dir=' . urlencode($current) . '">' . htmlspecialchars($part) . '</a>';
                }
            }
            ?>
        </div>
        
        <div class="content">
            <?php if (!empty($fm->getErrors())): ?>
                <div class="error-message">
                    <?php foreach ($fm->getErrors() as $error): ?>
                        <div>⚠️ <?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="actions">
                <button class="btn btn-primary" onclick="showModal('file')">➕ Buat File Baru</button>
                <button class="btn btn-primary" onclick="showModal('dir')">📁 Buat Folder Baru</button>
                <a href="?dir=<?php echo urlencode(dirname($fm->getCurrentPath())); ?>" class="btn btn-secondary">⬆️ Kembali</a>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Ukuran</th>
                        <th>Permission</th>
                        <th>Terakhir Diubah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fm->getDirectoryList() as $item): ?>
                        <tr>
                            <td>
                                <span class="file-icon">
                                    <?php echo $item['type'] == 'dir' ? '📁' : '📄'; ?>
                                </span>
                                <?php if ($item['type'] == 'dir'): ?>
                                    <a href="?dir=<?php echo urlencode($item['path']); ?>" class="file-name dir-name">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="file-name">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['type'] == 'dir' ? 'Folder' : 'File'; ?></td>
                            <td><?php echo $item['size']; ?></td>
                            <td><code><?php echo $item['permission']; ?></code></td>
                            <td><?php echo $item['modified']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Simple File Manager dengan proteksi anti error 500 | Untuk Yang Mulia Princess Incha 👑</p>
        </div>
    </div>
    
    <!-- Modal Buat File -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <h3>📄 Buat File Baru</h3>
            <form method="POST">
                <input type="text" name="filename" placeholder="Nama file (contoh: index.php)" required>
                <textarea name="content" placeholder="Isi file..."></textarea>
                <button type="submit" name="create_file" class="btn btn-primary">Buat File</button>
                <button type="button" class="btn btn-secondary" onclick="hideModal('file')">Batal</button>
            </form>
        </div>
    </div>
    
    <!-- Modal Buat Direktori -->
    <div id="dirModal" class="modal">
        <div class="modal-content">
            <h3>📁 Buat Folder Baru</h3>
            <form method="POST">
                <input type="text" name="dirname" placeholder="Nama folder" required>
                <button type="submit" name="create_dir" class="btn btn-primary">Buat Folder</button>
                <button type="button" class="btn btn-secondary" onclick="hideModal('dir')">Batal</button>
            </form>
        </div>
    </div>
    
    <script>
        function showModal(type) {
            document.getElementById(type + 'Modal').style.display = 'flex';
        }
        
        function hideModal(type) {
            document.getElementById(type + 'Modal').style.display = 'none';
        }
        
        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>
