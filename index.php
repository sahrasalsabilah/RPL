<?php
// index.php
require_once 'connection.php';
session_start();

// Ambil kata kunci pencarian & filter tipe
$search = isset($_GET['q']) ? '%'.$_GET['q'].'%' : '%';
$type = $_GET['type'] ?? 'All';

if ($type === 'All') {
    $q = $conn->prepare("SELECT * FROM lowongan WHERE status='aktif' AND (posisi LIKE :s OR nama_perusahaan LIKE :s OR lokasi LIKE :s) ORDER BY id_loker DESC");
    $q->execute([':s' => $search]);
} else {
    $q = $conn->prepare("SELECT * FROM lowongan WHERE status='aktif' AND tipe = :t AND (posisi LIKE :s OR nama_perusahaan LIKE :s OR lokasi LIKE :s) ORDER BY id_loker DESC");
    $q->execute([':t' => $type, ':s' => $search]);
}
$lowongan = $q->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>InfoLoker — Cari Lowongan Kerja</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet"/>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --primary: #185FA5; --primary-dark: #0C447C; --primary-light: #E6F1FB; --primary-mid: #378ADD;
    --text: #1a1a1a; --text-sec: #5c5c5c; --bg: #f5f7fa; --surface: #ffffff; --border: rgba(0,0,0,0.08);
    --radius-md: 12px; --radius-lg: 16px; --radius-xl: 24px; --font: 'Plus Jakarta Sans', sans-serif; --font-display: 'DM Serif Display', serif;
  }
  body { font-family: var(--font); background: var(--bg); color: var(--text); padding-top: 64px; }
  .topnav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: 64px; background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 24px; }
  .logo { font-family: var(--font-display); font-size: 22px; color: var(--primary); text-decoration:none; font-weight:700; }
  .logo span { color: var(--primary-mid); }
  .nav-search { flex: 1; max-width: 400px; margin-left: 24px; position: relative; }
  .nav-search input { width: 100%; background: var(--bg); border: 1px solid rgba(0,0,0,0.13); border-radius: 24px; padding: 8px 16px 8px 40px; font-size: 13px; outline: none; }
  .nav-auth { margin-left: auto; display: flex; gap: 12px; }
  .btn-link { text-decoration: none; padding: 8px 16px; font-weight: 600; font-size: 13px; border-radius: 8px; }
  .btn-link.login { color: var(--primary); }
  .btn-link.register { background: var(--primary); color: white; }
  .content { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
  .hero { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); border-radius: var(--radius-xl); padding: 40px; color: #fff; margin-bottom: 30px; }
  .hero h1 { font-family: var(--font-display); font-size: 32px; margin-bottom: 8px; }
  .chips { display: flex; gap: 8px; margin-bottom: 24px; }
  .chip { padding: 8px 16px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.13); background: #fff; color: var(--text-sec); font-size: 13px; text-decoration: none; font-weight: 500; }
  .chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }
  .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
  .job-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
  .job-title { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
  .company-name { font-size: 12px; color: var(--text-sec); margin-bottom: 12px; }
  .req-docs { font-size: 11px; background: #FFF3CD; color: #856404; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 12px; font-weight: 600; }
  .job-salary { font-weight: 700; color: var(--primary); margin-bottom: 16px; }
  .btn-apply { width: 100%; padding: 10px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; text-align: center; text-decoration: none; display: block; }
</style>
</head>
<body>

<nav class="topnav">
  <a href="index.php" class="logo">Info<span>Loker</span></a>
  <div class="nav-search">
    <form method="GET" action="index.php">
      <input type="text" name="q" placeholder="Cari posisi atau lokasi..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"/>
    </form>
  </div>
  <div class="nav-auth">
    <?php if(isset($_SESSION['id_user'])): ?>
      <a href="dashboard.php" class="btn-link register">Masuk ke Dashboard ➔</a>
    <?php else: ?>
      <a href="login.php" class="btn-link login">Masuk</a>
      <a href="registrasi.php" class="btn-link register">Daftar Akun</a>
    <?php endif; ?>
  </div>
</nav>

<div class="content">
  <div class="hero">
    <h1>Jelajahi Peluang Kerja Terbaik</h1>
    <p>Temukan lowongan kerja yang sesuai dengan kualifikasi dan kompetensi berkas Anda.</p>
  </div>

  <div class="chips">
    <a href="index.php?type=All" class="chip <?= $type==='All'?'active':'' ?>">Semua Kategori</a>
    <a href="index.php?type=Full-time" class="chip <?= $type==='Full-time'?'active':'' ?>">Full-time</a>
    <a href="index.php?type=Part-time" class="chip <?= $type==='Part-time'?'active':'' ?>">Part-time</a>
    <a href="index.php?type=Remote" class="chip <?= $type==='Remote'?'active':'' ?>">Remote</a>
  </div>

  <div class="jobs-grid">
    <?php foreach($lowongan as $job): ?>
      <div class="job-card">
        <div class="job-title"><?= htmlspecialchars($job['posisi']) ?></div>
        <div class="company-name">🏢 <?= htmlspecialchars($job['nama_perusahaan']) ?> — <small><?= htmlspecialchars($job['lokasi']) ?></small></div>
        
        <!-- REVISI 2: Menampilkan Dokumen/Surat Yang Diperlukan -->
        <div class="req-docs">📄 Wajib Kirim: <?= htmlspecialchars($job['berkas_wajib']) ?></div>
        
        <div class="job-salary"><?= htmlspecialchars($job['gaji']) ?></div>
        <a href="dashboard.php?action=apply&id=<?= $job['id_loker'] ?>" class="btn-apply">Lamar Lowongan</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>