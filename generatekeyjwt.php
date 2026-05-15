<?php
// Generate random JWT key 64 chars
$key = bin2hex(random_bytes(32));
echo "JWT_SECRET=\"" . $key . "\"\n";
echo "Length: " . strlen($key) . " characters\n";
?>