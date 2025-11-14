<?php
session_start();
// File ini untuk memperbaiki masalah pada database

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

// Buat koneksi PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Pesan status
    $status_messages = [];
    $error_messages = [];
    
    // Jika user klik tombol fix
    if (isset($_POST['fix_database'])) {
        
        // 1. Perbaiki kolom atas_nama di tabel pembayaran yang kosong
        $stmt = $conn->prepare("UPDATE pembayaran SET atas_nama = 'Pelanggan' WHERE atas_nama IS NULL OR atas_nama = ''");
        $stmt->execute();
        $count = $stmt->rowCount();
        $status_messages[] = "Perbaikan nama pembeli: $count data pembayaran diperbaiki";
        
        // 2. Periksa apakah ada kolom atau tabel yang perlu dibuat
        try {
            // Periksa apakah kolom atas_nama sudah ada di tabel transaksi
            $stmt = $conn->query("DESCRIBE transaksi");
            $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('atas_nama', $fields)) {
                // Tambahkan kolom atas_nama ke tabel transaksi
                $conn->exec("ALTER TABLE transaksi ADD COLUMN atas_nama VARCHAR(100) AFTER kode_customer");
                $status_messages[] = "Kolom 'atas_nama' berhasil ditambahkan ke tabel transaksi";
                
                // Update data transaksi dengan informasi atas_nama dari tabel pembayaran
                $conn->exec("
                    UPDATE transaksi t
                    JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                    SET t.atas_nama = p.atas_nama
                    WHERE t.atas_nama IS NULL OR t.atas_nama = ''
                ");
                $status_messages[] = "Data atas_nama pada tabel transaksi berhasil diperbarui";
            }
        } catch (PDOException $e) {
            $error_messages[] = "Error saat memeriksa struktur tabel: " . $e->getMessage();
        }
        
        // 3. Pastikan semua transaksi memiliki pembayaran
        try {
            $stmt = $conn->query("
                SELECT DISTINCT t.kode_transaksi 
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
                WHERE p.kode_transaksi IS NULL
            ");
            $missing_payments = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($missing_payments)) {
                $fixed_count = 0;
                foreach ($missing_payments as $kode) {
                    // Hitung total transaksi
                    $total_stmt = $conn->prepare("
                        SELECT SUM(subtotal) as total 
                        FROM transaksi 
                        WHERE kode_transaksi = :kode
                    ");
                    $total_stmt->execute([':kode' => $kode]);
                    $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Buat pembayaran baru
                    $payment_stmt = $conn->prepare("
                        INSERT INTO pembayaran 
                        (kode_transaksi, kode_customer, atas_nama, total_bayar, metode_pembayaran, status_pembayaran, catatan)
                        VALUES
                        (:kode, 'CUSTOMER', 'Pelanggan', :total, 'Tunai', 'Completed', 'Dibuat oleh fix_database.php')
                    ");
                    $payment_stmt->execute([
                        ':kode' => $kode,
                        ':total' => $total
                    ]);
                    $fixed_count++;
                }
                $status_messages[] = "Pembayaran dibuat untuk $fixed_count transaksi yang tidak memiliki data pembayaran";
            }
        } catch (PDOException $e) {
            $error_messages[] = "Error saat memeriksa transaksi tanpa pembayaran: " . $e->getMessage();
        }
    }
    
    // Dapatkan statistik data
    $stats = [];
    
    // Total transaksi
    $stmt = $conn->query("SELECT COUNT(DISTINCT kode_transaksi) FROM transaksi");
    $stats['total_transactions'] = $stmt->fetchColumn();
    
    // Total pembayaran
    $stmt = $conn->query("SELECT COUNT(DISTINCT kode_transaksi) FROM pembayaran");
    $stats['total_payments'] = $stmt->fetchColumn();
    
    // Pembayaran tanpa atas_nama
    $stmt = $conn->query("SELECT COUNT(*) FROM pembayaran WHERE atas_nama IS NULL OR atas_nama = ''");
    $stats['payments_without_name'] = $stmt->fetchColumn();
    
    // Transaksi tanpa pembayaran
    $stmt = $conn->query("
        SELECT COUNT(DISTINCT t.kode_transaksi) 
        FROM transaksi t
        LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
        WHERE p.kode_transaksi IS NULL
    ");
    $stats['transactions_without_payment'] = $stmt->fetchColumn();
    
    // Pembayaran tanpa transaksi
    $stmt = $conn->query("
        SELECT COUNT(DISTINCT p.kode_transaksi) 
        FROM pembayaran p
        LEFT JOIN transaksi t ON p.kode_transaksi = t.kode_transaksi
        WHERE t.kode_transaksi IS NULL
    ");
    $stats['payments_without_transaction'] = $stmt->fetchColumn();
    
    // Ambil 5 transaksi terbaru untuk ditampilkan
    $stmt = $conn->query("
        SELECT 
            t.kode_transaksi,
            COUNT(t.id) as total_items,
            SUM(t.subtotal) as total_amount,
            p.atas_nama,
            p.status_pembayaran,
            MAX(t.created_at) as transaction_date
        FROM transaksi t
        LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
        GROUP BY t.kode_transaksi
        ORDER BY transaction_date DESC
        LIMIT 5
    ");
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_messages[] = "Koneksi database gagal: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fixer - Cafe Ciels</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f9f9f9;
        }
        .container {
            max-width: 900px;
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
        .info {
            background-color: #e2f0fd;
            border-left: 5px solid #17a2b8;
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
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 200px;
            text-align: center;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 16px;
        }
        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Fixer - Cafe Ciels</h1>
        
        <?php if (!empty($error_messages)): ?>
            <?php foreach ($error_messages as $message): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($status_messages)): ?>
            <?php foreach ($status_messages as $message): ?>
                <div class="success">
                    <strong>✅</strong> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="section">
            <h2>Statistik Database</h2>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Transaksi</h3>
                    <div class="number"><?php echo $stats['total_transactions']; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Pembayaran</h3>
                    <div class="number"><?php echo $stats['total_payments']; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Pembayaran Tanpa Nama</h3>
                    <div class="number">
                        <?php echo $stats['payments_without_name']; ?>
                        <?php if ($stats['payments_without_name'] > 0): ?>
                            <span class="badge badge-warning">Perlu diperbaiki</span>
                        <?php else: ?>
                            <span class="badge badge-success">OK</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Transaksi Tanpa Pembayaran</h3>
                    <div class="number">
                        <?php echo $stats['transactions_without_payment']; ?>
                        <?php if ($stats['transactions_without_payment'] > 0): ?>
                            <span class="badge badge-warning">Perlu diperbaiki</span>
                        <?php else: ?>
                            <span class="badge badge-success">OK</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($stats['payments_without_name'] > 0 || $stats['transactions_without_payment'] > 0 || $stats['payments_without_transaction'] > 0): ?>
                <div class="warning">
                    <p>⚠️ Ditemukan beberapa masalah pada database yang perlu diperbaiki:</p>
                    <ul>
                        <?php if ($stats['payments_without_name'] > 0): ?>
                            <li>Ada <?php echo $stats['payments_without_name']; ?> pembayaran tanpa nama pembeli</li>
                        <?php endif; ?>
                        
                        <?php if ($stats['transactions_without_payment'] > 0): ?>
                            <li>Ada <?php echo $stats['transactions_without_payment']; ?> transaksi yang tidak memiliki data pembayaran</li>
                        <?php endif; ?>
                        
                        <?php if ($stats['payments_without_transaction'] > 0): ?>
                            <li>Ada <?php echo $stats['payments_without_transaction']; ?> pembayaran yang tidak memiliki data transaksi</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <form action="" method="post" style="margin-top: 15px;">
                    <input type="hidden" name="fix_database" value="1">
                    <button type="submit" class="btn btn-success">Perbaiki Semua Masalah</button>
                </form>
            <?php else: ?>
                <div class="success">
                    <p>✅ Database Anda dalam kondisi baik! Tidak ada masalah yang ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Transaksi Terbaru</h2>
            
            <?php if (!empty($recent_transactions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Pembeli</th>
                            <th>Jumlah Item</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['kode_transaksi']); ?></td>
                                <td>
                                    <?php 
                                    if (empty($transaction['atas_nama'])) {
                                        echo '<span class="badge badge-warning">Tidak ada</span>';
                                    } else {
                                        echo htmlspecialchars($transaction['atas_nama']); 
                                    }
                                    ?>
                                </td>
                                <td><?php echo $transaction['total_items']; ?> item</td>
                                <td>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    if (empty($transaction['status_pembayaran'])) {
                                        echo '<span class="badge badge-warning">Tidak ada data</span>';
                                    } else {
                                        echo htmlspecialchars($transaction['status_pembayaran']); 
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                <td>
                                    <a href="transaksi_simple.php?kode=<?php echo urlencode($transaction['kode_transaksi']); ?>" class="badge badge-success">Lihat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Tidak ada transaksi terbaru.</p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Perbaikan Manual Database</h2>
            
            <div class="info">
                <p>Jika Anda masih mengalami masalah setelah perbaikan otomatis, Anda dapat menjalankan perbaikan database secara manual melalui phpMyAdmin.</p>
            </div>
            
            <h3>SQL Query untuk Perbaikan:</h3>
            <pre style="background: #f8f8f8; padding: 15px; overflow: auto; border-radius: 4px;">
-- Perbaiki nama pembeli yang kosong
UPDATE pembayaran SET atas_nama = 'Pelanggan' WHERE atas_nama IS NULL OR atas_nama = '';

-- Tambahkan kolom atas_nama ke tabel transaksi jika belum ada
ALTER TABLE transaksi ADD COLUMN IF NOT EXISTS atas_nama VARCHAR(100) AFTER kode_customer;

-- Update data transaksi dengan informasi dari tabel pembayaran
UPDATE transaksi t 
JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi 
SET t.atas_nama = p.atas_nama 
WHERE t.atas_nama IS NULL OR t.atas_nama = '';
            </pre>
        </div>
        
        <div class="section">
            <h2>Tindakan</h2>
            <a href="fix_transaction.php" class="btn">Uji Transaksi</a>
            <a href="transaksi_simple.php" class="btn">Lihat Transaksi Terbaru</a>
            <a href="user.php" class="btn" style="background-color: #6c757d;">Kembali ke Menu</a>
        </div>
    </div>
</body>
</html>
