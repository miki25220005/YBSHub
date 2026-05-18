<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/database.php');

// Ensure only admins or internal auto-scripts can run this
if (!isset($_SESSION['AdminName']) && !isset($isAutoBackupTriggered)) {
    exit('Unauthorized access');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'auto';

// Ensure backups directory exists
$autoPath = __DIR__ . '/backups/auto/';
$manualPath = __DIR__ . '/backups/manual/';
if (!is_dir($autoPath)) mkdir($autoPath, 0777, true);
if (!is_dir($manualPath)) mkdir($manualPath, 0777, true);

// Get the database name (assuming 'YBSHub' based on connect.php)
$dbQuery = mysqli_query($connect, "SELECT DATABASE()");
$dbRow = mysqli_fetch_row($dbQuery);
$databaseName = $dbRow[0] ? $dbRow[0] : 'YBSHub';

// Start creating the SQL file content
$sqlScript = "-- Database Backup for $databaseName\n";
$sqlScript .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Get all tables
$tables = array();
$result = mysqli_query($connect, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Get table creation script
    $createTableQuery = mysqli_query($connect, "SHOW CREATE TABLE `$table`");
    $createTableRow = mysqli_fetch_row($createTableQuery);
    
    $sqlScript .= "\n-- Table structure for table `$table`\n";
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
    $sqlScript .= $createTableRow[1] . ";\n\n";
    
    // Get data for the table
    $dataQuery = mysqli_query($connect, "SELECT * FROM `$table`");
    $numRows = mysqli_num_rows($dataQuery);
    $numFields = mysqli_num_fields($dataQuery);
    
    if ($numRows > 0) {
        $sqlScript .= "-- Dumping data for table `$table`\n";
        while ($row = mysqli_fetch_row($dataQuery)) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $numFields; $j++) {
                if (isset($row[$j])) {
                    $val = addslashes($row[$j]);
                    $val = str_replace("\n", "\\n", $val);
                    $sqlScript .= "'" . $val . "'";
                } else {
                    $sqlScript .= "NULL";
                }
                if ($j < ($numFields - 1)) {
                    $sqlScript .= ",";
                }
            }
            $sqlScript .= ");\n";
        }
        $sqlScript .= "\n";
    }
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Save the file
$dateString = date('Y-m-d_H-i-s');
$filename = "{$databaseName}_backup_{$dateString}.sql";

if ($action == 'manual') {
    $backupFile = $manualPath . $filename;
    file_put_contents($backupFile, $sqlScript);
    
    // Force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($backupFile));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($backupFile));
    readfile($backupFile);
    exit;
} else {
    // Auto backup
    $backupFile = $autoPath . $filename;
    file_put_contents($backupFile, $sqlScript);
    // Since auto-backups aren't deleted as per the user's request, we just save and exit.
}
?>
