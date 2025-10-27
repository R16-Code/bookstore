<?php
session_start();
include 'config.php'; 

// **Guardrail Keamanan:** User harus login untuk mengakses keranjang
$is_logged_in = isset($_SESSION['user_id']);
$username = '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$message = '';
$message_type = '';

if ($is_logged_in) {
    // Ambil username untuk ditampilkan di navbar
    $user_query = mysqli_query($koneksi, "SELECT username FROM users WHERE id = '$user_id'");
    $username = mysqli_fetch_assoc($user_query)['username'];
}

// --- A. Logika Update Kuantitas ---
if (isset($_POST['update_quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $update_query = "UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = "Kuantitas berhasil diperbarui.";
        $message_type = 'success';
    } else {
        $message = "Kuantitas harus lebih dari 0.";
        $message_type = 'error';
    }
}

// --- B. Logika Hapus Item ---
if (isset($_POST['remove_item'])) {
    $cart_id = (int)$_POST['cart_id'];

    $delete_query = "DELETE FROM carts WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($koneksi, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $message = "Item berhasil dihapus dari keranjang.";
    $message_type = 'success';
}

// --- C. Logika Checkout (Payment at Delivery) ---
if (isset($_POST['checkout'])) {
    // 1. Ambil semua item dari keranjang user
    $cart_items_result = mysqli_query($koneksi, "
        SELECT c.book_id, c.quantity, b.price, b.stock 
        FROM carts c
        JOIN books b ON c.book_id = b.id
        WHERE c.user_id = '$user_id'");
    $cart_items = mysqli_fetch_all($cart_items_result, MYSQLI_ASSOC);

    if (empty($cart_items)) {
        $message = "Keranjang Anda kosong!";
        $message_type = 'error';
    } else {
        $total_amount = 0;
        $all_available = true;
        
        // 2. Cek stok dan hitung total
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock']) {
                $all_available = false;
                $message = "Stok untuk beberapa buku tidak mencukupi!";
                $message_type = 'error';
                break;
            }
            $total_amount += $item['quantity'] * $item['price'];
        }

        if ($all_available) {
            mysqli_autocommit($koneksi, FALSE); // Mulai transaksi
            $success = true;

            // 3. Masukkan ke tabel ORDERS
            $order_date = date('Y-m-d H:i:s');
            // Status: Paid at Delivery (sesuai permintaan soal)
            $order_status = 'Paid at Delivery'; 
            $payment_method = 'Payment at Delivery'; 

            $query_order = "INSERT INTO orders (user_id, order_date, total_amount, status, payment_method) VALUES (?, ?, ?, ?, ?)";
            $stmt_order = mysqli_prepare($koneksi, $query_order);
            mysqli_stmt_bind_param($stmt_order, "isdss", $user_id, $order_date, $total_amount, $order_status, $payment_method);
            
            if (mysqli_stmt_execute($stmt_order)) {
                $order_id = mysqli_insert_id($koneksi);

                // 4. Masukkan ke tabel ORDER_DETAILS dan update stok
                foreach ($cart_items as $item) {
                    $book_id = $item['book_id'];
                    $quantity = $item['quantity'];
                    $price = $item['price'];

                    // Insert detail
                    $query_detail = "INSERT INTO order_details (order_id, book_id, quantity, price_at_order) VALUES (?, ?, ?, ?)";
                    $stmt_detail = mysqli_prepare($koneksi, $query_detail);
                    mysqli_stmt_bind_param($stmt_detail, "iiid", $order_id, $book_id, $quantity, $price);
                    
                    if (!mysqli_stmt_execute($stmt_detail)) {
                        $success = false; break;
                    }

                    // Update stok
                    $query_stock = "UPDATE books SET stock = stock - ? WHERE id = ?";
                    $stmt_stock = mysqli_prepare($koneksi, $query_stock);
                    mysqli_stmt_bind_param($stmt_stock, "ii", $quantity, $book_id);
                    if (!mysqli_stmt_execute($stmt_stock)) {
                        $success = false; break;
                    }
                }

                // 5. Hapus isi keranjang (carts)
                $query_clear_cart = "DELETE FROM carts WHERE user_id = ?";
                $stmt_clear = mysqli_prepare($koneksi, $query_clear_cart);
                mysqli_stmt_bind_param($stmt_clear, "i", $user_id);
                if (!mysqli_stmt_execute($stmt_clear)) {
                    $success = false;
                }
            } else {
                $success = false;
            }

            // 6. Selesaikan transaksi
            if ($success) {
                mysqli_commit($koneksi);
                $message = "Checkout berhasil! Pesanan Anda (#{$order_id}) akan segera diproses dengan metode Payment at Delivery.";
                $message_type = 'success';
                // Redirect untuk menghindari form resubmission
                header("Location: cart.php?status=checkout_success&id={$order_id}");
                exit();
            } else {
                mysqli_rollback($koneksi);
                $message = "Checkout gagal karena kesalahan sistem. Coba lagi.";
                $message_type = 'error';
            }
        }
    }
}
// Ambil pesan dari URL setelah checkout
if (isset($_GET['status']) && $_GET['status'] == 'checkout_success' && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $message = "Checkout berhasil! Pesanan Anda (#{$order_id}) akan segera diproses dengan metode Payment at Delivery.";
    $message_type = 'success';
}


// --- D. Logika Menampilkan Isi Keranjang (Read) ---
$query_cart = "
    SELECT 
        c.id AS cart_id, c.quantity, b.id AS book_id, b.title, b.author, b.price, b.stock 
    FROM carts c
    JOIN books b ON c.book_id = b.id
    WHERE c.user_id = '$user_id'
";
$cart_result = mysqli_query($koneksi, $query_cart);
$cart_items = mysqli_fetch_all($cart_result, MYSQLI_ASSOC);

$total_cart_amount = 0;
foreach ($cart_items as $item) {
    $total_cart_amount += $item['quantity'] * $item['price'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - BookStore</title>
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
        <h2 class="text-3xl font-semibold mb-6 text-gray-800">Keranjang Belanja Anda</h2>

        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-md 
                <?php echo ($message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="bg-white p-4 rounded-lg shadow-md flex items-center justify-between">
                            <div class="flex-grow">
                                <h4 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p class="text-sm text-gray-500 italic">Oleh: <?php echo htmlspecialchars($item['author']); ?></p>
                                <p class="text-md font-semibold text-red-600">Rp. <?php echo number_format($item['price'], 0, ',', '.'); ?> / pcs</p>
                                <?php if ($item['quantity'] > $item['stock']): ?>
                                    <p class="text-sm text-red-500 font-bold mt-1">Stok tidak cukup! (Maks: <?php echo $item['stock']; ?>)</p>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center space-x-4 ml-4">
                                <form method="POST" action="cart.php" class="flex items-center space-x-2">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <input type="number" name="quantity" min="1" max="<?php echo $item['stock'] > 0 ? $item['stock'] : 1; ?>" required 
                                           value="<?php echo $item['quantity']; ?>"
                                           class="w-16 text-center border border-gray-300 rounded-md py-1">
                                    <button type="submit" name="update_quantity" class="text-indigo-600 hover:text-indigo-800 text-sm">Update</button>
                                </form>
                                
                                <form method="POST" action="cart.php" onsubmit="return confirm('Hapus item ini?')">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" name="remove_item" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <a href="index.php" class="inline-block text-indigo-600 hover:text-indigo-800 mt-4 text-sm font-medium">
                        ← Lanjut Belanja
                    </a>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-lg h-fit">
                    <h3 class="text-xl font-bold mb-4">Ringkasan Pesanan</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Subtotal Item:</span>
                            <span class="font-medium">Rp. <?php echo number_format($total_cart_amount, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="flex justify-between font-bold text-xl text-gray-900 pt-2">
                            <span>Total Tagihan:</span>
                            <span class="text-indigo-600">Rp. <?php echo number_format($total_cart_amount, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-6 border-t pt-4">
                        <p class="font-semibold text-gray-700 mb-2">Metode Pembayaran:</p>
                        <p class="bg-yellow-100 text-yellow-800 p-2 rounded-md font-medium text-sm">
                            Payment at Delivery (Bayar di Tempat)
                        </p>
                    </div>

                    <form method="POST" action="cart.php" class="mt-6">
                        <input type="hidden" name="checkout" value="1">
                        <button type="submit" 
                                class="w-full bg-green-600 text-white py-3 rounded-lg text-lg font-bold hover:bg-green-700 transition duration-150"
                                onclick="return confirm('Lanjutkan proses Checkout dengan metode Payment at Delivery?')">
                            Lakukan Checkout
                        </button>
                    </form>
                </div>

            </div>
        <?php else: ?>
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <p class="text-gray-500 text-lg mb-4">Keranjang belanja Anda kosong. Mulai belanja sekarang!</p>
                <a href="index.php" class="inline-block bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Lihat Buku</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>