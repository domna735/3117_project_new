<?php
session_start();
include('db_connect.php');

// Get the poll ID from the request
$poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;

// Fetch comments for the poll
$sql = "SELECT comments.comment_text, users.nickname, comments.created_at
        FROM comments
        JOIN users ON comments.user_id = users.user_id
        WHERE comments.poll_id = ?
        ORDER BY comments.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $poll_id);
$stmt->execute();
$commentsResult = $stmt->get_result();

$comments = [];
while ($comment = $commentsResult->fetch_assoc()) {
    $comments[] = $comment;
}
$stmt->close();

// Prepare the response
$response = [
    'total_comments' => count($comments),
    'comments' => $comments
];

// Return the content as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>