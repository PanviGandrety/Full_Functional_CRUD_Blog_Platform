<?php
require 'db.php';
require 'functions.php';
require_login();
if (!is_admin()) { http_response_code(403); exit('Forbidden'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid'); }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
header('Location: manage_users.php');
exit;
    