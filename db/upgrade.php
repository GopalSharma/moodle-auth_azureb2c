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

/**
 * Update plugin.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_azureb2c_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2014111703) {
        // Lengthen field.
        $table = new xmldb_table('auth_azureb2c_token');
        $field = new xmldb_field('scope', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'username');
        $dbman->change_field_type($table, $field);

        upgrade_plugin_savepoint($result, '2014111703', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2015012702) {
        $table = new xmldb_table('auth_azureb2c_state');
        $field = new xmldb_field('additionaldata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint($result, '2015012702', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2015012703) {
        $table = new xmldb_table('auth_azureb2c_token');
        $field = new xmldb_field('azureb2cusername', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'username');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint($result, '2015012703', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2015012704) {
        // Update azureb2c users.
        $sql = 'SELECT u.id as userid,
                       u.username as username,
                       tok.id as tokenid,
                       tok.azureb2cuniqid as azureb2cuniqid,
                       tok.idtoken as idtoken,
                       tok.azureb2cusername as azureb2cusername
                  FROM {auth_azureb2c_token} tok
                  JOIN {user} u ON u.username = tok.username
                 WHERE u.auth = ? AND deleted = ?';
        $params = ['azureb2c', 0];
        $userstoupdate = $DB->get_recordset_sql($sql, $params);
        foreach ($userstoupdate as $user) {
            if (empty($user->idtoken)) {
                continue;
            }

            try {
                // Decode idtoken and determine azureb2c username.
                $idtoken = \auth_azureb2c\jwt::instance_from_encoded($user->idtoken);
                $azureb2cusername = $idtoken->claim('upn');
                if (empty($azureb2cusername)) {
                    $azureb2cusername = $idtoken->claim('sub');
                }

                // Populate token azureb2cusername.
                if (empty($user->azureb2cusername)) {
                    $updatedtoken = new \stdClass;
                    $updatedtoken->id = $user->tokenid;
                    $updatedtoken->azureb2cusername = $azureb2cusername;
                    $DB->update_record('auth_azureb2c_token', $updatedtoken);
                }

                // Update user username (if applicable), so user can use rocreds loginflow.
                if ($user->username == strtolower($user->azureb2cuniqid)) {
                    // Old username, update to upn/sub.
                    if ($azureb2cusername != $user->username) {
                        // Update username.
                        $updateduser = new \stdClass;
                        $updateduser->id = $user->userid;
                        $updateduser->username = $azureb2cusername;
                        $DB->update_record('user', $updateduser);

                        $updatedtoken = new \stdClass;
                        $updatedtoken->id = $user->tokenid;
                        $updatedtoken->username = $azureb2cusername;
                        $DB->update_record('auth_azureb2c_token', $updatedtoken);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        upgrade_plugin_savepoint($result, '2015012704', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2015012707) {
        if (!$dbman->table_exists('auth_azureb2c_prevlogin')) {
            $dbman->install_one_table_from_xmldb_file(__DIR__.'/install.xml', 'auth_azureb2c_prevlogin');
        }
        upgrade_plugin_savepoint($result, '2015012707', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2015012710) {
        // Lengthen field.
        $table = new xmldb_table('auth_azureb2c_token');
        $field = new xmldb_field('scope', XMLDB_TYPE_TEXT, null, null, null, null, null, 'azureb2cusername');
        $dbman->change_field_type($table, $field);
        upgrade_plugin_savepoint($result, '2015012710', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2015111904.01) {
        // Ensure the username field in auth_azureb2c_token is lowercase.
        $authtokensrs = $DB->get_recordset('auth_azureb2c_token');
        foreach ($authtokensrs as $authtokenrec) {
            $newusername = trim(\core_text::strtolower($authtokenrec->username));
            if ($newusername !== $authtokenrec->username) {
                $updatedrec = new \stdClass;
                $updatedrec->id = $authtokenrec->id;
                $updatedrec->username = $newusername;
                $DB->update_record('auth_azureb2c_token', $updatedrec);
            }
        }
        upgrade_plugin_savepoint($result, '2015111904.01', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2015111905.01) {
        // Update old endpoints.
        $config = get_config('auth_azureb2c');
        if ($config->authendpoint === 'https://login.windows.net/common/oauth2/authorize') {
            set_config('authendpoint', 'https://login.microsoftonline.com/common/oauth2/authorize', 'auth_azureb2c');
        }

        if ($config->tokenendpoint === 'https://login.windows.net/common/oauth2/token') {
            set_config('tokenendpoint', 'https://login.microsoftonline.com/common/oauth2/token', 'auth_azureb2c');
        }

        upgrade_plugin_savepoint($result, '2015111905.01', 'auth', 'azureb2c');
    }

    if ($result && $oldversion < 2018051700.01) {
        $table = new xmldb_table('auth_azureb2c_token');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'username');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $sql = 'SELECT tok.id, tok.username, u.username, u.id as userid
                      FROM {auth_azureb2c_token} tok
                      JOIN {user} u ON u.username = tok.username';
            $records = $DB->get_recordset_sql($sql);
            foreach ($records as $record) {
                $newrec = new \stdClass;
                $newrec->id = $record->id;
                $newrec->userid = $record->userid;
                $DB->update_record('auth_azureb2c_token', $newrec);
            }
        }
        upgrade_plugin_savepoint($result, '2018051700.01', 'auth', 'azureb2c');
    }
    return $result;
}
