<?php
session_start();
include 'config.php'; 

$is_logged_in = isset($_SESSION['user_id']);
$username = '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_email = '';
$message_status = '';

// Jika login, ambil email user untuk form
if ($is_logged_in) {
    // Ambil username untuk ditampilkan di navbar
    $user_query = mysqli_query($koneksi, "SELECT username FROM users WHERE id = '$user_id'");
    $username = mysqli_fetch_assoc($user_query)['username'];
}

// Logika Pengiriman Pesan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email   = mysqli_real_escape_string($koneksi, $_POST['email']);
    $subject = mysqli_real_escape_string($koneksi, $_POST['subject']);
    $msg     = mysqli_real_escape_string($koneksi, $_POST['message']);
    
    // Simpan pesan ke database
    $query = "INSERT INTO messages (user_id, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    // Menggunakan user_id jika user login, jika tidak, masukkan NULL
    if ($is_logged_in) {
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $email, $subject, $msg);
    } else {
        $null_id = null;
        mysqli_stmt_bind_param($stmt, "isss", $null_id, $email, $subject, $msg);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $message_status = "Pesan berhasil dikirim ke Admin. Kami akan segera merespons!";
    } else {
        $message_status = "Gagal mengirim pesan: " . mysqli_error($koneksi);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Admin - BookStore</title>
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
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl mx-auto">
            <h2 class="text-3xl font-semibold mb-6 text-gray-800 text-center">Hubungi Admin</h2>
            <p class="text-gray-600 mb-6 text-center">
                Silakan kirimkan pertanyaan, saran, atau keluhan Anda.
            </p>

            <?php if ($message_status): ?>
                <div class="p-3 mb-4 rounded-md bg-green-100 text-green-700">
                    <?php echo $message_status; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="contact.php" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Anda</label>
                    <input type="email" id="email" name="email" required 
                           class="mt-1 mb-3 w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Subjek</label>
                    <input type="text" id="subject" name="subject" required 
                           class="mt-1 mb-3 w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Pesan</label>
                    <textarea id="message" name="message" rows="5" required 
                              class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                    Kirim Pesan
                </button>
            </form>
        </div>
    </div>
</body>
</html>