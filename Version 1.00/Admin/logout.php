<?php
// Start the session
session_start();

// Destroy the session
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #000; /* Black background */
            color: #fff; /* White text */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .logout-container {
            text-align: center;
            background: #000; /* Black background */
            padding: 30px;
            border: 2px solid #fff; /* White border */
            border-radius: 10px;
            max-width: 400px;
            width: 100%;
        }

        .logout-container h1 {
            font-size: 2em;
            color: #fff; /* White text */
            margin-bottom: 10px;
        }

        .logout-container p {
            font-size: 1.1em;
            color: #ccc; /* Slightly lighter gray for the text */
            margin-bottom: 20px;
        }

        .logout-container a {
            display: inline-block;
            padding: 10px 20px;
            font-size: 1em;
            color: #000; /* Black text */
            background-color: #fff; /* White background */
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .logout-container a:hover {
            background-color: #ccc; /* Gray background for hover */
            color: #000; /* Black text */
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <h1>See You Again!</h1>
        <p>You have successfully logged out. Have a great day!</p>
        <a href="AdminLogin.php">Go to Login</a>
    </div>
</body>
</html>
