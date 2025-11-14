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
        $stmt = $conn->prepare("SELECT * FROM customer WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $customer = $stmt->fetch();
        if (!$customer) {
            echo "<div class='alert alert-danger'>Customer tidak ditemukan!</div>";
            exit();
        }
    } else {
        echo "<div class='alert alert-danger'>ID tidak ditemukan!</div>";
        exit();
    }

    // Process form submission for update
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

        $kode_customer = $_POST['kode_customer'];
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $telepon = $_POST['telepon'];
        $alamat = $_POST['alamat'] ?? '';

        // Check if email is changed and if new email already exists
        if ($email !== $customer['email']) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE email = :email AND id != :id");
            $check_stmt->execute([':email' => $email, ':id' => $_GET['id']]);
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("Email sudah digunakan oleh customer lain!");
            }
        }

        $sql = "UPDATE customer SET 
                kode_customer = :kode_customer,
                nama = :nama,
                email = :email,
                telepon = :telepon,
                alamat = :alamat
                WHERE id = :id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':kode_customer' => $kode_customer,
            ':nama' => $nama,
            ':email' => $email,
            ':telepon' => $telepon,
            ':alamat' => $alamat,
            ':id' => $_GET['id']
        ]);
        echo "<div class='alert alert-success'>Data berhasil diupdate!</div>";
        
        // Refresh data after update
        $stmt = $conn->prepare("SELECT * FROM customer WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $customer = $stmt->fetch();
    }
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
} catch(Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Cafe Ciels</title>
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

            .card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <a href="manage_customer.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Kembali
    </a>

    <div class="container">
        <h2>Edit Customer</h2>
        
        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Kode Customer</label>
                    <input type="text" class="form-input" name="kode_customer" value="<?php echo htmlspecialchars($customer['kode_customer']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Customer</label>
                    <input type="text" class="form-input" name="nama" value="<?php echo htmlspecialchars($customer['nama']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Telepon</label>
                    <input type="tel" class="form-input" name="telepon" value="<?php echo htmlspecialchars($customer['telepon']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea class="form-input" name="alamat" rows="3"><?php echo htmlspecialchars($customer['alamat']); ?></textarea>
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="manage_customer.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn = null; ?> 