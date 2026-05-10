<?php include('db/connection.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Recipe App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h2>Welcome Back</h2>
        <form action="login_process.php" method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn-search">Login</button>
        </form>
        <p>New user? <a href="register.php">Create an account</a></p>
    </div>
</body>
</html>
