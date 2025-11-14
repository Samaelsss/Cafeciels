<?php
// File debugging untuk memeriksa data transaksi
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

echo "<h1>Debug Data Transaksi</h1>";

try {
    // Buat koneksi
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Cek total data transaksi
    $stmt = $conn->query("SELECT COUNT(*) as total FROM transaksi");
    $totalTransaksi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total data di tabel transaksi: <strong>{$totalTransaksi}</strong></p>";
    
    // 2. Cek kode transaksi unik
    $stmt = $conn->query("SELECT DISTINCT kode_transaksi FROM transaksi");
    $kodeTransaksi = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Jumlah kode transaksi unik: <strong>" . count($kodeTransaksi) . "</strong></p>";
    
    if (count($kodeTransaksi) > 0) {
        echo "<p>Daftar kode transaksi yang tersedia:</p>";
        echo "<ul>";
        foreach ($kodeTransaksi as $kode) {
            echo "<li><a href='lihat_transaksi.php?kode=" . urlencode($kode) . "'>{$kode}</a></li>";
        }
        echo "</ul>";
    }
    
    // 3. Periksa detail beberapa data transaksi terakhir
    echo "<h2>Data Transaksi Terakhir</h2>";
    $stmt = $conn->query("SELECT * FROM transaksi ORDER BY created_at DESC LIMIT 5");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recentTransactions) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Kode Transaksi</th><th>Kode Customer</th><th>Nama Item</th><th>Quantity</th><th>Harga</th><th>Subtotal</th><th>Tanggal</th></tr>";
        
        foreach ($recentTransactions as $trans) {
            echo "<tr>";
            echo "<td>{$trans['id']}</td>";
            echo "<td>{$trans['kode_transaksi']}</td>";
            echo "<td>{$trans['kode_customer']}</td>";
            echo "<td>{$trans['nama_barang']}</td>";
            echo "<td>{$trans['quantity']}</td>";
            echo "<td>{$trans['harga']}</td>";
            echo "<td>{$trans['subtotal']}</td>";
            echo "<td>{$trans['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Tidak ada data transaksi terakhir</p>";
    }
    
    // 4. Periksa query getAllTransactions
    echo "<h2>Test Query getAllTransactions</h2>";
    
    // Cek apakah tabel diskon ada
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = :dbname 
        AND table_name = 'diskon'
    ");
    $stmt->execute([':dbname' => $dbname]);
    $diskonTableExists = (bool)$stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($diskonTableExists) {
        // Jika tabel diskon ada
        $stmt = $conn->prepare("
            SELECT t.*, p.kode_diskon, p.diskon_amount, d.nama_diskon, d.persentase_diskon, t.atas_nama, p.atas_nama as pembeli 
            FROM transaksi t
            LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
            LEFT JOIN diskon d ON p.kode_diskon = d.kode_diskon
            ORDER BY t.created_at DESC
            LIMIT 3
        ");
    } else {
        // Jika tabel diskon tidak ada
        $stmt = $conn->prepare("
            SELECT t.*, p.kode_diskon, p.diskon_amount, NULL as nama_diskon, 0 as persentase_diskon, t.atas_nama, p.atas_nama as pembeli 
            FROM transaksi t
            LEFT JOIN pembayaran p ON t.kode_transaksi = p.kode_transaksi
            ORDER BY t.created_at DESC
            LIMIT 3
        ");
    }
    $stmt->execute();
    $testGetAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($testGetAll) > 0) {
        echo "<p>Query berhasil mengembalikan " . count($testGetAll) . " baris data</p>";
    } else {
        echo "<p>Query tidak mengembalikan data. Kemungkinan ada masalah dengan JOIN atau data tidak tersedia.</p>";
    }
    
    // 5. Cek data di tabel pembayaran
    echo "<h2>Data Pembayaran</h2>";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pembayaran");
    $totalPembayaran = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total data di tabel pembayaran: <strong>{$totalPembayaran}</strong></p>";
    
    if ($totalPembayaran > 0) {
        $stmt = $conn->query("SELECT * FROM pembayaran ORDER BY created_at DESC LIMIT 3");
        $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Kode Transaksi</th><th>Kode Customer</th><th>Atas Nama</th><th>Total</th><th>Tanggal</th></tr>";
        
        foreach ($recentPayments as $payment) {
            echo "<tr>";
            echo "<td>{$payment['id']}</td>";
            echo "<td>{$payment['kode_transaksi']}</td>";
            echo "<td>{$payment['kode_customer']}</td>";
            echo "<td>{$payment['atas_nama']}</td>";
            echo "<td>{$payment['total_bayar']}</td>";
            echo "<td>{$payment['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red'>Error: " . $e->getMessage() . "</p>";
}
?>
