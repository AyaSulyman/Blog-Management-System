<?php
session_start();


$conn = new mysqli("localhost", "root", "", "blog_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$title = $content = "";
$category_id = 0; 
$errors = [];

$categories_sql = "SELECT id, name FROM categories";
$categories_result = $conn->query($categories_sql);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $category_id = (int)$_POST["category_id"]; 

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = basename($_FILES['image']['name']);
        $file_size = $_FILES['image']['size'];
        $file_type = pathinfo($file_name, PATHINFO_EXTENSION);

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($file_type), $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
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
        $errors[] = "Title, content, and category must be filled.";
    }

    if (empty($errors)) {
        $author_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO posts (title, content, author_id, category_id, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $title, $content, $author_id, $category_id, $image_path);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Error creating post.";
        }
        $stmt->close();
    }
}


$page_title = "Create Post";
include 'header.php';
?>

<!-- HTML Content -->
<link rel="stylesheet" href="create.css">

<div class="small-container">

    <h2>Create New Post</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="create_post.php" class="createForm" enctype="multipart/form-data" >
        <label for="title">Title</label>
        <input type="text" name="title" id="title" required>

        <label for="content">Content</label>
        <textarea name="content" id="content" required></textarea>

        <label for="category_id">Select Category</label>
        <select style="height:25px;width:150px" name="category_id" id="category_id" required>
            <option value="">Select a category</option>
            <?php while ($category = $categories_result->fetch_assoc()): ?>
                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
            <?php endwhile; ?>
        </select><br>   

        <label style="margin-top:20px" for="image">Upload Image</label>
        <input style="margin-top:20px" type="file" name="image" id="image" accept="image/*">

        <button type="submit">Create Post</button>
    </form>

<div style="margin-top:60px;width:100%">
<?php
include 'footer.php';
$conn->close();
?>
</div>
</div>