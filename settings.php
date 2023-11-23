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

require_once(__DIR__.'/lib.php');

$configkey = new lang_string('cfg_scope_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_scope_desc', 'auth_azureb2c');
$configdefault = "your_scope_value";
$settings->add(new admin_setting_configtext('auth_azureb2c/scope', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_opname_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_opname_desc', 'auth_azureb2c');
$configdefault = new lang_string('pluginname', 'auth_azureb2c');
$settings->add(new admin_setting_configtext('auth_azureb2c/opname', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_clientid_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_clientid_desc', 'auth_azureb2c');
$settings->add(new admin_setting_configtext('auth_azureb2c/clientid', $configkey, $configdesc, '', PARAM_TEXT));

$configkey = new lang_string('cfg_clientsecret_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_clientsecret_desc', 'auth_azureb2c');
$settings->add(new admin_setting_configtext('auth_azureb2c/clientsecret', $configkey, $configdesc, '', PARAM_TEXT));

$configkey = new lang_string('cfg_authendpoint_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_authendpoint_desc', 'auth_azureb2c');
$configdefault = 'https://tenantname.b2clogin.com/common/oauth2/authorize?p=signinandsignup_policy_name';
$settings->add(new admin_setting_configtext('auth_azureb2c/authendpoint', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_resetpassendpoint_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_resetpassendpoint_desc', 'auth_azureb2c');
$configdefault = "https://tenantname.b2clogin.com/common/oauth2/authorize?p=reset_policy_name";
$settings->add(new admin_setting_configtext('auth_azureb2c/resetpassendpoint', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_editprofileendpoint_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_editprofileendpoint_desc', 'auth_azureb2c');
$configdefault = "https://tenantname.b2clogin.com/common/oauth2/authorize?p=edit_policy_name";
$settings->add(new admin_setting_configtext('auth_azureb2c/editprofileendpoint', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_tokenendpoint_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_tokenendpoint_desc', 'auth_azureb2c');
$configdefault = 'https://tenantname.b2clogin.com/common/oauth2/token?p=signinandsignup_policy_name';
$settings->add(new admin_setting_configtext('auth_azureb2c/tokenendpoint', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_azureb2cresource_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_azureb2cresource_desc', 'auth_azureb2c');
$configdefault = 'https://graph.windows.net';
$settings->add(new admin_setting_configtext('auth_azureb2c/azureb2cresource', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_redirecturi_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_redirecturi_desc', 'auth_azureb2c');
$settings->add(new \auth_azureb2c\form\adminsetting\redirecturi('auth_azureb2c/redirecturi', $configkey, $configdesc));

$configkey = new lang_string('cfg_autoappend_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_autoappend_desc', 'auth_azureb2c');
$configdefault = '';
$settings->add(new admin_setting_configtext('auth_azureb2c/autoappend', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_domainhint_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_domainhint_desc', 'auth_azureb2c');
$configdefault = '';
$settings->add(new admin_setting_configtext('auth_azureb2c/domainhint', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('cfg_loginflow_key', 'auth_azureb2c');
$configdesc = '';
$configdefault = 'authcode';
$settings->add(new \auth_azureb2c\form\adminsetting\loginflow('auth_azureb2c/loginflow', $configkey, $configdesc, $configdefault));

$configkey = new lang_string('cfg_userrestrictions_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_userrestrictions_desc', 'auth_azureb2c');
$configdefault = '';
$settings->add(new admin_setting_configtextarea('auth_azureb2c/userrestrictions', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$label = new lang_string('cfg_debugmode_key', 'auth_azureb2c');
$desc = new lang_string('cfg_debugmode_desc', 'auth_azureb2c');
$settings->add(new \admin_setting_configcheckbox('auth_azureb2c/debugmode', $label, $desc, '0'));


$label = new lang_string('cfg_o365mapping_key', 'auth_azureb2c');
$desc = new lang_string('cfg_o365mapping_desc', 'auth_azureb2c');
$settings->add(new \admin_setting_configcheckbox('auth_azureb2c/o365mapping', $label, $desc, '1'));

$configkey = new lang_string('cfg_icon_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_icon_desc', 'auth_azureb2c');
$configdefault = 'auth_azureb2c:o365';
$icons = [
    [
        'pix' => 'o365',
        'alt' => new lang_string('cfg_iconalt_o365', 'auth_azureb2c'),
        'component' => 'auth_azureb2c',
    ],
    [
        'pix' => 't/locked',
        'alt' => new lang_string('cfg_iconalt_locked', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 't/lock',
        'alt' => new lang_string('cfg_iconalt_lock', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 't/go',
        'alt' => new lang_string('cfg_iconalt_go', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 't/stop',
        'alt' => new lang_string('cfg_iconalt_stop', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 't/user',
        'alt' => new lang_string('cfg_iconalt_user', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 'u/user35',
        'alt' => new lang_string('cfg_iconalt_user2', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 'i/permissions',
        'alt' => new lang_string('cfg_iconalt_key', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 'i/cohort',
        'alt' => new lang_string('cfg_iconalt_group', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 'i/groups',
        'alt' => new lang_string('cfg_iconalt_group2', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 'i/mnethost',
        'alt' => new lang_string('cfg_iconalt_mnet', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 'i/permissionlock',
        'alt' => new lang_string('cfg_iconalt_userlock', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 't/more',
        'alt' => new lang_string('cfg_iconalt_plus', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 't/approve',
        'alt' => new lang_string('cfg_iconalt_check', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
    [
        'pix' => 't/right',
        'alt' => new lang_string('cfg_iconalt_rightarrow', 'auth_azureb2c'),
        'component' => 'moodle',
    ],
];
$settings->add(new \auth_azureb2c\form\adminsetting\iconselect('auth_azureb2c/icon', $configkey, $configdesc, $configdefault, $icons));

$configkey = new lang_string('cfg_customicon_key', 'auth_azureb2c');
$configdesc = new lang_string('cfg_customicon_desc', 'auth_azureb2c');
$setting = new admin_setting_configstoredfile('auth_azureb2c/customicon', $configkey, $configdesc, 'customicon');
$setting->set_updatedcallback('auth_azureb2c_initialize_customicon');
$settings->add($setting);
