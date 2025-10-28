<?php
// Wrapper for the static login HTML that injects session-based flash messages
session_start();
$flash = null;
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$htmlPath = __DIR__ . '/auth-cover-login.html';
if (!file_exists($htmlPath)) {
    echo "Login page not found.";
    exit;
}

$content = file_get_contents($htmlPath);

// Prepare injection script to show flash message in client
$script = "<script>\n(function(){\n  try {\n    var flash = " . json_encode($flash) . ";\n    if (!flash) return;\n    var container = document.createElement('div');\n    container.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);background:#fff4e5;color:#663c00;padding:10px 16px;border-radius:6px;z-index:1000;font-weight:700;box-shadow:0 6px 18px rgba(0,0,0,0.08)';\n    container.textContent = flash.text || '';\n    document.body.appendChild(container);\n    if (flash.console) console.info(flash.console);\n    setTimeout(function(){ container.remove(); }, 6000);\n  } catch (e) { /* ignore */ }\n})();\n</script>";

// Insert just before closing </body> if present
$pos = stripos($content, '</body>');
if ($pos !== false) {
    $out = substr($content, 0, $pos) . $script . substr($content, $pos);
    echo $out;
} else {
    // fallback: append
    echo $content . $script;
}

?>
