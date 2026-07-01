<?php
// register.php
require_once 'connection.php';
session_start();

if (isset($_SESSION['id_user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role']; 

    if ($nama && $email && $password && $role) {
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, :role)");
            $stmt->execute([
                ':nama' => $nama,
                ':email' => $email,
                ':password' => $password_hashed,
                ':role' => $role
            ]);
            $success = "Registrasi sukses! Silakan <a href='login.php' style='color:#185FA5; font-weight:700; text-decoration:none;'>Login di sini</a>.";
        } catch (PDOException $e) {
            $error = "Email sudah terdaftar atau terjadi masalah pada server.";
        }
    } else {
        $error = "Mohon melengkapi seluruh formulir pendaftaran.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru — InfoLoker</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #f5f7fa; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            padding: 20px;
        }
        .register-container {
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(24, 95, 165, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        .brand-logo {
            font-family: 'DM Serif Display', serif;
            font-size: 28px;
            color: #185FA5;
            margin-bottom: 6px;
            text-align: center;
        }
        .brand-logo span {
            color: #378ADD;
        }
        .subtitle {
            font-size: 13px;
            color: #5c5c5c;
            text-align: center;
            margin-bottom: 28px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }
        .form-control:focus {
            border-color: #185FA5;
            box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.12);
        }
        select.form-control {
            cursor: pointer;
            background-color: #fff;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #185FA5;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
            margin-top: 8px;
            transition: 0.2s;
        }
        .btn-submit:hover {
            background: #0c447c;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.4;
        }
        .alert-danger {
            background: #FCEBEB;
            color: #A32D2D;
            border: 1px solid rgba(163, 45, 45, 0.1);
        }
        .alert-success {
            background: #EAF3DE;
            color: #3B6D11;
            border: 1px solid rgba(59, 109, 17, 0.1);
        }
        .footer-text {
            text-align: center;
            font-size: 13px;
            margin-top: 24px;
            color: #5c5c5c;
        }
        .footer-text a {
            color: #185FA5;
            font-weight: 600;
            text-decoration: none;
        }
        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="brand-logo">Info<span>Loker</span></div>
    <div class="subtitle">Daftarkan akun baru Anda untuk mulai mengeksplorasi</div>

    <?php if($error): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
    <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap Anda" required />
        </div>
        
        <div class="form-group">
            <label class="form-label">Alamat Email</label>
            <input type="email" name="email" class="form-control" placeholder="contoh@email.com" required />
        </div>
        
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Buat kata sandi minimal 6 karakter" required />
        </div>
        
        <div class="form-group">
            <label class="form-label">Mendaftar Sebagai</label>
            <select name="role" class="form-control" required>
                <option value="pelamar">Pelamar Kerja (Mencari Pekerjaan)</option>
                <option value="hrd">HRD / Perusahaan (Membuka Lowongan)</option>
            </select>
        </div>
        
        <button type="submit" class="btn-submit">Daftar Akun</button>
    </form>

    <div class="footer-text">
        Sudah memiliki akun? <a href="login.php">Masuk di sini</a>
    </div>
</div>

</body>
</html>