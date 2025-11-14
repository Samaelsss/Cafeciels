<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Ciels - Menu</title>
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

        .search-container {
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .cart-button {
            position: relative;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .cart-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .menu-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .menu-item:hover {
            transform: translateY(-5px);
        }

        .menu-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .menu-info {
            padding: 20px;
        }

        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            background-color: #e8f4fd;
            color: #3498db;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .menu-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .menu-price {
            font-size: 16px;
            font-weight: 600;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .add-to-cart {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-to-cart:hover {
            background: #2980b9;
        }

        .add-to-cart:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .stock-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .out-of-stock {
            color: #e74c3c;
            font-weight: 500;
        }

        .quantity-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .quantity-label {
            font-size: 14px;
            color: #2c3e50;
        }

        .quantity-input {
            width: 70px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .add-to-cart-form {
            margin-top: 10px;
        }

        /* Styling untuk tombol spinners di input number */
        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            opacity: 1;
            height: 24px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }

            .search-container {
                width: 100%;
                max-width: none;
                margin: 10px 0;
            }

            body {
                padding-top: 140px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="user.php" class="navbar-brand">Cafe Ciels</a>

        <div class="search-container">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari menu..." class="search-input"
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </form>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="margin-right: 10px;">Welcome, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>

            <a href="survey_kepuasan.php" class="cart-button" style="background-color: #9b59b6;">
                <i class="fas fa-star"></i>
                Survei
            </a>

            <a href="cart.php" class="cart-button">
                <i class="fas fa-shopping-cart"></i>
                Keranjang
                <?php
                $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                if ($cart_count > 0) {
                    echo "<span class='cart-count'>$cart_count</span>";
                }
                ?>
            </a>

            <a href="logout.php" class="cart-button" style="background-color: #e74c3c;">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="menu-grid">
            <?php
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "cafeciels";

            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sql = "SELECT * FROM barang";
                $params = [];

                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $sql .= " WHERE nama_barang LIKE :search OR kategori LIKE :search OR CAST(harga AS CHAR) LIKE :search";
                    $params['search'] = "%$search%";
                }

                $sql .= " ORDER BY nama_barang ASC";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<div class='menu-item'>";
                    if (!empty($row['gambar'])) {
                        echo "<a href='product_detail.php?id=" . $row['id'] . "'>";
                        echo "<img src='" . htmlspecialchars($row['gambar']) . "' alt='" . htmlspecialchars($row['nama_barang']) . "' class='menu-image'>";
                        echo "</a>";
                    }
                    echo "<div class='menu-info'>";
                    echo "<span class='category-badge'>" . htmlspecialchars($row['kategori']) . "</span>";
                    echo "<a href='product_detail.php?id=" . $row['id'] . "' style='text-decoration: none; color: inherit;'>";
                    echo "<h3 class='menu-name'>" . htmlspecialchars($row['nama_barang']) . "</h3>";
                    echo "</a>";
                    echo "<p class='menu-price'>Rp " . number_format($row['harga'], 0, ',', '.') . "</p>";

                    if ($row['stok'] > 0) {
                        echo "<p class='stock-info'>Stok: " . $row['stok'] . "</p>";
                        echo "<form action='add_to_cart.php' method='POST' class='add-to-cart-form'>";
                        echo "<input type='hidden' name='product_id' value='" . $row['id'] . "'>";
                        echo "<div class='quantity-input-container'>";
                        echo "<label class='quantity-label'>Jumlah:</label>";
                        echo "<input type='number' name='quantity' value='1' min='1' max='" . $row['stok'] . "' class='quantity-input'>";
                        echo "</div>";
                        echo "<button type='submit' class='add-to-cart'>";
                        echo "<i class='fas fa-cart-plus'></i>Tambah ke Keranjang";
                        echo "</button>";
                        echo "</form>";
                    } else {
                        echo "<p class='stock-info out-of-stock'>Stok Habis</p>";
                        echo "<button class='add-to-cart' disabled>";
                        echo "<i class='fas fa-cart-plus'></i>Stok Habis";
                        echo "</button>";
                    }

                    echo "</div>";
                    echo "</div>";
                }
            } catch(PDOException $e) {
                echo "<div style='text-align: center; color: #e74c3c;'>Error: " . $e->getMessage() . "</div>";
            }
            $conn = null;
            ?>
        </div>
    </div>
</body>
</html>