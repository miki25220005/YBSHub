<?php
session_start();
include('../config/database.php');
include('AutoID_Functions.php'); 

if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You do not have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

$GateID = AutoID('gate', 'GateID', 'GTE-', 5);

if (isset($_POST['btnSave'])) {
    $txtGateName = $_POST['txtGateName'];
    $txtLatitude = $_POST['txtLatitude'];
    $txtLongitude = $_POST['txtLongitude'];
    $txtRoad = $_POST['txtRoad'];
    $cmbTownshipID = $_POST['cmbTownshipID'];

    if (empty($txtGateName) || empty($txtLatitude) || empty($txtLongitude) || empty($txtRoad) || empty($cmbTownshipID)) {
        echo "<script>alert('Please fill in all fields, including selecting a location on the map.');</script>";
    } elseif (!is_numeric($txtLatitude) || $txtLatitude < -90 || $txtLatitude > 90) {
        echo "<script>alert('Latitude must be a number between -90 and 90.');</script>";
    } elseif (!is_numeric($txtLongitude) || $txtLongitude < -180 || $txtLongitude > 180) {
        echo "<script>alert('Longitude must be a number between -180 and 180.');</script>";
    } else {
        $insertQuery = "INSERT INTO gate (GateID, GateName, Latitude, Longitude, Road, TownshipID) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connect, $insertQuery);
        mysqli_stmt_bind_param($stmt, "ssssss", $GateID, $txtGateName, $txtLatitude, $txtLongitude, $txtRoad, $cmbTownshipID);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                localStorage.removeItem('gateFormData');
                alert('Gate successfully saved.');
                window.location='GateEntry.php';
            </script>";
        } else {
            echo "<script>alert('Something went wrong: " . addslashes(mysqli_error($connect)) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

$townshipQuery = "SELECT * FROM township ORDER BY TownshipName ASC";
$townshipResult = mysqli_query($connect, $townshipQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Entry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <style>
        .select2-container--default .select2-selection--single {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            height: 2.5rem;
            background-color: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .select2-container--default .select2-selection--single:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151;
            line-height: 1.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.5rem;
        }
        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .select2-results__option {
            padding: 0.5rem 1rem;
            color: #374151;
        }
        .select2-results__option--highlighted {
            background-color: #3b82f6;
            color: #fff;
        }
        #map {
            height: 300px;
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 1.5s linear infinite;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php include('../includes/admheader.php'); ?>

    <main class="flex-grow">
        <div class="max-w-4xl mx-auto mt-24 p-4 sm:p-6 bg-white shadow-lg rounded-lg mb-8 sm:mb-12">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Gate Entry</h2>

            <form action="GateEntry" method="POST" class="space-y-4" id="gateForm">
                <div>
                    <label class="block font-medium text-gray-700">Gate ID</label>
                    <input type="text" name="txtGateID" value="<?php echo $GateID; ?>" readonly class="w-full px-4 py-2 border rounded-lg bg-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Gate Name</label>
                    <input type="text" name="txtGateName" id="txtGateName" placeholder="Enter Gate Name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                </div>
                <!-- Hidden fields for Latitude and Longitude -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700">Latitude</label>
                        <input type="text" name="txtLatitude" id="latitude" readonly placeholder="Select on map" class="w-full px-4 py-2 border rounded-lg bg-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700">Longitude</label>
                        <input type="text" name="txtLongitude" id="longitude" readonly placeholder="Select on map" class="w-full px-4 py-2 border rounded-lg bg-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                    </div>
                </div>
                <!-- Button to toggle map -->
                <div>
                    <button type="button" id="toggleMapBtn" class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-700 text-white rounded-lg shadow-md hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 transition-all duration-300 ease-in-out transform hover:scale-105 flex items-center justify-center">
                        <i class="fas fa-map-marker-alt mr-2"></i> Select Location on Map
                    </button>
                </div>
                <!-- Map container (hidden by default) -->
                <div id="mapContainer" class="hidden">
                    <div id="map">
                        <div class="flex items-center justify-center h-full bg-gray-100 rounded-lg">
                            <div class="text-center p-6">
                                <i class="fas fa-spinner text-3xl text-blue-500 spinner mb-4"></i>
                                <p class="text-lg font-semibold text-gray-700">Loading Map...</p>
                                <p class="text-sm text-gray-500 mt-2">Please wait a moment while we fetch the gate location.</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Click on the map to select the gate's location.</p>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Road</label>
                    <input type="text" name="txtRoad" id="txtRoad" placeholder="Enter Road Name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                </div>
                <div>
                    <label for="townshipSelect" class="block font-medium text-gray-700 mb-1">Township</label>
                    <select name="cmbTownshipID" id="townshipSelect" required class="w-full">
                        <option value="">-- Select Township --</option>
                        <?php while ($row = mysqli_fetch_assoc($townshipResult)) { ?>
                            <option value="<?php echo $row['TownshipID']; ?>">
                                <?php echo htmlspecialchars($row['TownshipName']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-3 sm:space-y-0 mt-4">
                    <button type="submit" name="btnSave" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-500 to-green-700 text-white rounded-lg shadow-md hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition-all duration-300 ease-in-out transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i> Save
                    </button>
                    <button type="reset" id="resetBtn" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-700 text-white rounded-lg shadow-md hover:from-gray-600 hover:to-gray-800 focus:ring-4 focus:ring-gray-300 transition-all duration-300 ease-in-out transform hover:scale-105">
                        <i class="fas fa-undo-alt mr-2"></i> Reset
                    </button>
                    <a href="GateListForAdmin.php" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-700 text-white rounded-lg shadow-md hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 transition-all duration-300 ease-in-out transform hover:scale-105 text-center">
                        <i class="fas fa-list mr-2"></i> View Gate List
                    </a>
                </div>
            </form>
        </div>
    </main>

    <?php include('../includes/admfooter.php'); ?>

<script>
$(document).ready(function() {
    // Initialize Select2 for township dropdown
    $('#townshipSelect').select2({
        placeholder: "-- Select Township --",
        allowClear: true,
        width: '100%',
        dropdownCssClass: 'text-gray-700',
    });

    let map;
    let marker;
    const defaultLocation = [16.8409, 96.1735]; // Default to Yangon

    // Function to initialize the map
    function initMap() {
        map = L.map('map').setView(defaultLocation, 12);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        }).addTo(map);

        // Restore marker if it exists in localStorage
        const savedData = JSON.parse(localStorage.getItem('gateFormData'));
        if (savedData && savedData.latitude && savedData.longitude) {
            const savedLat = parseFloat(savedData.latitude);
            const savedLng = parseFloat(savedData.longitude);
            if (!isNaN(savedLat) && !isNaN(savedLng)) {
                const savedLocation = [savedLat, savedLng];
                marker = L.marker(savedLocation).addTo(map).bindPopup('Selected Location');
                map.setView(savedLocation, 15);
            }
        }

        // Add click event listener to the map
        map.on('click', function(event) {
            const lat = event.latlng.lat;
            const lng = event.latlng.lng;

            // Update the latitude and longitude fields
            $('#latitude').val(lat.toFixed(6));
            $('#longitude').val(lng.toFixed(6));

            // Place or update marker
            if (marker) {
                marker.setLatLng(event.latlng);
            } else {
                marker = L.marker(event.latlng).addTo(map).bindPopup('Selected Location');
            }

            // Center the map on the clicked location
            map.setView(event.latlng, Math.max(map.getZoom(), 15));

            // Save the updated location to localStorage
            const formData = {
                gateName: $('#txtGateName').val(),
                latitude: $('#latitude').val(),
                longitude: $('#longitude').val(),
                road: $('#txtRoad').val(),
                townshipID: $('#townshipSelect').val()
            };
            localStorage.setItem('gateFormData', JSON.stringify(formData));
        });
    }

    // Restore form data from localStorage on page load
    const savedData = JSON.parse(localStorage.getItem('gateFormData'));
    if (savedData) {
        $('#txtGateName').val(savedData.gateName || '');
        $('#latitude').val(savedData.latitude || '');
        $('#longitude').val(savedData.longitude || '');
        $('#txtRoad').val(savedData.road || '');
        if (savedData.townshipID) {
            $('#townshipSelect').val(savedData.townshipID).trigger('change');
        }
    }

    // Save form data to localStorage on input change
    $('#txtGateName, #txtRoad').on('input', function() {
        const formData = {
            gateName: $('#txtGateName').val(),
            latitude: $('#latitude').val(),
            longitude: $('#longitude').val(),
            road: $('#txtRoad').val(),
            townshipID: $('#townshipSelect').val()
        };
        localStorage.setItem('gateFormData', JSON.stringify(formData));
    });

    $('#townshipSelect').on('change', function() {
        const formData = {
            gateName: $('#txtGateName').val(),
            latitude: $('#latitude').val(),
            longitude: $('#longitude').val(),
            road: $('#txtRoad').val(),
            townshipID: $('#townshipSelect').val()
        };
        localStorage.setItem('gateFormData', JSON.stringify(formData));
    });

    // Toggle map visibility and initialize map on first show
    let mapInitialized = false;
    $('#toggleMapBtn').click(function() {
        $('#mapContainer').toggleClass('hidden');
        $(this).html(function(i, html) {
            return html.includes('Select') ? '<i class="fas fa-times mr-2"></i> Hide Map' : '<i class="fas fa-map-marker-alt mr-2"></i> Select Location on Map';
        }).toggleClass('from-blue-500 from-red-500 to-blue-700 to-red-700');

        if (!$('#mapContainer').hasClass('hidden') && !mapInitialized) {
            initMap();
            mapInitialized = true;
        } else if (!$('#mapContainer').hasClass('hidden')) {
            // Resize map when shown
            setTimeout(function() {
                if (map) {
                    map.invalidateSize();
                    const savedData = JSON.parse(localStorage.getItem('gateFormData'));
                    if (savedData && savedData.latitude && savedData.longitude) {
                        const savedLat = parseFloat(savedData.latitude);
                        const savedLng = parseFloat(savedData.longitude);
                        if (!isNaN(savedLat) && !isNaN(savedLng)) {
                            map.setView([savedLat, savedLng], 15);
                            return;
                        }
                    }
                    map.setView(defaultLocation, 12);
                }
            }, 100);
        }
    });

    // Reset form and clear localStorage
    $('#resetBtn').click(function() {
        $('#latitude').val('');
        $('#longitude').val('');
        $('#txtGateName').val('');
        $('#txtRoad').val('');
        $('#townshipSelect').val('').trigger('change');
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        if (map) {
            map.setView(defaultLocation, 12);
        }
        localStorage.removeItem('gateFormData');
    });

    // Prevent form submission if latitude or longitude is empty
    $('#gateForm').on('submit', function(e) {
        const lat = $('#latitude').val();
        const lng = $('#longitude').val();
        if (!lat || !lng) {
            alert('Please select a location on the map.');
            e.preventDefault();
        }
    });

});
</script>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>
</body>
</html>