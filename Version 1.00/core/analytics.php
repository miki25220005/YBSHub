<?php
// analytics.php - Centralized tracking logic for YBSHub

// Ensure session is started to track unique visitors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Detects the device type of the current visitor.
 * @return string Device type ('Tablet', 'iOS', 'Android', 'Mobile', 'Computer', 'Unknown')
 */
function detectDeviceType() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
        return 'Tablet';
    }
    if (preg_match('/mobile/i', $userAgent)) {
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }
        return 'Mobile';
    }
    if (preg_match('/windows|macintosh|linux/i', $userAgent)) {
        return 'Computer';
    }
    return 'Unknown';
}

/**
 * Logs a statistic to the database. Automatically captures session_id, referrer, and device_type.
 * 
 * @param mysqli $connect The database connection object
 * @param string $action_type The category of the action (e.g., 'page_view', 'search_bus', 'click')
 * @param string $action_value The specific value or page name (e.g., 'Home', 'bus_details.php', 'YBS-12')
 * @param int|null $township_id Optional township ID if the action relates to a specific township
 */
function logStat($connect, $action_type, $action_value = '', $township_id = null) {
    $device_type = detectDeviceType();
    $session_id = session_id();
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Prevent logging empty action values if it's a page view to keep DB clean
    if ($action_type === 'page_view' && empty($action_value)) {
        $action_value = basename($_SERVER['PHP_SELF']);
    }

    $query = "INSERT INTO website_stats (township_id, action_type, action_value, device_type, session_id, referrer) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connect, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssss', $township_id, $action_type, $action_value, $device_type, $session_id, $referrer);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("Analytics Error: Failed to prepare stat query - " . mysqli_error($connect));
    }
}
?>
