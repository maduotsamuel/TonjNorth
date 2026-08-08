<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$name = trim(strip_tags((string) ($_POST['name'] ?? '')));
$email = trim(strip_tags((string) ($_POST['email'] ?? '')));
$message = trim(strip_tags((string) ($_POST['message'] ?? '')));

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please complete all fields.']);
    exit;
}

if (mb_strlen($name, 'UTF-8') > 120 || mb_strlen($message, 'UTF-8') > 4000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The form contains values that are too long.']);
    exit;
}

$isEmail = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
$isPhone = preg_match('/^\+?[0-9\s\-()]{7,15}$/', $email) === 1;
if (!$isEmail && !$isPhone) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address or phone number.']);
    exit;
}

$to = 'boldit2015@gmail.com';
$subject = 'New message from Tonj North FC website';
$body = "Name: $name\nEmail/Phone: $email\n\nMessage:\n$message";
$mailtoUrl = "mailto:$to?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);

echo json_encode([
    'success' => true,
    'message' => 'Opening your email app with your message ready to send.',
    'mailtoUrl' => $mailtoUrl
]);
