<?php
// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cek apakah tabel customer ada
    $tableExists = $conn->query("SHOW TABLES LIKE 'customer'")->rowCount() > 0;
    
    if ($tableExists) {
        // Tampilkan struktur tabel
        $stmt = $conn->query("DESCRIBE customer");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Struktur Tabel Customer</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $col) {
            echo "<tr>";
            foreach ($col as $key => $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Tampilkan 5 data teratas
        $stmt = $conn->query("SELECT * FROM customer LIMIT 5");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($data) > 0) {
            echo "<h2>Sample Data Customer</h2>";
            echo "<table border='1'>";
            echo "<tr>";
            foreach ($data[0] as $key => $value) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Tidak ada data di tabel customer</p>";
        }
    } else {
        echo "<h2>Tabel customer tidak ditemukan</h2>";
        
        // Buat tabel customer jika belum ada
        echo "<h3>Membuat tabel customer...</h3>";
        
        $sql = "CREATE TABLE customer (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            kode_customer VARCHAR(20) NOT NULL,
            nama VARCHAR(100) NOT NULL,
            alamat TEXT,
            no_telp VARCHAR(20),
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
        
        // Tambahkan beberapa data contoh
        $sql = "INSERT INTO customer (kode_customer, nama, alamat, no_telp, email) VALUES
            ('CUST001', 'Budi Santoso', 'Jl. Merdeka No. 10, Jakarta', '08123456789', 'budi@example.com'),
            ('CUST002', 'Siti Rahayu', 'Jl. Sudirman No. 45, Bandung', '08987654321', 'siti@example.com'),
            ('CUST003', 'Ahmad Rizki', 'Jl. Ahmad Yani No. 23, Surabaya', '08234567890', 'ahmad@example.com')
        ";
        
        $conn->exec($sql);
        echo "<p>Tabel customer berhasil dibuat dengan data contoh</p>";
    }
    
} catch(PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
