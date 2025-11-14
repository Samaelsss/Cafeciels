<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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
    
    // Process survey submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_survey'])) {
        $rating_pelayanan = $_POST['rating_pelayanan'];
        $rating_kebersihan = $_POST['rating_kebersihan'];
        $rating_kecepatan = $_POST['rating_kecepatan'];
        $rating_suasana = $_POST['rating_suasana'];
        $rating_keseluruhan = $_POST['rating_keseluruhan'];
        $saran = trim($_POST['saran']);
        
        // Validate input
        $valid = true;
        if ($rating_pelayanan < 1 || $rating_pelayanan > 5) {
            $valid = false;
        }
        if ($rating_kebersihan < 1 || $rating_kebersihan > 5) {
            $valid = false;
        }
        if ($rating_kecepatan < 1 || $rating_kecepatan > 5) {
            $valid = false;
        }
        if ($rating_suasana < 1 || $rating_suasana > 5) {
            $valid = false;
        }
        if ($rating_keseluruhan < 1 || $rating_keseluruhan > 5) {
            $valid = false;
        }
        
        if (!$valid) {
            $error_message = "Semua rating harus antara 1 sampai 5";
        } else {
            // Insert survey data
            $stmt = $conn->prepare("INSERT INTO survey_kepuasan (id_user, rating_pelayanan, rating_kebersihan, rating_kecepatan, rating_suasana, rating_keseluruhan, saran) 
                                    VALUES (:id_user, :rating_pelayanan, :rating_kebersihan, :rating_kecepatan, :rating_suasana, :rating_keseluruhan, :saran)");
            $stmt->bindParam(':id_user', $user_id);
            $stmt->bindParam(':rating_pelayanan', $rating_pelayanan);
            $stmt->bindParam(':rating_kebersihan', $rating_kebersihan);
            $stmt->bindParam(':rating_kecepatan', $rating_kecepatan);
            $stmt->bindParam(':rating_suasana', $rating_suasana);
            $stmt->bindParam(':rating_keseluruhan', $rating_keseluruhan);
            $stmt->bindParam(':saran', $saran);
            $stmt->execute();
            
            $success_message = "Terima kasih atas feedback Anda! Kami sangat menghargai masukan Anda untuk meningkatkan layanan kami.";
        }
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
    <title>Survei Kepuasan Pelanggan - Cafe Ciels</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .survey-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .survey-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .survey-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .survey-description {
            color: #7f8c8d;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .rating-group {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .rating-item {
            text-align: center;
            width: 60px;
        }

        .rating-radio {
            display: none;
        }

        .rating-label {
            display: block;
            cursor: pointer;
            width: 50px;
            height: 50px;
            line-height: 50px;
            background-color: #f0f2f5;
            border-radius: 50%;
            margin: 0 auto 5px;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s ease;
        }

        .rating-text {
            font-size: 12px;
            color: #7f8c8d;
        }

        .rating-radio:checked + .rating-label {
            background-color: #3498db;
            color: white;
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

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f2f5;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .survey-card {
                padding: 20px;
            }
            
            .rating-group {
                justify-content: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="user.php" class="navbar-brand">Cafe Ciels</a>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="margin-right: 10px;">Welcome, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
        
        
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
        
        <div class="survey-card">
            <div class="survey-header">
                <h1 class="survey-title">Survei Kepuasan Pelanggan</h1>
                <p class="survey-description">Bantu kami meningkatkan layanan dengan memberikan feedback Anda</p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <h3 class="section-title">Kualitas Pelayanan</h3>
                    <label>Bagaimana penilaian Anda terhadap pelayanan staf kami?</label>
                    <div class="rating-group">
                        <div class="rating-item">
                            <input type="radio" id="pelayanan-1" name="rating_pelayanan" value="1" class="rating-radio" required>
                            <label for="pelayanan-1" class="rating-label">1</label>
                            <div class="rating-text">Sangat Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="pelayanan-2" name="rating_pelayanan" value="2" class="rating-radio">
                            <label for="pelayanan-2" class="rating-label">2</label>
                            <div class="rating-text">Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="pelayanan-3" name="rating_pelayanan" value="3" class="rating-radio">
                            <label for="pelayanan-3" class="rating-label">3</label>
                            <div class="rating-text">Cukup</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="pelayanan-4" name="rating_pelayanan" value="4" class="rating-radio">
                            <label for="pelayanan-4" class="rating-label">4</label>
                            <div class="rating-text">Baik</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="pelayanan-5" name="rating_pelayanan" value="5" class="rating-radio">
                            <label for="pelayanan-5" class="rating-label">5</label>
                            <div class="rating-text">Sangat Baik</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <h3 class="section-title">Kebersihan</h3>
                    <label>Bagaimana penilaian Anda terhadap kebersihan cafe kami?</label>
                    <div class="rating-group">
                        <div class="rating-item">
                            <input type="radio" id="kebersihan-1" name="rating_kebersihan" value="1" class="rating-radio" required>
                            <label for="kebersihan-1" class="rating-label">1</label>
                            <div class="rating-text">Sangat Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kebersihan-2" name="rating_kebersihan" value="2" class="rating-radio">
                            <label for="kebersihan-2" class="rating-label">2</label>
                            <div class="rating-text">Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kebersihan-3" name="rating_kebersihan" value="3" class="rating-radio">
                            <label for="kebersihan-3" class="rating-label">3</label>
                            <div class="rating-text">Cukup</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kebersihan-4" name="rating_kebersihan" value="4" class="rating-radio">
                            <label for="kebersihan-4" class="rating-label">4</label>
                            <div class="rating-text">Baik</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kebersihan-5" name="rating_kebersihan" value="5" class="rating-radio">
                            <label for="kebersihan-5" class="rating-label">5</label>
                            <div class="rating-text">Sangat Baik</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <h3 class="section-title">Kecepatan Pelayanan</h3>
                    <label>Bagaimana penilaian Anda terhadap kecepatan pelayanan kami?</label>
                    <div class="rating-group">
                        <div class="rating-item">
                            <input type="radio" id="kecepatan-1" name="rating_kecepatan" value="1" class="rating-radio" required>
                            <label for="kecepatan-1" class="rating-label">1</label>
                            <div class="rating-text">Sangat Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kecepatan-2" name="rating_kecepatan" value="2" class="rating-radio">
                            <label for="kecepatan-2" class="rating-label">2</label>
                            <div class="rating-text">Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kecepatan-3" name="rating_kecepatan" value="3" class="rating-radio">
                            <label for="kecepatan-3" class="rating-label">3</label>
                            <div class="rating-text">Cukup</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kecepatan-4" name="rating_kecepatan" value="4" class="rating-radio">
                            <label for="kecepatan-4" class="rating-label">4</label>
                            <div class="rating-text">Baik</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="kecepatan-5" name="rating_kecepatan" value="5" class="rating-radio">
                            <label for="kecepatan-5" class="rating-label">5</label>
                            <div class="rating-text">Sangat Baik</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <h3 class="section-title">Suasana Cafe</h3>
                    <label>Bagaimana penilaian Anda terhadap suasana cafe kami?</label>
                    <div class="rating-group">
                        <div class="rating-item">
                            <input type="radio" id="suasana-1" name="rating_suasana" value="1" class="rating-radio" required>
                            <label for="suasana-1" class="rating-label">1</label>
                            <div class="rating-text">Sangat Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="suasana-2" name="rating_suasana" value="2" class="rating-radio">
                            <label for="suasana-2" class="rating-label">2</label>
                            <div class="rating-text">Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="suasana-3" name="rating_suasana" value="3" class="rating-radio">
                            <label for="suasana-3" class="rating-label">3</label>
                            <div class="rating-text">Cukup</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="suasana-4" name="rating_suasana" value="4" class="rating-radio">
                            <label for="suasana-4" class="rating-label">4</label>
                            <div class="rating-text">Baik</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="suasana-5" name="rating_suasana" value="5" class="rating-radio">
                            <label for="suasana-5" class="rating-label">5</label>
                            <div class="rating-text">Sangat Baik</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <h3 class="section-title">Penilaian Keseluruhan</h3>
                    <label>Secara keseluruhan, bagaimana penilaian Anda terhadap cafe kami?</label>
                    <div class="rating-group">
                        <div class="rating-item">
                            <input type="radio" id="keseluruhan-1" name="rating_keseluruhan" value="1" class="rating-radio" required>
                            <label for="keseluruhan-1" class="rating-label">1</label>
                            <div class="rating-text">Sangat Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="keseluruhan-2" name="rating_keseluruhan" value="2" class="rating-radio">
                            <label for="keseluruhan-2" class="rating-label">2</label>
                            <div class="rating-text">Buruk</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="keseluruhan-3" name="rating_keseluruhan" value="3" class="rating-radio">
                            <label for="keseluruhan-3" class="rating-label">3</label>
                            <div class="rating-text">Cukup</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="keseluruhan-4" name="rating_keseluruhan" value="4" class="rating-radio">
                            <label for="keseluruhan-4" class="rating-label">4</label>
                            <div class="rating-text">Baik</div>
                        </div>
                        <div class="rating-item">
                            <input type="radio" id="keseluruhan-5" name="rating_keseluruhan" value="5" class="rating-radio">
                            <label for="keseluruhan-5" class="rating-label">5</label>
                            <div class="rating-text">Sangat Baik</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="saran">Saran dan Masukan</label>
                    <textarea id="saran" name="saran" class="form-control" rows="4" placeholder="Berikan saran atau masukan untuk kami..."></textarea>
                </div>
                
                <div style="display: flex; margin-top: 20px;">
                    <a href="user.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Menu
                    </a>
                    
                    <button type="submit" name="submit_survey" class="btn" style="margin-left: auto;">
                        <i class="fas fa-paper-plane"></i> Kirim Survei
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
