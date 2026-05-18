<?php
include('../config/database.php');

// ✅ Add a gate to a route in the session
function AddGateToRoute($RouteID, $BusID, $Direction, $Notes, $GateID, $AdminID) {
    include('../config/database.php');

    // Fetch bus and gate details
    $busQuery = "SELECT * FROM bus WHERE BusID='$BusID'";
    $busResult = mysqli_query($connect, $busQuery);
    $bus = mysqli_fetch_assoc($busResult);

    $gateQuery = "SELECT * FROM gate WHERE GateID='$GateID'";
    $gateResult = mysqli_query($connect, $gateQuery);
    $gate = mysqli_fetch_assoc($gateResult);

    if (!$bus || !$gate) {
        echo "<script>alert('Invalid Bus or Gate selection.');</script>";
        return;
    }

    // ✅ Prepare gate details
    $gateDetails = [
        'GateID' => $GateID,
        'GateName' => $gate['GateName'],
    ];

    // ✅ Store route, direction, and gates in session
    if (!isset($_SESSION['Route_Process'][$RouteID])) {
        $_SESSION['Route_Process'][$RouteID] = [
            'BusID' => $BusID,
            'BusNo' => $bus['BusNo'],
            'Path' => $bus['Path'],
            'Direction' => $Direction, // ✅ Store Direction
            'Notes' => $Notes,         // ✅ Store Notes
            'AdminID' => $AdminID,
            'Gates' => [],
        ];
    }

    // ✅ Add gate to the route session
    $_SESSION['Route_Process'][$RouteID]['Gates'][$GateID] = $gateDetails;
}

// ✅ Remove a gate from a route in the session
function RemoveGateFromRoute($RouteID, $GateID) {
    if (isset($_SESSION['Route_Process'][$RouteID]['Gates'][$GateID])) {
        unset($_SESSION['Route_Process'][$RouteID]['Gates'][$GateID]);
    }
}

// ✅ Save the route and gates to the database
function SaveRouteWithGates() {
    include('../config/database.php');

    foreach ($_SESSION['Route_Process'] as $RouteID => $routeDetails) {
        $BusID = $routeDetails['BusID'];
        $AdminID = $routeDetails['AdminID'];
        $Notes = mysqli_real_escape_string($connect, $routeDetails['Notes']);
        $Direction = mysqli_real_escape_string($connect, $routeDetails['Direction']); // ✅ Get Direction from session

        // ✅ Prevent duplicate Forward/Reverse routes for the same bus
        $checkQuery = "SELECT COUNT(*) as count FROM route WHERE BusID='$BusID' AND Direction='$Direction'";
        $checkResult = mysqli_query($connect, $checkQuery);
        $row = mysqli_fetch_assoc($checkResult);

        if ($row['count'] > 0) {
            echo "<script>alert('This bus already has a $Direction route!');</script>";
            return;
        }

        // ✅ Insert Route with Direction
        $query = "INSERT INTO route (RouteID, BusID, AdminID, Notes, Direction) 
                  VALUES ('$RouteID', '$BusID', '$AdminID', '$Notes', '$Direction')";
        mysqli_query($connect, $query);

        // ✅ Insert each gate associated with the route
        $pos = 1;
        foreach ($routeDetails['Gates'] as $gate) {
            $GateID = $gate['GateID'];
            $gateQuery = "INSERT INTO route_gate (RouteID, GateID, Position) VALUES ('$RouteID', '$GateID', $pos)";
            mysqli_query($connect, $gateQuery);
            $pos++;
        }
    }

    // ✅ Clear session after saving
    unset($_SESSION['Route_Process']);
}
?>
