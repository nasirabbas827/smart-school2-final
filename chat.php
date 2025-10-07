<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || !in_array($_SESSION["role"], ["teacher", "student"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["id"];
$user_role = $_SESSION["role"];

/* Get class_id based on role */
if ($user_role === "student") {
    $stmt = $conn->prepare("SELECT class_id FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $class_id = $res ? $res['class_id'] : 0;
} else {
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE teacher_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $class_id = $res ? $res['class_id'] : 0;
}

if (!$class_id) {
    die("No associated class found.");
}

/* Handle message send */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if ($msg !== "") {
        $stmt = $conn->prepare("
            INSERT INTO class_messages (class_id, sender_id, sender_role, message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $class_id, $user_id, $user_role, $msg);
        $stmt->execute();
    }
    // Use POST-Redirect-GET pattern to prevent form resubmission
    header("Location: chat.php"); 
    exit();
}

/* Fetch messages */
$stmt = $conn->prepare("
    SELECT m.*, 
      CASE 
        WHEN m.sender_role='student' THEN CONCAT(s.first_name, ' ', s.last_name)
        WHEN m.sender_role='teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
        ELSE 'Unknown'
      END as sender_name
    FROM class_messages m
    LEFT JOIN students s ON m.sender_role='student' AND m.sender_id=s.student_id
    LEFT JOIN teachers t ON m.sender_role='teacher' AND m.sender_id=t.teacher_id
    WHERE m.class_id = ?
    ORDER BY m.sent_at ASC
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$messages = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Class Chat</title>
    <link rel="stylesheet" href="./CSS/style.css">
    <style>
        /* --- Chat Container (The main box) --- */
        .chat-window {
            border: 1px solid #ddd;
            background-color: #e5ddd5; /* Light chat background */
            max-width: 600px;
            height: 400px; /* Increased height */
            overflow-y: scroll;
            padding: 10px;
            margin: 20px auto;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        
        /* --- Individual Message Styling --- */
        .message-row {
            display: flex;
            margin-bottom: 10px;
            /* Remove default <hr> */
            border-bottom: none !important; 
        }

        .message-bubble {
            max-width: 75%;
            padding: 8px 12px;
            border-radius: 18px;
            line-height: 1.4;
            box-shadow: 0 1px 0.5px rgba(0, 0, 0, 0.13);
            position: relative;
            word-wrap: break-word;
        }

        /* Message sent by the CURRENT USER (right alignment) */
        .is-user {
            justify-content: flex-end; /* Pushes the bubble to the right */
        }
        .is-user .message-bubble {
            background-color: #dcf8c6; /* Greenish background */
            color: #000;
            margin-left: auto; /* Pushes it right */
        }

        /* Message sent by OTHERS (left alignment) */
        .is-other {
            justify-content: flex-start; /* Pushes the bubble to the left */
        }
        .is-other .message-bubble {
            background-color: #fff; /* White background */
            color: #000;
            margin-right: auto; /* Pushes it left */
        }
        
        /* Teacher messages get a distinct color */
        .is-other.teacher-message .message-bubble {
            background-color: #b0e0e6; /* Pale Turquoise for Teacher */
        }


        /* Sender Name and Role Tag */
        .sender-info {
            font-size: 0.8em;
            color: #4a4a4a;
            margin-bottom: 2px;
            font-weight: bold;
        }
        .role-tag {
            font-size: 0.75em;
            color: #666;
            font-weight: normal;
            margin-left: 5px;
            padding: 1px 5px;
            border-radius: 3px;
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Timestamp */
        .timestamp {
            display: block;
            font-size: 0.7em;
            color: #606060;
            text-align: right;
            margin-top: 5px;
        }
        
        /* --- Input Form Styling --- */
        .chat-form {
            max-width: 600px;
            margin: 10px auto;
            display: flex;
            gap: 10px;
            padding: 10px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .chat-form textarea {
            flex-grow: 1;
            resize: none;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .chat-form button {
            padding: 10px 15px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .chat-form button:hover {
            background-color: #005bb5;
        }
        
        .no-messages {
            text-align: center;
            color: #666;
            margin-top: 50px;
        }
        
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<h2 style="text-align: center; color: #333; margin-bottom: 0;">Class Chat</h2>

<div class="chat-window">
<?php if ($messages->num_rows > 0): ?>
    <?php while($row = $messages->fetch_assoc()): 
        // Determine if the message is from the current user
        $is_user_message = ($row['sender_id'] == $user_id && $row['sender_role'] == $user_role);
        
        // Determine the main CSS class for alignment
        $alignment_class = $is_user_message ? 'is-user' : 'is-other';
        
        // Add a specific class for teacher messages (if it's not the current user)
        $role_class = ($row['sender_role'] === 'teacher' && !$is_user_message) ? 'teacher-message' : '';
    ?>
        <div class="message-row <?php echo $alignment_class; ?> <?php echo $role_class; ?>">
            <div class="message-bubble">
                <?php if (!$is_user_message): ?>
                    <div class="sender-info">
                        <?php echo htmlspecialchars($row['sender_name']); ?>
                        <span class="role-tag"><?php echo strtoupper($row['sender_role']); ?></span>
                    </div>
                <?php endif; ?>
                
                <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                
                <span class="timestamp"><?php echo date("H:i A | M d", strtotime($row['sent_at'])); ?></span>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p class="no-messages">Start the conversation! No messages yet.</p>
<?php endif; ?>
</div>

<form action="" method="post" class="chat-form">
    <textarea name="message" rows="3" placeholder="Type your message..." required></textarea>
    <button type="submit">Send</button>
</form>

<script>
    // Scroll chat window to the bottom on load
    document.addEventListener("DOMContentLoaded", function() {
        var chatWindow = document.querySelector(".chat-window");
        chatWindow.scrollTop = chatWindow.scrollHeight;
    });
</script>

</body>
</html>