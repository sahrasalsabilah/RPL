<?php
// dashboard_hrd.php
require_once 'connection.php';
session_start();

// Validasi Hak Akses: Hanya role HRD yang boleh masuk halaman ini
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'hrd') {
    header("Location: dashboard.php"); // Jika pelamar nyasar kesini, kembalikan ke dashboard utama
    exit;
}

// PROSES HRD MENGUBAH STATUS LAMARAN (VALIDASI)
if (isset($_POST['aksi_validasi'])) {
    $id_lamaran = $_POST['id_lamaran'];
    $status_baru = $_POST['status_baru'];
    $catatan = filter_input(INPUT_POST, 'catatan', FILTER_SANITIZE_SPECIAL_CHARS);
    $status_lama = $_POST['status_lama'];
    $hrd_id = $_SESSION['id_user'];

    try {
        $conn->beginTransaction();

        // 1. Update status di tabel lamaran
        $stmtUpdate = $conn->prepare("UPDATE lamaran SET status_lamaran = :status WHERE id_lamaran = :id_lamaran");
        $stmtUpdate->execute([':status' => $status_baru, ':id_lamaran' => $id_lamaran]);

        // 2. Catat riwayatnya ke tabel log_validasi
        $stmtLog = $conn->prepare("INSERT INTO log_validasi (id_lamaran, status_lama, status_baru, catatan, diubah_oleh) VALUES (:id, :lama, :baru, :catatan, :hrd)");
        $stmtLog->execute([
            ':id' => $id_lamaran,
            ':lama' => $status_lama,
            ':baru' => $status_baru,
            ':catatan' => $catatan,
            ':hrd' => $hrd_id
        ]);

        $conn->commit();
        echo "<script>alert('Status lamaran berhasil diperbarui!'); window.location='dashboard_hrd.php';</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Gagal memproses validasi.');</script>";
    }
}

// AMBIL DATA LAMARAN YANG MASUK BESERTA PROFIL PENCARI KERJA
try {
    $sql = "SELECT l.id_lamaran, l.status_lamaran, l.tgl_melamar,
                   u.nama AS nama_pelamar, u.email AS email_pelamar,
                   p.no_telp, p.pendidikan_terakhir, p.keahlian, p.cv_file,
                   j.posisi, j.nama_perusahaan
            FROM lamaran l
            JOIN users u ON l.id_user = u.id_user
            JOIN lowongan j ON l.id_loker = j.id_loker
            LEFT JOIN profil_pelamar p ON u.id_user = p.id_user
            ORDER BY l.id_lamaran DESC";
    $stmt = $conn->query($sql);
    $daftar_lamaran = $stmt->fetchAll();
} catch (PDOException $e) {
    $daftar_lamaran = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<title>Panel HRD — Kelola Lamaran Masuk</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet"/>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --primary: #185FA5; --primary-light: #E6F1FB;
    --success: #3B6D11; --success-light: #EAF3DE;
    --danger: #A32D2D; --danger-light: #FCEBEB;
    --warning: #BA7517; --warning-light: #FAEEDA;
    --text: #1a1a1a; --text-sec: #5c5c5c; --bg: #f5f7fa; --surface: #ffffff;
  }
  body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); padding: 30px; }
  .container { max-width: 1200px; margin: 0 auto; }
  .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .title { font-family: 'DM Serif Display', serif; font-size: 28px; color: var(--primary); }
  .btn-back { padding: 8px 16px; background: #fff; border: 1px solid #ccc; border-radius: 8px; text-decoration: none; color: #333; font-size: 14px; font-weight: 500; }
  
  .table-card { background: var(--surface); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); overflow: hidden; padding: 24px; }
  table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  th { background: #f8fafc; padding: 14px; font-weight: 600; color: var(--text-sec); border-bottom: 2px solid #e2e8f0; }
  td { padding: 14px; border-bottom: 1px solid #edf2f7; vertical-align: top; }
  
  .badge { padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; display: inline-block; }
  .badge.Pending { background: var(--warning-light); color: var(--warning); }
  .badge.Diterima { background: var(--success-light); color: var(--success); }
  .badge.Ditolak { background: var(--danger-light); color: var(--danger); }
  
  .action-form { display: flex; flex-direction: column; gap: 6px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; width: 220px; }
  .select-box { padding: 6px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit; font-size: 13px; }
  .input-note { padding: 6px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit; font-size: 12px; resize: vertical; }
  .btn-save { background: var(--primary); color: #fff; border: none; padding: 6px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 12px; }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1 class="title">Panel HRD — Validasi Berkas Lamaran</h1>
        <a href="dashboard.php" class="btn-back">← Kembali ke Beranda</a>
    </div>

    <div class="table-card">
        <h2 style="font-size: 18px; margin-bottom: 16px;">Daftar Lamaran Masuk Kerja</h2>
        <table>
            <thead>
                <tr>
                    <th>Pelamar & Kontak</th>
                    <th>Kualifikasi & CV</th>
                    <th>Lowongan Dituju</th>
                    <th>Status Saat Ini</th>
                    <th>Tindakan HRD (Validasi)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($daftar_lamaran)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-sec);">Belum ada pelamar kerja yang mengirimkan berkas lamaran.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($daftar_lamaran as $row): ?>
                        <tr>
                            <td>
                                <b><?= htmlspecialchars($row['nama_pelamar']) ?></b><br>
                                <span style="font-size:12px; color:var(--text-sec);"><?= htmlspecialchars($row['email_pelamar']) ?></span><br>
                                <span style="font-size:12px; color:var(--text-sec);">📞 <?= htmlspecialchars($row['no_telp'] ?? '-') ?></span>
                            </td>
                            <td>
                                <b>🎓 <?= htmlspecialchars($row['pendidikan_terakhir'] ?? 'Belum isi') ?></b><br>
                                <span style="font-size:12px; color:var(--text-sec);">🛠️ Keahlian: <?= htmlspecialchars($row['keahlian'] ?? '-') ?></span><br>
                                <?php if(!empty($row['cv_file'])): ?>
                                    <a href="#" style="font-size:12px; color: var(--primary); font-weight: 600; text-decoration: none;" onclick="alert('Membuka file: <?= $row['cv_file'] ?>')">📄 Lihat Dokumen CV</a>
                                <?php else: ?>
                                    <span style="font-size:12px; color:var(--danger);">CV belum diunggah</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($row['posisi']) ?></span><br>
                                <span style="font-size:12px; color:var(--text-sec);"><?= htmlspecialchars($row['nama_perusahaan']) ?></span><br>
                                <span style="font-size:11px; color:#aaa;">📅 <?= date('d M Y', strtotime($row['tgl_melamar'])) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $row['status_lamaran'] ?>"><?= $row['status_lamaran'] ?></span>
                            </td>
                            <td>
                                <form action="" method="POST" class="action-form">
                                    <input type="hidden" name="id_lamaran" value="<?= $row['id_lamaran'] ?>">
                                    <input type="hidden" name="status_lama" value="<?= $row['status_lamaran'] ?>">
                                    
                                    <select name="status_baru" class="select-box" required>
                                        <option value="Pending" <?= $row['status_lamaran'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Diterima" <?= $row['status_lamaran'] === 'Diterima' ? 'selected' : '' ?>>Terima Pelamar</option>
                                        <option value="Ditolak" <?= $row['status_lamaran'] === 'Ditolak' ? 'selected' : '' ?>>Tolak Berkas</option>
                                    </select>
                                    
                                    <input type="text" name="catatan" class="input-note" placeholder="Catatan/Alasan..." required>
                                    
                                    <button type="submit" name="aksi_validasi" class="btn-save">Simpan Perubahan</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>