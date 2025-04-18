<?php
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
$poll_id = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;
$option_id = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;

if ($poll_id <= 0 || $option_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Poll ID or Option ID']);
    exit;
}

// Check if the selected option exists for this poll
$sql = "SELECT option_id FROM options WHERE poll_id = ? AND option_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $poll_id, $option_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid vote option']);
    exit;
}

// Check if the user has already voted in this poll
$user_id = $_SESSION['user_id'];
$sql = "SELECT vote_id FROM votes WHERE poll_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $poll_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$has_voted = ($result->num_rows > 0);

if ($has_voted) {
    // Update existing vote
    $sql = "UPDATE votes SET option_id = ?, voted_at = CURRENT_TIMESTAMP WHERE poll_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $option_id, $poll_id, $user_id);
} else {
    // Insert new vote
    $sql = "INSERT INTO votes (poll_id, option_id, user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $poll_id, $option_id, $user_id);
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Vote submission failed']);
    exit;
}

echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>