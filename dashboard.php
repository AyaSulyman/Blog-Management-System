<?php
session_start();
$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM posts WHERE author_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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

    <h2 class='h2-con'>Your Blog Posts</h2>
    <div class='button-con'>
    <button class='create-btn'><a  href="create_post.php">+ New Post</a></button>
    <button class='logout-btn' onclick="location.href='logout.php'">Logout</button>
</div>
    <table>
        <tr>
            <th>Title</th>
            <th>Actions</th>
        </tr>
        <?php while ($post = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($post['title']); ?></td>
            <td>
                <button><a href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a></button>
               <button><a href="delete_post.php?id=<?php echo $post['id']; ?>">Delete</a></button> 
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
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
