<?php
include('../config/database.php');

$search = isset($_GET['q']) ? $_GET['q'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$search = mysqli_real_escape_string($connect, $search);

$where = "";
if ($search != '') {
    $where = "WHERE g.GateName LIKE '%$search%' OR g.GateID LIKE '%$search%' OR g.Road LIKE '%$search%' OR t.TownshipName LIKE '%$search%'";
}

$countQuery = "SELECT COUNT(*) as total FROM gate g LEFT JOIN township t ON g.TownshipID = t.TownshipID $where";
$countResult = mysqli_query($connect, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['total'];

$gateQuery = "
    SELECT g.GateID, g.GateName, g.Road, t.TownshipName 
    FROM gate g 
    LEFT JOIN township t ON g.TownshipID = t.TownshipID
    $where
    ORDER BY g.GateName ASC, t.TownshipName ASC
    LIMIT $offset, $limit";
    
$gateResult = mysqli_query($connect, $gateQuery);

$results = [];
while ($row = mysqli_fetch_assoc($gateResult)) {
    $township = $row['TownshipName'] ? $row['TownshipName'] : 'N/A';
    $road = $row['Road'] ? $row['Road'] : 'N/A';
    $displayText = "{$row['GateName']} - {$road} ({$township})";
    $results[] = [
        'id' => $row['GateID'],
        'text' => $displayText
    ];
}

$response = [
    'results' => $results,
    'pagination' => [
        'more' => ($offset + $limit) < $total
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>
