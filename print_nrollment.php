<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: NewL.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "kurt");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];
$enrollment_id = $_GET['id'] ?? 0;

// Get enrollment data
$sql = "SELECT * FROM enrollments WHERE id = ? AND username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $enrollment_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Enrollment not found or access denied.");
}

$enrollment = $result->fetch_assoc();
$subjects = json_decode($enrollment['subjects'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Enrollment - ASCOT</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12pt; margin: 0; padding: 0; }
            @page { margin: 1cm; }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 40px;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #0f1724;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #0f1724;
            margin: 10px 0 5px 0;
        }
        
        .header h2 {
            color: #0ea5a4;
            margin: 5px 0;
        }
        
        .info-section {
            margin: 25px 0;
        }
        
        .info-row {
            display: flex;
            margin: 8px 0;
            padding: 5px 0;
        }
        
        .info-label {
            flex: 0 0 180px;
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            flex: 1;
        }
        
        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .subjects-table th {
            background-color: #f5f5f5;
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        
        .subjects-table td {
            padding: 10px 12px;
            border: 1px solid #ddd;
        }
        
        .subjects-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .signature-area {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin: 40px 0 10px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10pt;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #0f1724;
            color: white;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Now</button>
        <button class="btn btn-secondary" onclick="window.close()">✕ Close</button>
    </div>
    
    <div class="header">
        <h1>AURORA STATE COLLEGE OF TECHNOLOGY</h1>
        <h2>BSIT Enrollment Form</h2>
        <p>Bachelor of Science in Information Technology</p>
    </div>
    
    <div class="info-section">
        <h3 style="color: #0f1724; border-bottom: 2px solid #0ea5a4; padding-bottom: 5px;">Student Information</h3>
        
        <div class="info-row">
            <div class="info-label">Full Name:</div>
            <div class="info-value"><?php echo htmlspecialchars($enrollment['fullname']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Email Address:</div>
            <div class="info-value"><?php echo htmlspecialchars($enrollment['email']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Year Level:</div>
            <div class="info-value">Year <?php echo htmlspecialchars($enrollment['year_level']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Semester:</div>
            <div class="info-value">Semester <?php echo htmlspecialchars($enrollment['semester']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Enrollment Date:</div>
            <div class="info-value"><?php echo date('F d, Y', strtotime($enrollment['created_at'])); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Application Status:</div>
            <div class="info-value"><strong><?php echo strtoupper($enrollment['status']); ?></strong></div>
        </div>
    </div>
    
    <div class="info-section">
        <h3 style="color: #0f1724; border-bottom: 2px solid #0ea5a4; padding-bottom: 5px;">Enrolled Subjects</h3>
        
        <?php if (!empty($subjects) && is_array($subjects)): ?>
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th>Subject Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $index => $subject): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($subject); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right; font-weight: bold;">
                            Total Subjects: <?php echo count($subjects); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php else: ?>
            <p>No subjects enrolled.</p>
        <?php endif; ?>
    </div>
    
    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line"></div>
            <p><strong>Student's Signature</strong></p>
            <p>Date: <?php echo date('m/d/Y'); ?></p>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <p><strong>Registrar's Office</strong></p>
            <p>Date: ______________</p>
        </div>
    </div>
    
    <div class="footer">
        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        <p>ASCOT BSIT Enrollment System</p>
        <p>This document is system-generated. Official copy available at the Registrar's Office.</p>
    </div>
    
    <script>
        // Auto print if requested
        <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
        window.onload = function() {
            window.print();
        }
        <?php endif; ?>
        
        // Close window after printing
        window.onafterprint = function() {
            setTimeout(function() {
                // Optional: uncomment to auto-close after printing
                // window.close();
            }, 1000);
        };
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>