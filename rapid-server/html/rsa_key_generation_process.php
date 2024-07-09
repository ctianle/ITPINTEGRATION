<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<title>ITP24 Admin Panel (RSA Key Generation)</title>

</head>

<?php include 'nav_bar.php'; ?>

<style> #paddingDiv{ padding-top: 2%; padding-right: 2%; padding-bottom: 2%; padding-left: 2%; } </style> <div id="paddingDiv"> <!-- Padding applies to this area onwards -->

<div class="alert alert-success" role="alert">
    <h4 class="alert-heading">RSA Asymmetric Keys Generated!</h4>
    <p>A pair of RSA asymmetric keys has been successfully generated.</p>
    <hr>
    <p class="mb-0">The public key is labelled as 'public_rsa.key' while the private key is labelled as 'private_rsa.key'</p>
    <p class="mb-0">
    <?php
    /////////////////////////////////////////////////
    // Display Last Line of RSA Key Generation Logs
    /////////////////////////////////////////////////
    
    $file = "/var/logs/myapp/" . "rsa_key_generation.log";
    $file = escapeshellarg($file); // For Security Purposes
    $line = `tail -n 1 $file`; //Last Line
    echo $line;
    ?>
    </p>
    <p class="mb-0">To head back to the index page, please click <a href="index.php" class="alert-link">here</a>.</p>
</div>

<?php

////////////////////////////////////////////
//      RSA Encryption Configurations
////////////////////////////////////////////
$config = array(
    "digest_alg" => "sha512",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

////////////////////////////////////////////
//           RSA Key Generation
////////////////////////////////////////////
   
// Create the private and public key
$res = openssl_pkey_new($config);

// Extract the private key from $res to $privKey
openssl_pkey_export($res, $privKey);
$pubKey = openssl_pkey_get_details($res)["key"];

// // NEW
// putenv("RSA_PRIVATE_KEY"=$privKey);
// putenv("RSA_PUBLIC_KEY"=$pubKey);
// NEW: TO BE RUNNED ON BASH
// export RSA_PRIVATE_KEY=$(cat /path/to/your/private_rsa.key)
// export RSA_PUBLIC_KEY=$(cat /path/to/your/public_rsa.key)

// OLD
file_put_contents('RSA/private_rsa.key', $privKey);
file_put_contents('RSA/public_rsa.key', $pubKey);


// Extract the public key from $res to $pubKey



//=============================================
//             Logging Parameters
//=============================================
date_default_timezone_set('Asia/Singapore');
$date_time = date('d-m-Y H:i:s');
$date = date('d-m-Y');

$log = "The previous RSA asymmetric key pairs were generated on " . $date_time . "\n";
// error_log(print_r($log, true), 3, $_SERVER['DOCUMENT_ROOT'] . "/rsa_key_generation.log");
$logfilelocation = "/var/logs/myapp/rsa_key_generation.log";
error_log(print_r($log, true), 3, $logfilelocation);

?>