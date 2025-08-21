<?php
require 'db.php';
require 'functions.php';
require_login();

$token  = csrf_token();
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'user';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: dashboard.php'); exit; }

$stmt = $conn->prepare("SELECT user_id, title, content FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) { header('Location: dashboard.php'); exit; }
$stmt->bind_result($owner_id, $title, $content);
$stmt->fetch();
$stmt->close();

if (!($role === 'admin' || (int)$owner_id === (int)$user_id)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) $errors[] = 'Invalid request.';
    else {
        $new_title   = trim($_POST['title'] ?? '');
        $new_content = trim($_POST['content'] ?? '');
        if ($new_title === '' || strlen($new_title) > 255) $errors[] = 'Title invalid.';
        if ($new_content === '') $errors[] = 'Content required.';
        if (empty($errors)) {
            $u = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
            $u->bind_param("ssi", $new_title, $new_content, $id);
            $u->execute();
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Post</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width:800px;">
  <h3>Edit Post</h3>
  <?php if (!empty($errors)): foreach($errors as $e): ?>
    <div class="alert alert-danger"><?php echo e($e); ?></div>
  <?php endforeach; endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
    <div class="mb-2">
      <input name="title" class="form-control" value="<?php echo e($title); ?>" maxlength="255" required>
    </div>
    <div class="mb-2">
      <textarea name="content" class="form-control" rows="6" required><?php echo e($content); ?></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
