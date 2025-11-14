<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tambahkan kolom atas_nama jika belum ada
    $sql = "ALTER TABLE transaksi ADD COLUMN IF NOT EXISTS atas_nama VARCHAR(100) DEFAULT NULL AFTER kode_transaksi";
    $conn->exec($sql);
    
    echo "Kolom atas_nama berhasil ditambahkan!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
