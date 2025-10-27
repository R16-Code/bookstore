<?php
include 'config.php';
// Inisialisasi variabel untuk pesan
$message = '';
$message_type = ''; // 'success' atau 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($email) || empty($password)) {
        $message = "Semua field harus diisi!";
        $message_type = 'error';
    } else {
        // Hashing Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password, email, role) 
                  VALUES (?, ?, ?, 'User')";
        
        $stmt = mysqli_prepare($koneksi, $query);
        // "sss" menunjukkan tiga string (username, hashed_password, email)
        mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $email);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Registrasi berhasil! Silakan Login.";
            $message_type = 'success';
            header("Location: login.php"); exit(); // Bisa langsung redirect
        } else {
            if (mysqli_errno($koneksi) == 1062) {
                $message = "Username atau Email sudah terdaftar.";
            } else {
                $message = "Error saat registrasi: " . mysqli_error($koneksi);
            }
            $message_type = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi User - BookStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-md rounded-lg p-8">
            <h2 class="text-2xl font-bold mb-6 text-center text-indigo-600">Daftar Akun Baru</h2>
            
            <?php if ($message): ?>
                <div class="p-3 mb-4 rounded-md 
                    <?php echo ($message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Daftar Akun
                </button>
            </form>
            
            <p class="mt-6 text-center text-sm text-gray-600">
                Sudah punya akun? 
                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>