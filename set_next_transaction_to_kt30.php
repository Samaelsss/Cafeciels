<?php
// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cek apakah tabel pembayaran ada
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = :dbname 
        AND table_name = 'pembayaran'
    ");
    $stmt->execute([':dbname' => $dbname]);
    $tableExists = (bool)$stmt->fetchColumn();
    
    if ($tableExists) {
        // Cek kode transaksi terakhir
        $stmt = $conn->prepare("
            SELECT kode_transaksi 
            FROM pembayaran 
            WHERE kode_transaksi LIKE 'KT%' 
            ORDER BY CAST(SUBSTRING(kode_transaksi, 3) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $lastCode = $result['kode_transaksi'];
            $lastNumber = (int)substr($lastCode, 2);
            echo "<h3>Kode transaksi terakhir: $lastCode (nomor: $lastNumber)</h3>";
            
            // Buat tabel temporary untuk menyimpan nilai kode transaksi berikutnya
            $conn->exec("CREATE TABLE IF NOT EXISTS next_transaction_code (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                next_code INT NOT NULL
            )");
            
            // Hapus semua data yang ada di tabel next_transaction_code
            $conn->exec("TRUNCATE TABLE next_transaction_code");
            
            // Masukkan nilai 30 sebagai kode transaksi berikutnya
            $stmt = $conn->prepare("INSERT INTO next_transaction_code (next_code) VALUES (30)");
            $stmt->execute();
            
            echo "<h3>Kode transaksi berikutnya telah diatur ke KT30</h3>";
            echo "<p>Transaksi berikutnya akan menggunakan kode KT30.</p>";
        } else {
            echo "<h3>Belum ada transaksi di database.</h3>";
            
            // Buat tabel temporary untuk menyimpan nilai kode transaksi berikutnya
            $conn->exec("CREATE TABLE IF NOT EXISTS next_transaction_code (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                next_code INT NOT NULL
            )");
            
            // Hapus semua data yang ada di tabel next_transaction_code
            $conn->exec("TRUNCATE TABLE next_transaction_code");
            
            // Masukkan nilai 30 sebagai kode transaksi berikutnya
            $stmt = $conn->prepare("INSERT INTO next_transaction_code (next_code) VALUES (30)");
            $stmt->execute();
            
            echo "<h3>Kode transaksi berikutnya telah diatur ke KT30</h3>";
            echo "<p>Transaksi berikutnya akan menggunakan kode KT30.</p>";
        }
    } else {
        echo "<h3>Tabel pembayaran belum ada.</h3>";
        echo "<p>Silakan buat tabel pembayaran terlebih dahulu dengan mengakses create_pembayaran_table.php</p>";
    }
    
} catch(PDOException $e) {
    echo "<h3>Error:</h3> <p>" . $e->getMessage() . "</p>";
}

$conn = null;
?>