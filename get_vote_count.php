<?php
session_start();
include('db_connect.php');

// Get the poll ID from the request
$poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;

// Fetch votes display
$sql = "SELECT votes.option_id, votes.user_id, votes.voted_at, users.nickname
        FROM votes
        JOIN users ON votes.user_id = users.user_id
        WHERE votes.poll_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $poll_id);
    $stmt->execute();
    $votesResult = $stmt->get_result();
    $total_votes = $votesResult->num_rows; // Total number of votes
    $votes = [];

    while ($vote = $votesResult->fetch_assoc()) {
        $votes[] = $vote; // Collect each vote
    }

    $stmt->close();
} else {
    $total_votes = 0;
    $votes = [];
}

// Prepare the response
$response = [
    'total_votes' => $total_votes,
    'votes' => $votes
];

// Return the content as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>