<?php
// Simple test to check TCPDF
echo "Testing TCPDF Installation...<br><br>";

$tcpdf_path = __DIR__ . '/tcpdf/tcpdf.php';

if (file_exists($tcpdf_path)) {
    echo "✅ TCPDF found at: " . $tcpdf_path . "<br>";
    
    // Check folder structure
    echo "<br>Checking required folders:<br>";
    $folders = ['tcpdf/fonts', 'tcpdf/config', 'tcpdf/images'];
    foreach ($folders as $folder) {
        if (is_dir($folder)) {
            echo "✅ $folder exists<br>";
        } else {
            echo "❌ $folder missing<br>";
        }
    }
    
    // Try to include TCPDF
    try {
        require_once($tcpdf_path);
        echo "<br>✅ TCPDF loaded successfully!<br>";
        
        // Create simple PDF
        $pdf = new TCPDF();
        echo "✅ TCPDF class instantiated!<br>";
        
    } catch (Exception $e) {
        echo "<br>❌ Error loading TCPDF: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ TCPDF not found. Please make sure:<br>";
    echo "1. You extracted the TCPDF zip file<br>";
    echo "2. The folder is named 'tcpdf' (lowercase)<br>";
    echo "3. The tcpdf folder is in the same directory as this script<br>";
    echo "<br>Current directory: " . __DIR__ . "<br>";
}
?>