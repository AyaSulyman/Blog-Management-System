<?php
session_start();

$conn = new mysqli("localhost", "root", "", "blog_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$post_id = $_GET['id'];
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

$stmt = $conn->prepare("SELECT posts.title, posts.content, users.username, posts.created_at, posts.image_path FROM posts JOIN users ON posts.author_id = users.id WHERE posts.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    die("Post not found.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in && isset($_POST['comment_content'])) {
    $comment = trim($_POST['comment_content']);
    if (!empty($comment)) {
        $insert_stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iis", $post_id, $current_user_id, $comment);
        $insert_stmt->execute();
        header("Location: view_post.php?id=" . $post_id);
        exit();
    }
}

if (isset($_GET['delete_comment']) && $is_logged_in) {
    $comment_id = intval($_GET['delete_comment']);
    $check_stmt = $conn->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $comment_id, $current_user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $del_stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $del_stmt->bind_param("i", $comment_id);
        $del_stmt->execute();
        header("Location: view_post.php?id=" . $post_id);
        exit();
    }
}

// Fetch all comments for this post
$comments_stmt = $conn->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at DESC");
$comments_stmt->bind_param("i", $post_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
 <style>
    .content-container {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        margin: 20px auto;
        max-width: 800px;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-top: 80px;
    }

    .post-image {
        flex: 1;
        max-width: 300px;
        margin-right: 20px;
    }

    .post-image img {
        width: 100%;
        height: 300px;
        border-radius: 5px;
    }

    .post-content {
        flex: 2;
        min-width: 0;
    }

    .post-content h2 {
        margin: 0 0 10px;
        font-size: 24px;
    }

    .post-content p {
        margin-top: 20px;
        font-size: 18px;
    }

    .post-content button {
        height: 45px;
        width: 220px;
        border: 1px solid #875b3c;
        border-radius: 10px;
        background-color: #875b3c;
        margin-left: 15px;
        margin-top: 20px;
        color: white;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .post-content button:hover {
        background-color: #604a3a;
    }

    .a-small {
        display: flex;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    .a-small button:first-child {
        margin-left: 0;
    }

    .comments-section {
        max-width: 800px;
        margin: 50px auto;
        padding: 0 10px;
    }

    .comments-section textarea {
        width: 400px;
        border-radius: 5px;
        padding: 10px;
        resize: vertical;
    }

    .comments-section button {
        display: block;
        width: 100%;
        max-width: 300px;
        margin: 10px auto 20px auto;
        padding: 10px 20px;
        background-color: #875b3c;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .comments-section .comment-box {
        background: #f0f0f0;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    .comments-section .comment-box p {
        margin: 5px 0;
        font-size: 18px;
    }

    .comments-section .delete-btn {
        display: block;
        width: 100%;
        max-width: 300px;
        margin: 10px auto;
        padding: 10px 20px;
        background-color: #875b3c;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    /* Responsive Design */
    @media screen and (max-width: 768px) {
        .content-container {
            flex-direction: column;
        }

        .post-image {
            max-width: 100%;
            margin-right: 0;
            margin-bottom: 20px;
        }

        .post-content button {
            width: 100%;
            margin-left: 0;
            margin-bottom: 10px;
        }

        .a-small {
            flex-direction: column;
            gap: 10px;
        }

        .a-small button {
            width: 100%;
            margin-left: 0;
        }
    }

    @media screen and (max-width: 480px) {
        .post-content h2 {
            font-size: 20px;
        }

        .post-content p,
        .comments-section .comment-box p {
            font-size: 16px;
        }

        footer {
            text-align: center;
            padding: 20px 10px;
        }
    }
</style>

</head>
<body>

<div class="page-container"> 
    <div class="large-container">
        <?php include 'header.php'; ?>

        <div class='content-container'>
            <?php if (!empty($post['image_path'])): ?>
                <div class="post-image">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image">
                </div>
            <?php endif; ?>
            <div class="post-content">
                <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                <p><strong>By:</strong> <?php echo htmlspecialchars($post['username']); ?></p>
                <p><strong>Published on:</strong> <?php echo htmlspecialchars($post['created_at']); ?></p>
                <div class='answer'><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                <div class='a-small'>
                    <button onclick="location.href='index.php'">Back to Homepage</button>
                    <button onclick="location.href='dashboard.php'">Create your own post</button> 
                </div>
            </div>
        </div>

        <!-- Comment Section -->
        <div class="comments-section">
            <h3>Comments</h3>

            <?php if ($is_logged_in): ?>
                <form method="post">
                    <textarea name="comment_content" rows="4" placeholder="Write your comment here..." required></textarea><br>
                    <button type="submit">Post Comment</button>
                </form>
            <?php else: ?>
                <p><a href="login.php">Log in</a> to post a comment.</p>
            <?php endif; ?>

            <?php if ($comments_result->num_rows > 0): ?>
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment-box">
                        <p><strong><?php echo htmlspecialchars($comment['username']); ?></strong> 
                        <span style="color:gray; font-size:12px;">on <?php echo $comment['created_at']; ?></span></p>
                        <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                        <?php if ($is_logged_in && $comment['user_id'] == $current_user_id): ?>
                            <form method="get" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $post_id; ?>">
                                <input type="hidden" name="delete_comment" value="<?php echo $comment['id']; ?>">
                                <button class="delete-btn" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</div> 

</body>
</html>
