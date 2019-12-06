<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package auth_azureb2c
 * @author Gopal Sharma <gopalsharma66@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2020 Gopal Sharma <gopalsharma66@gmail.com>
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/auth.php');
require_once(__DIR__.'/lib.php');

require_login();

$action = optional_param('action', null, PARAM_TEXT);

$azureb2ctoken = $DB->get_record('auth_azureb2c_token', ['userid' => $USER->id]);
$azureb2cconnected = (!empty($azureb2ctoken)) ? true : false;

$azureb2cloginconnected = ($USER->auth === 'azureb2c') ? true : false;

if (!empty($action)) {
    if ($action === 'connectlogin' && $azureb2cloginconnected === false) {
        // Use authorization request login flow to connect existing users.
        if (!is_enabled_auth('azureb2c')) {
            throw new \moodle_exception('errorazureb2cnotenabled', 'auth_azureb2c');
        }
        auth_azureb2c_connectioncapability($USER->id, 'connect', true);
        $auth = new \auth_azureb2c\loginflow\authcode;
        $auth->set_httpclient(new \auth_azureb2c\httpclient());
        $auth->initiateauthrequest();
    } else if ($action === 'disconnectlogin' && $azureb2cloginconnected === true) {
        if (is_enabled_auth('manual') === true) {
            auth_azureb2c_connectioncapability($USER->id, 'disconnect', true);
            $auth = new \auth_plugin_azureb2c;
            $auth->set_httpclient(new \auth_azureb2c\httpclient());
            $auth->disconnect();
        }
    } else {
        throw new \moodle_exception('errorucpinvalidaction', 'auth_azureb2c');
    }
} else {
    $PAGE->set_url('/auth/azureb2c/ucp.php');
    $usercontext = \context_user::instance($USER->id);
    $PAGE->set_context(\context_system::instance());
    $PAGE->set_pagelayout('standard');
    $USER->editing = false;
    $authconfig = get_config('auth_azureb2c');
    $opname = (!empty($authconfig->opname)) ? $authconfig->opname : get_string('pluginname', 'auth_azureb2c');

    $ucptitle = get_string('ucp_title', 'auth_azureb2c', $opname);
    $PAGE->navbar->add($ucptitle, $PAGE->url);
    $PAGE->set_title($ucptitle);

    echo $OUTPUT->header();
    echo \html_writer::tag('h2', $ucptitle);
    echo get_string('ucp_general_intro', 'auth_azureb2c', $opname);
    echo '<br /><br />';

    if (optional_param('o365accountconnected', null, PARAM_TEXT) == 'true') {
        echo \html_writer::start_div('connectionstatus alert alert-error');
        echo \html_writer::tag('h5', get_string('ucp_o365accountconnected', 'auth_azureb2c'));
        echo \html_writer::end_div();
    }

    // Login status.
    echo \html_writer::start_div('auth_azureb2c_ucp_indicator');
    echo \html_writer::tag('h4', get_string('ucp_login_status', 'auth_azureb2c', $opname));
    if ($azureb2cloginconnected === true) {
        echo \html_writer::tag('h4', get_string('ucp_status_enabled', 'auth_azureb2c'), ['class' => 'notifysuccess']);
        if (is_enabled_auth('manual') === true) {
            if (auth_azureb2c_connectioncapability($USER->id, 'disconnect')) {
                $connectlinkuri = new \moodle_url('/auth/azureb2c/ucp.php', ['action' => 'disconnectlogin']);
                $strdisconnect = get_string('ucp_login_stop', 'auth_azureb2c', $opname);
                $linkhtml = \html_writer::link($connectlinkuri, $strdisconnect);
                echo \html_writer::tag('h5', $linkhtml);
                echo \html_writer::span(get_string('ucp_login_stop_desc', 'auth_azureb2c', $opname));
            }
        }
    } else {
        echo \html_writer::tag('h4', get_string('ucp_status_disabled', 'auth_azureb2c'), ['class' => 'notifyproblem']);
        if (auth_azureb2c_connectioncapability($USER->id, 'connect')) {
            $connectlinkuri = new \moodle_url('/auth/azureb2c/ucp.php', ['action' => 'connectlogin']);
            $linkhtml = \html_writer::link($connectlinkuri, get_string('ucp_login_start', 'auth_azureb2c', $opname));
            echo \html_writer::tag('h5', $linkhtml);
            echo \html_writer::span(get_string('ucp_login_start_desc', 'auth_azureb2c', $opname));
        }
    }
    echo \html_writer::end_div();

    echo $OUTPUT->footer();
}
