<?php
session_start();
include('db_connect.php');
//vote_page/get_poll_content.php
// Get the poll ID from the request

$poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;


if ($poll_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Poll ID']);
    exit;
}

// Fetch poll question, creator, and profile picture.
$sql = "SELECT polls.question, users.nickname AS creator, users.profile_pic
        FROM polls
        JOIN users ON polls.user_id = users.user_id
        WHERE polls.poll_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed (poll details): " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again later.']);
    exit;
}

$stmt->bind_param("i", $poll_id);
if (!$stmt->execute()) {
    error_log("SQL Error: " . $stmt->error);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again later.']);
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $poll = $result->fetch_assoc();

    // Prepare the response array
    $response = [
        'success' => true,
        'poll' => [
            'question' => htmlspecialchars($poll['question'], ENT_QUOTES, 'UTF-8'),
            'creator' => htmlspecialchars($poll['creator'], ENT_QUOTES, 'UTF-8'),
            'profile_pic' => htmlspecialchars($poll['profile_pic'], ENT_QUOTES, 'UTF-8')
        ]
    ];

    // Fetch poll options with their vote counts.
    $sql = "SELECT options.option_text, COUNT(votes.option_id) AS vote_count
            FROM options
            LEFT JOIN votes ON options.option_id = votes.option_id
            WHERE options.poll_id = ?
            GROUP BY options.option_id";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed (options): " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again later.']);
        exit;
    }
    $stmt->bind_param("i", $poll_id);
    if (!$stmt->execute()) {
        error_log("SQL Error: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again later.']);
        exit;
    }

    $result_options = $stmt->get_result();
    $options = [];
    while ($row = $result_options->fetch_assoc()) {
        $options[] = [
            'option_text' => htmlspecialchars($row['option_text'], ENT_QUOTES, 'UTF-8'),
            'vote_count' => intval($row['vote_count'])
        ];
    }

    $response['poll']['options'] = $options;

    // Return the JSON response
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => "poll content failed"]);
}

$stmt->close();
$conn->close();
?>