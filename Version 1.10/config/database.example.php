<?php 	
//** connect with databases (TEMPLATE) **//
// Rename this file to database.php and fill in your actual credentials.
// Do NOT commit the real database.php to version control.

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0) {
    // Local environment
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'YBSHub';
} else {
    // Hosting environment
    $host = 'your_production_host';
    $user = 'your_production_user';
    $password = 'your_production_password';
    $database = 'your_production_database';
}

$connect = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($connect, "utf8mb4");

// if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
//	header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//	exit(); 
// }
?>
