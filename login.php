<?php
session_start();
include 'config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'Admin') {
        header("Location: admin/index.php"); 
    } else {
        header("Location: index.php"); 
    }
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = $_POST['username_email'];
    $password = $_POST['password'];
    
    // Query mencari user berdasarkan username ATAU email
    $query = "SELECT id, password, role FROM users WHERE username = ? OR email = ?";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $username_email, $username_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Verifikasi Password 
        if (password_verify($password, $row['password'])) {
            // Login Berhasil! Buat Session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role']    = $row['role'];
            
            // Arahkan sesuai Role
            if ($_SESSION['role'] == 'Admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $message = "Password salah.";
        }
    } else {
        $message = "Username atau Email tidak ditemukan.";
    }
    mysqli_stmt_close($stmt);
    
    if ($message) {
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login User - BookStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-md rounded-lg p-8">
            <h2 class="text-2xl font-bold mb-6 text-center text-indigo-600">Login ke BookStore</h2>
            
            <?php if ($message): ?>
                <div class="p-3 mb-4 rounded-md bg-red-100 text-red-700">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-4">
                    <label for="username_email" class="block text-sm font-medium text-gray-700 mb-1">Username atau Email</label>
                    <input type="text" id="username_email" name="username_email" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?php echo htmlspecialchars($_POST['username_email'] ?? ''); ?>">
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Login
                </button>
            </form>
            
            <p class="mt-6 text-center text-sm text-gray-600">
                Belum punya akun? 
                <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>