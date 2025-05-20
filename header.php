<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="create.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Blog'; ?></title>
</head>
<body>
<div class="small-container">
    <nav class="navigation-bar">
        <ul class='ul-nav'>
            <li><a href="index.php">Home</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
        </ul>
    </nav>
