<?php
// Read the content of the cart.php file
$content = file_get_contents('cart.php');

// Replace the test checkout button with empty string
$content = preg_replace(
    '/<a href="checkout\.php\?test=true" class="checkout-button" style="background-color: #e74c3c; margin-top: 10px;">\s*<i class="fas fa-vial"><\/i>\s*Test Checkout\s*<\/a>/s',
    '',
    $content
);

// Write the modified content back to the file
file_put_contents('cart.php', $content);

echo "Tombol Test Checkout telah dihapus dari file cart.php";
?>
