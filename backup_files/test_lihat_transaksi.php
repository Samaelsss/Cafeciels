<?php
// File ini untuk menguji apakah fungsi getAllTransactions sudah berfungsi dengan benar dari browser
require_once 'manage_transaksi.php';

echo "<h1>Test Fungsi getAllTransactions</h1>";
$transactions = getAllTransactions();

if (empty($transactions)) {
    echo "<p style='color: red'>Tidak ada data transaksi ditemukan!</p>";
} else {
    echo "<p style='color: green'>Berhasil mendapatkan " . count($transactions) . " data transaksi!</p>";
    
    // Tampilkan sample data
    echo "<h2>Sample Data (3 data teratas)</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Kode Transaksi</th><th>Nama Barang</th><th>Quantity</th><th>Harga</th><th>Subtotal</th><th>Atas Nama</th><th>Created At</th></tr>";
    
    $count = 0;
    foreach ($transactions as $transaction) {
        if ($count < 3) {
            echo "<tr>";
            echo "<td>" . $transaction['kode_transaksi'] . "</td>";
            echo "<td>" . $transaction['nama_barang'] . "</td>";
            echo "<td>" . $transaction['quantity'] . "</td>";
            echo "<td>" . $transaction['harga'] . "</td>";
            echo "<td>" . $transaction['subtotal'] . "</td>";
            echo "<td>" . $transaction['atas_nama'] . "</td>";
            echo "<td>" . $transaction['created_at'] . "</td>";
            echo "</tr>";
            $count++;
        }
    }
    
    echo "</table>";
    
    // Tampilkan grouped transactions seperti yang dilakukan di lihat_transaksi.php
    echo "<h2>Grouped Transactions</h2>";
    
    // Group transactions by kode_transaksi
    $grouped_transactions = [];
    foreach ($transactions as $transaction) {
        $code = $transaction['kode_transaksi'];
        
        if (!isset($grouped_transactions[$code])) {
            // Prioritaskan nama pembeli dari tabel pembayaran jika tersedia
            $nama_pembeli = !empty($transaction['pembeli']) ? $transaction['pembeli'] : $transaction['atas_nama'];
            
            $grouped_transactions[$code] = [
                'kode_transaksi' => $code,
                'created_at' => $transaction['created_at'],
                'atas_nama' => $nama_pembeli,
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
    
    // Sort by most recent first
    usort($grouped_transactions, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo "<p>Total grouped transactions: " . count($grouped_transactions) . "</p>";
    
    // Tampilkan beberapa transaksi terkelompok
    if (count($grouped_transactions) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Kode Transaksi</th><th>Tanggal</th><th>Atas Nama</th><th>Jumlah Item</th><th>Total</th></tr>";
        
        $count = 0;
        foreach ($grouped_transactions as $group) {
            if ($count < 5) {
                echo "<tr>";
                echo "<td><a href='lihat_transaksi.php?kode=" . urlencode($group['kode_transaksi']) . "'>" . $group['kode_transaksi'] . "</a></td>";
                echo "<td>" . $group['created_at'] . "</td>";
                echo "<td>" . $group['atas_nama'] . "</td>";
                echo "<td>" . count($group['items']) . "</td>";
                echo "<td>Rp " . number_format($group['total'], 0, ',', '.') . "</td>";
                echo "</tr>";
                $count++;
            }
        }
        
        echo "</table>";
    }
    
    echo "<p><a href='lihat_transaksi.php' target='_blank'>Buka halaman Lihat Transaksi</a></p>";
}
?>
