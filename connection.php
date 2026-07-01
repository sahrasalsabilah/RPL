<?php
// connection.php
$host = "localhost";
$username = "root";
$password = "";
$database = "db_infoloker"; // Sesuaikan dengan nama database di phpMyAdmin Anda

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}
?>