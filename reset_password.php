<?php
session_start();
$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$success = "";


if (isset($_GET['token'])) {
    $token = $_GET['token'];

 
    $stmt = $conn->prepare("SELECT username FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
      
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $password = trim($_POST["password"]);
            $confirm_password = trim($_POST["confirm_password"]);

            if (empty($password) || empty($confirm_password)) {
                $errors[] = "Both fields are required.";
            } elseif ($password !== $confirm_password) {
                $errors[] = "Passwords do not match.";
            } else {
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

               
                $stmt->close();
                $stmt = $conn->prepare("UPDATE users SET password = (SELECT ? WHERE username = (SELECT username FROM password_resets WHERE token = ?))");
                $stmt->bind_param("ss", $hashed_password, $token);
                $stmt->execute();

              
                $stmt->close();
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();

                $success = "Your password has been reset successfully. You can now log in.";
            }
        }
    } else {
        $errors[] = "Invalid or expired token.";
    }
} else {
    $errors[] = "No token provided.";
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body>
<div class="container">
    <?php
    if (!empty($errors)) {
        foreach ($errors as $e) {
            echo "<p style='color:red;'>$e</p>";
        }
    }
    if ($success) {
        echo "<p style='color:green;'>$success</p>";
    }
    ?>
    <form method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" class='form'>
        <h2>Set New Password</h2>
        <label>New Password</label><br>
        <input type="password" name="password" required><br><br>
        <label>Confirm Password</label><br>
        <input type="password" name="confirm_password" required><br><br>
        <button type="submit">Reset Password</button>
    </form>
</div>
</body>
</html>
