<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Cafe Ciels</title>
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
            padding: 20px;
        }

        .search-container {
            max-width: 800px;
            margin: 0 auto 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 12px 20px;
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

        .search-button {
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .results-container {
            max-width: 800px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .item:hover {
            transform: translateY(-5px);
        }

        .item h3 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .item p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
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

        .price {
            font-weight: 600;
            color: #27ae60;
            font-size: 16px;
        }

        .no-results {
            text-align: center;
            color: #666;
            padding: 20px;
            grid-column: 1 / -1;
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

        @media (max-width: 600px) {
            .search-form {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
            }
            
            .results-container {
                grid-template-columns: 1fr;
            }

            .back-button {
                position: static;
                margin-bottom: 20px;
                width: fit-content;
            }
        }
    </style>
</head>
<body>
    <a href="manage_barang.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Menu
    </a>

    <div class="search-container">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="search-form">
            <input type="text" name="search" placeholder="Cari menu, kategori, atau harga..." class="search-input" value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>">
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i>
                Cari
            </button>
        </form>
    </div>

    <div class="results-container">
    <?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (isset($_POST['search'])) {
            $cari = $_POST['search'];

            $nyari = "SELECT * FROM barang WHERE 
                     nama_barang LIKE :search OR 
                     kategori LIKE :search OR 
                     CAST(harga AS CHAR) LIKE :search
                     ORDER BY nama_barang ASC";
            $hasil = $conn->prepare($nyari);
            $hasil->execute(['search' => "%$cari%"]);
            
            $found = false;
            while ($row = $hasil->fetch(PDO::FETCH_ASSOC)) {
                $found = true;
                echo "<div class='item'>";
                echo "<span class='category-badge'>" . htmlspecialchars($row['kategori']) . "</span>";
                echo "<h3>" . htmlspecialchars($row['nama_barang']) . "</h3>";
                if (!empty($row['gambar'])) {
                    echo "<img src='" . htmlspecialchars($row['gambar']) . "' alt='Menu Image' style='width:100%; height:150px; object-fit:cover; border-radius:8px; margin:10px 0;'>";
                }
                echo "<p class='price'>Rp " . number_format($row['harga'], 0, ',', '.') . "</p>";
                echo "<p>Stok: " . htmlspecialchars($row['stok']) . "</p>";
                echo "</div>";
            }

            if (!$found) {
                echo "<div class='no-results'>";
                echo "<i class='fas fa-search' style='font-size:24px; color:#999; margin-bottom:10px;'></i>";
                echo "<p>Tidak ada hasil yang ditemukan untuk '" . htmlspecialchars($cari) . "'</p>";
                echo "</div>";
            }
        }
    } catch(PDOException $e) {
        echo "<div class='no-results'>Error: " . $e->getMessage() . "</div>";
    }
    $conn = null;
    ?>
    </div>
</body>
</html>