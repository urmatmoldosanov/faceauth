<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * FaceAuth access rule.
 */
class quizaccess_faceauth extends quiz_access_rule_base {
    /**
     * Inject JS for attempt pages.
     *
     * @param mod_quiz_renderer $renderer
     * @param moodle_page $page
     * @param quiz_attempt $attemptobj
     */
    public function setup_attempt_page($page) {
        global $USER;

        $backendurl = trim((string)get_config('quizaccess_faceauth', 'backend_url'));
        $tenantid = trim((string)get_config('quizaccess_faceauth', 'tenant_id'));
        $interval = (int)get_config('quizaccess_faceauth', 'snapshot_interval');

        if ($interval < 5) {
            $interval = 5;
        }

        $config = array(
            'backendUrl' => $backendurl,
            'tenantId' => $tenantid,
            'snapshotInterval' => $interval,
            'attemptId' => (int)$this->quizobj->get_attemptid(),
            'quizId' => (int)$this->quizobj->get_quizid(),
            'courseModuleId' => (int)$this->quizobj->get_cmid(),
            'userHash' => sha1((string)$USER->id),
        );

        $page->requires->js_call_amd('quizaccess_faceauth/faceauth', 'init', array($config));
    }

    /**
     * This rule does not block quiz start by itself.
     *
     * @param int $numprevattempts
     * @param int $lastattempt
     * @return bool
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        return false;
    }
}
