<?php
session_start();
include 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$username = '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

if ($is_logged_in) {
    // Ambil username untuk ditampilkan di navbar
    $user_query = mysqli_query($koneksi, "SELECT username FROM users WHERE id = '$user_id'");
    $username = mysqli_fetch_assoc($user_query)['username'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - BookStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    
    <nav class="bg-white shadow-md">
        <div class="container mx-auto p-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-indigo-600">BookStore</h1>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600 font-medium">Beranda</a>
                <a href="about.php" class="text-gray-600 hover:text-indigo-600 font-medium">About Us</a>
                <a href="contact.php" class="text-gray-600 hover:text-indigo-600 font-medium">Kontak Admin</a>
                
                <?php if ($is_logged_in): ?>
                    <a href="cart.php" class="text-gray-600 hover:text-indigo-600 flex items-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </a>
                    <span class="text-indigo-600 font-bold"><?php echo htmlspecialchars($username); ?></span>
                    <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded-full text-sm hover:bg-red-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-white bg-indigo-600 px-3 py-1 rounded-full text-sm hover:bg-indigo-700">Login</a>
                    <a href="register.php" class="text-indigo-600 border border-indigo-600 px-3 py-1 rounded-full text-sm hover:bg-indigo-50">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-4xl mx-auto">
            <h2 class="text-4xl font-extrabold mb-6 text-gray-900 text-center">Tentang BookStore</h2>
            
            <p class="mb-4 text-gray-700 leading-relaxed">
                BookStore adalah platform toko buku online yang didedikasikan untuk menyediakan koleksi buku terlengkap dari berbagai genre, mulai dari fiksi, non-fiksi, buku pelajaran, hingga pemrograman. Kami berkomitmen untuk memudahkan Anda menemukan dan membeli buku favorit Anda dengan cepat dan aman.
            </p>
            
            <h3 class="text-2xl font-semibold mt-8 mb-4 text-indigo-600 border-b pb-2">Visi Kami</h3>
            <p class="text-gray-700 leading-relaxed">
                Menjadi toko buku digital terdepan di Indonesia, mendukung literasi nasional, dan menghubungkan pembaca dengan dunia pengetahuan.
            </p>

            <h3 class="text-2xl font-semibold mt-8 mb-4 text-indigo-600 border-b pb-2">Layanan Kami</h3>
            <ul class="list-disc list-inside text-gray-700 space-y-2">
                <li>Koleksi buku terbaru dan terpopuler.</li>
                <li>Sistem pencarian yang akurat.</li>
                <li>**Payment at Delivery** untuk kenyamanan bertransaksi.</li>
                <li>Dukungan pelanggan melalui fitur **Contact to Admin**.</li>
            </ul>
            
            <p class="mt-10 text-center text-sm text-gray-500">
                Terima kasih telah memilih BookStore sebagai tujuan belanja buku Anda.
            </p>
        </div>
    </div>
</body>
</html>