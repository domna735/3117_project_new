<?php
//vote.php

session_start();
include('db_connect.php');

// Validate poll_id from URL
if (!isset($_GET['poll_id'])) {
    header("Location: error.php?error=InvalidPollId");
    exit;
}
$poll_id = intval($_GET['poll_id']);
if ($poll_id <= 0) {
    header("Location: error.php?error=InvalidPollId");
    exit;
}

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
            fetch('get_poll_content.php?poll_id=' + pollId) // Adjust the URL as needed
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
            fetchPollContent(pollId);

             // Set interval to fetch comments every 1 seconds (1000 milliseconds)
            setInterval(() => {
                fetchPollContent(pollId);
            }, 1000);
        });
    </script>


    <form id="voteForm" action="vote_process.php" method="POST">
        <!-- Include CSRF token and poll_id -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
        <?php foreach ($options as $option): ?>
            <label>
                <input type="radio" name="option_id" value="<?php echo intval($option['option_id']); ?>" required>
                <?php echo htmlspecialchars($option['option_text'], ENT_QUOTES, 'UTF-8'); ?>
            </label><br>
        <?php endforeach; ?>
        <button type="submit">Vote</button>
    </form>

    <div id="vote-result"></div> <!-- Placeholder for vote result -->

    <script>
        document.getElementById('voteForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            const formData = new FormData(this); // Create FormData object from the form

            // Send the AJAX request
            fetch('vote_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json(); // Parse JSON response
            })
            .then(data => {
                if (data.success) {
                    // Display success message or update the vote result
                    document.getElementById('vote-result').innerHTML = `<p>Thank you for voting!</p>`;
                    // Optionally, you can fetch updated vote counts here
                } else {
                    // Handle error (e.g., show error message)
                    document.getElementById('vote-result').innerHTML = `<p>Error: ${data.error}</p>`;
                }
            })
            .catch(error => {
                console.error('Error submitting vote:', error);
                document.getElementById('vote-result').innerHTML = `<p>An unexpected error occurred.</p>`;
            });
        });
    </script>

























        <h3 id="total-votes">Total Votes:</h3>
        <ul id="votes-container"></ul> <!-- Placeholder for votes -->

        <script>
    function fetchVotes(pollId) {
        fetch('get_vote_count.php?poll_id='  + pollId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json(); // Parse JSON response
            })
            .then(data => {
                if (data) {
                    // Update the total votes count
                    document.getElementById('total-votes').innerText = `Total Votes: ${data.total_votes}`;

                    // Update the list of users who voted
                    const votesContainer = document.getElementById('votes-container');
                    votesContainer.innerHTML = ''; // Clear existing votes
                    data.votes.forEach(vote => {
                        const voteElement = document.createElement('li');
                        voteElement.innerText = `${vote.nickname} voted on ${new Date(vote.voted_at).toLocaleString()}`;
                        votesContainer.appendChild(voteElement);
                    });
                } else {
                    console.error('No data returned');
                }
            })
            .catch(error => {
                console.error('Error fetching votes:', error);
            });
    }

    // Call the function with the desired poll ID
    document.addEventListener('DOMContentLoaded', function() {
        const pollId = <?php echo json_encode($poll_id); ?>;; // Replace with the actual poll ID you want to fetch
        fetchVotes(pollId);
        
        // Set interval to fetch comments every 1 seconds (1000 milliseconds)
        setInterval(() => {
                fetchVotes(pollId);
                }, 1000);
            });
</script>
        
        
        
        <h3 id="comments-count">Comments</h3>
        <div id="comments-container"></div> <!-- Placeholder for comments -->
        
        <script>
        // Function to fetch comments
        function fetchComments(pollId) {
            fetch('get_vote_comment.php?poll_id=' + pollId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json(); // Parse JSON response
                })
                .then(data => {
                    const commentsContainer = document.getElementById('comments-container');
                    commentsContainer.innerHTML = ''; // Clear existing comments

                    // Update comments count
                    const commentsCount = document.getElementById('comments-count');
                    commentsCount.textContent = `Comments (${data.total_comments})`;

                    // Construct the comments HTML
                    if (data.total_comments > 0) {
                        data.comments.forEach(comment => {
                            const commentElement = document.createElement('p');
                            commentElement.innerHTML = `<strong>${comment.nickname}</strong>: ${comment.comment_text} <em>(${comment.created_at})</em>`;
                            commentsContainer.appendChild(commentElement);
                        });
                    } else {
                        commentsContainer.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching comments:', error);
                });
        }

        // Call the function with the desired poll ID
        document.addEventListener('DOMContentLoaded', function() {
            const pollId = <?php echo json_encode($poll_id); ?>; // Ensure poll_id is available
            fetchComments(pollId); // Initial fetch

            // Set interval to fetch comments every 1 seconds (1000 milliseconds)
            setInterval(() => {
                fetchComments(pollId);
            }, 1000);
        });
    </script>




        
        <?php if (isset($_SESSION['user_id'])): ?>
    
    <h3>Leave a Comment</h3>
<form id="comment-form">
    <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <textarea name="comment_text" required></textarea><br>
    <button type="submit">Submit Comment</button>
</form>




<script>
    document.getElementById('comment-form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(this); // Create FormData object from the form

        // Send the AJAX request
        fetch('comment_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json(); // Parse JSON response
        })
        .then(data => {
            if (data.success) {
                // Clear the textarea
                this.reset();
                // Fetch the updated comments
                fetchComments(formData.get('poll_id'));
            } else {
                // Redirect to the error page if there is an error
                window.location.href = `error.php?error=${encodeURIComponent(data.error)}`;
            }
        })
        .catch(error => {
            console.error('Error submitting comment:', error);
            // Optionally redirect to a generic error page
            window.location.href = 'error.php?error=An unexpected error occurred';
        });
    });

    

</script>
<?php else: ?>
    <p><a href="login.php">Log in</a> to leave a comment.</p>
<?php endif; ?>
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