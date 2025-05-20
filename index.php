<?php
session_start();

$conn = new mysqli("localhost", "root", "", "blog_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

$search_query = '';
$params = [];
$param_types = '';
$where_clauses = [];

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clauses[] = "(posts.title LIKE ? OR posts.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "ss";
}

if ($selected_category > 0) {
    $where_clauses[] = "posts.category_id = ?";
    $params[] = $selected_category;
    $param_types .= "i";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

$count_sql = "SELECT COUNT(*) FROM posts" . $where_sql;
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt) {
    if (!empty($param_types)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_posts = $count_result->fetch_row()[0];
    $count_stmt->close();
} else {
    $total_posts_sql = "SELECT COUNT(*) FROM posts";
    $total_posts_result = $conn->query($total_posts_sql);
    $total_posts = $total_posts_result->fetch_row()[0];
}

$total_pages = ceil($total_posts / $limit);

$sql = "SELECT posts.id, posts.title, posts.created_at, users.id AS user_id, users.username, categories.name AS category_name
        FROM posts 
        JOIN users ON posts.author_id = users.id
        LEFT JOIN categories ON posts.category_id = categories.id" . $where_sql . " 
        ORDER BY posts.created_at DESC 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $bind_params = $params;
    $bind_types = $param_types . "ii";
    $bind_params[] = $offset;
    $bind_params[] = $limit;

    $refs = [];
    foreach ($bind_params as $key => $value) {
        $refs[$key] = &$bind_params[$key];
    }

    array_unshift($refs, $bind_types);
    call_user_func_array([$stmt, 'bind_param'], $refs);

    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Failed to prepare statement.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="nav.css">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Blog Homepage</title>
</head>
<body>
    <div class="page-container">

    <nav class="navigation-bar">
        <ul class='ul-nav'>
            <li><a href="index.php">Home</a></li>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php else: ?>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li><a href="admin_dashboard.php" title="Admin Panel" style="font-weight:bold;">&#9881; Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="large-container">
        <h1 class='h1-con'>Welcome to the Blog</h1>

        <form method="GET" action="index.php#posts" class="search-form">
            <input class="search-input" style="max-width: 300px;height:40px" type="text" name="search" placeholder="Search posts..." value="<?php echo htmlspecialchars($search); ?>" />
            
            <select name="category_id" class="search-input" style="max-width: 200px;height:40px">
                <option value="0">All Categories</option>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($selected_category == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button class="search-btn" type="submit">Search</button>
            <input type="hidden" name="limit" value="<?php echo $limit; ?>" />
            <input type="hidden" name="page" value="1" />
        </form>

        <div class="posts-per-page">
            <form method="GET" action="index.php#posts">
                <label for="limit">Posts per page:</label>
                <select name="limit" id="limit" onchange="this.form.submit()">
                    <option value="5" <?php echo ($limit == 5) ? 'selected' : ''; ?>>5</option>
                    <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>10</option>
                </select>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>" />
                <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>" />
                <input type="hidden" name="page" value="1" />
            </form>
        </div>

        <div class='All-post' id="posts">
            <?php if ($total_posts > 0): ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="post">
                        <h3>
                            <a href="view_post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                        </h3>
                        <p><strong>By:</strong> <a href="profile.php?user_id=<?php echo $post['user_id']; ?>"><?php echo htmlspecialchars($post['username']); ?></a></p>
                        <p><strong>Published on:</strong> <?php echo htmlspecialchars($post['created_at']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></p>
                        <button onclick="location.href='view_post.php?id=<?php echo $post['id']; ?>'">View Post</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; font-size: 20px; margin-top: 30px;">No posts found matching your search.</p>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $selected_category; ?>#posts">Previous</a>
            <?php else: ?>
                <a class="disabled">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $selected_category; ?>#posts" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $selected_category; ?>#posts">Next</a>
            <?php else: ?>
                <a class="disabled">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <div>
        <?php include 'footer.php'; ?>
    </div>
    </div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        if (window.location.hash === "#posts") {
            const el = document.querySelector("#posts");
            if (el) el.scrollIntoView({ behavior: "smooth" });
        }
    });
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
