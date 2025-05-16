<?php
session_start();
$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$post_id = $_GET['id'];
$sql = "SELECT * FROM posts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    die("Post not found.");
}

$title = $post['title'];
$content = $post['content'];
$errors = [];

// Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST ["title"]);
    $content = trim($_POST["content"]);

    if (empty($title) || empty($content)) {
        $errors[] = "Title and content must be filled.";
    } else {
        $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $content, $post_id);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Error updating post.";
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
    <title>Edit Post</title>
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
    <h2>Edit Post</h2>
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $e): ?>
            <p style='color:red;'><?php echo $e; ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post" class='edit-form' action="edit_post.php?id=<?php echo $post_id; ?>">
        <label>Title</label><br>
        <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" required><br><br>
        <label>Content</label><br>
        <textarea name="content" required><?php echo htmlspecialchars($content); ?></textarea><br><br>
        <button type="submit">Update Post</button>
    </form>
</div>

<footer class='footer'>
        <p style="padding-top:10px">&copy; <?php echo date("Y"); ?> Blog_Management. All rights reserved.</p>
        <p><a href="privacy_policy.php">Privacy Policy</a> | <a href="terms_of_service.php">Terms of Service</a></p>
    </footer>
</body>
</html>
