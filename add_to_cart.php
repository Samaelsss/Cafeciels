<?php
session_start();

// Handle print action
if (isset($_GET['action']) && $_GET['action'] === 'print') {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        header('Location: cart.php');
        exit;
    }

    // Get payment amount from POST
    $payment_amount = isset($_POST['payment_amount']) ? (int)$_POST['payment_amount'] : 0;
    $atas_nama = isset($_POST['atas_nama']) ? trim($_POST['atas_nama']) : 'Pelanggan';
    $kode_customer = isset($_POST['kode_customer']) ? trim($_POST['kode_customer']) : 'CUSTOMER';
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

    // Calculate total
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['harga'] * $item['quantity'];
    }

    // Calculate change
    $change = $payment_amount - $total;
    
    // Save transaction to database
    require_once 'manage_transaksi.php';
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Start transaction
        $conn->beginTransaction();
        
        // Create transaction code
        $kode_transaksi = getNextTransactionCode();
        
        // Insert into pembayaran table
        $stmt = $conn->prepare("INSERT INTO pembayaran (kode_transaksi, kode_customer, atas_nama, total_bayar, metode_pembayaran, status_pembayaran, catatan) 
                              VALUES (:kode_transaksi, :kode_customer, :atas_nama, :total_bayar, 'Tunai', 'Completed', 'Transaksi dari print')");
        $stmt->execute([
            ':kode_transaksi' => $kode_transaksi,
            ':kode_customer' => $kode_customer,
            ':atas_nama' => !empty($atas_nama) ? $atas_nama : 'Pelanggan',
            ':total_bayar' => $total
        ]);
        
        // Pastikan pembayaran tersimpan
        $verify_payment = $conn->prepare("SELECT * FROM pembayaran WHERE kode_transaksi = :kode");
        $verify_payment->execute([':kode' => $kode_transaksi]);
        $payment_data = $verify_payment->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment_data) {
            throw new Exception("Gagal menyimpan data pembayaran");
        }
        
        error_log("Payment data saved successfully: $kode_transaksi for customer: $atas_nama");
        
        // Record each item in the transaction table
        foreach ($_SESSION['cart'] as $item) {
            // Add transaction record
            addTransaction(
                $kode_transaksi,
                $item['id'],
                $item['nama_barang'],
                $item['quantity'],
                $item['harga'],
                $atas_nama, // Pastikan atas_nama diteruskan ke semua transaksi
                $kode_customer // Tambahkan kode_customer
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
        
        // Log transaction
        error_log("Print transaction created: $kode_transaksi with total: $total");
        
        // Simpan kode transaksi ke session agar bisa diambil di halaman berikutnya
        $_SESSION['last_transaction_code'] = $kode_transaksi;
        
        // Tunggu sejenak untuk memastikan semua data sudah tersimpan
        sleep(1);
        
        // Periksa lagi apakah transaksi sudah tersimpan dengan benar
        $verify_trans = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi = :kode");
        $verify_trans->execute([':kode' => $kode_transaksi]);
        $trans_count = $verify_trans->fetchColumn();
        
        if ($trans_count == 0) {
            error_log("PERINGATAN: Transaksi dengan kode $kode_transaksi tidak ditemukan di database setelah insert");
        } else {
            error_log("Transaksi dengan kode $kode_transaksi berhasil diverifikasi: $trans_count item");
        }
        
        // Redirect ke lihat_transaksi.php setelah 3 detik (setelah struk muncul)
        $redirect_script = "
        <script>
            setTimeout(function() {
                window.location.href = 'lihat_transaksi.php?kode=" . $kode_transaksi . "&t=" . time() . "';
            }, 3000);
        </script>";
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        if (isset($conn)) {
            $conn->rollBack();
        }
        error_log("Error in print transaction: " . $e->getMessage());
    }

    // Generate receipt HTML
    $receipt = '<!DOCTYPE html>
    <html>
    <head>
        <title>Struk Pembelian - Cafe Ciels</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                ground-color: #3498db;
                background-color: #3498db;
                
            }
            .receipt {
                max-width: 300px;
                margin: auto;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
            }
            .item {
                margin-bottom: 10px;
                border-bottom: 1px dashed #ccc;
                padding-bottom: 10px;
            }
            .item img {
                max-width: 100px;
                height: auto;
                display: block;
                margin: 5px 0;
            }
            .total {
                font-weight: bold;
                margin-top: 20px;
                border-top: 2px solid #000;
                padding-top: 10px;
            }
            .payment-details {
                margin-top: 10px;
                font-size: 18px;
            }
            .payment-details p {
                margin: 5px 0;
            }
            .transaction-code {
                font-family: monospace;
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                padding: 5px;
                text-align: center;
                margin: 10px 0;
                font-weight: bold;
            }
            @media print {
                body {
                    width: 100%; /* Full width */
                    display: flex;
                    justify-content: center; /* Center horizontally */
                    height: 100vh; /* Full height */
                    margin: 0; /* Remove default margin */
                }
                .receipt {
                    width: 300px; /* Fixed width for the receipt */
                    margin: 0; /* Remove margin */
                }
            }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <h2>Cafe Ciels</h2>
                <p>'.date('d/m/Y H:i:s').'</p>
                <p><strong>Nama Pembeli:</strong> '.$atas_nama.'</p>
                <div class="transaction-code">'.$kode_transaksi.'</div>
            </div>';

    foreach ($_SESSION['cart'] as $item) {
        $receipt .= '<div class="item">
            <img src="'.$item['gambar'].'" alt="'.$item['nama_barang'].'">
            <p>'.$item['nama_barang'].'<br>
            '.$item['quantity'].' x Rp '.number_format($item['harga'], 0, ',', '.').'<br>';
        if($item['quantity'] > 1) {
            $receipt .= 'Subtotal: Rp '.number_format($item['harga'] * $item['quantity'], 0, ',', '.').'<br>';
        }
        $receipt .= '</p>
        </div>';
    }

    $receipt .= '<div class="total">
            <p>Total: Rp '.number_format($total, 0, ',', '.').'</p>
            <div class="payment-details">
                <p>Tunai: Rp '.number_format($payment_amount, 0, ',', '.').'</p>
                <p>Kembali: Rp '.number_format($change, 0, ',', '.').'</p>
            </div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p style="font-size: 12px; margin-top: 10px;">Lihat riwayat transaksi Anda di <br>
            <strong>Menu Transaksi</strong></p>
        </div>
        </div>
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.location.href = "redirect_transaksi.php?kode=<?php echo $kode_transaksi; ?>";
                }, 1000);
            }
        </script>
        '.($redirect_script ?? '').'
    </body>
    </html>';

    // Clear cart after printing
    unset($_SESSION['cart']);
    
    // Output receipt
    echo $receipt;
    exit;
}

// Regular add to cart functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cafeciels";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get product details
        $stmt = $conn->prepare("SELECT * FROM barang WHERE id = :id AND stok > 0");
        $stmt->execute(['id' => $_POST['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Get quantity from form, default to 1 if not set
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            // Validate quantity against stock
            $quantity = max(1, min($quantity, $product['stok']));

            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Check if product already in cart
            $product_in_cart = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $product['id']) {
                    // Check if adding quantity exceeds stock
                    $new_quantity = $item['quantity'] + $quantity;
                    if ($new_quantity <= $product['stok']) {
                        $item['quantity'] = $new_quantity;
                    } else {
                        $item['quantity'] = $product['stok']; // Set to maximum available stock
                    }
                    $product_in_cart = true;
                    break;
                }
            }

            // If product not in cart, add it
            if (!$product_in_cart) {
                $_SESSION['cart'][] = [
                    'id' => $product['id'],
                    'kode_barang' => $product['kode_barang'],
                    'nama_barang' => $product['nama_barang'],
                    'harga' => $product['harga'],
                    'quantity' => $quantity,
                    'gambar' => $product['gambar']
                ];
            }

            $_SESSION['success_message'] = "Produk berhasil ditambahkan ke keranjang!";
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    $conn = null;
}

// Redirect back to previous page
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit; 