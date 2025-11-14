<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create pembayaran table
    $sql = "CREATE TABLE IF NOT EXISTS pembayaran (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        kode_transaksi VARCHAR(50) NOT NULL UNIQUE,
        kode_customer VARCHAR(50) NOT NULL,
        total_bayar DECIMAL(10,2) NOT NULL,
        metode_pembayaran VARCHAR(50) NOT NULL,
        status_pembayaran VARCHAR(20) NOT NULL,
        catatan TEXT,
        kode_diskon VARCHAR(50) NULL,
        diskon_amount DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "Pembayaran table created successfully";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
