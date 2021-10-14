## Azure AD B2C Connect Authentication Plugin.

The Azure AD B2C Connect plugin provides registering of a new user and single-sign-on functionality using configurable identity providers, scope, and custom policies of Azure B2C.

This repository is updated with stable releases and matched with OIDC plugin. To follow active development, see: https://github.com/GopalSharma/moodle-auth_azureb2c

## Installation

1. Unpack the plugin into /auth/azureb2c within your Moodle install.
2. From the Moodle Administration block, expand Site Administration and click "Notifications".
3. Follow the on-screen instructions to install the plugin.
4. To configure the plugin, from the Moodle Administration block, go to Site Administration > Plugins > Authentication > Manage Authentication.
5. Click the icon to enable the plugin, then visit the settings page to configure the plugin. Follow the directions below for each setting.

For more documentation, visit https://docs.moodle.org/37en/azureb2c

For more information including support and instructions on how to contribute, please see: https://github.com/GopalSharma/moodle-auth_azureb2c/README.md

## Edit Profile
For edit/update profile(Azure AD B2C prfile) to work, one needs to the following  
Add the follwing code to theme's `profile.php` and if you are not using the customise page for profile, then add it to the `layout` page which profile page will use.
```sh
<head>
	<script>
		document.cookie="id_token="+window.location.hash;
    	</script>
</head>
<?php
	$id_token = "";
	if(!empty($_COOKIE['id_token'])) {
		$id_token = $_COOKIE['id_token'];
		$idtoken = explode("=",$id_token);
	}

	$editcheck = get_user_preferences('auth_azureb2c_edit');
	if($editcheck == 0 || $editcheck == 2 ) {
		if($editcheck == 0) {
			set_user_preference('auth_azureb2c_edit', 2);
			header("Refresh:0");
		}
		if($editcheck == 2) {
			$userid = optional_param('id', null, PARAM_INT);
			if(!empty($idtoken[1]) && ($idtoken[0] =="#id_token")) {
				updateuserazureb2c($userid, $idtoken[1]);
				$_COOKIE['id_token'] = null;
				set_user_preference('auth_azureb2c_edit', 1);
			}
		}
	}
?>

<?php
	$url = get_config('auth_azureb2c', 'editprofileendpoint')."&client_id=". get_config('auth_azureb2c', 'clientid')."&
	nonce=defaultNonce&redirect_uri=". $CFG->wwwroot."/auth/azureb2c/&scope=openid&response_type=id_token"; 
?>
<a href="<?php echo $url;?>"><?php echo get_string('editmyprofile'); ?></a>
```

Add the folloing function to theme's `lib.php`
```sh
function updateuserazureb2c($userid, $id_token) {
    global $CFG, $OUTPUT, $USER, $PAGE, $DB;
    require_once("{$CFG->dirroot}/auth/azureb2c/classes/loginflow/base.php");
    $idtoken = \auth_azureb2c\jwt::instance_from_encoded($id_token);
    $username = $idtoken->claim('oid');
    if (!empty($username)) {         
            $firstname = $idtoken->claim('given_name');
            $lastname = $idtoken->claim('family_name');
            $country = $idtoken->claim('country');
            $countryval = "";
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
        
        $userupdate = new \stdClass;
        $userupdate->id = $userid;
        if (!empty($firstname))
            $userupdate->firstname = $firstname;
        if (!empty($lastname))
            $userupdate->lastname = $lastname;
        if (!empty($countryval))
            $userupdate->country = $countryval;
        $DB->update_record('user', $userupdate);
        
        $USER->firstname = $firstname;
        $USER->lastname = $lastname;
        $USER->country = $countryval;
        return true;
    }
}
```
## Issues
Please post issues for this plugin to: https://github.com/GopalSharma/moodle-auth_azureb2c/issues
