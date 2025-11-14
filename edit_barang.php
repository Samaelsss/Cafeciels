<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get record to edit
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT * FROM barang WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $barang = $stmt->fetch();
        if (!$barang) {
            echo "<div class='alert alert-danger'>Menu tidak ditemukan!</div>";
            exit();
        }
    } else {
        echo "<div class='alert alert-danger'>ID tidak ditemukan!</div>";
        exit();
    }

    // Process form submission for update
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $kode_barang = $_POST['kode_barang'];
        $nama_barang = $_POST['nama_barang'];
        $kategori = $_POST['kategori'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        
        // Handle file upload if new image is selected
        $gambar = $barang['gambar']; // Keep existing image by default
        if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . basename($_FILES["gambar"]["name"]);
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                $gambar = $target_file;
            }
        }

        $sql = "UPDATE barang SET 
                kode_barang = :kode_barang,
                nama_barang = :nama_barang,
                kategori = :kategori,
                gambar = :gambar,
                harga = :harga,
                stok = :stok
                WHERE id = :id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':kode_barang' => $kode_barang,
            ':nama_barang' => $nama_barang,
            ':kategori' => $kategori,
            ':gambar' => $gambar,
            ':harga' => $harga,
            ':stok' => $stok,
            ':id' => $_GET['id']
        ]);
        echo "<div class='alert alert-success'>Data berhasil diupdate!</div>";
        
        // Refresh data after update
        $stmt = $conn->prepare("SELECT * FROM barang WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $barang = $stmt->fetch();
    }
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu - Cafe Ciels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 90%;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h2 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #34495e;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
        }

        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .current-image {
            margin: 10px 0;
        }

        .current-image img {
            max-width: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 0 10px;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Menu</h2>
        
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Kode Menu</label>
                    <input type="text" class="form-input" name="kode_barang" value="<?php echo htmlspecialchars($barang['kode_barang']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Menu</label>
                    <input type="text" class="form-input" name="nama_barang" value="<?php echo htmlspecialchars($barang['nama_barang']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori Menu</label>
                    <select class="form-select" name="kategori" required>
                        <option value="Makanan" <?php if($barang['kategori'] == 'Makanan') echo 'selected'; ?>>Makanan</option>
                        <option value="Minuman" <?php if($barang['kategori'] == 'Minuman') echo 'selected'; ?>>Minuman</option>
                        <option value="Dessert" <?php if($barang['kategori'] == 'Dessert') echo 'selected'; ?>>Dessert</option>
                        <option value="Snack" <?php if($barang['kategori'] == 'Snack') echo 'selected'; ?>>Snack</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Gambar</label>
                    <?php if($barang['gambar']): ?>
                        <div class="current-image">
                            <p>Gambar Saat Ini:</p>
                            <img src="<?php echo htmlspecialchars($barang['gambar']); ?>" alt="Current Image">
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-input" name="gambar" accept="image/*">
                    <small>*Biarkan kosong jika tidak ingin mengubah gambar</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Harga</label>
                    <input type="number" class="form-input" name="harga" value="<?php echo htmlspecialchars($barang['harga']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stok</label>
                    <input type="number" class="form-input" name="stok" value="<?php echo htmlspecialchars($barang['stok']); ?>" required>
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="manage_barang.php" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn = null; ?> 