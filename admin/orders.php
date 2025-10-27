<?php
session_start();
include '../config.php'; 

// **Guardrail Keamanan:** Cek apakah user sudah login dan role-nya adalah Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// Logika Mengubah Status Pesanan
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = mysqli_real_escape_string($koneksi, $_POST['status']);

    $query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Status pesanan ID #{$order_id} berhasil diperbarui menjadi **{$new_status}**.";
    } else {
        $message = "Gagal memperbarui status pesanan: " . mysqli_error($koneksi);
    }
    mysqli_stmt_close($stmt);

    // Redirect untuk menghindari pengiriman form berulang
    header("Location: orders.php?message=" . urlencode($message));
    exit();
}

// Logika Mengambil Data Pesanan
// Ambil semua orders dan data user yang memesan
$query_orders = "
    SELECT 
        o.id, o.order_date, o.total_amount, o.status, o.payment_method, u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
";
$orders_result = mysqli_query($koneksi, $query_orders);
$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);

// Ambil pesan dari URL setelah redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Fungsi untuk mendapatkan detail item per pesanan (akan dipanggil di dalam tabel)
function get_order_details($koneksi, $order_id) {
    $query = "
        SELECT 
            od.quantity, od.price_at_order, b.title 
        FROM order_details od
        JOIN books b ON od.book_id = b.id
        WHERE od.order_id = ?
    ";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $details = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $details;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Pesanan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleDetails(orderId) {
            const row = document.getElementById('details-row-' + orderId);
            row.classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-600 p-4 text-white shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Admin BookStore</h1>
            <div>
                <a href="index.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Dashboard</a>
                <a href="categories.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Kategori</a>
                <a href="books.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Data Buku</a>
                <a href="users.php" class="hover:bg-indigo-700 px-3 py-2 rounded">List User</a>
                <a href="orders.php" class="bg-indigo-700 px-3 py-2 rounded">Transaksi</a>
                <a href="messages.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Pesan</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded ml-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold mb-8 text-gray-800">List Pesanan Buku</h2>

        <?php if ($message): ?>
            <div class="p-3 mb-4 rounded-md bg-green-100 text-green-700">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-lg overflow-x-auto">
            <h3 class="text-xl font-semibold mb-4">Total Pesanan: <?php echo count($orders); ?></h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Pesan</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDetails(<?php echo $order['id']; ?>)">
                                <td class="px-3 py-4 whitespace-nowrap font-medium text-indigo-600">#<?php echo $order['id']; ?></td>
                                <td class="px-3 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['username']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</td>
                                <td class="px-3 py-4 whitespace-nowrap"><?php echo date("d M Y H:i", strtotime($order['order_date'])); ?></td>
                                <td class="px-3 py-4 whitespace-nowrap font-semibold">Rp. <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                            if ($order['status'] == 'Completed') echo 'bg-green-100 text-green-800';
                                            else if ($order['status'] == 'Shipped') echo 'bg-blue-100 text-blue-800';
                                            else echo 'bg-yellow-100 text-yellow-800';
                                        ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="toggleDetails(<?php echo $order['id']; ?>)" class="text-indigo-600 hover:text-indigo-900">Detail</button>
                                </td>
                            </tr>
                            <tr id="details-row-<?php echo $order['id']; ?>" class="hidden bg-gray-50">
                                <td colspan="6" class="p-4">
                                    <div class="p-3 border rounded-lg bg-white shadow-sm">
                                        <p class="font-bold mb-2">Detail Pesanan #<?php echo $order['id']; ?></p>
                                        <ul class="text-sm space-y-1">
                                            <li>**Metode Pembayaran:** <?php echo $order['payment_method']; ?></li>
                                            <li class="font-semibold mt-2">Item:</li>
                                            <?php 
                                                $details = get_order_details($koneksi, $order['id']);
                                                foreach ($details as $item) {
                                                    echo "<li>- {$item['title']} ({$item['quantity']}x) @ Rp. ". number_format($item['price_at_order'], 0, ',', '.') ."</li>";
                                                }
                                            ?>
                                        </ul>
                                        
                                        <div class="mt-4 border-t pt-3">
                                            <form method="POST" action="orders.php" class="flex items-center space-x-2">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <label for="status-<?php echo $order['id']; ?>" class="text-sm font-medium">Ubah Status:</label>
                                                <select name="status" id="status-<?php echo $order['id']; ?>" class="border rounded-md p-1 text-sm">
                                                    <option value="Pending" <?php echo ($order['status'] == 'Pending' ? 'selected' : ''); ?>>Pending</option>
                                                    <option value="Paid at Delivery" <?php echo ($order['status'] == 'Paid at Delivery' ? 'selected' : ''); ?>>Paid at Delivery</option>
                                                    <option value="Shipped" <?php echo ($order['status'] == 'Shipped' ? 'selected' : ''); ?>>Shipped</option>
                                                    <option value="Completed" <?php echo ($order['status'] == 'Completed' ? 'selected' : ''); ?>>Completed</option>
                                                </select>
                                                <button type="submit" name="update_status" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600">Update</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada pesanan dari pengguna.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>