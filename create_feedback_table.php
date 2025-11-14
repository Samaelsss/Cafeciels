<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create feedback table
    $sql = "CREATE TABLE IF NOT EXISTS feedback (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_barang INT NOT NULL,
        id_user INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        komentar TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_barang) REFERENCES barang(id) ON DELETE CASCADE,
        FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product (id_user, id_barang)
    )";
    $conn->exec($sql);
    echo "Table feedback created successfully<br>";

    // Create survey table for general customer satisfaction
    $sql = "CREATE TABLE IF NOT EXISTS survey_kepuasan (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        rating_pelayanan INT NOT NULL CHECK (rating_pelayanan BETWEEN 1 AND 5),
        rating_kebersihan INT NOT NULL CHECK (rating_kebersihan BETWEEN 1 AND 5),
        rating_kecepatan INT NOT NULL CHECK (rating_kecepatan BETWEEN 1 AND 5),
        rating_suasana INT NOT NULL CHECK (rating_suasana BETWEEN 1 AND 5),
        rating_keseluruhan INT NOT NULL CHECK (rating_keseluruhan BETWEEN 1 AND 5),
        saran TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Table survey_kepuasan created successfully<br>";

} catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
}

$conn = null;
?>
