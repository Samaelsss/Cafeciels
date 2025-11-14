<?php
// File untuk memperbaiki nama pembeli di database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

$messages = [];
$errors = [];

try {
    // Buat koneksi
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Periksa apakah kolom atas_nama ada di tabel pembayaran
    $column_exists = false;
    try {
        $stmt = $conn->query("DESCRIBE pembayaran");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $column_exists = in_array('atas_nama', $columns);
    } catch(PDOException $e) {
        $errors[] = "Error memeriksa kolom: " . $e->getMessage();
    }
    
    // Jika kolom tidak ada, tambahkan
    if (!$column_exists) {
        try {
            $conn->exec("ALTER TABLE pembayaran ADD COLUMN atas_nama VARCHAR(100) AFTER kode_customer");
            $messages[] = "Kolom 'atas_nama' berhasil ditambahkan ke tabel pembayaran";
            
            // Isi dengan nilai default
            $conn->exec("UPDATE pembayaran SET atas_nama = 'Pelanggan'");
            $messages[] = "Semua data pembayaran sekarang memiliki nama pembeli default 'Pelanggan'";
        } catch(PDOException $e) {
            $errors[] = "Error menambahkan kolom: " . $e->getMessage();
        }
    } else {
        $messages[] = "Kolom 'atas_nama' sudah ada di tabel pembayaran";
        
        // Periksa apakah ada nilai null atau kosong
        $stmt = $conn->query("SELECT COUNT(*) FROM pembayaran WHERE atas_nama IS NULL OR atas_nama = ''");
        $empty_count = $stmt->fetchColumn();
        
        if ($empty_count > 0) {
            $conn->exec("UPDATE pembayaran SET atas_nama = 'Pelanggan' WHERE atas_nama IS NULL OR atas_nama = ''");
            $messages[] = "Berhasil mengisi $empty_count nama pembeli yang kosong dengan nilai default 'Pelanggan'";
        } else {
            $messages[] = "Semua data pembayaran sudah memiliki nama pembeli";
        }
    }
    
    // Periksa data pembayaran terbaru
    $stmt = $conn->query("SELECT * FROM pembayaran ORDER BY id DESC LIMIT 5");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $errors[] = "Koneksi database gagal: " . $e->getMessage();
}

// Fungsi untuk memperbaiki add_to_cart.php
function fix_checkout_code() {
    $file_path = 'add_to_cart.php';
    $fixed = false;
    $msg = "";
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Cari dan ganti kode untuk memastikan atas_nama selalu terisi
        $search = "':kode_transaksi' => \$kode_transaksi,
            ':atas_nama' => \$atas_nama,
            ':total_bayar' => \$total";
        
        $replace = "':kode_transaksi' => \$kode_transaksi,
            ':atas_nama' => !empty(\$atas_nama) ? \$atas_nama : 'Pelanggan',
            ':total_bayar' => \$total";
        
        if (strpos($content, $search) !== false) {
            $new_content = str_replace($search, $replace, $content);
            file_put_contents($file_path, $new_content);
            $fixed = true;
            $msg = "File add_to_cart.php berhasil diperbaiki untuk memastikan nama pembeli selalu terisi";
        } else {
            $msg = "Tidak perlu memperbaiki file add_to_cart.php";
        }
    } else {
        $msg = "File add_to_cart.php tidak ditemukan";
    }
    
    return ['fixed' => $fixed, 'message' => $msg];
}

// Perbaiki kode checkout jika ada request
$checkout_fixed = false;
$checkout_message = "";

if (isset($_POST['fix_checkout'])) {
    $result = fix_checkout_code();
    $checkout_fixed = $result['fixed'];
    $checkout_message = $result['message'];
    
    if ($checkout_fixed) {
        $messages[] = $checkout_message;
    } else {
        $errors[] = $checkout_message;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan Nama Pembeli - Cafe Ciels</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Perbaikan Nama Pembeli</h1>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="success">
                    <strong>✅</strong> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="section">
            <h2>Status Database</h2>
            <?php if (isset($column_exists)): ?>
                <?php if ($column_exists): ?>
                    <div class="success">
                        <p>✅ Kolom 'atas_nama' sudah ada di tabel pembayaran</p>
                    </div>
                <?php else: ?>
                    <div class="error">
                        <p>❌ Kolom 'atas_nama' tidak ditemukan di tabel pembayaran</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <h3>Pembayaran Terbaru</h3>
            <?php if (isset($payments) && !empty($payments)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Nama Pembeli</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['kode_transaksi']); ?></td>
                                <td>
                                    <?php if (isset($payment['atas_nama'])): ?>
                                        <?php echo htmlspecialchars($payment['atas_nama']); ?>
                                    <?php else: ?>
                                        <span style="color: red">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td>Rp <?php echo number_format($payment['total_bayar'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($payment['status_pembayaran']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Tidak ada data pembayaran.</p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Perbaikan Kode Checkout</h2>
            
            <p>Selain memperbaiki database, kita perlu memastikan kode checkout juga selalu menyimpan nama pembeli.</p>
            
            <?php if (isset($checkout_fixed)): ?>
                <?php if ($checkout_fixed): ?>
                    <div class="success">
                        <p>✅ <?php echo htmlspecialchars($checkout_message); ?></p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($checkout_message)): ?>
                        <div class="error">
                            <p>❌ <?php echo htmlspecialchars($checkout_message); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <input type="hidden" name="fix_checkout" value="1">
                        <button type="submit" class="btn btn-success">Perbaiki Kode Checkout</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
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
