<?php
session_start();

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $id => $quantity) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] = max(1, min((int)$quantity, 99)); // Limit between 1 and 99
                break;
            }
        }
    }
}

// Handle item removal
if (isset($_GET['remove']) && isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $_GET['remove']) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
}

// Calculate total
$total = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['harga'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Cafe Ciels</title>
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
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .cart-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }

        .back-button {
            background: #34495e;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: #2c3e50;
            transform: translateY(-2px);
        }

        .cart-items {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .item-name {
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .item-price {
            font-size: 14px;
            color: #27ae60;
            font-weight: 500;
        }

        .item-subtotal {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 16px;
            color: #2c3e50;
            font-weight: 600;
            text-align: right;
        }

        .item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .remove-item {
            color: #e74c3c;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .cart-summary {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .total-amount {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .checkout-button {
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
            text-align: center;
            text-decoration: none;
            margin-top: 20px;
        }

        .checkout-button:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .checkout-button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .empty-cart i {
            font-size: 48px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-cart p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .update-cart {
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-cart:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .form-input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 5px;
            background-color: #f9f9f9;
        }

        .payment-info {
            margin: 30px 0 20px;
        }
        
        .payment-info label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .payment-info input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .customer-search-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .customer-results {
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
            z-index: 100;
            display: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .customer-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .customer-item:hover {
            background-color: #f5f7fa;
        }
        
        .customer-item .customer-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .customer-item .customer-info {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .selected-customer-info {
            background-color: #f0f7ff;
            border: 1px solid #c3e0ff;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 15px;
            display: none;
        }
        
        .selected-customer-info p {
            margin: 5px 0;
        }
        
        .selected-customer-info .label {
            font-weight: 500;
            color: #2c3e50;
            display: inline-block;
            width: 100px;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .item-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
            }

            .item-details {
                width: 100%;
            }
        }

        .payment-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .payment-input-wrapper {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .currency-prefix {
            padding: 10px 15px;
            background: #f1f3f5;
            color: #495057;
            border-right: 1px solid #ddd;
            font-weight: 500;
        }

        .payment-input {
            flex: 1;
            padding: 10px 15px;
            border: none;
            font-size: 16px;
            width: 100%;
        }

        .payment-input:focus {
            outline: none;
        }

        .change-amount {
            margin-top: 10px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 6px;
            color: #2e7d32;
            font-weight: 500;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cart-header">
            <h1 class="cart-title">Keranjang Belanja</h1>
            <a href="user.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Menu
            </a>
        </div>

        <?php if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Keranjang belanja Anda masih kosong</p>
                <a href="user.php" class="back-button">
                    <i class="fas fa-utensils"></i>
                    Lihat Menu
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="cart-items">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="cart-item">
                            <?php if (!empty($item['gambar'])): ?>
                                <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_barang']); ?>" class="item-image">
                            <?php endif; ?>
                            <div class="item-details">
                                <div>
                                    <h3 class="item-name"><?php echo htmlspecialchars($item['nama_barang']); ?></h3>
                                    <p class="item-price">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></p>
                                    <div class="item-quantity">
                                        <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="quantity-input">
                                        <a href="?remove=<?php echo $item['id']; ?>" class="remove-item">
                                            <i class="fas fa-trash"></i>
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                                <?php if($item['quantity'] > 1): ?>
                                    <p class="item-subtotal">Subtotal:<br>Rp <?php echo number_format($item['harga'] * $item['quantity'], 0, ',', '.'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Total</span>
                        <span class="total-amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="payment-info">
                        <div class="customer-search-container">
                            <label for="customer_search">Nama Pembeli:</label>
                            <input type="text" id="customer_search" name="customer_search" placeholder="Ketik nama atau email customer...">
                            <div class="customer-results" id="customerResults"></div>
                        </div>
                        
                        <div class="selected-customer-info" id="selectedCustomerInfo">
                            <p><span class="label">Nama:</span> <span id="customerNameDisplay"></span></p>
                            <p><span class="label">Email:</span> <span id="customerEmailDisplay"></span></p>
                            <p><span class="label">Telepon:</span> <span id="customerPhoneDisplay"></span></p>
                        </div>
                        
                        <!-- Hidden fields for customer data -->
                        <input type="hidden" id="kode_customer" name="kode_customer" value="">
                        <input type="hidden" id="customer_id" name="customer_id" value="">
                        <input type="hidden" id="atas_nama" name="atas_nama" value="Pelanggan">
                        
                        <label for="payment_amount">Jumlah Bayar:</label>
                        <input type="number" id="payment_amount" name="payment_amount" placeholder="Masukkan jumlah pembayaran" min="<?php echo $total; ?>" required onkeyup="calculateChange()">
                    </div>
                    <button type="submit" name="update_cart" class="update-cart">
                        <i class="fas fa-sync"></i>
                        Update Keranjang
                    </button>
                    <a href="add_to_cart.php?action=print" onclick="return submitPayment(this, event)" class="checkout-button">
                        <i class="fas fa-receipt"></i>
                        Checkout
                    </a>
                    
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    function calculateChange() {
        const total = <?php echo $total; ?>;
        const paymentInput = document.getElementById('payment_amount');
        const changeDisplay = document.getElementById('changeAmount');
        const payment = parseInt(paymentInput.value) || 0;
        
        if (payment >= total) {
            const change = payment - total;
            changeDisplay.style.display = 'block';
            changeDisplay.textContent = `Kembalian: Rp ${change.toLocaleString('id-ID')}`;
            changeDisplay.style.background = '#e8f5e9';
            changeDisplay.style.color = '#2e7d32';
        } else {
            changeDisplay.style.display = 'block';
            changeDisplay.textContent = 'Pembayaran kurang dari total';
            changeDisplay.style.background = '#ffebee';
            changeDisplay.style.color = '#c62828';
        }
    }

    function submitPayment(link, event) {
        event.preventDefault();
        
        const paymentInput = document.getElementById('payment_amount');
        const atasNamaInput = document.getElementById('atas_nama');
        const payment = parseInt(paymentInput.value) || 0;
        const atasNama = atasNamaInput.value.trim();
        const total = <?php echo $total; ?>;
        
        // Validate payment
        if (payment < total) {
            alert('Pembayaran harus sama atau lebih besar dari total belanja');
            return false;
        }
        
        // Validate atas_nama
        if (!atasNama) {
            alert('Mohon masukkan nama pemesan');
            return false;
        }
        
        // Create form with payment data
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = link.href;
        
        // Add payment_amount field
        const paymentField = document.createElement('input');
        paymentField.type = 'hidden';
        paymentField.name = 'payment_amount';
        paymentField.value = payment;
        form.appendChild(paymentField);
        
        // Add atas_nama field
        const atasNamaField = document.createElement('input');
        atasNamaField.type = 'hidden';
        atasNamaField.name = 'atas_nama';
        atasNamaField.value = atasNama;
        form.appendChild(atasNamaField);
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
        
        return false;
    }

    document.getElementById('payment_amount').addEventListener('input', calculateChange);
    calculateChange(); // Calculate initial change
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customerSearchInput = document.getElementById('customer_search');
            const customerResults = document.getElementById('customerResults');
            const selectedCustomerInfo = document.getElementById('selectedCustomerInfo');
            const customerNameDisplay = document.getElementById('customerNameDisplay');
            const customerEmailDisplay = document.getElementById('customerEmailDisplay');
            const customerPhoneDisplay = document.getElementById('customerPhoneDisplay');
            const kodeCustomerInput = document.getElementById('kode_customer');
            const customerIdInput = document.getElementById('customer_id');
            const atasNamaInput = document.getElementById('atas_nama');
            
            // Function to search for customers
            function searchCustomers(query) {
                if (query.length < 2) {
                    customerResults.style.display = 'none';
                    return;
                }
                
                fetch('search_customer.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        customerResults.innerHTML = '';
                        
                        if (data.length === 0) {
                            const item = document.createElement('div');
                            item.className = 'customer-item';
                            item.innerHTML = 'Tidak ada hasil ditemukan';
                            customerResults.appendChild(item);
                        } else {
                            data.forEach(customer => {
                                const item = document.createElement('div');
                                item.className = 'customer-item';
                                item.innerHTML = `
                                    <div class="customer-name">${customer.nama}</div>
                                    <div class="customer-info">${customer.email} | ${customer.telepon}</div>
                                `;
                                
                                item.addEventListener('click', function() {
                                    selectCustomer(customer);
                                });
                                
                                customerResults.appendChild(item);
                            });
                        }
                        
                        customerResults.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error searching customers:', error);
                    });
            }
            
            // Function to select a customer
            function selectCustomer(customer) {
                customerNameDisplay.textContent = customer.nama;
                customerEmailDisplay.textContent = customer.email;
                customerPhoneDisplay.textContent = customer.telepon;
                
                kodeCustomerInput.value = customer.kode_customer;
                customerIdInput.value = customer.id;
                atasNamaInput.value = customer.nama;
                
                customerResults.style.display = 'none';
                selectedCustomerInfo.style.display = 'block';
                customerSearchInput.value = customer.nama;
            }
            
            // Event listener for customer search
            customerSearchInput.addEventListener('input', function() {
                searchCustomers(this.value);
            });
            
            // Close results when clicking outside
            document.addEventListener('click', function(e) {
                if (!customerSearchInput.contains(e.target) && !customerResults.contains(e.target)) {
                    customerResults.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>