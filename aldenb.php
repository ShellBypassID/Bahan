<?php
// edit-index.php - File editor untuk Yang Mulia
$file = 'index.php';

// Ambil konten lama jika ada
$current_content = '';
if(file_exists($file)) {
    $current_content = file_get_contents($file);
}

// Proses simpan
if(isset($_POST['code'])) {
    file_put_contents($file, $_POST['code']);
    $message = "✅ index.php BERHASIL DIUPDATE!";
    $current_content = $_POST['code']; // Update tampilan
}

// Fungsi untuk format tampilan kode
function highlightCode($code) {
    if(empty($code)) return '<i style="color:#666;">(file kosong atau belum ada)</i>';
    return highlight_string($code, true);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Index.php Editor - Yang Mulia</title>
    <style>
        body {
            background: #0a0e1a;
            color: #00ff9d;
            font-family: 'Courier New', monospace;
            padding: 30px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #00ff9d;
            border-bottom: 2px solid #00ff9d;
            padding-bottom: 15px;
            text-shadow: 0 0 10px #00ff9d;
        }
        h2 {
            color: #00ff9d;
            margin-top: 30px;
        }
        .message {
            background: #003300;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .current-code {
            background: #0f1320;
            border: 1px solid #00ff9d;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            overflow-x: auto;
        }
        .current-code pre {
            margin: 0;
            color: #00ff9d;
        }
        textarea {
            width: 100%;
            height: 400px;
            background: #1a1e2a;
            border: 2px solid #00ff9d;
            color: #00ff9d;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border-radius: 5px;
            line-height: 1.6;
        }
        textarea:focus {
            outline: none;
            box-shadow: 0 0 20px rgba(0,255,157,0.3);
        }
        button {
            background: #00ff9d;
            color: #0a0e1a;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }
        button:hover {
            background: #00cc7a;
            box-shadow: 0 0 20px #00ff9d;
        }
        .info {
            background: #1a1e2a;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #00ff9d;
        }
        .file-stats {
            color: #8899cc;
            font-size: 14px;
            margin-top: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #00ff9d;
            text-decoration: none;
            border: 1px solid #00ff9d;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .back-link:hover {
            background: #00ff9d;
            color: #0a0e1a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ INDEX.PHP EDITOR - UNTUK YANG MULIA ⚡</h1>
        
        <?php if(isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- TAMPILAN ISI INDEX.PHP SAAT INI -->
        <h2>📄 ISI INDEX.PHP SAAT INI:</h2>
        <div class="current-code">
            <?php 
            if(!empty($current_content)) {
                echo highlightCode($current_content);
            } else {
                echo "<p style='color:#ffaa00;'>⚠️ File index.php belum ada atau masih kosong!</p>";
            }
            ?>
        </div>
        
        <div class="file-stats">
            <?php if(file_exists($file)): ?>
                📁 Lokasi: <?php echo __DIR__ . '/' . $file; ?><br>
                💾 Ukuran: <?php echo filesize($file); ?> bytes<br>
                🕐 Terakhir diubah: <?php echo date('d-m-Y H:i:s', filemtime($file)); ?>
            <?php else: ?>
                📁 File index.php akan dibuat saat pertama kali disimpan
            <?php endif; ?>
        </div>
        
        <!-- FORM EDITOR -->
        <h2>✏️ EDIT INDEX.PHP:</h2>
        <div class="info">
            💡 Tips: Edit kode di bawah ini, lalu klik SIMPAN untuk mengupdate index.php
        </div>
        
        <form method="post">
            <textarea name="code" placeholder="Tulis kode PHP/HTML untuk index.php di sini..."><?php echo htmlspecialchars($current_content); ?></textarea>
            <br>
            <button type="submit">💾 SIMPAN PERUBAHAN</button>
        </form>
        
        <!-- PREVIEW KODE DEFAULT (jika kosong) -->
        <?php if(empty($current_content)): ?>
        <div style="margin-top: 30px; padding: 20px; background: #1a1e2a; border-radius: 5px;">
            <h3 style="color: #ffaa00;">📋 Contoh kode index.php:</h3>
            <pre style="color: #8899cc; overflow-x: auto;">
&lt;?php
// Website untuk Yang Mulia
echo "&lt;h1&gt;Selamat datang, Yang Mulia!&lt;/h1&gt;";
echo "&lt;p&gt;Website ini berjalan dengan baik.&lt;/p&gt;";
?&gt;
            </pre>
            <p style="color: #666;">Copy paste kode di atas ke editor lalu simpan.</p>
        </div>
        <?php endif; ?>
        
        <a href="index.php" class="back-link" target="_blank">👁️ Lihat Hasil Index.php</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="back-link" style="margin-left: 10px;">🔄 Refresh</a>
    </div>
</body>
</html>
