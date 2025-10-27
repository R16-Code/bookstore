<?php
session_start();
include 'config.php'; 

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);
$username = '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

if ($is_logged_in) {
    // Ambil username untuk ditampilkan di navbar
    $user_query = mysqli_query($koneksi, "SELECT username FROM users WHERE id = '$user_id'");
    $username = mysqli_fetch_assoc($user_query)['username'];
}

$search_query = '';
$where_clause = '';

// Logika Pencarian Buku
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($koneksi, $_GET['search']);
    $where_clause = "WHERE b.title LIKE '%{$search_query}%' OR b.author LIKE '%{$search_query}%'";
}

// Logika Mengambil Data Buku
$query_books = "
    SELECT 
        b.id, b.title, b.author, b.price, b.stock, b.description, c.name AS category_name 
    FROM books b
    JOIN categories c ON b.category_id = c.id
    {$where_clause}
    ORDER BY b.title ASC";
$books_result = mysqli_query($koneksi, $query_books);
$books = mysqli_fetch_all($books_result, MYSQLI_ASSOC);

// Logika Add to Cart (jika form dikirim)
if ($is_logged_in && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $book_id = (int)$_POST['book_id'];
    $quantity = 1; // Default 1 per klik
    
    // Cek apakah item sudah ada di cart user
    $check_cart = mysqli_query($koneksi, "SELECT * FROM carts WHERE user_id = '$user_id' AND book_id = '$book_id'");
    
    if (mysqli_num_rows($check_cart) > 0) {
        // Jika sudah ada, update quantity
        $update_query = "UPDATE carts SET quantity = quantity + 1 WHERE user_id = ? AND book_id = ?";
        $stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $book_id);
    } else {
        // Jika belum ada, masukkan item baru
        $insert_query = "INSERT INTO carts (user_id, book_id, quantity) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $insert_query);
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $book_id, $quantity);
    }

    if (mysqli_stmt_execute($stmt)) {
        // Redirect ke halaman yang sama dengan pesan sukses (agar tidak terjadi resubmission)
        header("Location: index.php?status=success_cart" . ($search_query ? "&search={$search_query}" : ''));
        exit();
    } else {
        $error_message = "Gagal menambahkan ke keranjang: " . mysqli_error($koneksi);
    }
}

// Cek status dari URL (setelah redirect)
$status_message = '';
if (isset($_GET['status']) && $_GET['status'] == 'success_cart') {
    $status_message = "Buku berhasil ditambahkan ke keranjang!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore - Toko Buku Online</title>
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
        <h2 class="text-4xl font-extrabold mb-4 text-gray-900">Koleksi Buku Terbaik</h2>

        <div class="mb-6 flex justify-between items-center">
            <form method="GET" action="index.php" class="flex w-full max-w-lg">
                <input type="text" name="search" placeholder="Cari Judul atau Penulis..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-lg hover:bg-indigo-700">Cari</button>
            </form>
        </div>

        <?php if ($status_message): ?>
            <div class="p-3 mb-4 rounded-md bg-green-100 text-green-700"><?php echo $status_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="p-3 mb-4 rounded-md bg-red-100 text-red-700"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (count($books) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php foreach ($books as $book): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col justify-between">
                        <div class="h-48 bg-gray-200 flex items-center justify-center text-gray-500 font-bold">
                            ); ?>]
                        </div>
                        
                        <div class="p-5 flex flex-col flex-grow">
                            <span class="text-xs font-semibold text-indigo-600 uppercase mb-1"><?php echo htmlspecialchars($book['category_name']); ?></span>
                            <h3 class="text-xl font-bold mb-2 text-gray-900"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-sm text-gray-500 mb-3 italic">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                            
                            <p class="text-lg font-extrabold text-red-600 mt-auto">Rp. <?php echo number_format($book['price'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-gray-400 mt-1">Stok: <?php echo $book['stock'] > 0 ? $book['stock'] : 'Habis'; ?></p>

                            <?php if ($is_logged_in): ?>
                                <?php if ($book['stock'] > 0): ?>
                                    <form method="POST" action="index.php" class="mt-4">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <button type="submit" class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600 transition duration-150">
                                            + Keranjang
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="w-full bg-gray-400 text-white py-2 rounded-md mt-4 cursor-not-allowed" disabled>Stok Habis</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="w-full text-center bg-indigo-500 text-white py-2 rounded-md mt-4 hover:bg-indigo-600">Login untuk Beli</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white p-6 rounded-lg shadow-lg text-center text-gray-500">
                <?php if ($search_query): ?>
                    <p>Tidak ditemukan buku dengan kata kunci "<?php echo htmlspecialchars($search_query); ?>".</p>
                <?php else: ?>
                    <p>Belum ada buku yang terdaftar di toko ini.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>