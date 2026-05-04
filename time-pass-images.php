<?php
/* ================= AUTH LOGIC ================= */
session_start();

$basePassword = "admin"; // Change this to your desired base password

/**
 * Validates: Base Password + Last 2 digits of current minute
 */
function validateDynamicPassword($input, $base) {
    $currentMinute = date('i'); 
    $expected = $base . $currentMinute;
    return $input === $expected;
}

// Handle Logout
if (isset($_GET['logout'])) {
    setcookie("gal_access", "", time() - 3600, "/");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Login
if (isset($_POST['login_pass'])) {
    if (validateDynamicPassword($_POST['login_pass'], $basePassword)) {
        $expiry = time() + (24 * 3600); // Allocate 24 Hours
        setcookie("gal_access", $expiry, $expiry, "/");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Invalid Password";
    }
}

// Check Authorization
$isAuthorized = isset($_COOKIE['gal_access']) && time() < $_COOKIE['gal_access'];

if (!$isAuthorized): ?>
<!DOCTYPE html>
<html>
<head>
    <title>Locked Gallery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { background: #0e0e0e; color: white; font-family: system-ui; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #1c1c1c; padding: 30px; border-radius: 15px; text-align: center; width: 280px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        input { width: 100%; padding: 12px; margin: 15px 0; border-radius: 8px; border: 1px solid #333; background: #000; color: white; box-sizing: border-box; outline: none; }
        input:focus { border-color: #4da3ff; }
        button { width: 100%; padding: 12px; border-radius: 8px; border: none; background: #4da3ff; color: white; font-weight: bold; cursor: pointer; }
        .server-time { font-size: 11px; color: #444; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Gallery Access</h2>
        <?php if(isset($error)): ?> <p style="color:#ff4d4d; font-size: 13px; font-weight:bold;"><?= $error ?></p> <?php endif; ?>
        <form method="POST">
            <input type="password" name="login_pass" placeholder="Password" autofocus required>
            <button type="submit">Unlock</button>
        </form>
        <div class="server-time">System Time: <?= date('H:i') ?></div>
    </div>
</body>
</html>
<?php 
exit; 
endif; 

/* ================= GALLERY LOGIC ================= */
$images = glob("*.{jpg,jpeg,png,webp,gif}", GLOB_BRACE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Image Gallery</title>
<style>
body { margin: 0; background: #111; color: #fff; font-family: system-ui; }
.header-bar { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; background: #1a1a1a; }
.gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; padding: 10px; }
.gallery img { width: 100%; height: 120px; object-fit: cover; border-radius: 6px; cursor: pointer; transition: 0.2s; }
.gallery img:hover { opacity: 0.8; transform: scale(1.02); }
.viewer { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); justify-content: center; align-items: center; z-index: 100; }
.viewer img { max-width: 95%; max-height: 95%; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
.logout { font-size: 12px; color: #666; text-decoration: none; padding: 5px 10px; border: 1px solid #333; border-radius: 6px; }
.logout:hover { color: #ff4d4d; border-color: #ff4d4d; }
</style>
</head>
<body>

<div class="header-bar">
    <span>🖼️ Image Gallery</span>
    <a href="?logout=1" class="logout">Lock</a>
</div>

<div class="gallery">
<?php foreach ($images as $img): ?>
    <img src="<?= $img ?>" onclick="openImg('<?= $img ?>')" loading="lazy">
<?php endforeach; ?>
</div>

<div class="viewer" onclick="this.style.display='none'">
    <img id="big">
</div>

<script>
function openImg(src){
    document.getElementById("big").src = src;
    document.querySelector(".viewer").style.display = "flex";
}
</script>

</body>
</html>
