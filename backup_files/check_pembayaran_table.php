<?php
// File untuk memeriksa struktur tabel pembayaran
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    // Buat koneksi
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cek struktur tabel pembayaran
    $stmt = $conn->query("DESCRIBE pembayaran");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Struktur Tabel Pembayaran</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Cek data pada tabel pembayaran
    $stmt = $conn->query("SELECT * FROM pembayaran ORDER BY created_at DESC LIMIT 3");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Data Pembayaran</h2>";
    
    if (count($payments) > 0) {
        echo "<table border='1'>";
        
        // Header tabel
        echo "<tr>";
        foreach (array_keys($payments[0]) as $header) {
            echo "<th>" . $header . "</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($payments as $payment) {
            echo "<tr>";
            foreach ($payment as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Tidak ada data pembayaran ditemukan</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red'>Error: " . $e->getMessage() . "</p>";
}
?>
