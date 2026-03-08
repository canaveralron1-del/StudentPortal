<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['UserName']) && !isset($_SESSION['email'])) {
    // Redirect to login if not logged in
    header('Location: logIn.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Messages — ASCOT BSIT Enrollment Portal</title>
  <link rel="stylesheet" href="design2.css">
  <style>
    /* Reset-ish */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    :root{
      --brand:#0f1724;        /* dark navy */
      --accent:#0ea5a4;       /* teal */
      --muted:#6b7280;        /* gray */
      --card:#ffffff;
      --glass: rgba(255,255,255,0.6);
      --border: rgba(15,23,36,0.04);
      --shadow: rgba(12,18,30,0.06);
    }
    body {
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(180deg,#f3f6f9 0%, #eef2f6 100%);
      color: #0b1220;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      line-height:1.45;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* NAV */
    header.site-header {
      background: var(--brand);
      color: white;
      padding: 12px 18px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
    }
    .brand {
      display:flex;
      align-items:center;
      gap:12px;
    }
    .brand img { height:42px; width:auto; border-radius:6px; }
    .brand h1 { font-size:18px; letter-spacing:0.2px; margin:0; }
    nav.primary { display:flex; gap:10px; align-items:center; }
    nav.primary a {
      color:white; text-decoration:none; padding:8px 12px; border-radius:8px; font-size:14px;
      opacity:0.95;
      transition: background-color 0.2s;
    }
    nav.primary a:hover { background: rgba(255,255,255,0.06); }
    nav.primary a.active { background: rgba(255,255,255,0.06); }

    /* Main layout */
    main.container {
      max-width:1200px;
      margin:28px auto;
      padding:0 18px;
      flex: 1;
    }

    /* Messages Hero */
    .messages-hero {
        background: linear-gradient(rgba(15,23,36,0.85), rgba(15,23,36,0.75)), url('ascot.jpg');
        background-size: cover;
        background-position: center;
        border-radius: 14px;
        padding: 50px 40px;
        color: white;
        margin-bottom: 40px;
        box-shadow: 0 8px 24px var(--shadow);
        min-height: 300px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .messages-hero h2 { font-size:32px; margin-bottom:12px; }
    .messages-hero p { font-size:16px; opacity:0.9; max-width:700px; }

    /* User Info Banner */
    .user-banner {
      background: linear-gradient(90deg, rgba(14,165,164,0.1), rgba(56,189,248,0.1));
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 24px;
      border: 1px solid rgba(14,165,164,0.15);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .user-info {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .user-avatar {
      width: 48px;
      height: 48px;
      background: linear-gradient(180deg,var(--accent), #22c1c3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 20px;
      font-weight: bold;
    }
    .user-details h3 {
      color: var(--brand);
      margin-bottom: 4px;
      font-size: 18px;
    }
    .user-details p {
      color: var(--muted);
      font-size: 14px;
      margin: 0;
    }
    .view-messages-btn {
      background: var(--accent);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .view-messages-btn:hover {
      background: #0d8f8e;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(14,165,164,0.3);
    }

    /* Messages Container */
    .messages-container {
      background: white;
      border-radius: 14px;
      padding: 32px;
      margin-bottom: 32px;
      border: 1px solid var(--border);
      box-shadow: 0 8px 24px var(--shadow);
    }
    .messages-title {
      color: var(--brand);
      font-size: 22px;
      margin-bottom: 28px;
      padding-bottom: 16px;
      border-bottom: 2px solid rgba(14,165,164,0.1);
    }

    /* Loading State */
    .loading-state {
      text-align: center;
      padding: 40px;
      color: var(--muted);
    }
    .spinner {
      width: 40px;
      height: 40px;
      border: 3px solid rgba(14,165,164,0.1);
      border-top-color: var(--accent);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 20px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Messages List */
    .messages-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .message-item {
      background: #f8fafc;
      border-radius: 10px;
      padding: 20px;
      border: 1px solid var(--border);
      transition: all 0.2s;
    }
    .message-item:hover {
      background: #f1f5f9;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px var(--shadow);
    }
    .message-item.unread {
      background: linear-gradient(90deg, rgba(14,165,164,0.05), rgba(56,189,248,0.05));
      border-left: 4px solid var(--accent);
    }
    .message-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
    }
    .message-title {
      color: var(--brand);
      font-weight: 600;
      font-size: 16px;
      margin: 0;
    }
    .message-date {
      color: var(--muted);
      font-size: 12px;
      white-space: nowrap;
    }
    .message-content {
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 12px;
      white-space: pre-line;
    }
    .message-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }
    .status-accepted {
      background: #d1fae5;
      color: #065f46;
    }
    .status-rejected {
      background: #fee2e2;
      color: #991b1b;
    }
    .status-manual {
      background: #e0e7ff;
      color: #3730a3;
    }
    .status-ongoing {
      background: #f3f4f6;
      color: #4b5563;
    }

    /* Message Meta Info */
    .message-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid rgba(0,0,0,0.05);
      font-size: 12px;
      color: var(--muted);
    }
    .message-sender {
      font-style: italic;
    }

    /* No Messages State */
    .no-messages {
      text-align: center;
      padding: 40px;
      color: var(--muted);
    }
    .no-messages-icon {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: 0.5;
    }

    /* Enrollment Info */
    .enrollment-info {
      background: linear-gradient(90deg, rgba(14,165,164,0.05), rgba(56,189,248,0.05));
      border-radius: 10px;
      padding: 16px;
      margin-bottom: 20px;
      border: 1px solid rgba(14,165,164,0.1);
    }
    .enrollment-info h4 {
      color: var(--brand);
      margin-bottom: 8px;
      font-size: 16px;
    }
    .enrollment-details {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
    .detail-item {
      display: flex;
      flex-direction: column;
    }
    .detail-label {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px;
    }
    .detail-value {
      font-weight: 500;
      color: var(--brand);
    }

    /* Buttons */
    .btn{
      display:inline-flex;
      gap:10px;
      align-items:center;
      text-decoration:none;
      padding:12px 20px;
      border-radius:10px;
      font-weight:600;
      font-size:14px;
      border:1px solid rgba(15,23,36,0.06);
      transition: all 0.2s;
      cursor: pointer;
      font-family: inherit;
    }
    .btn.primary { 
      background:var(--brand); 
      color:white; 
      box-shadow: 0 8px 18px rgba(15,23,36,0.06); 
    }
    .btn.primary:hover {
      background: #1a2436;
      transform: translateY(-1px);
      box-shadow: 0 12px 24px rgba(15,23,36,0.1);
    }
    .btn.outline { 
      background:transparent; 
      color:var(--brand); 
      border:1px solid rgba(15,23,36,0.08); 
    }
    .btn.outline:hover {
      background: rgba(15,23,36,0.02);
    }
    .form-actions {
      display: flex;
      gap: 12px;
      margin-top: 32px;
    }

    /* Info Cards */
    .info-cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-top: 32px;
    }
    .info-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      border: 1px solid var(--border);
      box-shadow: 0 4px 12px var(--shadow);
      text-align: center;
    }
    .info-card .icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(180deg,var(--accent), #22c1c3);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 20px;
      margin: 0 auto 16px;
    }
    .info-card h4 {
      color: var(--brand);
      margin-bottom: 8px;
      font-size: 16px;
    }
    .info-card p {
      color: var(--muted);
      font-size: 13px;
      margin: 0;
    }

    /* Footer */
    footer.site-footer {
      margin-top:28px; 
      padding:20px; 
      background:var(--brand); 
      color:white; 
      border-radius:10px;
      max-width:1200px; 
      margin-left:auto; 
      margin-right:auto; 
      display:flex; 
      justify-content:space-between; 
      align-items:center;
    }

    /* Responsive */
    @media (max-width:1000px){
      .info-cards { grid-template-columns: repeat(2, 1fr); }
      footer.site-footer { flex-direction:column; gap:12px; text-align:center; }
      .user-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    }
    @media (max-width:768px){
      .info-cards { grid-template-columns: 1fr; }
      .messages-hero h2 { font-size: 26px; }
      .messages-hero { padding: 30px 20px; }
      .messages-container { padding: 24px; }
      .enrollment-details { grid-template-columns: 1fr; }
    }
    @media (max-width:560px){
      header.site-header { flex-direction: column; align-items: flex-start; }
      nav.primary { width: 100%; margin-top: 10px; }
      .form-actions { flex-direction: column; }
      .user-banner .user-info { flex-direction: column; align-items: flex-start; }
    }
  </style>

  <script>
    // Load messages when page loads
    document.addEventListener('DOMContentLoaded', function() {
      loadMessages();
    });

    function loadMessages() {
      const messagesContainer = document.getElementById('messages-list');
      const enrollmentInfo = document.getElementById('enrollment-info');
      
      // Show loading state
      messagesContainer.innerHTML = `
        <div class="loading-state">
          <div class="spinner"></div>
          <p>Loading your messages...</p>
        </div>
      `;

      // Fetch messages from server
      fetch('get_messages.php', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          displayEnrollmentInfo(data.enrollment);
          displayMessages(data.messages);
        } else {
          showNoMessages(data.error || 'No messages found');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNoMessages('Unable to load messages. Please try again.');
      });
    }

    function displayEnrollmentInfo(enrollment) {
      const enrollmentInfo = document.getElementById('enrollment-info');
      
      if (!enrollment) {
        enrollmentInfo.style.display = 'none';
        return;
      }

      enrollmentInfo.innerHTML = `
        <h4>📋 Your Enrollment Information</h4>
        <div class="enrollment-details">
          <div class="detail-item">
            <span class="detail-label">Current Status</span>
            <span class="detail-value ${enrollment.status}">${getStatusIcon(enrollment.status)} ${enrollment.status.charAt(0).toUpperCase() + enrollment.status.slice(1)}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Year Level</span>
            <span class="detail-value">${enrollment.year_level}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Semester</span>
            <span class="detail-value">${enrollment.semester}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Enrollment Date</span>
            <span class="detail-value">${new Date(enrollment.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
          </div>
        </div>
      `;
    }

    function displayMessages(messages) {
      const messagesContainer = document.getElementById('messages-list');
      
      if (!messages || messages.length === 0) {
        showNoMessages();
        return;
      }

      let messagesHTML = '<div class="messages-list">';
      
      messages.forEach(message => {
        const date = new Date(message.created_at).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
        
        let statusClass = 'status-manual';
        if (message.status === 'pending') statusClass = 'status-pending';
        else if (message.status === 'accepted') statusClass = 'status-accepted';
        else if (message.status === 'rejected') statusClass = 'status-rejected';
        else if (message.status === 'ongoing') statusClass = 'status-ongoing';
        
        const isUnread = message.is_read === 0 ? 'unread' : '';
        
        messagesHTML += `
          <div class="message-item ${isUnread}" data-id="${message.id}">
            <div class="message-header">
              <h4 class="message-title">${message.subject || 'Enrollment Update'}</h4>
              <div class="message-date">${date}</div>
            </div>
            <div class="message-content">${message.body || message.content}</div>
            <div class="message-meta">
              <div class="message-status ${statusClass}">
                ${getStatusIcon(message.status)} ${message.status.charAt(0).toUpperCase() + message.status.slice(1)}
              </div>
              <div class="message-sender">From: ${message.sender_type === 'admin' ? 'ASCOT Admissions' : 'System'}</div>
            </div>
          </div>
        `;
      });
      
      messagesHTML += '</div>';
      messagesContainer.innerHTML = messagesHTML;

      // Add click event to mark as read
      document.querySelectorAll('.message-item.unread').forEach(item => {
        item.addEventListener('click', function() {
          const messageId = this.getAttribute('data-id');
          markAsRead(messageId);
        });
      });
    }

    function getStatusIcon(status) {
      switch(status) {
        case 'pending': return '⏳';
        case 'accepted': return '✅';
        case 'rejected': return '❌';
        case 'ongoing': return '📝';
        default: return '📧';
      }
    }

    function markAsRead(messageId) {
      fetch('mark_as_read.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `message_id=${messageId}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const messageItem = document.querySelector(`.message-item[data-id="${messageId}"]`);
          messageItem.classList.remove('unread');
        }
      });
    }

    function showNoMessages(message = "You don't have any messages yet.") {
      const messagesContainer = document.getElementById('messages-list');
      messagesContainer.innerHTML = `
        <div class="no-messages">
          <div class="no-messages-icon">📭</div>
          <p>${message}</p>
          <p style="font-size: 12px; margin-top: 10px;">Messages will appear here once the admissions office reviews your enrollment.</p>
        </div>
      `;
    }

    function refreshMessages() {
      loadMessages();
    }
  </script>
</head>
<body>
  <header class="site-header" role="banner">
    <div class="brand">
      <img src="ascot3.png" alt="ASCOT logo" onerror="this.style.display='none'">
      <div>
        <h1 style="font-size:16px;">ASCOT — BSIT Enrollment Portal</h1>
        <div style="font-size:12px; color:rgba(255,255,255,0.8); margin-top:3px;">Aurora State College of Technology</div>
      </div>
    </div>

    <nav class="primary" role="navigation" aria-label="Primary">
      <a href="index.html">Home</a>
      <a href="enrollment.php">Enroll</a>
      <a href="about.html">Programs</a>
      <a href="contact.html">Contact</a>
      <a href="messages.php" class="active">Messages</a>
      <a href="profile.php">My Profile</a>
      <a href="logout.php">Log Out</a>
    </nav>
  </header>

  <main class="container" role="main">
    <!-- Messages Hero -->
    <section class="messages-hero" aria-label="Messages Introduction">
      <h2>Your Enrollment Messages</h2>
      <p>View all messages regarding your enrollment status, application updates, requirements, and important announcements from the admissions office.</p>
    </section>

    <!-- User Info Banner -->
    <div class="user-banner">
      <div class="user-info">
        <div class="user-avatar">
          <?php
          // Get user initials from session
          if (isset($_SESSION['First_Name']) && isset($_SESSION['Last_Name'])) {
            echo strtoupper(substr($_SESSION['First_Name'], 0, 1) . substr($_SESSION['Last_Name'], 0, 1));
          } elseif (isset($_SESSION['fullname'])) {
            $name = $_SESSION['fullname'];
            $initials = '';
            $words = explode(' ', $name);
            foreach ($words as $word) {
              if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
              }
              if (strlen($initials) >= 2) break;
            }
            echo $initials;
          } else {
            echo "U";
          }
          ?>
        </div>
        <div class="user-details">
          <h3>
            <?php 
            if (isset($_SESSION['First_Name']) && isset($_SESSION['Last_Name'])) {
              echo htmlspecialchars($_SESSION['First_Name'] . ' ' . $_SESSION['Last_Name']);
            } elseif (isset($_SESSION['fullname'])) {
              echo htmlspecialchars($_SESSION['fullname']);
            } else {
              echo 'Guest User';
            }
            ?>
          </h3>
          <p>
            <?php 
            if (isset($_SESSION['email'])) {
              echo htmlspecialchars($_SESSION['email']);
            } elseif (isset($_SESSION['UserName'])) {
              echo htmlspecialchars($_SESSION['UserName']);
            } else {
              echo 'Please log in to view messages';
            }
            ?>
          </p>
        </div>
      </div>
      <button onclick="refreshMessages()" class="view-messages-btn">🔄 Refresh Messages</button>
    </div>

    <!-- Messages Container -->
    <div class="messages-container">
      <h2 class="messages-title">Messages & Enrollment Status</h2>
      
      <!-- Enrollment Information -->
      <div id="enrollment-info" class="enrollment-info">
        <!-- Enrollment info will be loaded here dynamically -->
      </div>
      
      <!-- Messages List -->
      <div id="messages-list">
        <!-- Messages will be loaded here dynamically -->
      </div>

      <!-- Quick Actions -->
      <div class="form-actions">
        <a href="enrollment.php" class="btn outline" style="text-decoration: none;">
          <span>View Enrollment Form</span>
        </a>
        <a href="profile.php" class="btn outline" style="text-decoration: none;">
          <span>Update Profile</span>
        </a>
        <a href="contact.html" class="btn primary" style="text-decoration: none;">
          <span>Contact Admissions</span>
        </a>
      </div>
    </div>

    <!-- Information Cards -->
    <div class="info-cards">
      <div class="info-card">
        <div class="icon">📧</div>
        <h4>Automatic Updates</h4>
        <p>Messages are automatically linked to your account. No need to enter email each time.</p>
      </div>
      
      <div class="info-card">
        <div class="icon">⏱️</div>
        <h4>Real-time Status</h4>
        <p>Check back regularly for admission decisions, requirement updates, and announcements.</p>
      </div>
      
      <div class="info-card">
        <div class="icon">🔒</div>
        <h4>Secure Access</h4>
        <p>Your messages are private and only accessible through your authenticated account.</p>
      </div>
    </div>

    <!-- Contact Support -->
    <div style="background: white; border-radius: 14px; padding: 24px; margin-top: 32px; border: 1px solid var(--border); box-shadow: 0 8px 24px var(--shadow);">
      <h3 style="color: var(--brand); margin-bottom: 16px; font-size: 20px;">Need Assistance?</h3>
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
        <div>
          <h4 style="color: var(--brand); margin-bottom: 8px; font-size: 16px;">Common Questions</h4>
          <ul style="color: var(--muted); font-size: 13px; padding-left: 20px;">
            <li>When will I receive admission decision?</li>
            <li>How to submit missing requirements?</li>
            <li>Can't see expected messages?</li>
            <li>Technical issues with messages?</li>
          </ul>
        </div>
        <div>
          <h4 style="color: var(--brand); margin-bottom: 8px; font-size: 16px;">Contact Support</h4>
          <p style="color: var(--muted); font-size: 13px; margin-bottom: 12px;">
            For assistance, contact:<br>
            Email: admissions@ascot.edu.ph<br>
            Phone: (042) 123-4567
          </p>
          <a href="contact.html" class="btn outline" style="text-decoration: none;">Contact Page</a>
        </div>
      </div>
    </div>
  </main>

  <footer class="site-footer" role="contentinfo">
    <div style="display:flex; gap:12px; align-items:center;">
      <img src="images/ascot-logo.png" alt="ASCOT" style="height:42px; width:auto; border-radius:6px; opacity:0.95;" onerror="this.style.display='none'">
      <div>
        <div style="font-weight:700;">Aurora State College of Technology</div>
        <div style="font-size:13px; color:rgba(255,255,255,0.85);">Bachelor of Science in Information Technology</div>
      </div>
    </div>

    <div style="text-align:right; font-size:13px;">
      <div>© 2025 ASCOT — Student Enrollment System</div>
      <div style="color:rgba(255,255,255,0.8); margin-top:6px;">Privacy • Terms • Support</div>
    </div>
  </footer>
</body>
</html>