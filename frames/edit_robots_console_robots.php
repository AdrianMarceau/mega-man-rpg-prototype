<?

// CONSOLE MARKUP : ROBOTS

// Start the output buffer
ob_start();

// Include the necessary database files
require(MMRPG_CONFIG_ROOTDIR.'database/types.php');
require(MMRPG_CONFIG_ROOTDIR.'database/items.php');

// Predefine the player options markup
$player_options_markup = '';
foreach($allowed_edit_data AS $player_token => $player_info){
    $temp_player_battles = rpg_prototype::battles_complete($player_token);
    $temp_player_transfer = $temp_player_battles >= 1 ? true : false;
    $player_options_markup .= '<option value="'.$player_info['player_token'].'" data-label="'.$player_info['player_token'].'" title="'.$player_info['player_name'].'" '.(!$temp_player_transfer ? 'disabled="disabled"' : '').'>'.$player_info['player_name'].'</option>';
}

// Predefine the item options markup
$item_options_markup = '';
$item_options_markup .= '<option value="" data-label="No Item" title="No Item">- No Item -</option>';
$item_options_markup .= '<optgroup label="Single-Use Items">';
if (!empty($_SESSION[$session_token]['values']['battle_items'])){
    foreach($mmrpg_database_items AS $item_token => $item_info){
        if (preg_match('/-screw$/i', $item_token)){ continue; }
        elseif (preg_match('/-shard$/i', $item_token)){ continue; }
        elseif (preg_match('/-star$/i', $item_token)){ continue; }
        $item_quantity = !empty($_SESSION[$session_token]['values']['battle_items'][$item_token]) ? $_SESSION[$session_token]['values']['battle_items'][$item_token] : 0;
        if ($item_quantity < 1){ continue; }
        if (preg_match('/-core$/i', $item_token)){
            $item_options_markup .= '</optgroup>';
            $item_options_markup .= '<optgroup label="Multi-Use Items">';
        }
        $item_label = $item_info['item_name'];
        $item_title = $item_info['item_name'];
        if (!empty($item_info['item_type'])){ $item_title .= ' | '.ucfirst($item_info['item_type']).' Type';  }
        else { $item_title .= ' | Neutral Type'; }
        if (!empty($item_info['item_description'])){ $item_title .= ' || '.$item_info['item_description'];  }
        $item_options_markup .= '<option value="'.$item_token.'" data-label="'.$item_label.'" title="'.$item_title.'">'.$item_label.' x '.$item_quantity.'</option>';
    }
}
$item_options_markup .= '</optgroup>';

/*
foreach($allowed_edit_data AS $player_token => $player_info){
    $temp_player_battles = rpg_prototype::battles_complete($player_token);
    $temp_player_transfer = $temp_player_battles >= 1 ? true : false;
    $item_options_markup .= '<option value="'.$player_info['player_token'].'" data-label="'.$player_info['player_token'].'" title="'.$player_info['player_name'].'" '.(!$temp_player_transfer ? 'disabled="disabled"' : '').'>'.$player_info['player_name'].'</option>';
}
*/

// Loop through the allowed edit data for all players
$key_counter = 0;

// Loop through and count each player's robot totals
$temp_robot_totals = array();
foreach($allowed_edit_data AS $player_token => $player_info){
    $temp_robot_totals[$player_token] = !empty($player_info['player_robots']) ? count($player_info['player_robots']) : 0;
}

// Loop through the players in the ability edit data
foreach($allowed_edit_data AS $player_token => $player_info){

    // Collect the rewards for this player
    $player_rewards = rpg_game::player_rewards($player_token);

    // Check how many robots this player has and see if they should be able to transfer
    $counter_player_robots = !empty($player_info['player_robots']) ? count($player_info['player_robots']) : false;
    $counter_player_missions = rpg_prototype::battles_complete($player_info['player_token']);
    $allow_player_selector = $allowed_edit_player_count > 1 && $counter_player_missions > 0 ? true : false;

    // Loop through the player robots and display their edit boxes
    foreach ($player_info['player_robots'] AS $robot_token => $robot_info){

        // Update the robot key to the current counter
        $robot_key = $key_counter;

        // Make a backup of the player selector
        $allow_player_selector_backup = $allow_player_selector;

        // Collect this player's ability rewards and add them to the dropdown
        if (!empty($_SESSION[$session_token]['values']['battle_abilities'])){

            // Collect global abilities from the session and expand
            $temp_ability_rewards = $_SESSION[$session_token]['values']['battle_abilities'];
            $player_ability_rewards = array();
            foreach ($temp_ability_rewards AS $token){ $player_ability_rewards[$token] = array('ability_token' => $token); }
            unset($temp_ability_rewards);

        } elseif (!empty($player_rewards['player_abilities'])){

            // Collect player abilities from the session
            $player_ability_rewards = $player_rewards['player_abilities'];

        } else {

            // Define an empty array for abilities
            $player_ability_rewards = array();

        }

        if (!empty($player_ability_rewards)){ asort($player_ability_rewards); }

        // Collect and print the editor markup for this robot
        if (
            !empty($_REQUEST['player']) && $_REQUEST['player'] == $player_info['player_token'] &&
            !empty($_REQUEST['robot']) && $_REQUEST['robot'] == $robot_info['robot_token']
            ){

            $temp_editor_markup = rpg_robot::print_editor_markup($player_info, $robot_info);
            echo $temp_editor_markup;

            // Collect the array of unseen menu frame robots if there is one, then clear it
            $frame_token = 'edit_robots';
            $content_token = $robot_info['robot_token'];
            rpg_prototype::remove_menu_frame_content_unseen($frame_token, $content_token);

            // Collect the contents of the buffer
            $edit_console_markup = ob_get_clean();
            $edit_console_markup = preg_replace('/\s+/', ' ', trim($edit_console_markup));
            exit($edit_console_markup);

        }

        $key_counter++;

        // Return the backup of the player selector
        $allow_player_selector = $allow_player_selector_backup;

    }

}

// Collect the contents of the buffer
$edit_console_markup = ob_get_clean();
$edit_console_markup = preg_replace('/\s+/', ' ', trim($edit_console_markup));
exit($edit_console_markup);

?>