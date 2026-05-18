<?php
session_start();
include('../config/database.php'); // Connect to the database

// Check if admin is logged in
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You do not have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Get the GateID from the query parameter
$GateID = $_GET['GateID'];

// SQL query to delete the gate
$Delete = "DELETE FROM gate WHERE GateID='$GateID'";
$ret = mysqli_query($connect, $Delete);

if ($ret) {
    // Success message and redirection
    echo "<script>window.alert('Gate Successfully Deleted.')</script>";
    echo "<script>window.location='GateListForAdmin.php'</script>";
} else {
    // Error message if the deletion fails
    echo "<p>Something went wrong: " . mysqli_error($connect) . "</p>";
}
?>