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
     * @param moodle_page $page
     */
    public function setup_attempt_page($page) {
        $context = context_module::instance($this->quizobj->get_cmid());
        if (!has_capability('quizaccess/faceauth:use', $context)) {
            return;
        }

        $interval = (int)get_config('quizaccess_faceauth', 'snapshot_interval');
        if ($interval < 5) {
            $interval = 5;
        }

        $config = array(
            'snapshotEndpoint' => (new moodle_url('/quiz/accessrule/faceauth/ajax/snapshot.php'))->out(false),
            'snapshotInterval' => $interval,
            'attemptId' => (int)$this->quizobj->get_attemptid(),
            'quizId' => (int)$this->quizobj->get_quizid(),
            'courseModuleId' => (int)$this->quizobj->get_cmid(),
            'sesskey' => sesskey(),
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
