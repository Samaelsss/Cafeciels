<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if columns already exist
    $stmt = $conn->prepare("SHOW COLUMNS FROM pembayaran LIKE 'kode_diskon'");
    $stmt->execute();
    $kode_diskon_exists = $stmt->fetch();
    
    $stmt = $conn->prepare("SHOW COLUMNS FROM pembayaran LIKE 'diskon_amount'");
    $stmt->execute();
    $diskon_amount_exists = $stmt->fetch();
    
    // Add columns if they don't exist
    if (!$kode_diskon_exists) {
        $conn->exec("ALTER TABLE pembayaran ADD COLUMN kode_diskon VARCHAR(50) NULL");
        echo "Added kode_diskon column to pembayaran table<br>";
    } else {
        echo "kode_diskon column already exists<br>";
    }
    
    if (!$diskon_amount_exists) {
        $conn->exec("ALTER TABLE pembayaran ADD COLUMN diskon_amount DECIMAL(10,2) DEFAULT 0.00");
        echo "Added diskon_amount column to pembayaran table<br>";
    } else {
        echo "diskon_amount column already exists<br>";
    }
    
    echo "Pembayaran table updated successfully";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
