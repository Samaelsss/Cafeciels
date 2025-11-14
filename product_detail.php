<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: user.php");
    exit;
}

$product_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
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

    // Get product details
    $stmt = $conn->prepare("SELECT * FROM barang WHERE id = :id");
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        header("Location: user.php");
        exit;
    }

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Process feedback submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
        $rating = $_POST['rating'];
        $komentar = trim($_POST['komentar']);

        // Validate input
        if ($rating < 1 || $rating > 5) {
            $error_message = "Rating harus antara 1 sampai 5";
        } else {
            // Check if user already submitted feedback for this product
            $stmt = $conn->prepare("SELECT id FROM feedback WHERE id_user = :id_user AND id_barang = :id_barang");
            $stmt->bindParam(':id_user', $user_id);
            $stmt->bindParam(':id_barang', $product_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Update existing feedback
                $stmt = $conn->prepare("UPDATE feedback SET rating = :rating, komentar = :komentar, created_at = CURRENT_TIMESTAMP WHERE id_user = :id_user AND id_barang = :id_barang");
                $stmt->bindParam(':rating', $rating);
                $stmt->bindParam(':komentar', $komentar);
                $stmt->bindParam(':id_user', $user_id);
                $stmt->bindParam(':id_barang', $product_id);
                $stmt->execute();
                $success_message = "Ulasan Anda berhasil diperbarui!";
            } else {
                // Insert new feedback
                $stmt = $conn->prepare("INSERT INTO feedback (id_barang, id_user, rating, komentar) VALUES (:id_barang, :id_user, :rating, :komentar)");
                $stmt->bindParam(':id_barang', $product_id);
                $stmt->bindParam(':id_user', $user_id);
                $stmt->bindParam(':rating', $rating);
                $stmt->bindParam(':komentar', $komentar);
                $stmt->execute();
                $success_message = "Terima kasih atas ulasan Anda!";
            }
        }
    }

    // Get user's existing feedback if any
    $stmt = $conn->prepare("SELECT * FROM feedback WHERE id_user = :id_user AND id_barang = :id_barang");
    $stmt->bindParam(':id_user', $user_id);
    $stmt->bindParam(':id_barang', $product_id);
    $stmt->execute();
    $user_feedback = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all feedback for this product
    $stmt = $conn->prepare("
        SELECT f.*, u.nama as user_name
        FROM feedback f
        JOIN users u ON f.id_user = u.id
        WHERE f.id_barang = :id_barang
        ORDER BY f.created_at DESC
    ");
    $stmt->bindParam(':id_barang', $product_id);
    $stmt->execute();
    $all_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM feedback WHERE id_barang = :id_barang");
    $stmt->bindParam(':id_barang', $product_id);
    $stmt->execute();
    $rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $avg_rating = round($rating_data['avg_rating'], 1);
    $total_ratings = $rating_data['total_ratings'];

} catch(PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['nama_barang']); ?> - Cafe Ciels</title>
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
            padding-top: 80px;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .product-detail {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .product-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
        }

        .product-info {
            padding: 30px;
        }

        .product-category {
            display: inline-block;
            padding: 4px 12px;
            background-color: #e8f4fd;
            color: #3498db;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .product-name {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .product-price {
            font-size: 24px;
            font-weight: 600;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .product-stock {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
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

        .btn-back {
            background-color: #7f8c8d;
            margin-right: 10px;
        }

        .btn-back:hover {
            background-color: #6c7a7d;
        }

        .feedback-section {
            margin-top: 40px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2f5;
        }

        .rating-summary {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .average-rating {
            font-size: 48px;
            font-weight: 700;
            color: #2c3e50;
            margin-right: 20px;
        }

        .rating-stars {
            color: #f39c12;
            font-size: 24px;
            margin-right: 15px;
        }

        .rating-count {
            color: #7f8c8d;
            font-size: 16px;
        }

        .feedback-form {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            width: 40px;
            height: 40px;
            margin-right: 5px;
            position: relative;
            font-size: 30px;
            color: #ddd;
        }

        .star-rating label:before {
            content: '\f005';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 0;
            line-height: 40px;
            text-align: center;
            width: 100%;
        }

        .star-rating input:checked ~ label {
            color: #f39c12;
        }

        .star-rating:not(:checked) label:hover,
        .star-rating:not(:checked) label:hover ~ label {
            color: #f1c40f;
        }

        .star-rating input:checked + label:hover,
        .star-rating input:checked ~ label:hover,
        .star-rating label:hover ~ input:checked ~ label,
        .star-rating input:checked ~ label:hover ~ label {
            color: #f39c12;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .feedback-list {
            margin-top: 30px;
        }

        .feedback-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .user-info {
            font-weight: 500;
            color: #2c3e50;
        }

        .feedback-date {
            color: #7f8c8d;
            font-size: 14px;
        }

        .feedback-rating {
            color: #f39c12;
            margin-bottom: 10px;
        }

        .feedback-comment {
            color: #333;
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
                padding: 15px;
            }

            .product-info {
                padding: 20px;
            }

            .product-name {
                font-size: 24px;
            }

            .average-rating {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="user.php" class="navbar-brand">Cafe Ciels</a>

        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="margin-right: 10px;">Welcome, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>

            <a href="lihat_transaksi.php" class="btn" style="background-color: #27ae60; padding: 8px 15px;">
                <i class="fas fa-receipt"></i>
                Transaksi
            </a>

            <a href="survey_kepuasan.php" class="btn" style="background-color: #9b59b6; padding: 8px 15px;">
                <i class="fas fa-star"></i>
                Survei
            </a>

            <a href="cart.php" class="btn" style="background-color: #3498db; padding: 8px 15px;">
                <i class="fas fa-shopping-cart"></i>
                Keranjang
                <?php
                $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                if ($cart_count > 0) {
                    echo "<span class='cart-count'>$cart_count</span>";
                }
                ?>
            </a>

            <a href="logout.php" class="btn" style="background-color: #e74c3c; padding: 8px 15px;">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <div class="container">
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

        <div class="product-detail">
            <?php if (!empty($product['gambar'])): ?>
                <img src="<?php echo htmlspecialchars($product['gambar']); ?>" alt="<?php echo htmlspecialchars($product['nama_barang']); ?>" class="product-image">
            <?php endif; ?>

            <div class="product-info">
                <span class="product-category"><?php echo htmlspecialchars($product['kategori']); ?></span>
                <h1 class="product-name"><?php echo htmlspecialchars($product['nama_barang']); ?></h1>
                <p class="product-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></p>
                <p class="product-stock">Stok: <?php echo $product['stok']; ?></p>

                <div style="display: flex; margin-top: 20px;">
                    <a href="user.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Menu
                    </a>

                    <?php if ($product['stok'] > 0): ?>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn">
                                <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn" disabled style="background-color: #95a5a6;">
                            <i class="fas fa-times-circle"></i> Stok Habis
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="feedback-section">
            <h2 class="section-title">Ulasan Produk</h2>

            <div class="rating-summary">
                <div class="average-rating"><?php echo $avg_rating ? $avg_rating : '0.0'; ?></div>
                <div class="rating-stars">
                    <?php
                    $full_stars = floor($avg_rating);
                    $half_star = $avg_rating - $full_stars >= 0.5;
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
                <div class="rating-count"><?php echo $total_ratings; ?> ulasan</div>
            </div>

            <div class="feedback-form">
                <h3 style="margin-bottom: 20px;">Berikan Ulasan Anda</h3>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Rating</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" <?php echo (isset($user_feedback) && is_array($user_feedback) && $user_feedback['rating'] == 5) ? 'checked' : ''; ?>>
                            <label for="star5" title="5 stars"></label>
                            <input type="radio" id="star4" name="rating" value="4" <?php echo (isset($user_feedback) && is_array($user_feedback) && $user_feedback['rating'] == 4) ? 'checked' : ''; ?>>
                            <label for="star4" title="4 stars"></label>
                            <input type="radio" id="star3" name="rating" value="3" <?php echo (isset($user_feedback) && is_array($user_feedback) && $user_feedback['rating'] == 3) ? 'checked' : ''; ?>>
                            <label for="star3" title="3 stars"></label>
                            <input type="radio" id="star2" name="rating" value="2" <?php echo (isset($user_feedback) && is_array($user_feedback) && $user_feedback['rating'] == 2) ? 'checked' : ''; ?>>
                            <label for="star2" title="2 stars"></label>
                            <input type="radio" id="star1" name="rating" value="1" <?php echo (isset($user_feedback) && is_array($user_feedback) && $user_feedback['rating'] == 1) ? 'checked' : ''; ?>>
                            <label for="star1" title="1 star"></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="komentar">Komentar</label>
                        <textarea id="komentar" name="komentar" class="form-control" rows="4"><?php echo (isset($user_feedback) && is_array($user_feedback)) ? htmlspecialchars($user_feedback['komentar']) : ''; ?></textarea>
                    </div>

                    <button type="submit" name="submit_feedback" class="btn">
                        <?php echo (isset($user_feedback) && is_array($user_feedback)) ? 'Perbarui Ulasan' : 'Kirim Ulasan'; ?>
                    </button>
                </form>
            </div>

            <div class="feedback-list">
                <h3 style="margin-bottom: 20px;">Semua Ulasan</h3>

                <?php if (count($all_feedback) > 0): ?>
                    <?php foreach ($all_feedback as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <div class="user-info"><?php echo htmlspecialchars($feedback['user_name']); ?></div>
                                <div class="feedback-date"><?php echo date('d M Y', strtotime($feedback['created_at'])); ?></div>
                            </div>
                            <div class="feedback-rating">
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
                            <div class="feedback-comment">
                                <?php echo nl2br(htmlspecialchars($feedback['komentar'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Belum ada ulasan untuk produk ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
