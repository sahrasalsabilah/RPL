<?php
// view_file.php
session_start();
if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak. Anda harus login untuk melihat berkas.");
}

$file = basename($_GET['file'] ?? ''); 
$filepath = 'uploads/' . $file;

if (!empty($file) && file_exists($filepath)) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $file . '"');
        readfile($filepath);
        exit;
    } else {
        die("Format berkas tidak didukung.");
    }
} else {
    die("Berkas lampiran tidak ditemukan di server.");
}