<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
require_once($CFG->dirroot . '/quiz/accessrule/faceauth/classes/rule.php');

/**
 * Factory for access rule.
 *
 * @param quiz $quizobj
 * @param int $timenow
 * @param bool $canignoretimelimits
 * @return quizaccess_faceauth|null
 */
function quizaccess_faceauth_make_rule(quiz $quizobj, $timenow, $canignoretimelimits) {
    if (!get_config('quizaccess_faceauth', 'enabled')) {
        return null;
    }

    $backendurl = trim((string)get_config('quizaccess_faceauth', 'backend_url'));
    $tenantid = trim((string)get_config('quizaccess_faceauth', 'tenant_id'));

    if ($backendurl === '' || $tenantid === '') {
        return null;
    }

    return new quizaccess_faceauth($quizobj, $timenow);
}
