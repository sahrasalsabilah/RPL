<?php
// dashboard.php
require_once 'connection.php';
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$user_nama = $_SESSION['nama'] ?? 'Pengguna';
$user_role = $_SESSION['role'] ?? 'pelamar';

// Inisialisasi Avatar
$words = explode(" ", $user_nama);
$user_initial = strtoupper(substr(($words[0] ?? 'P'), 0, 1) . substr(($words[1] ?? ''), 0, 1));

$toast_msg = '';
$toast_type = 'success';

// Ambil Data Profil Perusahaan untuk keperluan global (seperti template email interview)
$my_corp = ['nama_perusahaan' => $user_nama, 'email_perusahaan' => '', 'sektor_industri' => '', 'alamat_perusahaan' => '', 'deskripsi_perusahaan' => '', 'status_verifikasi' => 'Belum Verifikasi'];
if ($user_role === 'hrd') {
    $get_corp = $conn->prepare("SELECT * FROM profil_perusahaan WHERE id_user = :u");
    $get_corp->execute([':u' => $id_user]);
    if ($get_corp->rowCount() > 0) {
        $my_corp = $get_corp->fetch();
    }
}

// ─── PROCESS 1: AKSI DATABASE PELAMAR ───
if (isset($_POST['toggle_save'])) {
    $id_loker = $_POST['id_loker'];
    $cek = $conn->prepare("SELECT id_simpan FROM lowongan_tersimpan WHERE id_user = :u AND id_loker = :l");
    $cek->execute([':u' => $id_user, ':l' => $id_loker]);
    
    if ($cek->rowCount() > 0) {
        $del = $conn->prepare("DELETE FROM lowongan_tersimpan WHERE id_user = :u AND id_loker = :l");
        $del->execute([':u' => $id_user, ':l' => $id_loker]);
        $_SESSION['toast'] = ["Dihapus dari daftar tersimpan", "info"];
    } else {
        $ins = $conn->prepare("INSERT INTO lowongan_tersimpan (id_user, id_loker) VALUES (:u, :l)");
        $ins->execute([':u' => $id_user, ':l' => $id_loker]);
        $_SESSION['toast'] = ["Lowongan berhasil disimpan! ✓", "success"];
    }
    header("Location: dashboard.php" . (isset($_GET['type']) ? "?type=".$_GET['type'] : ""));
    exit;
}

// VALIDASI: SEBELUM APPLY, WAJIB PERIKSA APAKAH PELAMAR SUDAH PUNYA CV
if (isset($_POST['ajukan_lamaran'])) {
    $id_loker = $_POST['id_loker'];
    
    $cek_cv = $conn->prepare("SELECT cv_file FROM profil_pelamar WHERE id_user = :u");
    $cek_cv->execute([':u' => $id_user]);
    $profil = $cek_cv->fetch();
    
    if (!$profil || empty($profil['cv_file'])) {
        $_SESSION['toast'] = ["Gagal Melamar! Anda wajib mengunggah CV terlebih dahulu di menu 'Pengaturan Profil'.", "danger"];
        header("Location: dashboard.php");
        exit;
    }

    $check = $conn->prepare("SELECT id_lamaran FROM lamaran WHERE id_loker = :l AND id_user = :u");
    $check->execute([':l' => $id_loker, ':u' => $id_user]);
    
    if ($check->rowCount() == 0) {
        $stmt = $conn->prepare("INSERT INTO lamaran (id_loker, id_user, status_lamaran) VALUES (:l, :u, 'Pending')");
        $stmt->execute([':l' => $id_loker, ':u' => $id_user]);
        $_SESSION['toast'] = ["Lamaran berhasil dikirim! ✓", "success"];
    } else {
        $_SESSION['toast'] = ["Anda sudah melamar posisi ini.", "info"];
    }
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['update_profil_pelamar'])) {
    $telp = $_POST['no_telp'];
    $pendidikan = $_POST['pendidikan_terakhir'];
    $keahlian = $_POST['keahlian'];
    $nama_file_cv = $_POST['cv_lama'] ?? '';

    if (!empty($_FILES['cv_file']['name'])) {
        $ekstensi = pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($ekstensi) == 'pdf') {
            $nama_file_cv = 'cv_' . $id_user . '_' . time() . '.pdf';
            if(!is_dir('uploads')) mkdir('uploads', 0777, true);
            move_uploaded_file($_FILES['cv_file']['tmp_name'], 'uploads/' . $nama_file_cv);
        }
    }

    $cek_prof = $conn->prepare("SELECT id_profil FROM profil_pelamar WHERE id_user = :u");
    $cek_prof->execute([':u' => $id_user]);
    
    if ($cek_prof->rowCount() > 0) {
        $up = $conn->prepare("UPDATE profil_pelamar SET no_telp = :t, pendidikan_terakhir = :p, keahlian = :k, cv_file = :c WHERE id_user = :u");
        $up->execute([':t' => $telp, ':p' => $pendidikan, ':k' => $keahlian, ':c' => $nama_file_cv, ':u' => $id_user]);
    } else {
        $ins = $conn->prepare("INSERT INTO profil_pelamar (id_user, no_telp, pendidikan_terakhir, keahlian, cv_file) VALUES (:u, :t, :p, :k, :c)");
        $ins->execute([':u' => $id_user, ':t' => $telp, ':p' => $pendidikan, ':k' => $keahlian, ':c' => $nama_file_cv]);
    }
    $_SESSION['toast'] = ["Profil Pelamar berhasil diperbarui!", "success"];
    header("Location: dashboard.php");
    exit;
}

// ─── PROCESS 2: AKSI DATABASE HRD / PERUSAHAAN ───
if (isset($_POST['validasi_hrd']) && $user_role === 'hrd') {
    $id_lamaran = $_POST['id_lamaran'];
    $status_baru = $_POST['status_baru'];
    $catatan = filter_input(INPUT_POST, 'catatan', FILTER_SANITIZE_SPECIAL_CHARS);
    $status_lama = $_POST['status_lama'];

    $get_applicant = $conn->prepare("SELECT u.nama, u.email, l.posisi FROM lamaran lm JOIN users u ON lm.id_user = u.id_user JOIN lowongan l ON lm.id_loker = l.id_loker WHERE lm.id_lamaran = :id");
    $get_applicant->execute([':id' => $id_lamaran]);
    $applicant = $get_applicant->fetch();

    try {
        $conn->beginTransaction();
        $up = $conn->prepare("UPDATE lamaran SET status_lamaran = :stat WHERE id_lamaran = :id");
        $up->execute([':stat' => $status_baru, ':id' => $id_lamaran]);
        
        $log = $conn->prepare("INSERT INTO log_validasi (id_lamaran, status_lama, status_baru, catatan, diubah_oleh) VALUES (:id, :lama, :baru, :catatan, :hrd)");
        $log->execute([':id' => $id_lamaran, ':lama' => $status_lama, ':baru' => $status_baru, ':catatan' => $catatan, ':hrd' => $id_user]);
        $conn->commit();
        
        $pesan_toast = "Status berkas pelamar diperbarui!";

        if ($status_baru === 'Diterima' && $applicant) {
            $ke_email = $applicant['email'];
            $nama_user = $applicant['nama'];
            $posisi_kerja = $applicant['posisi'];
            
            // MENYISIPKAN ALAMAT LENGKAP DINAMIS KE TEMPLATE EMAIL INTERVIEW
            $alamat_kirim = !empty($my_corp['alamat_perusahaan']) ? $my_corp['alamat_perusahaan'] : "Ruang HRD Utama Kantor Pusat";
            
            $subjek = "Undangan Interview Kerja Offline - " . $posisi_kerja;
            $isi_email = "Halo " . $nama_user . ",\n\nSelamat! Berkas lamaran Anda dinyatakan lolos seleksi berkas administrasi. Kami mengundang Anda menghadiri sesi wawancara (interview) offline pada:\n\nHari/Tanggal: Senin Depan\nWaktu: 10:00 WIB s/d Selesai\nTempat / Alamat Lengkap Perusahaan:\n" . $alamat_kirim . "\n\nCatatan Tambahan dari HRD:\n" . $catatan . "\n\nSalam,\nTeam Recruitment InfoLoker";
            
            if(!is_dir('mail_logs')) mkdir('mail_logs', 0777, true);
            file_put_contents('mail_logs/mail_' . time() . '.txt', "UNTUK: $ke_email\nSUBJEK: $subjek\n\n$isi_email");

            $pesan_toast = "Berkas Diterima! Email interview offline sukses dikirim ke " . $ke_email;
        }

        $_SESSION['toast'] = [$pesan_toast, "success"];
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['toast'] = ["Gagal memvalidasi.", "danger"];
    }
    header("Location: dashboard.php?menu=hrd");
    exit;
}

if (isset($_POST['tambah_loker']) && $user_role === 'hrd') {
    $perusahaan = $_POST['nama_perusahaan'];
    $posisi = $_POST['posisi'];
    $gaji = $_POST['gaji'];
    $lokasi = $_POST['lokasi'];
    $tipe = $_POST['tipe'];
    $pendidikan = $_POST['minimal_pendidikan'];
    $deskripsi = $_POST['deskripsi'];
    $berkas_wajib = $_POST['berkas_wajib'] ?? 'CV'; 

    $stmt = $conn->prepare("INSERT INTO lowongan (id_user, nama_perusahaan, posisi, gaji, lokasi, tipe, minimal_pendidikan, deskripsi, berkas_wajib, status) VALUES (:hrd, :perusahaan, :posisi, :gaji, :lokasi, :tipe, :edu, :deskripsi, :b, 'aktif')");
    $stmt->execute([':hrd' => $id_user, ':perusahaan' => $perusahaan, ':posisi' => $posisi, ':gaji' => $gaji, ':lokasi' => $lokasi, ':tipe' => $tipe, ':edu' => $pendidikan, ':deskripsi' => $deskripsi, ':b' => $berkas_wajib]);
    $_SESSION['toast'] = ["Lowongan pekerjaan berhasil dipublikasikan!", "success"];
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['edit_loker']) && $user_role === 'hrd') {
    $id_loker = $_POST['id_loker'];
    $perusahaan = $_POST['nama_perusahaan'];
    $posisi = $_POST['posisi'];
    $gaji = $_POST['gaji'];
    $lokasi = $_POST['lokasi'];
    $tipe = $_POST['tipe'];
    $pendidikan = $_POST['minimal_pendidikan'];
    $deskripsi = $_POST['deskripsi'];
    $berkas_wajib = $_POST['berkas_wajib'] ?? 'CV';

    $stmt = $conn->prepare("UPDATE lowongan SET nama_perusahaan = :perusahaan, posisi = :posisi, gaji = :gaji, lokasi = :lokasi, tipe = :tipe, minimal_pendidikan = :edu, deskripsi = :deskripsi, berkas_wajib = :b WHERE id_loker = :id AND id_user = :hrd");
    $stmt->execute([':perusahaan' => $perusahaan, ':posisi' => $posisi, ':gaji' => $gaji, ':lokasi' => $lokasi, ':tipe' => $tipe, ':edu' => $pendidikan, ':deskripsi' => $deskripsi, ':b' => $berkas_wajib, ':id' => $id_loker, ':hrd' => $id_user]);
    
    $_SESSION['toast'] = ["Lowongan pekerjaan berhasil diperbarui!", "success"];
    header("Location: dashboard.php");
    exit;
}

// PROSES MENYIMPAN ALAMAT DETAIL PERUSAHAAN KE DATABASE
if (isset($_POST['update_profil_hrd']) && $user_role === 'hrd') {
    $nama_corp = filter_input(INPUT_POST, 'nama_hrd', FILTER_SANITIZE_SPECIAL_CHARS);
    $email_corp = filter_input(INPUT_POST, 'email_hrd', FILTER_VALIDATE_EMAIL);
    $industri = filter_input(INPUT_POST, 'sektor_industri', FILTER_SANITIZE_SPECIAL_CHARS);
    $alamat_corp = filter_input(INPUT_POST, 'alamat_perusahaan', FILTER_SANITIZE_SPECIAL_CHARS); // Pembaruan Kolom Alamat
    $deskripsi = filter_input(INPUT_POST, 'deskripsi_corp', FILTER_SANITIZE_SPECIAL_CHARS);

    $cek_corp = $conn->prepare("SELECT id_profil_corp FROM profil_perusahaan WHERE id_user = :u");
    $cek_corp->execute([':u' => $id_user]);
    
    if ($cek_corp->rowCount() > 0) {
        $up = $conn->prepare("UPDATE profil_perusahaan SET nama_perusahaan = :n, email_perusahaan = :e, sektor_industri = :i, alamat_perusahaan = :a, deskripsi_perusahaan = :d WHERE id_user = :u");
        $up->execute([':n' => $nama_corp, ':e' => $email_corp, ':i' => $industri, ':a' => $alamat_corp, ':d' => $deskripsi, ':u' => $id_user]);
    } else {
        $ins = $conn->prepare("INSERT INTO profil_perusahaan (id_user, nama_perusahaan, email_perusahaan, sektor_industri, alamat_perusahaan, deskripsi_perusahaan) VALUES (:u, :n, :e, :i, :a, :d)");
        $ins->execute([':u' => $id_user, ':n' => $nama_corp, ':e' => $email_corp, ':i' => $industri, ':a' => $alamat_corp, ':d' => $deskripsi]);
    }
    
    $up_user = $conn->prepare("UPDATE users SET nama = :n WHERE id_user = :u");
    $up_user->execute([':n' => $nama_corp, ':u' => $id_user]);
    $_SESSION['nama'] = $nama_corp;

    $_SESSION['toast'] = ["Profil Perusahaan berhasil diperbarui!", "success"];
    header("Location: dashboard.php");
    exit;
}

if (isset($_SESSION['toast'])) {
    $toast_msg = $_SESSION['toast'][0];
    $toast_type = $_SESSION['toast'][1];
    unset($_SESSION['toast']);
}

// ─── QUERY BACA DATA (READ ENGINE) ───
$filter_type = $_GET['type'] ?? 'All';
$search_word = isset($_GET['q']) ? '%'.$_GET['q'].'%' : '%';

if ($filter_type === 'All') {
    $get_jobs = $conn->prepare("SELECT l.*, (SELECT COUNT(*) FROM lowongan_tersimpan WHERE id_user = :uid AND id_loker = l.id_loker) as is_saved FROM lowongan l WHERE status='aktif' AND (posisi LIKE :q OR nama_perusahaan LIKE :q OR lokasi LIKE :q) ORDER BY id_loker DESC");
    $get_jobs->execute([':q' => $search_word, ':uid' => $id_user]);
} else {
    $get_jobs = $conn->prepare("SELECT l.*, (SELECT COUNT(*) FROM lowongan_tersimpan WHERE id_user = :uid AND id_loker = l.id_loker) as is_saved FROM lowongan l WHERE status='aktif' AND tipe = :tipe AND (posisi LIKE :q OR nama_perusahaan LIKE :q OR lokasi LIKE :q) ORDER BY id_loker DESC");
    $get_jobs->execute([':tipe' => $filter_type, ':q' => $search_word, ':uid' => $id_user]);
}
$data_lowongan = $get_jobs->fetchAll();

// Ambil Data Profil Pelamar
$my_profile = ['no_telp'=>'', 'pendidikan_terakhir'=>'', 'keahlian'=>'', 'cv_file'=>''];
if ($user_role === 'pelamar') {
    $get_prof = $conn->prepare("SELECT * FROM profil_pelamar WHERE id_user = :u");
    $get_prof->execute([':u' => $id_user]);
    if ($get_prof->rowCount() > 0) $my_profile = $get_prof->fetch();
}

$saved_jobs = [];
$my_apps = [];
if ($user_role === 'pelamar') {
    $get_saved = $conn->prepare("SELECT l.* FROM lowongan_tersimpan lt JOIN lowongan l ON lt.id_loker = l.id_loker WHERE lt.id_user = :u");
    $get_saved->execute([':u' => $id_user]);
    $saved_jobs = $get_saved->fetchAll();

    $get_apps = $conn->prepare("SELECT l.*, lw.posisi, lw.nama_perusahaan, lw.lokasi, lw.tipe, lw.berkas_wajib, (SELECT catatan FROM log_validasi WHERE id_lamaran = l.id_lamaran ORDER BY id_log DESC LIMIT 1) as catatan_hrd FROM lamaran l JOIN lowongan lw ON l.id_loker = lw.id_loker WHERE l.id_user = :u ORDER BY l.id_lamaran DESC");
    $get_apps->execute([':u' => $id_user]);
    $my_apps = $get_apps->fetchAll();
}

$hrd_apps = [];
$total_pending = 0; $total_terima = 0; $total_tolak = 0;
if ($user_role === 'hrd') {
    $get_hrd = $conn->prepare("SELECT l.*, u.nama AS nama_pelamar, u.email, lw.posisi, lw.nama_perusahaan, lw.berkas_wajib, pp.cv_file FROM lamaran l JOIN users u ON l.id_user = u.id_user JOIN lowongan lw ON l.id_loker = lw.id_loker LEFT JOIN profil_pelamar pp ON l.id_user = pp.id_user WHERE lw.id_user = :hrd_id ORDER BY l.id_lamaran DESC");
    $get_hrd->execute([':hrd_id' => $id_user]);
    $hrd_apps = $get_hrd->fetchAll();
    foreach ($hrd_apps as $h) {
        if ($h['status_lamaran'] === 'Pending') $total_pending++;
        if ($h['status_lamaran'] === 'Diterima') $total_terima++;
        if ($h['status_lamaran'] === 'Ditolak') $total_tolak++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>InfoLoker — Platform Lowongan Kerja</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet"/>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --primary: #185FA5; --primary-dark: #0C447C; --primary-light: #E6F1FB; --primary-mid: #378ADD;
    --success: #3B6D11; --success-light: #EAF3DE; --warning: #BA7517; --warning-light: #FAEEDA;
    --danger: #A32D2D; --danger-light: #FCEBEB; --gray: #888780; --gray-light: #F1EFE8;
    --text: #1a1a1a; --text-sec: #5c5c5c; --text-ter: #999; --bg: #f5f7fa; --surface: #ffffff;
    --border: rgba(0,0,0,0.08); --border-md: rgba(0,0,0,0.13);
    --radius-sm: 8px; --radius-md: 12px; --radius-lg: 16px; --radius-xl: 24px;
    --shadow-sm: 0 1px 4px rgba(0,0,0,0.06); --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --shadow-lg: 0 8px 32px rgba(0,0,0,0.12);
    --font: 'Plus Jakarta Sans', sans-serif; --font-display: 'DM Serif Display', serif;
    --nav-h: 64px; --sidebar-w: 240px;
  }
  body { font-family: var(--font); background: var(--bg); color: var(--text); font-size: 14px; padding-top: var(--nav-h); min-height: 100vh; }
  
  /* TOAST */
  .toast { position: fixed; bottom: 24px; right: 24px; background: #333; color: #fff; padding: 12px 24px; border-radius: var(--radius-md); font-weight: 600; font-size: 13px; transform: translateY(100px); opacity: 0; transition: 0.3s; z-index: 999; box-shadow: var(--shadow-lg); }
  .toast.show { transform: translateY(0); opacity: 1; }
  .toast.success { background: var(--success); }
  .toast.info { background: var(--primary-dark); }
  .toast.danger { background: var(--danger); }

  /* NAVIGATION */
  .topnav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: var(--nav-h); background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 24px; gap: 16px; box-shadow: var(--shadow-sm); }
  .logo { font-family: var(--font-display); font-size: 22px; color: var(--primary); text-decoration:none; }
  .logo span { color: var(--primary-mid); }
  .nav-search { flex: 1; max-width: 420px; position: relative; }
  .nav-search input { width: 100%; background: var(--bg); border: 1px solid var(--border-md); border-radius: 24px; padding: 9px 16px 9px 40px; font-family: var(--font); font-size: 13px; outline: none; }
  .nav-search svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-ter); }
  .nav-links { display: flex; align-items: center; gap: 12px; margin-left: auto; }
  .avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; border: 2px solid var(--primary); cursor: pointer; }

  /* LAYOUT STRUCT */
  .main-layout { display: flex; min-height: calc(100vh - var(--nav-h)); }
  .sidebar { width: var(--sidebar-w); background: var(--surface); border-right: 1px solid var(--border); padding: 24px 16px; flex-shrink: 0; }
  .sidebar-label { font-size: 11px; font-weight: 700; color: var(--text-ter); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; padding-left: 8px; }
  .sidebar-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; color: var(--text-sec); font-weight: 500; border-radius: var(--radius-sm); cursor: pointer; text-decoration: none; margin-bottom: 4px; transition: 0.2s; }
  .sidebar-item:hover, .sidebar-item.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
  .sidebar-badge { margin-left: auto; background: var(--primary); color: white; padding: 2px 6px; font-size: 11px; font-weight: 700; border-radius: 10px; }
  .sidebar-divider { height: 1px; background: var(--border); margin: 16px 0; }

  .content-body { flex: 1; padding: 32px; overflow-x: hidden; }
  .page-panel { display: none; }
  .page-panel.active { display: block; }

  /* HERO */
  .hero { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, var(--primary-mid) 100%); border-radius: var(--radius-xl) ; padding: 40px; color: #fff; margin-bottom: 32px; }
  .hero h1 { font-family: var(--font-display); font-size: 32px; line-height: 1.2; margin-bottom: 8px; }
  .hero p { opacity: 0.85; font-size: 14px; }
  
  /* CHIPS */
  .chips { display: flex; gap: 8px; margin-bottom: 24px; }
  .chip { padding: 8px 16px; border-radius: 20px; border: 1px solid var(--border-md); background: #fff; color: var(--text-sec); font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; }
  .chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }

  /* JOBS CARD */
  .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
  .job-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; position: relative; transition: 0.2s; box-shadow: var(--shadow-sm); }
  .job-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
  .job-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
  .company-box { display: flex; gap: 12px; align-items: center; }
  .company-logo { width: 44px; height: 44px; background: var(--primary-light); color: var(--primary); font-weight: 700; font-size: 18px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); }
  .job-title { font-weight: 700; font-size: 15px; color: var(--text); }
  .company-name { font-size: 12px; color: var(--text-sec); }
  .btn-save-bookmark { background: none; border: none; cursor: pointer; color: var(--text-ter); padding: 4px; }
  .btn-save-bookmark.saved { color: var(--danger); }
  .job-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0; }
  .tag { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: var(--bg); color: var(--text-sec); }
  .tag.blue { background: var(--primary-light); color: var(--primary); }
  .tag.green { background: var(--success-light); color: var(--success); }
  .tag.purple { background: #F3EAFB; color: #7822A8; }
  .job-salary { font-weight: 700; color: var(--primary); font-size: 14px; margin-bottom: 16px; }
  .btn-apply { width: 100%; padding: 10px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); font-weight: 600; font-size: 13px; cursor: pointer; }

  /* CARDS STATISTICS & TABLE */
  .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
  .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px 18px; }
  .stat-card-num { font-size: 24px; font-weight: 700; }
  .stat-card-lbl { font-size: 12px; color: var(--text-sec); }
  .table-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: var(--bg); padding: 12px; font-weight: 600; font-size: 12px; color: var(--text-sec); border-bottom: 2px solid var(--border); text-align: left; }
  td { padding: 14px 12px; border-bottom: 1px solid var(--border); font-size: 13px; }
  .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
  .badge.Pending { background: var(--warning-light); color: var(--warning); }
  .badge.Diterima { background: var(--success-light); color: var(--success); }
  .badge.Ditolak { background: var(--danger-light); color: var(--danger); }

  /* FORMS */
  .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
  .form-label { font-size: 12px; font-weight: 600; color: var(--text-sec); }
  .form-control { padding: 10px 14px; border: 1px solid var(--border-md); border-radius: var(--radius-sm); font-family: inherit; font-size: 13px; outline: none; background:#fff; }

  /* MODAL STYLE */
  .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
  .modal-overlay.active { display: flex; }
</style>
</head>
<body>

<div id="toast-notif" class="toast <?= $toast_msg ? 'show ' . $toast_type : '' ?>">
  <span><?= htmlspecialchars($toast_msg) ?></span>
</div>

<!-- TOPBAR -->
<nav class="topnav">
  <a href="dashboard.php" class="logo">Info<span>Loker</span></a>
  <div class="nav-search">
    <form method="GET" action="dashboard.php">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="q" placeholder="Cari posisi, keahlian, kota penempatan..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"/>
    </form>
  </div>
  <div class="nav-links">
    <span style="font-size:13px; font-weight:600; color:var(--text-sec);"><?= htmlspecialchars($user_nama) ?> (<?= strtoupper($user_role) ?>)</span>
    <div class="avatar" onclick="pindahMenu('profile')"><?= $user_initial ?></div>
  </div>
</nav>

<div class="main-layout">
  
  <!-- SIDEBAR NAVIGATION -->
  <aside class="sidebar">
    <div class="sidebar-label">Menu Utama</div>
    <div class="sidebar-item active" id="side-home" onclick="pindahMenu('home')">📁 Jelajah Lowongan</div>
    
    <?php if($user_role === 'pelamar'): ?>
      <div class="sidebar-item" id="side-saved" onclick="pindahMenu('saved')">❤️ Lowongan Tersimpan <span class="sidebar-badge"><?= count($saved_jobs) ?></span></div>
      <div class="sidebar-item" id="side-apps" onclick="pindahMenu('apps')">📩 Lamaran Saya <span class="sidebar-badge"><?= count($my_apps) ?></span></div>
      <div class="sidebar-item" id="side-profile" onclick="pindahMenu('profile')">👤 Pengaturan Profil</div>
    <?php endif; ?>

    <!-- PANEL KHUSUS HRD -->
    <?php if($user_role === 'hrd'): ?>
      <div class="sidebar-divider"></div>
      <div class="sidebar-label">Panel HRD / Perusahaan</div>
      <div class="sidebar-item" id="side-hrd" onclick="pindahMenu('hrd')">⚙️ Kelola Pelamar <span class="sidebar-badge" style="background:var(--warning);"><?= $total_pending ?></span></div>
      <div class="sidebar-item" id="side-post" onclick="pindahMenu('post')">➕ Pasang Loker</div>
      <div class="sidebar-item" id="side-profile" onclick="pindahMenu('profile')">🏢 Profil Perusahaan</div>
    <?php endif; ?>

    <div class="sidebar-divider"></div>
    <a href="logout.php" class="sidebar-item" style="color:var(--danger);">🚪 Keluar</a>
  </aside>

  <!-- CONTENT PANELS -->
  <main class="content-body">

    <!-- PANEL 1: JELAJAH LOWONGAN -->
    <div class="page-panel active" id="panel-home">
      <div class="hero">
        <h1>Temukan Pekerjaan Impian<br>Masa Depanmu</h1>
        <p>Ribuan gerbang karir perusahaan terkemuka Indonesia siap kamu masuki hari ini.</p>
      </div>

      <div class="chips">
        <a href="dashboard.php?type=All" class="chip <?= $filter_type==='All'?'active':'' ?>">Semua Kategori</a>
        <a href="dashboard.php?type=Full-time" class="chip <?= $filter_type==='Full-time'?'active':'' ?>">Full-time</a>
        <a href="dashboard.php?type=Part-time" class="chip <?= $filter_type==='Part-time'?'active':'' ?>">Part-time</a>
        <a href="dashboard.php?type=Remote" class="chip <?= $filter_type==='Remote'?'active':'' ?>">Remote</a>
      </div>

      <div class="jobs-grid">
        <?php if(empty($data_lowongan)): ?>
          <p style="color:var(--text-ter); grid-column: 1/-1;">Lowongan pekerjaan tidak ditemukan.</p>
        <?php else: ?>
          <?php foreach($data_lowongan as $job): ?>
            <div class="job-card">
              <div class="job-header">
                <div class="company-box">
                  <div class="company-logo"><?= substr($job['nama_perusahaan'],0,1) ?></div>
                  <div>
                    <div class="job-title"><?= htmlspecialchars($job['posisi']) ?></div>
                    <div class="company-name"><?= htmlspecialchars($job['nama_perusahaan']) ?></div>
                  </div>
                </div>
                <?php if($user_role === 'pelamar'): ?>
                  <form method="POST" action="dashboard.php<?= isset($_GET['type']) ? '?type='.$_GET['type'] : '' ?>">
                    <input type="hidden" name="id_loker" value="<?= $job['id_loker'] ?>">
                    <button type="submit" name="toggle_save" class="btn-save-bookmark <?= $job['is_saved'] ? 'saved' : '' ?>">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $job['is_saved'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
              <div class="job-tags">
                <span class="tag blue">📍 <?= htmlspecialchars($job['lokasi']) ?></span>
                <span class="tag green">💼 <?= htmlspecialchars($job['tipe']) ?></span>
                <span class="tag purple">🎓 Minimal <?= htmlspecialchars($job['minimal_pendidikan'] ?? 'Semua Jenjang') ?></span>
              </div>
              
              <div style="font-size:11px; font-weight:600; color:var(--warning); margin-bottom:8px;">
                📄 Wajib Kirim: <?= htmlspecialchars($job['berkas_wajib'] ?? 'CV') ?>
              </div>

              <div class="job-salary"><?= htmlspecialchars($job['gaji']) ?></div>
              
              <?php if($user_role === 'pelamar'): ?>
                <form method="POST" action="">
                  <input type="hidden" name="id_loker" value="<?= $job['id_loker'] ?>">
                  <button type="submit" name="ajukan_lamaran" class="btn-apply">Lamar Sekarang</button>
                </form>
              <?php else: ?>
                <?php if($job['id_user'] == $id_user): ?>
                  <button type="button" class="btn-apply" style="background:var(--warning);" 
                          onclick="bukaEditModal(this)"
                          data-id="<?= $job['id_loker'] ?>"
                          data-perusahaan="<?= htmlspecialchars($job['nama_perusahaan']) ?>"
                          data-posisi="<?= htmlspecialchars($job['posisi']) ?>"
                          data-berkas="<?= htmlspecialchars($job['berkas_wajib'] ?? 'CV') ?>"
                          data-pendidikan="<?= htmlspecialchars($job['minimal_pendidikan'] ?? 'Semua Jenjang') ?>"
                          data-gaji="<?= htmlspecialchars($job['gaji']) ?>"
                          data-lokasi="<?= htmlspecialchars($job['lokasi']) ?>"
                          data-tipe="<?= htmlspecialchars($job['tipe']) ?>"
                          data-deskripsi="<?= htmlspecialchars($job['deskripsi']) ?>">
                    ✏️ Edit Lowongan
                  </button>
                <?php else: ?>
                  <div style="font-size:11px; text-align:center; color:var(--text-ter); background:#f9f9f9; padding:6px; border-radius:6px;">Melihat sebagai Perusahaan</div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- PANEL 2: LOWONGAN TERSIMPAN -->
    <div class="page-panel" id="panel-saved">
      <div class="table-card">
        <h2 style="font-size:16px; margin-bottom:16px;">❤️ Lowongan Kerja Yang Anda Simpan</h2>
        <div class="jobs-grid">
          <?php if(empty($saved_jobs)): ?>
            <p style="color:var(--text-ter);">Belum ada lowongan pekerjaan yang Anda simpan.</p>
          <?php else: ?>
            <?php foreach($saved_jobs as $sj): ?>
              <div class="job-card">
                <div class="job-header">
                  <div class="company-box">
                    <div class="company-logo"><?= substr($sj['nama_perusahaan'],0,1) ?></div>
                    <div>
                      <div class="job-title"><?= htmlspecialchars($sj['posisi']) ?></div>
                      <div class="company-name"><?= htmlspecialchars($sj['nama_perusahaan']) ?></div>
                    </div>
                  </div>
                </div>
                <div class="job-tags">
                  <span class="tag blue">📍 <?= htmlspecialchars($sj['lokasi']) ?></span>
                  <span class="tag green">💼 <?= htmlspecialchars($sj['tipe']) ?></span>
                  <span class="tag purple">🎓 Minimal <?= htmlspecialchars($sj['minimal_pendidikan'] ?? 'Semua Jenjang') ?></span>
                </div>
                <div class="job-salary"><?= htmlspecialchars($sj['gaji']) ?></div>
                <form method="POST" action="">
                  <input type="hidden" name="id_loker" value="<?= $sj['id_loker'] ?>">
                  <button type="submit" name="ajukan_lamaran" class="btn-apply">Kirim Lamaran Sekarang</button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PANEL 3: LAMARAN SAYA -->
    <div class="page-panel" id="panel-apps">
      <div class="table-card">
        <h2 style="font-size:16px; margin-bottom:16px;">📩 Riwayat Berkas Lamaran Anda</h2>
        <table>
          <thead>
            <tr>
              <th>Posisi Lowongan</th>
              <th>Jenis Kerja</th>
              <th>Tanggal Daftar</th>
              <th>Status Kelulusan</th>
              <th>Catatan Evaluasi HRD (Feedback)</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($my_apps)): ?>
              <tr><td colspan="5" style="text-align:center; color:var(--text-ter);">Anda belum mengirim lamaran kerja apapun.</td></tr>
            <?php else: ?>
              <?php foreach($my_apps as $app): ?>
                <tr>
                  <td><b><?= htmlspecialchars($app['posisi']) ?></b><br><small style="color:var(--text-sec);"><?= htmlspecialchars($app['nama_perusahaan']) ?> — <?= htmlspecialchars($app['lokasi']) ?></small></td>
                  <td><span class="tag blue"><?= htmlspecialchars($app['tipe']) ?></span></td>
                  <td><?= date('d M Y', strtotime($app['tgl_melamar'])) ?></td>
                  <td><span class="badge <?= $app['status_lamaran'] ?>"><?= $app['status_lamaran'] ?></span></td>
                  
                  <td style="color:var(--primary-dark); font-weight:500;">
                    <?= !empty($app['catatan_hrd']) ? '💬 ' . htmlspecialchars($app['catatan_hrd']) : '<span style="color:#aaa; font-style:italic;">Belum ditinjau HRD</span>' ?>
                    <?php if($app['status_lamaran'] === 'Diterima'): ?>
                      <br><small style="color:var(--success); font-weight:700;">📨 Panggilan interview offline otomatis dikirim ke email Anda!</small>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PANEL 4: PENGATURAN PROFIL -->
    <div class="page-panel" id="panel-profile">
      <div class="table-card" style="max-width:600px; margin:0 auto;">
        
        <?php if($user_role === 'pelamar'): ?>
          <!-- PROFIL PELAMAR -->
          <h2 style="font-size:16px; margin-bottom:16px;">👤 Kelola Profil Pelamar</h2>
          <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="cv_lama" value="<?= htmlspecialchars($my_profile['cv_file'] ?? '') ?>">
            <div class="form-group">
              <label class="form-label">Nomor WhatsApp / Telepon Utama</label>
              <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($my_profile['no_telp']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Pendidikan Terakhir</label>
              <input type="text" name="pendidikan_terakhir" class="form-control" value="<?= htmlspecialchars($my_profile['pendidikan_terakhir']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Keahlian Utama (Pisahkan dengan Koma)</label>
              <input type="text" name="keahlian" class="form-control" value="<?= htmlspecialchars($my_profile['keahlian']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Unggah Dokumen CV Baru (.PDF)</label>
              <input type="file" name="cv_file" class="form-control" accept=".pdf">
              <?php if(!empty($my_profile['cv_file'])): ?>
                <small style="color:var(--success); font-weight:600; margin-top:4px; display:block;">✓ CV Anda saat ini aktif: <a href="uploads/<?= $my_profile['cv_file'] ?>" target="_blank" style="color:var(--primary);">Buka File PDF</a></small>
              <?php else: ?>
                <small style="color:var(--danger); font-weight:600; margin-top:4px; display:block;">⚠️ Belum unggah CV. Anda tidak bisa melamar loker sebelum mengunggah file PDF.</small>
              <?php endif; ?>
            </div>
            <button type="submit" name="update_profil_pelamar" class="btn-apply" style="margin-top:12px;">💾 Simpan Profil Pelamar</button>
          </form>

        <?php else: ?>
          <!-- PROFIL KHUSUS HRD / PERUSAHAAN -->
          <h2 style="font-size:16px; margin-bottom:16px;">🏢 Pengaturan Profil Perusahaan / Instansi</h2>
          <form method="POST" action="">
            <div class="form-group">
              <label class="form-label">Nama Resmi Perusahaan</label>
              <input type="text" name="nama_hrd" class="form-control" value="<?= htmlspecialchars($my_corp['nama_perusahaan'] ?? '') ?>" required placeholder="Contoh: PT Tokopedia Indonesia">
            </div>
            <div class="form-group">
              <label class="form-label">Email Korespondensi / HRD</label>
              <input type="email" name="email_hrd" class="form-control" value="<?= htmlspecialchars($my_corp['email_perusahaan'] ?? '') ?>" required placeholder="Contoh: recruitment@tokopedia.com">
            </div>
            <div class="form-group">
              <label class="form-label">Sektor Industri</label>
              <input type="text" name="sektor_industri" class="form-control" value="<?= htmlspecialchars($my_corp['sektor_industri'] ?? '') ?>" placeholder="Contoh: Teknologi, E-Commerce, Perbankan">
            </div>
            
            <!-- INPUT ALAMAT LENGKAP PERUSAHAAN -->
            <div class="form-group">
              <label class="form-label">Alamat Lengkap Perusahaan</label>
              <textarea name="alamat_perusahaan" class="form-control" style="height: 70px;" placeholder="Tuliskan alamat detail, nomor gedung, lantai, RT/RW, kota, dan kode pos lokasi interview..."><?= htmlspecialchars($my_corp['alamat_perusahaan'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Deskripsi Singkat Perusahaan</label>
              <textarea name="deskripsi_corp" class="form-control" style="height: 80px;" placeholder="Tuliskan visi misi atau profil singkat perusahaan..."><?= htmlspecialchars($my_corp['deskripsi_perusahaan'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Status Verifikasi Badan Usaha</label>
              <?php if(($my_corp['status_verifikasi'] ?? '') === 'Terverifikasi'): ?>
                <div style="padding:10px; border-radius:6px; background:var(--success-light); color:var(--success); font-weight:700; font-size:12px; display:inline-block;">✓ Institusi Terverifikasi Sistem</div>
              <?php else: ?>
                <div style="padding:10px; border-radius:6px; background:var(--warning-light); color:var(--warning); font-weight:700; font-size:12px; display:inline-block;">⚠️ Akun Profil Dasar (Belum Verifikasi Berkas)</div>
              <?php endif; ?>
            </div>
            <button type="submit" name="update_profil_hrd" class="btn-apply" style="margin-top:12px; background:var(--primary-dark)">💾 Simpan Profil Perusahaan</button>
          </form>
        <?php endif; ?>

      </div>
    </div>

    <!-- PANEL 5: DASHBOARD CONTROL KELOLA PELAMAR -->
    <div class="page-panel" id="panel-hrd">
      <div class="stats-row">
        <div class="stat-card"><div class="stat-card-num"><?= count($hrd_apps) ?></div><div class="stat-card-lbl">Total Berkas Masuk</div></div>
        <div class="stat-card" style="border-left:4px solid var(--warning);"><div class="stat-card-num"><?= $total_pending ?></div><div class="stat-card-lbl">Perlu Validasi</div></div>
        <div class="stat-card" style="border-left:4px solid var(--success);"><div class="stat-card-num"><?= $total_terima ?></div><div class="stat-card-lbl">Diterima</div></div>
        <div class="stat-card" style="border-left:4px solid var(--danger);"><div class="stat-card-num"><?= $total_tolak ?></div><div class="stat-card-lbl">Ditolak</div></div>
      </div>

      <div class="table-card">
        <h2 style="font-size:16px; margin-bottom:16px;">Validasi Kelayakan Berkas Pelamar (HRD)</h2>
        <table>
          <thead>
            <tr>
              <th>Identitas Pelamar</th>
              <th>Posisi Dilamar & Kebutuhan</th>
              <th>Status Saat Ini</th>
              <th>Validasi Keputusan HRD</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($hrd_apps)): ?>
              <tr><td colspan="4" style="text-align:center; color:var(--text-ter);">Belum ada pelamar yang mendaftar ke lowongan Anda.</td></tr>
            <?php else: ?>
              <?php foreach($hrd_apps as $row): ?>
                <tr>
                  <td>
                    <b><?= htmlspecialchars($row['nama_pelamar']) ?></b><br>
                    <small><?= htmlspecialchars($row['email']) ?></small><br>
                    
                    <?php if(!empty($row['cv_file'])): ?>
                      <a href="uploads/<?= htmlspecialchars($row['cv_file']) ?>" target="_blank" style="color:var(--primary); font-weight:600; text-decoration:underline; display:inline-block; margin-top:4px;">📄 Lihat CV Pelamar</a>
                    <?php else: ?>
                      <span style="color:var(--danger); font-size:11px; font-weight:600;">⚠️ CV Tidak Ditemukan</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <b><?= htmlspecialchars($row['posisi']) ?></b><br>
                    <small style="color:var(--warning); font-weight:600;">Wajib: <?= htmlspecialchars($row['berkas_wajib'] ?? 'CV') ?></small>
                  </td>
                  <td><span class="badge <?= $row['status_lamaran'] ?>"><?= $row['status_lamaran'] ?></span></td>
                  <td>
                    <form method="POST" action="" style="display:flex; gap:6px;">
                      <input type="hidden" name="id_lamaran" value="<?= $row['id_lamaran'] ?>">
                      <input type="hidden" name="status_lama" value="<?= $row['status_lamaran'] ?>">
                      <select name="status_baru" class="form-control" style="width:120px; padding:4px;" required>
                        <option value="Pending" <?= $row['status_lamaran']=='Pending'?'selected':'' ?>>Pending</option>
                        <option value="Diterima" <?= $row['status_lamaran']=='Diterima'?'selected':'' ?>>Terima & Email</option>
                        <option value="Ditolak" <?= $row['status_lamaran']=='Ditolak'?'selected':'' ?>>Tolak Berkas</option>
                      </select>
                      <input type="text" name="catatan" placeholder="Catatan/Alasan..." class="form-control" style="width:120px; padding:4px;" required>
                      <button type="submit" name="validasi_hrd" class="btn-apply" style="width:auto; padding:4px 12px; font-size:12px;">Simpan</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PANEL 6: PASANG LOKER -->
    <div class="page-panel" id="panel-post">
      <div class="table-card" style="max-width:650px; margin:0 auto;">
        <h2 style="font-size:16px; margin-bottom:16px;">➕ Formulir Pembuatan Lowongan Kerja Baru</h2>
        <form method="POST" action="" autocomplete="off">
          <div class="form-group">
            <label class="form-label">Nama Perusahaan</label>
            <input type="text" name="nama_perusahaan" class="form-control" required value="<?= htmlspecialchars($user_nama) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Posisi Jabatan</label>
            <input type="text" name="posisi" class="form-control" required placeholder="Contoh: Senior Data Analyst">
          </div>

          <div class="form-group">
            <label class="form-label">Berkas/Surat yang Wajib Dilampirkan Pelamar</label>
            <input type="text" name="berkas_wajib" class="form-control" value="CV, Surat Lamaran, SKCK, Portofolio" required placeholder="Contoh: CV dan Portofolio Desain">
          </div>

          <div class="form-group">
            <label class="form-label">Minimal Pendidikan Pelamar</label>
            <select name="minimal_pendidikan" class="form-control" required>
              <option value="Semua Jenjang">Semua Jenjang / Lulusan</option>
              <option value="SMA/SMK Sederajat">SMA / SMK Sederajat</option>
              <option value="Diploma (D3)">Diploma (D3)</option>
              <option value="Sarjana (S1)">Sarjana (S1 / D4)</option>
              <option value="Magister (S2)">Magister (S2)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Estimasi Gaji Bulanan</label>
            <input type="text" name="gaji" class="form-control" required placeholder="Contoh: Rp 12.000.000">
          </div>
          <div class="form-group">
            <label class="form-label">Lokasi Penempatan</label>
            <input type="text" name="lokasi" class="form-control" required placeholder="Contoh: Jakarta Pusat">
          </div>
          <div class="form-group">
            <label class="form-label">Tipe Pekerjaan</label>
            <select name="tipe" class="form-control" required>
              <option value="Full-time">Full-time</option>
              <option value="Part-time">Part-time</option>
              <option value="Remote">Remote</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Deskripsi Pekerjaan</label>
            <textarea name="deskripsi" class="form-control" style="height:100px;" required></textarea>
          </div>
          <button type="submit" name="tambah_loker" class="btn-apply" style="width:100%; margin-top:12px; background:var(--success)">🚀 Publikasikan Lowongan Kerja</button>
        </form>
      </div>
    </div>

  </main>
</div>

<!-- OVERLAY MODAL FOR POPUP EDIT LOKER -->
<div id="modal-edit" class="modal-overlay">
  <div class="table-card" style="max-width:600px; width:100%; max-height:90vh; overflow-y:auto; margin:0;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <h2 style="font-size:16px;">✏️ Perbarui Data Lowongan Kerja</h2>
      <button type="button" onclick="tutupEditModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--danger); font-weight:700;">&times;</button>
    </div>
    
    <form method="POST" action="" autocomplete="off">
      <input type="hidden" name="id_loker" id="edit-id">
      
      <div class="form-group">
        <label class="form-label">Nama Perusahaan</label>
        <input type="text" name="nama_perusahaan" id="edit-perusahaan" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Posisi Jabatan</label>
        <input type="text" name="posisi" id="edit-posisi" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Berkas Wajib Pelamar</label>
        <input type="text" name="berkas_wajib" id="edit-berkas" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Minimal Pendidikan Pelamar</label>
        <select name="minimal_pendidikan" id="edit-pendidikan" class="form-control" required>
          <option value="Semua Jenjang">Semua Jenjang / Lulusan</option>
          <option value="SMA/SMK Sederajat">SMA / SMK Sederajat</option>
          <option value="Diploma (D3)">Diploma (D3)</option>
          <option value="Sarjana (S1)">Sarjana (S1 / D4)</option>
          <option value="Magister (S2)">Magister (S2)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Estimasi Gaji Bulanan</label>
        <input type="text" name="gaji" id="edit-gaji" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Lokasi Penempatan</label>
        <input type="text" name="lokasi" id="edit-lokasi" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Tipe Pekerjaan</label>
        <select name="tipe" id="edit-tipe" class="form-control" required>
          <option value="Full-time">Full-time</option>
          <option value="Part-time">Part-time</option>
          <option value="Remote">Remote</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Deskripsi Pekerjaan</label>
        <textarea name="deskripsi" id="edit-deskripsi" class="form-control" style="height:100px;" required></textarea>
      </div>
      
      <button type="submit" name="edit_loker" class="btn-apply" style="background:var(--success); margin-top:8px;">💾 Simpan Perubahan Loker</button>
    </form>
  </div>
</div>

<script>
  function pindahMenu(panelId) {
    document.querySelectorAll('.page-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar-item').forEach(s => s.classList.remove('active'));
    
    const targetPanel = document.getElementById('panel-' + panelId);
    if(targetPanel) targetPanel.classList.add('active');
    
    const targetSide = document.getElementById('side-' + panelId);
    if(targetSide) targetSide.classList.add('active');
  }

  function bukaEditModal(button) {
    document.getElementById('edit-id').value = button.getAttribute('data-id');
    document.getElementById('edit-perusahaan').value = button.getAttribute('data-perusahaan');
    document.getElementById('edit-posisi').value = button.getAttribute('data-posisi');
    document.getElementById('edit-berkas').value = button.getAttribute('data-berkas');
    document.getElementById('edit-pendidikan').value = button.getAttribute('data-pendidikan');
    document.getElementById('edit-gaji').value = button.getAttribute('data-gaji');
    document.getElementById('edit-lokasi').value = button.getAttribute('data-lokasi');
    document.getElementById('edit-tipe').value = button.getAttribute('data-tipe');
    document.getElementById('edit-deskripsi').value = button.getAttribute('data-deskripsi');
    
    document.getElementById('modal-edit').classList.add('active');
  }

  function tutupEditModal() {
    document.getElementById('modal-edit').classList.remove('active');
  }

  window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('menu') === 'hrd') {
        pindahMenu('hrd');
    }
    
    const t = document.getElementById('toast-notif');
    if(t && t.classList.contains('show')) {
      setTimeout(() => { t.classList.remove('show'); }, 3000);
    }
  });
</script>
</body>
</html>