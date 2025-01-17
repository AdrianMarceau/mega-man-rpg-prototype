<?

// Deprecate this old page (remove it completed later)
echo('Legacy "file" frame is no longer used!');
die();

// Require the application top file
require_once('../top.php');

// Collect the current request type if set
$this_action = !empty($_REQUEST['action']) ? strtolower($_REQUEST['action']) : false;
$allow_fadein = true;
// Define the allowable actions in this script
$allowed_actions = array('save', 'new', 'load', 'unload', 'reset');
// If this action is not allowed, kill the script
if (empty($this_action)){ die('An action must be defined!'); }
elseif (!in_array($this_action, $allowed_actions)){ die(ucfirst($this_action).' is not an allowed action!'); }
else { $allow_fadein = false; }

// Define the variables to hold HTML markup
$html_header_title = '';
$html_header_text = '';
$html_form_fields = '';
$html_form_buttons = '';
$html_form_messages = '';
$html_form_verified = true;

// Create the has updated flag and default to false
$file_has_updated = false;
$session_token = mmrpg_game_token();

// If the SAVE action was requested
while ($this_action == 'save'){

    // -- GENERATE AVATAR OPTIONS -- //
    if (true){

        // Collect the database types so we can use 'em later
        if (!isset($mmrpg_index_types)){ $mmrpg_index_types = rpg_type::get_index(); }

        // Collect the database robots so we can use 'em later
        $mmrpg_database_robots = rpg_robot::get_index();

        // Define an array to hold all the allowed avatar options
        $allowed_avatar_options = array();

        // Define an array to hold all the HTML avatar options
        $html_avatar_options = array();
        $html_avatar_options[] = '<option value="">- Select Robot -</option>';

        // Add all the robot avatars to the list
        $last_group_token = false;
        foreach ($mmrpg_database_robots AS $token => $info){

            if ($token == 'robot' || strstr($token, 'copy')){ continue; }
            elseif (isset($info['robot_image']) && $info['robot_image'] == 'robot'){ continue; }
            elseif (isset($info['robot_class']) && $info['robot_class'] == 'mecha'){ continue; }
            elseif (preg_match('/^(DLM)/i', $info['robot_number'])){ continue; }
            elseif (!rpg_game::sprite_exists(MMRPG_CONFIG_ROOTDIR.'images/robots/'.$token.'/')){ continue; }
            if (!mmrpg_prototype_robot_unlocked(false, $token) && $this_userinfo['role_id'] != 1){ continue; }

            // If the game has changed print the new optgroup
            $robot_game_token = $info['robot_game'];
            if (preg_match('/^(mega-man|proto-man|bass|roll|disco|rhythm)$/i', $token)){ $robot_game_token = 'HEROES'; }
            if ($robot_game_token != $last_group_token){
                if (!empty($last_group_token)){ $html_avatar_options[] = '</optgroup>'; }
                $last_group_token = $robot_game_token;
                if ($robot_game_token === 'HEROES'){ $last_group_name = 'Mega Man Heroes'; }
                else { $last_group_name = rpg_game::get_source_name($last_group_token, false).' '.ucfirst(rpg_robot::robot_class_to_noun($info['robot_class'], false, true)); }
                $html_avatar_options[] = '<optgroup label="'.$last_group_name.'">';
            }

            $size = isset($info['robot_image_size']) ? $info['robot_image_size'] : 40;
            $html_avatar_options[] = '<option value="robots/'.$token.'/'.$size.'">'.$info['robot_number'].' : '.$info['robot_name'].'</option>';
            $allowed_avatar_options[] = 'robots/'.$token.'/'.$size;

            // Collect the summon count for this robot and unlocked alts
            $temp_summon_count = mmrpg_prototype_database_summoned($token);
            $temp_alts_unlocked = mmrpg_prototype_altimage_unlocked($token);

            // If this is a copy core, add it's type alts
            if (isset($info['robot_core']) && $info['robot_core'] == 'copy'){
                foreach ($mmrpg_index_types AS $type_token => $type_info){
                    if ($type_token == 'none' || $type_token == 'copy' || (isset($type_info['type_class']) && $type_info['type_class'] == 'special')){ continue; }
                    if (!isset($_SESSION['GAME']['values']['battle_items'][$type_token.'-core']) && $this_userinfo['role_id'] != 1){ continue; }
                    $html_avatar_options[] = '<option value="robots/'.$token.'_'.$type_token.'/'.$size.'">'.$info['robot_number'].' : '.$info['robot_name'].' ('.$type_info['type_name'].' Core)</option>';
                    $allowed_avatar_options[] = 'robots/'.$token.'_'.$type_token.'/'.$size;
                }
            }
            // Otherwise, if this ROBOT MASTER alt skin has been inlocked
            elseif (!empty($info['robot_image_alts'])){
                // Loop through each of the available alts and print if unlocked
                foreach ($info['robot_image_alts'] AS $key => $this_altinfo){
                    // Define the unlocked flag as false to start
                    $alt_unlocked = false;
                    // If this alt is unlocked via summon and we have enough
                    if (!empty($this_altinfo['summons']) && $temp_summon_count >= $this_altinfo['summons']){ $alt_unlocked = true; }
                    // Else if this alt is unlocked via the shop and has been purchased
                    elseif (in_array($this_altinfo['token'], $temp_alts_unlocked)){ $alt_unlocked = true; }
                    // Print the alt option markup if unlocked
                    if ($alt_unlocked){
                        $html_avatar_options[] = '<option value="robots/'.$token.'_'.$this_altinfo['token'].'/'.$size.'">'.$info['robot_number'].' : '.$this_altinfo['name'].'</option>';
                        $allowed_avatar_options[] = 'robots/'.$token.'_'.$this_altinfo['token'].'/'.$size;
                    }
                }
            }

        }
        if (!empty($last_group_token)){ $html_avatar_options[] = '</optgroup>'; }

        // Add player avatars if this is the developer
        if ($this_userinfo['role_id'] == 1 || $this_userinfo['role_id'] == 6){
            $html_avatar_options[] = '</optgroup>';
            $html_avatar_options[] = '<optgroup label="Player Characters">';
            $html_avatar_options[] = '<option value="players/dr-light/40">PLAYER : Dr. Light</option>';
            $html_avatar_options[] = '<option value="players/dr-wily/40">PLAYER : Dr. Wily</option>';
            $html_avatar_options[] = '<option value="players/dr-cossack/40">PLAYER : Dr. Cossack</option>';
            $allowed_avatar_options[] = 'players/dr-light/40';
            $allowed_avatar_options[] = 'players/dr-wily/40';
            $allowed_avatar_options[] = 'players/dr-cossack/40';
        }

        // Add the optgroup closing tag
        $html_avatar_options[] = '</optgroup>';
        $temp_avatar_select_options = str_replace('value="'.$_SESSION['GAME']['USER']['imagepath'].'"', 'value="'.$_SESSION['GAME']['USER']['imagepath'].'" selected="selected"', implode('', $html_avatar_options));
    }

    // -- GENERATE COLOUR OPTIONS -- //
    if (true){

        // Collect the type index and generate colour option html
        $mmrpg_database_type = $mmrpg_index_types;
        sort($mmrpg_database_type);
        $allowed_colour_options = array();
        $html_colour_options = array();
        $html_colour_options[] = '<option value="">- Select Type -</option>';
        $html_colour_options[] = '<option value="none">Neutral Type</option>';
        // Add all the robot avatars to the list
        foreach ($mmrpg_database_type AS $token => $info){
            if ($token == 'none'){ continue; }
            $html_colour_options[] = '<option value="'.$info['type_token'].'">'.$info['type_name'].' Type</option>';
            $allowed_colour_options[] = $info['type_token'];
        }
        // Add player avatars if this is the developer
        if ($this_userinfo['role_id'] == 1){
            $html_colour_options[] = '<option value="energy">Energy Type</option>';
            $html_colour_options[] = '<option value="attack">Attack Type</option>';
            $html_colour_options[] = '<option value="defense">Defense Type</option>';
            $html_colour_options[] = '<option value="speed">Speed Type</option>';
            $allowed_colour_options[] = 'energy';
            $allowed_colour_options[] = 'attack';
            $allowed_colour_options[] = 'defense';
            $allowed_colour_options[] = 'speed';
        }
        $temp_colour_select_options = str_replace('value="'.$_SESSION['GAME']['USER']['colourtoken'].'"', 'value="'.$_SESSION['GAME']['USER']['colourtoken'].'" selected="selected"', implode('', $html_colour_options));

    }

    // If the form has already been submit, process input
    while (!empty($_POST['submit']) && $_POST['submit'] == 'true'){

        // Collect any profile details
        $user_displayname = !empty($_POST['displayname']) ? preg_replace('/[^-_a-z0-9\.\s]+/i', '', trim($_POST['displayname'])) : '';
        $user_emailaddress = !empty($_POST['emailaddress']) ? preg_replace('/[^-_a-z0-9\.\+@]+/i', '', trim($_POST['emailaddress'])) : '';
        $user_imagepath = !empty($_POST['imagepath']) && preg_match('/^[-_a-z0-9]+\/[-_a-z0-9]+\/[0-9]+$/i', $_POST['imagepath']) ? trim($_POST['imagepath']) : '';
        $user_colourtoken = !empty($_POST['colourtoken']) && preg_match('/^[-_a-z0-9]+$/i', $_POST['colourtoken']) ? trim($_POST['colourtoken']) : '';
        $user_omegaseed = !empty($_POST['omegaseed']) ? trim($_POST['omegaseed']) : '';

        if (!in_array($user_imagepath, $allowed_avatar_options)){ $user_imagepath = $allowed_avatar_options[0]; }
        if (!empty($user_colourtoken) && !in_array($user_colourtoken, $allowed_colour_options)){ $user_colourtoken = $allowed_colour_options[0]; }

        // Check if the password has changed at all
        if (true){

            // Backup the current game's filename for deletion purposes
            $backup_user = $_SESSION[$session_token]['USER'];

            // Update the current game's user and file info using the new password
            $_SESSION[$session_token]['USER']['displayname'] = $user_displayname;
            if (!empty($user_emailaddress)){ $_SESSION[$session_token]['USER']['emailaddress'] = $user_emailaddress; }
            if (!empty($user_imagepath)){ $_SESSION[$session_token]['USER']['imagepath'] = $user_imagepath; }
            if (!empty($user_colourtoken)){ $_SESSION[$session_token]['USER']['colourtoken'] = $user_colourtoken; }
            if (!empty($user_omegaseed)){
                $_SESSION[$session_token]['USER']['omega'] = md5(MMRPG_SETTINGS_OMEGA_SEED.$user_omegaseed);
            }

        }

        // If possible, attempt to save the game to the session
        if (rpg_game::is_user()){ mmrpg_prototype_refresh_battle_points(); }

        // Save the current game session into the file
        mmrpg_save_game_session();
        $db_users_fields = rpg_user::get_index_fields(true, 'users');
        $db_users_roles_fields = rpg_user_role::get_index_fields(true, 'roles');
        $this_userinfo = $db->get_array("SELECT
            {$db_users_fields},
            {$db_users_roles_fields}
            FROM mmrpg_users AS users
            LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id
            WHERE users.user_id = '{$this_userid}'
            LIMIT 1
            ;");
        $_SESSION['GAME']['USER']['userinfo'] = $this_userinfo;
        $_SESSION['GAME']['USER']['userinfo']['user_password_encoded'] = '';

        // Update the has updated flag variable
        $file_has_updated = true;

        // Break from the POST loop
        break;

    }

    // Update the header markup title
    $html_header_title .= 'Game Settings';
    // Update the header markup text
    $html_header_text .= 'Your game is saved automatically whenever you return to the main menu, shop, or customize your characters.<br /> ';
    $html_header_text .= 'Make changes to your game using the form below or <a href="'.MMRPG_CONFIG_ROOTURL.'file/profile/" target="_blank">click here</a> to update account settings. ';
    $html_header_text .= '';

    // Start the output buffer to collect form fields
    ob_start();
    if (!$file_has_updated){

        // Update the form markup fields

        // Username
        echo '<div class="field field_username">';
            echo '<label class="label label_username">Username :</label>';
            echo '<input class="text text_username" type="text" name="username" value="'.htmlentities(trim($_SESSION[$session_token]['USER']['username']), ENT_QUOTES, 'UTF-8', true).'" disabled="disabled" />';
        echo '</div>';

        // Display Name
        if (!empty($this_userinfo['user_flag_postpublic'])){
            echo '<div class="field field_displayname">';
                echo '<label class="label label_displayname">Display Name :</label>';
                echo '<input class="text text_displayname" type="text" name="displayname" maxlength="18" value="'.htmlentities(trim(!empty($_SESSION[$session_token]['USER']['displayname']) ? $_SESSION[$session_token]['USER']['displayname'] : ''), ENT_QUOTES, 'UTF-8', true).'" />';
            echo '</div>';
        } else {
            echo '<input type="hidden" name="displayname" maxlength="18" value="'.htmlentities(trim(!empty($_SESSION[$session_token]['USER']['displayname']) ? $_SESSION[$session_token]['USER']['displayname'] : ''), ENT_QUOTES, 'UTF-8', true).'" />';
        }

        echo '<div class="field field_colourtoken">';
            echo '<label class="label label_colourtoken">Profile Colour :</label>';
            echo '<select class="select select_colourtoken" name="colourtoken">'.$temp_colour_select_options.'</select>';
        echo '</div>';

        echo '<div class="field field_imagepath">';
            echo '<label class="label label_imagepath">Robot Avatar :</label>';
            echo '<select class="select select_imagepath" name="imagepath">'.$temp_avatar_select_options.'</select>';
        echo '</div>';

        // Add the omega sequence fields if applicable
        if (mmrpg_prototype_item_unlocked('omega-seed')){
            echo '<div class="field field_omega">';
                echo '<label class="label label_omega">Omega Sequence :</label>';
                echo '<input class="text text_omega" type="text" name="omega" value="'.htmlentities(trim($_SESSION[$session_token]['USER']['omega']), ENT_QUOTES, 'UTF-8', true).'" disabled="disabled" />';
            echo '</div>';
            echo '<div class="field field_omegaseed">';
                echo '<label class="label label_omegaseed">Regenerate Sequence :</label>';
                echo '<input class="text text_omegaseed" type="text" name="omegaseed" maxlength="32" value="" />';
            echo '</div>';
        }


    }
    $html_form_fields = ob_get_clean();

    // Start the output buffer to collect form buttons
    ob_start();
    if (!$file_has_updated){

        // Update the form markup buttons
        echo '<input class="button type type_nature button_submit" type="submit" name="save" value="Save Changes" />';
        echo '<input class="button type type_flame button_reset" type="button" name="reset" value="Reset Game" onclick="javascript:parent.window.mmrpg_trigger_reset();" />';

        /*
        echo '<div class="extra_options">';

            // Ensure the player is unlocked
            if (mmrpg_prototype_player_unlocked('dr-light')){
                echo '<div class="reset_wrapper wrapper_dr-light">';
                    echo '<div class="wrapper_header">Dr. Light'.(mmrpg_prototype_complete('dr-light') ? ' <span style="position: relative; bottom: 2px;" title="Thank you for playing!!! :D">&hearts;</span>' : '').'</div>';
                    if (mmrpg_prototype_battles_complete('dr-light') > 0){ echo '<input class="button button_reset button_reset_missions" type="button" value="Reset Missions" onclick="javascript:parent.window.mmrpg_trigger_reset_missions(\'dr-light\', \'Dr. Light\');" />'; }
                    else { echo '<input class="button button_reset button_reset_missions" type="button" value="Reset Missions" style="text-decoration: line-through;" />'; }
                echo '</div>';
            }

            // Ensure the player is unlocked
            if (mmrpg_prototype_player_unlocked('dr-wily')){
                echo '<div class="reset_wrapper wrapper_dr-wily">';
                    echo '<div class="wrapper_header">Dr. Wily'.(mmrpg_prototype_complete('dr-light') ? ' <span style="position: relative; bottom: 2px;" title="Thank you for playing!!! >:D">&clubs;</span>' : '').'</div>';
                    if (mmrpg_prototype_battles_complete('dr-wily') > 0){ echo '<input class="button button_reset button_reset_missions" type="button" value="Reset Missions" onclick="javascript:parent.window.mmrpg_trigger_reset_missions(\'dr-wily\', \'Dr. Wily\');" />'; }
                    else { echo '<input class="button button_reset button_reset_missions" type="button" value="Reset Missions" style="text-decoration: line-through;" />'; }
                echo '</div>';
            }

            // Ensure the player is unlocked
            if (mmrpg_prototype_player_unlocked('dr-cossack')){
                echo '<div class="reset_wrapper wrapper_dr-cossack">';
                    echo '<div class="wrapper_header">Dr. Cossack'.(mmrpg_prototype_complete('dr-light') ? ' <span style="position: relative; bottom: 2px;" title="Thank you for playing!!! >:D">&diams;</span>' : '').'</div>';
                    if (mmrpg_prototype_battles_complete('dr-cossack') > 0){ echo '<input class="button button_reset button_reset_missions" type="button" value="Reset Missions" onclick="javascript:parent.window.mmrpg_trigger_reset_missions(\'dr-cossack\', \'Dr. Cossack\');" />'; }
                    else { echo '<input class="button button_reset button_reset_missions" type="button" value="Reset Missions" style="text-decoration: line-through;" />'; }
                echo '</div>';
            }

        echo '</div>';
        */

        //echo '<input class="button button_cancel" type="button" value="Cancel" onclick="javascript:parent.window.location.href=\'prototype.php\';" />';

    }
    $html_form_buttons = ob_get_clean();


    // If the file has been updated, update the data
    if ($file_has_updated){

        // Update the form messages markup text
        $html_form_messages .= '<span class="success">(!) Thank you.  Your game has been saved.</span>';
        // Clear the form fields markup
        $html_form_fields = '<script type="text/javascript"> reloadTimeout = 0; reloadParent = true; </script>';
        // Update the form markup buttons
        $html_form_buttons = ''; //<input class="button button_continue" type="button" value="Continue" onclick="javascript:parent.window.location.href=\'prototype.php\';" />';

    }

    // Break from the SAVE loop
    break;
}
// Else, if the LOAD action was requested
while ($this_action == 'load'){

    // Define the coppa flag
    $html_form_show_coppa = false;

    // If the form has already been submit, process input
    while (!empty($_POST['submit']) && $_POST['submit'] == 'true'){

        // If both the username or password are empty, produce an error
        if (empty($_REQUEST['username']) && empty($_REQUEST['password'])){
            $html_form_messages .= '<span class="error">(!) The username and password were not provided.</span>';
            break;
        }
        // Otherwise, if at least one of them was provided, validate
        else {
            // Trim spaces off the end and beginning
            $_REQUEST['username'] = trim($_REQUEST['username']);
            $_REQUEST['password'] = trim($_REQUEST['password']);
            // Ensure the username is valid
            if (empty($_REQUEST['username'])){
                $html_form_messages .= '<span class="error">(!) The username was not provided.</span>';
                break;
            } elseif ($_REQUEST['username'] == 'demo'){
                $html_form_messages .= '<span class="error">(!) The provided username is not valid.</span>';
                break;
            } elseif (!preg_match('/^[-_a-z0-9\.]+$/i', $_REQUEST['username'])){
                $html_form_messages .= '<span class="error">(!) The provided username contains invalid characters.</span>';
                break;
            }
            // Ensure the password is valid
            if (empty($_REQUEST['password'])){
                $html_form_messages .= '<span class="error">(!) The password was not provided.</span>';
                break;
            }
        }

        // Collect the user details and generate the file ones as well
        $this_user = array();
        $this_user['username'] = trim($_REQUEST['username']);
        $this_user['username_clean'] = preg_replace('/[^-a-z0-9]+/i', '', strtolower($this_user['username']));
        $this_user['password'] = trim($_REQUEST['password']);
        $this_user['password_encoded'] = md5(MMRPG_SETTINGS_PASSWORD_SALT.$this_user['password']);

        // The file exists, so let's collect this user's info from teh database
        $db_user_fields = rpg_user::get_index_fields(true);
        $temp_database_user = $db->get_array("SELECT
            {$db_user_fields},
            user_password_encoded
            FROM mmrpg_users WHERE
            user_name_clean = '{$this_user['username_clean']}'
            ;");

        // Check if the requested save file path exists
        if (!empty($temp_database_user)){

            // And now let's let's check the password
            if ($this_user['password_encoded'] == $temp_database_user['user_password_encoded']){

                // Clear the password from these vars, we don't need it any more
                $this_user['password'] = '';
                $this_user['password_encoded'] = '';

                // The password was correct and the user has been approved for login
                if (!empty($temp_database_user['user_date_birth']) && !empty($temp_database_user['user_flag_approved'])){

                    // The password was correct! Update the session with these credentials
                    mmrpg_reset_game_session();
                    $_SESSION['GAME']['DEMO'] = 0;
                    $_SESSION['GAME']['USER'] = $this_user;
                    $_SESSION['GAME']['USER']['userid'] = $temp_database_user['user_id'];
                    $_SESSION['GAME']['PENDING_LOGIN_ID'] = $temp_database_user['user_id'];

                    // Load the save file into memory and overwrite the session
                    mmrpg_load_game_session();
                    if (empty($_SESSION['GAME']['counters']['battle_points'])){
                        mmrpg_reset_game_session();
                    } elseif (empty($_SESSION['GAME']['values']['battle_rewards'])){
                        mmrpg_reset_game_session();
                    }

                    // Update the form markup, then break from the loop
                    $file_has_updated = true;
                    break;

                }
                // The user has not confirmed their date of birth, produce an error
                else {

                    // Define the data of birth checking variables
                    $min_dateofbirth = date('Y-m-d', strtotime('13 years ago'));
                    $bypass_dateofbirth = false;

                    // Allow bypassing date-of-birth if pre-approved via email
                    $bypass_emails = strstr(MMRPG_CONFIG_COPPA_PERMISSIONS, ',') ? explode(',', MMRPG_CONFIG_COPPA_PERMISSIONS) : array(MMRPG_CONFIG_COPPA_PERMISSIONS);
                    if (in_array(strtolower($temp_database_user['user_email_address']), $bypass_emails)){ $bypass_dateofbirth = true; }
                    elseif (!empty($temp_database_user['user_flag_approved'])){ $bypass_dateofbirth = true; }

                    // Ensure the dateofbirth is valid
                    $_REQUEST['dateofbirth'] = !empty($_REQUEST['dateofbirth']) ? str_replace(array('/', '_', '.', ' '), '-', $_REQUEST['dateofbirth']) : '';
                    if (empty($_REQUEST['dateofbirth'])){
                        $html_form_messages .= '<span class="error">(!) Your date of birth must be confirmed in order to continue.</span>';
                        $html_form_verified = false;
                        $html_form_show_coppa = true;
                        break;
                    } elseif (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_REQUEST['dateofbirth'])){
                        $html_form_messages .= '<span class="error">(!) The date of birth provided was not valid.</span>';
                        $html_form_verified = false;
                        $html_form_show_coppa = true;
                        break;
                    } elseif ($_REQUEST['dateofbirth'] > $min_dateofbirth && !$bypass_dateofbirth){
                        $html_form_messages .= '<span class="error">(!) You must be at least 13 years of age to use this website or have <a href="images/misc/MMRPG-Prototype_COPPA-Compliance.pdf" target="_blank">a parent or guardian\'s permission</a>.</span>';
                        $html_form_verified = false;
                        $html_form_show_coppa = true;
                        break;
                    } elseif ($_REQUEST['dateofbirth'] > $min_dateofbirth && $bypass_dateofbirth){
                        $html_form_messages .= '<span class="success">(!) You are under 13 years of age but have obtained parental consent.</span>';
                        $html_form_verified = false;
                        $html_form_show_coppa = true;
                    }

                    // If the account is not verified, break now
                    if (!$html_form_verified){ break; }

                    // The password was correct! Update the session with these credentials
                    mmrpg_reset_game_session();
                    $_SESSION['GAME']['DEMO'] = 0;
                    $_SESSION['GAME']['USER'] = $this_user;
                    $_SESSION['GAME']['USER']['userid'] = $temp_database_user['user_id'];
                    $_SESSION['GAME']['USER']['dateofbirth'] = strtotime($_REQUEST['dateofbirth']);
                    $_SESSION['GAME']['USER']['approved'] = 1;
                    $_SESSION['GAME']['PENDING_LOGIN_ID'] = $temp_database_user['user_id'];

                    // Load the save file into memory and overwrite the session
                    mmrpg_load_game_session();
                    if (empty($_SESSION['GAME']['counters']['battle_points'])){
                        mmrpg_reset_game_session();
                    } elseif (empty($_SESSION['GAME']['values']['battle_rewards'])){
                        mmrpg_reset_game_session();
                    } else {
                        mmrpg_save_game_session();
                    }

                    // Update the form markup, then break from the loop
                    $file_has_updated = true;
                    break;

                }

            }
            // Otherwise, if the password was incorrect
            else {

                // Create an error message and break out of the form
                $html_form_messages .= '<span class="error">(!) The provided password was not correct.</span>';
                break;

            }

        }
        // Otherwise, if the file does not exist, print an error
        else {

            // Create an error message and break out of the form
            $html_form_messages .= '<span class="error">(!) The requested username ('.$this_user['username_clean'].') does not exist.</span>';
            break;

        }

        // Break from the POST loop
        break;

    }

    // Update the header markup title
    $html_header_title .= 'Load Existing Game File';

    // Update the header markup text
    $html_header_text .= 'Please enter the username and password of your save file below. ';
    $html_header_text .= 'Passwords are case-sensitive, though usernames are not.';
    if ($html_form_show_coppa){
        $html_header_text .= '<br /> Your date of birth must now be confirmed in accordance with <a href="http://www.coppa.org/" target="_blank">COPPA</a> guidelines.';
    }
    // Update the form markup fields
    $html_form_fields .= '<div class="field field_username">';
        $html_form_fields .= '<label class="label label_username">Username : </label>';
        $html_form_fields .= '<input class="text text_username" type="text" name="username" value="'.(!empty($_REQUEST['username']) ? htmlentities(trim($_REQUEST['username']), ENT_QUOTES, 'UTF-8', true) : '').'" maxlength="18" />';
    $html_form_fields .= '</div>';
    $html_form_fields .= '<div class="field field_password">';
        $html_form_fields .= '<label class="label label_password">Password :</label>';
        $html_form_fields .= '<input class="text text_password" type="password" name="password" value="'.(!empty($_REQUEST['password']) ? htmlentities(trim($_REQUEST['password']), ENT_QUOTES, 'UTF-8', true) : '').'" maxlength="18" />';
    $html_form_fields .= '</div>';
    if ($html_form_show_coppa){
        $html_form_fields .= '<div class="field field_dateofbirth" style="clear: both;">';
            $html_form_fields .= '<label class="label label_dateofbirth">Date of Birth : </label>';
            $html_form_fields .= '<input class="text text_dateofbirth" type="text" name="dateofbirth" value="'.(!empty($_REQUEST['dateofbirth']) ? htmlentities(trim($_REQUEST['dateofbirth']), ENT_QUOTES, 'UTF-8', true) : '').'" maxlength="10" />';
            $html_form_fields .= '<span style="padding-left: 20px; color: #969696; font-size: 10px; letter-spacing: 1px;  ">YYYY-MM-DD</span>';
        $html_form_fields .= '</div>';
    }
    // Update the form markup buttons
    $html_form_buttons .= '<input class="button type type_nature button_submit" type="submit" value="Load File" />';
    //$html_form_buttons .= '<input class="button type type_flame button_cancel" type="button" value="Cancel" onclick="javascript:parent.window.location.href=\'prototype.php\';" />';

    // If the file has been updated, update the data
    if ($file_has_updated && !empty($temp_database_user['user_id'])){

        // Update the session with the pending login ID
        $_SESSION['GAME']['PENDING_LOGIN_ID'] = $temp_database_user['user_id'];
        $_SESSION['GAME']['USER']['userid'] = $temp_database_user['user_id'];
        mmrpg_load_game_session();

        /*
        echo('<pre>$_POST = '.print_r($_POST, true).'</pre>');
        echo('<pre>$_SESSION[GAME][PENDING_LOGIN_ID] = '.print_r($_SESSION['GAME']['PENDING_LOGIN_ID'], true).'</pre>');
        echo('<pre>$_SESSION[GAME][USER] = '.print_r($_SESSION['GAME']['USER'], true).'</pre>');
        exit();
        */

        // Manually update this user's last login time for notifications
        $db->update('mmrpg_users', array(
            'user_last_login' => time(),
            'user_backup_login' => $temp_database_user['user_last_login'],
            ), "user_id = {$temp_database_user['user_id']}");

        // Redirect without wasting time to the home again
        header('Location: '.MMRPG_CONFIG_ROOTURL.'frames/file.php?action=load&reload=true');
        exit();

    }

    // Print out the success message if applicable
    if (!empty($_REQUEST['reload']) || ($file_has_updated && !empty($temp_database_user['user_id']))){

        // Update the form messages markup text
        $html_form_messages .= '<span class="success">(!) Thank you.  Your game has been loaded.</span>';
        // Clear the form fields markup
        $html_form_fields = '<script type="text/javascript"> reloadParent = false; </script>';
        // Update the form markup buttons
        $html_form_buttons = '<input class="button button_continue" type="button" value="Continue" onclick="javascript:window.location.href=\'prototype.php\';" />';

    }

    // Break from the LOAD loop
    break;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>File | Prototype | Mega Man RPG Prototype</title>
<base href="<?=MMRPG_CONFIG_ROOTURL?>" />
<meta name="robots" content="noindex,nofollow" />
<meta name="darkreader-lock" content="already-dark-mode" />
<meta name="format-detection" content="telephone=no" />
<link type="text/css" href=".libs/fontawesome/v5.6.3/css/solid.css" rel="stylesheet" />
<link type="text/css" href=".libs/fontawesome/v5.6.3/css/fontawesome.css" rel="stylesheet" />
<link type="text/css" href="styles/style.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/prototype.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/file.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<?if($flag_wap):?>
<link type="text/css" href="styles/style-mobile.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/prototype-mobile.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<?endif;?>
<script type="text/javascript" src=".libs/jquery/jquery-<?= MMRPG_CONFIG_JQUERY_VERSION ?>.min.js"></script>
<script type="text/javascript" src="scripts/script.js?<?=MMRPG_CONFIG_CACHE_DATE?>"></script>
<script type="text/javascript" src="scripts/prototype.js?<?=MMRPG_CONFIG_CACHE_DATE?>"></script>
<script type="text/javascript">
// Update game settings for this page
<? require_once(MMRPG_CONFIG_ROOTDIR.'scripts/gamesettings.js.php'); ?>
gameSettings.fadeIn = false;
// Create the reload parent flag for later
var reloadIndex = false;
var reloadParent = false;
var reloadTimeout = 1000;
var thisBody = false;
var thisPrototype = false;
var thisWindow = false;
// Generate the document ready events for this page
$(document).ready(function(){
    // Start playing the file menu music
    //top.mmrpg_music_load('misc/file-menu');

    // Update global reference variables
    thisBody = $('#mmrpg');
    thisPrototype = $('#prototype', thisBody);
    thisWindow = $(window);

    // If reload parent has been set to true
    if (reloadIndex == true){
        //alert('about to reload index...');
        var reloadTimeout = setTimeout(function(){
            //alert('reloading index!');
            top.window.location.href = 'prototype/';
            }, reloadTimeout);
        }

    // If reload parent has been set to true
    if (reloadParent == true && window.self != window.parent){
        //alert('about to reload parent...');
        var reloadTimeout = setTimeout(function(){
            //alert('reloading parent!');
            parent.window.location.href = 'prototype.php?wap='+(gameSettings.wapFlag ? 'true' : 'false');
            }, reloadTimeout);
        }

    /*
    // If reload parent/index has been set to true
    if (reloadIndex == true || reloadParent == true){
        alert('about to reload parent...');
        var reloadTimeout = setTimeout(function(){
            alert('reloading parent! '+parent.window.location);
            parent.window.location.href = parent.window.location;
            }, 1000);
        }
    */

    // Fade in the leaderboard screen slowly
    thisBody.waitForImages(function(){
        var tempTimeout = setTimeout(function(){
            <? if ($allow_fadein): ?>
            thisBody.css({opacity:0}).removeClass('hidden').animate({opacity:1.0}, 800, 'swing');
            <? else: ?>
            thisBody.css({opacity:1}).removeClass('hidden');
            <? endif; ?>
            // Let the parent window know the menu has loaded
            if (typeof parent.prototype_menu_loaded !== 'undefined'){
                parent.prototype_menu_loaded();
                }
            }, 1000);
        }, false, true);

    // Attach resize events to the window
    thisWindow.resize(function(){ windowResizeFrame(); });
    setTimeout(function(){ windowResizeFrame(); }, 1000);
    windowResizeFrame();

    var windowHeight = $(window).height();
    var htmlHeight = $('html').height();
    var htmlScroll = $('html').scrollTop();
    //alert('windowHeight = '+windowHeight+'; htmlHeight = '+htmlHeight+'; htmlScroll = '+htmlScroll+'; ');

});

// Create the windowResize event for this page
function windowResizeFrame(){

    var windowWidth = thisWindow.width();
    var windowHeight = thisWindow.height();
    var headerHeight = $('.header', thisBody).outerHeight(true);

    var newBodyHeight = windowHeight;
    var newFrameHeight = newBodyHeight - headerHeight;

    if (windowWidth > 800){ thisBody.addClass((gameSettings.wapFlag ? 'mobileFlag' : 'windowFlag')+'_landscapeMode'); }
    else { thisBody.removeClass((gameSettings.wapFlag ? 'mobileFlag' : 'windowFlag')+'_landscapeMode'); }

    thisBody.css({height:newBodyHeight+'px'});
    thisPrototype.css({height:newBodyHeight+'px'});

    //console.log('windowWidth = '+windowWidth+'; parentWidth = '+parentWidth+'; thisTypeContainerWidth = '+thisTypeContainerWidth+'; thisStarContainerWidth = '+thisStarContainerWidth+'; ');

}
</script>
</head>
<body id="mmrpg" class="iframe" data-frame="file">
    <div id="prototype" style="<?= $this_action == 'load' ? 'padding: 60px 0;' : '' ?>">

        <form class="menu" action="frames/file.php?action=<?= $this_action ?>" method="post" autocomplete="on">

            <? if(!empty($html_header_text)): ?>
                <span class="header block_1 header_types type_<?= defined('MMRPG_SETTINGS_REMOTE_FIELDTYPE') ? MMRPG_SETTINGS_REMOTE_FIELDTYPE : MMRPG_SETTINGS_CURRENT_FIELDTYPE ?>">
                    <span class="count">
                        <i class="fa fas fa-cogs"></i>
                        <?= str_replace(' & ', ' &amp; ', $html_header_title) ?>
                    </span>
                </span>
            <? endif; ?>

            <div class="wrapper">
                <? if(!empty($html_header_text)): ?>
                    <p class="intro intro_new"><?= $html_header_text ?></p>
                <? endif; ?>
                <? if(!empty($html_form_messages)): ?>
                    <div class="messages_wrapper">
                        <?= $html_form_messages ?>
                    </div>
                <? endif; ?>
                <div class="fields_wrapper">
                    <input type="hidden" name="submit" value="true" />
                    <input type="hidden" name="action" value="<?= $this_action ?>" />
                    <?= !empty($html_form_fields) ? $html_form_fields : '' ?>
                </div>
                <? if(!empty($html_form_buttons)): ?>
                    <div class="buttons_wrapper">
                        <?= $html_form_buttons ?>
                    </div>
                <? endif; ?>
            </div>

        </form>

    </div>
<?
// Google Analytics
if(MMRPG_CONFIG_IS_LIVE){ require(MMRPG_CONFIG_ROOTDIR.'includes/analytics.php'); }
// Unset the database variable
unset($db);
?>
</body>
</html>