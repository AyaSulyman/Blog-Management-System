<?php 
session_start();

$conn = new mysqli("localhost", "root", "", "blog_management");

if($conn->connect_error){
    die("Connection failed: ". $conn->connect_error);
}

$username = $password = "";
$errors = [];

// Form Submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if(empty($username) || empty($password)){
        $errors[] = "Username and password must be filled";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                // Check if the user is admin
                if ($username === 'admin' && $password === 'admin') {
                    $_SESSION['is_admin'] = true; // Set admin session variable
                } else {
                    $_SESSION['is_admin'] = false; // Not admin
                }
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid username or password.";
                echo "<script>alert('Invalid username or password');</script>";
            }
        } else {
            $errors[] = "Invalid username or password.";
            echo "<script>alert('Invalid username or password');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!-- HTML Login Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
<div class="container" style="background: linear-gradient(to right,  #8B4513, #D2B48C, #F5F5DC);height:850px;margin-top:0px">
    <?php
    if(!empty($errors)){
        foreach($errors as $e){
            echo "<p style='color:red;'>$e</p>";
        }
    }
    ?>
    <form method="post" action="login.php" class='form'>
        <div style="margin-left:50px">
            <h2>User Login</h2>
            <label>Username</label><br>
            <input type="text" name="username" required><br><br>
            <label>Password</label><br>
            <input type="password" name="password" required><br><br>
            <button type="submit">Login</button>
            <p style="margin-left:0px;margin-top:30px">Don't have an account? <a href="register.php">Register</a></p>
            <p style="margin-left:0px;margin-top:20px">Forgot your password? <a href="forgot_password.php">Reset it</a></p>
        </div>
        <div class='img-container'>
            <img style="margin-left:220px;width:500px" src="images/peace.jpeg" alt="peace" />
        </div>
    </form>
</div>
</body>
</html>
