<?php
session_start();

header('Content-Type: application/json');

// Generate a new CAPTCHA question
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$question = "$num1 + $num2 = ?";
$answer = $num1 + $num2;

// Store the new question and answer in the session
$_SESSION['captcha_question'] = $question;
$_SESSION['captcha_answer'] = $answer;

// Send the new question back as a JSON response
echo json_encode(['question' => $question]);
?>