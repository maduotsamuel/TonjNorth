<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please complete all fields.']);
    exit;
}

$to = 'boldit2015@gmail.com';
$subject = 'New message from Tonj North FC website';
$body = "Name: $name\nEmail/Phone: $email\n\nMessage:\n$message";
$headers = "From: no-reply@tonjnorthfc.com\r\nReply-To: $email\r\nX-Mailer: PHP/" . phpversion();

$mailSent = @mail($to, $subject, $body, $headers);

if ($mailSent) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to send message right now.']);
}
