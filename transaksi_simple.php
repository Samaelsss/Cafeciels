<?php
session_start();
// Ambil kode transaksi dari parameter atau dari session
$kode_transaksi = isset($_GET['kode']) ? $_GET['kode'] : '';

// Jika tidak ada di parameter URL, coba ambil dari session
if (empty($kode_transaksi) && isset($_SESSION['last_transaction_code'])) {
    $kode_transaksi = $_SESSION['last_transaction_code'];
    error_log("Menggunakan kode transaksi dari session: $kode_transaksi");
}

// Koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

// Tambahkan debugging
error_log("Halaman transaksi_simple.php diakses dengan parameter: " . json_encode($_GET));
error_log("Kode transaksi yang digunakan: $kode_transaksi");

try {
    // Buat koneksi PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Array untuk menyimpan data transaksi
    $transactions = [];
    $payment_info = null;
    
    // Tampilkan error log untuk membantu debugging
    if (!empty($kode_transaksi)) {
        // Cek dahulu apakah kode transaksi ada di database
        $check = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi = :kode");
        $check->execute([':kode' => $kode_transaksi]);
        $count = $check->fetchColumn();
        error_log("Cek database: Ditemukan $count data di tabel transaksi untuk kode: $kode_transaksi");
        
        // Jika tidak ada di tabel transaksi, mungkin ada di tabel pembayaran
        if ($count == 0) {
            $check_payment = $conn->prepare("SELECT COUNT(*) FROM pembayaran WHERE kode_transaksi = :kode");
            $check_payment->execute([':kode' => $kode_transaksi]);
            $payment_count = $check_payment->fetchColumn();
            error_log("Cek database: Ditemukan $payment_count data di tabel pembayaran untuk kode: $kode_transaksi");
        }
    }
    
    // 1. Ambil data dari tabel transaksi
    if (!empty($kode_transaksi)) {
        $stmt = $conn->prepare("SELECT * FROM transaksi WHERE kode_transaksi = :kode ORDER BY created_at DESC");
        $stmt->execute([':kode' => $kode_transaksi]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Query transaksi: Ditemukan " . count($transactions) . " data");
        
        // 2. Ambil data pembayaran jika ada
        $payment_stmt = $conn->prepare("SELECT * FROM pembayaran WHERE kode_transaksi = :kode");
        $payment_stmt->execute([':kode' => $kode_transaksi]);
        $payment_info = $payment_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Query pembayaran: " . ($payment_info ? "Ditemukan" : "Tidak ditemukan"));
    } else {
        // Jika tidak ada kode transaksi, ambil semua transaksi
        $stmt = $conn->prepare("SELECT DISTINCT kode_transaksi FROM transaksi ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $all_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($all_codes as $code) {
            $stmt = $conn->prepare("SELECT * FROM transaksi WHERE kode_transaksi = :kode ORDER BY created_at DESC");
            $stmt->execute([':kode' => $code]);
            $trans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($trans)) {
                $transactions = array_merge($transactions, $trans);
            }
        }
    }
    
} catch(PDOException $e) {
    error_log("Error database: " . $e->getMessage());
    die("Koneksi database gagal: " . $e->getMessage());
}

// Hitung total transaksi
$total = 0;
if (!empty($transactions)) {
    foreach ($transactions as $transaction) {
        $total += $transaction['subtotal'];
    }
}

// Nama pembeli
$customer_name = !empty($payment_info) ? $payment_info['atas_nama'] : 
                ((!empty($transactions) && isset($transactions[0]['atas_nama'])) ? 
                $transactions[0]['atas_nama'] : 'Pelanggan');

// Format tanggal
$transaction_date = !empty($transactions) ? date('d M Y H:i', strtotime($transactions[0]['created_at'])) : '-';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - Cafe Ciels</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f9f9f9;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .transaction-info {
            margin-bottom: 20px;
        }
        .transaction-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .total {
            text-align: right;
            font-weight: bold;
            margin: 20px 0;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-btn:hover {
            background-color: #45a049;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #777;
        }
        .debug {
            background: #f0f0f0;
            padding: 15px;
            margin-top: 20px;
            border-left: 5px solid #ccc;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detail Transaksi</h1>
        
        <?php if (empty($transactions)): ?>
            <div class="no-data">
                <p>Tidak ada data transaksi yang ditemukan.</p>
                
                <?php if (!empty($kode_transaksi)): ?>
                    <div class="debug">
                        <h3>Informasi Debug:</h3>
                        <p>Kode Transaksi: <?= htmlspecialchars($kode_transaksi) ?></p>
                        <p>Tidak ditemukan data transaksi dengan kode tersebut.</p>
                        
                        <?php if (isset($_GET['debug']) || true): // Selalu tampilkan debug info ?>
                            <h4>Database Check:</h4>
                            <p>
                                <?php
                                try {
                                    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    
                                    // Cek semua tabel yang relevan
                                    echo "Checking transaksi table: ";
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi = :kode");
                                    $stmt->execute([':kode' => $kode_transaksi]);
                                    $count = $stmt->fetchColumn();
                                    echo "$count records found<br>";
                                    
                                    // Jika ada di tabel transaksi, tampilkan datanya
                                    if ($count > 0) {
                                        echo "Transaction entries:<br>";
                                        $detail_stmt = $conn->prepare("SELECT * FROM transaksi WHERE kode_transaksi = :kode");
                                        $detail_stmt->execute([':kode' => $kode_transaksi]);
                                        $entries = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($entries as $entry) {
                                            echo "- {$entry['nama_barang']} x {$entry['quantity']} = Rp " . number_format($entry['subtotal'], 0, ',', '.') . "<br>";
                                        }
                                    }
                                    
                                    echo "<br>Checking pembayaran table: ";
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM pembayaran WHERE kode_transaksi = :kode");
                                    $stmt->execute([':kode' => $kode_transaksi]);
                                    $count = $stmt->fetchColumn();
                                    echo "$count records found<br>";
                                    
                                    // Jika ada di tabel pembayaran, tampilkan datanya
                                    if ($count > 0) {
                                        $detail_stmt = $conn->prepare("SELECT * FROM pembayaran WHERE kode_transaksi = :kode");
                                        $detail_stmt->execute([':kode' => $kode_transaksi]);
                                        $payment = $detail_stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        echo "Payment details:<br>";
                                        echo "- Customer: {$payment['atas_nama']}<br>";
                                        echo "- Total: Rp " . number_format($payment['total_bayar'], 0, ',', '.') . "<br>";
                                        echo "- Status: {$payment['status_pembayaran']}<br>";
                                    }
                                    
                                } catch(PDOException $e) {
                                    echo "Database error: " . $e->getMessage();
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="transaction-info">
                <p><strong>Kode Transaksi:</strong> <?php echo htmlspecialchars($transactions[0]['kode_transaksi']); ?></p>
                <p><strong>Tanggal:</strong> <?php echo $transaction_date; ?></p>
                <p><strong>Pembeli:</strong> <?php echo htmlspecialchars($customer_name); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total">
                <?php if (!empty($payment_info) && !empty($payment_info['diskon_amount']) && $payment_info['diskon_amount'] > 0): ?>
                    <p>Subtotal: Rp <?php echo number_format($total, 0, ',', '.'); ?></p>
                    <p>Diskon: Rp <?php echo number_format($payment_info['diskon_amount'], 0, ',', '.'); ?></p>
                    <p>Total: Rp <?php echo number_format($total - $payment_info['diskon_amount'], 0, ',', '.'); ?></p>
                <?php else: ?>
                    <p>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($payment_info)): ?>
                <div class="payment-info">
                    <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($payment_info['metode_pembayaran']); ?></p>
                    <p><strong>Status Pembayaran:</strong> <?php echo htmlspecialchars($payment_info['status_pembayaran']); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="user.php" class="back-btn">Kembali ke Menu</a>
    </div>
</body>
</html>
