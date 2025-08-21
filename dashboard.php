<?php
require 'db.php';
require 'functions.php';
require_login();

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'] ?? 'user';

$errors  = [];
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title === '' || strlen($title) > 255) $errors[] = 'Title required (1-255 chars).';
        if ($content === '') $errors[] = 'Content required.';
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $title, $content);
            $stmt->execute();
            $success = 'Post created.';
        }
    }
}
 
$search = trim($_GET['search'] ?? '');
$limit  = 5;
$page   = max(1, (int)($_GET['page'] ?? 1));
 
if ($search !== '') {
    $like = "%$search%";
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE title LIKE ? OR content LIKE ?");
    $count_stmt->bind_param("ss", $like, $like);
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM posts");
}
$count_stmt->execute();
$count_stmt->bind_result($total_posts);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = (int)ceil(max(0, $total_posts) / $limit);
if ($total_pages < 1) $total_pages = 1;

if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;
if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT p.id, p.user_id, p.title, p.content, p.created_at, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.title LIKE ? OR p.content LIKE ?
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $like, $like, $limit, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT p.id, p.user_id, p.title, p.content, p.created_at, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$token = csrf_token();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Welcome, <?php echo e($username); ?> <?php if ($role === 'admin') echo '(Admin)'; ?></h4>
    <div>
      <?php if (is_admin()): ?>
        <a href="manage_users.php" class="btn btn-outline-secondary btn-sm me-2">Manage Users</a>
      <?php endif; ?>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>

  <?php if (!empty($errors)): foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?php echo e($err); ?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>

  <form method="get" class="mb-3">
    <div class="input-group">
      <input name="search" class="form-control" placeholder="Search posts..." value="<?php echo e($search); ?>">
      <button class="btn btn-primary" type="submit">Search</button>
    </div>
  </form>

  <div class="card mb-4">
    <div class="card-header">Create New Post</div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
        <input type="hidden" name="action" value="create">
        <div class="mb-2">
          <input name="title" class="form-control" placeholder="Title" required maxlength="255">
        </div>
        <div class="mb-2">
          <textarea name="content" class="form-control" rows="4" placeholder="Content" required></textarea>
        </div>
        <button class="btn btn-success" type="submit">Post</button>
      </form>
    </div>
  </div>

  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title"><?php echo e($row['title']); ?></h5>
        <p class="card-text"><?php echo nl2br(e($row['content'])); ?></p>
        <p class="card-text"><small class="text-muted">By <?php echo e($row['username']); ?> — <?php echo e($row['created_at']); ?></small></p>
        <div>
          <?php if ($role === 'admin' || (int)$row['user_id'] === (int)$user_id): ?>
            <a href="edit_post.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
            <form method="post" action="delete.php" style="display:inline-block;" onsubmit="return confirm('Delete this post?');">
              <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endwhile; ?>

  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>
</body>
</html>
