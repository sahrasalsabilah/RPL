<?php
// login.php
require_once 'connection.php';
session_start();

// Jika sudah login, langsung dialihkan ke dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';

if (isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if ($email && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Validasi password (mendukung plain text atau password_verify jika sudah menggunakan hash)
        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role']; // 'pelamar' atau 'hrd'
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error_msg = "Email atau password yang Anda masukkan salah.";
        }
    } else {
        $error_msg = "Silakan masukkan format email dan password dengan benar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Masuk — InfoLoker</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet"/>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --primary: #185FA5; --primary-dark: #0C447C; --primary-light: #E6F1FB;
    --danger: #A32D2D; --danger-light: #FCEBEB;
    --text: #1a1a1a; --text-sec: #5c5c5c; --bg: #f5f7fa; --surface: #ffffff;
    --border-md: rgba(0,0,0,0.13); --radius-md: 12px; --radius-sm: 8px;
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.08); --font: 'Plus Jakarta Sans', sans-serif;
    --font-display: 'DM Serif Display', serif;
  }
  body { font-family: var(--font); background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
  .login-card { background: var(--surface); width: 100%; max-width: 400px; padding: 40px 32px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); border: 1px solid rgba(0,0,0,0.05); }
  .logo { font-family: var(--font-display); font-size: 28px; color: var(--primary); text-decoration: none; display: block; text-align: center; margin-bottom: 8px; }
  .logo span { color: #378ADD; }
  .subtitle { text-align: center; color: var(--text-sec); font-size: 14px; margin-bottom: 24px; }
  .alert { padding: 12px; background: var(--danger-light); color: var(--danger); border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; margin-bottom: 20px; border: 1px solid rgba(163,45,45,0.15); }
  .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
  .form-label { font-size: 12px; font-weight: 600; color: var(--text-sec); }
  .form-control { padding: 11px 14px; border: 1px solid var(--border-md); border-radius: var(--radius-sm); font-family: inherit; font-size: 14px; outline: none; transition: 0.2s; }
  .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
  .btn-login { width: 100%; padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); font-weight: 700; font-size: 14px; cursor: pointer; transition: 0.2s; margin-top: 8px; }
  .btn-login:hover { background: var(--primary-dark); }
  .footer-text { text-align: center; font-size: 13px; color: var(--text-sec); margin-top: 24px; }
  .footer-text a { color: var(--primary); font-weight: 600; text-decoration: none; }
  .footer-text a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="login-card">
  <a href="#" class="logo">Info<span>Loker</span></a>
  <div class="subtitle">Masuk untuk menjelajahi lowongan pekerjaan</div>

  <?php if (!empty($error_msg)): ?>
    <div class="alert"><?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="form-group">
      <label class="form-label">Alamat Email</label>
      <input type="email" name="email" class="form-control" required placeholder="nama@email.com" autocomplete="email">
    </div>

    <div class="form-group">
      <label class="form-label">Kata Sandi (Password)</label>
      <input type="password" name="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
    </div>

    <button type="submit" name="login" class="btn-login">Masuk ke Akun</button>
  </form>

  <div class="footer-text">
    Belum memiliki akun? <a href="registrasi.php">Daftar Akun Baru</a>
  </div>
</div>

</body>
</html>