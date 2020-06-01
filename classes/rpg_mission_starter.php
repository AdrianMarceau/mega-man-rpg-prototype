<?php
/**
 * Mega Man RPG Starter-Battle Mission
 * <p>The starter mission class for the Mega Man RPG Prototype.</p>
 */
class rpg_mission_starter extends rpg_mission {

    // Define a function for generating the STARTER missions
    public static function generate($this_prototype_data, $this_robot_token = 'met', $this_start_level = 1, $this_rescue_token = 'roll', $this_intro_field = 'intro-field'){

        // Pull in global variables for this function
        global $mmrpg_index, $db;
        global $this_omega_factors_one;
        global $this_omega_factors_two;
        global $this_omega_factors_three;
        global $this_omega_factors_four;
        global $this_omega_factors_five;
        global $this_omega_factors_six;
        global $this_omega_factors_seven;
        global $this_omega_factors_eight;
        global $this_omega_factors_eight_two;
        global $this_omega_factors_nine;
        global $this_omega_factors_ten;
        global $this_omega_factors_eleven;

        // Collect data on this robot and the rescue robot
        $db_robot_fields = rpg_robot::get_index_fields(true);
        $this_robot_index = $db->get_array_list("SELECT {$db_robot_fields} FROM mmrpg_index_robots WHERE robot_flag_complete = 1;", 'robot_token');
        $this_robot_data = rpg_robot::parse_index_info($this_robot_index[$this_robot_token]);
        $this_robot_name = $this_robot_data['robot_name'];
        $intro_field_data = rpg_field::get_index_info($this_intro_field);
        // Populate the battle options with the starter battle option
        $temp_target_count = 1;
        $temp_battle_token = $this_prototype_data['phase_battle_token'].'-'.$this_robot_token;
        $temp_battle_omega = array();
        $temp_battle_omega['battle_field_base']['field_id'] = 100;
        $temp_battle_omega['battle_field_base']['field_token'] = $this_intro_field;
        $temp_battle_omega['flags']['starter_battle'] = true;
        $temp_battle_omega['battle_token'] = $temp_battle_token;
        $temp_battle_omega['battle_size'] = '1x4';
        $temp_battle_omega_complete = mmrpg_prototype_battle_complete($this_prototype_data['this_player_token'], $temp_battle_omega['battle_token']);
        if (!empty($temp_battle_omega_complete['battle_count'])){ $temp_target_count = 1 + $temp_battle_omega_complete['battle_count']; }
        if ($temp_target_count > 8 ){ $temp_target_count = 8; }
        $temp_battle_omega['battle_level'] = $this_start_level;
        $temp_battle_omega['battle_phase'] = $this_prototype_data['battle_phase'];
        $temp_battle_omega['battle_name'] = 'Chapter One Intro Battle';
        //$temp_battle_omega['battle_name'] = $this_robot_name.($temp_target_count > 1 ? 's' : '');
        //$temp_battle_omega['battle_name'] = $this_robot_name.($temp_target_count > 1 ? 's' : '').' Battle';
        $temp_battle_omega['battle_turns'] = (MMRPG_SETTINGS_BATTLETURNS_PERROBOT * $temp_target_count);
        $temp_battle_omega['battle_zenny'] = ceil(($this_prototype_data['battles_complete'] > 1 ? MMRPG_SETTINGS_BATTLEPOINTS_PERLEVEL2 : MMRPG_SETTINGS_BATTLEPOINTS_PERLEVEL) * $temp_target_count * MMRPG_SETTINGS_BATTLEPOINTS_PERZENNY_MULTIPLIER);
        if ($temp_battle_omega_complete['battle_count'] > 0){ $temp_battle_omega['battle_zenny'] = ceil($temp_battle_omega['battle_zenny'] * (2 / (2 + $temp_battle_omega_complete['battle_count']))); }
        //$temp_battle_omega['battle_field_base']['field_music'] = mmrpg_prototype_get_player_boss_music($this_prototype_data['this_player_token']);
        $temp_battle_omega['battle_target_player']['player_id'] = MMRPG_SETTINGS_TARGET_PLAYERID;
        $temp_battle_omega['battle_target_player']['player_token'] = 'player';
        $temp_battle_omega['battle_target_player']['player_robots'][0] = array('robot_id' => (MMRPG_SETTINGS_TARGET_PLAYERID + 1), 'robot_token' => $this_robot_token);
        $temp_mook_robot = $temp_battle_omega['battle_target_player']['player_robots'][0];
        $temp_battle_omega['battle_target_player']['player_robots'] = array();
        $temp_name_index = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $temp_mook_tokens = array();
        /// Loop through and add other robots to the battle
        for ($i = 0; $i < $temp_target_count; $i++){
            $temp_clone_robot = $temp_mook_robot;
            $temp_clone_robot['robot_id'] = MMRPG_SETTINGS_TARGET_PLAYERID + $i;
            $temp_clone_robot['robot_level'] = $this_start_level;
            $temp_clone_robot['robot_token'] = $this_robot_token;
            $temp_robot_name = $this_robot_name;
            $temp_robot_name_token = $temp_clone_robot['robot_name_token'] = str_replace(' ', '-', strtolower($temp_robot_name));
            if (!isset($temp_mook_tokens[$temp_robot_name_token])){ $temp_mook_tokens[$temp_robot_name_token] = 0; }
            else { $temp_mook_tokens[$temp_robot_name_token]++; }
            if ($temp_target_count > 1){ $temp_clone_robot['robot_name'] = $temp_robot_name.' '.$temp_name_index[$temp_mook_tokens[$temp_robot_name_token]]; }
            else { $temp_clone_robot['robot_name'] = $temp_robot_name; }
            $temp_battle_omega['battle_target_player']['player_robots'][] = $temp_clone_robot;
        }
        // Remove any uncessesary A's from the robots' names
        foreach ($temp_battle_omega['battle_target_player']['player_robots'] AS $key => $info){
            if (!isset($info['robot_name_token'])){ continue; }
            if (isset($temp_mook_tokens[$info['robot_name_token']]) && $temp_mook_tokens[$info['robot_name_token']] == 0){
                $temp_battle_omega['battle_target_player']['player_robots'][$key]['robot_name'] = str_replace(' A', '', $info['robot_name']);
            }
        }

        // If the rescure robot has not yet been unlocked as a playable character, show it in the background
        $rescue_robot_unlockable = false;
        if (!mmrpg_prototype_robot_unlocked(false, $this_rescue_token)
            && $temp_target_count >= 2){

            // Define the rescue robot's level and display properties depending on who it is
            $rescue_robot_unlockable = true;
            $rescue_robot_level = $temp_target_count;
            $rescue_robot_frame = array(8,0,8,0,0);
            $rescue_robot_position = array('x' => 354, 'y' => 118, 'direction' => 'left');
            if ($this_rescue_token === 'roll'){
                $rescue_robot_level += 0;
                $rescue_robot_frame = array(0,6,8,6,8,10);
                $rescue_robot_position = array('x' => 91, 'y' => 118, 'direction' => 'right');
            }
            elseif ($this_rescue_token === 'disco'){
                $rescue_robot_level += 10;
                $rescue_robot_frame = array(0,1,2,1,2,10);
                $rescue_robot_position = array('x' => 180, 'y' => 140, 'direction' => 'left');
            }
            elseif ($this_rescue_token === 'rhythm'){
                $rescue_robot_level += 20;
                $rescue_robot_frame = array(0,6,2,6,2,10);
                $rescue_robot_position = array('x' => 112, 'y' => 168, 'direction' => 'left');
            }

            // Add the rescue robot to the background with animation
            if (empty($temp_battle_omega['battle_field_base']['field_foreground_attachments']) && !empty($intro_field_data['field_foreground_attachments'])){ $temp_battle_omega['battle_field_base']['field_foreground_attachments'] = $intro_field_data['field_foreground_attachments']; }
            $temp_battle_omega['battle_field_base']['field_foreground_attachments']['robot_'.$this_rescue_token.'-01'] = array('class' => 'robot', 'size' => 40, 'offset_x' => $rescue_robot_position['x'], 'offset_y' => $rescue_robot_position['y'], 'offset_z' => 2, 'robot_token' => $this_rescue_token, 'robot_frame' => $rescue_robot_frame, 'robot_direction' => $rescue_robot_position['direction']);

            // Add the rescue robot to the list of unlockables
            $temp_battle_omega['battle_rewards']['robots'] = array();
            $temp_battle_omega['battle_rewards']['robots'][] = array('token' => $this_rescue_token, 'level' => $rescue_robot_level, 'experience' => 999);

        }

        // Allow unlocking of the mecha support ability if the player has reached max targets
        if ($temp_target_count >= 8){
            // Add the Mecha Support ability as an unlockable move if not already unlocked
            $temp_battle_omega['battle_rewards']['abilities'] = array();
            if (!mmrpg_prototype_ability_unlocked($this_prototype_data['this_player_token'], false, 'mecha-support')){
                // Add the Met as a reward for the battle
                $temp_battle_omega['battle_rewards']['abilities'][] = array('token' => 'mecha-support');
                // Update the description text for the battle
                $temp_battle_omega['battle_description'] = 'Defeat the '.$this_robot_name.($temp_target_count > 1 ? 's' : '').' and download '.($temp_target_count > 1 ? 'their' : 'its').' secret mecha data! &#10023; ';
            } elseif (!mmrpg_prototype_ability_unlocked($this_prototype_data['this_player_token'], false, 'field-support')){
                // Add the Met as a reward for the battle
                $temp_battle_omega['battle_rewards']['abilities'][] = array('token' => 'field-support');
                // Update the description text for the battle
                $temp_battle_omega['battle_description'] = 'Defeat the '.$this_robot_name.($temp_target_count > 1 ? 's' : '').' and download '.($temp_target_count > 1 ? 'their' : 'its').' secret field data! &#10022; ';
            } else {
                // Update the description text for the battle
                $temp_battle_omega['battle_description'] = 'Defeat the '.$this_robot_name.($temp_target_count > 1 ? 's that are' : ' that\'s').' blocking your progress!';
            }
        }
        // Otherwise, if the player has already unlocked Roll
        else {
            // Update the description text for the battle
            $temp_battle_omega['battle_description'] = 'Defeat the '.$this_robot_name.($temp_target_count > 1 ? 's that are' : ' that\'s').' blocking your progress!';
        }

        // If the rescue robot is here, add some ambiguous text to the description
        if ($rescue_robot_unlockable){
            //$temp_battle_omega['battle_description'] = str_replace('!', ' and rescue the support robot that\'s appeared on the field!', $temp_battle_omega['battle_description']);
            $temp_battle_omega['battle_description2'] = ' Wait a minute... who\'s that in the background?';
        }

        // Add some random item drops to the starter battle
        if ($temp_target_count > 1){
            $temp_battle_omega['battle_rewards']['items'] = array(
                // Nothing if fought more than once FOR NOW
                );
        } else {
            $temp_battle_omega['battle_rewards']['items'] = array(
                // Nothing special the first time around
                );
        }

        // Return the generated omega battle data
        return $temp_battle_omega;

    }

}
?>