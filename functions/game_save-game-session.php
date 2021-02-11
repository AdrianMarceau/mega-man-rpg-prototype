<?
// Define a function for saving the game session
function mmrpg_save_game_session(){

    // Reference global variables
    global $db;
    $session_token = mmrpg_game_token();
    global $mmrpg_index_players, $mmrpg_index_robots;
    if (empty($mmrpg_index_players)){ $mmrpg_index_players = rpg_player::get_index(true); }
    if (empty($mmrpg_index_robots)){ $mmrpg_index_robots = rpg_robot::get_index(true); }

    // Do NOT load, save, or otherwise alter the game file while viewing remote
    if (defined('MMRPG_REMOTE_GAME')){ return true; }

    // If the required USER or FILE arrays do not exist, reset
    if (!isset($_SESSION[$session_token]['USER'])){ mmrpg_reset_game_session(); }

    // Update the last saved value
    $_SESSION[$session_token]['values']['last_save'] = time();

    // Collect the save info
    $save = $_SESSION[$session_token];
    $this_user = $save['USER'];

    // -- DEMO MODE SAVE -- //
    if (!empty($_SESSION[$session_token]['DEMO'])){

        // You can't save in demo mode...

    }
    // -- NORMAL MODE SAVE -- //
    elseif (empty($_SESSION[$session_token]['DEMO'])){

        //error_log('saving game session for user ID '.$this_user['userid']);

        // UPDATE DATABASE INFO

        // Collect the save info
        $this_cache_date = !empty($save['CACHE_DATE']) ? $save['CACHE_DATE'] : MMRPG_CONFIG_CACHE_DATE;
        $this_counters = !empty($save['counters']) ? $save['counters'] : array();
        $this_values = !empty($save['values']) ? $save['values'] : array();
        $this_flags = !empty($save['flags']) ? $save['flags'] : array();
        $this_settings = !empty($save['battle_settings']) ? $save['battle_settings'] : array();
        $this_stars = !empty($save['values']['battle_stars']) ? $save['values']['battle_stars'] : array();
        unset($save);

        // Define the flag for whether this is a new user
        $is_new_user = false;

        // Define a flag for if this is a freshly reset game
        $reset_in_progress = !empty($_SESSION[$session_token]['RESET']) ? true : false;

        // Collect this user's ID from the database if not set
        if (!isset($this_user['userid'])){

            // Attempt to collect the user ID from the database
            $temp_query = "SELECT user_id FROM mmrpg_users WHERE user_name_clean = '{$this_user['username_clean']}' LIMIT 1";
            $temp_value = $db->get_value($temp_query, 'user_id');

            // If the user ID was found, collect it and proceed as normal
            if (!empty($temp_value)){
                //echo('!empty($temp_value) = '.$temp_value.';<br /> ');
                //echo('$is_new_user = false;<br /> ');

                // Update the ID in the user array and continue
                $this_user['userid'] = $temp_value;

            }
            // Otherwise, create database rows for this new file
            else {
                //echo('empty($temp_value)<br />');
                //echo('$is_new_user = true;<br /> ');

                // This is a new user so update the flag
                $is_new_user = true;

                // Generate new user, save, and board IDs for this listing
                $temp_user_id = $db->get_value('SELECT MAX(user_id) AS user_id FROM mmrpg_users', 'user_id') + 1;
                $temp_save_id = $db->get_value('SELECT MAX(save_id) AS save_id FROM mmrpg_saves', 'save_id') + 1;
                $temp_board_id = $db->get_value('SELECT MAX(board_id) AS board_id FROM mmrpg_leaderboard', 'board_id') + 1;

                // Generate the USER details for import
                $this_user_array = array();
                $this_user_array['user_id'] = $temp_user_id;
                $this_user_array['role_id'] = isset($this_user['roleid']) ? $this_user['roleid'] : 3;
                $this_user_array['user_name'] = $this_user['username'];
                $this_user_array['user_name_clean'] = $this_user['username_clean'];
                $this_user_array['user_name_public'] = !empty($this_user['displayname']) ? $this_user['displayname'] : '';
                if (!empty($this_user['password_encoded'])){ $this_user_array['user_password_encoded'] = $this_user['password_encoded']; }
                if (!empty($this_user['omega'])){ $this_user_array['user_omega'] = $this_user['omega']; }
                $this_user_array['user_profile_text'] = !empty($this_user['profiletext']) ? $this_user['profiletext'] : '';
                $this_user_array['user_credit_text'] = !empty($this_user['creditstext']) ? $this_user['creditstext'] : '';
                $this_user_array['user_credit_line'] = !empty($this_user['creditsline']) ? $this_user['creditsline'] : '';
                $this_user_array['user_image_path'] = !empty($this_user['imagepath']) ? $this_user['imagepath'] : '';
                $this_user_array['user_background_path'] = !empty($this_user['backgroundpath']) ? $this_user['backgroundpath'] : '';
                $this_user_array['user_colour_token'] = !empty($this_user['colourtoken']) ? $this_user['colourtoken'] : '';
                $this_user_array['user_colour_token2'] = !empty($this_user['colourtoken2']) ? $this_user['colourtoken2'] : '';
                $this_user_array['user_gender'] = !empty($this_user['gender']) ? $this_user['gender'] : '';
                $this_user_array['user_email_address'] = !empty($this_user['emailaddress']) ? $this_user['emailaddress'] : '';
                $this_user_array['user_website_address'] = !empty($this_user['websiteaddress']) ? $this_user['websiteaddress'] : '';
                $this_user_array['user_date_created'] = time();
                $this_user_array['user_date_accessed'] = time();
                $this_user_array['user_date_modified'] = time();
                $this_user_array['user_date_birth'] = !empty($this_user['dateofbirth']) ? $this_user['dateofbirth'] : 0;
                $this_user_array['user_flag_approved'] = 1;

                // Generate the BOARD details for import
                $this_board_array = array();
                $this_board_array['board_id'] = $temp_board_id;
                $this_board_array['user_id'] = $temp_user_id;
                $this_board_array['save_id'] = $temp_save_id;
                $this_board_array['board_points'] = !empty($this_counters['battle_points']) ? $this_counters['battle_points'] : 0;
                $this_board_array['board_robots_count'] = 0;
                $this_board_array['board_robots'] = array();
                $this_board_array['board_stars'] = 0;
                $this_board_array['board_stars_dr_light'] = 0;
                $this_board_array['board_stars_dr_wily'] = 0;
                $this_board_array['board_stars_dr_cossack'] = 0;
                $this_board_array['board_abilities'] = mmrpg_prototype_abilities_unlocked();
                if (!empty($this_values['battle_rewards'])){
                    foreach ($mmrpg_index_players AS $player_token => $player_array){
                        if ($player_token == 'player'){ continue; }
                        $player_reward_array = !empty($this_values['battle_rewards'][$player_token]) ? $this_values['battle_rewards'][$player_token] : array();
                        $player_battles_array = !empty($this_values['battle_complete'][$player_token]) ? $this_values['battle_complete'][$player_token] : array();
                        $player_database_token = str_replace('-', '_', $player_token);
                        if (!empty($player_reward_array)){
                            $this_board_array['board_robots_'.$player_database_token] = array();
                            if (!empty($player_reward_array['player_robots'])){
                                foreach ($player_reward_array['player_robots'] AS $robot_token => $robot_array){
                                    if (!isset($mmrpg_index_robots[$robot_token])){ continue; }
                                    elseif (!mmrpg_prototype_robot_unlocked($player_token, $robot_token)){ continue; }
                                    else { $robot_index = $mmrpg_index_robots[$robot_token]; }
                                    if (empty($robot_index['robot_flag_published'])){ continue; }
                                    elseif (empty($robot_index['robot_flag_complete'])){ continue; }
                                    elseif (empty($robot_index['robot_flag_unlockable'])){ continue; }
                                    $temp_token = $robot_array['robot_token'];
                                    $temp_level = !empty($robot_array['robot_level']) ? $robot_array['robot_level'] : 1;
                                    $temp_robot_info = array('robot_token' => $temp_token, $temp_level);
                                    $this_board_array['board_robots'][] = '['.$temp_token.':'.$temp_level.']';
                                    $this_board_array['board_robots_'.$player_database_token][] = '['.$temp_token.':'.$temp_level.']';
                                }
                            }
                        } else {
                            $this_board_array['board_robots_'.$player_database_token] = array();
                        }
                        $this_board_array['board_robots_'.$player_database_token] = !empty($this_board_array['board_robots_'.$player_database_token]) ? implode(',', $this_board_array['board_robots_'.$player_database_token]) : '';
                    }
                }

                if (!empty($this_stars)){
                    foreach ($this_stars AS $temp_star_token => $temp_star_info){
                        $temp_star_player = str_replace('-', '_', $temp_star_info['star_player']);
                        $this_board_array['board_stars'] += 1;
                        $this_board_array['board_stars_'.$temp_star_player] += 1;
                    }
                }
                $this_board_array['board_robots_count'] = !empty($this_board_array['board_robots']) ? count($this_board_array['board_robots']) : 0;
                $this_board_array['board_robots'] = !empty($this_board_array['board_robots']) ? implode(',', $this_board_array['board_robots']) : '';
                $this_board_array['board_date_created'] = $this_user_array['user_date_created'];
                $this_board_array['board_date_modified'] = $this_user_array['user_date_modified'];

                // Generate the SAVE details for import
                $this_save_array = array();
                if (!empty($this_values['battle_index'])){
                    unset($this_values['battle_index']);
                }
                if (!empty($this_values['battle_complete']) || $reset_in_progress){
                    $this_save_array['save_values_battle_complete'] = json_encode(!empty($this_values['battle_complete']) ? $this_values['battle_complete'] : array());
                    $temp_hash = md5($this_save_array['save_values_battle_complete']);
                    if (isset($this_values['battle_complete_hash']) && $this_values['battle_complete_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_complete']); }
                    unset($this_values['battle_complete'], $this_values['battle_complete_hash']);
                }
                if (!empty($this_values['battle_failure']) || $reset_in_progress){
                    $this_save_array['save_values_battle_failure'] = json_encode(!empty($this_values['battle_failure']) ? $this_values['battle_failure'] : array());
                    $temp_hash = md5($this_save_array['save_values_battle_failure']);
                    if (isset($this_values['battle_failure_hash']) && $this_values['battle_failure_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_failure']); }
                    unset($this_values['battle_failure'], $this_values['battle_failure_hash']);
                }
                if (!empty($this_values['battle_rewards']) || $reset_in_progress){
                    $this_save_array['save_values_battle_rewards'] = json_encode(!empty($this_values['battle_rewards']) ? $this_values['battle_rewards'] : array());
                    $temp_hash = md5($this_save_array['save_values_battle_rewards']);
                    if (isset($this_values['battle_rewards_hash']) && $this_values['battle_rewards_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_rewards']); }
                    unset($this_values['battle_rewards'], $this_values['battle_rewards_hash']);
                }
                if (!empty($this_values['battle_settings']) || $reset_in_progress){
                    $this_save_array['save_values_battle_settings'] = json_encode(!empty($this_values['battle_settings']) ? $this_values['battle_settings'] : array());
                    $temp_hash = md5($this_save_array['save_values_battle_settings']);
                    if (isset($this_values['battle_settings_hash']) && $this_values['battle_settings_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_settings']); }
                    unset($this_values['battle_settings'], $this_values['battle_settings_hash']);
                }
                /*
                if (!empty($this_values['battle_items']) || $reset_in_progress){
                    $this_save_array['save_values_battle_items'] = json_encode(!empty($this_values['battle_items']) ? $this_values['battle_items'] : array());
                    $temp_hash = md5($this_save_array['save_values_battle_items']);
                    if (isset($this_values['battle_items_hash']) && $this_values['battle_items_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_items']); }
                    unset($this_values['battle_items'], $this_values['battle_items_hash']);
                }
                */
                /*
                if (!empty($this_values['battle_abilities'])){
                    $this_save_array['save_values_battle_abilities'] = json_encode(!empty($this_values['battle_abilities']) ? $this_values['battle_abilities'] : array());
                    $temp_hash = md5($this_save_array['save_values_battle_abilities']);
                    if (isset($this_values['battle_abilities_hash']) && $this_values['battle_abilities_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_abilities']); }
                    unset($this_values['battle_abilities'], $this_values['battle_abilities_hash']);
                }
                */
                /*
                if (!empty($this_values['battle_stars']) || $reset_in_progress){
                    $this_save_array['save_values_battle_stars'] = json_encode(!empty($this_values['battle_stars']) ? $this_values['battle_stars'] : array());
                    $temp_hash = md5($this_save_array['save_values_battle_stars']);
                    if (isset($this_values['battle_stars_hash']) && $this_values['battle_stars_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_stars']); }
                    unset($this_values['battle_stars'], $this_values['battle_stars_hash']);
                }
                */
                if (!empty($this_values['robot_alts']) || $reset_in_progress){
                    $this_save_array['save_values_robot_alts'] = json_encode(!empty($this_values['robot_alts']) ? $this_values['robot_alts'] : array());
                    $temp_hash = md5($this_save_array['save_values_robot_alts']);
                    if (isset($this_values['robot_alts_hash']) && $this_values['robot_alts_hash'] == $temp_hash){ unset($this_save_array['save_values_robot_alts']); }
                    unset($this_values['robot_alts'], $this_values['robot_alts_hash']);
                }
                /*
                if (!empty($this_values['robot_database']) || $reset_in_progress){
                    $this_save_array['save_values_robot_database'] = json_encode(!empty($this_values['robot_database']) ? $this_values['robot_database'] : array());
                    $temp_hash = md5($this_save_array['save_values_robot_database']);
                    if (isset($this_values['robot_database_hash']) && $this_values['robot_database_hash'] == $temp_hash){ unset($this_save_array['save_values_robot_database']); }
                    unset($this_values['robot_database'], $this_values['robot_database_hash']);
                }
                */
                $this_save_array['save_id'] = $temp_save_id;
                $this_save_array['user_id'] = $temp_user_id;
                $this_save_array['save_counters'] = json_encode($this_counters);
                $this_save_array['save_values'] = json_encode($this_values);
                $this_save_array['save_flags'] = json_encode($this_flags);
                $this_save_array['save_settings'] = json_encode($this_settings);
                $this_save_array['save_cache_date'] = $this_cache_date;
                $this_save_array['save_date_created'] = $this_user_array['user_date_created'];
                $this_save_array['save_date_accessed'] = $this_user_array['user_date_accessed'];
                $this_save_array['save_date_modified'] = $this_user_array['user_date_modified'];

                // Insert these users into the database
                //echo('<hr /><pre>NEW DB USER/SAVE/BOARD UPDATES ($temp_user_id = '.$temp_user_id.')</pre>');
                //echo('<pre>$this_user_array = '.print_r($this_user_array, true).'</pre>');
                //echo('<pre>$this_save_array = '.print_r($this_save_array, true).'</pre>');
                //echo('<pre>$this_board_array = '.print_r($this_board_array, true).'</pre>');
                $this_user_array_return = $db->insert('mmrpg_users', $this_user_array);
                $this_save_array_return = $db->insert('mmrpg_saves', $this_save_array);
                $this_board_array_return = $db->insert('mmrpg_leaderboard', $this_board_array);

                // Update the ID in the user array and continue
                $this_user['userid'] = $temp_user_id;
                $_SESSION['GAME']['PENDING_LOGIN_ID'] = $temp_user_id;

                // We're done, we should return now
                return true;

            }
        }

        // DEBUG
        $DEBUG = '';

        // Update the user modified and accessed date (everything else is saved via profile settings pages)
        $db->update('mmrpg_users', array(
            'user_date_modified' => time(),
            'user_date_accessed' => time()
            ), 'user_id = '.$this_user['userid']);

        // Define the save database update array and populate
        $this_save_array = array();
        if (!empty($this_values['battle_index']) || $reset_in_progress){
            unset($this_values['battle_index']);
        }
        if (!empty($this_values['battle_complete']) || $reset_in_progress){
            $this_save_array['save_values_battle_complete'] = json_encode(!empty($this_values['battle_complete']) ? $this_values['battle_complete'] : array());
            $temp_hash = md5($this_save_array['save_values_battle_complete']);
            if (isset($this_values['battle_complete_hash']) && $this_values['battle_complete_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_complete']); }
            unset($this_values['battle_complete'], $this_values['battle_complete_hash']);
        }
        if (!empty($this_values['battle_failure']) || $reset_in_progress){
            $this_save_array['save_values_battle_failure'] = json_encode(!empty($this_values['battle_failure']) ? $this_values['battle_failure'] : array());
            $temp_hash = md5($this_save_array['save_values_battle_failure']);
            if (isset($this_values['battle_failure_hash']) && $this_values['battle_failure_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_failure']); }
            unset($this_values['battle_failure'], $this_values['battle_failure_hash']);
        }
        if (!empty($this_values['battle_rewards']) || $reset_in_progress){
            $this_save_array['save_values_battle_rewards'] = json_encode(!empty($this_values['battle_rewards']) ? $this_values['battle_rewards'] : array());
            $temp_hash = md5($this_save_array['save_values_battle_rewards']);
            if (isset($this_values['battle_rewards_hash']) && $this_values['battle_rewards_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_rewards']); }
            unset($this_values['battle_rewards'], $this_values['battle_rewards_hash']);
        }
        if (!empty($this_values['battle_settings']) || $reset_in_progress){
            $this_save_array['save_values_battle_settings'] = json_encode(!empty($this_values['battle_settings']) ? $this_values['battle_settings'] : array());
            $temp_hash = md5($this_save_array['save_values_battle_settings']);
            if (isset($this_values['battle_settings_hash']) && $this_values['battle_settings_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_settings']); }
            unset($this_values['battle_settings'], $this_values['battle_settings_hash']);
        }
        /*
        if (!empty($this_values['battle_items']) || $reset_in_progress){
            $this_save_array['save_values_battle_items'] = json_encode(!empty($this_values['battle_items']) ? $this_values['battle_items'] : array());
            $temp_hash = md5($this_save_array['save_values_battle_items']);
            if (isset($this_values['battle_items_hash']) && $this_values['battle_items_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_items']); }
            unset($this_values['battle_items'], $this_values['battle_items_hash']);
        }
        */
        /*
        if (!empty($this_values['battle_abilities']) || $reset_in_progress){
            $this_save_array['save_values_battle_abilities'] = json_encode(!empty($this_values['battle_abilities']) ? $this_values['battle_abilities'] : array());
            $temp_hash = md5($this_save_array['save_values_battle_abilities']);
            if (isset($this_values['battle_abilities_hash']) && $this_values['battle_abilities_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_abilities']); }
            unset($this_values['battle_abilities'], $this_values['battle_abilities_hash']);
        }
        */
        /*
        if (!empty($this_values['battle_stars']) || $reset_in_progress){
            $this_save_array['save_values_battle_stars'] = json_encode(!empty($this_values['battle_stars']) ? $this_values['battle_stars'] : array());
            $temp_hash = md5($this_save_array['save_values_battle_stars']);
            if (isset($this_values['battle_stars_hash']) && $this_values['battle_stars_hash'] == $temp_hash){ unset($this_save_array['save_values_battle_stars']); }
            unset($this_values['battle_stars'], $this_values['battle_stars_hash']);
        }
        */
        if (!empty($this_values['robot_alts']) || $reset_in_progress){
            $this_save_array['save_values_robot_alts'] = json_encode(!empty($this_values['robot_alts']) ? $this_values['robot_alts'] : array());
            $temp_hash = md5($this_save_array['save_values_robot_alts']);
            if (isset($this_values['robot_alts_hash']) && $this_values['robot_alts_hash'] == $temp_hash){ unset($this_save_array['save_values_robot_alts']); }
            unset($this_values['robot_alts'], $this_values['robot_alts_hash']);
        }
        /*
        if (!empty($this_values['robot_database']) || $reset_in_progress){
            $this_save_array['save_values_robot_database'] = json_encode(!empty($this_values['robot_database']) ? $this_values['robot_database'] : array());
            $temp_hash = md5($this_save_array['save_values_robot_database']);
            if (isset($this_values['robot_database_hash']) && $this_values['robot_database_hash'] == $temp_hash){ unset($this_save_array['save_values_robot_database']); }
            unset($this_values['robot_database'], $this_values['robot_database_hash']);
        }
        */

        $this_save_array['save_counters'] = json_encode($this_counters);
        $this_save_array['save_values'] = json_encode($this_values);
        $this_save_array['save_flags'] = json_encode($this_flags);
        $this_save_array['save_settings'] = json_encode($this_settings);
        $this_save_array['save_cache_date'] = $this_cache_date;
        $this_save_array['save_date_modified'] = time();

        // Update this save's info in the database
        //echo('<hr /><pre>FINAL DB SAVES UPDATE (user_id = '.$this_user['userid'].')</pre>');
        //echo('<pre>$this_save_array = '.print_r($this_save_array, true).'</pre>');
        $db->update('mmrpg_saves', $this_save_array, 'user_id = '.$this_user['userid']);

        // If the robot database records were not empty, we should update them in the database table
        if (!empty($_SESSION[$session_token]['values']['robot_database'])){
            $user_robot_records = $_SESSION[$session_token]['values']['robot_database'];
            rpg_user::update_robot_records($this_user['userid'], $user_robot_records);
        }

        // If the unlocked item list was not empty, we should update them in the database table
        if (!empty($_SESSION[$session_token]['values']['battle_items'])){
            $user_unlocked_items = $_SESSION[$session_token]['values']['battle_items'];
            rpg_user::update_unlocked_items($this_user['userid'], $user_unlocked_items);
        }

        // If the unlocked ability list was not empty, we should update them in the database table
        if (!empty($_SESSION[$session_token]['values']['battle_abilities'])){
            $user_unlocked_abilities = $_SESSION[$session_token]['values']['battle_abilities'];
            rpg_user::update_unlocked_abilities($this_user['userid'], $user_unlocked_abilities);
        }

        // If the unlocked star list was not empty, we should update them in the database table
        if (!empty($_SESSION[$session_token]['values']['battle_stars'])){
            $user_unlocked_stars = $_SESSION[$session_token]['values']['battle_stars'];
            rpg_user::update_unlocked_stars($this_user['userid'], $user_unlocked_stars);
        }


        // -- UPDATE LEADERBOARD RANKINGS -- //

        // Call the global battle points function to collect progress details
        mmrpg_prototype_calculate_battle_points_2k19($this_user['userid'], $battle_points_index);
        //error_log('<pre>$battle_points_index : '.print_r($battle_points_index, true).'</pre>');

        // Define the tokens for updating legacy player fields to ZERO
        $legacy_player_field_tokens = array('dr_light', 'dr_wily', 'dr_cossack');

        // Define the board database update array and populate
        $this_board_array = array();
        $this_board_array['board_points'] = $battle_points_index['total_battle_points'];
            foreach ($legacy_player_field_tokens AS $ptoken){ $this_board_array['board_points_'.$ptoken] = 0; }
        $this_board_array['board_robots'] = implode(',', $battle_points_index['robots_unlocked']);
        $this_board_array['board_robots_count'] = count($battle_points_index['robots_unlocked']);
            foreach ($legacy_player_field_tokens AS $ptoken){ $this_board_array['board_robots_'.$ptoken] = ''; }
        $this_board_array['board_abilities'] = count($battle_points_index['abilities_unlocked']);
            foreach ($legacy_player_field_tokens AS $ptoken){ $this_board_array['board_abilities_'.$ptoken] = 0; }
        $this_board_array['board_stars'] = count($battle_points_index['field_stars_collected']) + count($battle_points_index['fusion_stars_collected']);
            foreach ($legacy_player_field_tokens AS $ptoken){ $this_board_array['board_stars_'.$ptoken] = 0; }
        $this_board_array['board_items'] = count($battle_points_index['items_unlocked']);
        $this_board_array['board_battles'] = 0;
            foreach ($legacy_player_field_tokens AS $ptoken){ $this_board_array['board_battles_'.$ptoken] = 0; }
        $this_board_array['board_missions'] = 0;
            foreach ($legacy_player_field_tokens AS $ptoken){ $this_board_array['board_missions_'.$ptoken] = 0; }
        $this_board_array['board_awards'] = !empty($this_values['prototype_awards']) ? array_keys($this_values['prototype_awards']) : '';
        $this_board_array['board_awards'] = !empty($this_board_array['board_awards']) ? implode(',', $this_board_array['board_awards']) : '';
        $this_board_array['board_date_modified'] = time();

        // Update this board's info in the database
        //error_log('<pre>$this_board_array : '.print_r($this_board_array, true).'</pre>');
        $db->update('mmrpg_leaderboard', $this_board_array, 'user_id = '.$this_user['userid']);

        // Clear any leaderboard data that exists in the session, forcing it to recache
        if (isset($_SESSION[$session_token]['BOARD']['boardrank'])){ unset($_SESSION[$session_token]['BOARD']['boardrank']); }

    }

    // Unset the reset flag in the session
    unset($_SESSION[$session_token]['RESET']);

    //echo('GAME has been saved!');
    //exit();

    // Return true on success
    return true;

}
?>