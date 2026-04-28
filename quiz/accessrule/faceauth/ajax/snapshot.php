<?php
// This file is part of Moodle - http://moodle.org/

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

require_login();

header('Content-Type: application/json');

global $USER;

$maximagesizebytes = 2 * 1024 * 1024;

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

if (strpos($backendurl, 'https://') !== 0) {
    http_response_code(500);
    echo json_encode(array('status' => 'warn', 'code' => 'backend_url_must_be_https'));
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

if (strlen($imagebase64) > ($maximagesizebytes * 2)) {
    http_response_code(413);
    echo json_encode(array('status' => 'warn', 'code' => 'image_too_large'));
    exit;
}

$normalizedimage = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $imagebase64);
$binaryimage = base64_decode($normalizedimage, true);
if ($binaryimage === false || strlen($binaryimage) === 0 || strlen($binaryimage) > $maximagesizebytes) {
    http_response_code(400);
    echo json_encode(array('status' => 'warn', 'code' => 'invalid_image'));
    exit;
}

try {
    $attemptobj = quiz_attempt::create($attemptid);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(array('status' => 'warn', 'code' => 'attempt_not_found'));
    exit;
}

if ((int)$attemptobj->get_userid() !== (int)$USER->id || (int)$attemptobj->get_quizid() !== $quizid) {
    http_response_code(403);
    echo json_encode(array('status' => 'block', 'code' => 'attempt_access_denied'));
    exit;
}


try {
    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(array('status' => 'warn', 'code' => 'cmid_not_found'));
    exit;
}

if ((int)$cm->instance !== $quizid) {
    http_response_code(403);
    echo json_encode(array('status' => 'block', 'code' => 'cmid_quiz_mismatch'));
    exit;
}

$payload = array(
    'tenant_id' => $tenantid,
    'attempt_id' => $attemptid,
    'quiz_id' => $quizid,
    'cmid' => $cmid,
    'event_time' => $eventtime,
    'image_base64' => $normalizedimage,
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

$eventstatus = clean_param($decoded['status'], PARAM_ALPHA);
$eventcode = isset($decoded['code']) ? clean_param($decoded['code'], PARAM_ALPHANUMEXT) : 'none';
error_log('quizaccess_faceauth relay: attempt=' . $attemptid . ' status=' . $eventstatus . ' code=' . $eventcode);

echo json_encode($decoded);
