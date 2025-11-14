<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get list of tables in the database with better error handling and debugging
    $tables = [];
    $manageable_tables = ['barang', 'customer', 'promo', 'supplier', 'karyawan', 'shift', 'feedback', 'transaksi', 'diskon'];

    // Create tables if they don't exist
    // Barang table
    $sql = "CREATE TABLE IF NOT EXISTS barang (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_barang VARCHAR(50) NOT NULL,
        nama_barang VARCHAR(100) NOT NULL,
        kategori VARCHAR(50) NOT NULL,
        gambar VARCHAR(255),
        harga DECIMAL(10,2) NOT NULL,
        stok INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Customer table
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

    // Promo table
    $sql = "CREATE TABLE IF NOT EXISTS promo (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_promo VARCHAR(50) NOT NULL,
        nama_promo VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        jenis_diskon ENUM('Persentase', 'Nominal') NOT NULL,
        nilai_diskon DECIMAL(10,2) NOT NULL,
        min_pembelian DECIMAL(10,2) DEFAULT 0,
        tanggal_mulai DATE NOT NULL,
        tanggal_selesai DATE NOT NULL,
        status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Get all tables from database
    $stmt = $conn->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Debug information
    $debug_info = "Available tables in database: " . implode(", ", $all_tables);

    // Filter only manageable tables that exist
    $tables = array_intersect($manageable_tables, $all_tables);

    // More debug information
    $debug_info .= "\nManageable tables found: " . implode(", ", $tables);

    // If no tables found, show message with debug info
    if (empty($tables)) {
        $warning_message = "Tidak ada tabel yang terdeteksi. Debug info: " . $debug_info;
    }

} catch(PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cafe Ciels</title>
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

        h1 {
            color: #2c3e50;
            font-size: 32px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 40px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background-color: #3498db;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .card-icon i {
            color: white;
            font-size: 24px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .card-description {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
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
            text-align: center;
        }

        .btn:hover {
            background-color: #2980b9;
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

            .dashboard {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin-bottom: 0;">Admin Dashboard</h1>
            <div>
                <span style="margin-right: 15px;">Welcome, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                <a href="logout.php" class="btn" style="background-color: #e74c3c;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if (isset($warning_message)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $warning_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard">
            <?php foreach($tables as $table): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <h2 class="card-title"><?php echo htmlspecialchars(ucfirst($table)); ?></h2>
                </div>
                <p class="card-description">Manage data in the <?php echo htmlspecialchars($table); ?> table</p>
                <?php if($table === 'transaksi'): ?>
                    <a href="manage_transaksi_admin.php" class="btn">Manage Table</a>
                <?php elseif($table === 'diskon'): ?>
                    <a href="manage_diskon.php" class="btn">Manage Discounts</a>
                <?php else: ?>
                    <a href="manage_<?php echo $table; ?>.php" class="btn">Manage Table</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <!-- Laporan Transaksi Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background-color: #8e44ad;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h2 class="card-title">Laporan Transaksi</h2>
                </div>
                <p class="card-description">Generate and manage transaction reports with customizable date ranges</p>
                <a href="laporan_transaksi.php" class="btn" style="background-color: #8e44ad;">View Reports</a>
            </div>

            
        </div>
    </div>
</body>
</html>
<?php $conn = null; ?>