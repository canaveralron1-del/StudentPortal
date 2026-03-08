<?php
require_once __DIR__ . '/db.php';
session_start();

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$sessionUser = isset($_SESSION['username']) ? trim($_SESSION['username']) : '';
$rows = [];
$error = null;

if ($email !== '') {
    try {
        // ensure messages table exists
        $mysqli->query("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            enrollment_id INT,
            email VARCHAR(255),
            subject VARCHAR(255),
            body TEXT,
            status VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if ($stmt = $mysqli->prepare('SELECT id, enrollment_id, subject, body, status, created_at FROM messages WHERE email = ? ORDER BY created_at DESC')) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        $error = $e->getMessage();
    }
}

// If user is logged in and no email was provided, show messages for their enrollments
if (empty($email) && $sessionUser !== '') {
    try {
        // ensure messages table exists
        $mysqli->query("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            enrollment_id INT,
            email VARCHAR(255),
            subject VARCHAR(255),
            body TEXT,
            status VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if ($stmt = $mysqli->prepare('SELECT id, enrollment_id, subject, body, status, created_at FROM messages WHERE enrollment_id IN (SELECT id FROM enrollments WHERE username = ?) ORDER BY created_at DESC')) {
            $stmt->bind_param('s', $sessionUser);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Your Messages</title>
  <link rel="stylesheet" href="design2.css">
    <style>
        body { font-family: Arial, sans-serif; background:#f2f4f8; margin:0; }
        nav { background:#1f2937; padding:12px; text-align:center; }
        nav a { color:white; margin:0 10px; text-decoration:none; padding:6px 12px; }
        nav a:hover { background:#111827; border-radius:6px; }
        .container{max-width:900px;margin:20px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border:1px solid #ddd;text-align:left}
        th{background:#1f2937;color:#fff}
        html, body {
  height: 100%;
  margin: 0;
}

body {
  display: flex;
  flex-direction: column;
}

main {
  flex: 1; /* pushes footer down */
}

footer {
  background: #1f2937;
  color: white;
  text-align: center;
  padding: 10px 0;
}
    </style>
</head>
<body>

    <main class="container">
        <?php $displayId = $email ? $email : ($sessionUser ? $sessionUser : '...'); ?>
        <h2>Messages for <?php echo esc($displayId) ?></h2>
        <p><a href="messages.html">Search another email</a> — <a href="enrollment.php">Enrollment</a></p>
        <p style="color:#111">Note: You only need the email address you used during enrollment. No Gmail sign-in is required.</p>

<?php if ($error): ?>
    <div style="background:#fee;color:#900;padding:10px;border:1px solid #fbb;margin-bottom:12px">DB error: <?php echo esc($error); ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['delete_notice'])): ?>
    <div style="background:#eef9ff;border:1px solid #bde4ff;padding:10px;margin-bottom:12px;color:#055160"><?php echo htmlspecialchars($_SESSION['delete_notice'], ENT_QUOTES); ?></div>
    <?php unset($_SESSION['delete_notice']); ?>
<?php endif; ?>

<?php if ($email === ''): ?>
    <p>Enter an email on the previous page to view messages.</p>
<?php else: ?>
    <?php if (count($rows) === 0): ?>
        <p>No messages found for this email.</p>
    <?php else: ?>
        <form method="post" action="delete_messages.php" id="deleteForm">
            <div style="margin-bottom:8px;">
                <?php if ($sessionUser !== ''): ?>
                    <button type="submit" onclick="return confirm('Delete selected messages?')" class="btn">Delete Selected</button>
                <?php else: ?>
                    <em>Log in to delete messages.</em>
                <?php endif; ?>
                <button type="button" id="selectToggle" class="btn" style="margin-left:12px;">Select all</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:48px">&nbsp;</th>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $m): ?>
                        <tr>
                            <td>
                                <?php if ($sessionUser !== ''): ?>
                                    <input type="checkbox" name="ids[]" value="<?php echo (int)$m['id']; ?>">
                                <?php else: ?>
                                    &nbsp;
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc($m['created_at']); ?></td>
                            <td><?php echo esc($m['subject']); ?></td>
                            <td><pre style="white-space:pre-wrap;margin:0;padding:0;border:0;background:transparent"><?php echo esc($m['body']); ?></pre></td>
                            <td><?php echo esc($m['status']); ?></td>
                            <td style="width:120px;text-align:center">
                                <?php if ($sessionUser !== ''): ?>
                                    <form method="post" action="delete_messages.php" onsubmit="return confirm('Delete this message?')" style="display:inline">
                                        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                        <button type="submit" class="btn">Delete</button>
                                    </form>
                                <?php else: ?>
                                    &nbsp;
                                <?php endif; ?>
                            </td>
                        </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <script>
            (function(){
                var toggle = document.getElementById('selectToggle');
                var form = document.getElementById('deleteForm');
                var all = false;
                toggle && toggle.addEventListener('click', function(e){
                    e.preventDefault();
                    var checkboxes = form.querySelectorAll('input[type="checkbox"][name="ids[]"]');
                    all = !all;
                    checkboxes.forEach(function(cb){ cb.checked = all; });
                    toggle.textContent = all ? 'Unselect all' : 'Select all';
                });
            })();
        </script>
    <?php endif; ?>
<?php endif; ?>
  </main>
  <footer>© 2025 Student Enrollment System</footer>
</body>
</html>
