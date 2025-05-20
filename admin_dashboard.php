<?php
session_start();
require_once 'config.php';

function check_admin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !$user['is_admin']) {
        http_response_code(403);
        echo "<h1>Access denied. Admins only.</h1>";
        exit;
    }
}

check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if (in_array($action, ['delete', 'deactivate', 'reactivate']) && $id > 0) {
        if ($type === 'user') {
            if ($action === 'delete') {
                if ($id === $_SESSION['user_id']) {
                    $message = "You cannot delete yourself.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "User deleted.";
                }
            } elseif ($action === 'deactivate') {
                $stmt = $pdo->prepare("UPDATE users SET active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $message = "User deactivated.";
            } elseif ($action === 'reactivate') {
                $stmt = $pdo->prepare("UPDATE users SET active = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $message = "User reactivated.";
            }
        } elseif ($type === 'post') {
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Post deleted.";
            } elseif ($action === 'deactivate') {
                $stmt = $pdo->prepare("UPDATE posts SET active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Post deactivated.";
            } elseif ($action === 'reactivate') {
                $stmt = $pdo->prepare("UPDATE posts SET active = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Post reactivated.";
            }
        }
    }
}

$users = $pdo->query("SELECT id, username, email, is_admin, active FROM users")->fetchAll(PDO::FETCH_ASSOC);
$posts = $pdo->query("SELECT id, title, author_id, active FROM posts")->fetchAll(PDO::FETCH_ASSOC);

$postAuthors = [];
foreach ($posts as $post) {
    $postAuthors[$post['id']] = null;
}
if (count($posts) > 0) {
    $authorIds = array_unique(array_column($posts, 'author_id'));
    $placeholders = implode(',', array_fill(0, count($authorIds), '?'));
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id IN ($placeholders)");
    $stmt->execute($authorIds);
    $authors = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
    $authors = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-weight: 400; 
    margin: 0;
    background: #f9f9f9;
    color: #444;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

h1, h2 {
    text-align: center;
    color: #4b3f35;
    font-weight: 500;
    margin-bottom: 30px;
}

table {
    border-collapse: collapse;
    width: 100%;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 40px;
}

th, td {
    padding: 12px 18px;
    text-align: left;
    font-weight: 400;
    font-size: 18px;
    border-bottom: 1px solid #eee;
}

th {
    background-color: #fafafa;
    color: #333;
     font-weight: bold;
   
}

.actions button {
    background-color: #6c5c47;
    border: none;
    color: white;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 13px;
    margin-right: 5px;
    cursor: pointer;
}

.actions button.delete { background-color: #d9534f; }
.actions button.deactivate { background-color: #f0ad4e; }
.actions button.reactivate { background-color: #5cb85c; }

.actions button:hover {
    opacity: 0.85;
}

.status-active { color: #5cb85c; }
.status-inactive { color: #d9534f; }
.admin-yes { color: #337ab7; }

.message {
    text-align: center;
    background-color: #e9ffe8;
    color: #3c763d;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.footer {
    background-color: #6c5c47;
    color: white;
    text-align: center;
    padding: 15px;
}

    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-container">

    <h1>Admin Dashboard</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>Users</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['is_admin'] ? '<span class="admin-yes">Yes</span>' : 'No' ?></td>
                <td>
                    <?= $user['active'] ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>' ?>
                </td>
                <td class="actions">
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <?php if ($user['active']): ?>
                            <form method="post">
                                <input type="hidden" name="action" value="deactivate">
                                <input type="hidden" name="type" value="user">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                <button class="deactivate">Deactivate</button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="action" value="reactivate">
                                <input type="hidden" name="type" value="user">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                <button class="reactivate">Reactivate</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="type" value="user">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                            <button class="delete">Delete</button>
                        </form>
                    <?php else: ?>
                        <em>Self</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Posts</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Title</th><th>Author</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($posts as $post): ?>
            <tr>
                <td><?= htmlspecialchars($post['id']) ?></td>
                <td><?= htmlspecialchars($post['title']) ?></td>
                <td><?= htmlspecialchars($authors[$post['author_id']] ?? 'Unknown') ?></td>
                <td>
                    <?= $post['active'] ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>' ?>
                </td>
                <td class="actions">
                    <?php if ($post['active']): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="type" value="post">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($post['id']) ?>">
                            <button class="deactivate">Deactivate</button>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="reactivate">
                            <input type="hidden" name="type" value="post">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($post['id']) ?>">
                            <button class="reactivate">Reactivate</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('Delete this post? This cannot be undone.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="type" value="post">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($post['id']) ?>">
                        <button class="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
