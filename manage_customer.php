<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

function autokode($conn) {
    try {
        // Query untuk mendapatkan kode terbesar
        $stmt = $conn->query("SELECT kode_customer FROM customer WHERE kode_customer LIKE 'C%' ORDER BY kode_customer DESC LIMIT 1");
        $row = $stmt->fetch();
        
        if ($row) {
            // Jika ada data, ambil nomor dari kode terakhir
            $last_code = $row['kode_customer'];
            $number = intval(substr($last_code, 1));
            $next_number = $number + 1;
        } else {
            // Jika tidak ada data, mulai dari 1
            $next_number = 1;
        }
        
        // Format kode baru dengan padding 3 digit
        $kode_customer = 'C' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        
        // Periksa apakah kode baru sudah ada
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE kode_customer = ?");
        $check_stmt->execute([$kode_customer]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists) {
            // Jika kode sudah ada, cari nomor berikutnya yang tersedia
            do {
                $next_number++;
                $kode_customer = 'C' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
                $check_stmt->execute([$kode_customer]);
                $exists = $check_stmt->fetchColumn();
            } while ($exists);
        }
        
        return $kode_customer;
    } catch(PDOException $e) {
        return 'C001';
    }
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create customer table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS customer (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_customer VARCHAR(50) NOT NULL,
        nama VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        telepon VARCHAR(15) NOT NULL,
        alamat TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Add delete handler
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
        try {
            $delete_id = $_POST['delete_id'];
            
            // Delete the record
            $stmt = $conn->prepare("DELETE FROM customer WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            $success_message = "Data customer berhasil dihapus!";
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
    // HANDLE ADD NEW CUSTOMER
    elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validasi field wajib
        $required = ['nama', 'email', 'telepon'];
        foreach($required as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("Field $field harus diisi!");
            }
        }

        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid!");
        }

        $kode_customer = autokode($conn);
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $telepon = $_POST['telepon'];
        $alamat = $_POST['alamat'] ?? '';

        // Insert data
        $sql = "INSERT INTO customer (kode_customer, nama, email, telepon, alamat) 
                VALUES (:kode_customer, :nama, :email, :telepon, :alamat)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':kode_customer' => $kode_customer,
            ':nama' => $nama,
            ':email' => $email,
            ':telepon' => $telepon,
            ':alamat' => $alamat
        ]);
        $success_message = "Data customer berhasil ditambahkan dengan kode: " . $kode_customer;
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
    <title>Manage Customer - Cafe Ciels</title>
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

        .col-table {
            flex: 1;
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
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
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
            text-decoration: none;
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

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 0 10px;
            }

            .col-table {
                min-width: 100%;
            }

            .card {
                padding: 15px;
            }

            th, td {
                padding: 10px;
            }
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

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 0 10px;
            }

            .col-table {
                min-width: 100%;
            }

            .card {
                padding: 15px;
            }

            th, td {
                padding: 10px;
            }
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }
    </style>
</head>
<body>
    <a href="admin_home.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Dashboard
    </a>

    <div class="container">
        <h2>Manage Customer</h2>

        <button class="btn-create" onclick="toggleCreateForm()">
            <i class="fas fa-plus"></i> Tambah Customer Baru
        </button>
        
        <div id="createForm" class="create-form">
            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nama Customer</label>
                        <input type="text" class="form-input" name="nama" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="tel" class="form-input" name="telepon" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-input" name="alamat" rows="3"></textarea>
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
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Alamat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $conn->query("SELECT * FROM customer ORDER BY created_at DESC");
                                    while ($row = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['kode_customer']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['telepon']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['alamat']) . "</td>";
                                        echo "<td class='action-buttons'>";
                                        echo "<a href='edit_customer.php?id=" . $row['id'] . "' class='btn btn-edit'><i class='fas fa-edit'></i> Edit</a>";
                                        echo "<form method='POST' style='display: inline;' onsubmit='return confirmDelete()'>";
                                        echo "<input type='hidden' name='delete_id' value='" . $row['id'] . "'>";
                                        echo "<button type='submit' class='btn-delete'><i class='fas fa-trash'></i> Hapus</button>";
                                        echo "</form>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<tr><td colspan='6'>Error: " . $e->getMessage() . "</td></tr>";
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
            return confirm('Apakah Anda yakin ingin menghapus data customer ini?');
        }
    </script>
</body>
</html>
<?php $conn = null; ?> 