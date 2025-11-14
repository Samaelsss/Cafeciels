<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS barang (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_barang VARCHAR(50) NOT NULL,
        nama_barang VARCHAR(100) NOT NULL UNIQUE,
        kategori VARCHAR(50) NOT NULL,
        gambar VARCHAR(255) NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        stok INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table barang created successfully<br>";

} catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
}

$conn = null;
?> 