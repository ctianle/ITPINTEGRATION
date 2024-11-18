<?php
 
?>

<html lang="en">
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/index.css"> <!-- Optional, for styles -->
</head>
<body>
    <main class="container">
        <h2>Register</h2>
        <form action="process/register_process.php" method="post">
            <p>
                <label for="email">Email: <span>*</span></label>
                <input type="email" id="email" name="email" placeholder="Enter Email" required>
            </p>
            <p>
                <label for="password">Password: <span>*</span></label>
                <input type="password" id="password" name="password" placeholder="Enter Password" required>
            </p>
            <p>
                <input type="submit" value="Register">
            </p>
        </form>
        <?php
        // Display any registration errors
        if (isset($_SESSION['register_error'])) {
            echo "<p style='color:red;'>".$_SESSION['register_error']."</p>";
            unset($_SESSION['register_error']);
        }
        ?>
    </main>
</body>
</html>