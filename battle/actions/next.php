<?

// -- NEXT BATTLE ACTION -- //

// Pre-collect the redirect token (in case we need to change it)
if (!empty($this_battle->battle_complete_redirect_token)){ $battle_complete_redirect_token = $this_battle->battle_complete_redirect_token; }
else { $battle_complete_redirect_token = $this_battle->battle_token; }

// Create the battle chain array if not exists
$is_first_mission = false;
$this_battle_chain = !empty($_SESSION['BATTLES_CHAIN'][$this_battle->battle_chain_token]) ? $_SESSION['BATTLES_CHAIN'][$this_battle->battle_chain_token] : array();
if (empty($this_battle_chain)){ $is_first_mission = true; }
$this_chain_record = array(
    'battle_token' => $this_battle->battle_token,
    'battle_turns_used' => $this_battle->counters['battle_turn'],
    'battle_robots_used' => (!empty($this_player->counters['robots_start_total']) ? $this_player->counters['robots_start_total'] : 0),
    'battle_zenny_earned' => (!empty($this_battle->counters['final_zenny_reward']) ? $this_battle->counters['final_zenny_reward'] : 0)
    );
if ($is_first_mission){
    $this_team_config = $this_player->player_token.'::'.implode(',', $this_player->values['robots_start_team']);
    $this_chain_record['battle_team_config'] = $this_team_config;
}
$this_battle_chain[] = $this_chain_record;
if ($this_battle->battle_status === 'complete'
    && $this_battle->battle_result == 'victory'){
    $_SESSION['BATTLES_CHAIN'][$this_battle->battle_chain_token] = $this_battle_chain;
}
//error_log('$_SESSION[\'BATTLES_CHAIN\']['.$this_battle->battle_chain_token.'] = '.print_r($_SESSION['BATTLES_CHAIN'][$this_battle->battle_chain_token], true));

// If this is a STAR FIELD battle, break apart the next action
if (!empty($this_battle->flags['starfield_mission'])
    && empty($this_battle->battle_complete_redirect_token)){

    // Break apart the action token into the requested star type, else return to home if we can't
    if (empty($this_action_token) || !strstr($this_action_token, '-star')){ $this_redirect = 'prototype.php?'.($flag_wap ? 'wap=true' : ''); return; }
    list($next_star_type) = explode('-', $this_action_token);

    //echo('$_GET = '.print_r($_GET, true).PHP_EOL);
    //echo('$this_action = '.print_r($this_action, true).PHP_EOL);
    //echo('$this_action_token = '.print_r($this_action_token, true).PHP_EOL);
    //echo('$next_star_type = '.print_r($next_star_type, true).PHP_EOL.PHP_EOL);

    // Collect the fields index in case we need it later
    $mmrpg_index_fields = rpg_field::get_index();

    // Count the number of stars collected to determine level
    $star_count = mmrpg_prototype_stars_unlocked();
    $star_level = 50 + ceil(50 * ($star_count / MMRPG_SETTINGS_STARFORCE_STARTOTAL));

    // Collect a list of possible stars
    $possible_star_list = mmrpg_prototype_possible_stars(true);
    $max_star_force = array();
    if (!empty($possible_star_list)){
        foreach ($possible_star_list AS $star_token => $star_info){
            if (!isset($max_star_force[$star_info['info1']['type']])){ $max_star_force[$star_info['info1']['type']] = 0; }
            if (!empty($star_info['info2']) && !isset($max_star_force[$star_info['info2']['type']])){ $max_star_force[$star_info['info2']['type']] = 0; }
            if ($star_info['kind'] == 'fusion'){
                if ($star_info['info1']['type'] == $star_info['info2']['type']){
                    $max_star_force[$star_info['info1']['type']] += 2;
                } else {
                    $max_star_force[$star_info['info1']['type']] += 1;
                    $max_star_force[$star_info['info2']['type']] += 1;
                }
            } else {
                $max_star_force[$star_info['info1']['type']] += 1;
            }
        }
    }

    // Collect a list of available stars still left for the player to encounter
    $temp_remaining_stars = mmrpg_prototype_remaining_stars(true);

    // If the user has stars remaining, we should pick from them
    $temp_allowed_stars = array();
    $temp_allowed_star_types = array();
    if (!empty($temp_remaining_stars)){
        //error_log('pull from $temp_remaining_stars');

        // Catalogue the remaining star types so that we can prioritize them
        $temp_allowed_stars = $temp_remaining_stars;

    }
    // Otherwise we should simply send them to a new empty field
    else {
        //error_log('pull from $possible_star_list');

        // Catalogue the allowed star types so that we can shuffle them
        $temp_allowed_stars = $possible_star_list;

    }

    // Catalogue the remaining star types so that we can prioritize them
    if (!empty($temp_allowed_stars)){
        foreach ($temp_allowed_stars AS $token => $details){
            if (!empty($details['info1']['type'])){
                $type1 = $details['info1']['type'];
                if (!isset($temp_allowed_star_types[$type1])){ $temp_allowed_star_types[$type1] = array(); }
                $temp_allowed_star_types[$type1][] = $token;
            }
            if (!empty($details['info2']['type'])){
                $type2 = $details['info2']['type'];
                if (!isset($temp_allowed_star_types[$type2])){ $temp_allowed_star_types[$type2] = array(); }
                $temp_allowed_star_types[$type2][] = $token;
            }
        }
    }

    // If the user has requested an auto-selected type, selected it now
    if ($next_star_type === 'same'
        || $next_star_type === 'any'){
        //error_log('next//$temp_allowed_star_types: '.print_r($temp_allowed_star_types, true));
        //error_log('next//$temp_allowed_star_types(keys): '.print_r(array_keys($temp_allowed_star_types), true));
        $possible_types = array();
        // If the user has requested the same star type, pick one at random from the remaining
        if ($next_star_type === 'same'){
            if (!empty($this_battle->battle_field_base['field_type'])
                && !empty($temp_allowed_star_types[$this_battle->battle_field_base['field_type']])){
                $possible_types[] = $this_battle->battle_field_base['field_type'];
            }
            if (!empty($this_battle->battle_field_base['field_type2'])
                && !empty($temp_allowed_star_types[$this_battle->battle_field_base['field_type2']])){
                $possible_types[] = $this_battle->battle_field_base['field_type2'];
            }
            //error_log('next//same//$possible_types: '.print_r($possible_types, true));
        }
        // Else if the user has requested any star type, pick one at random from the remaining
        elseif ($next_star_type === 'any'){
            $possible_types = array_keys(array_filter($temp_allowed_star_types));
            //error_log('next//any//$possible_types: '.print_r($possible_types, true));
        }
        if (!empty($possible_types)){
            $next_star_type = $possible_types[mt_rand(0, (count($possible_types) - 1))];
            //error_log('next//$next_star_type: '.print_r($next_star_type, true));
        } else {
            //error_log('next//empty-possible-types//redirect');
            $this_redirect = 'prototype.php?'.($flag_wap ? 'wap=true' : '');
            return;
        }
    }

    // Create a flag variables for the random encounter
    $random_encounter_chance = false;
    if (empty($this_battle->flags['superboss_battle'])){
        if ($star_count >= 10){
            for ($i = 0; $i < 10; $i++){
                if (mt_rand(0, MMRPG_SETTINGS_STARFORCE_STARTOTAL) <= $star_count){
                    $random_encounter_chance = true;
                    break;
                }
            }
        }
    }
    //error_log('next//$star_count: '.print_r($star_count, true));
    //error_log('next//MMRPG_SETTINGS_STARFORCE_STARTOTAL: '.print_r(MMRPG_SETTINGS_STARFORCE_STARTOTAL, true));
    //error_log('next//$random_encounter_chance: '.print_r($random_encounter_chance ? 'true' : 'false', true));

    // Ensure there is a star for the requested type, else return to home if we can't
    if (empty($temp_allowed_star_types[$next_star_type])){ $this_redirect = 'prototype.php?'.($flag_wap ? 'wap=true' : ''); return; }
    $next_star_options = $temp_allowed_star_types[$next_star_type];
    $next_star_token = $next_star_options[mt_rand(0, (count($next_star_options) - 1))];
    $next_star_info = $temp_allowed_stars[$next_star_token];
    //error_log('$next_star_token = '.print_r($next_star_token, true));
    //error_log('$next_star_info = '.print_r($next_star_info, true));

    // Collect basic star variables necessary to generating next battle
    $info = $next_star_info['info1'];
    $info2 = $next_star_info['info2'];
    $field_info = $mmrpg_index_fields[$info['field']];
    $field_info2 = !empty($info2) ? $mmrpg_index_fields[$info2['field']] : $field_info;
    $next_star_level = $this_battle->battle_level + 1;
    if ($next_star_level > 100){ $next_star_level = 100; }

    // Generate the pseudo prototype data needed for generating next battle
    $this_prototype_data = array();
    $this_prototype_data['this_player_token'] = $this_player->player_token;
    $this_prototype_data['battle_phase'] = 2;
    $this_prototype_data['phase_token'] = 'phase'.$this_prototype_data['battle_phase'];
    $this_prototype_data['phase_battle_token'] = $this_prototype_data['this_player_token'].'-'.$this_prototype_data['phase_token'];
    $this_prototype_data['battles_complete'] = mmrpg_prototype_battles_complete($this_prototype_data['this_player_token']);
    $this_prototype_data['this_current_chapter'] = '7';

    // Calculate the current starforce total vs max starforce total for mission gen
    $session_token = mmrpg_game_token();
    $current_starforce = !empty($_SESSION[$session_token]['values']['star_force']) ? $_SESSION[$session_token]['values']['star_force'] : array();
    $this_prototype_data['current_starforce_total'] = !empty($_SESSION[$session_token]['values']['star_force']) ? array_sum($_SESSION[$session_token]['values']['star_force']) : 0;
    $this_prototype_data['max_starforce'] = $max_star_force;
    $this_prototype_data['max_starforce_total'] = array_sum($max_star_force);

    //echo('$info = '.print_r($info, true).PHP_EOL);
    //echo('$info2 = '.print_r($info2, true).PHP_EOL);
    //echo('$next_star_level = '.print_r($next_star_level, true).PHP_EOL);
    //echo('$this_prototype_data = '.print_r($this_prototype_data, true).PHP_EOL.PHP_EOL);

    // Include relevant dependent files like starforce and omega factors
    include(MMRPG_CONFIG_ROOTDIR.'prototype/omega.php');

    // Generate the actual battle given the provided star info, whether fusion or field variety
    if (!empty($info) && !empty($info2) && $info['field'] != $info2['field']){
        $temp_battle_omega = rpg_mission_starfield::generate_double($this_prototype_data, array($info['robot'], $info2['robot']), array($info['field'], $info2['field']), $next_star_level, true, false, true);
    } elseif (!empty($info)){
        $temp_battle_omega = rpg_mission_starfield::generate_single($this_prototype_data, $info['robot'], $info['field'], $next_star_level, true, false, true);
    }

    //echo('$temp_battle_token = '.print_r($temp_battle_omega['battle_token'], true).PHP_EOL.PHP_EOL);
    //echo('$temp_battle_omega = '.print_r($temp_battle_omega, true).PHP_EOL.PHP_EOL);

    // Update the chapter number and then save this data to the temp index
    $temp_battle_omega['option_chapter'] = $this_prototype_data['this_current_chapter'];
    rpg_battle::update_index_info($temp_battle_omega['battle_token'], $temp_battle_omega);


    // SUPERBOSS STARTDROIDS (+ SUNSTAR) : RANDOM ENCOUNTERS
    // If random encounter has not been added, check to see if we can add now
    if ($random_encounter_chance){
        mmrpg_prototype_overwrite_with_stardroid_encounter_data($this_prototype_data, $temp_battle_omega, $field_info, $field_info2);
    }

    // Update the redirect token to that of the new star field mission
    $battle_complete_redirect_token = $temp_battle_omega['battle_token'];
    //error_log('next//$battle_complete_redirect_token: '.print_r($battle_complete_redirect_token, true));

}

// Pre-generate active robots string and save any buffs/debuffs/etc.
$active_robot_array = array();
$active_robot_array_first = array();
if (!isset($_SESSION['ROBOTS_PRELOAD'])){ $_SESSION['ROBOTS_PRELOAD'] = array(); }
$temp_player_active_robots = $this_player->values['robots_active'];
usort($temp_player_active_robots, function($r1, $r2){
    if ($r1['robot_position'] == 'active'){ return -1; }
    elseif ($r2['robot_position'] == 'active'){ return 1; }
    elseif ($r1['robot_key'] < $r2['robot_key']){ return -1; }
    elseif ($r1['robot_key'] > $r2['robot_key']){ return 1; }
    else { return 0; }
    });
foreach ($temp_player_active_robots AS $key => $robot){

    // Add this robot's ID + token to the list
    $robot_string = $robot['robot_id'].'_'.$robot['robot_token'];
    $active_robot_array[] = $robot_string;
    if (empty($active_robot_array_first)){
        $active_robot_array_first = array($robot['robot_id'], $robot['robot_token']);
    }

    // Recover Weapon Energy between battles, one if active two if bench (as if a turn had passed)
    $old_weapon_energy = $robot['robot_weapons'];
    $new_weapon_energy = $old_weapon_energy + ($robot['robot_position'] == 'active' ? 1 : 2);
    if ($new_weapon_energy > $robot['robot_base_weapons']){ $new_weapon_energy = $robot['robot_base_weapons']; }

    // Loop through attack/defense/speed mods and normalize them if not zero by one point where applicable
    $stat_mods = array('attack_mods', 'defense_mods', 'speed_mods');
    $new_mod_values = array();
    foreach ($stat_mods AS $mod_token){
        $new_mod_value = (int)($robot['counters'][$mod_token]);
        if ($new_mod_value > 0){ $new_mod_value -= 1; }
        elseif ($new_mod_value < 0){ $new_mod_value += 1; }
        $new_mod_values[$mod_token] = $new_mod_value;
    }

    // Reduce any durations by one if we have to so things don't persist forever
    $new_robot_attachments = $robot['robot_attachments'];
    foreach ($new_robot_attachments AS $key => $info){
        if (isset($info['attachment_duration']) && $info['attachment_duration'] > 0){
            $info['attachment_duration'] -= 1;
            $new_robot_attachments[$key] = $info;
            if ($info['attachment_duration'] <= 0){ unset($new_robot_attachments[$key]); }
        }
    }

    // Save this robot's current energy, weapons, attack/defense/speed mods, etc. to the session
    $robot_preload_array = array(
        'robot_energy' => $robot['robot_energy'],
        'robot_weapons' => $new_weapon_energy,
        'robot_attack_mods' => $new_mod_values['attack_mods'],
        'robot_defense_mods' => $new_mod_values['defense_mods'],
        'robot_speed_mods' => $new_mod_values['speed_mods'],
        'robot_image' => $robot['robot_image'],
        'robot_item' => $robot['robot_item'],
        'robot_abilities' => $robot['robot_abilities'],
        'robot_attachments' => $new_robot_attachments
        );
    if (!empty($robot['robot_persona'])){ unset($robot_preload_array['robot_image']); }
    if (isset($robot['robot_persona'])){ $robot_preload_array['robot_persona'] = $robot['robot_persona']; }
    if (isset($robot['robot_persona_image'])){ $robot_preload_array['robot_persona_image'] = $robot['robot_persona_image']; }
    if (isset($robot['robot_support'])){ $robot_preload_array['robot_support'] = $robot['robot_support']; }
    if (isset($robot['robot_support_image'])){ $robot_preload_array['robot_support_image'] = $robot['robot_support_image']; }
    $_SESSION['ROBOTS_PRELOAD'][$battle_complete_redirect_token][$robot_string] = $robot_preload_array;
    //error_log('saving to ROBOTS_PRELOAD: '.$robot_string.' = '.print_r($robot_preload_array, true));

}
$active_robot_string = implode(',', $active_robot_array);

// Automatically empty all temporary battle variables
$_SESSION['BATTLES'] = array();
$_SESSION['FIELDS'] = array();
$_SESSION['PLAYERS'] = array();
$_SESSION['ROBOTS'] = array();
$_SESSION['ABILITIES'] = array();
$_SESSION['ITEMS'] = array();

// Generate the URL for the next mission with provided token
$next_battle_id = $this_battle->battle_id + 1;
$next_battle_token = $battle_complete_redirect_token;
$next_mission_href = 'battle.php?wap='.($flag_wap ? 'true' : 'false');
$next_mission_href .= '&this_battle_id='.$next_battle_id;
$next_mission_href .= '&this_battle_token='.$next_battle_token;
$next_mission_href .= '&this_player_id='.$this_player->player_id;
$next_mission_href .= '&this_player_token='.$this_player->player_token;
$next_mission_href .= '&this_player_robots='.$active_robot_string;
$next_mission_href .= '&flag_skip_fadein=true';

// If we're in the middle of an ENDLESS ATTACK MODE challene, regenerate the mission
if (!empty($this_battle->flags['challenge_battle'])
    && !empty($this_battle->flags['endless_battle'])){

    // Generate the first ENDLESS ATTACK MODE mission and append it to the list
    $this_mission_number = count($this_battle_chain);
    $next_mission_number = $this_mission_number + 1;
    $this_prototype_data = array();
    $this_prototype_data['this_player_token'] = $this_player->player_token;
    $this_prototype_data['this_current_chapter'] = '8';
    $this_prototype_data['battle_phase'] = 4;
    $temp_battle_sigma = rpg_mission_endless::generate_endless_mission($this_prototype_data, $next_mission_number);
    rpg_battle::update_index_info($temp_battle_sigma['battle_token'], $temp_battle_sigma);

    // We should also save this data in the DB in case we need to restore later
    $save_state = array(
        'BATTLES_CHAIN' => $this_battle_chain,
        'ROBOTS_PRELOAD' => $_SESSION['ROBOTS_PRELOAD'],
        'NEXT_MISSION' => array(
            'this_battle_id' => $next_battle_id,
            'this_battle_token' => $next_battle_token,
            'this_player_id' => $this_player->player_id,
            'this_player_token' => $this_player->player_token,
            'this_player_robots' => $active_robot_string
            )
        );
    $save_state_encoded = json_encode($save_state, JSON_HEX_QUOT);
    $save_state_encoded = str_replace('\\u0022', '\\\\"', $save_state_encoded);
    //$save_state_encoded = json_encode($save_state, JSON_HEX_QUOT | JSON_HEX_TAG);
    //$save_state_encoded = serialize($save_state);
    $update_array = array('challenge_wave_savestate' => $save_state_encoded);
    $db->update('mmrpg_challenges_waveboard', $update_array, array('user_id' => $this_user_id));

    // And just-in-case the user closes the window, let's save the actual game now too
    mmrpg_save_game_session();

}

// If this is a STAR FIELD battle, break apart the next action
if (!empty($this_battle->flags['starfield_mission'])){

    // And just-in-case the user closes the window, let's save the actual game now too
    mmrpg_save_game_session();

}

// Redirect the user back to the next mission
$this_redirect = $next_mission_href;

/*

// Generate the URL for the next mission with provided token
$next_mission_href = 'battle_loop.php?wap='.($flag_wap ? 'true' : 'false');
$next_mission_href .= '&this_battle_id='.($this_battle->battle_id + 1);
$next_mission_href .= '&this_battle_token='.$battle_complete_redirect_token;
$next_mission_href .= '&this_field_id='.$this_field_id;
$next_mission_href .= '&this_field_token='.$this_field_token;
$next_mission_href .= '&this_user_id='.$this_user_id;
$next_mission_href .= '&this_player_id='.$this_player->player_id;
$next_mission_href .= '&this_player_token='.$this_player->player_token;
$next_mission_href .= '&this_player_robots='.$active_robot_string;
$next_mission_href .= '&this_robot_id='.$active_robot_array_first[0];
$next_mission_href .= '&this_robot_token='.$active_robot_array_first[1];
$next_mission_href .= '&target_user_id='.$target_user_id;
$next_mission_href .= '&target_player_id='.$target_player_id;
$next_mission_href .= '&target_player_token='.$target_player_token;
$next_mission_href .= '&target_robot_id=auto';
$next_mission_href .= '&target_robot_token=auto';
$next_mission_href .= '&this_action=start';
$next_mission_href .= '&target_action=start';

// Redirect to a new battle loop with new targets
header('Location: '.$next_mission_href);
exit();

*/

?>