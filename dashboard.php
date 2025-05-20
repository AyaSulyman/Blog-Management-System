<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

$page_title = "Dashboard";
include 'header.php'; 
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
<div class="large-container" >

    <h2 class='h2-con'>Your Blog Posts</h2>
    <div class='button-con'>
        <button class='create-btn'><a href="create_post.php">+ New Post</a></button>
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
            <td >
                <button><a style="padding-bottom:200px" href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a></button>
                <button>
                    <a style="padding-top:-100px" href="javascript:void(0);" onclick="confirmDelete(<?php echo $post['id']; ?>)">Delete</a>
                </button> 
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
<div style="margin-top:425px">
  <?php
$stmt->close();
$conn->close();
include 'footer.php'; 
?>
</div>
</div>


<script>
function confirmDelete(postId) {
    if (confirm("Are you sure you want to delete this post?")) {
        window.location.href = "delete_post.php?id=" + postId;
    }
}
</script>

  
</body>

</html>

