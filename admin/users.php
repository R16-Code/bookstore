<?php
session_start();
include '../config.php'; 

// **Guardrail Keamanan:** Cek apakah user sudah login dan role-nya adalah Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// Logika Menghapus User (Opsional, tapi penting untuk manajemen)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Pastikan Admin tidak menghapus akun sendiri
    if ($id == $_SESSION['user_id']) {
        $message = "Anda tidak dapat menghapus akun Anda sendiri.";
    } else {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Pengguna berhasil dihapus!";
        } else {
            $message = "Gagal menghapus pengguna.";
        }
        mysqli_stmt_close($stmt);
        
        // Redirect setelah aksi delete
        header("Location: users.php?message=" . urlencode($message));
        exit();
    }
}

// Logika Mengambil Data User
$query_users = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($koneksi, $query_users);
$users = mysqli_fetch_all($users_result, MYSQLI_ASSOC);

// Ambil pesan dari URL setelah redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List User - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-600 p-4 text-white shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Admin BookStore</h1>
            <div>
                <a href="index.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Dashboard</a>
                <a href="categories.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Kategori</a>
                <a href="books.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Data Buku</a>
                <a href="users.php" class="bg-indigo-700 px-3 py-2 rounded">List User</a>
                <a href="orders.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Pesanan</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded ml-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold mb-8 text-gray-800">List User Terdaftar</h2>

        <?php if ($message): ?>
            <div class="p-3 mb-4 rounded-md bg-green-100 text-green-700">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-lg overflow-x-auto">
            <h3 class="text-xl font-semibold mb-4">Total User: <?php echo count($users); ?></h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Daftar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo ($user['role'] == 'Admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date("d M Y", strtotime($user['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                           onclick="return confirm('Yakin hapus user <?php echo $user['username']; ?>?')"
                                           class="text-red-600 hover:text-red-900">Hapus</a>
                                    <?php else: ?>
                                        <span class="text-gray-400">Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada user terdaftar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>