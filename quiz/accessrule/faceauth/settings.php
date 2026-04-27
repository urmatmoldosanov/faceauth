<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('quizaccess_faceauth', get_string('pluginname', 'quizaccess_faceauth'));

    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_faceauth/enabled',
        get_string('enabled', 'quizaccess_faceauth'),
        get_string('enabled_desc', 'quizaccess_faceauth'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_faceauth/backend_url',
        get_string('backend_url', 'quizaccess_faceauth'),
        get_string('backend_url_desc', 'quizaccess_faceauth'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_faceauth/tenant_id',
        get_string('tenant_id', 'quizaccess_faceauth'),
        get_string('tenant_id_desc', 'quizaccess_faceauth'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'quizaccess_faceauth/shared_secret',
        get_string('shared_secret', 'quizaccess_faceauth'),
        get_string('shared_secret_desc', 'quizaccess_faceauth'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_faceauth/snapshot_interval',
        get_string('snapshot_interval', 'quizaccess_faceauth'),
        get_string('snapshot_interval_desc', 'quizaccess_faceauth'),
        '30',
        PARAM_INT
    ));

    $ADMIN->add('modsettings', $settings);
}
