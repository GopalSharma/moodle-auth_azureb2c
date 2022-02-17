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

namespace auth_azureb2c\loginflow;

class base {
    /** @var object Plugin config. */
    public $config;

    /** @var \auth_azureb2c\httpclientinterface An HTTP client to use. */
    protected $httpclient;

    public function __construct() {
        $default = [
            'opname' => get_string('pluginname', 'auth_azureb2c')
        ];
        $storedconfig = (array)get_config('auth_azureb2c');
        $forcedconfig = [
            'field_updatelocal_idnumber' => 'oncreate',
            'field_lock_idnumber' => 'locked',
            'field_updatelocal_lang' => 'oncreate',
            'field_lock_lang' => 'locked',
            'field_updatelocal_firstname' => 'onlogin',
            'field_lock_firstname' => 'unlocked',
            'field_updatelocal_lastname' => 'onlogin',
            'field_lock_lastname' => 'unlocked',
            'field_updatelocal_email' => 'onlogin',
            'field_lock_email' => 'unlocked',
        ];

        $this->config = (object)array_merge($default, $storedconfig, $forcedconfig);
    }

    /**
     * Returns a list of potential IdPs that this authentication plugin supports. Used to provide links on the login page.
     *
     * @param string $wantsurl The relative url fragment the user wants to get to.
     * @return array Array of idps.
     */
    public function loginpage_idp_list($wantsurl) {
        return [];
    }

    /**
     * This is the primary method that is used by the authenticate_user_login() function in moodlelib.php.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password = null) {
        return false;
    }

    /**
     * Provides a hook into the login page.
     *
     * @param object &$frm Form object.
     * @param object &$user User object.
     */
    public function loginpage_hook(&$frm, &$user) {
        return true;
    }

    /**
     * Read user information from external database and returns it as array().
     *
     * @param string $username username
     * @return mixed array with no magic quotes or false on error
     */
    public function get_userinfo($username) {
        global $DB;

        $tokenrec = $DB->get_record('auth_azureb2c_token', ['username' => $username]);
        if (empty($tokenrec)) {
            return false;
        }

        $idtoken = \auth_azureb2c\jwt::instance_from_encoded($tokenrec->idtoken);

        // B2C provides custom field mapping, skip azureb2c mapping if B2C is present.
        $o365installed = $DB->get_record('config_plugins', ['plugin' => 'local_o365', 'name' => 'version']);
        if (!empty($o365installed)) {
            return [];
        }

        $userinfo = ['idnumber' => $username];

        $firstname = $idtoken->claim('given_name');
        if (!empty($firstname)) {
            $userinfo['firstname'] = $firstname;
        }

        $lastname = $idtoken->claim('family_name');
        if (!empty($lastname)) {
            $userinfo['lastname'] = $lastname;
        }

        $email = $idtoken->claim('emails');
        if (!empty($email)) {
             $userinfo['email'] = $email[0];
             //$userinfo['email'] = $email;
        }

        if (empty($userinfo['email'])) {
            $aademail = $idtoken->claim('upn');
            if (!empty($aademail)) {
                $aademailvalidateresult = filter_var($aademail, FILTER_VALIDATE_EMAIL);
                if (!empty($aademailvalidateresult)) {
                    $userinfo['email'] = $aademail;
                }
            }
        }
        
        $country = $idtoken->claim('country');
        if (!empty($country)) {
            $countries = get_string_manager()->get_list_of_countries();
            foreach ($countries as  $countrykey => $countryvalue) {
                $countryb2c = $country;
                $countrymoodle = $countryvalue;
                if($countrymoodle == $countryb2c)
                    $countryval = $countrykey;
            }
    }

        $gender = $idtoken->claim('extension_WP_Gender');
        $userinfo['lastnamephonetic'] = $gender;

        $lang = $idtoken->claim('language');
        if (!empty($lang)) {
            $userinfo['lang'] = $lang;
        } else {
            $userinfo['lang'] = 'en';
        }

        return $userinfo;
    }

    /**
     * Set an HTTP client to use.
     *
     * @param auth_azureb2chttpclientinterface $httpclient [description]
     */
    public function set_httpclient(\auth_azureb2c\httpclientinterface $httpclient) {
        $this->httpclient = $httpclient;
    }

    /**
     * Handle azureb2c disconnection from Moodle account.
     *
     * @param bool $justremovetokens If true, just remove the stored azureb2c tokens for the user, otherwise revert login methods.
     * @param bool $donotremovetokens If true, do not remove tokens when disconnecting. This migrates from a login account to a
     *                                "linked" account.
     * @param \moodle_url $redirect Where to redirect if successful.
     * @param \moodle_url $selfurl The page this is accessed from. Used for some redirects.
     */
    public function disconnect($justremovetokens = false, $donotremovetokens = false, \moodle_url $redirect = null,
                               \moodle_url $selfurl = null, $userid = null) {
        global $USER, $DB, $CFG;
        if ($redirect === null) {
            $redirect = new \moodle_url('/auth/azureb2c/ucp.php');
        }
        if ($selfurl === null) {
            $selfurl = new \moodle_url('/auth/azureb2c/ucp.php', ['action' => 'disconnectlogin']);
        }

        // Get the record of the user involved. Current user if no ID received.
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $userrec = $DB->get_record('user', ['id' => $userid]);
        if (empty($userrec)) {
            redirect($redirect);
            die();
        }

        if ($justremovetokens === true) {
            // Delete token data.
            $DB->delete_records('auth_azureb2c_token', ['userid' => $userrec->id]);
            $eventdata = ['objectid' => $userrec->id, 'userid' => $userrec->id];
            $event = \auth_azureb2c\event\user_disconnected::create($eventdata);
            $event->trigger();
            redirect($redirect);
        } else {
            global $OUTPUT, $PAGE;
            require_once($CFG->dirroot.'/user/lib.php');
            $PAGE->set_url($selfurl->out());
            $PAGE->set_context(\context_system::instance());
            $PAGE->set_pagelayout('standard');
            $USER->editing = false;

            $ucptitle = get_string('ucp_disconnect_title', 'auth_azureb2c', $this->config->opname);
            $PAGE->navbar->add($ucptitle, $PAGE->url);
            $PAGE->set_title($ucptitle);

            // Check if we have recorded the user's previous login method.
            $prevmethodrec = $DB->get_record('auth_azureb2c_prevlogin', ['userid' => $userrec->id]);
            $prevauthmethod = (!empty($prevmethodrec) && is_enabled_auth($prevmethodrec->method) === true) ? $prevmethodrec->method : null;
            // Manual is always available, we don't need it twice.
            if ($prevauthmethod === 'manual') {
                $prevauthmethod = null;
            }

            // We need either the user's previous method or the manual login plugin to be enabled for disconnection.
            if (empty($prevauthmethod) && is_enabled_auth('manual') !== true) {
                throw new \moodle_exception('errornodisconnectionauthmethod', 'auth_azureb2c');
            }

            // Check to see if the user has a username created by azureb2c, or a self-created username.
            // azureb2c-created usernames are usually very verbose, so we'll allow them to choose a sensible one.
            // Otherwise, keep their existing username.
            $azureb2ctoken = $DB->get_record('auth_azureb2c_token', ['userid' => $userrec->id]);
            $ccun = (isset($azureb2ctoken->azureb2cuniqid) && strtolower($azureb2ctoken->azureb2cuniqid) === $userrec->username) ? true : false;
            $customdata = [
                'canchooseusername' => $ccun,
                'prevmethod' => $prevauthmethod,
                'donotremovetokens' => $donotremovetokens,
                'redirect' => $redirect,
                'userid' => $userrec->id,
            ];

            $mform = new \auth_azureb2c\form\disconnect($selfurl, $customdata);

            if ($mform->is_cancelled()) {
                redirect($redirect);
            } else if ($fromform = $mform->get_data()) {

                $origusername = $userrec->username;

                if (empty($fromform->newmethod) || ($fromform->newmethod !== $prevauthmethod && $fromform->newmethod !== 'manual')) {
                    throw new \moodle_exception('errorauthdisconnectinvalidmethod', 'auth_azureb2c');
                }

                $updateduser = new \stdClass;

                if ($fromform->newmethod === 'manual') {
                    if (empty($fromform->password)) {
                        throw new \moodle_exception('errorauthdisconnectemptypassword', 'auth_azureb2c');
                    }
                    if ($customdata['canchooseusername'] === true) {
                        if (empty($fromform->username)) {
                            throw new \moodle_exception('errorauthdisconnectemptyusername', 'auth_azureb2c');
                        }

                        if (strtolower($fromform->username) !== $userrec->username) {
                            $newusername = strtolower($fromform->username);
                            $usercheck = ['username' => $newusername, 'mnethostid' => $CFG->mnet_localhost_id];
                            if ($DB->record_exists('user', $usercheck) === false) {
                                $updateduser->username = $newusername;
                            } else {
                                throw new \moodle_exception('errorauthdisconnectusernameexists', 'auth_azureb2c');
                            }
                        }
                    }
                    $updateduser->auth = 'manual';
                    $updateduser->password = $fromform->password;
                } else if ($fromform->newmethod === $prevauthmethod) {
                    $updateduser->auth = $prevauthmethod;
                    // We can't use user_update_user as it will rehash the value.
                    if (!empty($prevmethodrec->password)) {
                        $manualuserupdate = new \stdClass;
                        $manualuserupdate->id = $userrec->id;
                        $manualuserupdate->password = $prevmethodrec->password;
                        $DB->update_record('user', $manualuserupdate);
                    }
                }

                // Update user.
                $updateduser->id = $userrec->id;
                try {
                    user_update_user($updateduser);
                } catch (\Exception $e) {
                    throw new \moodle_exception($e->errorcode, '', $selfurl);
                }

                // Delete token data.
                if (empty($fromform->donotremovetokens)) {
                    $DB->delete_records('auth_azureb2c_token', ['userid' => $userrec->id]);

                    $eventdata = ['objectid' => $userrec->id, 'userid' => $userrec->id];
                    $event = \auth_azureb2c\event\user_disconnected::create($eventdata);
                    $event->trigger();
                }

                // If we're dealing with the current user, refresh the object.
                if ($userrec->id == $USER->id) {
                    $USER = $DB->get_record('user', ['id' => $USER->id]);
                }

                if (!empty($fromform->redirect)) {
                    redirect($fromform->redirect);
                } else {
                    redirect($redirect);
                }
            }

            echo $OUTPUT->header();
            $mform->display();
            echo $OUTPUT->footer();
        }
    }

    /**
     * Handle requests to the redirect URL.
     *
     * @return mixed Determined by loginflow.
     */
    public function handleredirect() {

    }

    /**
     * Construct the Azure AD B2C Connect client.
     *
     * @return \auth_azureb2c\azureb2cclient The constructed client.
     */
    protected function get_azureb2cclient() {
        global $CFG;
        if (empty($this->httpclient) || !($this->httpclient instanceof \auth_azureb2c\httpclientinterface)) {
            $this->httpclient = new \auth_azureb2c\httpclient();
        }
        if (empty($this->config->clientid) || empty($this->config->clientsecret)) {
            throw new \moodle_exception('errorauthnocreds', 'auth_azureb2c');
        }
        if (empty($this->config->authendpoint) || empty($this->config->tokenendpoint)) {
            throw new \moodle_exception('errorauthnoendpoints', 'auth_azureb2c');
        }

        $clientid = (isset($this->config->clientid)) ? $this->config->clientid : null;
        $clientsecret = (isset($this->config->clientsecret)) ? $this->config->clientsecret : null;
        $redirecturi = (!empty($CFG->loginhttps)) ? str_replace('http://', 'https://', $CFG->wwwroot) : $CFG->wwwroot;
        $redirecturi .= '/auth/azureb2c/';
        $resource = (isset($this->config->azureb2cresource)) ? $this->config->azureb2cresource : null;

        $client = new \auth_azureb2c\azureb2cclient($this->httpclient);
        $client->setcreds($clientid, $clientsecret, $redirecturi, $resource);

        $client->setendpoints(['auth' => $this->config->authendpoint, 'token' => $this->config->tokenendpoint]);
        return $client;
    }

    /**
     * Process an idtoken, extract uniqid and construct jwt object.
     *
     * @param string $idtoken Encoded id token.
     * @param string $orignonce Original nonce to validate received nonce against.
     * @return array List of azureb2cuniqid and constructed idtoken jwt.
     */
    protected function process_idtoken($idtoken, $orignonce = '') {
        // Decode and verify idtoken.
        $idtoken = \auth_azureb2c\jwt::instance_from_encoded($idtoken);
        $sub = $idtoken->claim('sub');
        if (empty($sub)) {
            \auth_azureb2c\utils::debug('Invalid idtoken', 'base::process_idtoken', $idtoken);
            throw new \moodle_exception('errorauthinvalididtoken', 'auth_azureb2c');
        }
        $receivednonce = $idtoken->claim('nonce');
        if (!empty($orignonce) && (empty($receivednonce) || $receivednonce !== $orignonce)) {
            \auth_azureb2c\utils::debug('Invalid nonce', 'base::process_idtoken', $idtoken);
            throw new \moodle_exception('errorauthinvalididtoken', 'auth_azureb2c');
        }

        // Use 'oid' if available (Azure-specific), or fall back to standard "sub" claim.
        $azureb2cuniqid = $idtoken->claim('oid');
        if (empty($azureb2cuniqid)) {
            $azureb2cuniqid = $idtoken->claim('sub');
        }
        return [$azureb2cuniqid, $idtoken];
    }

    /**
     * Check user restrictions, if present.
     *
     * This check will return false if there are restrictions in place that the user did not meet, otherwise it will return
     * true. If there are no restrictions in place, this will return true.
     *
     * @param \auth_azureb2c\jwt $idtoken The ID token of the user who is trying to log in.
     * @return bool Whether the restriction check passed.
     */
    protected function checkrestrictions(\auth_azureb2c\jwt $idtoken) {
        $restrictions = (isset($this->config->userrestrictions)) ? trim($this->config->userrestrictions) : '';
        $hasrestrictions = false;
        $userpassed = false;
        if ($restrictions !== '') {
            $restrictions = explode("\n", $restrictions);
            // Match "UPN" (Azure-specific) if available, otherwise match azureb2c-standard "sub".
            $tomatch = $idtoken->claim('upn');
            if (empty($tomatch)) {
                $tomatch = $idtoken->claim('sub');
            }
            foreach ($restrictions as $restriction) {
                $restriction = trim($restriction);
                if ($restriction !== '') {
                    $hasrestrictions = true;
                    ob_start();
                    try {
                        $count = @preg_match('/'.$restriction.'/', $tomatch, $matches);
                        if (!empty($count)) {
                            $userpassed = true;
                            break;
                        }
                    } catch (\Exception $e) {
                        $debugdata = [
                            'exception' => $e,
                            'restriction' => $restriction,
                            'tomatch' => $tomatch,
                        ];
                        \auth_azureb2c\utils::debug('Error running user restrictions.', 'handleauthresponse', $debugdata);
                    }
                    $contents = ob_get_contents();
                    ob_end_clean();
                    if (!empty($contents)) {
                        $debugdata = [
                            'contents' => $contents,
                            'restriction' => $restriction,
                            'tomatch' => $tomatch,
                        ];
                        \auth_azureb2c\utils::debug('Output while running user restrictions.', 'handleauthresponse', $debugdata);
                    }
                }
            }
        }
        return ($hasrestrictions === true && $userpassed !== true) ? false : true;
    }


    /**
     * Create a token for a user, thus linking a Moodle user to an Azure AD B2C Connect user.
     *
     * @param string $azureb2cuniqid A unique identifier for the user.
     * @param array $username The username of the Moodle user to link to.
     * @param array $authparams Parameters receieved from the auth request.
     * @param array $tokenparams Parameters received from the token request.
     * @param \auth_azureb2c\jwt $idtoken A JWT object representing the received id_token.
     * @return \stdClass The created token database record.
     */
    protected function createtoken($azureb2cuniqid, $username, $authparams, $tokenparams, \auth_azureb2c\jwt $idtoken, $userid = 0) {
        global $DB;

        // Determine remote username. Use 'upn' if available (Azure-specific), or fall back to standard 'sub'.
        $azureb2cusername = $idtoken->claim('upn');
        if (empty($azureb2cusername)) {
            $azureb2cusername = $idtoken->claim('sub');
        }

        // We should not fail here (idtoken was verified earlier to at least contain 'sub', but just in case...).
        if (empty($azureb2cusername)) {
            throw new \moodle_exception('errorauthinvalididtoken', 'auth_azureb2c');
        }

        $tokenrec = new \stdClass;
        $tokenrec->azureb2cuniqid = $azureb2cuniqid;
        $tokenrec->username = $username;
        $tokenrec->userid = $userid;
        $tokenrec->azureb2cusername = $azureb2cusername;
        $tokenrec->scope = !empty($tokenparams['scope']) ? $tokenparams['scope'] : 'openid profile email';
        $tokenrec->resource = !empty($tokenparams['resource']) ? $tokenparams['resource'] : $this->config->azureb2cresource;
        $tokenrec->authcode = $authparams['code'];
        $tokenrec->token = null;
        if (isset($tokenparams['access_token'])) {
            $tokenrec->token = $tokenparams['access_token'];
        } else if (isset($tokenparams['id_token'])) {
            $tokenrec->token = $tokenparams['id_token'];
        }
        if (!empty($tokenparams['expires_on'])) {
            $tokenrec->expiry = $tokenparams['expires_on'];
        } else if (isset($tokenparams['expires_in'])) {
            $tokenrec->expiry = time() + $tokenparams['expires_in'];
        } else {
            $tokenrec->expiry = time() + DAYSECS;
        }
        $tokenrec->refreshtoken = !empty($tokenparams['refresh_token']) ? $tokenparams['refresh_token'] : ''; // TBD?
        $tokenrec->idtoken = $tokenparams['id_token'];
        $tokenrec->id = $DB->insert_record('auth_azureb2c_token', $tokenrec);
        return $tokenrec;
    }

    /**
     * Update a token with a new auth code and access token data.
     *
     * @param int $tokenid The database record ID of the token to update.
     * @param array $authparams Parameters receieved from the auth request.
     * @param array $tokenparams Parameters received from the token request.
     */
    protected function updatetoken($tokenid, $authparams, $tokenparams) {
        global $DB;
        $tokenrec = new \stdClass;
        $tokenrec->id = $tokenid;
        $tokenrec->authcode = $authparams['code'];
        $tokenrec->token = null;
        if (isset($tokenparams['access_token'])) {
            $tokenrec->token = $tokenparams['access_token'];
        } else if (isset($tokenparams['id_token'])) {
            $tokenrec->token = $tokenparams['id_token'];
        }
        if (!empty($tokenparams['expires_on'])) {
            $tokenrec->expiry = $tokenparams['expires_on'];
        } else if (isset($tokenparams['expires_in'])) {
            $tokenrec->expiry = time() + $tokenparams['expires_in'];
        } else {
            $tokenrec->expiry = time() + DAYSECS;
        }
        $tokenrec->refreshtoken = !empty($tokenparams['refresh_token']) ? $tokenparams['refresh_token'] : ''; // TBD?
        $tokenrec->idtoken = $tokenparams['id_token'];
        $DB->update_record('auth_azureb2c_token', $tokenrec);
    }
}
