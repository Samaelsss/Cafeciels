<?php
// File untuk mengupdate struktur tabel transaksi (menambahkan kolom kode_customer)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

$messages = [];
$errors = [];

try {
    // Buat koneksi
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cek struktur tabel saat ini
    $columns = [];
    $stmt = $conn->query("DESCRIBE transaksi");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    // Tambahkan kolom kode_customer jika belum ada
    if (!isset($columns['kode_customer'])) {
        $conn->exec("ALTER TABLE transaksi ADD COLUMN kode_customer VARCHAR(50) NOT NULL DEFAULT 'CUSTOMER' AFTER kode_transaksi");
        $messages[] = "Kolom kode_customer berhasil ditambahkan ke tabel transaksi";
    } else {
        $messages[] = "Kolom kode_customer sudah ada di tabel transaksi";
    }
    
    // Periksa struktur tabel setelah perubahan
    $stmt = $conn->query("DESCRIBE transaksi");
    $updated_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $errors[] = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Struktur Tabel - Cafe Ciels</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f9f9f9;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 5px solid #28a745;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 5px solid #dc3545;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Struktur Tabel Transaksi</h1>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <h2>Struktur Tabel Transaksi</h2>
        
        <?php if (isset($updated_columns)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($updated_columns as $column): ?>
                        <tr>
                            <td><?php echo $column['Field']; ?></td>
                            <td><?php echo $column['Type']; ?></td>
                            <td><?php echo $column['Null']; ?></td>
                            <td><?php echo $column['Key']; ?></td>
                            <td><?php echo $column['Default']; ?></td>
                            <td><?php echo $column['Extra']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h3>Tindakan</h3>
        <a href="cart.php" class="btn">Kembali ke Keranjang</a>
        <a href="user.php" class="btn">Kembali ke Menu</a>
    </div>
</body>
</html>
