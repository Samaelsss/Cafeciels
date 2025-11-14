<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS transaksi (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_transaksi VARCHAR(50) NOT NULL,
        id_barang INT NOT NULL,
        nama_barang VARCHAR(100) NOT NULL,
        quantity INT NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_barang) REFERENCES barang(id)
    )";
    $conn->exec($sql);
    echo "Table transaksi created successfully<br>";

} catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
}

$conn = null;
?> 
