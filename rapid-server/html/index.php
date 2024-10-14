<?php
session_start();
?>

<html lang="en">
    <head>
        <?php
        include "component/essential.inc.php";
        ?>
        <link rel="stylesheet" href="css/index.css">
        <title>Login</title>
        <!-- bcrypt CDN -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bcryptjs/2.4.3/bcrypt.min.js"></script>
        <script type="text/javascript">
        // Display an alert if there is a login error
        <?php if (isset($_SESSION['login_error'])): ?>
            alert("<?php echo $_SESSION['login_error']; ?>");
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>
        </script>
    </head>
    <body>
        <main class="container">
            <div class="login">
                <div class="logincontainer row-cols-3 g-3">
                    <div class="left col-lg-6 col-md-6 col-sm-6 col-12">
                        <div class="login-text">
                            <h2>RAPID</h2>
                            <p>Remote Assessment and Proctoring using Intelligent Devices</p>
                        </div>
                    </div>
                    <div class="right col-lg-6 col-md-6 col-sm-6 col-12">
                        <div class="login-form">
                            <h2>Login</h2>
                            <form id="loginForm" action="process/login_process.php" method="post">
                                <p>
                                    <label for="email">Email: <span>*</span></label>
                                    <input type="text" id="email" name="email" placeholder="Enter Email" required>
                                </p>
                                <p>
                                    <label for="customer_pwd">Password: <span>*</span></label>
                                    <input type="password" id="password" name="password" placeholder="Enter Password" required>
                                </p>
                                <div id="html_element"></div>
                                <p>
                                    <input type="submit" value="Sign In">
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
    <script defer src="js/index.js"></script>
</html>
