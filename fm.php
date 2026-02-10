<?php
declare(strict_types=1);

$BASE_DIR = __DIR__;

// Login
$AUTH_USER = 'admin';

$AUTH_PASS_HASH = '$2y$10$wxgoSCbD3ah4omQR4NsJBeDKbOQWBboGh6Ndhua1DhnxocMKhPgRW';

$EDIT_ALLOW_EXT = ['txt','md','log','json','xml','csv','ini','conf','php','html'];

$UPLOAD_BLOCK_EXT = [
  'user.ini'
];

$MAX_UPLOAD_BYTES = 50 * 1024 * 1024; // 50 MB per file
$MAX_EDIT_BYTES   = 512 * 1024;       // 512 KB max for browser editor

/* =========================
   BOOTSTRAP
   ========================= */

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!headers_sent() && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) {
  ini_set('session.cookie_secure', '1');
}
session_start();

$BASE_REAL = realpath($BASE_DIR);
if ($BASE_REAL === false || !is_dir($BASE_REAL)) {
  http_response_code(500);
  exit("BASE_DIR tidak valid.");
}

/* =========================
   HELPERS
   ========================= */

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function clean_rel(string $path): string {
  $path = str_replace('\\', '/', $path);
  $path = str_replace("\0", '', $path);
  $path = trim($path);
  $path = ltrim($path, '/');

  $parts = explode('/', $path);
  $safe = [];
  foreach ($parts as $p) {
    if ($p === '' || $p === '.') continue;
    if ($p === '..') { array_pop($safe); continue; }
    $safe[] = $p;
  }
  return implode('/', $safe);
}

function resolve_path(string $base_real, string $rel, bool $must_exist = true): string|false {
  $rel = clean_rel($rel);
  $joined = rtrim($base_real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ($rel === '' ? '' : $rel);

  if ($must_exist) {
    $rp = realpath($joined);
    if ($rp === false) return false;
    if (strpos($rp, $base_real) !== 0) return false; // jail
    return $rp;
  }

  // for non-existing targets: jail check on parent
  $parent = realpath(dirname($joined));
  if ($parent === false) return false;
  if (strpos($parent, $base_real) !== 0) return false;
  return $joined;
}

function is_symlink(string $path): bool { return is_link($path); }

function bytes_human(int $bytes): string {
  $units = ['B','KB','MB','GB','TB'];
  $i = 0; $n = (float)$bytes;
  while ($n >= 1024 && $i < count($units)-1) { $n /= 1024; $i++; }
  return ($i === 0) ? ($bytes.' '.$units[$i]) : (number_format($n, 2).' '.$units[$i]);
}

function perms_str(int $perms): string {
  $type = 'u';
  if (($perms & 0x4000) === 0x4000) $type = 'd';
  elseif (($perms & 0xA000) === 0xA000) $type = 'l';
  elseif (($perms & 0x8000) === 0x8000) $type = '-';

  $map = [
    0x0100 => 'r', 0x0080 => 'w', 0x0040 => 'x',
    0x0020 => 'r', 0x0010 => 'w', 0x0008 => 'x',
    0x0004 => 'r', 0x0002 => 'w', 0x0001 => 'x',
  ];
  $s = $type;
  foreach ($map as $bit => $ch) $s .= (($perms & $bit) ? $ch : '-');

  if ($perms & 0x0800) $s[3] = ($s[3] === 'x') ? 's' : 'S';
  if ($perms & 0x0400) $s[6] = ($s[6] === 'x') ? 's' : 'S';
  if ($perms & 0x0200) $s[9] = ($s[9] === 'x') ? 't' : 'T';

  return $s;
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}

function csrf_check(): void {
  $t = $_POST['csrf'] ?? '';
  if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$t)) {
    http_response_code(400);
    exit('CSRF token invalid.');
  }
}

function require_login(string $user, string $hash): void {
  if (!empty($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
  }

  if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) return;

  $err = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'login')) {
    $u = (string)($_POST['u'] ?? '');
    $p = (string)($_POST['p'] ?? '');
    if (hash_equals($user, $u) && password_verify($p, $hash)) {
      $_SESSION['logged_in'] = true;
      session_regenerate_id(true);
      header('Location: '.$_SERVER['PHP_SELF']);
      exit;
    } else {
      $err = 'Login gagal.';
    }
  }

  echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>Login</title>';
  echo '<style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; background:#0b1220; color:#e5e7eb; display:flex; min-height:100vh; align-items:center; justify-content:center; margin:0;}
    .card{width:min(420px,92vw); background:#111a2e; border:1px solid #26324d; border-radius:14px; padding:18px;}
    label{display:block; font-size:14px; margin:10px 0 6px;}
    input{width:100%; padding:10px 12px; border-radius:10px; border:1px solid #2b3a59; background:#0b1220; color:#e5e7eb;}
    button{margin-top:14px; width:100%; padding:10px 12px; border-radius:10px; border:0; background:#3b82f6; color:#fff; font-weight:600; cursor:pointer;}
    .err{color:#fca5a5; font-size:14px; margin-top:10px;}
    .hint{color:#93c5fd; font-size:12px; margin-top:8px; opacity:.9;}
  </style></head><body>';
  echo '<div class="card"><h2 style="margin:0 0 6px;">Login</h2><div class="hint">Akseskan hanya untuk admin.</div>';
  if ($err) echo '<div class="err">'.h($err).'</div>';
  echo '<form method="post">
    <input type="hidden" name="action" value="login">
    <label>Username</label><input name="u" autocomplete="username" required>
    <label>Password</label><input name="p" type="password" autocomplete="current-password" required>
    <button type="submit">Masuk</button>
  </form></div></body></html>';
  exit;
}

/* =========================
   AUTH
   ========================= */
require_login($AUTH_USER, $AUTH_PASS_HASH);

/* =========================
   STATE
   ========================= */

$rel = clean_rel((string)($_GET['p'] ?? ''));
$cwd = resolve_path($BASE_REAL, $rel, true);
if ($cwd === false || !is_dir($cwd) || is_symlink($cwd)) {
  $rel = '';
  $cwd = $BASE_REAL;
}

$msg = '';
$err = '';

/* =========================
   ACTIONS
   ========================= */

$action = (string)($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
  csrf_check();

  $item = (string)($_POST['item'] ?? '');
  $item = str_replace("\0", '', $item);
  $item_base = basename($item);

  $item_rel  = ($rel === '' ? $item_base : $rel . '/' . $item_base);
  $item_abs  = resolve_path($BASE_REAL, $item_rel, true);

  if ($action === 'upload') {
    if (empty($_FILES['up'])) {
      $err = 'Tidak ada file.';
    } else {
      $files = $_FILES['up'];
      $count = is_array($files['name']) ? count($files['name']) : 1;
      $ok = 0;

      for ($i = 0; $i < $count; $i++) {
        $name = is_array($files['name']) ? (string)$files['name'][$i] : (string)$files['name'];
        $tmp  = is_array($files['tmp_name']) ? (string)$files['tmp_name'][$i] : (string)$files['tmp_name'];
        $size = is_array($files['size']) ? (int)$files['size'][$i] : (int)$files['size'];
        $e    = is_array($files['error']) ? (int)$files['error'][$i] : (int)$files['error'];

        if ($e !== UPLOAD_ERR_OK) { $err = 'Ada file gagal diupload (error code '.$e.').'; continue; }
        if ($size > $MAX_UPLOAD_BYTES) { $err = 'Ukuran file terlalu besar: '.h($name); continue; }

        // sanitize filename
        $safeName = preg_replace('/[^\w\.\-\s\(\)\[\]]+/u', '_', $name);
        $safeName = trim((string)$safeName);
        if ($safeName === '' || $safeName === '.' || $safeName === '..') { $err = 'Nama file tidak valid.'; continue; }

        // block dangerous extensions
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array($ext, $UPLOAD_BLOCK_EXT, true)) {
          $err = 'Ekstensi diblokir untuk keamanan: '.h($safeName);
          continue;
        }

        // upload goes to CURRENT DIRECTORY (folder yang sedang dibuka)
        $dest_rel = ($rel === '' ? $safeName : $rel . '/' . $safeName);
        $dest_abs = resolve_path($BASE_REAL, $dest_rel, false);
        if ($dest_abs === false) { $err = 'Path tujuan tidak valid.'; continue; }

        if (@move_uploaded_file($tmp, $dest_abs)) {
          @chmod($dest_abs, 0640);
          $ok++;
        } else {
          $err = 'Gagal memindahkan file: '.h($safeName);
        }
      }

      if ($ok > 0) $msg = "Upload sukses: {$ok} file.";
    }
  }

  elseif ($action === 'delete') {
    if ($item_abs === false) {
      $err = 'Item tidak ditemukan.';
    } elseif (is_symlink($item_abs)) {
      $err = 'Operasi pada symlink ditolak.';
    } elseif (is_dir($item_abs)) {
      $scan = @scandir($item_abs);
      if ($scan === false) $err = 'Gagal membaca folder.';
      else {
        $non = array_values(array_diff($scan, ['.','..']));
        if (count($non) > 0) $err = 'Folder tidak kosong (hanya bisa hapus folder kosong).';
        else {
          if (@rmdir($item_abs)) $msg = 'Folder dihapus.';
          else $err = 'Gagal menghapus folder.';
        }
      }
    } else {
      if (@unlink($item_abs)) $msg = 'File dihapus.';
      else $err = 'Gagal menghapus file.';
    }
  }

  elseif ($action === 'rename') {
    $new = trim(str_replace("\0", '', (string)($_POST['new_name'] ?? '')));

    if ($item_abs === false) {
      $err = 'Item tidak ditemukan.';
    } elseif (is_symlink($item_abs)) {
      $err = 'Operasi pada symlink ditolak.';
    } elseif ($new === '' || $new === '.' || $new === '..') {
      $err = 'Nama baru tidak valid.';
    } else {
      $newBase = basename($new);
      $newBase = preg_replace('/[^\w\.\-\s\(\)\[\]]+/u', '_', (string)$newBase);
      $newBase = trim((string)$newBase);
      if ($newBase === '' || $newBase === '.' || $newBase === '..') {
        $err = 'Nama baru tidak valid.';
      } else {
        $target_rel = ($rel === '' ? $newBase : $rel . '/' . $newBase);
        $target_abs = resolve_path($BASE_REAL, $target_rel, false);
        if ($target_abs === false) $err = 'Target tidak valid.';
        else {
          if (file_exists($target_abs)) $err = 'Target sudah ada.';
          else {
            if (@rename($item_abs, $target_abs)) $msg = 'Rename berhasil.';
            else $err = 'Rename gagal.';
          }
        }
      }
    }
  }

  elseif ($action === 'save') {
    $file = basename((string)($_POST['file'] ?? ''));
    $fileRel  = ($rel === '' ? $file : $rel . '/' . $file);
    $fileAbs  = resolve_path($BASE_REAL, $fileRel, true);

    if ($fileAbs === false || !is_file($fileAbs)) {
      $err = 'File tidak ditemukan.';
    } elseif (is_symlink($fileAbs)) {
      $err = 'Operasi pada symlink ditolak.';
    } else {
      $ext = strtolower(pathinfo($fileAbs, PATHINFO_EXTENSION));
      if (!in_array($ext, $EDIT_ALLOW_EXT, true)) {
        $err = 'Ekstensi tidak diizinkan untuk edit (editor dibatasi untuk file teks).';
      } elseif (filesize($fileAbs) > $MAX_EDIT_BYTES) {
        $err = 'File terlalu besar untuk diedit via browser.';
      } else {
        $content = (string)($_POST['content'] ?? '');
        $tmp = $fileAbs . '.tmp_' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $content, LOCK_EX) !== false) {
          @chmod($tmp, 0640);
          if (@rename($tmp, $fileAbs)) $msg = 'Perubahan disimpan.';
          else { @unlink($tmp); $err = 'Gagal menyimpan (rename).'; }
        } else {
          $err = 'Gagal menyimpan.';
        }
      }
    }
  }
}

/* =========================
   DOWNLOAD
   ========================= */

if (isset($_GET['dl'])) {
  $dl = clean_rel((string)$_GET['dl']);
  $abs = resolve_path($BASE_REAL, $dl, true);

  if ($abs === false || !is_file($abs) || is_symlink($abs)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
  }

  $bn = basename($abs);
  header('Content-Type: application/octet-stream');
  header('Content-Length: ' . filesize($abs));
  header('Content-Disposition: attachment; filename="'.rawurlencode($bn).'"');
  header('X-Content-Type-Options: nosniff');
  readfile($abs);
  exit;
}

/* =========================
   EDIT VIEW
   ========================= */

$editFile = isset($_GET['edit']) ? basename((string)$_GET['edit']) : '';
$editAbs = false;
$editContent = '';
$editInfo = '';

if ($editFile !== '') {
  $editRel = ($rel === '' ? $editFile : $rel . '/' . $editFile);
  $editAbs = resolve_path($BASE_REAL, $editRel, true);

  if ($editAbs === false || !is_file($editAbs) || is_symlink($editAbs)) {
    $err = 'File edit tidak valid.';
    $editAbs = false;
  } else {
    $ext = strtolower(pathinfo($editAbs, PATHINFO_EXTENSION));
    if (!in_array($ext, $EDIT_ALLOW_EXT, true)) {
      $err = 'Ekstensi tidak diizinkan untuk edit.';
      $editAbs = false;
    } elseif (filesize($editAbs) > $MAX_EDIT_BYTES) {
      $err = 'File terlalu besar untuk diedit via browser.';
      $editAbs = false;
    } else {
      $editContent = (string)@file_get_contents($editAbs);
      $st = @stat($editAbs);
      $editInfo = $st ? (bytes_human((int)$st['size']).' | '.date('Y-m-d H:i:s', (int)$st['mtime'])) : '';
    }
  }
}

/* =========================
   LISTING
   ========================= */

$items = @scandir($cwd);
if ($items === false) $items = [];

$crumbs = [];
if ($rel !== '') {
  $acc = '';
  foreach (explode('/', $rel) as $p) {
    $acc = ($acc === '' ? $p : $acc.'/'.$p);
    $crumbs[] = [$p, $acc];
  }
}

/* =========================
   UI
   ========================= */
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mini File Manager</title>
  <style>
    :root{--bg:#0b1220;--card:#111a2e;--muted:#94a3b8;--line:#24324d;--btn:#3b82f6;--danger:#ef4444;--warn:#f59e0b;}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; background:var(--bg); color:#e5e7eb; margin:0;}
    .wrap{max-width:1100px; margin:0 auto; padding:18px;}
    .top{display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;}
    .card{background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px;}
    a{color:#93c5fd; text-decoration:none;}
    a:hover{text-decoration:underline;}
    .msg{padding:10px 12px; border-radius:12px; margin:12px 0;}
    .ok{background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.25); color:#bbf7d0;}
    .er{background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.25); color:#fecaca;}
    .muted{color:var(--muted);}
    .path{display:flex; gap:8px; align-items:center; flex-wrap:wrap;}
    .pill{display:inline-flex; gap:8px; align-items:center; padding:6px 10px; border-radius:999px; border:1px solid var(--line); background:rgba(255,255,255,.03);}
    table{width:100%; border-collapse:separate; border-spacing:0; overflow:hidden; border-radius:14px; border:1px solid var(--line); background:rgba(255,255,255,.02);}
    th,td{padding:10px 10px; border-bottom:1px solid var(--line); font-size:14px; vertical-align:middle;}
    th{color:#cbd5e1; text-align:left; background:rgba(255,255,255,.03);}
    tr:last-child td{border-bottom:0;}
    .right{text-align:right;}
    .actions{display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;}
    .btn{display:inline-flex; align-items:center; gap:8px; padding:8px 10px; border-radius:10px; border:1px solid var(--line); background:rgba(255,255,255,.03); color:#e5e7eb; cursor:pointer; font-size:13px;}
    .btn:hover{background:rgba(255,255,255,.06);}
    .btn-pri{background:rgba(59,130,246,.18); border-color:rgba(59,130,246,.35);}
    .btn-del{background:rgba(239,68,68,.14); border-color:rgba(239,68,68,.30);}
    .btn-warn{background:rgba(245,158,11,.14); border-color:rgba(245,158,11,.30);}
    input[type="text"], textarea{width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--line); background:rgba(255,255,255,.03); color:#e5e7eb;}
    textarea{min-height:50vh; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size:13px;}
    .grid{display:grid; grid-template-columns: 1.2fr .8fr; gap:12px;}
    @media (max-width: 980px){ .grid{grid-template-columns:1fr;} }
    .small{font-size:12px;}
    .kbd{font-family:ui-monospace; font-size:12px; padding:2px 6px; border:1px solid var(--line); border-radius:8px; background:rgba(255,255,255,.03); color:#cbd5e1;}
  </style>
</head>
<body>
<div class="wrap">

  <div class="top">
    <div class="path">
      <div class="pill"><strong>Mini File Manager</strong> <span class="muted small">BASE: <?=h($BASE_REAL)?></span></div>
      <div class="pill">
        <span class="muted">Path:</span>
        <a href="<?=h($_SERVER['PHP_SELF'])?>">/</a>
        <?php foreach ($crumbs as [$name,$p]): ?>
          <span class="muted">/</span> <a href="<?=h($_SERVER['PHP_SELF'])?>?p=<?=h(urlencode($p))?>"><?=h($name)?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="pill">
      <a href="<?=h($_SERVER['PHP_SELF'])?>?logout=1" class="muted">Logout</a>
    </div>
  </div>

  <?php if ($msg): ?><div class="msg ok"><?=h($msg)?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg er"><?=h($err)?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h3 style="margin:0 0 10px;">Listing</h3>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th class="right">Size</th>
            <th>Perm</th>
            <th>Owner/Group</th>
            <th>Modified</th>
            <th class="right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rel !== ''): ?>
            <tr>
              <td colspan="6">
                <?php $up = dirname($rel); $up = ($up === '.' ? '' : $up); ?>
                <a href="<?=h($_SERVER['PHP_SELF'])?>?p=<?=h(urlencode($up))?>">..</a>
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($items as $it):
            if ($it === '.' || $it === '..') continue;

            $abs = resolve_path($BASE_REAL, ($rel === '' ? $it : $rel.'/'.$it), true);
            if ($abs === false) continue;

            $isLink = is_symlink($abs);
            $isDir  = is_dir($abs);
            $isFile = is_file($abs);

            $st = @stat($abs);
            $size = ($isFile && $st) ? (int)$st['size'] : 0;
            $mtime = ($st) ? date('Y-m-d H:i:s', (int)$st['mtime']) : '-';
            $perms = ($st) ? perms_str((int)$st['mode']) : '-';
            $oct   = ($st) ? substr(sprintf('%o', (int)$st['mode']), -4) : '----';
            $owner = ($st) ? (string)$st['uid'] : '-';
            $group = ($st) ? (string)$st['gid'] : '-';

            $dlRel = ($rel === '' ? $it : $rel.'/'.$it);

            $typeLabel = $isLink ? 'LINK' : ($isDir ? 'DIR' : ($isFile ? 'FILE' : 'OTHER'));
          ?>
            <tr>
              <td>
                <?php if ($isDir && !$isLink): ?>
                  [<?=h($typeLabel)?>] <a href="<?=h($_SERVER['PHP_SELF'])?>?p=<?=h(urlencode($dlRel))?>"><?=h($it)?></a>
                <?php else: ?>
                  [<?=h($typeLabel)?>] <?=h($it)?>
                <?php endif; ?>
              </td>
              <td class="right"><?= $isFile ? h(bytes_human($size)) : '-' ?></td>
              <td><span class="kbd"><?=h($perms)?></span> <span class="muted small">(<?=h($oct)?>)</span></td>
              <td class="muted"><?=h($owner)?>/<?=h($group)?></td>
              <td class="muted"><?=h($mtime)?></td>
              <td class="right">
                <div class="actions">
                  <?php if ($isFile && !$isLink): ?>
                    <a class="btn btn-pri" href="<?=h($_SERVER['PHP_SELF'])?>?p=<?=h(urlencode($rel))?>&dl=<?=h(urlencode($dlRel))?>">Download</a>
                    <?php
                      $ext = strtolower(pathinfo($it, PATHINFO_EXTENSION));
                      if (in_array($ext, $EDIT_ALLOW_EXT, true) && $size <= $MAX_EDIT_BYTES):
                    ?>
                      <a class="btn" href="<?=h($_SERVER['PHP_SELF'])?>?p=<?=h(urlencode($rel))?>&edit=<?=h(urlencode($it))?>">Edit</a>
                    <?php endif; ?>
                  <?php endif; ?>

                  <?php if (!$isLink): ?>
                    <form method="post" style="display:inline-flex; gap:8px; align-items:center;">
                      <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                      <input type="hidden" name="action" value="rename">
                      <input type="hidden" name="item" value="<?=h($it)?>">
                      <input type="text" name="new_name" placeholder="Rename" style="width:160px;">
                      <button class="btn btn-warn" type="submit">Rename</button>
                    </form>

                    <form method="post" style="display:inline;">
                      <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="item" value="<?=h($it)?>">
                      <button class="btn btn-del" type="submit" onclick="return confirm('Delete: <?=h(addslashes($it))?> ?')">Delete</button>
                    </form>
                  <?php else: ?>
                    <span class="muted small">Blocked</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (count($items) <= 2 && $rel === ''): ?>
            <tr><td colspan="6" class="muted">Empty.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="muted small" style="margin-top:10px;">
        Notes: symlink blocked; editor restricted to safe text types; operations jailed under BASE_DIR.
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 10px;">Upload</h3>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="upload">
        <div class="muted small" style="margin-bottom:8px;">
          Destination: <span class="kbd"><?=h($rel === '' ? '/' : '/'.$rel)?></span><br>
          Limit: <?=h(bytes_human($MAX_UPLOAD_BYTES))?> per file.
        </div>
        <input type="file" name="up[]" multiple required>
        <button class="btn btn-pri" type="submit" style="margin-top:10px;">Upload</button>
      </form>

      <?php if ($editAbs !== false): ?>
        <hr style="border:0;border-top:1px solid var(--line); margin:16px 0;">
        <h3 style="margin:0 0 10px;">Edit: <?=h($editFile)?></h3>
        <div class="muted small" style="margin-bottom:8px;"><?=h($editInfo)?></div>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="file" value="<?=h($editFile)?>">
          <textarea name="content"><?=h($editContent)?></textarea>
          <button class="btn btn-pri" type="submit" style="margin-top:10px;">Save</button>
          <a class="btn" href="<?=h($_SERVER['PHP_SELF'])?>?p=<?=h(urlencode($rel))?>" style="margin-top:10px;">Cancel</a>
        </form>
      <?php endif; ?>
    </div>
  </div>

</div>
</body>
</html>
