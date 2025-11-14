<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$success_message = '';
$error_message = '';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafeciels";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete feedback if requested
    if (isset($_GET['delete']) && !empty($_GET['delete'])) {
        $feedback_id = $_GET['delete'];

        $stmt = $conn->prepare("DELETE FROM feedback WHERE id = :id");
        $stmt->bindParam(':id', $feedback_id);
        $stmt->execute();

        $success_message = "Ulasan berhasil dihapus";
    }

    // Get all feedback with product and user details
    $stmt = $conn->prepare("
        SELECT f.*, b.nama_barang, u.nama as user_name
        FROM feedback f
        JOIN barang b ON f.id_barang = b.id
        JOIN users u ON f.id_user = u.id
        ORDER BY f.created_at DESC
    ");
    $stmt->execute();
    $all_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all survey responses
    $stmt = $conn->prepare("
        SELECT s.*, u.nama as user_name
        FROM survey_kepuasan s
        JOIN users u ON s.id_user = u.id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $all_surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average ratings for products
    $stmt = $conn->prepare("
        SELECT b.id, b.nama_barang, AVG(f.rating) as avg_rating, COUNT(f.id) as total_ratings
        FROM barang b
        LEFT JOIN feedback f ON b.id = f.id_barang
        GROUP BY b.id
        ORDER BY avg_rating DESC
    ");
    $stmt->execute();
    $product_ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average survey ratings
    $stmt = $conn->prepare("
        SELECT
            AVG(rating_pelayanan) as avg_pelayanan,
            AVG(rating_kebersihan) as avg_kebersihan,
            AVG(rating_kecepatan) as avg_kecepatan,
            AVG(rating_suasana) as avg_suasana,
            AVG(rating_keseluruhan) as avg_keseluruhan,
            COUNT(*) as total_surveys
        FROM survey_kepuasan
    ");
    $stmt->execute();
    $survey_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Initialize with default values if no surveys exist
    if (!$survey_stats || $survey_stats['total_surveys'] == 0) {
        $survey_stats = [
            'avg_pelayanan' => 0,
            'avg_kebersihan' => 0,
            'avg_kecepatan' => 0,
            'avg_suasana' => 0,
            'avg_keseluruhan' => 0,
            'total_surveys' => 0
        ];
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
    <title>Manajemen Ulasan - Cafe Ciels</title>
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f2f5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }

        .rating-stars {
            color: #f39c12;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f2f5;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background-color: #e8f4fd;
            color: #3498db;
        }

        .badge-success {
            background-color: #d4edda;
            color: #27ae60;
        }

        .badge-warning {
            background-color: #fef9e7;
            color: #f39c12;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #e74c3c;
        }

        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
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

        .tab-container {
            margin-bottom: 30px;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab.active {
            border-bottom-color: #3498db;
            color: #3498db;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 0 10px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            th, td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manajemen Ulasan</h1>
            <a href="admin_home.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="overview">Ringkasan</div>
                <div class="tab" data-tab="product-ratings">Rating Produk</div>
                <div class="tab" data-tab="feedback">Ulasan Produk</div>
                <div class="tab" data-tab="surveys">Survei Kepuasan</div>
            </div>

            <!-- Overview Tab -->
            <div class="tab-content active" id="overview">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($all_feedback); ?></div>
                        <div class="stat-label">Total Ulasan Produk</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $survey_stats['total_surveys']; ?></div>
                        <div class="stat-label">Total Survei Kepuasan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            $avg_product_rating = 0;
                            $total_rated_products = 0;
                            foreach ($product_ratings as $product) {
                                if ($product['avg_rating']) {
                                    $avg_product_rating += $product['avg_rating'];
                                    $total_rated_products++;
                                }
                            }
                            echo $total_rated_products > 0 ? number_format($avg_product_rating / $total_rated_products, 1) : '0.0';
                            ?>
                        </div>
                        <div class="rating-stars">
                            <?php
                            $avg = $total_rated_products > 0 ? $avg_product_rating / $total_rated_products : 0;
                            $full_stars = floor($avg);
                            $half_star = $avg - $full_stars >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }

                            if ($half_star) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }

                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <div class="stat-label">Rating Produk Rata-rata</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($survey_stats['avg_keseluruhan'], 1); ?></div>
                        <div class="rating-stars">
                            <?php
                            $avg = $survey_stats['avg_keseluruhan'];
                            $full_stars = floor($avg);
                            $half_star = $avg - $full_stars >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }

                            if ($half_star) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }

                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <div class="stat-label">Rating Kepuasan Keseluruhan</div>
                    </div>
                </div>

                <div class="card">
                    <h2 class="card-title">Statistik Survei Kepuasan</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Rating Rata-rata</th>
                                    <th>Visualisasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Pelayanan</td>
                                    <td><?php echo number_format($survey_stats['avg_pelayanan'], 1); ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php
                                            $avg = $survey_stats['avg_pelayanan'];
                                            $full_stars = floor($avg);
                                            $half_star = $avg - $full_stars >= 0.5;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star"></i>';
                                            }

                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }

                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Kebersihan</td>
                                    <td><?php echo number_format($survey_stats['avg_kebersihan'], 1); ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php
                                            $avg = $survey_stats['avg_kebersihan'];
                                            $full_stars = floor($avg);
                                            $half_star = $avg - $full_stars >= 0.5;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star"></i>';
                                            }

                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }

                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Kecepatan</td>
                                    <td><?php echo number_format($survey_stats['avg_kecepatan'], 1); ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php
                                            $avg = $survey_stats['avg_kecepatan'];
                                            $full_stars = floor($avg);
                                            $half_star = $avg - $full_stars >= 0.5;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star"></i>';
                                            }

                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }

                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Suasana</td>
                                    <td><?php echo number_format($survey_stats['avg_suasana'], 1); ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php
                                            $avg = $survey_stats['avg_suasana'];
                                            $full_stars = floor($avg);
                                            $half_star = $avg - $full_stars >= 0.5;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star"></i>';
                                            }

                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }

                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Keseluruhan</td>
                                    <td><?php echo number_format($survey_stats['avg_keseluruhan'], 1); ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php
                                            $avg = $survey_stats['avg_keseluruhan'];
                                            $full_stars = floor($avg);
                                            $half_star = $avg - $full_stars >= 0.5;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star"></i>';
                                            }

                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }

                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Product Ratings Tab -->
            <div class="tab-content" id="product-ratings">
                <div class="card">
                    <h2 class="card-title">Rating Produk</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Rating Rata-rata</th>
                                    <th>Jumlah Ulasan</th>
                                    <th>Visualisasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_ratings as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['nama_barang']); ?></td>
                                    <td><?php echo $product['avg_rating'] ? number_format($product['avg_rating'], 1) : 'Belum ada rating'; ?></td>
                                    <td><?php echo $product['total_ratings']; ?></td>
                                    <td>
                                        <?php if ($product['avg_rating']): ?>
                                        <div class="rating-stars">
                                            <?php
                                            $avg = $product['avg_rating'];
                                            $full_stars = floor($avg);
                                            $half_star = $avg - $full_stars >= 0.5;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star"></i>';
                                            }

                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }

                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                        <?php else: ?>
                                        <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Feedback Tab -->
            <div class="tab-content" id="feedback">
                <div class="card">
                    <h2 class="card-title">Ulasan Produk</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Pelanggan</th>
                                    <th>Rating</th>
                                    <th>Komentar</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($all_feedback) > 0): ?>
                                    <?php foreach ($all_feedback as $feedback): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($feedback['nama_barang']); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['user_name']); ?></td>
                                        <td>
                                            <div class="rating-stars">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $feedback['rating']) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td><?php echo nl2br(htmlspecialchars($feedback['komentar'])); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($feedback['created_at'])); ?></td>
                                        <td>
                                            <a href="?delete=<?php echo $feedback['id']; ?>" class="action-btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Belum ada ulasan produk</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Surveys Tab -->
            <div class="tab-content" id="surveys">
                <div class="card">
                    <h2 class="card-title">Survei Kepuasan Pelanggan</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pelanggan</th>
                                    <th>Pelayanan</th>
                                    <th>Kebersihan</th>
                                    <th>Kecepatan</th>
                                    <th>Suasana</th>
                                    <th>Keseluruhan</th>
                                    <th>Saran</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($all_surveys) > 0): ?>
                                    <?php foreach ($all_surveys as $survey): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($survey['user_name']); ?></td>
                                        <td><?php echo $survey['rating_pelayanan']; ?></td>
                                        <td><?php echo $survey['rating_kebersihan']; ?></td>
                                        <td><?php echo $survey['rating_kecepatan']; ?></td>
                                        <td><?php echo $survey['rating_suasana']; ?></td>
                                        <td><?php echo $survey['rating_keseluruhan']; ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($survey['saran'])); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($survey['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">Belum ada survei kepuasan</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
