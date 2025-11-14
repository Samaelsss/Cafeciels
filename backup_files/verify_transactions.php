<?php
require_once 'manage_transaksi.php';

echo "<h2>Verifying Transaction Display</h2>";

// Get all transactions
$transactions = getAllTransactions();

if (empty($transactions)) {
    echo "<p>No transactions found in the database.</p>";
} else {
    echo "<p>Found " . count($transactions) . " transaction records in the database.</p>";
    
    // Group transactions by kode_transaksi
    $grouped_transactions = [];
    foreach ($transactions as $transaction) {
        $code = $transaction['kode_transaksi'];
        if (!isset($grouped_transactions[$code])) {
            $grouped_transactions[$code] = [
                'kode_transaksi' => $code,
                'created_at' => $transaction['created_at'],
                'items' => [],
                'total' => 0,
                'kode_diskon' => $transaction['kode_diskon'] ?? null,
                'diskon_amount' => $transaction['diskon_amount'] ?? 0,
                'nama_diskon' => $transaction['nama_diskon'] ?? null,
                'persentase_diskon' => $transaction['persentase_diskon'] ?? 0
            ];
        }
        
        $grouped_transactions[$code]['items'][] = $transaction;
        $grouped_transactions[$code]['total'] += $transaction['subtotal'];
    }
    
    echo "<p>Found " . count($grouped_transactions) . " unique transactions.</p>";
    
    // Display grouped transactions
    echo "<h3>Transaction Summary:</h3>";
    echo "<ul>";
    foreach ($grouped_transactions as $code => $transaction) {
        echo "<li>";
        echo "<strong>Transaction Code:</strong> " . htmlspecialchars($code) . "<br>";
        echo "<strong>Date:</strong> " . date('Y-m-d H:i:s', strtotime($transaction['created_at'])) . "<br>";
        echo "<strong>Total Items:</strong> " . count($transaction['items']) . "<br>";
        echo "<strong>Total Amount:</strong> Rp " . number_format($transaction['total'], 0, ',', '.') . "<br>";
        
        if (!empty($transaction['kode_diskon']) && $transaction['diskon_amount'] > 0) {
            echo "<strong>Discount:</strong> " . htmlspecialchars($transaction['nama_diskon'] ?? $transaction['kode_diskon']) . 
                 " (" . $transaction['persentase_diskon'] . "%) - Rp " . 
                 number_format($transaction['diskon_amount'], 0, ',', '.') . "<br>";
            echo "<strong>Final Total:</strong> Rp " . 
                 number_format($transaction['total'] - $transaction['diskon_amount'], 0, ',', '.') . "<br>";
        }
        
        echo "<strong>Items:</strong><br>";
        echo "<ul>";
        foreach ($transaction['items'] as $item) {
            echo "<li>" . 
                 htmlspecialchars($item['nama_barang']) . " - " . 
                 $item['quantity'] . " x Rp " . 
                 number_format($item['harga'], 0, ',', '.') . " = Rp " . 
                 number_format($item['subtotal'], 0, ',', '.') . 
                 "</li>";
        }
        echo "</ul>";
        
        echo "</li><br>";
    }
    echo "</ul>";
    
    echo "<p><a href='lihat_transaksi.php'>Go to Transaction History Page</a></p>";
}
?>
