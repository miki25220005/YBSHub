<?php
session_start();
include('../config/database.php');


header('Content-Type: application/json');

if (!isset($_SESSION['AdminName'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

if (!isset($_GET['BusID']) || !isset($_GET['Direction'])) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit();
}

$BusID = mysqli_real_escape_string($connect, $_GET['BusID']);
$newDirection = mysqli_real_escape_string($connect, $_GET['Direction']);

// ✅ Find the RouteID for this BusID in the new direction
$query = "SELECT RouteID FROM route WHERE BusID = ? AND Direction = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("ss", $BusID, $newDirection);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["success" => true, "RouteID" => $row['RouteID']]);
} else {
    echo json_encode(["success" => false, "message" => "No route found for this direction."]);
}

$stmt->close();
$connect->close();
?>
