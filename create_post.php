<?php
session_start();
$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$title = $content = "";
$errors = [];

// Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);

    if (empty($title) || empty($content)) {
        $errors[] = "Title and content must be filled.";
    } else {
        $author_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO posts (title, content, author_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $content, $author_id);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Error creating post.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="create.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
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
    <h2>Create New Post</h2>
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $e): ?>
            <p style='color:red;'><?php echo $e; ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post" action="create_post.php" class="createForm">
        <label>Title</label><br>
        <input type="text" name="title" required><br><br>
        <label>Content</label><br>
        <textarea name="content" required></textarea><br><br>
        <button type="submit">Create Post</button>
    </form>
</div>
<footer class='footer'>
        <p style="padding-top:10px">&copy; <?php echo date("Y"); ?> Blog_Management. All rights reserved.</p>
        <p><a href="privacy_policy.php">Privacy Policy</a> | <a href="terms_of_service.php">Terms of Service</a></p>
    </footer>
</body>
</html>
