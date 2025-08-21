<?php
require 'db.php';
require 'functions.php';
$error = '';
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['count' => 0, 'ts' => 0];
}
$la =& $_SESSION['login_attempts'];
$locked = ($la['count'] >= 5 && (time() - $la['ts']) < 300);
if ($locked) {
    $error = 'Too many attempts. Try again in a few minutes.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $username     = trim($_POST['username'] ?? '');
        $password_raw = $_POST['password'] ?? '';

        if ($username === '' || $password_raw === '') {
            $error = 'Enter username and password.';
        } else {
            $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $hashed, $role);
                $stmt->fetch();
                if (password_verify($password_raw, $hashed)) {
                    session_regenerate_id(true);
                    $_SESSION['user_id']  = $id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role']     = $role;
                    // reset throttle
                    $la = ['count' => 0, 'ts' => 0];
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid credentials.';
                    $la['count']++;
                    $la['ts'] = time();
                }
            } else {
                $error = 'Invalid credentials.';
                $la['count']++;
                $la['ts'] = time();
            }
            $stmt->close();
        }
    }
}
$token = csrf_token();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:520px;">
  <div class="card p-4 shadow-sm">
    <h3 class="mb-3 text-center">Login</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
      <div class="mb-2">
        <input name="username" class="form-control" placeholder="Username" required
               value="<?php echo e($_POST['username'] ?? ''); ?>">
      </div>
      <div class="mb-2">
        <input name="password" type="password" class="form-control" placeholder="Password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit" <?php echo $locked ? 'disabled' : ''; ?>>Login</button>
    </form>
    <p class="mt-3 text-center">New user? <a href="register.php">Register</a></p>
  </div>
</div>
</body>
</html>
