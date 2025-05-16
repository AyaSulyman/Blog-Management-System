<?php
$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$limit = 6; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT posts.id, posts.title, posts.created_at, users.username 
        FROM posts 
        JOIN users ON posts.author_id = users.id 
        ORDER BY posts.created_at DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

$total_posts_sql = "SELECT COUNT(*) FROM posts";
$total_posts_result = $conn->query($total_posts_sql);
$total_posts = $total_posts_result->fetch_row()[0];
$total_pages = ceil($total_posts / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Homepage</title>
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

        <h1 class='h1-con'>Welcome to the Blog</h1>
        <div class='All-post'>
    <?php while ($post = $result->fetch_assoc()): ?>
        <div class="post">
            <h3>
                <a href="view_post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
            </h3>
            <p><strong>By:</strong> <?php echo htmlspecialchars($post['username']); ?></p>
            <p><strong>Published on:</strong> <?php echo htmlspecialchars($post['created_at']); ?></p>
            <button onclick="location.href='view_post.php?id=<?php echo $post['id']; ?>'">View Post</button>
        </div>
    <?php endwhile; ?>
</div>

        
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
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
