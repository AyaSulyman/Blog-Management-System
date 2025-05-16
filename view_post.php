<?php
$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$post_id = $_GET['id'];
$stmt = $conn->prepare("SELECT posts.title, posts.content, users.username, posts.created_at FROM posts JOIN users ON posts.author_id = users.id WHERE posts.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    die("Post not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" href="nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
</head>
<body>
<div class="large-container">
       <nav class="navigation-bar">
            <ul class='ul-nav'>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
            </ul>
        </nav> 
<div class='small-content'>

    <h2><?php echo htmlspecialchars($post['title']); ?></h2>
    <p><strong>By:</strong> <?php echo htmlspecialchars($post['username']); ?></p>
    <p><strong>Published on:</strong> <?php echo htmlspecialchars($post['created_at']); ?></p>
    <div class='answer'><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
    <div class='a-small'>
     <button><a href="index.php">Back to Homepage</a></button>
     <button><a href="dashboard.php">Create your own post</a></button> 
</div>

  
</div>
 
</div>

 <footer class='footer'>
        <p style="padding-top:10px">&copy; <?php echo date("Y"); ?> Blog_Management. All rights reserved.</p>
        <p><a href="privacy_policy.php">Privacy Policy</a> | <a href="terms_of_service.php">Terms of Service</a></p>
    </footer>
</body>
</html>

<?php
$stmt->close();
$conn->close();
