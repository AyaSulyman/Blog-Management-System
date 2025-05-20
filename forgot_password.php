<?php
session_start();
$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["new_password"])) {
    $token = $_POST["token"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($new_password) || empty($confirm_password)) {
        $errors[] = "Please fill all fields.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT username FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($username);
            $stmt->fetch();
            $stmt->close();

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $hashed_password, $username);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $success = "Password has been successfully reset.";
        } else {
            $errors[] = "Invalid or expired token.";
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"])) {
    $username = trim($_POST["username"]);

    if (empty($username)) {
        $errors[] = "Username is required.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $token = bin2hex(random_bytes(50));
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO password_resets (username, token, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)");
            $stmt->bind_param("ss", $username, $token);
            $stmt->execute();

            $success = "A reset link has been generated: <a href='forgot_password.php?token=$token'>Click here to reset password</a>";
        } else {
            $errors[] = "No account found with that username.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="nav.css">
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #D2B48C, #F5F5DC, #875b3c);
            margin: 0;
        }

        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            margin: 100px auto 40px auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #875b3c;
            color: white;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 30px;
        }

        button:hover {
            background-color: #604a3a;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .error-messages {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        a {
            color: #875b3c;
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            background-color: #333;
            color: #fff;
            padding: 15px 10px;
            font-size: 14px;
        }

        .footer a {
            color: #f5f5f5;
            margin: 0 10px;
        }

        .navigation-bar {
            background-color: #875b3c;
            padding: 10px 0;
        }

        .ul-nav {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        .ul-nav li {
            display: inline;
        }

        .ul-nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .ul-nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="page-container">
      <?php include 'header.php'; ?>

        <!-- Form content -->
        <div class="form-container">
            <?php
            if (!empty($errors)) {
                echo "<div class='message error-messages'>";
                foreach ($errors as $e) {
                    echo "<p>$e</p>";
                }
                echo "</div>";
            }

            if (!empty($success)) {
                echo "<div class='message success-message'><p>$success</p></div>";
            }

            if (isset($_GET["token"])) {
                $token = $_GET["token"];
            ?>
                <form method="post" action="forgot_password.php">
                    <h2>Set New Password</h2>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                    <button type="submit">Reset Password</button>
                </form>
            <?php
            } else {
            ?>
                <form method="post" action="forgot_password.php">
                    <h2>Forgot Password</h2>
                    <label>Username</label>
                    <input type="text" name="username" required>
                    <button type="submit">Send Reset Link</button>
                </form>
            <?php } ?>
        </div>
<div style="margin-top:200px;width:100%">
        <?php include 'footer.php'; ?>
        </div>
    </div>
</body>
</html>
