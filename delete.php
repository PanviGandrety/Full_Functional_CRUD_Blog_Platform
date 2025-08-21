<?php
require 'db.php';
require 'functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request'); }
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { header('Location: dashboard.php'); exit; }

    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) { header('Location: dashboard.php'); exit; }
    $stmt->bind_result($owner_id);
    $stmt->fetch();
    $stmt->close();

    $user_id = (int)($_SESSION['user_id']);
    $role    = $_SESSION['role'] ?? 'user';

    if ($role === 'admin' || (int)$owner_id === $user_id) {
        $del = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
    } else {
        http_response_code(403); exit('Forbidden');
    }
}

header('Location: dashboard.php');
exit;
