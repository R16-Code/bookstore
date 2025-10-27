<?php
session_start();
include '../config.php'; 

// **Guardrail Keamanan:** Cek apakah user sudah login dan role-nya adalah Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// --- A. Logika Menambahkan/Mengubah Kategori (Create/Update) ---
if (isset($_POST['submit_category'])) {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $category_id = $_POST['category_id'] ?? null;

    if ($category_id) {
        // Update Kategori
        $query = "UPDATE categories SET name = ? WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "si", $name, $category_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Kategori berhasil diperbarui!";
        } else {
            $message = "Gagal memperbarui kategori.";
        }
        mysqli_stmt_close($stmt);
    } else {
        // Tambah Kategori
        $query = "INSERT INTO categories (name) VALUES (?)";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "s", $name);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Kategori berhasil ditambahkan!";
        } else {
            $message = "Gagal menambahkan kategori. Mungkin sudah ada.";
        }
        mysqli_stmt_close($stmt);
    }
}

// --- B. Logika Menghapus Kategori (Delete) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Perhatian: Jika ada buku yang terhubung, ini akan gagal (RESTRICT)
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Kategori berhasil dihapus!";
    } else {
        $message = "Gagal menghapus kategori. Mungkin ada buku yang menggunakan kategori ini.";
    }
    mysqli_stmt_close($stmt);
    // Redirect untuk menghilangkan parameter GET dari URL
    header("Location: categories.php");
    exit();
}

// --- C. Logika Mengambil Data Kategori (Read) ---
$categories_result = mysqli_query($koneksi, "SELECT * FROM categories ORDER BY name ASC");
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

// --- D. Logika Mengisi Form untuk Edit ---
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $edit_result = mysqli_query($koneksi, "SELECT * FROM categories WHERE id = $id");
    $edit_category = mysqli_fetch_assoc($edit_result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-600 p-4 text-white shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Admin BookStore</h1>
            <div>
                <a href="index.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Dashboard</a>
                <a href="categories.php" class="bg-indigo-700 px-3 py-2 rounded">Kategori</a>
                <a href="books.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Data Buku</a>
                <a href="users.php" class="hover:bg-indigo-700 px-3 py-2 rounded">List User</a>
                <a href="orders.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Pesanan</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded ml-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold mb-8 text-gray-800">Kelola Kategori Buku</h2>

        <?php if ($message): ?>
            <div class="p-3 mb-4 rounded-md bg-green-100 text-green-700">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
            <h3 class="text-xl font-semibold mb-4"><?php echo $edit_category ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?></h3>
            <form method="POST" action="categories.php">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                    <input type="text" id="name" name="name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>">
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" name="submit_category"
                            class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <?php echo $edit_category ? 'Perbarui' : 'Simpan'; ?>
                    </button>
                    <?php if ($edit_category): ?>
                        <a href="categories.php" class="bg-gray-400 text-white py-2 px-4 rounded-md hover:bg-gray-500 self-end">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold mb-4">Daftar Kategori</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $category['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($category['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                    <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')"
                                       class="text-red-600 hover:text-red-900">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada data kategori.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>