<?php
session_start();

// Include transaction management functionality
require_once 'manage_transaksi.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Initialize variables
$discount_amount = 0;
$discount_code = '';
$discount_message = '';
$discount_error = '';

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['harga'] * $item['quantity'];
}

// Handle discount code application
if (isset($_POST['apply_discount']) && !empty($_POST['discount_code'])) {
    $discount_code = trim($_POST['discount_code']);
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if discount code exists
        $stmt = $conn->prepare("SELECT * FROM diskon WHERE kode_diskon = :kode_diskon");
        $stmt->execute([':kode_diskon' => $discount_code]);
        $discount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($discount) {
            // Calculate discount amount
            $discount_percentage = $discount['persentase_diskon'];
            $discount_amount = ($discount_percentage / 100) * $total;
            $discount_message = "Diskon {$discount_percentage}% ({$discount['nama_diskon']}) berhasil diterapkan!";
        } else {
            $discount_error = "Kode diskon tidak valid!";
            $discount_code = '';
        }
        
    } catch(PDOException $e) {
        $discount_error = "Error: " . $e->getMessage();
    }
}

// Calculate final total after discount
$final_total = $total - $discount_amount;

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Start transaction
        $conn->beginTransaction();

        // Create order
        $stmt = $conn->prepare("INSERT INTO pembayaran (kode_transaksi, kode_customer, atas_nama, total_bayar, metode_pembayaran, status_pembayaran, catatan, kode_diskon, diskon_amount) 
                               VALUES (:kode_transaksi, :kode_customer, :atas_nama, :total_bayar, :metode_pembayaran, 'Completed', :catatan, :kode_diskon, :diskon_amount)");
        
        $kode_transaksi = getNextTransactionCode();
        $stmt->execute([
            ':kode_transaksi' => $kode_transaksi,
            ':kode_customer' => $_POST['kode_customer'],
            ':atas_nama' => $_SESSION['cart'][0]['atas_nama'],
            ':total_bayar' => $final_total,
            ':metode_pembayaran' => $_POST['metode_pembayaran'],
            ':catatan' => $_POST['catatan'],
            ':kode_diskon' => $discount_code,
            ':diskon_amount' => $discount_amount
        ]);

        // Record each item in the transaction table
        foreach ($_SESSION['cart'] as $item) {
            // Add transaction record
            addTransaction(
                $kode_transaksi,
                $item['id'],
                $item['nama_barang'],
                $item['quantity'],
                $item['harga'],
                $_SESSION['cart'][0]['atas_nama']
            );
            
            // Update stock
            $stmt = $conn->prepare("UPDATE barang SET stok = stok - :quantity WHERE id = :id");
            $stmt->execute([
                ':quantity' => $item['quantity'],
                ':id' => $item['id']
            ]);
        }

        // Commit transaction
        $conn->commit();

        // Log transaction details for debugging
        error_log("Transaction created: $kode_transaksi with total: $final_total and items: " . count($_SESSION['cart']));
        error_log("Customer name: " . $_SESSION['cart'][0]['atas_nama']);
        
        

        // Tunggu sejenak untuk memastikan database telah menyimpan data
        sleep(1);
        
        // Verifikasi transaksi tersimpan
        $verify_stmt = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi = :kode");
        $verify_stmt->execute([':kode' => $kode_transaksi]);
        $transaction_count = $verify_stmt->fetchColumn();
        error_log("Verification check - Found $transaction_count items in transaksi table for $kode_transaksi");
        
        // Verifikasi pembayaran tersimpan
        $verify_payment = $conn->prepare("SELECT COUNT(*) FROM pembayaran WHERE kode_transaksi = :kode");
        $verify_payment->execute([':kode' => $kode_transaksi]);
        $payment_count = $verify_payment->fetchColumn();
        error_log("Verification check - Found $payment_count records in pembayaran table for $kode_transaksi");
        
        // Set transaction code in session for easy access
        $_SESSION['last_transaction'] = $kode_transaksi;
        
        unset($_SESSION['cart']);

        // Set success message
        $_SESSION['success_message'] = "Pesanan berhasil dibuat dengan kode: " . $kode_transaksi;
        
        // Redirect to transaction history page with the transaction code
        header('Location: lihat_transaksi.php?kode=' . $kode_transaksi . '&t=' . time());
        exit;

    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
    $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Cafe Ciels</title>
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
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .checkout-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .checkout-subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }

        .checkout-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
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
            padding: 12px 15px;
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

        .order-summary {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .summary-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .item-name {
            color: #2c3e50;
        }

        .item-price {
            font-weight: 500;
            color: #27ae60;
        }

        .subtotal-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .subtotal-label {
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
        }

        .subtotal-amount {
            font-size: 16px;
            font-weight: 500;
            color: #27ae60;
        }

        .discount-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .discount-label {
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
        }

        .discount-amount {
            font-size: 16px;
            font-weight: 500;
            color: #27ae60;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 2px solid #eee;
            margin-top: 10px;
        }

        .total-label {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .total-amount {
            font-size: 20px;
            font-weight: 600;
            color: #27ae60;
        }

        .submit-button {
            display: block;
            width: 100%;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .submit-button:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .back-link {
            display: block;
            text-align: center;
            color: #7f8c8d;
            text-decoration: none;
            margin-top: 20px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #2c3e50;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .discount-form {
            margin-bottom: 20px;
        }

        .discount-input-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .discount-input-group input[type="text"] {
            width: 70%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .discount-input-group button[type="submit"] {
            width: 25%;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .discount-input-group button[type="submit"]:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .discount-message {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .discount-message.success {
            background: #dff0d8;
            color: #3c763d;
        }

        .discount-message.error {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .checkout-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkout-header">
            <h1 class="checkout-title">Checkout</h1>
            <p class="checkout-subtitle">Lengkapi informasi pembayaran Anda</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="order-summary">
            <h2 class="summary-title">Ringkasan Pesanan</h2>
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="summary-item">
                    <span class="item-name">
                        <?php echo htmlspecialchars($item['nama_barang']); ?> 
                        (x<?php echo $item['quantity']; ?>)
                    </span>
                    <span class="item-price">
                        Rp <?php echo number_format($item['harga'] * $item['quantity'], 0, ',', '.'); ?>
                    </span>
                </div>
            <?php endforeach; ?>
            
            <div class="subtotal-row">
                <span class="subtotal-label">Subtotal</span>
                <span class="subtotal-amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
            </div>
            
            <!-- Discount Form -->
            <div class="discount-form">
                <form method="POST" class="discount-input-group">
                    <input type="text" name="discount_code" placeholder="Kode Diskon" 
                           value="<?php echo htmlspecialchars($discount_code); ?>" class="form-input">
                    <button type="submit" name="apply_discount" class="btn-apply-discount">Terapkan</button>
                </form>
                
                <?php if (!empty($discount_message)): ?>
                    <div class="discount-message success">
                        <i class="fas fa-check-circle"></i> <?php echo $discount_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($discount_error)): ?>
                    <div class="discount-message error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $discount_error; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($discount_amount > 0): ?>
            <div class="discount-row">
                <span class="discount-label">Diskon</span>
                <span class="discount-amount">- Rp <?php echo number_format($discount_amount, 0, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="total-row">
                <span class="total-label">Total</span>
                <span class="total-amount">Rp <?php echo number_format($final_total, 0, ',', '.'); ?></span>
            </div>
        </div>

        <form action="" method="POST" class="checkout-form" name="checkout">
            <input type="hidden" name="checkout" value="1">
            <div class="form-group">
                <label class="form-label">Kode Customer</label>
                <input type="text" name="kode_customer" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <select name="metode_pembayaran" class="form-input" required>
                    <option value="Cash">Cash</option>
                    <option value="Debit">Debit</option>
                    <option value="Credit">Credit</option>
                    <option value="E-Wallet">E-Wallet</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="catatan" class="form-input" rows="3"></textarea>
            </div>
            <button type="submit" class="submit-button">
                <i class="fas fa-lock"></i>
                Selesaikan Pembayaran
            </button>
        </form>

        <a href="cart.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Keranjang
        </a>
    </div>
</body>
</html>