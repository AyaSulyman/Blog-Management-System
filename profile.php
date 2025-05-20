<?php
$conn = new mysqli("localhost", "root", "", "blog_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    die("Invalid user ID.");
}


$user_stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user) {
    die("User  not found.");
}


$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;


$count_stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_posts = $count_result->fetch_row()[0];
$count_stmt->close();


$sql = "SELECT posts.id, posts.title, posts.created_at
        FROM posts 
        WHERE posts.author_id = ?
        ORDER BY posts.created_at DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();


$total_pages = ceil($total_posts / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="nav.css">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile</title>
</head>
<body>
<div class="page-container">

<nav class="navigation-bar">
    <ul class='ul-nav'>
        <li><a href="index.php">Home</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php">Register</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
    </ul>
</nav>

<div class="large-container">
    <h1 class='h1-con'>Profile: <?php echo htmlspecialchars($user['username']); ?></h1>
    <p><strong>Total Posts:</strong> <?php echo $total_posts; ?></p>

    <div class='All-post' id="posts">
        <?php if ($total_posts > 0): ?>
            <?php while ($post = $result->fetch_assoc()): ?>
                <div class="post">
                    <h3>
                        <a href="view_post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                    </h3>
                    <p><strong>Published on:</strong> <?php echo htmlspecialchars($post['created_at']); ?></p>
                    <button onclick="location.href='view_post.php?id=<?php echo $post['id']; ?>'">View Post</button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; font-size: 20px; margin-top: 30px;">This user has not written any posts yet.</p>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>#posts">Previous</a>
        <?php else: ?>
            <a class="disabled">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $i; ?>&limit=<?php echo $limit; ?>#posts" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>#posts">Next</a>
        <?php else: ?>
            <a class="disabled">Next</a>
        <?php endif; ?>
    </div>
</div>

<footer class='footer'>
    <p style="padding-top:10px">&copy; <?php echo date("Y"); ?> Blog_Management. All rights reserved.</p>
    <p><a href="privacy_policy.php">Privacy Policy</a> | <a href="terms_of_service.php">Terms of Service</a></p>
</footer>

</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
