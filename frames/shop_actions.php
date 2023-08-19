<?

// -- PROCESS SHOP SELL ACTION -- //

// Check if an action request has been sent with an sell type
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'sell'){

    // Collect the action variables from the request header, if they exist
    $temp_shop = !empty($_REQUEST['shop']) ? $_REQUEST['shop'] : '';
    $temp_kind = !empty($_REQUEST['kind']) ? $_REQUEST['kind'] : '';
    $temp_action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
    $temp_token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : '';
    $temp_quantity = !empty($_REQUEST['quantity']) ? $_REQUEST['quantity'] : 0;
    $temp_price = !empty($_REQUEST['price']) ? $_REQUEST['price'] : 0;

    // If key variables are not provided, kill the script in error
    if (empty($temp_shop)){ die('error|request-error|shop-missing'); }
    if (empty($temp_kind)){ die('error|request-error|kind-missing'); }
    elseif (empty($temp_action)){ die('error|request-error|action-missing'); }
    elseif (empty($temp_token)){ die('error|request-error|token-missing'); }
    elseif (empty($temp_quantity)){ die('error|request-error|quantity-missing'); }
    elseif (empty($temp_price)){ die('error|request-error|price-missing'); }

    // Check if this is an ITEM based action
    if ($temp_kind == 'item'){
        // Ensure this item exists before continuing
        if (isset($_SESSION[$session_token]['values']['battle_items'][$temp_token])){
            // Collect a reference to the session variable amount
            $temp_is_shard = preg_match('/-shard$/i', $temp_token) ? true : false;
            $temp_is_core = preg_match('/-core$/i', $temp_token) ? true : false;
            if ($temp_is_shard){ $temp_max_quantity = MMRPG_SETTINGS_SHARDS_MAXQUANTITY; }
            elseif ($temp_is_core){ $temp_max_quantity = MMRPG_SETTINGS_CORES_MAXQUANTITY; }
            else { $temp_max_quantity = MMRPG_SETTINGS_ITEMS_MAXQUANTITY;  }
            $temp_current_quantity = $_SESSION[$session_token]['values']['battle_items'][$temp_token];

            // Now make sure we actually have enough of this item to sell
            if ($temp_quantity <= $temp_current_quantity){
                // Remove this item's count from the global variable and recollect
                $_SESSION[$session_token]['values']['battle_items'][$temp_token] = $temp_current_quantity - $temp_quantity;
                $temp_current_quantity = $_SESSION[$session_token]['values']['battle_items'][$temp_token];

                // Increment the player's zenny count based on the provided price
                $_SESSION[$session_token]['counters']['battle_zenny'] = $global_zenny_counter + $temp_price;
                $global_zenny_counter = $_SESSION[$session_token]['counters']['battle_zenny'];

                // Update the shop history with the item bought by the shop keeper
                if ($temp_is_core){
                    if (!isset($_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['cores_bought'][$temp_token])){ $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['cores_bought'][$temp_token] = 0; }
                    $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['cores_bought'][$temp_token] += $temp_quantity;
                } else {
                    if (!isset($_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['items_bought'][$temp_token])){ $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['items_bought'][$temp_token] = 0; }
                    $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['items_bought'][$temp_token] += $temp_quantity;
                }
                $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['zenny_spent'] += $temp_price;
                $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['shop_experience'] += $temp_price + (($temp_quantity - 1) * ($temp_quantity - 1));

                // Refresh battle points and ranking so we can return updated score
                mmrpg_prototype_refresh_battle_points();
                $new_battle_points = $_SESSION[$session_token]['counters']['battle_points'];
                $new_board_rank = $_SESSION[$session_token]['BOARD']['boardrank'];

                // Save, produce the success message with the new field order
                exit('success|item-sold|'.$temp_current_quantity.'|'.$global_zenny_counter.'|points:'.$new_battle_points.'|rank:'.mmrpg_number_suffix($new_board_rank));

            }
            // Otherwise if the user requested more than they have
            else {

                // Print an error message and kill the script
                exit('error|insufficient-quantity|'.$temp_quantity);

            }

        }
        // Otherwise if this item does not exist
        else {

            // Print an error message and kill the script
            exit('error|invalid-item|'.$temp_token);

        }

    }
    // Check if this is an STAR based action
    elseif ($temp_kind == 'star'){
        // Collect the actual star token from the provided one
        $temp_actual_token = preg_replace('/^star-/i', '', $temp_token);

        // Ensure this star exists before continuing
        if (isset($_SESSION[$session_token]['values']['battle_stars'][$temp_actual_token])){

            // Add this star's token to the daily list of shown stars
            $temp_session_key = 'star_list_array_raw';
            $_SESSION[$session_token]['SHOP'][$temp_session_key]['shown']['star-'.$temp_actual_token] = true;
            $temp_current_quantity = 0;

            // Increment the player's zenny count based on the provided price
            $_SESSION[$session_token]['counters']['battle_zenny'] = $global_zenny_counter + $temp_price;
            $global_zenny_counter = $_SESSION[$session_token]['counters']['battle_zenny'];

            // Update the shop history with the star bought by the shop keeper
            if (!isset($_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['stars_bought'][$temp_token])){ $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['stars_bought'][$temp_token] = 0; }
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['stars_bought'][$temp_token] += 1;
            $temp_new_quantity = $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['stars_bought'][$temp_token];
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['zenny_spent'] += $temp_price;
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['shop_experience'] += $temp_price - (($temp_new_quantity - 1) * ($temp_new_quantity - 1));

            // Refresh battle points and ranking so we can return updated score
            mmrpg_prototype_refresh_battle_points();
            $new_battle_points = $_SESSION[$session_token]['counters']['battle_points'];
            $new_board_rank = $_SESSION[$session_token]['BOARD']['boardrank'];

            // Save, produce the success message with the new field order
            exit('success|star-shown|'.$temp_current_quantity.'|'.$global_zenny_counter.'|points:'.$new_battle_points.'|rank:'.mmrpg_number_suffix($new_board_rank));

        }
        // Otherwise if this star does not exist
        else {

            // Print an error message and kill the script
            exit('error|invalid-star|'.$temp_actual_token);

        }

    }
    // Otherwise if undefined kind
    else {

        // Print an error message and kill the script
        exit('error|invalid-kind|'.$temp_kind);

    }

}


// -- PROCESS SHOP BUY ACTION -- //

// Check if an action request has been sent with an buy type
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'buy'){

    // Collect the action variables from the request header, if they exist
    $temp_shop = !empty($_REQUEST['shop']) ? $_REQUEST['shop'] : '';
    $temp_kind = !empty($_REQUEST['kind']) ? $_REQUEST['kind'] : '';
    $temp_action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
    $temp_token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : '';
    $temp_quantity = !empty($_REQUEST['quantity']) ? (int)($_REQUEST['quantity']) : 0;
    $temp_price = !empty($_REQUEST['price']) ? (int)($_REQUEST['price']) : 0;
    $temp_player = !empty($_REQUEST['player']) ? $_REQUEST['player'] : '';

    // If key variables are not provided, kill the script in error
    if (empty($temp_shop)){ die('error|request-error|shop-missing'); }
    if (empty($temp_kind)){ die('error|request-error|kind-missing'); }
    elseif (empty($temp_action)){ die('error|request-error|action-missing'); }
    elseif (empty($temp_token)){ die('error|request-error|token-missing'); }
    elseif (empty($temp_quantity)){ die('error|request-error|quantity-missing'); }
    elseif (empty($temp_price)){ die('error|request-error|price-missing'); }
    elseif ($temp_kind == 'ability' && empty($temp_player)){ die('error|request-error|player-missing'); }

    // Check if this is an ITEM based action
    if ($temp_kind == 'item'){

        // Ensure this item exists before continuing
        if (isset($mmrpg_database_items[$temp_token])){
            // Collect a reference to the session variable amount
            $temp_is_shard = preg_match('/-shard$/i', $temp_token) ? true : false;
            $temp_is_core = preg_match('/-core$/i', $temp_token) ? true : false;
            if ($temp_is_shard){ $temp_max_quantity = MMRPG_SETTINGS_SHARDS_MAXQUANTITY; }
            elseif ($temp_is_core){ $temp_max_quantity = MMRPG_SETTINGS_CORES_MAXQUANTITY; }
            else { $temp_max_quantity = MMRPG_SETTINGS_ITEMS_MAXQUANTITY;  }
            $temp_current_quantity = !empty($_SESSION[$session_token]['values']['battle_items'][$temp_token]) ? $_SESSION[$session_token]['values']['battle_items'][$temp_token] : 0;

            // Now make sure we actually have enough of this item to buy
            if (($temp_current_quantity + $temp_quantity) <= $temp_max_quantity){
                // Remove this item's count from the global variable and recollect
                $_SESSION[$session_token]['values']['battle_items'][$temp_token] = $temp_current_quantity + $temp_quantity;
                $temp_current_quantity = $_SESSION[$session_token]['values']['battle_items'][$temp_token];

                // Decrement the player's zenny count based on the provided price
                $_SESSION[$session_token]['counters']['battle_zenny'] = $global_zenny_counter - $temp_price;
                $global_zenny_counter = $_SESSION[$session_token]['counters']['battle_zenny'];

                // Update the shop history with this sold item under the given character
                if (!isset($_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['items_sold'][$temp_token])){ $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['items_sold'][$temp_token] = 0; }
                $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['items_sold'][$temp_token] += $temp_quantity;
                $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['zenny_earned'] += $temp_price;
                $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['shop_experience'] += $temp_price + (($temp_quantity - 1) * ($temp_quantity - 1));

                // Refresh battle points and ranking so we can return updated score
                mmrpg_prototype_refresh_battle_points();
                $new_battle_points = $_SESSION[$session_token]['counters']['battle_points'];
                $new_board_rank = $_SESSION[$session_token]['BOARD']['boardrank'];

                /*
                // DEBUG DEBUG DEBUG
                if ($temp_token === 'yashichi' && $temp_quantity === 7){
                    $delete_robots = array('acid-man', 'bounce-man', 'oil-man');
                    $possible_players = array('dr-light', 'dr-wily', 'dr-cossack');
                    foreach ($delete_robots as $delete_robot){
                        error_log('destroy '.$delete_robot.' in the player data for unlock testing!');
                        unset($_SESSION[$session_token]['values']['robot_database'][$delete_robot]);
                        foreach ($possible_players as $possible_player){
                            unset($_SESSION[$session_token]['values']['battle_rewards'][$possible_player]['player_robots'][$delete_robot]);
                            unset($_SESSION[$session_token]['values']['battle_settings'][$possible_player]['player_robots'][$delete_robot]);
                        }
                    }
                }
                */

                // Save, produce the success message with the new field order
                exit('success|item-bought|'.$temp_current_quantity.'|'.$global_zenny_counter.'|points:'.$new_battle_points.'|rank:'.mmrpg_number_suffix($new_board_rank));

            }
            // Otherwise if the user requested more than they have
            else {

                // Print an error message and kill the script
                exit('error|overkill-quantity|'.$temp_quantity);

            }

        }
        // Otherwise if this item does not exist
        else {

            // Print an error message and kill the script
            exit('error|invalid-item|'.$temp_token);

        }

    }
    // Check if this is an ABILITY based action
    elseif ($temp_kind == 'ability'){

        // Ensure this ability exists before continuing
        if (isset($mmrpg_database_abilities[$temp_token])){
            // Ensure the requested ability token was valid
            if (!empty($mmrpg_database_abilities[$temp_token])){

                // Collect the current ability's info from the database
                $ability_info = array('ability_token' => $temp_token);

                // Unlock this ability for all playable characters
                mmrpg_game_unlock_ability(false, false, $ability_info, true);
                $temp_current_quantity = 1;

                // If the unlock was successful
                if (mmrpg_game_ability_unlocked('', '', $temp_token)){

                    // Decrement the player's zenny count based on the provided price
                    $_SESSION[$session_token]['counters']['battle_zenny'] = $global_zenny_counter - $temp_price;
                    $global_zenny_counter = $_SESSION[$session_token]['counters']['battle_zenny'];

                    // Update the shop history with this sold item under the given character
                    if (!isset($_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['abilities_sold'][$temp_token])){ $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['abilities_sold'][$temp_token] = 0; }
                    $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['abilities_sold'][$temp_token] += 1;
                    $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['zenny_earned'] += $temp_price;
                    $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['shop_experience'] += $temp_price;

                    // Refresh battle points and ranking so we can return updated score
                    mmrpg_prototype_refresh_battle_points();
                    $new_battle_points = $_SESSION[$session_token]['counters']['battle_points'];
                    $new_board_rank = $_SESSION[$session_token]['BOARD']['boardrank'];

                    // Save, produce the success message with the new ability order
                    exit('success|ability-purchased|'.$temp_current_quantity.'|'.$global_zenny_counter.'|points:'.$new_battle_points.'|rank:'.mmrpg_number_suffix($new_board_rank));

                }
                // Otherwise, if the ability was not unlocked for some reason
                else {

                    // Print an error message and kill the script
                    exit('error|unlock-error|'.$temp_token);

                }

            }
            // Otherwise, produce an error
            else {

                // Print an error message and kill the script
                exit('error|invalid-player|'.$temp_token);

            }

        }
        // Otherwise if this star does not exist
        else {

            // Print an error message and kill the script
            exit('error|invalid-ability|'.$temp_token);

        }

    }
    // Check if this is an ROBOT based action
    elseif ($temp_kind == 'robot'){

        // Collect the actual robot token from the provided one
        $temp_actual_token = preg_replace('/^robot-/i', '', $temp_token);

        // Ensure this robot exists before continuing
        $unlock_robot_info = rpg_robot::get_index_info($temp_actual_token);
        if (!empty($unlock_robot_info)
            && !empty($unlock_robot_info['robot_flag_unlockable'])){

            // Hard-code this robot's player if from specific games
            if ($unlock_robot_info['robot_game'] == 'MM1'){

                // All MM1 robot masters go to Dr. Light
                $unlock_player_token = 'dr-light';

            } elseif ($unlock_robot_info['robot_game'] == 'MM2'){

                // All MM2 robot masters go to Dr. Wily
                $unlock_player_token = 'dr-wily';

            } elseif ($unlock_robot_info['robot_game'] == 'MM4'){

                // All MM4 robot masters go to Dr. Cossack
                $unlock_player_token = 'dr-cossack';

            } else {

                // All other robots have their Dr. based on best stat
                $this_best_stat_value = 0;
                $this_best_stat_kind = '';
                $stat_kinds = array('attack', 'defense', 'speed', 'energy');
                foreach ($stat_kinds AS $kind){
                    if ($unlock_robot_info['robot_'.$kind] > $this_best_stat_value){
                        $this_best_stat_value = $unlock_robot_info['robot_'.$kind];
                        $this_best_stat_kind = $kind;
                    }
                }
                if ($this_best_stat_kind === 'attack'){ $unlock_player_token = 'dr-wily'; }
                elseif ($this_best_stat_kind === 'defense'){ $unlock_player_token = 'dr-light'; }
                elseif ($this_best_stat_kind === 'speed'){ $unlock_player_token = 'dr-cossack'; }
                //elseif ($this_best_stat_kind === 'energy'){ $unlock_player_token = 'dr-lalinde'; }
                else { $unlock_player_token = 'dr-light'; } // default

            }

            // Manually unlock this robot using the predefined global function
            $temp_current_quantity = 1;
            if (!mmrpg_prototype_robot_unlocked(false, $temp_actual_token)){
                // Unlock the robot as a playable character
                //error_log('$temp_actual_token = '.print_r($temp_actual_token, true));
                $unlock_player_info = $mmrpg_database_players[$unlock_player_token];
                $unlock_robot_info = rpg_robot::get_index_info($temp_actual_token);
                $unlock_robot_info['robot_level'] = 1;
                $unlock_robot_info['robot_experience'] = 999;
                if (!empty($_SESSION[$session_token]['values']['robot_database'])
                    && !empty($_SESSION[$session_token]['values']['robot_database'][$temp_actual_token])){
                    $unlock_robot_records = $_SESSION[$session_token]['values']['robot_database'][$temp_actual_token];
                    //error_log('$unlock_robot_records = '.print_r($unlock_robot_records, true));
                    if (!empty($unlock_robot_records['robot_defeated'])){ $unlock_robot_info['robot_level'] += $unlock_robot_records['robot_defeated']; }
                    if ($unlock_robot_info['robot_level'] >= 100){ $unlock_robot_info['robot_level'] = 99; }
                }
                //error_log('$unlock_robot_info = '.print_r($unlock_robot_info, true));
                mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, true);
            }

            // Decrement the player's zenny count based on the provided price
            $_SESSION[$session_token]['counters']['battle_zenny'] = $global_zenny_counter - $temp_price;
            $global_zenny_counter = $_SESSION[$session_token]['counters']['battle_zenny'];

            // Update the shop history with this sold item under the given character
            if (!isset($_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['robots_sold'][$temp_token])){ $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['robots_sold'][$temp_token] = 0; }
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['robots_sold'][$temp_token] += 1;
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['zenny_earned'] += $temp_price;
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['shop_experience'] += $temp_price;

            // Refresh battle points and ranking so we can return updated score
            mmrpg_prototype_refresh_battle_points();
            $new_battle_points = $_SESSION[$session_token]['counters']['battle_points'];
            $new_board_rank = $_SESSION[$session_token]['BOARD']['boardrank'];

            // Save, produce the success message with the new robot order
            exit('success|robot-purchased|'.$temp_current_quantity.'|'.$global_zenny_counter.'|points:'.$new_battle_points.'|rank:'.mmrpg_number_suffix($new_board_rank));

        }
        // Otherwise if this robot does not exist
        else {

            // Print an error message and kill the script
            exit('error|invalid-robot|'.$temp_actual_token);

        }

    }
    // Check if this is an ALT based action
    elseif ($temp_kind == 'alt'){
        // Collect the actual alt token from the provided one
        $temp_actual_token = preg_replace('/^alt-/i', '', $temp_token);
        list($temp_robot_token, $temp_alt_token) = explode('_', $temp_actual_token);

        // Ensure this alt's robot exists before continuing
        if (isset($mmrpg_database_robots[$temp_robot_token])){

            // Remove this alts's entry from the global arrayand define the new quantity
            $temp_unlocked_alts = !empty($_SESSION[$session_token]['values']['robot_alts']) ? $_SESSION[$session_token]['values']['robot_alts'] : array();
            $temp_unlocked_alts[$temp_robot_token][] = $temp_alt_token;
            $temp_unlocked_alts[$temp_robot_token] = array_unique($temp_unlocked_alts[$temp_robot_token]);
            $_SESSION[$session_token]['values']['robot_alts'] = $temp_unlocked_alts;
            $temp_current_quantity = 1;

            // Decrement the player's zenny count based on the provided price
            $_SESSION[$session_token]['counters']['battle_zenny'] = $global_zenny_counter - $temp_price;
            $global_zenny_counter = $_SESSION[$session_token]['counters']['battle_zenny'];

            // Update the shop history with this sold item under the given character
            if (!isset($_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['alts_sold'][$temp_token])){ $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['alts_sold'][$temp_token] = 0; }
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['alts_sold'][$temp_token] += 1;
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['zenny_earned'] += $temp_price;
            $_SESSION[$session_token]['values']['battle_shops'][$temp_shop]['shop_experience'] += $temp_price;

            // Refresh battle points and ranking so we can return updated score
            mmrpg_prototype_refresh_battle_points();
            $new_battle_points = $_SESSION[$session_token]['counters']['battle_points'];
            $new_board_rank = $_SESSION[$session_token]['BOARD']['boardrank'];

            // Save, produce the success message with the new alt order
            exit('success|alt-purchased|'.$temp_current_quantity.'|'.$global_zenny_counter.'|points:'.$new_battle_points.'|rank:'.mmrpg_number_suffix($new_board_rank));

        }
        // Otherwise if this star does not exist
        else {

            // Print an error message and kill the script
            exit('error|invalid-alt|'.$temp_actual_token);

        }

    }
    // Otherwise if undefined kind
    else {

        // Print an error message and kill the script
        exit('error|invalid-kind|'.$temp_kind);

    }

}

?>