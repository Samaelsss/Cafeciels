<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check pembayaran table structure
    echo "<h2>Struktur Tabel Pembayaran</h2>";
    $stmt = $conn->query("SHOW COLUMNS FROM pembayaran");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>".$row['Field']."</td>";
        echo "<td>".$row['Type']."</td>";
        echo "<td>".$row['Null']."</td>";
        echo "<td>".$row['Key']."</td>";
        echo "<td>".$row['Default']."</td>";
        echo "<td>".$row['Extra']."</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check transaksi table structure
    echo "<h2>Struktur Tabel Transaksi</h2>";
    $stmt = $conn->query("SHOW COLUMNS FROM transaksi");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>".$row['Field']."</td>";
        echo "<td>".$row['Type']."</td>";
        echo "<td>".$row['Null']."</td>";
        echo "<td>".$row['Key']."</td>";
        echo "<td>".$row['Default']."</td>";
        echo "<td>".$row['Extra']."</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Sample data from pembayaran
    echo "<h2>Sample Data Pembayaran</h2>";
    $stmt = $conn->query("SELECT * FROM pembayaran LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($data) > 0) {
        echo "<table border='1'><tr>";
        foreach(array_keys($data[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach($data as $row) {
            echo "<tr>";
            foreach($row as $value) {
                echo "<td>".htmlspecialchars($value)."</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Tidak ada data di tabel pembayaran.";
    }

    // Sample data from transaksi
    echo "<h2>Sample Data Transaksi</h2>";
    $stmt = $conn->query("SELECT * FROM transaksi LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($data) > 0) {
        echo "<table border='1'><tr>";
        foreach(array_keys($data[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach($data as $row) {
            echo "<tr>";
            foreach($row as $value) {
                echo "<td>".htmlspecialchars($value)."</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Tidak ada data di tabel transaksi.";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
