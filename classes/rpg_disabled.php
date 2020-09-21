<?
/**
 * Mega Man RPG Disabled
 * <p>The battle disabled class for the Mega Man RPG Prototype.</p>
 */
class rpg_disabled {

    // Define a trigger for processing disabled events from abilities or items
    public static function trigger_robot_disabled($this_robot, $target_robot, $trigger_options = array()){

        // Pull in the global variable
        global $db;
        global $mmrpg_index_players;
        if (empty($mmrpg_index_players)){ $mmrpg_index_players = rpg_player::get_index(true); }

        // Generate default trigger options if not set
        if (!isset($trigger_options['item_multiplier'])){ $trigger_options['item_multiplier'] = 1.0; }
        if (!isset($trigger_options['item_quantity_min'])){ $trigger_options['item_quantity_min'] = 1; }
        if (!isset($trigger_options['item_quantity_max'])){ $trigger_options['item_quantity_max'] = 3; }

        // Create references to save time 'cause I'm tired
        // (rather than replace all target references to this references)
        $this_battle = $this_robot->battle;
        $this_player = $this_robot->player; // the player of the robot being disabled
        $target_player = $target_robot->player; // the player of the other robot
        $target_robot = $target_robot; // the other robot that isn't this one

        // If the target player is the same as the current
        if ($this_player->player_id == $target_player->player_id){

            // Collect the actual target player from the battle values
            if (!empty($this_battle->values['players'])){
                foreach ($this_battle->values['players'] AS $id => $info){
                    if ($this_player->player_id != $id){
                        unset($target_player, $target_robot);
                        $target_player = rpg_game::get_player($this_battle, $info);
                        $target_robot = $this_battle->find_target_robot($target_player->player_side);
                    }
                }
            }

            // Collect the actual target robot from the battle values
            if (!empty($target_player->values['robots_active'])){
                foreach ($target_player->values['robots_active'] AS $key => $info){
                    if ($info['robot_position'] == 'active'){
                        $target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                    }
                }
            }

        }

        // Update the target player's session
        $this_player->update_session();

        // Create the robot disabled event if not disabled already some other way
        $disabled_message_flag = 'disabled_on_'.$this_battle->counters['battle_turn'];
        if (!isset($this_robot->flags[$disabled_message_flag])){
            $this_robot->flags[$disabled_message_flag] = true;
            $event_header = ($this_player->player_token != 'player' ? $this_player->player_name.'&#39;s ' : '').$this_robot->robot_name;
            $event_body = ($this_player->player_token != 'player' ? $this_player->print_name().'&#39;s ' : 'The target ').' '.$this_robot->print_name().' was disabled!<br />';
            if (isset($this_robot->robot_quotes['battle_defeat'])){
                $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                $event_body .= $this_robot->print_quote('battle_defeat', $this_find, $this_replace);
            }
            if ($target_robot->robot_status != 'disabled'){ $target_robot->robot_frame = 'base'; }
            $this_robot->robot_frame = 'defeat';
            $target_robot->update_session();
            $this_robot->update_session();
            $this_battle->events_create($this_robot, $target_robot, $event_header, $event_body, array('console_show_target' => false, 'canvas_show_disabled_bench' => $this_robot->robot_id.'_'.$this_robot->robot_token));
        }

        // Check to see if this robot is holding an Extra Life before disabling
        if ($this_robot->has_item()){

            // Define the item info based on token and load into memory
            $item_token = $this_robot->get_item();
            $item_info = array(
                'flags' => array('is_part' => true),
                'part_token' => 'item_'.$item_token,
                'item_token' => $item_token
                );
            $this_item = rpg_game::get_item($this_battle, $this_player, $this_robot, $item_info);
            $this_battle->events_debug(__FILE__, __LINE__, $this_robot->robot_token.' checkpoint has item '.$item_token);

            // If this robot is holding an Extra Life, prevented it from being disabled
            if ($item_token == 'extra-life'){

                // Allow this robot to show on the canvas again so we can revive it
                unset($this_robot->flags['apply_disabled_state']);
                unset($this_robot->flags['hidden']);
                unset($this_robot->robot_attachments['object_defeat-explosion']);
                $this_robot->robot_frame = 'defeat';
                $this_robot->update_session();

                // Restore the target robot's health and weapons back to their full amounts
                $this_robot->robot_status = 'active';
                $this_robot->robot_energy = 0;
                $this_robot->robot_weapons = 0;
                $this_robot->robot_attack = $this_robot->robot_base_attack;
                $this_robot->robot_defense = $this_robot->robot_base_defense;
                $this_robot->robot_speed = $this_robot->robot_base_speed;
                $this_robot->update_session();

                // Update the target player's session
                $this_player->update_session();

                // Collect the base recovery amount for this item
                $temp_item_recovery = $this_item->get_recovery();

                // Remove the robot's current item now that it's used up in battle
                $this_robot->set_item('');

                // Define the item object and trigger info
                $temp_base_energy = $this_robot->get_base_energy();
                $temp_recovery_amount = round($temp_base_energy * ($temp_item_recovery / 100));
                $this_item->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'percent' => true,
                    'modifiers' => false,
                    'frame' => 'taunt',
                    'success' => array(9, 0, 0, -9999, 'The held '.$this_item->print_name().' restored '.$this_robot->print_name().'&#39;s life energy!'),
                    'failure' => array(9, 0, 0, -9999, '')
                    ));

                // Trigger stat recovery for the holding robot
                $this_battle->events_debug(__FILE__, __LINE__, $this_robot->robot_token.' '.$item_token.' restores energy by '.$temp_recovery_amount.' ('.$temp_item_recovery.'%)');
                if (!empty($temp_recovery_amount)){ $this_robot->trigger_recovery($this_robot, $this_item, $temp_recovery_amount); }

                // Define the item object and trigger info
                $temp_base_weapons = $this_robot->get_base_weapons();
                $temp_recovery_amount = round($temp_base_weapons * ($temp_item_recovery / 100));
                $this_item->recovery_options_update(array(
                    'kind' => 'weapons',
                    'frame' => 'taunt',
                    'percent' => true,
                    'modifiers' => false,
                    'frame' => 'taunt',
                    'success' => array(9, 0, 0, -9999, 'The held '.$this_item->print_name().' restored '.$this_robot->print_name().'&#39;s weapon energy!'),
                    'failure' => array(9, 0, 0, -9999, '')
                    ));

                // Trigger stat recovery for the holding robot
                $this_battle->events_debug(__FILE__, __LINE__, $this_robot->robot_token.' '.$item_token.' restores weapons by '.$temp_recovery_amount.' ('.$temp_item_recovery.'%)');
                if (!empty($temp_recovery_amount)){ $this_robot->trigger_recovery($this_robot, $this_item, $temp_recovery_amount); }

                // Also remove this robot's item from the session, we're done with it
                if ($this_player->player_side == 'left' && empty($this_battle->flags['player_battle']) && empty($this_battle->flags['challenge_battle'])){
                    $ptoken = $this_player->player_token;
                    $rtoken = $this_robot->robot_token;
                    if (isset($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'])){
                        $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'] = '';
                    }
                }

                // Update the target player's session
                $this_player->update_session();

                // Return false as we're not really disabled
                return false;

            }

        }

        /*
         * EFFORT VALUES / STAT BOOST BONUSES
         */

        // Define the event options array
        $event_options = array();
        $event_options['this_ability_results']['total_actions'] = 0;

        // Calculate the bonus boosts from defeating the target robot (if NOT player battle)
        if ($target_player->player_side === 'left'
            && $target_robot->robot_class === 'master'
            && $target_robot->robot_status !== 'disabled'
            && empty($this_battle->flags['player_battle'])
            && empty($this_battle->flags['challenge_battle'])
            ){

            // Collect this robot's stat details for reference
            $temp_index_info = rpg_robot::get_index_info($target_robot->robot_token);
            $temp_reward_info = mmrpg_prototype_robot_rewards($target_player->player_token, $target_robot->robot_token);
            $temp_robot_stats = rpg_robot::calculate_stat_values($target_robot->robot_level, $temp_index_info, $temp_reward_info, $target_robot->robot_core, $target_robot->player->player_starforce);

            // Define the stats to loop through and alter
            //$stat_tokens = array('energy', 'attack', 'defense', 'speed');
            $stat_tokens = array('attack', 'defense', 'speed');
            $stat_system = array('attack' => 'weapons', 'defense' => 'shield', 'speed' => 'mobility');

            // Define the temporary boost actions counter
            $temp_boost_actions = 1;

            // Create an options object for this function and populate
            $options = rpg_game::new_options_object();
            $options->victim_robot = $this_robot;
            $options->assailant_robot = $target_robot;
            $extra_objects = array('options' => $options);

            // Loop through the stats applying STAT BONUSES to any that apply
            foreach ($stat_tokens AS $stat){

                // Boost this robot's stat if a boost is in order
                $prop_stat = "robot_{$stat}";
                $prop_stat_base = "robot_base_{$stat}";
                $prop_stat_pending = "robot_{$stat}_pending";
                $prop_stat_max = "robot_max_{$stat}";
                $options->allow_stat_boost = true;
                $options->this_stat_type = $stat;
                $options->this_stat_boost = $this_robot->$prop_stat_base / 100;

                // If the robot who disabled this one is already at max bonus, they get no stat boosts
                if ($temp_robot_stats[$stat]['bonus'] >= $temp_robot_stats[$stat]['bonus_max']){

                    // Hard-code the stat boost to zero
                    $this_stat_overboost = 0;
                    $options->this_stat_boost = 0;
                    $options->allow_stat_boost = false;

                }
                // Otherwise check to see if any bonuses apply to the boost amount
                else {

                    // If the disabled robot was a mecha, it only gives half the stat boosts
                    if ($this_robot->robot_class == 'mecha'){  $options->this_stat_boost = $options->this_stat_boost / 2; }
                    // If the robot who disabled this one was a mecha, however, it gets double stat boosts
                    if ($target_player->player_side == 'left' && $target_robot->robot_class == 'mecha'){  $options->this_stat_boost = $options->this_stat_boost * 2;  }
                    // If the robot who disabled this one is at max level, dramatically boost stat bonuses
                    if ($target_robot->robot_level >= 100){ $options->this_stat_boost *= $target_robot->robot_level; }

                }

                // Trigger this and target robot's item functions if they have been defined for this context
                $this_robot->trigger_item_function('rpg-robot_trigger-disabled_stat-rewards', $extra_objects);
                $target_robot->trigger_item_function('rpg-robot_trigger-disabled_stat-rewards', $extra_objects);

                // If the stat was not empty, process it
                if ($options->this_stat_boost > 0){

                    // Round the stat boost to get an int value
                    $options->this_stat_boost = ceil($options->this_stat_boost);

                    // If the robot is under level 100, stat boosts are pending
                    if ($target_robot->robot_level < 100){

                        // Update the session variables with the pending stat boost
                        if (empty($_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat_pending])){ $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat_pending] = 0; }
                        $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat_pending] += $options->this_stat_boost;

                    }
                    // If the robot is at level 100 or a mecha, stat boosts are immediately rewarded
                    elseif ($target_robot->robot_level >= 100){

                        // Create/validate the session variables if not mecha
                        if (!isset($_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat])){ $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat] = 0; }
                        $current_bonus_amount = $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat];

                        // If the stat is already maxed, just continue
                        if ($current_bonus_amount >= $temp_robot_stats[$stat]['bonus_max']){ continue; }

                        // Define the base stat boost based on robot base stats
                        $temp_stat_base_boost = ceil($options->this_stat_boost);
                        if (($target_robot->$prop_stat_base + $temp_stat_base_boost) > MMRPG_SETTINGS_STATS_MAX){ $temp_stat_base_boost = MMRPG_SETTINGS_STATS_MAX - $target_robot->$prop_stat_base; }
                        if (($current_bonus_amount + $temp_stat_base_boost) > $temp_robot_stats[$stat]['bonus_max']){ $temp_stat_base_boost = $temp_robot_stats[$stat]['bonus_max'] - $current_bonus_amount; }

                        // Increment this robot's stat by the calculated amount
                        $target_robot->$prop_stat_base += $temp_stat_base_boost;
                        $target_robot->update_session();
                        $target_player->update_session();

                        // Update the session variables with the rewarded stat boost if not mecha
                        $stat_is_maxed = false;
                        if ($target_robot->robot_class == 'master'){
                            $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat] = ceil($_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat]);
                            $temp_stat_session_boost = round($options->this_stat_boost);
                            if ($temp_stat_session_boost < 1){ $temp_stat_session_boost = 1; }
                            $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat] += $temp_stat_session_boost;
                            $new_bonus_amount = $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$target_robot->robot_token][$prop_stat] >= $temp_robot_stats[$stat]['bonus_max'] ? true : false;
                            $stat_is_maxed = $target_robot->robot_level >= 100 && $new_bonus_amount >= $temp_robot_stats[$stat]['bonus_max'] ? true : false;
                        }

                        // Display an event showing the recovery increase
                        $event_options = array();
                        $event_options['this_ability_results']['trigger_kind'] = 'recovery';
                        $event_options['this_ability_results']['recovery_kind'] = $stat;
                        $event_options['this_ability_results']['recovery_type'] = '';
                        $event_options['this_ability_results']['flag_affinity'] = true;
                        $event_options['this_ability_results']['flag_critical'] = true;
                        $event_options['this_ability_results']['this_amount'] = $temp_stat_base_boost;
                        $event_options['this_ability_results']['this_result'] = 'success';
                        $event_options['this_ability_results']['total_actions'] = $temp_boost_actions++;
                        $event_options['this_ability_target'] = $target_robot->robot_id.'_'.$target_robot->robot_token;
                        $event_options['console_show_target'] = false;
                        $event_body = $target_robot->print_name().' downloads '.$stat_system[$stat].' data from the target! ';
                        $event_body .= '<br />';
                        $event_body .= $target_robot->print_name().'&#39;s base '.$stat.' grew by <span class="recovery_amount">'.$temp_stat_base_boost.'</span>! ';
                        if ($stat_is_maxed){ $event_body .= '<span class="recovery_amount type type_'.$stat.'">Max '.ucfirst($stat).'! &#9733;</span>'; }
                        $frame = 'taunt';
                        if ($stat == 'energy'){ $frame = 'summon'; }
                        elseif ($stat == 'attack'){ $frame = 'shoot'; }
                        elseif ($stat == 'defense'){ $frame = 'defend'; }
                        elseif ($stat == 'speed'){ $frame = 'slide'; }
                        $target_robot->set_frame($frame);
                        $this_battle->events_create($target_robot, $this_robot, $event_header, $event_body, $event_options);

                    }

                }

            }

            // Update the target robot frame
            $target_robot->robot_frame = 'base';
            $target_robot->update_session();

        }

        // Ensure player and robot variables are updated
        $target_robot->update_session();
        $target_player->update_session();
        $this_robot->update_session();
        $this_player->update_session();

        /*
        // DEBUG
        $this_robot->battle->events_create(false, false, 'DEBUG', 'we made it past the stat boosts... <br />'.
            '$this_robot->robot_token='.$this_robot->robot_token.'; $target_robot->robot_token='.$target_robot->robot_token.';<br />'.
            '$target_player->player_token='.$target_player->player_token.'; $target_player->player_side='.$target_player->player_side.';<br />'
            );
        */

        /*
         * ITEM REWARDS / EXPERIENCE POINTS / LEVEL UP
         * Reward the player and robots with items and experience if not in demo mode
         */

        if ($target_player->player_side == 'left'
            && empty($this_battle->flags['player_battle'])
            && empty($this_battle->flags['challenge_battle'])
            && empty($_SESSION['GAME']['DEMO'])){

            // -- EXPERIENCE POINTS / LEVEL UP -- //

            // Filter out robots who were active in this battle in at least some way
            $target_player->update_session();
            $temp_target_robots_active = $target_player->values['robots_active'];
            usort($temp_target_robots_active, array('rpg_player','robot_sort_by_active'));

            // Define the boost multiplier and start out at zero
            $temp_boost_multiplier = 0;

            // DEBUG
            //$event_body = preg_replace('/\s+/', ' ', $this_robot->robot_token.' : $this_robot->counters = <pre>'.print_r($this_robot->counters, true).'</pre>');
            //$this_battle->events_create(false, false, 'DEBUG', $event_body);

            // If the target has had any damage flags triggered, update the multiplier
            //if ($this_robot->flags['triggered_immunity']){ $temp_boost_multiplier += 0; }
            //if (!empty($this_robot->flags['triggered_resistance'])){ $temp_boost_multiplier -= $this_robot->counters['triggered_resistance'] * 0.10; }
            //if (!empty($this_robot->flags['triggered_affinity'])){ $temp_boost_multiplier -= $this_robot->counters['triggered_affinity'] * 0.10; }
            //if (!empty($this_robot->flags['triggered_weakness'])){ $temp_boost_multiplier += $this_robot->counters['triggered_weakness'] * 0.10; }
            //if (!empty($this_robot->flags['triggered_critical'])){ $temp_boost_multiplier += $this_robot->counters['triggered_critical'] * 0.10; }

            // If we're in DEMO mode, give a 100% experience boost
            //if (!empty($_SESSION['GAME']['DEMO'])){ $temp_boost_multiplier += 1; }

            // Ensure the multiplier has not gone below 100%
            if ($temp_boost_multiplier < -0.99){ $temp_boost_multiplier = -0.99; }
            elseif ($temp_boost_multiplier > 0.99){ $temp_boost_multiplier = 0.99; }

            // Define the boost text to match the multiplier
            $temp_boost_text = '';
            if ($temp_boost_multiplier < 0){ $temp_boost_text = 'a lowered '; }
            elseif ($temp_boost_multiplier > 0){ $temp_boost_text = 'a boosted '; }

            /*
            $event_body = preg_replace('/\s+/', ' ', $this_robot->robot_token.'<pre>'.print_r($this_robot->flags, true).'</pre>');
            $this_battle->events_create(false, false, 'DEBUG', $event_body);

            $event_body = preg_replace('/\s+/', ' ', $target_robot->robot_token.'<pre>'.print_r($target_robot->flags, true).'</pre>');
            $this_battle->events_create(false, false, 'DEBUG', $event_body);
            */


            // Define the base experience for the target robot
            $temp_experience = $this_robot->robot_base_energy + $this_robot->robot_base_attack + $this_robot->robot_base_defense + $this_robot->robot_base_speed;

            // DEBUG
            //$event_body = preg_replace('/\s+/', ' ', $this_robot->robot_token.' : $temp_boost_multiplier = '.$temp_boost_multiplier.'; $temp_experience = '.$temp_experience.'; ');
            //$this_battle->events_create(false, false, 'DEBUG_'.__LINE__, $event_body);

            // Apply any boost multipliers to the experience earned
            if ($temp_boost_multiplier > 0 || $temp_boost_multiplier < 0){ $temp_experience += $temp_experience * $temp_boost_multiplier; }
            if ($temp_experience <= 0){ $temp_experience = 1; }
            $temp_experience = round($temp_experience);
            $temp_target_experience = array('level' => $this_robot->robot_level, 'experience' => $temp_experience);

            // DEBUG
            //$event_body = preg_replace('/\s+/', ' ', $this_robot->robot_token.' : $temp_target_experience = <pre>'.print_r($temp_target_experience, true).'</pre>');
            //$this_battle->events_create(false, false, 'DEBUG', $event_body);

            // Sort the active robots based on active or not
            /*
            function mmrpg_sort_temp_active_robots($info1, $info2){
                if ($info1['robot_position'] == 'active'){ return -1; }
                else { return 1; }
            }
            usort($temp_target_robots_active, 'mmrpg_sort_temp_active_robots');
            */

            // Increment each of this player's robots
            $temp_target_robots_active_num = count($temp_target_robots_active);
            $temp_target_robots_active_num2 = $temp_target_robots_active_num; // This will be decremented for each non-experience gaining level 100 robots
            $temp_target_robots_active = array_reverse($temp_target_robots_active, true);
            usort($temp_target_robots_active, array('rpg_player', 'robot_sort_by_active'));
            $temp_robot_active_position = false;
            foreach ($temp_target_robots_active AS $temp_id => $temp_info){
                $temp_target_robot = $target_robot->robot_id == $temp_info['robot_id'] ? $target_robot : rpg_game::get_robot($this_robot, $target_player, $temp_info);
                if ($temp_target_robot->robot_class != 'master'){ $temp_target_robots_active_num2--; }
                if ($temp_target_robot->robot_position == 'active'){
                    $temp_robot_active_position = $temp_target_robots_active[$temp_id];
                    unset($temp_target_robots_active[$temp_id]);
                }
            }
            $temp_unshift = array_unshift($temp_target_robots_active, $temp_robot_active_position);

            // DEBUG
            //$event_body = preg_replace('/\s+/', ' ', $this_robot->robot_token.' : $temp_target_robots_active = <pre>'.count($temp_target_robots_active).'</pre>');
            //$this_battle->events_create(false, false, 'DEBUG', $event_body);

            // Create an options object for this function and populate
            $options = rpg_game::new_options_object();
            $options->victim_robot = $this_robot;
            $options->assailant_robot = $target_robot;
            $options->beneficiary_robot = false;
            $extra_objects = array('options' => $options);

            foreach ($temp_target_robots_active AS $temp_id => $temp_info){

                // Collect or define the robot points and robot rewards variables
                $temp_target_robot = $target_robot->robot_id == $temp_info['robot_id'] ? $target_robot : rpg_game::get_robot($this_robot, $target_player, $temp_info);
                if ($temp_target_robot->robot_class !== 'master'){ continue; }
                $temp_robot_token = $temp_info['robot_token'];
                if ($temp_robot_token == 'robot'){ continue; }
                $temp_robot_experience = mmrpg_prototype_robot_experience($target_player->player_token, $temp_info['robot_token']);
                $temp_robot_rewards = !empty($temp_info['robot_rewards']) ? $temp_info['robot_rewards'] : array();
                if (empty($temp_target_robots_active_num2)){ break; }
                $options->beneficiary_robot = $temp_target_robot;

                // Reset the robot experience points to zero
                $options->start_experience = 0;
                $options->divided_experience = 0;
                $options->earned_experience = 0;
                $options->this_experience_boost = 0;
                $options->this_experience_boost_word = 'boosted';
                $options->this_experience_boost_kinds = array();

                // Continue with experience mods only if under level 100
                if ($temp_target_robot->robot_level < 100){

                    //$debug_text = 'START EXPERIENCE | ';
                    //$debug_text .= '(for '.$temp_target_robot->robot_token.' via '.$this_robot->robot_token.') <br /> ';
                    // Give a proportionate amount of experience based on this and the target robot's levels
                    if ($temp_target_robot->robot_level == $temp_target_experience['level']){
                        $options->start_experience = $temp_target_experience['experience'];
                    } elseif ($temp_target_robot->robot_level < $temp_target_experience['level']){
                        $options->start_experience = $temp_target_experience['experience'] + round((($temp_target_experience['level'] - $temp_target_robot->robot_level) / 100)  * $temp_target_experience['experience']);
                    } elseif ($temp_target_robot->robot_level > $temp_target_experience['level']){
                        $options->start_experience = $temp_target_experience['experience'] - round((($temp_target_robot->robot_level - $temp_target_experience['level']) / 100)  * $temp_target_experience['experience']);
                    }
                    //$debug_text .= 'start_experience = '.$options->start_experience.' | ';
                    //$debug_text .= '(start) earned_experience = '.$options->earned_experience.' ';
                    //$this_battle->events_create(false, false, 'DEBUG', $debug_text);

                    //$debug_text = 'ACTIVE ROBOT DIVISION | ';
                    //$debug_text .= '(for '.$temp_target_robot->robot_token.' via '.$this_robot->robot_token.') <br /> ';
                    $options->divided_experience = ceil($options->start_experience / $temp_target_robots_active_num);
                    if ($options->divided_experience > MMRPG_SETTINGS_STATS_MAX){ $options->divided_experience = MMRPG_SETTINGS_STATS_MAX; }
                    $options->earned_experience += $options->divided_experience;
                    //$debug_text .= 'divided_experience = '.$options->divided_experience.' | ';
                    //$debug_text .= '(new) earned_experience = '.$options->earned_experience.' ';
                    //$this_battle->events_create(false, false, 'DEBUG', $debug_text);

                    //$debug_text = 'PLAYER BOOSTED | ';
                    //$debug_text .= '(for '.$temp_target_robot->robot_token.' via '.$this_robot->robot_token.') <br /> ';
                    // If this robot has been traded, give it an additional experience boost
                    $options->this_experience_boost = 0;
                    $options->is_player_boosted = false;
                    if ($temp_target_robot->player_token != $temp_target_robot->robot_original_player){
                        $options->this_experience_boost_kinds[] = 'player';
                        $options->is_player_boosted = true;
                        $temp_experience_bak = $options->earned_experience;
                        $options->earned_experience *= 2;
                        $options->this_experience_boost = $options->earned_experience - $temp_experience_bak;
                    }
                    //$debug_text .= 'this_experience_boost = '.$options->this_experience_boost.' | ';
                    //$debug_text .= '(new) earned_experience = '.$options->earned_experience.' ';
                    //$this_battle->events_create(false, false, 'DEBUG', $debug_text);

                    //$debug_text = 'FIELD MULTIPLIERS | ';
                    //$debug_text .= '(for '.$temp_target_robot->robot_token.' via '.$this_robot->robot_token.') <br /> ';
                    // If there are field multipliers in place, apply them now
                    $options->this_experience_boost = 0;
                    $options->is_field_boosted = false;
                    if (isset($this_robot->field->field_multipliers['experience']) && $this_robot->field->field_multipliers['experience'] != 1){
                        $options->this_experience_boost_kinds[] = 'field';
                        $options->is_field_boosted = true;
                        if ($this_robot->field->field_multipliers['experience'] < 1){ $options->this_experience_boost_word = 'modified'; }
                        $temp_experience_bak = $options->earned_experience;
                        $options->earned_experience = ceil($options->earned_experience * $this_robot->field->field_multipliers['experience']);
                        $options->this_experience_boost = $options->earned_experience - $temp_experience_bak;
                    }
                    //$debug_text .= 'this_experience_boost = '.$options->this_experience_boost.' | ';
                    //$debug_text .= '(new) earned_experience = '.$options->earned_experience.' ';
                    //$this_battle->events_create(false, false, 'DEBUG', $debug_text);

                    // Trigger this and target robot's item functions if they have been defined for this context
                    $this_robot->trigger_item_function('rpg-robot_trigger-disabled_experience-rewards', $extra_objects);
                    $temp_target_robot->trigger_item_function('rpg-robot_trigger-disabled_experience-rewards', $extra_objects);

                    //$debug_text = 'MIN/MAX ROUNDING | ';
                    //$debug_text .= '(for '.$temp_target_robot->robot_token.' via '.$this_robot->robot_token.') <br /> ';
                    // If the experience is greater then the max, level it off at the max (sorry guys!)
                    if ($options->earned_experience > MMRPG_SETTINGS_STATS_MAX){ $options->earned_experience = MMRPG_SETTINGS_STATS_MAX; }
                    if ($options->earned_experience < MMRPG_SETTINGS_STATS_MIN){ $options->earned_experience = MMRPG_SETTINGS_STATS_MIN; }
                    //$debug_text .= '(final) earned_experience = '.$options->earned_experience.' ';
                    //$this_battle->events_create(false, false, 'DEBUG', $debug_text);

                    // Update the boost text based on applied multiplier kinds
                    if (!empty($options->this_experience_boost_kinds)){
                        $temp_robot_boost_text = implode(', ', $options->this_experience_boost_kinds);
                        $temp_robot_boost_text = preg_replace('/,\s([a-z]+)$/i', ', and $1', $temp_robot_boost_text);
                        $temp_robot_boost_text = (preg_match('/^(a|e|i|o|y)/', $temp_robot_boost_text) ? 'an ' : 'a ').$temp_robot_boost_text.' '.$options->this_experience_boost_word.' ';
                    }

                    // Collect the robot's current experience and level for reference later
                    $temp_start_experience = mmrpg_prototype_robot_experience($target_player->player_token, $temp_robot_token);
                    $temp_start_level = mmrpg_prototype_robot_level($target_player->player_token, $temp_robot_token);

                    // Increment this robots's points total with the battle points
                    $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_robot_token]['robot_experience'] += $options->earned_experience;

                    // Define the new experience for this robot
                    $temp_new_experience = mmrpg_prototype_robot_experience($target_player->player_token, $temp_info['robot_token']);// If the new experience is over 1000, level up the robot
                    $level_boost = 0;
                    if ($temp_new_experience > 1000){
                        $level_boost = floor($temp_new_experience / 1000);
                        $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_robot_token]['robot_level'] += $level_boost;
                        $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_robot_token]['robot_experience'] -= $level_boost * 1000;
                        if ($_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_robot_token]['robot_level'] > 100){
                            $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_robot_token]['robot_level'] = 100;
                        }
                        $temp_new_experience = mmrpg_prototype_robot_experience($target_player->player_token, $temp_info['robot_token']);
                    }

                    // Define the new level for this robot
                    $temp_new_level = mmrpg_prototype_robot_level($target_player->player_token, $temp_robot_token);

                }
                // Otherwise if this is a level 100 robot already
                else {

                    // Collect the robot's current experience and level for reference later
                    $temp_start_experience = mmrpg_prototype_robot_experience($target_player->player_token, $temp_robot_token);
                    $temp_start_level = mmrpg_prototype_robot_level($target_player->player_token, $temp_robot_token);

                    // Define the new experience for this robot
                    $temp_new_experience = $temp_start_experience;
                    $temp_new_level = $temp_start_level;

                }

                // Define the event options
                $event_options = array();
                $event_options['this_ability_results']['trigger_kind'] = 'recovery';
                $event_options['this_ability_results']['recovery_kind'] = 'experience';
                $event_options['this_ability_results']['recovery_type'] = '';
                $event_options['this_ability_results']['this_amount'] = $options->earned_experience;
                $event_options['this_ability_results']['this_result'] = 'success';
                $event_options['this_ability_results']['flag_affinity'] = true;
                $event_options['this_ability_results']['total_actions'] = 1;
                $event_options['this_ability_target'] = $temp_target_robot->robot_id.'_'.$temp_target_robot->robot_token;

                // Update player/robot frames and points for the victory
                $temp_target_robot->robot_frame = 'victory';
                $temp_target_robot->robot_level = $temp_new_level;
                $temp_target_robot->robot_experience = $temp_new_experience;
                $target_player->player_frame = 'victory';
                $temp_target_robot->update_session();
                $target_player->update_session();

                // Only display the event if the player is under level 100
                if ($temp_target_robot->robot_level < 100 && $temp_target_robot->robot_class == 'master'){
                    // Display the win message for this robot with battle points
                    $temp_target_robot->robot_frame = 'taunt';
                    $temp_target_robot->robot_level = $temp_new_level;
                    if ($temp_start_level != $temp_new_level){ $temp_target_robot->robot_experience = 1000; }
                    $target_player->player_frame = 'victory';
                    $event_header = $temp_target_robot->robot_name.'&#39;s Rewards';
                    $event_multiplier_text = !empty($temp_robot_boost_text) ? $temp_robot_boost_text : '';
                    $event_body = $temp_target_robot->print_name().' collects '.$event_multiplier_text.'<span class="recovery_amount ability_type ability_type_cutter">'.number_format($options->earned_experience, 0, '.', ',').'</span> experience points! ';
                    $event_body .= '<br />';
                    if (isset($temp_target_robot->robot_quotes['battle_victory'])){
                        $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                        $this_replace = array($this_player->player_name, $this_robot->robot_name, $target_player->player_name, $temp_target_robot->robot_name);
                        $event_body .= $temp_target_robot->print_quote('battle_victory', $this_find, $this_replace);
                    }
                    $event_options['console_show_target'] = false;
                    $event_options['this_header_float'] = $event_options['this_body_float'] = $target_player->player_side;
                    $temp_target_robot->update_session();
                    $target_player->update_session();
                    $this_battle->events_create($temp_target_robot, $this_robot, $event_header, $event_body, $event_options);
                    if ($temp_start_level != $temp_new_level){ $temp_target_robot->robot_experience = $temp_new_experience; }
                    $temp_target_robot->update_session();
                    $target_player->update_session();
                }

                // Floor the robot's experience with or without the event
                $target_player->player_frame = 'victory';
                $target_player->update_session();
                $temp_target_robot->robot_frame = 'base';
                if ($temp_start_level != $temp_new_level){ $temp_target_robot->robot_experience = 0; }
                $temp_target_robot->update_session();

                // If the level has been boosted, display the stat increases
                if ($temp_start_level != $temp_new_level){

                    // Check to see if this robot finally hit level 100
                    $temp_is_max_level = $temp_new_level >= 100 ? true : false;

                    // Define the event options
                    $event_options = array();
                    $event_options['this_ability_results']['trigger_kind'] = 'recovery';
                    $event_options['this_ability_results']['recovery_kind'] = 'level';
                    $event_options['this_ability_results']['recovery_type'] = '';
                    $event_options['this_ability_results']['flag_affinity'] = true;
                    $event_options['this_ability_results']['flag_critical'] = true;
                    $event_options['this_ability_results']['this_amount'] = $temp_new_level - $temp_start_level;
                    $event_options['this_ability_results']['this_result'] = 'success';
                    $event_options['this_ability_results']['total_actions'] = 2;
                    $event_options['this_ability_target'] = $temp_target_robot->robot_id.'_'.$temp_target_robot->robot_token;

                    // Display the win message for this robot with battle points
                    $temp_target_robot->robot_frame = 'taunt';
                    $temp_target_robot->robot_level = $temp_new_level;
                    if ($temp_start_level != $temp_new_level){ $temp_target_robot->robot_experience = 1000; }
                    else { $temp_target_robot->robot_experience = $temp_new_experience; }
                    $target_player->player_frame = 'victory';
                    $event_header = $temp_target_robot->robot_name.'&#39;s Rewards';
                    $event_body = $temp_target_robot->print_name().' grew to <span class="recovery_amount ability_type ability_type_level">Level '.$temp_new_level.($temp_is_max_level ? ' &#9733;' : '').'</span>!<br /> ';
                    $event_body .= $temp_target_robot->robot_name.'&#39;s energy, weapons, shields, and mobility were upgraded!';
                    $event_options['console_show_target'] = false;
                    $event_options['this_header_float'] = $event_options['this_body_float'] = $target_player->player_side;
                    $temp_target_robot->update_session();
                    $target_player->update_session();
                    $this_battle->events_create($temp_target_robot, $this_robot, $event_header, $event_body, $event_options);
                    $temp_target_robot->robot_experience = 0;
                    $temp_target_robot->update_session();

                    // Collect the base robot template from the index for calculations
                    $temp_index_robot = rpg_robot::get_index_info($temp_target_robot->robot_token);

                    // Define the event options
                    $event_options['this_ability_results']['trigger_kind'] = 'recovery';
                    $event_options['this_ability_results']['recovery_type'] = '';
                    $event_options['this_ability_results']['this_amount'] = $level_boost;
                    $event_options['this_ability_results']['this_result'] = 'success';
                    $event_options['this_ability_results']['total_actions'] = 0;
                    $event_options['this_ability_target'] = $temp_target_robot->robot_id.'_'.$temp_target_robot->robot_token;

                    // Update the robot rewards array with any recent info
                    $temp_robot_rewards = mmrpg_prototype_robot_rewards($target_player->player_token, $temp_target_robot->robot_token);
                    //$this_battle->events_create(false, false, 'DEBUG', '<pre>'.preg_replace('/\s+/', ' ', print_r($temp_robot_rewards, true)).'</pre>', $event_options);

                    // If this robot has reached level 100, the max level, create the flag in their session
                    if ($temp_is_max_level){ $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['flags']['reached_max_level'] = true; }

                    // Define the base energy boost based on robot base stats
                    $temp_energy_boost = ceil($level_boost * (0.01 * $temp_index_robot['robot_energy']));

                    // Check if there are eny pending energy stat boosts for level up
                    if (!empty($temp_robot_rewards['robot_energy_pending'])){
                        $temp_robot_rewards['robot_energy_pending'] = round($temp_robot_rewards['robot_energy_pending']);
                        $temp_energy_boost += $temp_robot_rewards['robot_energy_pending'];
                        if (!empty($temp_robot_rewards['robot_energy'])){ $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_energy'] += $temp_robot_rewards['robot_energy_pending']; }
                        else { $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_energy'] = $temp_robot_rewards['robot_energy_pending']; }
                        $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_energy_pending'] = 0;
                    }

                    // Increment this robot's energy by the calculated amount and display an event
                    $temp_target_robot->robot_energy += $temp_energy_boost;
                    $temp_base_energy_boost = ceil($level_boost * (0.01 * $temp_index_robot['robot_energy']));
                    $temp_target_robot->robot_base_energy += $temp_base_energy_boost;
                    $temp_target_robot->update_session();
                    $target_player->update_session();
                    if ($temp_target_robot->robot_position == 'active'){
                        $event_options['this_ability_results']['recovery_kind'] = 'energy';
                        $event_options['this_ability_results']['this_amount'] = $temp_energy_boost;
                        $event_options['this_ability_results']['total_actions']++;
                        $event_body = $temp_target_robot->print_name().'&#39;s health improved! ';
                        $event_body .= '<br />';
                        $event_body .= $temp_target_robot->print_name().'&#39;s base energy grew by <span class="recovery_amount">'.$temp_energy_boost.'</span>! ';
                        $temp_target_robot->set_frame('summon');
                        $this_battle->events_create($temp_target_robot, $this_robot, $event_header, $event_body, $event_options);
                    }


                    // Define the base attack boost based on robot base stats
                    $temp_attack_boost = ceil($level_boost * (0.05 * $temp_index_robot['robot_attack']));

                    // Check if there are eny pending attack stat boosts for level up
                    if (!empty($temp_robot_rewards['robot_attack_pending'])){
                        $temp_robot_rewards['robot_attack_pending'] = round($temp_robot_rewards['robot_attack_pending']);
                        $temp_attack_boost += $temp_robot_rewards['robot_attack_pending'];
                        if (!empty($temp_robot_rewards['robot_attack'])){ $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_attack'] += $temp_robot_rewards['robot_attack_pending']; }
                        else { $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_attack'] = $temp_robot_rewards['robot_attack_pending']; }
                        $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_attack_pending'] = 0;
                    }

                    // Increment this robot's attack by the calculated amount and display an event
                    $temp_base_attack_boost = ceil($level_boost * (0.05 * $temp_index_robot['robot_attack']));
                    $temp_target_robot->robot_base_attack += $temp_base_attack_boost;
                    $temp_target_robot->update_session();
                    $target_player->update_session();
                    if ($temp_target_robot->robot_position == 'active'){
                        $event_options['this_ability_results']['recovery_kind'] = 'attack';
                        $event_options['this_ability_results']['this_amount'] = $temp_base_attack_boost;
                        $event_options['this_ability_results']['total_actions']++;
                        $event_body = $temp_target_robot->print_name().'&#39;s weapons improved! ';
                        $event_body .= '<br />';
                        $event_body .= $temp_target_robot->print_name().'&#39;s base attack grew by <span class="recovery_amount">'.$temp_base_attack_boost.'</span>! ';
                        $temp_target_robot->set_frame('shoot');
                        $this_battle->events_create($temp_target_robot, $this_robot, $event_header, $event_body, $event_options);
                    }


                    // Define the base defense boost based on robot base stats
                    $temp_defense_boost = ceil($level_boost * (0.05 * $temp_index_robot['robot_defense']));

                    // Check if there are eny pending defense stat boosts for level up
                    if (!empty($temp_robot_rewards['robot_defense_pending'])){
                        $temp_robot_rewards['robot_defense_pending'] = round($temp_robot_rewards['robot_defense_pending']);
                        $temp_defense_boost += $temp_robot_rewards['robot_defense_pending'];
                        if (!empty($temp_robot_rewards['robot_defense'])){ $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_defense'] += $temp_robot_rewards['robot_defense_pending']; }
                        else { $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_defense'] = $temp_robot_rewards['robot_defense_pending']; }
                        $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_defense_pending'] = 0;
                    }

                    // Increment this robot's defense by the calculated amount and display an event
                    $temp_base_defense_boost = ceil($level_boost * (0.05 * $temp_index_robot['robot_defense']));
                    $temp_target_robot->robot_base_defense += $temp_base_defense_boost;
                    $temp_target_robot->update_session();
                    $target_player->update_session();
                    if ($temp_target_robot->robot_position == 'active'){
                        $event_options['this_ability_results']['recovery_kind'] = 'defense';
                        $event_options['this_ability_results']['this_amount'] = $temp_base_defense_boost;
                        $event_options['this_ability_results']['total_actions']++;
                        $event_body = $temp_target_robot->print_name().'&#39;s shields improved! ';
                        $event_body .= '<br />';
                        $event_body .= $temp_target_robot->print_name().'&#39;s base defense grew by <span class="recovery_amount">'.$temp_base_defense_boost.'</span>! ';
                        $temp_target_robot->set_frame('defend');
                        $this_battle->events_create($temp_target_robot, $this_robot, $event_header, $event_body, $event_options);
                    }


                    // Define the base speed boost based on robot base stats
                    $temp_speed_boost = ceil($level_boost * (0.05 * $temp_index_robot['robot_speed']));

                    // Check if there are eny pending speed stat boosts for level up
                    if (!empty($temp_robot_rewards['robot_speed_pending'])){
                        $temp_robot_rewards['robot_speed_pending'] = round($temp_robot_rewards['robot_speed_pending']);
                        $temp_speed_boost += $temp_robot_rewards['robot_speed_pending'];
                        if (!empty($temp_robot_rewards['robot_speed'])){ $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_speed'] += $temp_robot_rewards['robot_speed_pending']; }
                        else { $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_target_robot->robot_token]['robot_speed'] = $temp_robot_rewards['robot_speed_pending']; }
                        $_SESSION['GAME']['values']['battle_rewards'][$target_player->player_token]['player_robots'][$temp_robot_token]['robot_speed_pending'] = 0;
                    }

                    // Increment this robot's speed by the calculated amount and display an event
                    $temp_base_speed_boost = ceil($level_boost * (0.05 * $temp_index_robot['robot_speed']));
                    $temp_target_robot->robot_base_speed += $temp_base_speed_boost;
                    $temp_target_robot->update_session();
                    $target_player->update_session();
                    if ($temp_target_robot->robot_position == 'active'){
                        $event_options['this_ability_results']['recovery_kind'] = 'speed';
                        $event_options['this_ability_results']['this_amount'] = $temp_base_speed_boost;
                        $event_options['this_ability_results']['total_actions']++;
                        $event_body = $temp_target_robot->print_name().'&#39;s mobility improved! ';
                        $event_body .= '<br />';
                        $event_body .= $temp_target_robot->print_name().'&#39;s base speed grew by <span class="recovery_amount">'.$temp_base_speed_boost.'</span>! ';
                        $temp_target_robot->set_frame('slide');
                        $this_battle->events_create($temp_target_robot, $this_robot, $event_header, $event_body, $event_options);
                    }

                    // Update the robot frame
                    $temp_target_robot->robot_frame = 'base';
                    $temp_target_robot->update_session();

                }

                // Update the experience level for real this time
                $temp_target_robot->robot_experience = $temp_new_experience;
                $temp_target_robot->update_session();

                // Collect the robot info array
                $temp_robot_info = $temp_target_robot->export_array();

                // Collect the indexed robot rewards for new abilities
                $index_robot_rewards = $temp_robot_info['robot_rewards'];
                //$event_body = preg_replace('/\s+/', ' ', '<pre>'.print_r($index_robot_rewards, true).'</pre>');
                //$this_battle->events_create(false, false, 'DEBUG', $event_body);

                // Loop through the ability rewards for this robot if set
                if ($temp_target_robot->robot_class != 'mecha' && ($temp_start_level == 100 || ($temp_start_level != $temp_new_level && !empty($index_robot_rewards['abilities'])))){
                    $temp_abilities_index = $db->get_array_list("SELECT * FROM mmrpg_index_abilities WHERE ability_flag_complete = 1;", 'ability_token');
                    foreach ($index_robot_rewards['abilities'] AS $ability_reward_key => $ability_reward_info){

                        // If this ability is already unlocked, continue
                        if (mmrpg_prototype_ability_unlocked($target_player->player_token, $temp_robot_token, $ability_reward_info['token'])){ continue; }
                        // If we're in DEMO mode, continue
                        if (!empty($_SESSION['GAME']['DEMO'])){ continue; }

                        // Check if the required level has been met by this robot
                        if ($temp_new_level >= $ability_reward_info['level']){

                            // Collect the ability info from the index
                            $ability_info = rpg_ability::parse_index_info($temp_abilities_index[$ability_reward_info['token']]);
                            // Create the temporary ability object for event creation
                            $temp_ability = rpg_game::get_ability($this_robot->battle, $target_player, $temp_target_robot, $ability_info);

                            // Collect or define the ability variables
                            $temp_ability_token = $ability_info['ability_token'];

                            // Display the robot reward message markup
                            if (!mmrpg_prototype_ability_unlocked('', $temp_robot_token, $ability_reward_info['token'])){
                                $event_header = $ability_info['ability_name'].' Unlocked';
                                $event_body = '<span class="robot_name">'.$temp_info['robot_name'].'</span> unlocked new ability data!<br />';
                                $event_body .= '<span class="ability_name">'.$ability_info['ability_name'].'</span> can now be used in battle!';
                                $event_options = array();
                                $event_options['console_show_target'] = false;
                                $event_options['this_header_float'] = $target_player->player_side;
                                $event_options['this_body_float'] = $target_player->player_side;
                                $event_options['this_ability'] = $temp_ability;
                                $event_options['this_ability_image'] = 'icon';
                                $event_options['console_show_this_player'] = false;
                                $event_options['console_show_this_robot'] = false;
                                $event_options['console_show_this_ability'] = true;
                                $event_options['canvas_show_this_ability'] = false;
                                $temp_target_robot->robot_frame = $ability_reward_key % 2 == 2 ? 'taunt' : 'victory';
                                $temp_target_robot->update_session();
                                $temp_ability->ability_frame = 'base';
                                $temp_ability->update_session();
                                $this_battle->events_create($temp_target_robot, false, $event_header, $event_body, $event_options);
                                $temp_target_robot->robot_frame = 'base';
                                $temp_target_robot->update_session();
                            }

                            // Automatically unlock this ability for use in battle
                            $this_reward = array('ability_token' => $temp_ability_token);
                            $temp_player_info = $target_player->export_array();
                            mmrpg_game_unlock_ability($temp_player_info, $temp_robot_info, $this_reward, true);
                            if ($temp_robot_info['robot_original_player'] == $temp_player_info['player_token']){ mmrpg_game_unlock_ability($temp_player_info, false, $this_reward); }
                            else { mmrpg_game_unlock_ability(array('player_token' => $temp_robot_info['robot_original_player']), false, $this_reward, true); }
                            //$_SESSION['GAME']['values']['battle_rewards'][$target_player_token]['player_robots'][$temp_robot_token]['robot_abilities'][$temp_ability_token] = $this_reward;

                        }

                    }
                }

            }

            // -- ITEM REWARDS -- //

            // Define the temp player rewards array
            $target_player_rewards = array();

            // Define the chance multiplier and start at one
            $temp_chance_multiplier = $trigger_options['item_multiplier'];

            // Increase the item chance multiplier if one is set for the stage
            if (isset($this_battle->field->field_multipliers['items'])){ $temp_chance_multiplier = ($temp_chance_multiplier * $this_battle->field->field_multipliers['items']); }

            // Increse the multiplier if this is an empty core robot
            if ($this_robot->robot_core == 'empty' || $this_robot->robot_core2 == 'empty'){ $temp_chance_multiplier = $temp_chance_multiplier * 2; }

            // Collect the current battle item counts for reference
            $current_items_counts = !empty($_SESSION['GAME']['values']['battle_items']) ? $_SESSION['GAME']['values']['battle_items'] : array();
            $num_existing_small_screws = !empty($current_items_counts['small-screw']) ? $current_items_counts['small-screw'] : 0;
            $num_existing_large_screws = !empty($current_items_counts['large-screw']) ? $current_items_counts['large-screw'] : 0;

            // Define the available item drops for this battle
            $target_player_rewards['items'] = !empty($this_battle->battle_rewards['items']) ? $this_battle->battle_rewards['items'] : array();

            // If this robot was a MECHA class, it may drop PELLETS and SMALL SCREWS
            if ($this_robot->robot_class == 'mecha'){

                // Append the Tier I screw drops
                if ($num_existing_small_screws < MMRPG_SETTINGS_ITEMS_MAXQUANTITY){ $target_player_rewards['items'][] =  array('chance' => 30, 'token' => 'small-screw', 'min' => 1, 'max' => 3); }

            }
            // If this robot was a MASTER class, it may drop PELLETS, CAPSULES and SMALL, LARGE SCREWS
            elseif ($this_robot->robot_class == 'master'){

                // Append the Tier I screw drops
                if ($num_existing_small_screws < MMRPG_SETTINGS_ITEMS_MAXQUANTITY){ $target_player_rewards['items'][] =  array('chance' => 30, 'token' => 'small-screw', 'min' => 3, 'max' => 6); }
                // Append the Tier II screw drops
                if ($num_existing_large_screws < MMRPG_SETTINGS_ITEMS_MAXQUANTITY){ $target_player_rewards['items'][] =  array('chance' => 60, 'token' => 'large-screw', 'min' => 1, 'max' => 3); }

            }
            // If this robot was a BOSS class, it may drop PELLETS, CAPSULES and SMALL, LARGE SCREWS and ....?
            elseif ($this_robot->robot_class == 'boss'){

                // Append the Tier I screw drops
                if ($num_existing_small_screws < MMRPG_SETTINGS_ITEMS_MAXQUANTITY){ $target_player_rewards['items'][] =  array('chance' => 60, 'token' => 'small-screw', 'min' => 6, 'max' => 9); }
                // Append the Tier II screw drops
                if ($num_existing_large_screws < MMRPG_SETTINGS_ITEMS_MAXQUANTITY){ $target_player_rewards['items'][] =  array('chance' => 90, 'token' => 'large-screw', 'min' => 3, 'max' => 6); }
                // Append the Tier III screw drops
                //$target_player_rewards['items'][] =  array('chance' => 90, 'token' => 'hyper-screw', 'min' => 1, 'max' => 3);

            }

            // If a weakness was triggered, we need to switch to a different item set (SHARDS / CORES)
            if (!empty($this_robot->flags['triggered_weakness'])){

                // Collect the shard or core type for this robot
                $temp_drop_type = !empty($this_robot->robot_core) ? $this_robot->robot_core : 'none';
                $temp_drop_kind = $this_robot->robot_class == 'mecha' ? 'shard' : 'core';
                $num_existing_shards = !empty($current_items_counts[$temp_drop_type.'-shard']) ? $current_items_counts[$temp_drop_type.'-shard'] : 0;
                $num_existing_cores = !empty($current_items_counts[$temp_drop_type.'-core']) ? $current_items_counts[$temp_drop_type.'-core'] : 0;

                /*
                $this_battle->events_create(false, false, 'DEBUG',
                    '$temp_drop_type = '.$temp_drop_type.
                    '<br /> $temp_drop_kind = '.$temp_drop_kind.
                    '<br /> $num_existing_shards = '.$num_existing_shards.
                    '<br /> $num_existing_cores = '.$num_existing_cores
                    );
                */

                // If we're allowed to drop this item at this time, continue
                if ($temp_drop_type !== 'empty' && (
                    ($temp_drop_kind === 'shard' && ($num_existing_shards < MMRPG_SETTINGS_SHARDS_MAXQUANTITY || $num_existing_cores < MMRPG_SETTINGS_CORES_MAXQUANTITY))
                    || ($temp_drop_kind === 'core' && $num_existing_cores < MMRPG_SETTINGS_CORES_MAXQUANTITY)
                    )){

                    // Clear the existing set of items as they're not relevant any more
                    $target_player_rewards['items'] = array();

                    // If this robot was a MECHA class it will drop a SHARD, else if a MASTER/BOSS class it will drop a CORE
                    if ($temp_drop_kind === 'shard'){
                        $target_player_rewards['items'][] =  array('chance' => 100, 'token' => $temp_drop_type.'-shard', 'min' => 1, 'max' => 1);
                    } else if ($temp_drop_kind === 'core'){
                        $target_player_rewards['items'][] =  array('chance' => 100, 'token' => $temp_drop_type.'-core', 'min' => 1, 'max' => 1);
                    }

                }

            }

            // If the target holds a Fortune Module, increase the chance of dropps
            $temp_fortune_module = false;
            if ($target_robot->robot_item == 'fortune-module'){
                $temp_fortune_module = true;
                $temp_chance_multiplier = ceil($temp_chance_multiplier * 2);
                foreach ($target_player_rewards['items'] AS $key => $info){
                    if ($info['min'] < $info['max']){ $info['min'] = $info['max'] - 1; }
                    $target_player_rewards['items'][$key] = $info;
                }
            }

            // Shuffle the rewards so it doesn't look to formulaic
            shuffle($target_player_rewards['items']);

            /*
            // DEBUG
            $this_battle->events_create(false, false, 'DEBUG',
                '$temp_chance_multiplier = '.$temp_chance_multiplier.
                '<br /> $target_player_rewards[\'items\'] = '.count($target_player_rewards['items']).
                '<br /> '.preg_replace('/\s+/', ' ', print_r($target_player_rewards['items'], true))
                );
            */

            // Loop through the ability rewards for this robot if set and NOT demo mode
            if (empty($_SESSION['GAME']['DEMO'])
                && !empty($target_player_rewards['items'])
                && empty($this_battle->flags['player_battle'])
                && empty($this_battle->flags['challenge_battle'])
                ){

                // Calculate the drop result based on success vs failure values
                $temp_success_value = $this_robot->robot_class == 'mecha' ? 50 : 25;
                $temp_success_value = ceil($temp_success_value * $temp_chance_multiplier);
                if ($temp_success_value > 100){ $temp_success_value = 100; }
                $temp_failure_value = 100 - $temp_success_value;
                $temp_dropping_result = $temp_success_value == 100 ? 'success' : $this_battle->weighted_chance(array('success', 'failure'), array($temp_success_value, $temp_failure_value));

                /*
                $this_battle->events_create(false, false, 'DEBUG',
                    '$temp_success_value = '.$temp_success_value.
                    '<br /> $temp_failure_value = '.$temp_failure_value.
                    '<br /> $temp_dropping_result = '.$temp_dropping_result
                    );
                */

                // If the drop was a success, calculate the details
                if ($temp_dropping_result == 'success'){

                    // Define variables to hold totals then loop to calculate them
                    $temp_value_total = 0;
                    $temp_count_total = 0;
                    foreach ($target_player_rewards['items'] AS $item_reward_key => $item_reward_info){
                        $temp_value_total += $item_reward_info['chance'];
                        $temp_count_total += 1;
                    }

                    // Generate the tokens and weights then pick a random item
                    $temp_item_tokens = array();
                    $temp_item_weights = array();
                    foreach ($target_player_rewards['items'] AS $item_reward_key => $item_reward_info){
                        $temp_item_tokens[] = $item_reward_info['token'];
                        $temp_item_weights[] = ceil(($item_reward_info['chance'] / $temp_value_total) * 100);
                    }
                    $random_item_token = $this_battle->weighted_chance($temp_item_tokens, $temp_item_weights);
                    $random_item_key = array_search($random_item_token, $temp_item_tokens);
                    $random_item_info = $target_player_rewards['items'][$random_item_key];

                    // Define the quantity multiplier based on chance and rarity
                    if (!isset($random_item_info['min'])){ $random_item_info['min'] = 1; }
                    if (!isset($random_item_info['max'])){ $random_item_info['max'] = $random_item_info['min']; }
                    if ($random_item_info['min'] != $random_item_info['max']){
                        $temp_quantity_dropped = mt_rand($random_item_info['min'], $random_item_info['max']);
                    } else {
                        $temp_quantity_dropped = $random_item_info['min'];
                    }

                    // Trigger the actual item drop function on for the player
                    rpg_player::trigger_item_drop($this_battle, $target_player, $target_robot, $this_robot, $item_reward_key, $random_item_token, $temp_quantity_dropped);

                }
            }

        }

        // If the player has replacement robots and the knocked-out one was active
        if ($this_player->counters['robots_active'] > 0){

            // Try to find at least one active POSITION robot before requiring a switch
            $has_active_positon_robot = false;
            foreach ($this_player->values['robots_active'] AS $key => $robot){
                if ($robot['robot_position'] == 'active'
                    && $robot['robot_id'] != $this_robot->robot_id){
                    $has_active_positon_robot = true;
                }
            }

            // If the player does NOT have an active position robot, trigger a switch
            if (!$has_active_positon_robot){

                // If the target player is not on autopilot, require input
                if ($this_player->player_autopilot == false){
                    // Empty the action queue to allow the player switch time
                    $this_battle->actions = array();
                }
                // Otherwise, if the target player is on autopilot, automate input
                elseif ($this_player->player_autopilot == true){  // && $this_player->player_next_action != 'switch'

                    // Empty the action queue to allow the player switch time
                    $this_battle->actions = array();

                    // Remove any previous switch actions for this player
                    $backup_switch_actions = $this_battle->actions_extract(array(
                        'this_player_id' => $this_player->player_id,
                        'this_action' => 'switch'
                        ));

                    //$this_battle->events_create(false, false, 'DEBUG DEBUG', 'This is a test from inside the dead trigger ['.count($backup_switch_actions).'].');

                    // If there were any previous switches removed
                    if (!empty($backup_switch_actions)){
                        // If the target robot was faster, it should attack first
                        if ($this_robot->robot_speed > $target_robot->robot_speed){
                            // Prepend an ability action for this robot
                            $this_battle->actions_prepend(
                                $this_player,
                                $this_robot,
                                $target_player,
                                $target_robot,
                                'ability',
                                ''
                                );
                        }
                        // Otherwise, if the target was slower, if should attack second
                        else {
                            // Prepend an ability action for this robot
                            $this_battle->actions_append(
                                $this_player,
                                $this_robot,
                                $target_player,
                                $target_robot,
                                'ability',
                                ''
                                );
                        }
                    }

                    // Prepend a switch action for the target robot
                    $this_battle->actions_prepend(
                        $this_player,
                        $this_robot,
                        $target_player,
                        $target_robot,
                        'switch',
                        ''
                        );

                }

            }

        }
        // Otherwise, if the target is out of robots...
        else {

            // Trigger a battle complete action
            //$this_battle->battle_complete_trigger($target_player, $target_robot, $this_player, $this_robot, '', '');

        }

        /*
        // If this robot was a mecha, remove it from view by incrementing its key
        if ($this_robot->robot_class == 'mecha'){
            $this_robot->robot_key += 1000;
            $this_robot->update_session();
        }
        */

        // Either way, set the hidden flag on the robot
        $this_robot->flags['apply_disabled_state'] = true;
        if ($this_robot->robot_energy < 1 && $this_robot->robot_status != 'disabled'){ $this_robot->robot_status = 'disabled'; }
        if ($this_robot->robot_status == 'disabled' && $this_robot->robot_position == 'bench'){ $this_robot->flags['hidden'] = true; }
        $this_robot->update_session();

        // -- ROBOT UNLOCKING STUFF!!! -- //

        // Check if this target winner was a HUMAN player and update the robot database counter for defeats
        if ($target_player->player_side == 'left'){
            // Add this robot to the global robot database array
            if (!isset($_SESSION['GAME']['values']['robot_database'][$this_robot->robot_token])){ $_SESSION['GAME']['values']['robot_database'][$this_robot->robot_token] = array('robot_token' => $this_robot->robot_token); }
            if (!isset($_SESSION['GAME']['values']['robot_database'][$this_robot->robot_token]['robot_defeated'])){ $_SESSION['GAME']['values']['robot_database'][$this_robot->robot_token]['robot_defeated'] = 0; }
            $_SESSION['GAME']['values']['robot_database'][$this_robot->robot_token]['robot_defeated']++;
        }

        // Check if this battle has any robot rewards to unlock and the winner was a HUMAN player
        if ($target_player->player_side == 'left' && !empty($this_battle->battle_rewards['robots'])){

            // Only continue if this robot is unlockable
            if (!empty($this_robot->flags['robot_is_unlockable'])){

                // Scan the reward array to find this robot's key
                $temp_reward_key = false;
                foreach ($this_battle->battle_rewards['robots'] AS $key => $reward){
                    if ($reward['token'] == $this_robot->robot_token){
                        $temp_reward_key = $key;
                        break;
                    }
                }

                // Calculate whether or not this robot is currently corrupted
                $temp_is_corrupted = false;
                if (!empty($this_robot->history['triggered_damage_types'])){
                    foreach ($this_robot->history['triggered_damage_types'] AS $types){
                        if (!empty($types)){
                            $temp_is_corrupted = true;
                            $this_robot->set_flag('robot_is_unlockable_corrupted', $temp_is_corrupted);
                            break;
                        }
                    }
                }

                // If this robot's data has NOT been corrupted, unlock is successful
                if ($temp_reward_key !== false
                    && !$temp_is_corrupted){

                    // Collect this reward's information
                    $robot_reward_info = $this_battle->battle_rewards['robots'][$temp_reward_key];
                    $robot_reward_index = rpg_robot::get_index_info($robot_reward_info['token']);

                    // Collect or define the robot points and robot rewards variables
                    $this_robot_token = $robot_reward_info['token'];
                    $this_robot_level = !empty($robot_reward_info['level']) ? $robot_reward_info['level'] : 1;
                    $this_robot_experience = !empty($robot_reward_info['experience']) ? $robot_reward_info['experience'] : 0;
                    $this_robot_rewards = !empty($robot_info['robot_rewards']) ? $robot_info['robot_rewards'] : array();

                    // Create the temp new robot for the player
                    $temp_unlock_robot_data = array();
                    $temp_unlock_robot_data['robot_id'] = MMRPG_SETTINGS_GUEST_ID + $robot_reward_index['robot_id'] + 1;
                    $temp_unlock_robot_data['robot_token'] = $this_robot_token;
                    $temp_unlock_robot_data['robot_level'] = $this_robot_level;
                    $temp_unlock_robot_data['robot_experience'] = $this_robot_experience;
                    $temp_unlocked_robot = rpg_game::get_robot($this_battle, $target_player, $temp_unlock_robot_data);

                    // Automatically unlock this robot for use in battle
                    $temp_unlocked_player = $mmrpg_index_players[$target_player->player_token];
                    $temp_was_unlocked = mmrpg_game_unlock_robot($temp_unlocked_player, array_merge($robot_reward_index, $temp_unlock_robot_data), true, true);

                    /*
                    // DEBUG
                    $event_body = $this_robot->robot_token.' unlock attempt:';
                    $event_body .= '<br /> key: '.$temp_reward_key.' ';
                    $event_body .= '<br /> in rewards: '.json_encode($this_battle->battle_rewards['robots']).' ';
                    $event_body .= '<br /> picked: '.json_encode($robot_reward_info).' ';
                    $event_body .= '<br /> unlock: '.json_encode($temp_unlock_robot_data).' ';
                    $event_body .= '<br /> final token: '.$temp_unlocked_robot->robot_token.' ';
                    $event_body .= '<br /> unlocked: '.($temp_was_unlocked ? 'true' : 'false').' ';
                    $event_options = array();
                    $event_options['console_show_target'] = false;
                    $event_options['this_header_float'] = $target_player->player_side;
                    $event_options['this_body_float'] = $target_player->player_side;
                    $event_options['this_robot_image'] = 'mug';
                    $this_battle->events_create($temp_unlocked_robot, false, 'debug', $event_body, $event_options);
                    */

                    // Display the robot reward message markup
                    if ($temp_was_unlocked){
                        $event_header = $temp_unlocked_robot->robot_name.' Unlocked';
                        $event_body = rpg_battle::random_positive_word().' '.$target_player->print_name().' unlocked new robot data!<br />';
                        $event_body .= $temp_unlocked_robot->print_name().' can now be used in battle!';
                        $event_options = array();
                        $event_options['console_show_target'] = false;
                        $event_options['this_header_float'] = $target_player->player_side;
                        $event_options['this_body_float'] = $target_player->player_side;
                        $event_options['this_robot_image'] = 'mug';
                        $temp_unlocked_robot->robot_frame = 'base';
                        $temp_unlocked_robot->update_session();
                        $this_battle->events_create($temp_unlocked_robot, false, $event_header, $event_body, $event_options);
                        unset($temp_unlocked_robot);
                    }

                } elseif ($temp_reward_key !== false
                    && $temp_is_corrupted){

                    // Remove this robot from the rewards as they're corrupted
                    $this_robot->flags['robot_is_unlockable'] = false;
                    $this_robot->update_session();
                    unset($this_battle->battle_rewards['robots'][$temp_reward_key]);
                    $this_battle->update_session();

                }

            }


        }

        // Return true on success
        return true;

    }

}
?>