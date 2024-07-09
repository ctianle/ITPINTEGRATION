<?php
header('Content-Type: text/plain');

// Path to the server's public key
$server_public_key_path = '/var/www/keys/public_rsa.key';

// Serve the public key
if (file_exists($server_public_key_path)) {
    echo file_get_contents($server_public_key_path);
} else {
    http_response_code(404);
    echo "Public key not found.";
}
?>
