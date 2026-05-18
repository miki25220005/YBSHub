<?php
include('../config/database.php'); // Connect to the database

// Get the TownshipID from the query parameter
$TownshipID = $_GET['TownshipID'];

// SQL query to delete the township
$Delete = "DELETE FROM township WHERE TownshipID='$TownshipID'";
$ret = mysqli_query($connect, $Delete);

if ($ret) {
    // Success message and redirection
    echo "<script>window.alert('Township Successfully Deleted.')</script>";
    echo "<script>window.location='TownshipEntry.php'</script>";
} else {
    // Error message if the deletion fails
    echo "<p>Something went wrong: " . mysqli_error($connect) . "</p>";
}
?>
