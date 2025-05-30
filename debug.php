<?php
//vote.php

session_start();
include('db_connect.php');


$poll_id = 4;

// Generate a CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}




// Fetch poll question and options
$sql = "SELECT question FROM polls WHERE poll_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $poll_id);
$stmt->execute();
$result = $stmt->get_result();
$poll = $result->fetch_assoc();

$sql = "SELECT option_id, option_text FROM options WHERE poll_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $poll_id);
$stmt->execute();
$optionsResult = $stmt->get_result();
$options = [];
while ($row = $optionsResult->fetch_assoc()) {
    $options[] = $row;
}

$stmt->close();
$conn->close();




?>













<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vote on Poll</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Vote in Poll</h1>
        <?php include('nav.php'); display_nav(1); ?>
    </header>
    <main>
    <h2 id="poll-question">Poll Question</h2>
    <p id="poll-creator">Created by: </p>
    <img id="profile-pic" src="" alt="Profile Picture" width="100"><br>
    <ul id="options-container"></ul> <!-- Placeholder for options -->
    <script>
        
        function fetchPollContent(pollId) {
            fetch("get_poll_content.php?poll_id=${pollId}") // Adjust the URL as needed
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json(); // Parse JSON response
                })
                .then(data => {
                    console.log(data); // Log the response data for debugging
                    if (data.success) {
                        // Update the HTML with poll details
                        document.getElementById('poll-question').innerText = data.poll.question;
                        document.getElementById('poll-creator').innerText = `Created by: ${data.poll.creator}`;
                        
                        const profilePic = document.getElementById('profile-pic');
                        if (data.poll.profile_pic) {
                            profilePic.src = data.poll.profile_pic;
                            profilePic.alt = "Profile Picture";
                        } else {
                            profilePic.style.display = 'none'; // Hide if no picture
                        }

                        // Update options
                        const optionsContainer = document.getElementById('options-container');
                        optionsContainer.innerHTML = ''; // Clear existing options
                        data.poll.options.forEach(option => {
                            const optionElement = document.createElement('li');
                            optionElement.innerHTML = `${option.option_text.trim()}: ${option.vote_count} votes`; // Trim whitespace
                            optionsContainer.appendChild(optionElement);
                        });
                    } else {
                        console.error(data.error);
                        alert(data.error); // Show error message
                    }
                })
                .catch(error => {
                    console.error('Error fetching poll content:', error);
                    alert('An error occurred while fetching poll content.');
                });
        }

        // Call the function with the desired poll ID
        document.addEventListener('DOMContentLoaded', function() {
            const pollId = <?php echo json_encode($poll_id); ?>; // Replace with the actual poll ID you want to fetch
            console.log('Poll ID:', pollId);
            fetchPollContent(pollId);

             // Set interval to fetch comments every 1 seconds (1000 milliseconds)
            setInterval(() => {
                fetchPollContent(pollId);
            }, 1000000);
        });
    </script>




    </main>
    <?php include('footer.php'); ?>
    
    <!-- Popup Modals -->
    <div id="loginPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <span class="close" onclick="closePopup()">&times;</span>
            <div class="message">You must be logged in to vote.</div>
            <div class="message">Do you have an account?</div>
            <button class="button" onclick="location.href='login.php'">Log In</button>
            <div class="message">If not, you can register here:</div>
            <button class="button" onclick="location.href='register.php'">Register</button>
        </div>
    </div>
    
    <div id="successPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <span class="close" onclick="closePopup()">&times;</span>
            <div class="message">Vote recorded successfully!</div>
            <button class="button" onclick="location.href='view_results.php?poll_id=<?php echo $poll_id; ?>'">View Results</button>
        </div>
    </div>
    
    <script>
        function closePopup() {
            document.getElementById('loginPopup').style.display = 'none';
            document.getElementById('successPopup').style.display = 'none';
        }
        
        // Optional check for login status before form submission.
        function checkLoginStatus() {
            <?php if (!isset($_SESSION['user_id'])): ?>
                document.getElementById('loginPopup').style.display = 'block';
                return false;
            <?php else: ?>
                return true;
            <?php endif; ?>
        }
        
        // Show success popup if vote was successful
        <?php if (isset($_SESSION['vote_successful']) && $_SESSION['vote_successful']): ?>
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById('successPopup').style.display = 'block';
            });
            <?php unset($_SESSION['vote_successful']); ?>
        <?php endif; ?>
    </script>
</body>
</html>