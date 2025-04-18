<?php
//comment_process.php
session_start();
include('db_connect.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Retrieve and validate input data
$poll_id = filter_input(INPUT_POST, 'poll_id', FILTER_VALIDATE_INT);
if ($poll_id === false || $poll_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Poll ID']);
    exit;
}

$comment_text = trim(filter_input(INPUT_POST, 'comment_text', FILTER_SANITIZE_STRING));
if (empty($comment_text)) {
    echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
    exit;
}

// Insert comment into the database
$user_id = $_SESSION['user_id'];
$sql = "INSERT INTO comments (poll_id, user_id, comment_text) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $poll_id, $user_id, $comment_text);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error inserting comment']);
}

$stmt->close();
$conn->close();
?>