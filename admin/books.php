<?php
session_start();
include '../config.php'; 

// **Guardrail Keamanan:** Cek apakah user sudah login dan role-nya adalah Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// --- A. Logika Menambahkan/Mengubah Buku (Create/Update) ---
if (isset($_POST['submit_book'])) {
    $title       = mysqli_real_escape_string($koneksi, $_POST['title']);
    $author      = mysqli_real_escape_string($koneksi, $_POST['author']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $description = mysqli_real_escape_string($koneksi, $_POST['description']);
    $book_id     = $_POST['book_id'] ?? null;

    if ($book_id) {
        // Update Buku
        $query = "UPDATE books SET title=?, author=?, category_id=?, price=?, stock=?, description=? WHERE id=?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssiidis", $title, $author, $category_id, $price, $stock, $description, $book_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Data buku berhasil diperbarui!";
        } else {
            $message = "Gagal memperbarui data buku: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt);
    } else {
        // Tambah Buku Baru
        $query = "INSERT INTO books (title, author, category_id, price, stock, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        // "ssiids" -> string, string, integer, double, integer, string
        mysqli_stmt_bind_param($stmt, "ssiids", $title, $author, $category_id, $price, $stock, $description);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Buku baru berhasil ditambahkan!";
        } else {
            $message = "Gagal menambahkan buku: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt);
    }
}

// --- B. Logika Menghapus Buku (Delete) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Hapus data buku
    $query = "DELETE FROM books WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Data buku berhasil dihapus!";
    } else {
        $message = "Gagal menghapus data buku.";
    }
    mysqli_stmt_close($stmt);

    header("Location: books.php");
    exit();
}

// --- C. Logika Mengambil Data Buku (Read) ---
// Join dengan tabel categories untuk menampilkan nama kategori
$query_books = "SELECT 
                    b.id, b.title, b.author, b.price, b.stock, c.name AS category_name 
                FROM books b
                JOIN categories c ON b.category_id = c.id
                ORDER BY b.title ASC";
$books_result = mysqli_query($koneksi, $query_books);
$books = mysqli_fetch_all($books_result, MYSQLI_ASSOC);

// --- D. Logika Mengambil Data Kategori untuk Dropdown Form ---
$categories_result = mysqli_query($koneksi, "SELECT id, name FROM categories ORDER BY name ASC");
$all_categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

// --- E. Logika Mengisi Form untuk Edit ---
$edit_book = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $edit_result = mysqli_query($koneksi, "SELECT * FROM books WHERE id = $id");
    $edit_book = mysqli_fetch_assoc($edit_result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Buku - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-600 p-4 text-white shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Admin BookStore</h1>
            <div>
                <a href="index.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Dashboard</a>
                <a href="categories.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Kategori</a>
                <a href="books.php" class="bg-indigo-700 px-3 py-2 rounded">Data Buku</a>
                <a href="users.php" class="hover:bg-indigo-700 px-3 py-2 rounded">List User</a>
                <a href="orders.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Pesanan</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded ml-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold mb-8 text-gray-800">Kelola Data Buku</h2>

        <?php if ($message): ?>
            <div class="p-3 mb-4 rounded-md bg-green-100 text-green-700">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
            <h3 class="text-xl font-semibold mb-4"><?php echo $edit_book ? 'Edit Data Buku: ' . $edit_book['title'] : 'Tambah Buku Baru'; ?></h3>
            <form method="POST" action="books.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if ($edit_book): ?>
                    <input type="hidden" name="book_id" value="<?php echo $edit_book['id']; ?>">
                <?php endif; ?>

                <div class="col-span-1">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Judul Buku</label>
                    <input type="text" id="title" name="title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                           value="<?php echo htmlspecialchars($edit_book['title'] ?? ''); ?>">
                </div>

                <div class="col-span-1">
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">Penulis</label>
                    <input type="text" id="author" name="author" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                           value="<?php echo htmlspecialchars($edit_book['author'] ?? ''); ?>">
                </div>

                <div class="col-span-1">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select id="category_id" name="category_id" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($edit_book && $edit_book['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-span-1">
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp.)</label>
                    <input type="number" step="0.01" id="price" name="price" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                           value="<?php echo htmlspecialchars($edit_book['price'] ?? ''); ?>">
                </div>
                
                <div class="col-span-1">
                    <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                    <input type="number" id="stock" name="stock" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                           value="<?php echo htmlspecialchars($edit_book['stock'] ?? ''); ?>">
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="3"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($edit_book['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-span-2 flex space-x-4 pt-4">
                    <button type="submit" name="submit_book"
                            class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <?php echo $edit_book ? 'Perbarui Buku' : 'Tambah Buku'; ?>
                    </button>
                    <?php if ($edit_book): ?>
                        <a href="books.php" class="bg-gray-400 text-white py-2 px-4 rounded-md hover:bg-gray-500 self-end">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg overflow-x-auto">
            <h3 class="text-xl font-semibold mb-4">Daftar Buku Tersedia</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penulis</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($books) > 0): ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="px-3 py-4 whitespace-nowrap"><?php echo $book['id']; ?></td>
                                <td class="px-3 py-4 whitespace-nowrap font-medium"><?php echo htmlspecialchars($book['title']); ?></td>
                                <td class="px-3 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['author']); ?></td>
                                <td class="px-3 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['category_name']); ?></td>
                                <td class="px-3 py-4 whitespace-nowrap">Rp. <?php echo number_format($book['price'], 0, ',', '.'); ?></td>
                                <td class="px-3 py-4 whitespace-nowrap text-center"><?php echo $book['stock']; ?></td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="books.php?action=edit&id=<?php echo $book['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                    <a href="books.php?action=delete&id=<?php echo $book['id']; ?>" 
                                       onclick="return confirm('Yakin hapus buku ini?')"
                                       class="text-red-600 hover:text-red-900">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada data buku.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>