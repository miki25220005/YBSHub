<?php
function AutoID($tableName, $columnName, $prefix, $length) {
    global $connect;

    // Find the highest numeric ID in the table
    $query = "SELECT MAX(CAST(SUBSTRING($columnName, LENGTH('$prefix') + 1) AS UNSIGNED)) AS maxID FROM `$tableName`";
    
    // Execute the query
    $result = mysqli_query($connect, $query);
    if (!$result) {
        die("SQL Error in AutoID function: " . mysqli_error($connect) . "<br>Query: " . $query);
    }

    $row = mysqli_fetch_assoc($result);
    $maxID = $row['maxID'] ?? 0; // Default to 0 if no records exist
    
    // Increment and format the new ID
    $nextID = $maxID + 1;
    return $prefix . str_pad($nextID, $length - strlen($prefix), '0', STR_PAD_LEFT);
}

function NumberFormatter($number, $n) {
    return str_pad((int) $number, $n, "0", STR_PAD_LEFT);
}
?>