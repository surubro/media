<?php
/* ================= CONFIG & AUTH ================= */
session_start();

$baseDir = realpath(__DIR__);
$allowedVideo = ['mp4','webm','ogg'];
$blockedFiles = ['index.php','.htaccess','.env'];
$basePassword = "admin"; // Your base password

/**
 * Validates the dynamic password
 */
function validateDynamicPassword($input, $base) {
    $currentMinute = date('i'); 
    $expected = $base . $currentMinute;
    return $input === $expected;
}

// Handle Logout
if (isset($_GET['logout'])) {
    setcookie("lib_access", "", time() - 3600, "/");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Login
if (isset($_POST['login_pass'])) {
    if (validateDynamicPassword($_POST['login_pass'], $basePassword)) {
        $expiry = time() + (24 * 3600); // 24 Hours
        setcookie("lib_access", $expiry, $expiry, "/");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Invalid Password"; // Hint removed as requested
    }
}

// Check Authorization
$isAuthorized = isset($_COOKIE['lib_access']) && time() < $_COOKIE['lib_access'];

if (!$isAuthorized): ?>
<!DOCTYPE html>
<html>
<head>
    <title>Locked Library</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { background: #0e0e0e; color: white; font-family: system-ui; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #1c1c1c; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; width: 280px; }
        input { width: 100%; padding: 12px; margin: 15px 0; border-radius: 8px; border: 1px solid #333; background: #000; color: white; box-sizing: border-box; outline: none; }
        input:focus { border-color: #4da3ff; }
        button { width: 100%; padding: 12px; border-radius: 8px; border: none; background: #4da3ff; color: white; font-weight: bold; cursor: pointer; transition: 0.2s; }
        button:active { transform: scale(0.98); opacity: 0.8; }
        .server-time { font-size: 11px; color: #444; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Private Access</h2>
        <?php if(isset($error)): ?> 
            <p style="color:#ff4d4d; font-size: 13px; font-weight: bold;"><?= $error ?></p> 
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="login_pass" placeholder="Password" autofocus required>
            <button type="submit">Unlock</button>
        </form>
        <!-- Keeping this so you can see the current minutes -->
        <div class="server-time">System Time: <?= date('H:i') ?></div>
    </div>
</body>
</html>
<?php 
exit; 
endif; 

/* ================= HELPERS ================= */

function safePath($path, $base) {
    $real = realpath($path);
    return $real && strpos($real, $base) === 0;
}

function sizeMB($bytes) {
    return round($bytes / 1024 / 1024, 1) . ' MB';
}

/* ================= STREAM ================= */

if (isset($_GET['play'])) {
    $file = $_GET['play'];
    $path = realpath($baseDir . '/' . $file);

    if (!safePath($path, $baseDir)) {
        http_response_code(403);
        exit('Access denied');
    }

    $size = filesize($path);
    $start = 0;
    $end = $size - 1;

    header("Content-Type: video/mp4");
    header("Accept-Ranges: bytes");

    if (isset($_SERVER['HTTP_RANGE'])) {
        preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $m);
        $start = intval($m[1]);
        if (!empty($m[2])) $end = intval($m[2]);
        header("HTTP/1.1 206 Partial Content");
        header("Content-Range: bytes $start-$end/$size");
    }

    header("Content-Length: " . ($end - $start + 1));

    $fp = fopen($path, 'rb');
    fseek($fp, $start);
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
    exit;
}

/* ================= LIST ================= */

$dir = $_GET['dir'] ?? '';
$current = realpath($baseDir . '/' . $dir);

if (!safePath($current, $baseDir)) {
    $current = $baseDir;
    $dir = '';
}

$items = scandir($current);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Video Browser</title>
<style>
body{margin:0;background:#0e0e0e;color:#fff;font-family:system-ui}
.header-bar{display:flex; justify-content: space-between; align-items: center; padding: 14px; background: #161616; border-bottom: 1px solid #222;}
.grid{display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:12px; padding:12px}
.card{background:#1c1c1c; border-radius:14px; padding:12px; cursor:pointer; transition: 0.2s;}
.card:hover{background:#2a2a2a; transform: translateY(-2px);}
.name{font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.meta{font-size:12px;color:#aaa;margin-top:4px}
video{width:100%;max-height: 80vh; background:black}
.back{padding:12px;color:#4da3ff;cursor:pointer; display: inline-block;}
.logout{font-size: 12px; color: #666; text-decoration: none; padding: 5px 10px; border: 1px solid #333; border-radius: 6px;}
.logout:hover{color:#ff4d4d; border-color:#ff4d4d;}
</style>
</head>
<body>

<?php if (isset($_GET['watch'])): ?>
    <div class="header-bar">
        <span>🎬 <?= htmlspecialchars(basename($_GET['watch'])) ?></span>
        <a href="?logout=1" class="logout">Lock</a>
    </div>
    <video controls autoplay>
        <source src="?play=<?= urlencode($_GET['watch']) ?>">
    </video>
    <div class="back" onclick="history.back()">⬅ Back to List</div>

<?php else: ?>
    <div class="header-bar">
        <span>📺 surendra's Library</span>
        <a href="?logout=1" class="logout">Lock Library</a>
    </div>

    <?php if ($dir): ?>
        <div class="back" onclick="location.href='?dir=<?= urlencode(dirname($dir)) ?>'">⬅ Back</div>
    <?php endif; ?>

    <div class="grid">
    <?php foreach ($items as $item):
        if ($item === '.' || $item === '..') continue;
        if (in_array($item, $blockedFiles)) continue;

        $full = $current . '/' . $item;
        $rel = ltrim($dir . '/' . $item, '/');

        if (is_dir($full)): ?>
            <div class="card" onclick="location.href='?dir=<?= urlencode($rel) ?>'">
                📁 <div class="name"><?= htmlspecialchars($item) ?></div>
            </div>

        <?php else:
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedVideo)) continue; ?>
            <div class="card" onclick="location.href='?watch=<?= urlencode($rel) ?>'">
                ▶ <div class="name"><?= htmlspecialchars($item) ?></div>
                <div class="meta"><?= sizeMB(filesize($full)) ?></div>
            </div>
        <?php endif;
    endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
