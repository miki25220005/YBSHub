<?php
// Function to check for active maintenance and redirect if necessary
function checkMaintenance($connect) {
    // Query the database for an active maintenance schedule
    $query = "SELECT * FROM maintenance_schedule WHERE is_active = 1 AND start_time <= NOW() AND end_time >= NOW() LIMIT 1";
    $result = mysqli_query($connect, $query);

    if (mysqli_num_rows($result) > 0) {
        // Maintenance is active, redirect to under_maintenance.php
        header("Location: Admin/under_maintenance.php");
        exit();
    }
}
?>