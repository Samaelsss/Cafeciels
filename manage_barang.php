<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

function autokode($conn) {
    try {
        // Query untuk mendapatkan kode terbesar
        $stmt = $conn->query("SELECT kode_barang FROM barang WHERE kode_barang LIKE 'M%' ORDER BY kode_barang DESC LIMIT 1");
        $row = $stmt->fetch();
        
        if ($row) {
            // Jika ada data, ambil nomor dari kode terakhir
            $last_code = $row['kode_barang'];
            $number = intval(substr($last_code, 1));
            $next_number = $number + 1;
        } else {
            // Jika tidak ada data, mulai dari 1
            $next_number = 1;
        }
        
        // Format kode baru dengan padding 3 digit
        $kode_barang = 'M' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        
        // Periksa apakah kode baru sudah ada
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM barang WHERE kode_barang = ?");
        $check_stmt->execute([$kode_barang]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists) {
            // Jika kode sudah ada, cari nomor berikutnya yang tersedia
            do {
                $next_number++;
                $kode_barang = 'M' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
                $check_stmt->execute([$kode_barang]);
                $exists = $check_stmt->fetchColumn();
            } while ($exists);
        }
        
        return $kode_barang;
    } catch(PDOException $e) {
        // Jika terjadi error, cari nomor yang belum digunakan
        try {
            $next_number = 1;
            $exists = true;
            
            while ($exists) {
                $kode_barang = 'M' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM barang WHERE kode_barang = ?");
                $check_stmt->execute([$kode_barang]);
                $exists = $check_stmt->fetchColumn();
                
                if ($exists) {
                    $next_number++;
                }
            }
            
            return $kode_barang;
        } catch(PDOException $e2) {
            return 'M001';
        }
    }
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // HANDLE DELETE ALL
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_all'])) {
        $stmt = $conn->prepare("DELETE FROM barang");
        $stmt->execute();
        $conn->exec("ALTER TABLE barang AUTO_INCREMENT = 1");
        $success_message = "Semua data berhasil dihapus!";
    }
    // HANDLE DELETE
    elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
        try {
            $delete_id = $_POST['delete_id'];
            
            // Get image path before delete
            $stmt = $conn->prepare("SELECT gambar FROM barang WHERE id = ?");
            $stmt->execute([$delete_id]);
            $barang = $stmt->fetch();
            
            // Delete the record
            $stmt = $conn->prepare("DELETE FROM barang WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            // Delete image file if exists
            if ($barang && $barang['gambar'] && file_exists($barang['gambar'])) {
                unlink($barang['gambar']);
            }
            
            $success_message = "Data berhasil dihapus!";
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
    // HANDLE ADD NEW ITEM
    elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validasi field wajib
        $required = ['nama_barang', 'kategori', 'harga', 'stok'];
        foreach($required as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("Field $field harus diisi!");
            }
        }

        $kode_barang = autokode($conn);
        $nama_barang = $_POST['nama_barang'];
        $kategori = $_POST['kategori'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        
        // Handle file upload
        $gambar = "";
        if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . basename($_FILES["gambar"]["name"]);
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                $gambar = $target_file;
            }
        }

        // Insert data
        $sql = "INSERT INTO barang (kode_barang, nama_barang, kategori, gambar, harga, stok) 
                VALUES (:kode_barang, :nama_barang, :kategori, :gambar, :harga, :stok)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':kode_barang' => $kode_barang,
            ':nama_barang' => $nama_barang,
            ':kategori' => $kategori,
            ':gambar' => $gambar,
            ':harga' => $harga,
            ':stok' => $stok
        ]);
        $success_message = "Data berhasil ditambahkan dengan kode: " . $kode_barang;
    }

} catch(PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
} catch(Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Barang - Cafe Ciels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
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
            max-width: 1200px;
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

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .col-form {
            flex: 1;
            min-width: 300px;
        }

        .col-table {
            flex: 2;
            min-width: 600px;
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
            border-color: #aa00014;
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

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        th {
            background-color: #3498db;
            color: white;
            font-weight: 500;
            text-align: left;
            padding: 15px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .btn-edit {
            display: inline-block;
            padding: 8px 16px;
            background-color: #f39c12;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background-color: #d68910;
            transform: translateY(-2px);
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

            .col-form, .col-table {
                min-width: 100%;
            }

            .card {
                padding: 15px;
            }

            th, td {
                padding: 10px;
            }
        }

        .btn-danger { background-color: #e74c3c !important; } .btn-danger:hover { background-color: #c0392b !important; } .text-danger { color: #e74c3c; }

        .btn-create {
            background-color: #2ecc71;
            margin-bottom: 30px;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .btn-create:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn-create::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease-out, height 0.6s ease-out;
        }

        .btn-create:active::after {
            width: 200px;
            height: 200px;
            opacity: 0;
        }

        .create-form {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 0;
        }

        .create-form.active {
            max-height: 1000px;
            opacity: 1;
            margin-bottom: 30px;
        }

        .create-form .card {
            transform: translateY(-20px);
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .create-form.active .card {
            transform: translateY(0);
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #34495e;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
        }

        .back-button:hover {
            background-color: #2c3e50;
            transform: translateY(-2px);
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-left: 8px;
            cursor: pointer;
        }

        .btn-delete:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-delete i {
            font-size: 12px;
        }

        .notification-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            min-width: 300px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert i {
            font-size: 18px;
        }

        .alert-success i {
            color: #28a745;
        }

        .alert-danger i {
            color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-search {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-search:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-search i {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <a href="admin_home.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Dashboard
    </a>

    <div class="container">
        <h2>Manage Barang</h2>

        <div style="display: flex; gap: 15px; margin-bottom: 30px;">
            <button class="btn-create" onclick="toggleCreateForm()">
                <i class="fas fa-plus"></i> Tambah Menu Baru
            </button>
            
            <a href="transaksi.php" class="btn-search">
                <i class="fas fa-search"></i> Cari Menu
            </a>
            
            <a href="manage_transaksi_admin.php" class="btn-search" style="background-color: #e67e22;">
                <i class="fas fa-receipt"></i> Riwayat Transaksi
            </a>
        </div>
        
        <div id="createForm" class="create-form">
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" class="form-input" name="nama_barang" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori Menu</label>
                        <select class="form-select" name="kategori" required>
                            <option value="Makanan">Makanan</option>
                            <option value="Minuman">Minuman</option>
                            <option value="Dessert">Dessert</option>
                            <option value="Snack">Snack</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar</label>
                        <input type="file" class="form-input" name="gambar" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga</label>
                        <input type="number" class="form-input" name="harga" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stok</label>
                        <input type="number" class="form-input" name="stok" required>
                    </div>
                    <button type="submit" class="btn">Simpan</button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-table">
                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Gambar</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $conn->query("SELECT * FROM barang ORDER BY created_at DESC");
                                    while ($row = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['kode_barang']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_barang']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                                        echo "<td>";
                                        if ($row['gambar']) {
                                            echo "<img src='" . htmlspecialchars($row['gambar']) . "' class='product-image' alt='Product Image'>";
                                        } else {
                                            echo "No image";
                                        }
                                        echo "</td>";
                                        echo "<td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['stok']) . "</td>";
                                        echo "<td class='action-buttons'>";
                                        echo "<a href='edit_barang.php?id=" . $row['id'] . "' class='btn btn-edit'><i class='fas fa-edit'></i> Edit</a>";
                                        echo "<form method='POST' style='display: inline;' onsubmit='return confirmDelete()'>";
                                        echo "<input type='hidden' name='delete_id' value='" . $row['id'] . "'>";
                                        echo "<button type='submit' class='btn-delete'><i class='fas fa-trash'></i> Hapus</button>";
                                        echo "</form>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<tr><td colspan='7'>Error: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="notification-container">
        <?php
        if (isset($success_message)) {
            echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>$success_message</div>";
        }
        if (isset($error_message)) {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i>$error_message</div>";
        }
        ?>
    </div>

    <script>
        function toggleCreateForm() {
            const form = document.getElementById('createForm');
            form.classList.toggle('active');
        }

        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus data ini?');
        }
    </script>
</body>
</html>
<?php $conn = null; ?>
