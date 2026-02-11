<?php
// ============================================
// SERVER DETECTOR & FILE MANAGER BUILDER
// ============================================

// Tampilkan semua error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ============================================
// DETECT SERVER CAPABILITIES
// ============================================
echo "<!DOCTYPE html>
<html>
<head>
    <title>Server Detector & File Manager</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; background: #e8f5e8; border-color: green; }
        .warning { color: orange; background: #fff8e1; border-color: orange; }
        .error { color: red; background: #ffebee; border-color: red; }
        .info { color: blue; background: #e3f2fd; border-color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Server Capability Detector</h1>";

// 1. PHP Version
$php_version = PHP_VERSION;
$php_major = (float)PHP_MAJOR_VERSION;
echo "<div class='section " . ($php_major >= 5.6 ? 'success' : 'error') . "'>
    <h3>PHP Version: $php_version</h3>
    <p>" . ($php_major >= 7.4 ? "✅ Version OK" : ($php_major >= 5.6 ? "⚠️ Acceptable" : "❌ Too old")) . "</p>
</div>";

// 2. Check critical functions
$critical_functions = [
    'session_start', 'scandir', 'file_exists', 'is_dir', 'is_file',
    'mkdir', 'unlink', 'rmdir', 'move_uploaded_file', 'realpath',
    'htmlspecialchars', 'basename', 'pathinfo'
];

$missing_critical = [];
foreach ($critical_functions as $func) {
    if (!function_exists($func)) {
        $missing_critical[] = $func;
    }
}

echo "<div class='section " . (empty($missing_critical) ? 'success' : 'error') . "'>
    <h3>Critical PHP Functions</h3>";
if (empty($missing_critical)) {
    echo "<p>✅ All critical functions available</p>";
} else {
    echo "<p>❌ Missing functions: " . implode(', ', $missing_critical) . "</p>";
}
echo "</div>";

// 3. Check optional functions
$optional_functions = [
    'ini_set', 'error_reporting', 'file_get_contents', 'file_put_contents',
    'filesize', 'filemtime', 'date', 'header', 'urlencode',
    'preg_match', 'str_replace', 'trim', 'explode', 'implode'
];

$missing_optional = [];
foreach ($optional_functions as $func) {
    if (!function_exists($func)) {
        $missing_optional[] = $func;
    }
}

echo "<div class='section " . (empty($missing_optional) ? 'success' : 'warning') . "'>
    <h3>Optional PHP Functions</h3>";
if (empty($missing_optional)) {
    echo "<p>✅ All optional functions available</p>";
} else {
    echo "<p>⚠️ Missing optional functions: " . implode(', ', $missing_optional) . "</p>";
}
echo "</div>";

// 4. Check server permissions
$write_test_dir = __DIR__ . '/test_write_' . time();
$write_test_file = $write_test_dir . '/test.txt';

$can_create_dir = @mkdir($write_test_dir, 0755);
$can_write_file = false;
$can_delete = false;

if ($can_create_dir) {
    $can_write_file = @file_put_contents($write_test_file, 'test') !== false;
    if ($can_write_file) {
        $can_delete = @unlink($write_test_file) && @rmdir($write_test_dir);
    }
}

echo "<div class='section " . ($can_write_file ? 'success' : ($can_create_dir ? 'warning' : 'error')) . "'>
    <h3>File System Permissions</h3>
    <ul>
        <li>Create directory: " . ($can_create_dir ? "✅ Yes" : "❌ No") . "</li>
        <li>Write file: " . ($can_write_file ? "✅ Yes" : "❌ No") . "</li>
        <li>Delete files: " . ($can_delete ? "✅ Yes" : "❌ No") . "</li>
    </ul>
</div>";

// 5. Check session support
$session_works = false;
if (function_exists('session_start')) {
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
    $session_works = session_status() == PHP_SESSION_ACTIVE;
}

echo "<div class='section " . ($session_works ? 'success' : 'warning') . "'>
    <h3>Session Support</h3>
    <p>" . ($session_works ? "✅ Sessions working" : "⚠️ Sessions may not work") . "</p>
</div>";

// 6. Upload capabilities
$max_upload = ini_get('upload_max_filesize');
$max_post = ini_get('post_max_size');

echo "<div class='section info'>
    <h3>Upload Settings</h3>
    <ul>
        <li>upload_max_filesize: $max_upload</li>
        <li>post_max_size: $max_post</li>
        <li>file_uploads: " . (ini_get('file_uploads') ? '✅ Enabled' : '❌ Disabled') . "</li>
    </ul>
</div>";

// 7. Safe mode and restrictions
echo "<div class='section info'>
    <h3>PHP Restrictions</h3>
    <ul>
        <li>safe_mode: " . (ini_get('safe_mode') ? '⚠️ ON' : '✅ OFF') . "</li>
        <li>disable_functions: " . (ini_get('disable_functions') ?: '✅ None') . "</li>
        <li>open_basedir: " . (ini_get('open_basedir') ?: '✅ None') . "</li>
    </ul>
</div>";

// 8. Summary and recommendations
$can_run_filemanager = empty($missing_critical) && $can_create_dir && $php_major >= 5.6;

echo "<div class='section " . ($can_run_filemanager ? 'success' : 'error') . "'>
    <h3>Overall Compatibility</h3>
    <p><strong>" . ($can_run_filemanager ? "✅ SERVER COMPATIBLE" : "❌ SERVER INCOMPATIBLE") . "</strong></p>
    
    <h4>Recommendations:</h4>
    <ul>";

if (!empty($missing_critical)) {
    echo "<li>❌ Enable these PHP functions: " . implode(', ', $missing_critical) . "</li>";
}
if (!$can_create_dir) {
    echo "<li>❌ Fix directory permissions (chmod 755)</li>";
}
if ($php_major < 5.6) {
    echo "<li>❌ Upgrade PHP to at least 5.6</li>";
}
if ($can_run_filemanager) {
    echo "<li>✅ Server is ready for file manager</li>";
}

echo "</ul></div>";

// ============================================
// GENERATE FILE MANAGER BASED ON DETECTION
// ============================================
if ($can_run_filemanager) {
    echo "<div class='section success'>
        <h3>📁 Generate File Manager</h3>
        <p>Based on server detection, generating optimized file manager...</p>
        
        <form method='POST' action=''>
            <input type='hidden' name='generate' value='1'>
            <p><strong>Configuration:</strong></p>
            
            <p>Username: <input type='text' name='username' value='admin' required></p>
            <p>Password: <input type='text' name='password' value='admin123' required></p>
            <p>Base Directory: <input type='text' name='base_dir' value='" . htmlspecialchars(__DIR__) . "' size='50'></p>
            
            <p><label><input type='checkbox' name='enable_upload' checked> Enable File Upload</label></p>
            <p><label><input type='checkbox' name='enable_delete' checked> Enable Delete</label></p>
            <p><label><input type='checkbox' name='enable_edit' " . (function_exists('file_get_contents') && function_exists('file_put_contents') ? 'checked' : '') . "> Enable File Editing</label></p>
            
            <input type='submit' value='Generate File Manager' class='btn'>
        </form>
    </div>";
}

// Handle generation request
if (isset($_POST['generate']) && $can_run_filemanager) {
    $username = $_POST['username'] ?? 'admin';
    $password = $_POST['password'] ?? 'admin123';
    $base_dir = $_POST['base_dir'] ?? __DIR__;
    $enable_upload = isset($_POST['enable_upload']);
    $enable_delete = isset($_POST['enable_delete']);
    $enable_edit = isset($_POST['enable_edit']);
    
    // Generate the file manager code
    $filemanager_code = generateFileManager($username, $password, $base_dir, $enable_upload, $enable_delete, $enable_edit);
    
    echo "<div class='section success'>
        <h3>✅ File Manager Generated</h3>
        <p>Save this code as <strong>filemanager.php</strong>:</p>
        <pre>" . htmlspecialchars($filemanager_code) . "</pre>
        
        <p><a href='?test=1' class='btn'>Test File Manager Now</a></p>
    </div>";
    
    // Also save to a temporary file for testing
    $test_file = __DIR__ . '/test_filemanager_' . time() . '.php';
    file_put_contents($test_file, $filemanager_code);
    
    if (isset($_GET['test'])) {
        header("Location: " . basename($test_file));
        exit;
    }
}

echo "</div></body></html>";

// ============================================
// FILE MANAGER GENERATOR FUNCTION
// ============================================
function generateFileManager($username, $password, $base_dir, $enable_upload, $enable_delete, $enable_edit) {
    $code = "<?php\n";
    $code .= "// ============================================\n";
    $code .= "// UNIVERSAL FILE MANAGER\n";
    $code .= "// Generated for your server configuration\n";
    $code .= "// ============================================\n\n";
    
    // Basic error handling
    $code .= "// Basic error display\n";
    $code .= "if (!defined('DISPLAY_ERRORS')) {\n";
    $code .= "    @ini_set('display_errors', 1);\n";
    $code .= "    @ini_set('display_startup_errors', 1);\n";
    $code .= "    @error_reporting(E_ALL);\n";
    $code .= "}\n\n";
    
    // Configuration
    $code .= "// Configuration\n";
    $code .= "\$CONFIG = [\n";
    $code .= "    'username' => '$username',\n";
    $code .= "    'password' => '$password',\n";
    $code .= "    'base_dir' => '$base_dir',\n";
    $code .= "    'enable_upload' => " . ($enable_upload ? 'true' : 'false') . ",\n";
    $code .= "    'enable_delete' => " . ($enable_delete ? 'true' : 'false') . ",\n";
    $code .= "    'enable_edit' => " . ($enable_edit ? 'true' : 'false') . ",\n";
    $code .= "    'max_upload_mb' => " . (int)ini_get('upload_max_filesize') . ",\n";
    $code .= "];\n\n";
    
    // Session handling with fallback
    $code .= "// Session handling\n";
    $code .= "if (!isset(\$_SESSION)) {\n";
    $code .= "    if (function_exists('session_start') && session_status() == PHP_SESSION_NONE) {\n";
    $code .= "        @session_start();\n";
    $code .= "    } else {\n";
    $code .= "        // Fallback: use simple authentication\n";
    $code .= "        \$GLOBALS['simple_auth'] = true;\n";
    $code .= "    }\n";
    $code .= "}\n\n";
    
    // Simple HTML escaping function
    $code .= "// Simple HTML escape function\n";
    $code .= "function h(\$s) {\n";
    $code .= "    if (function_exists('htmlspecialchars')) {\n";
    $code .= "        return htmlspecialchars(\$s ?? '', ENT_QUOTES, 'UTF-8');\n";
    $code .= "    }\n";
    $code .= "    return str_replace(['&', '\"', \"'\", '<', '>'], ['&amp;', '&quot;', '&#039;', '&lt;', '&gt;'], \$s ?? '');\n";
    $code .= "}\n\n";
    
    // Safe path function
    $code .= "// Safe path resolution\n";
    $code .= "function safePath(\$base, \$path) {\n";
    $code .= "    // Clean path\n";
    $code .= "    \$path = str_replace(['..', \"\\0\", \"\\\\\"], '', \$path ?? '');\n";
    $code .= "    \$path = trim(\$path, '/');\n";
    $code .= "    \n";
    $code .= "    // Build full path\n";
    $code .= "    \$full = rtrim(\$base, '/') . '/' . \$path;\n";
    $code .= "    \n";
    $code .= "    // Very basic security check\n";
    $code .= "    if (strpos(\$full, \$base) !== 0) {\n";
    $code .= "        return \$base;\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    return \$full;\n";
    $code .= "}\n\n";
    
    // Authentication system
    $code .= "// Authentication check\n";
    $code .= "function checkAuth() {\n";
    $code .= "    global \$CONFIG;\n";
    $code .= "    \n";
    $code .= "    // Logout\n";
    $code .= "    if (isset(\$_GET['logout'])) {\n";
    $code .= "        \$_SESSION = [];\n";
    $code .= "        if (function_exists('session_destroy')) @session_destroy();\n";
    $code .= "        header('Location: ?');\n";
    $code .= "        exit;\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // Check if already logged in\n";
    $code .= "    if (isset(\$_SESSION['fm_logged_in']) && \$_SESSION['fm_logged_in'] === true) {\n";
    $code .= "        return true;\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // Check login attempt\n";
    $code .= "    if (\$_SERVER['REQUEST_METHOD'] == 'POST' && isset(\$_POST['login'])) {\n";
    $code .= "        \$user = \$_POST['username'] ?? '';\n";
    $code .= "        \$pass = \$_POST['password'] ?? '';\n";
    $code .= "        \n";
    $code .= "        if (\$user == \$CONFIG['username'] && \$pass == \$CONFIG['password']) {\n";
    $code .= "            \$_SESSION['fm_logged_in'] = true;\n";
    $code .= "            \$_SESSION['fm_user'] = \$user;\n";
    $code .= "            header('Location: ?');\n";
    $code .= "            exit;\n";
    $code .= "        } else {\n";
    $code .= "            \$error = 'Login failed!';\n";
    $code .= "        }\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // Show login form\n";
    $code .= "    showLogin(\$error ?? '');\n";
    $code .= "    exit;\n";
    $code .= "}\n\n";
    
    // Login form
    $code .= "function showLogin(\$error = '') {\n";
    $code .= "    global \$CONFIG;\n";
    $code .= "    echo '<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Login</title>\n";
    $code .= "    <style>body{font-family:Arial;background:#f0f0f0;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}\n";
    $code .= "    .login{background:white;padding:30px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);width:300px;}\n";
    $code .= "    input{margin:10px 0;padding:10px;width:100%;box-sizing:border-box;}\n";
    $code .= "    button{padding:10px;background:#4CAF50;color:white;border:none;width:100%;cursor:pointer;}\n";
    $code .= "    .error{color:red;margin-bottom:10px;}</style></head>\n";
    $code .= "    <body><div class=\"login\"><h2>Login</h2>';\n";
    $code .= "    if (\$error) echo '<div class=\"error\">' . h(\$error) . '</div>';\n";
    $code .= "    echo '<form method=\"POST\">\n";
    $code .= "        <input type=\"text\" name=\"username\" placeholder=\"Username\" value=\"' . h(\$CONFIG['username']) . '\" required>\n";
    $code .= "        <input type=\"password\" name=\"password\" placeholder=\"Password\" value=\"' . h(\$CONFIG['password']) . '\" required>\n";
    $code .= "        <button type=\"submit\" name=\"login\">Login</button>\n";
    $code .= "    </form></div></body></html>';\n";
    $code .= "}\n\n";
    
    // Main file manager logic
    $code .= "// Main file manager\n";
    $code .= "function showFileManager() {\n";
    $code .= "    global \$CONFIG;\n";
    $code .= "    \n";
    $code .= "    // Get current directory\n";
    $code .= "    \$current_dir = isset(\$_GET['dir']) ? \$_GET['dir'] : '';\n";
    $code .= "    \$full_path = safePath(\$CONFIG['base_dir'], \$current_dir);\n";
    $code .= "    \n";
    $code .= "    // Ensure it's a directory\n";
    $code .= "    if (!is_dir(\$full_path)) {\n";
    $code .= "        \$full_path = \$CONFIG['base_dir'];\n";
    $code .= "        \$current_dir = '';\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // Handle actions\n";
    $code .= "    \$message = '';\n";
    $code .= "    \$error = '';\n";
    $code .= "    \n";
    $code .= "    // Upload file\n";
    $code .= "    if (\$CONFIG['enable_upload'] && isset(\$_FILES['file']) && \$_FILES['file']['error'] == 0) {\n";
    $code .= "        \$target = \$full_path . '/' . basename(\$_FILES['file']['name']);\n";
    $code .= "        if (move_uploaded_file(\$_FILES['file']['tmp_name'], \$target)) {\n";
    $code .= "            \$message = 'File uploaded!';\n";
    $code .= "        } else {\n";
    $code .= "            \$error = 'Upload failed!';\n";
    $code .= "        }\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // Delete file/folder\n";
    $code .= "    if (\$CONFIG['enable_delete'] && isset(\$_GET['delete'])) {\n";
    $code .= "        \$to_delete = safePath(\$CONFIG['base_dir'], \$_GET['delete']);\n";
    $code .= "        if (file_exists(\$to_delete)) {\n";
    $code .= "            if (is_dir(\$to_delete)) {\n";
    $code .= "                @rmdir(\$to_delete);\n";
    $code .= "                \$message = 'Folder deleted!';\n";
    $code .= "            } else {\n";
    $code .= "                @unlink(\$to_delete);\n";
    $code .= "                \$message = 'File deleted!';\n";
    $code .= "            }\n";
    $code .= "        }\n";
    $code .= "        header('Location: ?dir=' . urlencode(\$current_dir));\n";
    $code .= "        exit;\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // Download file\n";
    $code .= "    if (isset(\$_GET['download'])) {\n";
    $code .= "        \$to_download = safePath(\$CONFIG['base_dir'], \$_GET['download']);\n";
    $code .= "        if (file_exists(\$to_download) && is_file(\$to_download)) {\n";
    $code .= "            header('Content-Type: application/octet-stream');\n";
    $code .= "            header('Content-Disposition: attachment; filename=\"' . basename(\$to_download) . '\"');\n";
    $code .= "            readfile(\$to_download);\n";
    $code .= "            exit;\n";
    $code .= "        }\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // Show the file manager\n";
    $code .= "    echo '<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>File Manager</title>\n";
    $code .= "    <style>\n";
    $code .= "        body{font-family:Arial;margin:20px;background:#f5f5f5;}\n";
    $code .= "        .container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}\n";
    $code .= "        .header{background:#333;color:white;padding:15px;border-radius:5px;}\n";
    $code .= "        .message{padding:10px;margin:10px 0;border-radius:5px;}\n";
    $code .= "        .success{background:#d4edda;color:#155724;}\n";
    $code .= "        .error{background:#f8d7da;color:#721c24;}\n";
    $code .= "        table{width:100%;border-collapse:collapse;margin-top:20px;}\n";
    $code .= "        th,td{padding:10px;border:1px solid #ddd;text-align:left;}\n";
    $code .= "        th{background:#f2f2f2;}\n";
    $code .= "        .actions a{margin:0 5px;text-decoration:none;}\n";
    $code .= "        .btn{padding:5px 10px;background:#4CAF50;color:white;border:none;border-radius:3px;cursor:pointer;}\n";
    $code .= "        .btn-delete{background:#f44336;}\n";
    $code .= "    </style></head>\n";
    $code .= "    <body><div class=\"container\">\n";
    $code .= "    <div class=\"header\">\n";
    $code .= "        <h1>📁 File Manager</h1>\n";
    $code .= "        <p>User: ' . h(\$_SESSION['fm_user'] ?? 'Admin') . ' | \n";
    $code .= "        <a href=\"?logout=1\" style=\"color:white;\">Logout</a></p>\n";
    $code .= "    </div>\n";
    $code .= "    \n";
    $code .= "    <div class=\"breadcrumb\">\n";
    $code .= "        <a href=\"?\">Root</a>\n";
    $code .= "        ';\n";
    $code .= "    \n";
    $code .= "    // Breadcrumb\n";
    $code .= "    if (\$current_dir) {\n";
    $code .= "        \$parts = explode('/', \$current_dir);\n";
    $code .= "        \$path = '';\n";
    $code .= "        foreach (\$parts as \$part) {\n";
    $code .= "            if (\$part) {\n";
    $code .= "                \$path .= '/' . \$part;\n";
    $code .= "                echo ' / <a href=\"?dir=' . urlencode(ltrim(\$path, '/')) . '\">' . h(\$part) . '</a>';\n";
    $code .= "            }\n";
    $code .= "        }\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    echo '</div>';\n";
    $code .= "    \n";
    $code .= "    // Messages\n";
    $code .= "    if (\$message) echo '<div class=\"message success\">' . h(\$message) . '</div>';\n";
    $code .= "    if (\$error) echo '<div class=\"message error\">' . h(\$error) . '</div>';\n";
    $code .= "    \n";
    $code .= "    // Upload form\n";
    $code .= "    if (\$CONFIG['enable_upload']) {\n";
    $code .= "        echo '<form method=\"POST\" enctype=\"multipart/form-data\" style=\"margin:20px 0;\">\n";
    $code .= "            <input type=\"file\" name=\"file\" required>\n";
    $code .= "            <button type=\"submit\" class=\"btn\">Upload</button>\n";
    $code .= "        </form>';\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // File list\n";
    $code .= "    echo '<table><tr><th>Name</th><th>Size</th><th>Modified</th><th>Actions</th></tr>';\n";
    $code .= "    \n";
    $code .= "    // Parent directory\n";
    $code .= "    if (\$current_dir) {\n";
    $code .= "        \$parent = dirname(\$current_dir);\n";
    $code .= "        if (\$parent == '.') \$parent = '';\n";
    $code .= "        echo '<tr><td colspan=\"4\"><a href=\"?dir=' . urlencode(\$parent) . '\">📁 .. (Parent)</a></td></tr>';\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    // List files\n";
    $code .= "    \$items = @scandir(\$full_path);\n";
    $code .= "    if (\$items) {\n";
    $code .= "        foreach (\$items as \$item) {\n";
    $code .= "            if (\$item == '.' || \$item == '..') continue;\n";
    $code .= "            \n";
    $code .= "            \$item_path = \$full_path . '/' . \$item;\n";
    $code .= "            \$is_dir = is_dir(\$item_path);\n";
    $code .= "            \$size = \$is_dir ? '-' : (function_exists('filesize') ? round(filesize(\$item_path)/1024,2).' KB' : '-');\n";
    $code .= "            \$modified = function_exists('filemtime') ? date('Y-m-d H:i', filemtime(\$item_path)) : '-';\n";
    $code .= "            \$icon = \$is_dir ? '📁' : '📄';\n";
    $code .= "            \$item_url = \$current_dir ? \$current_dir . '/' . \$item : \$item;\n";
    $code .= "            \n";
    $code .= "            echo '<tr>';\n";
    $code .= "            echo '<td>' . \$icon . ' ';\n";
    $code .= "            if (\$is_dir) {\n";
    $code .= "                echo '<a href=\"?dir=' . urlencode(\$item_url) . '\">' . h(\$item) . '</a>';\n";
    $code .= "            } else {\n";
    $code .= "                echo h(\$item);\n";
    $code .= "            }\n";
    $code .= "            echo '</td>';\n";
    $code .= "            echo '<td>' . \$size . '</td>';\n";
    $code .= "            echo '<td>' . \$modified . '</td>';\n";
    $code .= "            echo '<td class=\"actions\">';\n";
    $code .= "            \n";
    $code .= "            if (!\$is_dir) {\n";
    $code .= "                echo '<a href=\"?dir=' . urlencode(\$current_dir) . '&download=' . urlencode(\$item_url) . '\" class=\"btn\">Download</a>';\n";
    $code .= "            }\n";
    $code .= "            \n";
    $code .= "            if (\$CONFIG['enable_delete']) {\n";
    $code .= "                echo '<a href=\"?dir=' . urlencode(\$current_dir) . '&delete=' . urlencode(\$item_url) . '\" class=\"btn btn-delete\" onclick=\"return confirm(\\\"Delete ' . h(\$item) . '?\\\")\">Delete</a>';\n";
    $code .= "            }\n";
    $code .= "            \n";
    $code .= "            echo '</td></tr>';\n";
    $code .= "        }\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    if (count(\$items) <= 2) {\n";
    $code .= "        echo '<tr><td colspan=\"4\" style=\"text-align:center;\">Empty folder</td></tr>';\n";
    $code .= "    }\n";
    $code .= "    \n";
    $code .= "    echo '</table>';\n";
    $code .= "    echo '</div></body></html>';\n";
    $code .= "}\n\n";
    
    // Main execution
    $code .= "// Main execution\n";
    $code .= "try {\n";
    $code .= "    checkAuth();\n";
    $code .= "    showFileManager();\n";
    $code .= "} catch (Exception \$e) {\n";
    $code .= "    echo '<h1>Error</h1><p>' . h(\$e->getMessage()) . '</p>';\n";
    $code .= "}\n";
    
    $code .= "?>";
    
    return $code;
}
