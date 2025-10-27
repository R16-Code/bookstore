<?php
session_start();
include '../config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

$message_status = '';

// --- A. Logika Mengubah Status Pesan (Menjadi 'Read') ---
if (isset($_GET['action']) && $_GET['action'] == 'mark_read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "UPDATE messages SET status = 'Read' WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message_status = "Pesan berhasil ditandai sudah dibaca.";
    } else {
        $message_status = "Gagal mengubah status pesan: " . mysqli_error($koneksi);
    }
    mysqli_stmt_close($stmt);

    // Redirect untuk menghilangkan parameter GET dan menampilkan pesan status
    header("Location: messages.php?status=" . urlencode($message_status));
    exit();
}

// --- B. Logika Mengambil Data Pesan ---
$query_messages = "
    SELECT 
        m.id, m.email, m.subject, m.message, m.sent_at, m.status, u.username
    FROM messages m
    LEFT JOIN users u ON m.user_id = u.id
    ORDER BY m.sent_at DESC
";
$messages_result = mysqli_query($koneksi, $query_messages);
$messages = mysqli_fetch_all($messages_result, MYSQLI_ASSOC);

// Ambil pesan status dari URL setelah redirect
if (isset($_GET['status'])) {
    $message_status = htmlspecialchars($_GET['status']);
}

// Hitung pesan belum dibaca
$unread_count_query = mysqli_query($koneksi, "SELECT COUNT(id) AS total_unread FROM messages WHERE status = 'Unread'");
$unread_count = mysqli_fetch_assoc($unread_count_query)['total_unread'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kotak Masuk Admin</title>
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
                <a href="users.php" class="hover:bg-indigo-700 px-3 py-2 rounded">List User</a>
                <a href="orders.php" class="hover:bg-indigo-700 px-3 py-2 rounded">Transaksi</a>
                <a href="messages.php" class="bg-indigo-700 px-3 py-2 rounded">Pesan</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded ml-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold mb-8 text-gray-800">Kotak Masuk (Pesan User)</h2>

        <?php if ($message_status): ?>
            <div class="p-3 mb-4 rounded-md bg-green-100 text-green-700">
                <?php echo $message_status; ?>
            </div>
        <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-lg space-y-4">
            <p class="text-sm text-gray-600">Total Pesan Belum Dibaca: <span class="font-bold text-red-600"><?php echo $unread_count; ?></span></p>

            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <?php $is_unread = $msg['status'] == 'Unread'; ?>
                    <div class="p-4 border rounded-lg 
                                <?php echo $is_unread ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50 border-gray-200'; ?>">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-bold <?php echo $is_unread ? 'text-indigo-800' : 'text-gray-800'; ?>"><?php echo htmlspecialchars($msg['subject']); ?></h3>
                                <p class="text-sm text-gray-500">
                                    Dari: <span class="font-medium"><?php echo htmlspecialchars($msg['email']); ?></span>
                                    (<?php echo $msg['username'] ? htmlspecialchars($msg['username']) : 'Tamu/Non-User'; ?>)
                                </p>
                            </div>
                            <div class="text-right text-xs text-gray-500">
                                <?php echo date("d M Y H:i", strtotime($msg['sent_at'])); ?><br>
                                <?php if ($is_unread): ?>
                                    <a href="messages.php?action=mark_read&id=<?php echo $msg['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-800 font-bold mt-1 inline-block">
                                        Tandai Sudah Dibaca
                                    </a>
                                <?php else: ?>
                                    <span class="text-green-600 font-medium">Sudah Dibaca</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-3 p-3 bg-white rounded border border-gray-200 text-gray-700 whitespace-pre-wrap">
                            <?php echo htmlspecialchars($msg['message']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500">Tidak ada pesan di kotak masuk.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>