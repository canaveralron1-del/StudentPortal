<?php
require_once __DIR__ . '/db.php';
session_start();

$rows = [];
$error = null;

// Ensure the enrollments table exists
$createSql = "CREATE TABLE IF NOT EXISTS enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  year_level VARCHAR(16) NOT NULL,
  semester VARCHAR(16) NOT NULL,
  subjects TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'ongoing',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

try {
  $mysqli->query($createSql);

  // allow filtering by status: all/ongoing/accepted/rejected
  $allowed = ['ongoing','accepted','rejected','all',''];
  $filter = isset($_GET['status']) ? $_GET['status'] : '';
  if (!in_array($filter, $allowed, true)) $filter = '';

  if ($filter === 'ongoing' || $filter === 'accepted' || $filter === 'rejected') {
    $sql = $mysqli->prepare('SELECT id, fullname, email, year_level, semester, subjects, status, created_at FROM enrollments WHERE status = ? ORDER BY id DESC');
    $sql->bind_param('s', $filter);
    $sql->execute();
    $result = $sql->get_result();
  } else {
    $result = $mysqli->query('SELECT id, fullname, email, year_level, semester, subjects, status, created_at FROM enrollments ORDER BY id DESC');
  }

  if ($result) {
    while ($r = $result->fetch_assoc()) {
      $rows[] = $r;
    }
    $result->free();
  }
} catch (mysqli_sql_exception $e) {
  $error = $e->getMessage();
}

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — ASCOT BSIT Enrollment Portal</title>
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
      margin: 0;
      padding: 0;
    }
    .brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand img { 
    height: 42px; 
    width: auto; 
    border-radius: 6px; 
}

.brand h1 { 
    font-size: 18px; 
    font-weight: 600;
    margin: 0; 
}

.brand .subtitle {
    font-size: 12px;
    color: rgba(255,255,255,0.8);
    margin-top: 3px;
}
.admin-nav { 
    display: flex; 
    gap: 12px; 
    align-items: center; 
}

.admin-nav a {
    color: white; 
    text-decoration: none; 
    padding: 8px 16px; 
    border-radius: 8px; 
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.admin-nav a:hover { 
    background: rgba(255,255,255,0.1); 
}

.admin-nav a.active { 
    background: rgba(255,255,255,0.15); 
    font-weight: 600; 
}
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    /* Header */
    .admin-header {
      background: var(--brand);
      color: white;
      padding: 16px 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 8px 24px var(--shadow);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .admin-header h1 {
      font-size: 24px;
      font-weight: 600;
      margin: 0;
    }

    .admin-actions-header {
      display: flex;
      gap: 12px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      text-decoration: none;
      transition: all 0.2s;
      border: 1px solid transparent;
      cursor: pointer;
      font-family: inherit;
    }

    .btn-primary {
      background: var(--brand);
      color: white;
      box-shadow: 0 6px 18px rgba(15, 23, 36, 0.1);
    }

    .btn-primary:hover {
      background: #1a2436;
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(15, 23, 36, 0.15);
    }

    .btn-secondary {
      background: transparent;
      color: var(--brand);
      border: 1px solid var(--border);
    }

    .btn-secondary:hover {
      background: rgba(15, 23, 36, 0.03);
    }

    .btn-accent {
      background: linear-gradient(90deg, var(--accent), var(--accent-light));
      color: white;
      box-shadow: 0 6px 18px rgba(14, 165, 164, 0.15);
    }

    .btn-accent:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* Main Container */
    .wrap {
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Filters */
    .filter-section {
      background: var(--card);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
      border: 1px solid var(--border);
      box-shadow: 0 4px 16px var(--shadow);
    }

    .filter-section h3 {
      color: var(--brand);
      margin-bottom: 16px;
      font-size: 16px;
      font-weight: 600;
    }

    .filter-tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filter-tab {
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      background: rgba(15, 23, 36, 0.03);
      color: var(--muted);
      border: 1px solid var(--border);
      transition: all 0.2s;
    }

    .filter-tab:hover {
      background: rgba(15, 23, 36, 0.06);
      color: var(--brand);
    }

    .filter-tab.active {
      background: var(--accent);
      color: white;
      border-color: var(--accent);
    }

    /* Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--card);
      border-radius: 12px;
      padding: 20px;
      border: 1px solid var(--border);
      box-shadow: 0 4px 16px var(--shadow);
      text-align: center;
    }

    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .stat-label {
      color: var(--muted);
      font-size: 14px;
      font-weight: 500;
    }

    /* Table */
    .table-container {
      background: var(--card);
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--border);
      box-shadow: 0 8px 24px var(--shadow);
      margin-bottom: 24px;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1000px;
    }

    th {
      background: var(--brand);
      color: white;
      padding: 16px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
    }

    td {
      padding: 16px;
      border-bottom: 1px solid var(--border);
      font-size: 14px;
    }

    tr:hover {
      background: rgba(14, 165, 164, 0.02);
    }

    tr:last-child td {
      border-bottom: none;
    }

    /* Status badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-ongoing {
      background: rgba(234, 179, 8, 0.1);
      color: #92400e;
    }

    .status-accepted {
      background: rgba(34, 197, 94, 0.1);
      color: #166534;
    }

    .status-rejected {
      background: rgba(239, 68, 68, 0.1);
      color: #991b1b;
    }

    /* Action buttons */
    .action-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .action-btn {
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .btn-accept {
      background: rgba(34, 197, 94, 0.1);
      color: #166534;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .btn-accept:hover {
      background: rgba(34, 197, 94, 0.2);
    }

    .btn-reject {
      background: rgba(239, 68, 68, 0.1);
      color: #991b1b;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .btn-reject:hover {
      background: rgba(239, 68, 68, 0.2);
    }

    .btn-message {
      background: rgba(14, 165, 164, 0.1);
      color: var(--accent);
      border: 1px solid rgba(14, 165, 164, 0.2);
    }

    .btn-message:hover {
      background: rgba(14, 165, 164, 0.2);
    }

    .btn-delete {
      background: rgba(107, 114, 128, 0.1);
      color: var(--muted);
      border: 1px solid rgba(107, 114, 128, 0.2);
    }

    .btn-delete:hover {
      background: rgba(107, 114, 128, 0.2);
    }

    /* Subjects display */
    .subjects-list {
      max-width: 300px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .subjects-list:hover {
      white-space: normal;
      overflow: visible;
      position: relative;
      z-index: 10;
      background: white;
      padding: 8px;
      border-radius: 8px;
      box-shadow: 0 4px 12px var(--shadow);
    }

    /* Messages */
    .message-alert {
      background: rgba(14, 165, 164, 0.1);
      border: 1px solid rgba(14, 165, 164, 0.2);
      color: var(--accent);
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .error-alert {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.2);
      color: #991b1b;
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }

    .empty-state-icon {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: 0.5;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .admin-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
      }

      .admin-actions-header {
        flex-direction: column;
        width: 100%;
      }

      .btn {
        width: 100%;
        justify-content: center;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      body {
        padding: 12px;
      }
    }

    @media (max-width: 480px) {
      .filter-tabs {
        flex-direction: column;
      }

      .filter-tab {
        width: 100%;
        text-align: center;
      }
}
    .site-header {
    background: #0f1724;
    color: white;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(15, 23, 36, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;}
  </style>
</head>
<body>
  <div class="wrap">
    <!-- Admin Header -->
    <nav class="site-header">
    <div class="brand">
        <img src="ascot3.png" alt="ASCOT Logo">
        <div>
            <h1>ASCOT — Admin Portal</h1>
            <div class="subtitle">Aurora State College of Technology</div>
        </div>
    </div>
    <div class="admin-nav">
        <a href="admin.php" class="active">Enrollment</a>
        <a href="admin_users.php">Management</a>
        <a href="admin_logout.php" style="color: #ef4444; margin-left: 20px;">Logout</a>
    </div>
</nav>
    </div>

    <!-- Stats -->
    <?php
    $total = count($rows);
    $ongoing = 0;
    $accepted = 0;
    $rejected = 0;
    foreach ($rows as $r) {
      switch ($r['status']) {
        case 'ongoing': $ongoing++; break;
        case 'accepted': $accepted++; break;
        case 'rejected': $rejected++; break;
      }
    }
    ?>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number"><?php echo $total; ?></div>
        <div class="stat-label">Total Applications</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $ongoing; ?></div>
        <div class="stat-label">Pending Review</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $accepted; ?></div>
        <div class="stat-label">Accepted</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $rejected; ?></div>
        <div class="stat-label">Rejected</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
      <h3>Filter Applications</h3>
      <div class="filter-tabs">
        <a href="admin.php" class="filter-tab <?php echo $filter === '' ? 'active' : ''; ?>">All Applications</a>
        <a href="admin.php?status=ongoing" class="filter-tab <?php echo $filter === 'ongoing' ? 'active' : ''; ?>">Pending Review</a>
        <a href="admin.php?status=accepted" class="filter-tab <?php echo $filter === 'accepted' ? 'active' : ''; ?>">Accepted</a>
        <a href="admin.php?status=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="error-alert">
        <strong>Database Error:</strong> <?php echo esc($error); ?>
      </div>
    <?php endif; ?>

    <!-- Enrollment Table -->
    <div class="table-container">
      <?php if (count($rows) === 0): ?>
        <div class="empty-state">
          <div class="empty-state-icon">📭</div>
          <h3>No enrollments found</h3>
          <p>No applications match your current filter. Try a different filter or check back later.</p>
          <a href="admin.php" class="btn btn-primary" style="margin-top: 16px;">View All Applications</a>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Year Level</th>
              <th>Semester</th>
              <th>Subjects</th>
              <th>Created Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo (int)$r['id']; ?></td>
              <td><strong><?php echo esc($r['fullname']); ?></strong></td>
              <td><?php echo esc($r['email']); ?></td>
              <td><?php echo esc($r['year_level']); ?></td>
              <td><?php echo esc($r['semester']); ?></td>
              <td class="subjects-list">
                <?php
                  $subs = json_decode($r['subjects'], true);
                  if (is_array($subs)) {
                    echo esc(implode(', ', $subs));
                  } else {
                    echo esc($r['subjects']);
                  }
                ?>
              </td>
              <td><?php echo esc($r['created_at']); ?></td>
              <td>
                <?php 
                  $statusClass = 'status-' . $r['status'];
                  $statusIcon = $r['status'] === 'accepted' ? '✅' : ($r['status'] === 'rejected' ? '❌' : '⏳');
                ?>
                <span class="status-badge <?php echo $statusClass; ?>">
                  <?php echo $statusIcon; ?> <?php echo esc($r['status']); ?>
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <?php if ($r['status'] === 'ongoing'): ?>
                    <form method="post" action="admin_action.php" style="display: inline;">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <input type="hidden" name="action" value="accept">
                      <input type="hidden" name="return_status" value="<?php echo esc($filter); ?>">
                      <button type="submit" class="action-btn btn-accept">✅ Accept</button>
                    </form>
                    <form method="post" action="admin_action.php" style="display: inline;">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <input type="hidden" name="action" value="reject">
                      <input type="hidden" name="return_status" value="<?php echo esc($filter); ?>">
                      <button type="submit" class="action-btn btn-reject">❌ Reject</button>
                    </form>
                  <?php endif; ?>
                  <a href="admin_message.php?id=<?php echo (int)$r['id']; ?>&return_status=<?php echo urlencode($filter); ?>" class="action-btn btn-message">📩 Message</a>
                  <?php if ($r['status'] !== 'ongoing'): ?>
                    <form method="post" action="admin_action.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this enrollment? This action cannot be undone.');">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="return_status" value="<?php echo esc($filter); ?>">
                      <button type="submit" class="action-btn btn-delete">🗑️ Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>