<?php
require_once __DIR__ . '/db.php';
session_start();

// get enrollment id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$return_status = isset($_GET['return_status']) ? $_GET['return_status'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : ''; // 'accept' or 'reject' from direct link
$fullname = '';
$email = '';
$current_status = '';
$error = '';

if ($id <= 0) {
    $_SESSION['admin_notice'] = 'Invalid enrollment selected for messaging.';
    header('Location: admin.php');
    exit;
}

// fetch enrollment details including status
if ($q = $mysqli->prepare('SELECT fullname, email, status FROM enrollments WHERE id = ?')) {
    $q->bind_param('i', $id);
    $q->execute();
    $res = $q->get_result();
    if ($row = $res->fetch_assoc()) {
        $fullname = $row['fullname'];
        $email = $row['email'];
        $current_status = $row['status'];
    } else {
        $_SESSION['admin_notice'] = 'Enrollment not found.';
        header('Location: admin.php');
        exit;
    }
    $q->close();
} else {
    error_log('admin_message: failed prepare select: ' . $mysqli->error);
    $_SESSION['admin_notice'] = 'Database error.';
    header('Location: admin.php');
    exit;
}

// If action parameter is provided (coming from direct accept/reject link), set default message
$default_subject = '';
$default_body = '';

if ($action === 'accept' || $current_status === 'accepted') {
    $default_subject = '🎉 Congratulations! Your BSIT Enrollment has been ACCEPTED';
    $default_body = "Dear " . htmlspecialchars($fullname) . ",\n\n" .
                   "We are pleased to inform you that your enrollment in the Bachelor of Science in Information Technology (BSIT) program at ASCOT has been **ACCEPTED**!\n\n" .
                   "**Enrollment Details:**\n" .
                   "- Program: BSIT\n" .
                   "- Status: ✅ Accepted\n\n" .
                   "**Next Steps:**\n" .
                   "1. Complete your tuition payment at the accounting office\n" .
                   "2. Submit remaining requirements (if any)\n" .
                   "3. Attend the orientation session\n" .
                   "4. Check your schedule for classes\n\n" .
                   "**Important Dates:**\n" .
                   "- Payment Deadline: 1 week from today\n" .
                   "- Orientation: Check announcement board\n" .
                   "- Classes Start: August 4, 2025\n\n" .
                   "You can view your enrollment status at any time by visiting the student portal.\n\n" .
                   "Welcome to ASCOT! We look forward to seeing you on campus.\n\n" .
                   "Best regards,\n" .
                   "ASCOT Admissions Office\n" .
                   "admissions@ascot.edu.ph\n" .
                   "(042) 123-4567";
} elseif ($action === 'reject' || $current_status === 'rejected') {
    $default_subject = 'Regarding Your BSIT Enrollment Application';
    $default_body = "Dear " . htmlspecialchars($fullname) . ",\n\n" .
                   "Thank you for your interest in the Bachelor of Science in Information Technology (BSIT) program at ASCOT.\n\n" .
                   "After careful review of your application, we regret to inform you that your enrollment has been **NOT ACCEPTED** at this time.\n\n" .
                   "**Reason for Decision:**\n" .
                   "Please visit the admissions office for a detailed review of your application.\n\n" .
                   "**Options Available:**\n" .
                   "1. You may reapply in the next enrollment period\n" .
                   "2. Consider alternative programs at ASCOT\n" .
                   "3. Schedule a consultation with our admissions counselor\n\n" .
                   "**Next Enrollment Period:**\n" .
                   "The next enrollment period will open on June 1, 2025.\n\n" .
                   "We encourage you to review the admission requirements and ensure all documents are complete for your next application.\n\n" .
                   "If you have questions about this decision, please contact the admissions office.\n\n" .
                   "Best regards,\n" .
                   "ASCOT Admissions Office\n" .
                   "admissions@ascot.edu.ph\n" .
                   "(042) 123-4567";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Message Student — ASCOT Admin</title>
<style>
    /* Home Page Color Scheme */
    :root {
        --brand: #0f1724;        /* dark navy */
        --accent: #0ea5a4;       /* teal */
        --accent-light: #38bdf8; /* light teal/blue */
        --muted: #6b7280;        /* gray */
        --light-gray: #eef2f6;
        --card: #ffffff;
        --border: rgba(15, 23, 36, 0.08);
        --shadow: rgba(15, 23, 36, 0.1);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        background: linear-gradient(180deg, #f3f6f9 0%, var(--light-gray) 100%);
        color: var(--brand);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        line-height: 1.45;
        padding: 20px;
        min-height: 100vh;
    }

    .container {
        max-width: 800px;
        margin: 40px auto;
        background: var(--card);
        padding: 32px;
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: 0 12px 32px var(--shadow);
    }

    h2 {
        color: var(--brand);
        margin-bottom: 24px;
        font-size: 24px;
        font-weight: 600;
        padding-bottom: 16px;
        border-bottom: 2px solid rgba(14, 165, 164, 0.1);
    }

    .student-info {
        background: rgba(14, 165, 164, 0.05);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        border: 1px solid rgba(14, 165, 164, 0.1);
    }

    .student-info h3 {
        color: var(--brand);
        margin-bottom: 12px;
        font-size: 16px;
        font-weight: 600;
    }

    .student-info p {
        color: var(--muted);
        margin: 8px 0;
        font-size: 14px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-left: 12px;
    }

    .status-accepted {
        background: rgba(34, 197, 94, 0.1);
        color: #166534;
    }

    .status-rejected {
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
    }

    .status-ongoing {
        background: rgba(234, 179, 8, 0.1);
        color: #92400e;
    }

    .quick-templates {
        background: rgba(15, 23, 36, 0.03);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        border: 1px solid var(--border);
    }

    .quick-templates h3 {
        color: var(--brand);
        margin-bottom: 16px;
        font-size: 16px;
        font-weight: 600;
    }

    .template-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .template-btn {
        background: var(--brand);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .template-btn:hover {
        background: #1a2436;
        transform: translateY(-1px);
    }

    .template-btn.accept {
        background: linear-gradient(90deg, var(--accent), var(--accent-light));
    }

    .template-btn.reject {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }

    label {
        display: block;
        margin-bottom: 8px;
        color: var(--brand);
        font-weight: 500;
        font-size: 14px;
        margin-top: 20px;
    }

    input[type="text"],
    textarea {
        width: 100%;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-family: inherit;
        font-size: 14px;
        background: white;
        transition: all 0.2s;
        margin-bottom: 8px;
    }

    input[type="text"]:focus,
    textarea:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.1);
    }

    textarea {
        min-height: 200px;
        resize: vertical;
        line-height: 1.6;
    }

    .form-actions {
        display: flex;
        gap: 16px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid var(--border);
    }

    .btn {
        padding: 14px 24px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        font-family: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--brand) 0%, #1a2436 100%);
        color: white;
        box-shadow: 0 8px 24px rgba(15, 23, 36, 0.15);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(15, 23, 36, 0.2);
        background: linear-gradient(135deg, #1a2436 0%, var(--brand) 100%);
    }

    .btn-secondary {
        background: transparent;
        color: var(--brand);
        border: 1px solid var(--border);
    }

    .btn-secondary:hover {
        background: rgba(15, 23, 36, 0.03);
    }

    .notice {
        background: rgba(14, 165, 164, 0.1);
        border: 1px solid rgba(14, 165, 164, 0.2);
        color: var(--accent);
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-size: 14px;
    }

    .error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #991b1b;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .container {
            padding: 24px;
            margin: 20px auto;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .template-buttons {
            flex-direction: column;
        }
        
        .template-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<script>
    function loadTemplate(type) {
        if (type === 'accept') {
            document.getElementById('subject').value = '🎉 Congratulations! Your BSIT Enrollment has been ACCEPTED';
            document.getElementById('body').value = `Dear <?php echo addslashes($fullname); ?>,

We are pleased to inform you that your enrollment in the Bachelor of Science in Information Technology (BSIT) program at ASCOT has been **ACCEPTED**!

**Enrollment Details:**
- Program: BSIT
- Status: ✅ Accepted

**Next Steps:**
1. Complete your tuition payment at the accounting office
2. Submit remaining requirements (if any)
3. Attend the orientation session
4. Check your schedule for classes

**Important Dates:**
- Payment Deadline: 1 week from today
- Orientation: Check announcement board
- Classes Start: August 4, 2025

You can view your enrollment status at any time by visiting the student portal.

Welcome to ASCOT! We look forward to seeing you on campus.

Best regards,
ASCOT Admissions Office
admissions@ascot.edu.ph
(042) 123-4567`;
        } else if (type === 'reject') {
            document.getElementById('subject').value = 'Regarding Your BSIT Enrollment Application';
            document.getElementById('body').value = `Dear <?php echo addslashes($fullname); ?>,

Thank you for your interest in the Bachelor of Science in Information Technology (BSIT) program at ASCOT.

After careful review of your application, we regret to inform you that your enrollment has been **NOT ACCEPTED** at this time.

**Reason for Decision:**
Please visit the admissions office for a detailed review of your application.

**Options Available:**
1. You may reapply in the next enrollment period
2. Consider alternative programs at ASCOT
3. Schedule a consultation with our admissions counselor

**Next Enrollment Period:**
The next enrollment period will open on June 1, 2025.

We encourage you to review the admission requirements and ensure all documents are complete for your next application.

If you have questions about this decision, please contact the admissions office.

Best regards,
ASCOT Admissions Office
admissions@ascot.edu.ph
(042) 123-4567`;
        } else if (type === 'requirements') {
            document.getElementById('subject').value = 'Additional Requirements Needed for Your Enrollment';
            document.getElementById('body').value = `Dear <?php echo addslashes($fullname); ?>,

Your enrollment application is currently under review. We need some additional documents to complete your application:

**Required Documents:**
1. Original birth certificate (PSA copy)
2. Medical certificate from accredited clinic
3. 2x2 recent ID pictures (white background)
4. Certificate of good moral character

**Submission Deadline:**
Please submit these documents within 7 days to avoid delays in processing your application.

**Where to Submit:**
ASCOT Admissions Office
Ground Floor, Main Building
Monday-Friday, 8:00 AM - 5:00 PM

If you have any questions, please contact the admissions office.

Best regards,
ASCOT Admissions Office
admissions@ascot.edu.ph
(042) 123-4567`;
        }
    }
</script>
</head>
<body>
  <div class="container">
    <h2>📨 Message Student</h2>

    <?php if (!empty($_SESSION['admin_notice'])): ?>
      <div class="notice">
        <?php echo htmlspecialchars($_SESSION['admin_notice'], ENT_QUOTES); ?>
        <?php unset($_SESSION['admin_notice']); ?>
      </div>
    <?php endif; ?>

    <div class="student-info">
      <h3>Student Information</h3>
      <p><strong>Name:</strong> <?php echo htmlspecialchars($fullname, ENT_QUOTES); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($email, ENT_QUOTES); ?></p>
      <p><strong>Status:</strong> 
        <span class="status-badge status-<?php echo htmlspecialchars($current_status, ENT_QUOTES); ?>">
          <?php 
            $statusIcon = $current_status === 'accepted' ? '✅' : ($current_status === 'rejected' ? '❌' : '⏳');
            echo $statusIcon . ' ' . htmlspecialchars($current_status, ENT_QUOTES);
          ?>
        </span>
      </p>
    </div>

    <div class="quick-templates">
      <h3>Quick Message Templates</h3>
      <div class="template-buttons">
        <button type="button" class="template-btn accept" onclick="loadTemplate('accept')">✅ Acceptance Letter</button>
        <button type="button" class="template-btn reject" onclick="loadTemplate('reject')">❌ Rejection Letter</button>
        <button type="button" class="template-btn" onclick="loadTemplate('requirements')">📋 Requirements Request</button>
      </div>
    </div>

    <form action="admin_message_action.php" method="post">
      <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>">
      <input type="hidden" name="return_status" value="<?php echo htmlspecialchars($return_status, ENT_QUOTES); ?>">

      <label for="subject">Subject</label>
      <input type="text" id="subject" name="subject" required 
             value="<?php echo htmlspecialchars($default_subject, ENT_QUOTES); ?>">

      <label for="body">Message</label>
      <textarea id="body" name="body" required><?php echo htmlspecialchars($default_body, ENT_QUOTES); ?></textarea>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">📤 Send Message</button>
        <a href="admin.php<?php echo $return_status !== '' ? '?status=' . urlencode($return_status) : ''; ?>" 
           class="btn btn-secondary">← Back to Admin</a>
      </div>
    </form>
  </div>
</body>
</html>