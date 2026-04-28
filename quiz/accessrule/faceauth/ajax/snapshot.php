<?php
// This file is part of Moodle - http://moodle.org/

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();

header('Content-Type: application/json');

if (!get_config('quizaccess_faceauth', 'enabled')) {
    http_response_code(403);
    echo json_encode(array('status' => 'block', 'code' => 'plugin_disabled'));
    exit;
}

$backendurl = trim((string)get_config('quizaccess_faceauth', 'backend_url'));
$tenantid = trim((string)get_config('quizaccess_faceauth', 'tenant_id'));
$sharedsecret = (string)get_config('quizaccess_faceauth', 'shared_secret');

if ($backendurl === '' || $tenantid === '' || $sharedsecret === '') {
    http_response_code(500);
    echo json_encode(array('status' => 'warn', 'code' => 'plugin_misconfigured'));
    exit;
}

$rawbody = file_get_contents('php://input');
if ($rawbody === false || $rawbody === '') {
    http_response_code(400);
    echo json_encode(array('status' => 'warn', 'code' => 'empty_payload'));
    exit;
}

$input = json_decode($rawbody, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(array('status' => 'warn', 'code' => 'invalid_json'));
    exit;
}


$sesskey = isset($input['sesskey']) ? clean_param($input['sesskey'], PARAM_ALPHANUM) : '';
if (!confirm_sesskey($sesskey)) {
    http_response_code(403);
    echo json_encode(array('status' => 'block', 'code' => 'invalid_sesskey'));
    exit;
}
$attemptid = isset($input['attempt_id']) ? clean_param($input['attempt_id'], PARAM_INT) : 0;
$quizid = isset($input['quiz_id']) ? clean_param($input['quiz_id'], PARAM_INT) : 0;
$cmid = isset($input['cmid']) ? clean_param($input['cmid'], PARAM_INT) : 0;
$eventtime = isset($input['event_time']) ? clean_param($input['event_time'], PARAM_RAW_TRIMMED) : '';
$imagebase64 = isset($input['image_base64']) ? $input['image_base64'] : '';

if ($attemptid <= 0 || $quizid <= 0 || $cmid <= 0 || $eventtime === '' || $imagebase64 === '') {
    http_response_code(400);
    echo json_encode(array('status' => 'warn', 'code' => 'invalid_payload'));
    exit;
}

$payload = array(
    'tenant_id' => $tenantid,
    'attempt_id' => $attemptid,
    'quiz_id' => $quizid,
    'cmid' => $cmid,
    'event_time' => $eventtime,
    'image_base64' => $imagebase64,
    'nonce' => bin2hex(random_bytes(16)),
);

$payloadjson = json_encode($payload);
if ($payloadjson === false) {
    http_response_code(500);
    echo json_encode(array('status' => 'warn', 'code' => 'encode_error'));
    exit;
}

$signature = hash_hmac('sha256', $payloadjson, $sharedsecret);

$curl = new curl();
$response = $curl->post($backendurl, $payloadjson, array(
    'CURLOPT_HTTPHEADER' => array(
        'Content-Type: application/json',
        'X-Tenant-Id: ' . $tenantid,
        'X-Signature: ' . $signature,
    ),
    'CURLOPT_TIMEOUT' => 10,
    'CURLOPT_CONNECTTIMEOUT' => 5,
));

if ($response === false) {
    http_response_code(502);
    echo json_encode(array('status' => 'warn', 'code' => 'backend_unavailable'));
    exit;
}

$decoded = json_decode($response, true);
if (!is_array($decoded) || !isset($decoded['status'])) {
    http_response_code(502);
    echo json_encode(array('status' => 'warn', 'code' => 'invalid_backend_response'));
    exit;
}

echo json_encode($decoded);
