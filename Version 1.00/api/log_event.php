<?php
// api/log_event.php - Client-side tracking endpoint

// Enable CORS if needed (optional based on setup, but good practice for APIs)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit;
}

// Get raw POST data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->action_type) || empty($data->action_type)) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. 'action_type' is required."]);
    exit;
}

// Include database and analytics logic
// Move up one directory to find Admin/connect.php and analytics.php
$baseDir = dirname(__DIR__);
require_once $baseDir . '/Admin/connect.php';
require_once $baseDir . '/analytics.php';

$action_type = htmlspecialchars(strip_tags($data->action_type));
$action_value = isset($data->action_value) ? htmlspecialchars(strip_tags($data->action_value)) : '';
$township_id = isset($data->township_id) && is_numeric($data->township_id) ? (int)$data->township_id : null;

// Log the event
logStat($connect, $action_type, $action_value, $township_id);

http_response_code(200);
echo json_encode(["message" => "Event logged successfully."]);
?>
