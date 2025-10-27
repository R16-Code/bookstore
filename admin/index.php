<?php
session_start();
include '../config.php'; // Kembali ke folder root untuk config

// **Guardrail Keamanan:** Cek apakah user sudah login dan role-nya adalah Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Data statistik untuk dashboard
$total_books = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) AS total FROM books"))['total'];
$total_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) AS total FROM users WHERE role = 'User'"))['total'];
$total_orders = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) AS total FROM orders"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BookStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-600 p-4 text-white shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Admin BookStore</h1>
            <div>
                <a href="index.php" class="bg-indigo-700 px-3 py-2 rounded">Dashboard</a>
                <a href="categories.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Kategori</a>
                <a href="books.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Data Buku</a>
                <a href="users.php" class="hover:bg-indigo-700 px-3 py-2 rounded">List User</a>
                <a href="orders.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Pesanan</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded ml-4">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold mb-8 text-gray-800">Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="bg-white p-6 rounded-lg shadow-lg border-l-4 border-indigo-500">
                <p class="text-sm font-medium text-gray-500">Total Buku</p>
                <p class="text-4xl font-bold text-gray-900 mt-1"><?php echo $total_books; ?></p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg border-l-4 border-green-500">
                <p class="text-sm font-medium text-gray-500">Pelanggan Terdaftar</p>
                <p class="text-4xl font-bold text-gray-900 mt-1"><?php echo $total_users; ?></p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg border-l-4 border-yellow-500">
                <p class="text-sm font-medium text-gray-500">Total Pesanan</p>
                <p class="text-4xl font-bold text-gray-900 mt-1"><?php echo $total_orders; ?></p>
            </div>
            
        </div>

        <div class="mt-10 p-6 bg-white rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold text-gray-800">Selamat Datang, Admin!</h3>
            <p class="mt-2 text-gray-600">Gunakan menu navigasi di atas untuk mengelola data buku, kategori, dan pesanan pelanggan.</p>
        </div>
        
    </div>
</body>
</html>