<?php
require 'db.php';
require 'functions.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $username     = trim($_POST['username'] ?? '');
        $password_raw = $_POST['password'] ?? '';

        if (strlen($username) < 3 || strlen($username) > 100 || !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $errors[] = 'Username must be 3-100 chars and only letters, numbers, underscores.';
        }
        if (strlen($password_raw) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (empty($errors)) {
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = 'Username already taken.';
            } else {
                $hashed = password_hash($password_raw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
                $stmt->bind_param("ss", $username, $hashed);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Database error. Try again.';
                }
            }
        }
    }
}
$token = csrf_token();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:520px;">
  <div class="card p-4 shadow-sm">
    <h3 class="mb-3 text-center">Register</h3>

    <?php if ($success): ?>
      <div class="alert alert-success">Registered successfully. <a href="login.php">Login here</a>.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?php echo e($err); ?></div>
    <?php endforeach; endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
      <div class="mb-2">
        <input name="username" class="form-control" placeholder="Username" required
               pattern="[A-Za-z0-9_]{3,100}" title="3-100 chars"
               value="<?php echo e($_POST['username'] ?? ''); ?>">
      </div>
      <div class="mb-2">
        <input name="password" type="password" class="form-control" placeholder="Password" required minlength="6">
      </div>
      <button class="btn btn-primary w-100" type="submit">Register</button>
    </form>
    <p class="mt-3 text-center">Already registered? <a href="login.php">Login</a></p>
  </div>
</div>
</body>
</html>
