<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: StudentLogin.php");
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

// Include TCPDF
require_once('tcpdf/tcpdf.php');

// Create PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ASCOT BSIT System');
$pdf->SetAuthor('ASCOT');
$pdf->SetTitle('Enrollment Form - ' . $enrollment['fullname']);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Add a page
$pdf->AddPage();

// Set font for title
$pdf->SetFont('helvetica', 'B', 20);

// Title - No logo
$pdf->Cell(0, 10, 'ASCOT BSIT ENROLLMENT FORM', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 5, 'Aurora State College of Technology', 0, 1, 'C');
$pdf->Cell(0, 5, 'Bachelor of Science in Information Technology', 0, 1, 'C');

// Line separator
$pdf->Ln(10);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(15);

// Student Information header
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 10, 'STUDENT INFORMATION', 0, 1, 'L', true);
$pdf->Ln(5);

// Student Information details
$pdf->SetFont('helvetica', '', 11);

$info_html = '
<table border="0" cellpadding="4" cellspacing="0">
<tr>
    <td width="40%"><b>Full Name:</b></td>
    <td width="60%">' . htmlspecialchars($enrollment['fullname']) . '</td>
</tr>
<tr>
    <td><b>Email Address:</b></td>
    <td>' . htmlspecialchars($enrollment['email']) . '</td>
</tr>
<tr>
    <td><b>Year Level:</b></td>
    <td>Year ' . htmlspecialchars($enrollment['year_level']) . '</td>
</tr>
<tr>
    <td><b>Semester:</b></td>
    <td>Semester ' . htmlspecialchars($enrollment['semester']) . '</td>
</tr>
<tr>
    <td><b>Enrollment Date:</b></td>
    <td>' . date('F d, Y', strtotime($enrollment['created_at'])) . '</td>
</tr>
<tr>
    <td><b>Application Status:</b></td>
    <td><b>' . strtoupper($enrollment['status']) . '</b></td>
</tr>
</table>';

$pdf->writeHTML($info_html, true, false, true, false, '');
$pdf->Ln(10);

// Subjects header
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 10, 'ENROLLED SUBJECTS', 0, 1, 'L', true);
$pdf->Ln(5);

if (!empty($subjects) && is_array($subjects)) {
    $pdf->SetFont('helvetica', '', 11);
    
    $subject_html = '
    <table border="0" cellpadding="5" cellspacing="0">
    <tr style="background-color:#f9f9f9;">
        <th width="10%" align="center">#</th>
        <th width="90%">Subject Name</th>
    </tr>';
    
    foreach ($subjects as $index => $subject) {
        $bg_color = ($index % 2 == 0) ? '#ffffff' : '#f9f9f9';
        $subject_html .= '
        <tr style="background-color:' . $bg_color . ';">
            <td align="center">' . ($index + 1) . '</td>
            <td>' . htmlspecialchars($subject) . '</td>
        </tr>';
    }
    
    $subject_html .= '
    <tr style="background-color:#f0f0f0;">
        <td colspan="2" align="right"><b>Total Subjects: ' . count($subjects) . '</b></td>
    </tr>
    </table>';
    
    $pdf->writeHTML($subject_html, true, false, true, false, '');
} else {
    $pdf->SetFont('helvetica', 'I', 11);
    $pdf->Cell(0, 10, 'No subjects enrolled.', 0, 1);
}

$pdf->Ln(20);

// Signature Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'ACKNOWLEDGEMENT AND SIGNATURE', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 8, 'I hereby certify that all information provided in this enrollment form is true and correct to the best of my knowledge. I agree to abide by the rules and regulations of Aurora State College of Technology.', 0, 'L');

$pdf->Ln(15);

// Signature lines
$y = $pdf->GetY();
$pdf->SetFont('helvetica', '', 10);

// Student signature
$pdf->Line(25, $y, 90, $y);
$pdf->SetXY(25, $y + 5);
$pdf->Cell(65, 5, 'Student\'s Signature', 0, 0, 'C');
$pdf->SetXY(25, $y + 10);
$pdf->Cell(65, 5, 'Date: ' . date('m/d/Y'), 0, 0, 'C');

// Registrar signature
$pdf->Line(110, $y, 175, $y);
$pdf->SetXY(110, $y + 5);
$pdf->Cell(65, 5, 'Registrar\'s Office', 0, 0, 'C');
$pdf->SetXY(110, $y + 10);
$pdf->Cell(65, 5, 'Date: ______________', 0, 0, 'C');

$pdf->Ln(20);

// Footer
$pdf->SetY(-25);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated on ' . date('F d, Y h:i A') . ' | ASCOT BSIT Enrollment System', 0, 0, 'C');
$pdf->Ln(5);
$pdf->Cell(0, 10, 'This is a system-generated document. Official copy available at the Registrar\'s Office.', 0, 0, 'C');

// Output PDF
$filename = 'ASCOT_Enrollment_' . preg_replace('/[^a-z0-9]/i', '_', $enrollment['fullname']) . '.pdf';
$pdf->Output($filename, 'D'); // 'D' for download

$stmt->close();
$conn->close();
exit();
?>