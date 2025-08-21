<?php
require 'db.php';
require 'functions.php';
require_login();
if (!is_admin()) { http_response_code(403); exit('Forbidden'); }

$users = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manage Users</h3>
    <a href="dashboard.php" class="btn btn-secondary btn-sm">Back</a>
  </div>
  <table class="table table-striped table-bordered">
    <thead class="table-light">
      <tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php while ($u = $users->fetch_assoc()): ?>
        <tr>
          <td><?php echo e($u['id']); ?></td>
          <td><?php echo e($u['username']); ?></td>
          <td><?php echo e($u['role']); ?></td>
          <td><?php echo e($u['created_at']); ?></td>
          <td>
            <?php if ($u['role'] !== 'admin'): ?>
              <form method="post" action="change_role.php" style="display:inline-block;">
                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <button name="promote" class="btn btn-sm btn-outline-success">Make Admin</button>
              </form>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
