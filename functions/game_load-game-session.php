<?
// Define a function for loading the game session
function mmrpg_load_game_session(){

    // Reference global variables
    global $db;
    $session_token = mmrpg_game_token();

    // Do NOT load, save, or otherwise alter the game file while viewing remote
    if (defined('MMRPG_REMOTE_GAME')){ return true; }

    // Ensure we attempt patches at least once
    unset($_SESSION['PATCHES']);

    // Clear the community thread tracker
    $_SESSION['COMMUNITY']['threads_viewed'] = array();

    // Collect the pending login details if set
    $login_user_id = 0;
    if (!empty($_SESSION[$session_token]['PENDING_LOGIN_ID'])){
        $login_user_id = $_SESSION[$session_token]['PENDING_LOGIN_ID'];
    } elseif (!empty($_SESSION[$session_token]['USER']['userid'])){
        $login_user_id = $_SESSION[$session_token]['USER']['userid'];
    }

    // If this is NOT demo mode, load from database
    $is_demo_mode = rpg_game::is_demo();
    if (!$is_demo_mode && !empty($login_user_id)){

        // Define a function for replacing legacy strings (names) in save data
        $temp_replace_legacy_strings = function($raw_json_string){
            $new_json_string = $raw_json_string;
            // Legacy ABILITY string replacements
            $new_json_string = str_replace('"repair-mode"', '"energy-mode"', $new_json_string);
            // Legacy ITEM string replacements
            $new_json_string = str_replace('"locking-module"', '"guard-module"', $new_json_string);
            // Legacy FIELD string replacements
            $new_json_string = str_replace('"lightning-control"', '"lighting-control"', $new_json_string);
            // Return the cleaned string
            return $new_json_string;
            };

        // LOAD DATABASE INFO

        // Collect the user and save info from the database

        $this_database_save = $db->get_array("SELECT * FROM mmrpg_saves WHERE user_id = {$login_user_id} LIMIT 1");
        if (empty($this_database_save)){ die('could not load save for file '.$temp_matches[2].' and path '.$temp_matches[1].' on line '.__LINE__); }

        $temp_user_fields = rpg_user::get_index_fields(true, 'users');
        $temp_user_role_fields = rpg_user_role::get_index_fields(true, 'roles');
        $this_database_user = $db->get_array("SELECT {$temp_user_fields}, {$temp_user_role_fields} FROM mmrpg_users AS users LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id WHERE users.user_id = '{$login_user_id}' LIMIT 1");
        if (empty($this_database_user)){ die('could not load user for '.$this_database_save['user_id'].' on line '.__LINE__); }


        // Update the game session with database extracted variables
        $new_game_data = array();

        $new_game_data['CACHE_DATE'] = $this_database_save['save_cache_date'];

        $new_game_data['USER'] = mmrpg_prototype_format_user_data_for_session($this_database_user);

        rpg_user::pull_save_counters($login_user_id, $user_save_counters);
        if (!empty($user_save_counters)){
            $new_game_data['counters'] = $user_save_counters;
        } else {
            // Legacy support / remove once new methods confirmed working
            $new_game_data['counters'] = !empty($this_database_save['save_counters']) ? json_decode($this_database_save['save_counters'], true) : array();
        }

        $new_game_data['values'] = !empty($this_database_save['save_values']) ? json_decode($this_database_save['save_values'], true) : array();

        if (!isset($this_database_save['save_values_battle_index'])){
            $new_game_data['values']['battle_index'] = array();
        }

        if (!empty($this_database_save['save_values_battle_complete'])){
            $new_game_data['values']['battle_complete'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_battle_complete']), true);
            $new_game_data['values']['battle_complete_hash'] = md5($this_database_save['save_values_battle_complete']);
        }

        if (!empty($this_database_save['save_values_battle_failure'])){
            $new_game_data['values']['battle_failure'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_battle_failure']), true);
            $new_game_data['values']['battle_failure_hash'] = md5($this_database_save['save_values_battle_failure']);
        }

        if (!empty($this_database_save['save_values_battle_rewards'])){
            $new_game_data['values']['battle_rewards'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_battle_rewards']), true);
            $new_game_data['values']['battle_rewards_hash'] = md5($this_database_save['save_values_battle_rewards']);
        }

        if (!empty($this_database_save['save_values_battle_settings'])){
            $new_game_data['values']['battle_settings'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_battle_settings']), true);
            $new_game_data['values']['battle_settings_hash'] = md5($this_database_save['save_values_battle_settings']);
        }

        rpg_user::pull_unlocked_items($login_user_id, $user_unlocked_items);
        if (!empty($user_unlocked_items)){
            $new_game_data['values']['battle_items'] = $user_unlocked_items;
            $new_game_data['values']['battle_items_hash'] = md5(json_encode($user_unlocked_items));
        } elseif (!empty($this_database_save['save_values_battle_items'])){
            // Legacy support / remove once new methods confirmed working
            $new_game_data['values']['battle_items'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_battle_items']), true);
            $new_game_data['values']['battle_items_hash'] = md5($this_database_save['save_values_battle_items']);
        }

        rpg_user::pull_unlocked_abilities($login_user_id, $user_unlocked_abilities);
        if (!empty($user_unlocked_abilities)){
            $new_game_data['values']['battle_abilities'] = $user_unlocked_abilities;
            $new_game_data['values']['battle_abilities_hash'] = md5(json_encode($user_unlocked_abilities));
        } elseif (!empty($this_database_save['save_values_battle_abilities'])){
            // Legacy support / remove once new methods confirmed working
            $new_game_data['values']['battle_abilities'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_battle_abilities']), true);
            $new_game_data['values']['battle_abilities_hash'] = md5($this_database_save['save_values_battle_abilities']);
        }

        rpg_user::pull_unlocked_stars($login_user_id, $user_unlocked_stars);
        if (!empty($user_unlocked_stars)){
            $new_game_data['values']['battle_stars'] = $user_unlocked_stars;
            $new_game_data['values']['battle_stars_hash'] = md5(json_encode($user_unlocked_stars));
        } elseif (!empty($this_database_save['save_values_battle_stars'])){
            // Legacy support / remove once new methods confirmed working
            $new_game_data['values']['battle_stars'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_battle_stars']), true);
            $new_game_data['values']['battle_stars_hash'] = md5($this_database_save['save_values_battle_stars']);
        }

        if (!empty($this_database_save['save_values_robot_alts'])){
            $new_game_data['values']['robot_alts'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_robot_alts']), true);
            $new_game_data['values']['robot_alts_hash'] = md5($this_database_save['save_values_robot_alts']);
        }

        rpg_user::pull_robot_records($login_user_id, $user_robot_records);
        if (!empty($user_robot_records)){
            $new_game_data['values']['robot_database'] = $user_robot_records;
            $new_game_data['values']['robot_database_hash'] = md5(json_encode($user_robot_records));
        } elseif (!empty($this_database_save['save_values_robot_database'])){
            // Legacy support / remove once new methods confirmed working
            $new_game_data['values']['robot_database'] = json_decode($temp_replace_legacy_strings($this_database_save['save_values_robot_database']), true);
            $new_game_data['values']['robot_database_hash'] = md5($this_database_save['save_values_robot_database']);
        }

        $new_game_data['flags'] = !empty($this_database_save['save_flags']) ? json_decode($this_database_save['save_flags'], true) : array();

        $new_game_data['battle_settings'] = !empty($this_database_save['save_settings']) ? json_decode($this_database_save['save_settings'], true) : array();

        // Update the session with the new save info
        $_SESSION[$session_token] = array_merge($_SESSION[$session_token], $new_game_data);
        unset($new_game_data);

        // Unset the player selection to restart at the player select screen
        if (mmrpg_prototype_players_unlocked() > 1){ $_SESSION[$session_token]['battle_settings']['this_player_token'] = false; }

        // Expand user's current IP list, then add a new entry and filter unique
        $local_ips = array('0.0.0.0', '127.0.0.1');
        $ip_list = !empty($this_database_user['user_ip_addresses']) ? $this_database_user['user_ip_addresses'] : '';
        $ip_list = strstr($ip_list, ',') ? explode(',', $ip_list) : array($ip_list);
        $ip_list = array_filter(array_map('trim', $ip_list));
        $ip_list[] = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){ $ip_list[] = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])); }
        foreach ($ip_list AS $k => $ip){ if (empty($ip) || in_array($ip, $local_ips)){ unset($ip_list[$k]); } }
        $ip_list = array_unique($ip_list);

        // Update the user table in the database if not done already
        if (empty($_SESSION[$session_token]['DEMO'])){
            $db->update('mmrpg_users', array(
                'user_ip_addresses' => implode(',', $ip_list)
                ), "user_id = {$this_database_user['user_id']}");
        }

        /*
        // Update the user table in the database if not done already
        if (empty($_SESSION[$session_token]['DEMO'])){
            $db->update('mmrpg_users', array(
                'user_last_login' => time(),
                'user_backup_login' => $this_database_user['user_last_login'],
                'user_ip_addresses' => implode(',', $ip_list)
                ), "user_id = {$this_database_user['user_id']}");
        }
        */

        // Clear the pending login ID
        unset($_SESSION[$session_token]['PENDING_LOGIN_ID']);

    }

    // Update the last saved value
    $_SESSION[$session_token]['values']['last_load'] = time();

    // Return true on success
    return true;

}
?>