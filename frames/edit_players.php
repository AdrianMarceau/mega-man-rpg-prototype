<?php
// Include the TOP file
require_once('../top.php');

// Unset the prototype temp variable
$_SESSION['PROTOTYPE_TEMP'] = array();

// Require the remote top in case we're in viewer mode
define('MMRPG_REMOTE_SKIP_INDEX', true);
define('MMRPG_REMOTE_SKIP_DATABASE', true);
require(MMRPG_CONFIG_ROOTDIR.'/frames/remote_top.php');

// Collect the session token
$session_token = mmrpg_game_token();

// Include the DATABASE file
require(MMRPG_CONFIG_ROOTDIR.'database/types.php');
require(MMRPG_CONFIG_ROOTDIR.'database/players.php');
require(MMRPG_CONFIG_ROOTDIR.'database/fields.php');
require(MMRPG_CONFIG_ROOTDIR.'database/items.php');

// Collect the editor flag if set
$global_allow_editing = !defined('MMRPG_REMOTE_GAME') ? true : false;
$global_frame_source = !empty($_GET['source']) ? trim($_GET['source']) : 'prototype';

// Include the prototype data for getting omega factors
//require_once(MMRPG_CONFIG_ROOTDIR.'prototype/include.php');
require_once(MMRPG_CONFIG_ROOTDIR.'prototype/omega.php');

// Add all unlockable fields in the game so far (we'll filter later)
$temp_omega_factor_options = array();
$temp_omega_factor_options = array_merge($temp_omega_factor_options, $this_omega_factors_one);
$temp_omega_factor_options = array_merge($temp_omega_factor_options, $this_omega_factors_two);
$temp_omega_factor_options = array_merge($temp_omega_factor_options, $this_omega_factors_three);
$temp_omega_factor_options = array_merge($temp_omega_factor_options, $this_omega_factors_four);

// Loop through and remove any fields who's robots haven't been encountered yet
$session_robot_database = !empty($_SESSION[$session_token]['values']['robot_database']) ? $_SESSION[$session_token]['values']['robot_database'] : array();
foreach($temp_omega_factor_options AS $key => $option){
    $rtoken = $option['robot'];
    if (!isset($session_robot_database[$rtoken])
        || empty($session_robot_database[$rtoken]['robot_unlocked'])){
        unset($temp_omega_factor_options[$key]);
    }
}


// -- COLLECT SETTINGS DATA -- //

// Define the index of allowable players to appear in the edit
$allowed_edit_players = array();
$allowed_edit_data = array();
foreach ($_SESSION[$session_token]['values']['battle_settings'] AS $player_token => $player_info){
    if (empty($player_token) || !isset($mmrpg_database_players[$player_token])){ continue; }
    $player_info = array_merge($mmrpg_database_players[$player_token], $player_info);
    $player_info['player_image'] = !empty($player_info['player_image']) ? $player_info['player_image'] : $player_info['player_token'];
    $allowed_edit_players[] = $player_info;
    $allowed_edit_data[$player_token] = $player_info;
}
$allowed_edit_data_count = !empty($allowed_edit_players) ? count($allowed_edit_players) : 0;
$allowed_edit_player_count = !empty($allowed_edit_players) ? count($allowed_edit_players) : 0;
//$allowed_edit_data = array_reverse($allowed_edit_data, true);


// -- PROCESS FIELD ACTION -- //

// Check if an action request has been sent with an field type
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'field'){

    // Collect the field variables from the request header, if they exist
    $temp_key = !empty($_REQUEST['key']) ? $_REQUEST['key'] : 0;
    $temp_player = !empty($_REQUEST['player']) ? $_REQUEST['player'] : '';
    $temp_field = !empty($_REQUEST['field']) ? $_REQUEST['field']: '';
    // If key variables are not provided, kill the script in error
    if (empty($temp_player) || empty($temp_player) || empty($temp_field)){ die('error|request-error|'.preg_replace('/\s+/', ' ', print_r($_REQUEST, true))); }

    //die(print_r($_REQUEST, true));

    // Collect this player's current field selection from the omega session
    $temp_session_key = $temp_player.'_target-robot-omega_prototype';
    $temp_this_item_omega = !empty($_SESSION[$session_token]['values'][$temp_session_key]) ? $_SESSION[$session_token]['values'][$temp_session_key] : array();
    $temp_fields = array();
    foreach ($temp_this_item_omega AS $key => $info){ if (!empty($info['field'])){ $temp_fields[] = $info['field']; } }
    //$temp_fields = array_reverse($temp_fields);
    // Crop the field settings if they've somehow exceeded the eight limit
    if (count($temp_fields) > 8){ $temp_fields = array_slice($temp_fields, 0, 8, true); }

    // If requested new field was an empty string, remove the previous value
    if (empty($temp_field)){
        // If this was the last field, do nothing with this request
        if (count($temp_fields) <= 1){ die('success|remove-last|'.implode(',', $temp_fields)); }
        // Unset the requested key in the array
        unset($temp_fields[$temp_key]);
        // Create a new array to hold the full field settings and populate
        $temp_fields_new = array();
        foreach ($temp_fields AS $temp_token){ $temp_fields_new[$temp_token] = array('field_token' => $temp_token); }
        // Update the new field settings in the session variable
        $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_fields'] = $temp_fields_new;
        // Collect the available star counts for this player
        $temp_star_counts = mmrpg_prototype_player_stars_available($temp_player);
        // Save, produce the success message with the new field order
        mmrpg_save_game_session();
        exit('success|field-removed|'.implode(',', $temp_fields).'|'.implode(',', $temp_star_counts));

    }
    // Otherwise if this was a shuffle request
    elseif ($temp_field == 'shuffle'){
        // Shuffle fields, simple as that
        shuffle($temp_fields);
        // Create a new array to hold the full field settings and populate
        $temp_fields_new = array();
        foreach ($temp_fields AS $temp_token){ $temp_fields_new[$temp_token] = array('field_token' => $temp_token); }
        // Update the new field settings in the session variable
        $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_fields'] = $temp_fields_new;
        // Manually update the target robot omega with the new field values
        $temp_session_key = $temp_player.'_target-robot-omega_prototype';
        $new_target_robot_omega = array();
        foreach ($temp_fields_new AS $token => $info){
            if (!isset($mmrpg_database_fields[$token])){ continue; }
            $info = rpg_field::parse_index_info($mmrpg_database_fields[$token]);
            $new_target_robot_omega[] = array(
                'robot' => $info['field_master'],
                'field' => $info['field_token'],
                'type' => $info['field_type']
                );
        }
        //die(print_r($new_target_robot_omega, true));
        //$new_target_robot_omega = array_reverse($new_target_robot_omega);
        $_SESSION[$session_token]['values'][$temp_session_key] = $new_target_robot_omega;
        // Collect the available star counts for this player
        $temp_star_counts = mmrpg_prototype_player_stars_available($temp_player);
        // Save, produce the success message with the new field order
        mmrpg_save_game_session();
        exit('success|field-shuffled|'.implode(',', $temp_fields).'|'.implode(',', $temp_star_counts));


    }
    // Otherwise if this was a randomize request
    elseif ($temp_field == 'randomize'){
        // Collect a copy of the available fields and then shuffle them
        $temp_available_fields = $temp_omega_factor_options;
        shuffle($temp_available_fields);
        // Pick eight fields randomly from the entire available list
        $temp_fields = array();
        foreach ($temp_available_fields AS $key => $info){
            $temp_fields[] = $info['field'];
            if (count($temp_fields) >= 8){ break; }
        }
        //die('<pre>$temp_omega_factor_options = '.print_r($temp_omega_factor_options, true).'</pre>');
        //die('<pre>$temp_fields = '.print_r($temp_fields, true).'</pre>');
        // Create a new array to hold the full field settings and populate
        $temp_fields_new = array();
        foreach ($temp_fields AS $temp_token){ $temp_fields_new[$temp_token] = array('field_token' => $temp_token); }
        // Update the new field settings in the session variable
        $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_fields'] = $temp_fields_new;
        // Manually update the target robot omega with the new field values
        $temp_session_key = $temp_player.'_target-robot-omega_prototype';
        $new_target_robot_omega = array();
        foreach ($temp_fields_new AS $token => $info){
            if (!isset($mmrpg_database_fields[$token])){ continue; }
            $info = rpg_field::parse_index_info($mmrpg_database_fields[$token]);
            $new_target_robot_omega[] = array(
                'robot' => $info['field_master'],
                'field' => $info['field_token'],
                'type' => $info['field_type']
                );
        }
        //die(print_r($new_target_robot_omega, true));
        //$new_target_robot_omega = array_reverse($new_target_robot_omega);
        $_SESSION[$session_token]['values'][$temp_session_key] = $new_target_robot_omega;
        // Collect the available star counts for this player
        $temp_star_counts = mmrpg_prototype_player_stars_available($temp_player);
        // Save, produce the success message with the new field order
        mmrpg_save_game_session();
        exit('success|field-randomize|'.implode(',', $temp_fields).'|'.implode(',', $temp_star_counts));


    }
    // Otherwise, if there was a new field provided, update it in the array
    elseif (!in_array($temp_field, $temp_fields)){
        // Update this position in the array with the new field
        $temp_fields[$temp_key] = $temp_field;
        // Create a new array to hold the full field settings and populate
        $temp_fields_new = array();
        foreach ($temp_fields AS $temp_token){ $temp_fields_new[$temp_token] = array('field_token' => $temp_token); }
        // Update the new field settings in the session variable
        $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_fields'] = $temp_fields_new;
        // Manually update the target robot omega with the new field values
        $temp_session_key = $temp_player.'_target-robot-omega_prototype';
        $new_target_robot_omega = array();
        foreach ($temp_fields_new AS $token => $info){
            if (!isset($mmrpg_database_fields[$token])){ continue; }
            $info = rpg_field::parse_index_info($mmrpg_database_fields[$token]);
            $new_target_robot_omega[] = array(
                'robot' => $info['field_master'],
                'field' => $info['field_token'],
                'type' => $info['field_type']
                );
        }
        //die(print_r($new_target_robot_omega, true));
        //$new_target_robot_omega = array_reverse($new_target_robot_omega);
        $_SESSION[$session_token]['values'][$temp_session_key] = $new_target_robot_omega;
        // Collect the available star counts for this player
        $temp_star_counts = mmrpg_prototype_player_stars_available($temp_player);
        // Save, produce the success message with the new field order
        mmrpg_save_game_session();
        exit('success|field-updated|'.implode(',', $temp_fields).'|'.implode(',', $temp_star_counts));

    }
    // Otherwise, if this field already exists, swap position in array
    elseif (in_array($temp_field, $temp_fields)){
        // Update this position in the array with the new field
        $this_slot_key = $temp_key;
        $this_slot_value = $temp_fields[$temp_key];
        $copy_slot_value = $temp_field;
        $copy_slot_key = array_search($temp_field, $temp_fields);
        // Update this slot with new value
        $temp_fields[$this_slot_key] = $copy_slot_value;
        // Update copy slot with new value
        $temp_fields[$copy_slot_key] = $this_slot_value;
        // Create a new array to hold the full field settings and populate
        $temp_fields_new = array();
        foreach ($temp_fields AS $temp_token){ $temp_fields_new[$temp_token] = array('field_token' => $temp_token); }
        // Update the new field settings in the session variable
        $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_fields'] = $temp_fields_new;
        // Manually update the target robot omega with the new field values
        $temp_session_key = $temp_player.'_target-robot-omega_prototype';
        $new_target_robot_omega = array();
        foreach ($temp_fields_new AS $token => $info){
            if (!isset($mmrpg_database_fields[$token])){ continue; }
            $info = rpg_field::parse_index_info($mmrpg_database_fields[$token]);
            $new_target_robot_omega[] = array(
                'robot' => $info['field_master'],
                'field' => $info['field_token'],
                'type' => $info['field_type']
                );
        }
        //die(print_r($new_target_robot_omega, true));
        //$new_target_robot_omega = array_reverse($new_target_robot_omega);
        $_SESSION[$session_token]['values'][$temp_session_key] = $new_target_robot_omega;
        // Collect the available star counts for this player
        $temp_star_counts = mmrpg_prototype_player_stars_available($temp_player);
        // Save, produce the success message with the new field order
        mmrpg_save_game_session();
        exit('success|field-updated|'.implode(',', $temp_fields).'|'.implode(',', $temp_star_counts));

    } else {
        // Collect the available star counts for this player
        $temp_star_counts = mmrpg_prototype_player_stars_available($temp_player);
        // Produce an error show this field has already been selected
        exit('error|field-exists|'.implode(',', $temp_fields).'|'.implode(',', $temp_star_counts));

    }

}


// -- PROCESS CHALLENGE ACTION -- //

// Check if an action request has been sent with an challenge type
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'challenge'){

    // Collect the challenge variables from the request header, if they exist
    $temp_key = !empty($_REQUEST['key']) ? $_REQUEST['key'] : 0;
    $temp_player = !empty($_REQUEST['player']) ? $_REQUEST['player'] : '';
    $temp_challenge = !empty($_REQUEST['challenge']) ? $_REQUEST['challenge']: '';
    // If key variables are not provided, kill the script in error
    if (empty($temp_player) || empty($temp_player) || empty($temp_challenge)){ die('error|request-error|'.preg_replace('/\s+/', ' ', print_r($_REQUEST, true))); }

    //die(print_r($_REQUEST, true));

    // Collect this player's current challenge selection from the omega session
    $temp_session_key = $temp_player.'_target-challenge-missions';
    $temp_current_challenges = !empty($_SESSION[$session_token]['values'][$temp_session_key]) ? $_SESSION[$session_token]['values'][$temp_session_key] : array();

    // Crop the challenge settings if they've somehow exceeded the eight limit
    $temp_new_challenges = $temp_current_challenges;
    for ($i = 0; $i < 3; $i++){ $temp_new_challenges[] = 0; }
    if (count($temp_new_challenges) > 3){ $temp_new_challenges = array_slice($temp_new_challenges, 0, 3, true); }

    // Otherwise, if there was a new challenge provided, update it in the array
    if (!in_array($temp_challenge, $temp_current_challenges)){

        // Update this position in the array with the new challenge
        $temp_new_challenges[$temp_key] = $temp_challenge;

        // Update the session with the new challenge ID value
        $_SESSION[$session_token]['values'][$temp_session_key] = $temp_new_challenges;

        // Save, produce the success message with the new challenge order
        mmrpg_save_game_session();
        exit('success|challenge-updated|'.implode(',', $temp_new_challenges));

    }
    // Otherwise, if this challenge already exists, swap position in array
    elseif (in_array($temp_challenge, $temp_new_challenges)){

        // Update this position in the array with the new challenge
        $this_slot_key = $temp_key;
        $this_slot_value = $temp_new_challenges[$temp_key];
        $copy_slot_value = $temp_challenge;
        $copy_slot_key = array_search($temp_challenge, $temp_new_challenges);

        // Update this slot with new value
        $temp_new_challenges[$this_slot_key] = $copy_slot_value;
        // Update copy slot with new value
        $temp_new_challenges[$copy_slot_key] = $this_slot_value;

        // Update the session with the new challenge ID value
        $_SESSION[$session_token]['values'][$temp_session_key] = $temp_new_challenges;

        // Save, produce the success message with the new challenge order
        mmrpg_save_game_session();
        exit('success|challenge-updated|'.implode(',', $temp_new_challenges));

    } else {

        // Produce an error show this challenge has already been selected
        exit('error|challenge-exists|'.implode(',', $temp_new_challenges));

    }

}


// -- RECOLLECT SETTINGS DATA -- //

// Define the index of allowable players to appear in the edit
$allowed_edit_players = array();
$allowed_edit_data = array();
foreach ($_SESSION[$session_token]['values']['battle_settings'] AS $player_token => $player_info){
    if (empty($player_token) || !isset($mmrpg_database_players[$player_token])){ continue; }
    $player_info = array_merge($mmrpg_database_players[$player_token], $player_info);
    $allowed_edit_players[] = $player_info;
    $allowed_edit_data[$player_token] = $player_info;
}
$allowed_edit_data_count = !empty($allowed_edit_players) ? count($allowed_edit_players) : 0;
$allowed_edit_player_count = !empty($allowed_edit_players) ? count($allowed_edit_players) : 0;
//$allowed_edit_data = array_reverse($allowed_edit_data, true);


// -- GENERATE EDITOR MARKUP

// CANVAS MARKUP

// Generate the canvas markup for this page
if (true){

    // Start the output buffer
    ob_start();

    // Loop through the allowed edit data for all players
    $key_counter = 0;
    $player_counter = 0;
    foreach($allowed_edit_data AS $player_token => $player_info){
        $player_counter++;
        //echo '<td style="width: '.floor(100 / $allowed_edit_player_count).'%;">'."\n";
            echo '<div class="wrapper wrapper_'.($player_counter % 2 != 0 ? 'left' : 'right').' wrapper_'.$player_token.'" data-select="players" data-player="'.$player_info['player_token'].'">'."\n";
            echo '<div class="wrapper_header" title="'.$player_info['player_name'].'">'.$player_info['player_name'].'</div>';
                $player_key = $key_counter;
                $player_info['player_image_size'] = !empty($player_info['player_image_size']) ? $player_info['player_image_size'] * 2 : 80;
                $player_image_offset = $player_info['player_image_size'] > 80 ? ceil(($player_info['player_image_size'] - 80) * 0.5) : 0;
                $player_image_offset_x = -14 - $player_image_offset;
                $player_image_offset_y = -14 - $player_image_offset;
                echo '<a data-number="'.$player_info['player_number'].'" data-token="'.$player_info['player_token'].'_'.$player_info['player_token'].'" data-player="'.$player_info['player_token'].'" data-player="'.$player_info['player_token'].'" style="background-image: url(images/players/'.(!empty($player_info['player_image']) ? $player_info['player_image'] : $player_info['player_token']).'/mug_right_'.$player_info['player_image_size'].'x'.$player_info['player_image_size'].'.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: '.$player_image_offset_x.'px '.$player_image_offset_y.'px;" class="sprite sprite_player sprite_player_'.$player_token.' sprite_player_sprite sprite_'.$player_info['player_image_size'].'x'.$player_info['player_image_size'].' sprite_'.$player_info['player_image_size'].'x'.$player_info['player_image_size'].'_mugshot player_status_active player_position_active '.($player_key == $first_player_token ? 'sprite_player_current sprite_player_'.$player_token.'_current ' : '').'">'.$player_info['player_name'].'</a>'."\n";
                $key_counter++;
            //echo '<a class="sort" data-player="'.$player_info['player_token'].'">sort</a>';
            echo '</div>'."\n";
        //echo '</td>'."\n";
    }

    // Collect the contents of the buffer
    $edit_canvas_markup = ob_get_clean();
    $edit_canvas_markup = preg_replace('/\s+/', ' ', trim($edit_canvas_markup));

}

// CONSOLE MARKUP

// Generate the console markup for this page
if (true){

    // Start the output buffer
    ob_start();

    // Predefine the player options markup
    $player_options_markup = '';
    foreach($allowed_edit_data AS $player_token => $player_info){
        $temp_player_battles = mmrpg_prototype_battles_complete($player_token);
        $temp_player_transfer = $temp_player_battles >= 1 ? true : false;
        $player_options_markup .= '<option value="'.$player_info['player_token'].'" data-label="'.$player_info['player_token'].'" title="'.$player_info['player_name'].'" '.(!$temp_player_transfer ? 'disabled="disabled"' : '').'>'.$player_info['player_name'].'</option>';
    }

    // Loop through the allowed edit data for all players
    $key_counter = 0;

    // Loop through and count each player's player totals
    $temp_player_totals = array();
    foreach($allowed_edit_data AS $player_token => $player_info){
        $temp_player_totals[$player_token] = !empty($player_info['player_players']) ? count($player_info['player_players']) : 0;
    }

    // Loop through the players in the field edit data
    foreach($allowed_edit_data AS $player_token => $player_info){

        // Collect the rewards for this player
        $player_rewards = mmrpg_prototype_player_rewards($player_token);

        // Auto-populate the player fields array with appropriate values
        if (empty($player_rewards['player_fields'])){
            // Define the player fields array and prepare to populate
            $player_rewards['player_fields'] = array();
            // Loop through and add all the MM1 fields
            foreach ($temp_omega_factor_options AS $omega_key => $omega_info){
                if (empty($mmrpg_database_fields[$omega_info['field']])){ continue; }
                $field_info = rpg_field::parse_index_info($mmrpg_database_fields[$omega_info['field']]);
                $player_rewards['player_fields'][] = $field_info;
            }
        }

        // Check how many players this player has and see if they should be able to transfer
        $counter_player_players = !empty($player_info['player_players']) ? count($player_info['player_players']) : false;
        $counter_player_missions = mmrpg_prototype_battles_complete($player_info['player_token']);
        $allow_player_selector = $player_counter > 1 && $counter_player_missions > 0 ? true : false; //$counter_player_players > 1 && $player_counter > 1 ? true : false;

        // Update the player key to the current counter
        $player_key = $key_counter;
        // Make a backup of the player selector
        $allow_player_selector_backup = $allow_player_selector;
        // Collect this player's field rewards and add them to the dropdown
        $player_field_rewards = !empty($player_rewards['player_fields']) ? $player_rewards['player_fields'] : array();
        // Collect this player's item rewards and add them to the dropdown
        $player_item_rewards = !empty($player_rewards['player_items']) ? $player_rewards['player_items'] : array();

        // Collect and print the editor markup for this player
        $temp_editor_markup = rpg_player::print_editor_markup($player_info);
        echo $temp_editor_markup;

        $key_counter++;

        // Return the backup of the player selector
        $allow_player_selector = $allow_player_selector_backup;

    }

    // Collect the contents of the buffer
    $edit_console_markup = ob_get_clean();
    $edit_console_markup = preg_replace('/\s+/', ' ', trim($edit_console_markup));

}

// Generate the edit markup using the battles settings and rewards
$this_edit_markup = '';
if (true){

    // Prepare the output buffer
    ob_start();

    // Determine the token for the very first player in the edit
    $temp_player_tokens = array_values($allowed_edit_players);
    $first_player_token = array_shift($temp_player_tokens);
    $first_player_token = $first_player_token['player_token'];
    unset($temp_player_tokens);

    // Start generating the edit markup
    ?>

    <span class="header block_1 header_types type_<?= defined('MMRPG_SETTINGS_REMOTE_FIELDTYPE') ? MMRPG_SETTINGS_REMOTE_FIELDTYPE : MMRPG_SETTINGS_CURRENT_FIELDTYPE ?>">
        <span class="count">
            <i class="fa fas fa-user-circle"></i>
            Player <?= $global_allow_editing ? 'Editor' : 'Viewer' ?>
            <span class="progress">(<?= $allowed_edit_player_count == 1 ? '1 Player' : $allowed_edit_player_count.' Players' ?>)</span>
        </span>
    </span>

    <div style="float: left; width: 100%;">
    <table class="formatter" style="width: 100%; table-layout: fixed;">
        <colgroup>
            <col width="70" />
            <col width="" />
        </colgroup>
        <tbody>
            <tr>
                <td class="canvas" style="vertical-align: top;">

                    <div id="canvas" class="player_counter_<?= $allowed_edit_player_count ?>" style="">
                        <div id="links"></div>
                    </div>

                </td>
                <td class="console" style="vertical-align: top;">

                    <div id="console" class="noresize" style="height: auto;">
                        <div id="players" class="wrapper"><?/*= $edit_console_markup */?></div>
                    </div>

                </td>
            </tr>
        </tbody>
    </table>
    </div>

    <?

    // Collect the output buffer content
    $this_edit_markup = preg_replace('#\s+#', ' ', trim(ob_get_clean()));
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>Mega Man RPG Prototype | Data Library | Last Updated <?= mmrpg_print_cache_date() ?></title>
<base href="<?=MMRPG_CONFIG_ROOTURL?>" />
<meta name="players" content="noindex,nofollow" />
<meta name="darkreader-lock" content="already-dark-mode" />
<meta name="format-detection" content="telephone=no" />
<link type="text/css" href=".libs/fontawesome/v5.6.3/css/solid.css" rel="stylesheet" />
<link type="text/css" href=".libs/fontawesome/v5.6.3/css/fontawesome.css" rel="stylesheet" />
<link type="text/css" href="styles/style.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/prototype.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/edit_players.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<?if($flag_wap):?>
<link type="text/css" href="styles/style-mobile.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/prototype-mobile.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<?endif;?>
</head>
<body id="mmrpg" class="iframe" data-frame="edit_players" data-mode="<?= $global_allow_editing ? 'editor' : 'viewer' ?>" data-source="<?= $global_frame_source ?>" style="<?= !$global_allow_editing ? 'width: 100% !important; max-width: 1000px !important; ' : '' ?>">
    <div id="prototype" class="hidden" style="opacity: 0; <?= !$global_allow_editing ? 'width: 100% !important; ' : '' ?>">
        <div id="edit" class="menu" style="position: relative;">
            <div id="edit_overlay" style="border-radius: 0.5em; -moz-border-radius: 0.5em; -webkit-border-radius: 0.5em; background-color: rgba(0, 0, 0, 0.75); position: absolute; top: 50px; left: 6px; right: 4px; height: 340px; z-index: 9999; display: none;">&nbsp;</div>

            <?= $this_edit_markup ?>

        </div>

    </div>
<script type="text/javascript" src=".libs/jquery/jquery-<?= MMRPG_CONFIG_JQUERY_VERSION ?>.min.js"></script>
<script type="text/javascript" src="scripts/script.js?<?=MMRPG_CONFIG_CACHE_DATE?>"></script>
<script type="text/javascript" src="scripts/prototype.js?<?=MMRPG_CONFIG_CACHE_DATE?>"></script>
<script type="text/javascript" src="scripts/edit_players.js?<?=MMRPG_CONFIG_CACHE_DATE?>"></script>
<script type="text/javascript">
// Update game settings for this page
<? require_once(MMRPG_CONFIG_ROOTDIR.'scripts/gamesettings.js.php'); ?>
gameSettings.autoScrollTop = false;
gameSettings.allowEditing = <?= isset($_GET['edit']) ? $_GET['edit'] : 'true' ?>;
// Wait until the document is ready
$(document).ready(function(){
    // Append the markup after load to prevent halting display and waiting players
    $('#console #players').append('<?= str_replace("'", "\'", $edit_console_markup) ?>');
    $('#canvas #links').append('<?= str_replace("'", "\'", $edit_canvas_markup) ?>');
    // Update the player and player count by counting elements
    thisEditorData.playerTotal = $('#canvas .wrapper[data-player]', thisEditor).length;
    thisEditorData.playerTotal = $('#canvas .sprite[data-player]', thisEditor).length;
});
</script>
<?
// Google Analytics
if(MMRPG_CONFIG_IS_LIVE){ require(MMRPG_CONFIG_ROOTDIR.'includes/analytics.php'); }
?>
</body>
</html>
<?
// Require the remote bottom in case we're in viewer mode
require(MMRPG_CONFIG_ROOTDIR.'/frames/remote_bottom.php');
// Unset the database variable
unset($db);
?>