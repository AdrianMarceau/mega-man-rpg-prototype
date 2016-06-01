<?

// ROBOT ACTIONS : SORT ROBOTS

// Collect the ability variables from the request header, if they exist
$temp_token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : '';
$temp_order = !empty($_REQUEST['order']) ? $_REQUEST['order'] : '';
$temp_player = !empty($_REQUEST['player']) ? $_REQUEST['player'] : '';
$temp_token_order = $temp_token.'_'.$temp_order;
// If key variables are not provided, kill the script in error
if (empty($temp_token) || empty($temp_order) || empty($temp_player)){
    die('error|request-error|'.preg_replace('/\s+/', ' ', print_r($_REQUEST, true)));
}

//die(print_r($_REQUEST, true));

// Ensure this player's robots exist in the current game session
if (!empty($_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'])
    && !empty($_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'])){

        // Make a copy of the player robots array
        $temp_player_robots = $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'];
        $temp_player_robots_rewards = $_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'];
        if (!empty($temp_player_robots)){
            foreach ($temp_player_robots AS $token => $info){
                // Update the current and session arrays to make absolutely sure the robot token is in the right place
                if (empty($info['robot_token'])){ $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'][$token]['robot_token'] = $temp_player_robots[$token]['robot_token'] = $token; }
                //$temp_player_robots[$token]['robot_level'] = $_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'][$token]['robot_level'];
                //$temp_player_robots[$token]['robot_experience'] = $_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'][$token]['robot_experience'];
            }
        }
        //die('<pre>'.print_r($temp_player_robots, true).'</pre>');

        // Define a temporarily function for sorting the robots
        $mmrpg_database_robots_keys = array_keys($mmrpg_database_robots);

        // If the sort token was by number and asc
        if ($temp_token_order == 'number_asc'){
            // Define the sort function that uses these keys
            function temp_player_robots_sort($r1, $r2){
                global $mmrpg_index, $mmrpg_database_robots_keys;
                if (empty($r1) || empty($r2)){ return 0; }
                $robot1_position = array_search($r1['robot_token'], $mmrpg_database_robots_keys);
                $robot2_position = array_search($r2['robot_token'], $mmrpg_database_robots_keys);
                if ($robot1_position === false){ return 1; }
                elseif ($robot2_position === false){ return -1; }
                elseif ($robot1_position < $robot2_position){ return -1; }
                elseif ($robot1_position > $robot2_position){ return 1; }
                else { return 0; }
            }
        }
        // Else if the sort token was by number and desc
        elseif ($temp_token_order == 'number_desc'){
            // Define the sort function that uses these keys
            function temp_player_robots_sort($r1, $r2){
                global $mmrpg_index, $mmrpg_database_robots_keys;
                if (empty($r1) || empty($r2)){ return 0; }
                $robot1_position = array_search($r1['robot_token'], $mmrpg_database_robots_keys);
                $robot2_position = array_search($r2['robot_token'], $mmrpg_database_robots_keys);
                if ($robot1_position === false){ return -1; }
                elseif ($robot2_position === false){ return 1; }
                elseif ($robot1_position < $robot2_position){ return 1; }
                elseif ($robot1_position > $robot2_position){ return -1; }
                else { return 0; }
            }
        }
        // Else if the sort token was by level and asc
        elseif ($temp_token_order == 'level_asc'){
            // Define the sort function that uses these keys
            function temp_player_robots_sort($r1, $r2){
                global $mmrpg_index, $mmrpg_database_robots_keys, $temp_player_robots_rewards;
                if (empty($r1) || empty($r2)){ return 0; }
                $robot1_position = array_search($r1['robot_token'], $mmrpg_database_robots_keys);
                $robot2_position = array_search($r2['robot_token'], $mmrpg_database_robots_keys);
                $r1['robot_level'] = $temp_player_robots_rewards[$r1['robot_token']]['robot_level'];
                $r1['robot_experience'] = $temp_player_robots_rewards[$r1['robot_token']]['robot_experience'];
                $r2['robot_level'] = $temp_player_robots_rewards[$r2['robot_token']]['robot_level'];
                $r2['robot_experience'] = $temp_player_robots_rewards[$r2['robot_token']]['robot_experience'];
                if ($robot1_position === false){ return -1; }
                elseif ($robot2_position === false){ return 1; }
                elseif ($r1['robot_level'] < $r2['robot_level']){ return -1; }
                elseif ($r1['robot_level'] > $r2['robot_level']){ return 1; }
                elseif ($r1['robot_experience'] < $r2['robot_experience']){ return -1; }
                elseif ($r1['robot_experience'] > $r2['robot_experience']){ return 1; }
                elseif ($robot1_position < $robot2_position){ return -1; }
                elseif ($robot1_position > $robot2_position){ return 1; }
                else { return 0; }
            }
        }
        // Else if the sort token was by level and desc
        elseif ($temp_token_order == 'level_desc'){
            // Define the sort function that uses these keys
            function temp_player_robots_sort($r1, $r2){
                global $mmrpg_index, $mmrpg_database_robots_keys, $temp_player_robots_rewards;
                if (empty($r1) || empty($r2)){ return 0; }
                $robot1_position = array_search($r1['robot_token'], $mmrpg_database_robots_keys);
                $robot2_position = array_search($r2['robot_token'], $mmrpg_database_robots_keys);
                $r1['robot_level'] = $temp_player_robots_rewards[$r1['robot_token']]['robot_level'];
                $r1['robot_experience'] = $temp_player_robots_rewards[$r1['robot_token']]['robot_experience'];
                $r2['robot_level'] = $temp_player_robots_rewards[$r2['robot_token']]['robot_level'];
                $r2['robot_experience'] = $temp_player_robots_rewards[$r2['robot_token']]['robot_experience'];
                if ($robot1_position === false){ return 1; }
                elseif ($robot2_position === false){ return -1; }
                elseif ($r1['robot_level'] < $r2['robot_level']){ return 1; }
                elseif ($r1['robot_level'] > $r2['robot_level']){ return -1; }
                elseif ($r1['robot_experience'] < $r2['robot_experience']){ return 1; }
                elseif ($r1['robot_experience'] > $r2['robot_experience']){ return -1; }
                elseif ($robot1_position < $robot2_position){ return 1; }
                elseif ($robot1_position > $robot2_position){ return -1; }
                else { return 0; }
            }
        }

        // Sort the robots and maintain index association
        uasort($temp_player_robots, 'temp_player_robots_sort');

        // Ensure nothing went wrong with the array before copying
        if (!empty($temp_player_robots)){
            $temp_robot_tokens = implode(',', array_keys($temp_player_robots));
            $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'] = $temp_player_robots;
            exit('success|array-sorted|'.$temp_robot_tokens);
        }
        // Otherwise produce an error
        else {
            // Produce the error message
            exit('error|array-corrupted|false');
        }


    }
// Otherwise, produce an error
else {

    // Produce the error message
    exit('error|robots-undefined|false');

}



?>