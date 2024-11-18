<?php
$allowed_roles = ['admin'];
include('auth_check.php');
?>
<html lang="en">

<head>
    <?php
    include "component/admin_essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel (RSA Key Generation)</title>
</head>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include 'component/sidebar.inc.php'; ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                        <div class="card-body">
                                        <div id="paddingDiv">
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
                                                
                                                $file = "/var/logs/myapp/rsa_key_generation.log";
                                                $file = escapeshellarg($file); // For Security Purposes
                                                $line = `tail -n 1 $file`; // Last Line
                                                echo htmlspecialchars($line); // Use htmlspecialchars to prevent XSS
                                                ?>
                                                </p>
                                                <p class="mb-0">To head back to the index page, please click <a href="admin_index.php" class="alert-link">here</a>.</p>
                                            </div>

                                            <?php
                                            $ca_key_passphrase = getenv('CA_KEY_PASSPHRASE');

                                            ////////////////////////////////////////////
                                            //      RSA Encryption Configurations
                                            ////////////////////////////////////////////
                                            $config = array(
                                                "digest_alg" => "sha256",
                                                "private_key_bits" => 2048,
                                                "private_key_type" => OPENSSL_KEYTYPE_RSA,
                                            );

                                            ////////////////////////////////////////////
                                            //           RSA Key Generation
                                            ////////////////////////////////////////////
                                            // Create the private and public key
                                            $res = openssl_pkey_new($config);
                                            
                                            // Extract the private key from $res to $privKey
                                            openssl_pkey_export($res, $privKey);

                                            $dn = array(
                                                "countryName" => "SG",
                                                "stateOrProvinceName" => "Singapore",
                                                "localityName" => "Singapore",
                                                "organizationName" => "SIT",
                                                "organizationalUnitName" => "Team 4",
                                                "commonName" => "ca"
                                            );

                                            $ca_csr = openssl_csr_new($dn, $privKey, $config);
                                            $ca_cert = openssl_csr_sign($ca_csr, null, $res, 365, $config);
                                            if ($ca_cert === false) {
                                                $error = openssl_error_string();
                                                error_log("Error signing CA CSR: $error");
                                                die("Error signing CA CSR: $error");
                                            }

                                            openssl_x509_export_to_file($ca_cert, '/var/www/keys/root_ca.crt');
                                            openssl_pkey_export($res, $ca_privKey, $ca_key_passphrase);
                                            file_put_contents('/var/www/keys/private_rsa.key', $ca_privKey);

                                            // Extract the public key from $res to $pubKey
                                            $pubKey = openssl_pkey_get_details($res);
                                            $pubKey = $pubKey["key"];
                                            file_put_contents('/var/www/keys/public_rsa.key', $pubKey);

                                            //=============================================
                                            //             Logging Parameters
                                            //=============================================
                                            date_default_timezone_set('Asia/Singapore');
                                            $date_time = date('d-m-Y H:i:s');
                                            $date = date('d-m-Y');

                                            $log = "The previous RSA asymmetric key pairs were generated on " . $date_time . "\n";
                                            $logfilelocation = "/var/logs/myapp/rsa_key_generation.log";
                                            error_log($log, 3, $logfilelocation);
                                            ?>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script defer src="js/index.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#datatable').DataTable({
                lengthChange: false,
                dom: 'Blfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
                "pageLength": 1000
            });

            table.buttons().container().appendTo('#datatable_wrapper .col-md-6:eq(0)');
        });
    </script>
    <script>
    $(document).ready(function() {
        // Initialize any additional scripts if needed
    });
    </script>
</body>

</html>


