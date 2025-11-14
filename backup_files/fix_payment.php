<?php
session_start();
// File ini untuk memperbaiki data pembayaran yang tidak memiliki atas_nama

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
    $status_message = "";
    $error_message = "";
    $updated_count = 0;
    
    // Jika ada request untuk memperbaiki data pembayaran
    if (isset($_POST['fix_payments'])) {
        // Default nama jika kosong
        $default_name = isset($_POST['default_name']) ? $_POST['default_name'] : 'Pelanggan';
        
        // Periksa dan perbaiki semua pembayaran yang tidak memiliki atas_nama
        $stmt = $conn->prepare("UPDATE pembayaran SET atas_nama = :default_name WHERE atas_nama IS NULL OR atas_nama = ''");
        $stmt->execute([':default_name' => $default_name]);
        $updated_count = $stmt->rowCount();
        
        if ($updated_count > 0) {
            $status_message = "Berhasil memperbaiki $updated_count data pembayaran.";
        } else {
            $status_message = "Tidak ada data pembayaran yang perlu diperbaiki.";
        }
    }
    
    // Dapatkan statistik data pembayaran
    $stmt = $conn->query("SELECT COUNT(*) FROM pembayaran");
    $total_payments = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM pembayaran WHERE atas_nama IS NULL OR atas_nama = ''");
    $empty_names = $stmt->fetchColumn();
    
    // Dapatkan 10 pembayaran terbaru
    $stmt = $conn->query("SELECT * FROM pembayaran ORDER BY created_at DESC LIMIT 10");
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Koneksi database gagal: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan Data Pembayaran - Cafe Ciels</title>
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
        .btn {
            display: inline-block;
            padding: 8px 12px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            border: none;
            cursor: pointer;
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
        input[type="text"] {
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Perbaikan Data Pembayaran</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($status_message)): ?>
            <div class="success">
                <strong>Status:</strong> <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Statistik Data Pembayaran</h2>
            <p>Total data pembayaran: <strong><?php echo $total_payments; ?></strong></p>
            <p>Data pembayaran tanpa nama pembeli: <strong><?php echo $empty_names; ?></strong></p>
            
            <?php if ($empty_names > 0): ?>
                <div class="warning">
                    <p>⚠️ Terdapat <?php echo $empty_names; ?> data pembayaran yang tidak memiliki nama pembeli.</p>
                </div>
                
                <h3>Perbaiki Data Pembayaran</h3>
                <form action="" method="post">
                    <label for="default_name">Nama Default untuk Data Kosong:</label>
                    <input type="text" id="default_name" name="default_name" value="Pelanggan" required>
                    
                    <input type="hidden" name="fix_payments" value="1">
                    <button type="submit" class="btn btn-success">Perbaiki Semua Data</button>
                </form>
            <?php else: ?>
                <div class="success">
                    <p>✅ Semua data pembayaran memiliki nama pembeli.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Data Pembayaran Terbaru</h2>
            
            <?php if (!empty($recent_payments)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Atas Nama</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['kode_transaksi']); ?></td>
                                <td>
                                    <?php 
                                    if (empty($payment['atas_nama'])) {
                                        echo '<span style="color: #dc3545;">Kosong</span>';
                                    } else {
                                        echo htmlspecialchars($payment['atas_nama']); 
                                    }
                                    ?>
                                </td>
                                <td>Rp <?php echo number_format($payment['total_bayar'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($payment['status_pembayaran']); ?></td>
                                <td><?php echo isset($payment['created_at']) ? date('d/m/Y H:i', strtotime($payment['created_at'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Tidak ada data pembayaran.</p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Tindakan</h2>
            <a href="fix_transaction.php" class="btn">Kembali ke Diagnosa Transaksi</a>
            <a href="user.php" class="btn" style="background-color: #6c757d;">Kembali ke Menu</a>
        </div>
    </div>
</body>
</html>
