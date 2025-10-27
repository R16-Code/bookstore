<?php
// config.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "bookstore_db";

// Membuat koneksi ke database MySQL
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Memeriksa koneksi
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Opsional: Atur encoding karakter ke UTF-8
mysqli_set_charset($koneksi, "utf8");

// Catatan: Variabel $koneksi ini akan di-include di setiap file PHP yang butuh akses DB.
?>