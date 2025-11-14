<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS diskon (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_diskon VARCHAR(50) NOT NULL UNIQUE,
        nama_diskon VARCHAR(100) NOT NULL,
        persentase_diskon DECIMAL(5,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table diskon created successfully<br>";

} catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
}

$conn = null;
?>
