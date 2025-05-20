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


$post_id = $_GET['id'] ?? null;
if (!$post_id || !is_numeric($post_id)) {
    die("Invalid post ID.");
}


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
$category_id = $post['category_id'];
$image_path = $post['image_path'];
$errors = [];


$categories_sql = "SELECT id, name FROM categories";
$categories_result = $conn->query($categories_sql);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $category_id = (int)$_POST["category_id"];

    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = basename($_FILES['image']['name']);
        $file_size = $_FILES['image']['size'];
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
        if ($file_size > 2 * 1024 * 1024) {
            $errors[] = "File size must be less than 2MB.";
        }

        if (empty($errors)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $image_path = $upload_dir . uniqid() . '.' . $file_type;
            move_uploaded_file($file_tmp, $image_path);
        }
    }

  
    if (empty($title) || empty($content) || $category_id <= 0) {
        $errors[] = "All fields are required.";
    }

  
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, category_id = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $title, $content, $category_id, $image_path, $post_id);

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
$page_title = "Edit Post";
include 'header.php';
?>

<link rel="stylesheet" href="create.css">

<div class="small-container">
    <h2>Edit Post</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="edit-form" enctype="multipart/form-data">
        <label for="title">Title</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" required>

        <label for="content">Content</label>
        <textarea name="content" id="content" required><?php echo htmlspecialchars($content); ?></textarea>

        <label for="category_id">Select Category</label>
        <select name="category_id" id="category_id" required>
            <option value="">Select a category</option>
            <?php while ($category = $categories_result->fetch_assoc()): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $category_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="image" style="margin-top:20px;">Upload New Image</label>
        <input type="file" name="image" id="image" accept="image/*">

        <?php if ($image_path): ?>
            <p style="margin-top:10px;">Current Image:</p>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Current Image" style="max-width: 200px; margin-bottom: 10px">
        <?php endif; ?>

        <button type="submit" style="margin-top:10px;">Update Post</button>
    </form>
    <div style="margin-top:105px;width:100%">
    <?php include 'footer.php'; ?>
        </div>
</div>


