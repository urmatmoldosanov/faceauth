<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'FaceAuth access rule';
$string['enabled'] = 'Enable FaceAuth';
$string['enabled_desc'] = 'Enable camera checks for quiz attempts.';
$string['backend_url'] = 'Client backend URL';
$string['backend_url_desc'] = 'HTTPS endpoint of client backend for snapshot relay.';
$string['tenant_id'] = 'Tenant ID';
$string['tenant_id_desc'] = 'Tenant identifier issued by FaceAuth Core.';
$string['shared_secret'] = 'Shared secret';
$string['shared_secret_desc'] = 'Secret used for HMAC signing (do not expose in JS).';
$string['snapshot_interval'] = 'Snapshot interval (seconds)';
$string['snapshot_interval_desc'] = 'How often a camera snapshot is sent during quiz attempt.';
$string['min_request_interval'] = 'Minimum relay interval (seconds)';
$string['min_request_interval_desc'] = 'Minimum allowed time between snapshot relay requests per attempt.';
