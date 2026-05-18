<?php
include('../config/database.php'); // Connect to the database

// Get the BusID from the query parameter
$BusID = $_GET['BusID'];

// SQL query to delete the bus
$Delete = "DELETE FROM bus WHERE BusID='$BusID'";
$ret = mysqli_query($connect, $Delete);

if ($ret) {
    // Success message and redirection
    echo "<script>window.alert('Bus Successfully Deleted.')</script>";
    echo "<script>window.location='BusListForAdmin.php'</script>";
} else {
    // Error message if the deletion fails
    echo "<p>Something went wrong: " . mysqli_error($connect) . "</p>";
}
?>
