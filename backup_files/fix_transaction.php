<?php
session_start();
// File ini untuk memperbaiki dan mendiagnosis masalah transaksi

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

// Fungsi untuk logging
function logInfo($message) {
    error_log("[FIX_TRANSACTION] " . $message);
}

try {
    // Buat koneksi PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cek apakah ada kode transaksi dari parameter
    $kode_transaksi = isset($_GET['kode']) ? trim($_GET['kode']) : '';
    
    // Jika tidak ada di parameter, coba ambil dari session
    if (empty($kode_transaksi) && isset($_SESSION['last_transaction_code'])) {
        $kode_transaksi = $_SESSION['last_transaction_code'];
        logInfo("Menggunakan kode transaksi dari session: $kode_transaksi");
    }
    
    // Jika masih kosong, ambil transaksi terakhir
    if (empty($kode_transaksi)) {
        $stmt = $conn->query("SELECT kode_transaksi FROM transaksi ORDER BY created_at DESC LIMIT 1");
        if ($latest = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $kode_transaksi = $latest['kode_transaksi'];
            logInfo("Menggunakan kode transaksi terbaru dari database: $kode_transaksi");
        }
    }
    
    // Hasil diagnosis
    $diagnosis = [];
    $transaction_found = false;
    
    if (!empty($kode_transaksi)) {
        // Periksa di tabel transaksi
        $stmt = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi = :kode");
        $stmt->execute([':kode' => $kode_transaksi]);
        $trans_count = $stmt->fetchColumn();
        $diagnosis['transaksi_count'] = $trans_count;
        
        if ($trans_count > 0) {
            $transaction_found = true;
            logInfo("Ditemukan $trans_count data di tabel transaksi untuk kode: $kode_transaksi");
            
            // Ambil detail transaksi
            $stmt = $conn->prepare("SELECT * FROM transaksi WHERE kode_transaksi = :kode");
            $stmt->execute([':kode' => $kode_transaksi]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $diagnosis['transaksi_detail'] = $transactions;
        }
        
        // Periksa di tabel pembayaran
        $stmt = $conn->prepare("SELECT COUNT(*) FROM pembayaran WHERE kode_transaksi = :kode");
        $stmt->execute([':kode' => $kode_transaksi]);
        $payment_count = $stmt->fetchColumn();
        $diagnosis['pembayaran_count'] = $payment_count;
        
        if ($payment_count > 0) {
            logInfo("Ditemukan $payment_count data di tabel pembayaran untuk kode: $kode_transaksi");
            
            // Ambil detail pembayaran
            $stmt = $conn->prepare("SELECT * FROM pembayaran WHERE kode_transaksi = :kode");
            $stmt->execute([':kode' => $kode_transaksi]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            $diagnosis['pembayaran_detail'] = $payment;
        }
    } else {
        logInfo("Tidak ada kode transaksi yang diberikan");
    }
    
} catch (PDOException $e) {
    logInfo("Database error: " . $e->getMessage());
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan Transaksi - Cafe Ciels</title>
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
        h1, h2, h3 {
            color: #333;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-left: 5px solid #3498db;
        }
        .success {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        .error {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-btn {
            display: inline-block;
            padding: 8px 12px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .action-btn:hover {
            background-color: #2980b9;
        }
        pre {
            background: #f8f8f8;
            padding: 10px;
            overflow: auto;
            font-family: monospace;
            font-size: 14px;
            border-left: 3px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Perbaikan & Diagnosis Transaksi</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Informasi Kode Transaksi</h2>
            <?php if (!empty($kode_transaksi)): ?>
                <p><strong>Kode Transaksi Saat Ini:</strong> <?php echo htmlspecialchars($kode_transaksi); ?></p>
                
                <?php if ($transaction_found): ?>
                    <div class="success">
                        <p>✅ Transaksi ditemukan di database</p>
                    </div>
                <?php else: ?>
                    <div class="warning">
                        <p>⚠️ Transaksi tidak ditemukan di database</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="warning">
                    <p>⚠️ Tidak ada kode transaksi yang diberikan</p>
                </div>
            <?php endif; ?>
            
            <h3>Cari Transaksi</h3>
            <form action="" method="get">
                <input type="text" name="kode" placeholder="Masukkan kode transaksi..." style="padding: 8px; width: 300px;">
                <button type="submit" style="padding: 8px 12px; background: #4CAF50; color: white; border: none; cursor: pointer;">Cari</button>
            </form>
        </div>
        
        <?php if (!empty($kode_transaksi)): ?>
            <div class="section">
                <h2>Hasil Diagnosis</h2>
                
                <h3>Data di Tabel Transaksi</h3>
                <?php if ($diagnosis['transaksi_count'] > 0): ?>
                    <p>Ditemukan <?php echo $diagnosis['transaksi_count']; ?> data di tabel transaksi.</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach ($diagnosis['transaksi_detail'] as $item): 
                                $total += $item['subtotal'];
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                                <td><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="warning">
                        <p>Tidak ada data di tabel transaksi untuk kode ini.</p>
                    </div>
                <?php endif; ?>
                
                <h3>Data di Tabel Pembayaran</h3>
                <?php if ($diagnosis['pembayaran_count'] > 0): ?>
                    <p>Ditemukan data pembayaran.</p>
                    
                    <table>
                        <tr>
                            <th>Atas Nama</th>
                            <td><?php echo htmlspecialchars($diagnosis['pembayaran_detail']['atas_nama']); ?></td>
                        </tr>
                        <tr>
                            <th>Total Bayar</th>
                            <td>Rp <?php echo number_format($diagnosis['pembayaran_detail']['total_bayar'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <th>Metode Pembayaran</th>
                            <td><?php echo htmlspecialchars($diagnosis['pembayaran_detail']['metode_pembayaran']); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?php echo htmlspecialchars($diagnosis['pembayaran_detail']['status_pembayaran']); ?></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="warning">
                        <p>Tidak ada data di tabel pembayaran untuk kode ini.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Tindakan</h2>
                
                <a href="transaksi_simple.php?kode=<?php echo urlencode($kode_transaksi); ?>&t=<?php echo time(); ?>" class="action-btn">Lihat di Halaman Transaksi</a>
                
                <?php if ($transaction_found): ?>
                    <a href="print_transaksi.php?kode=<?php echo urlencode($kode_transaksi); ?>" class="action-btn">Cetak Struk</a>
                <?php endif; ?>
                
                <a href="user.php" class="action-btn" style="background-color: #6c757d;">Kembali ke Menu</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
