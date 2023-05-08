<?
/**
 * Mega Man RPG Item Recovery
 * <p>The item-specific battle damage class for the Mega Man RPG Prototype.</p>
 */
class rpg_item_recovery extends rpg_recovery {

    // Define a trigger for inflicting all types of recovery on this robot
    public static function trigger_robot_recovery($this_robot, $target_robot, $this_item, $recovery_amount, $trigger_disabled = true, $trigger_options = array()){
        global $db;

        // DEBUG
        $debug = '';

        // Collect a reference to the actual battle object
        $this_battle = $this_robot->battle;

        // Generate default trigger options if not set
        if (!isset($trigger_options['apply_modifiers'])){ $trigger_options['apply_modifiers'] = true; }
        if (!isset($trigger_options['apply_type_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_type_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['apply_core_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_core_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['apply_position_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_position_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['apply_field_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_field_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['apply_stat_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_stat_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['apply_attachment_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_attachment_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['apply_attachment_recovery_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_attachment_recovery_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['referred_recovery'])){ $trigger_options['referred_recovery'] = false; }
        if (!isset($trigger_options['referred_recovery_id'])){ $trigger_options['referred_recovery_id'] = 0; }
        if (!isset($trigger_options['referred_recovery_stats'])){ $trigger_options['referred_recovery_stats'] = array(); }
        if (!isset($trigger_options['force_flags'])){ $trigger_options['force_flags'] = array(); }

        // If this is referred recovery, collect the actual target
        if (!empty($trigger_options['referred_recovery']) && !empty($trigger_options['referred_recovery_id'])){
            //$debug .= "<br /> referred_recovery is true and created by robot ID {$trigger_options['referred_recovery_id']} ";
            $new_target_robot = $this_robot->battle->find_target_robot($trigger_options['referred_recovery_id']);
            if (!empty($new_target_robot) && isset($new_target_robot->robot_token)){
                //$debug .= "<br /> \$new_target_robot was found! {$new_target_robot->robot_token} ";
                unset($target_player, $target_robot);
                $target_player = $new_target_robot->player;
                $target_robot = $new_target_robot;
            } else {
                //$debug .= "<br /> \$new_target_robot returned ".print_r($new_target_robot, true)." ";
                $trigger_options['referred_recovery'] = false;
                $trigger_options['referred_recovery_id'] = false;
                $trigger_options['referred_recovery_stats'] = array();
            }
        }

        // Backup this and the target robot's frames to revert later
        $this_robot_backup_frame = $this_robot->robot_frame;
        $this_player_backup_frame = $this_robot->player->player_frame;
        $target_robot_backup_frame = $target_robot->robot_frame;
        $target_player_backup_frame = $target_robot->player->player_frame;
        $this_item_backup_frame = $this_item->item_frame;

        // Collect this and the target's stat levels for later
        $this_robot_stats = $this_robot->get_stats();
        $target_robot_stats = $target_robot->get_stats();
        if (!empty($trigger_options['referred_recovery_stats'])){
            $target_robot_stats = array_merge($target_robot_stats, $trigger_options['referred_recovery_stats']);
        }

        // Define the event console options
        $event_options = array();
        $event_options['console_container_height'] = 1;
        $event_options['this_other_item'] = $this_item;
        $event_options['this_item_results'] = array();

        // Create an options object for this function and populate
        $options = rpg_game::new_options_object();
        $options->recovery_target = $this_robot;
        $options->recovery_initiator = $target_robot;
        $options->recovery_amount = $recovery_amount;
        $options->trigger_options = &$trigger_options;
        $options->event_options = &$event_options;
        $extra_objects = array('this_item' => $this_item, 'options' => $options);

        // Empty any text from the previous item result
        $this_item->item_results['this_text'] = '';

        // Update the recovery to whatever was supplied in the argument
        //if ($this_item->recovery_options['recovery_percent'] && $options->recovery_amount > 100){ $options->recovery_amount = 100; }
        $this_item->recovery_options['recovery_amount'] = $options->recovery_amount;

        // Collect the recovery amount argument from the function
        $this_item->item_results['this_amount'] = $options->recovery_amount;
        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | to('.$this_robot->robot_id.':'.$this_robot->robot_token.') vs from('.$target_robot->robot_id.':'.$target_robot->robot_token.') | recovery_start_amount |<br /> '.'amount:'.$this_item->item_results['this_amount'].' | '.'percent:'.($this_item->recovery_options['recovery_percent'] ? 'true' : 'false').' | '.'kind:'.$this_item->recovery_options['recovery_kind'].' | type1:'.(!empty($this_item->recovery_options['recovery_type']) ? $this_item->recovery_options['recovery_type'] : 'none').' | type2:'.(!empty($this_item->recovery_options['recovery_type2']) ? $this_item->recovery_options['recovery_type2'] : 'none').'');

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-item_trigger-recovery_before', $extra_objects);
        $target_robot->trigger_custom_function('rpg-item_trigger-recovery_before', $extra_objects);
        if ($options->return_early){ return $options->return_value; }

        // DEBUG
        if (!empty($debug)){ $debug .= ' <br /> '; }
        foreach ($trigger_options AS $key => $value){
            if ($value === true){ $debug .= $key.'=true; ';  }
            elseif ($value === false){ $debug .= $key.'=false; ';  }
            elseif (is_array($value) && !empty($value)){ $debug .= $key.'='.implode(',', $value).'; '; }
            elseif (is_array($value)){ $debug .= $key.'=[]; '; }
            else { $debug .= $key.'='.$value.'; '; }
        }
        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' : recovery_trigger_options : '.$debug);

        // Only apply modifiers if they have not been disabled
        if ($trigger_options['apply_modifiers'] != false){

            // Skip all weakness, resistance, etc. calculations if robot is targetting self
            if ($trigger_options['apply_type_modifiers'] != false && ($this_robot->robot_id != $target_robot->robot_id || $trigger_options['referred_recovery'])){

                // If this robot has weakness to the item (based on type)
                if ($this_robot->has_weakness($this_item->recovery_options['recovery_type']) && !$this_robot->has_affinity($this_item->recovery_options['recovery_type2'])){
                    //$this_item->item_results['counter_weaknesses'] += 1;
                    //$this_item->item_results['flag_weakness'] = true;
                    return $this_robot->trigger_damage($target_robot, $this_item, $options->recovery_amount);
                } else {
                    $this_item->item_results['flag_weakness'] = false;
                }

                // If this robot has weakness to the item (based on type2)
                if ($this_robot->has_weakness($this_item->recovery_options['recovery_type2']) && !$this_robot->has_affinity($this_item->recovery_options['recovery_type'])){
                    $this_item->item_results['counter_weaknesses'] += 1;
                    $this_item->item_results['flag_weakness'] = true;
                    return $this_robot->trigger_damage($target_robot, $this_item, $options->recovery_amount);
                }

                // If target robot has affinity to the item (based on type)
                if ($this_robot->has_affinity($this_item->recovery_options['recovery_type']) && !$this_robot->has_weakness($this_item->recovery_options['recovery_type2'])){
                    $this_item->item_results['counter_affinities'] += 1;
                    $this_item->item_results['flag_affinity'] = true;
                } else {
                    $this_item->item_results['flag_affinity'] = false;
                }

                // If target robot has affinity to the item (based on type2)
                if ($this_robot->has_affinity($this_item->recovery_options['recovery_type2']) && !$this_robot->has_weakness($this_item->recovery_options['recovery_type'])){
                    $this_item->item_results['counter_affinities'] += 1;
                    $this_item->item_results['flag_affinity'] = true;
                }

                // If target robot has resistance tp the item (based on type)
                if ($this_robot->has_resistance($this_item->recovery_options['recovery_type'])){
                    $this_item->item_results['counter_resistances'] += 1;
                    $this_item->item_results['flag_resistance'] = true;
                } else {
                    $this_item->item_results['flag_resistance'] = false;
                }

                // If target robot has resistance tp the item (based on type2)
                if ($this_robot->has_resistance($this_item->recovery_options['recovery_type2'])){
                    $this_item->item_results['counter_resistances'] += 1;
                    $this_item->item_results['flag_resistance'] = true;
                }

                // If target robot has immunity to the item (based on type)
                if ($this_robot->has_immunity($this_item->recovery_options['recovery_type'])){
                    $this_item->item_results['counter_immunities'] += 1;
                    $this_item->item_results['flag_immunity'] = true;
                } else {
                    $this_item->item_results['flag_immunity'] = false;
                }

                // If target robot has immunity to the item (based on type2)
                if ($this_robot->has_immunity($this_item->recovery_options['recovery_type2'])){
                    $this_item->item_results['counter_immunities'] += 1;
                    $this_item->item_results['flag_immunity'] = true;
                }

                // If any force flags have been applied, it's best to parse them now
                if (in_array('flag_weakness', $trigger_options['force_flags'])){
                    $this_item->item_results['counter_weaknesses'] += 1;
                    $this_item->item_results['flag_weakness'] = true;
                }
                if (in_array('flag_affinity', $trigger_options['force_flags'])){
                    $this_item->item_results['counter_affinities'] += 1;
                    $this_item->item_results['flag_affinity'] = true;
                }
                if (in_array('flag_resistance', $trigger_options['force_flags'])){
                    $this_item->item_results['counter_resistances'] += 1;
                    $this_item->item_results['flag_resistance'] = true;
                }
                if (in_array('flag_immunity', $trigger_options['force_flags'])){
                    $this_item->item_results['counter_immunities'] += 1;
                    $this_item->item_results['flag_immunity'] = true;
                }

            }

            // Apply core boosts if allowed to
            if ($trigger_options['apply_core_modifiers'] != false){

                // Collect this item's type tokens if they exist
                $item_type_token = !empty($this_item->recovery_options['recovery_type']) ? $this_item->recovery_options['recovery_type'] : 'none';
                $item_type_token2 = !empty($this_item->recovery_options['recovery_type2']) ? $this_item->recovery_options['recovery_type2'] : '';

                // Collect this robot's core type tokens if they exist
                $core_type_token = !empty($target_robot->robot_core) ? $target_robot->robot_core : 'none';
                $core_type_token2 = !empty($target_robot->robot_core2) ? $target_robot->robot_core2 : '';

                // Collect this robot's held robot core if it exists
                $core_type_token3 = '';
                if (!empty($target_robot->robot_item) && strstr($target_robot->robot_item, '-core')){
                    $core_type_token3 = str_replace('-core', '', $target_robot->robot_item);
                }

                // Define the coreboost flag and default to false
                $this_item->item_results['flag_coreboost'] = false;

                // Define an array to hold individual coreboost values
                $item_coreboost_multipliers = array();

                // Check this item's FIRST type for multiplier matches
                if (!empty($item_type_token)){

                    // Apply primary robot core multipliers if they exist
                    if ($item_type_token == $core_type_token){
                        $this_item->item_results['counter_coreboosts']++;
                        $item_coreboost_multipliers[] = MMRPG_SETTINGS_COREBOOST_MULTIPLIER;
                    }
                    // Apply secondary robot core multipliers if they exist
                    elseif ($item_type_token == $core_type_token2){
                        $this_item->item_results['counter_coreboosts']++;
                        $item_coreboost_multipliers[] = MMRPG_SETTINGS_COREBOOST_MULTIPLIER;
                    }

                    // Apply held robot core multipliers if they exist
                    if ($item_type_token == $core_type_token3){
                        $this_item->item_results['counter_coreboosts']++;
                        $item_coreboost_multipliers[] = MMRPG_SETTINGS_SUBCOREBOOST_MULTIPLIER;
                    }

                }

                // Check this item's SECOND type for multiplier matches
                if (!empty($item_type_token2)){

                    // Apply primary robot core multipliers if they exist
                    if ($item_type_token2 == $core_type_token){
                        $this_item->item_results['counter_coreboosts']++;
                        $item_coreboost_multipliers[] = MMRPG_SETTINGS_COREBOOST_MULTIPLIER;
                    }
                    // Apply secondary robot core multipliers if they exist
                    elseif ($item_type_token2 == $core_type_token2){
                        $this_item->item_results['counter_coreboosts']++;
                        $item_coreboost_multipliers[] = MMRPG_SETTINGS_COREBOOST_MULTIPLIER;
                    }

                    // Apply held robot core multipliers if they exist
                    if ($item_type_token2 == $core_type_token3){
                        $this_item->item_results['counter_coreboosts']++;
                        $item_coreboost_multipliers[] = MMRPG_SETTINGS_SUBCOREBOOST_MULTIPLIER;
                    }

                }

                // If any force flags have been applied, it's best to parse them now
                if (in_array('flag_coreboost', $trigger_options['force_flags'])){
                    $this_item->item_results['counter_coreboosts'] += 1;
                    $this_item->item_results['flag_coreboost'] = true;
                }

                // If any coreboosts were present, update the flag
                if (!empty($this_item->item_results['counter_coreboosts'])){
                    $this_item->item_results['flag_coreboost'] = true;
                }

            }

            // Apply position boosts if allowed to
            if ($trigger_options['apply_position_modifiers'] != false){

                // If this robot is not in the active position
                if ($this_robot->robot_position != 'active'){
                    // Collect the current key of the robot and apply recovery mods
                    $temp_recovery_key = $this_robot->robot_key + 1;
                    $temp_recovery_resistor = (10 - $temp_recovery_key) / 10;
                    $new_recovery_amount = rpg_functions::round_ceil($options->recovery_amount * $temp_recovery_resistor);
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | position_modifier_recovery | '.$options->recovery_amount.' = rpg_functions::round_ceil('.$options->recovery_amount.' * '.$temp_recovery_resistor.') = '.$new_recovery_amount.'');
                    $options->recovery_amount = $new_recovery_amount;
                }

            }

        }

        // Apply field multipliers preemtively if there are any
        if ($trigger_options['apply_field_modifiers'] != false && $this_item->recovery_options['recovery_modifiers'] && !empty($this_robot->field->field_multipliers)){

            // Collect the multipliters for easier
            $field_multipliers = $this_robot->field->field_multipliers;

            // Collect the item types else "none" for multipliers
            $temp_item_recovery_type = !empty($this_item->recovery_options['recovery_type']) ? $this_item->recovery_options['recovery_type'] : 'none';
            $temp_item_recovery_type2 = !empty($this_item->recovery_options['recovery_type2']) ? $this_item->recovery_options['recovery_type2'] : '';

            // If there's a recovery booster, apply that first
            if (isset($field_multipliers['recovery'])){
                $new_recovery_amount = rpg_functions::round_ceil($options->recovery_amount * $field_multipliers['recovery']);
                $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | field_multiplier_recovery | '.$options->recovery_amount.' = rpg_functions::round_ceil('.$options->recovery_amount.' * '.$field_multipliers['recovery'].') = '.$new_recovery_amount.'');
                $options->recovery_amount = $new_recovery_amount;
            }

            // Loop through all the other type multipliers one by one if this item has a type
            $skip_types = array('recovery', 'recovery', 'experience');
            foreach ($field_multipliers AS $temp_type => $temp_multiplier){
                // Skip non-type and special fields for this calculation
                if (in_array($temp_type, $skip_types)){ continue; }
                // If this item's type matches the multiplier, apply it
                if ($temp_item_recovery_type == $temp_type){
                    $new_recovery_amount = rpg_functions::round_ceil($options->recovery_amount * $temp_multiplier);
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | field_multiplier_'.$temp_type.' | '.$options->recovery_amount.' = rpg_functions::round_ceil('.$options->recovery_amount.' * '.$temp_multiplier.') = '.$new_recovery_amount.'');
                    $options->recovery_amount = $new_recovery_amount;
                }
                // If this item's type2 matches the multiplier, apply it
                if ($temp_item_recovery_type2 == $temp_type){
                    $new_recovery_amount = rpg_functions::round_ceil($options->recovery_amount * $temp_multiplier);
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | field_multiplier_'.$temp_type.' | '.$options->recovery_amount.' = rpg_functions::round_ceil('.$options->recovery_amount.' * '.$temp_multiplier.') = '.$new_recovery_amount.'');
                    $options->recovery_amount = $new_recovery_amount;
                }
            }


        }

        // Update the item results with the the trigger kind and recovery details
        $this_item->item_results['trigger_kind'] = 'recovery';
        $this_item->item_results['recovery_kind'] = $this_item->recovery_options['recovery_kind'];
        $this_item->item_results['recovery_type'] = $this_item->recovery_options['recovery_type'];
        $this_item->item_results['recovery_type2'] = !empty($this_item->recovery_options['recovery_type2']) ? $this_item->recovery_options['recovery_type2'] : '';

        // If the success rate was not provided, auto-calculate
        if ($this_item->recovery_options['success_rate'] == 'auto'){
            // If this robot is targetting itself, default to item accuracy
            if ($this_robot->robot_id == $target_robot->robot_id){
                // Update the success rate to the item accuracy value
                $this_item->recovery_options['success_rate'] = $this_item->item_accuracy;
            }
            // Otherwise, if this robot is in speed break or item accuracy 100%
            elseif ($target_robot_stats['robot_speed'] <= 0 && $this_robot->robot_speed > 0){
                // Hard-code the success rate at 100% accuracy
                    $this_item->recovery_options['success_rate'] = 0;
            }
            // Otherwise, if this robot is in speed break or item accuracy 100%
            elseif ($this_robot->robot_speed <= 0 || $this_item->item_accuracy == 100){
                // Hard-code the success rate at 100% accuracy
                    $this_item->recovery_options['success_rate'] = 100;
            }
            // Otherwise, calculate the success rate based on relative speeds
            else {
                // Collect this item's accuracy stat for modification
                $this_item_accuracy = $this_item->item_accuracy;
                // If the target was faster/slower, boost/lower the item accuracy
                if ($target_robot_stats['robot_speed'] > $this_robot->robot_speed
                    || $target_robot_stats['robot_speed'] < $this_robot->robot_speed){
                    $this_modifier = $target_robot_stats['robot_speed'] / $this_robot->robot_speed;
                    //$this_item_accuracy = ceil($this_item_accuracy * $this_modifier);
                    $this_item_accuracy = ceil($this_item_accuracy * 0.95) + ceil(($this_item_accuracy * 0.05) * $this_modifier);
                    if ($this_item_accuracy > 100){ $this_item_accuracy = 100; }
                    elseif ($this_item_accuracy < 0){ $this_item_accuracy = 0; }
                }
                // Update the success rate to the item accuracy value
                $this_item->recovery_options['success_rate'] = $this_item_accuracy;
                //$this_item->item_results['this_text'] .= '';
            }
        }

        // If the failure rate was not provided, auto-calculate
        if ($this_item->recovery_options['failure_rate'] == 'auto'){
            // Set the failure rate to the difference of success vs failure (100% base)
            $this_item->recovery_options['failure_rate'] = 100 - $this_item->recovery_options['success_rate'];
            if ($this_item->recovery_options['failure_rate'] < 0){
                $this_item->recovery_options['failure_rate'] = 0;
            }
        }

        // If this robot is in speed break, increase success rate, reduce failure
        if ($this_robot->robot_speed == 0 && $this_item->recovery_options['success_rate'] > 0){
            $this_item->recovery_options['success_rate'] = ceil($this_item->recovery_options['success_rate'] * 2);
            $this_item->recovery_options['failure_rate'] = ceil($this_item->recovery_options['failure_rate'] / 2);
        }
        // If the target robot is in speed break, decease the success rate, increase failure
        elseif ($target_robot_stats['robot_speed'] == 0 && $this_item->recovery_options['success_rate'] > 0){
            $this_item->recovery_options['success_rate'] = ceil($this_item->recovery_options['success_rate'] / 2);
            $this_item->recovery_options['failure_rate'] = ceil($this_item->recovery_options['failure_rate'] * 2);
        }

        // If success rate is at 100%, auto-set the result to success
        if ($this_item->recovery_options['success_rate'] == 100){
            // Set this item result as a success
            $this_item->recovery_options['failure_rate'] = 0;
            $this_item->item_results['this_result'] = 'success';
        }
        // Else if the success rate is at 0%, auto-set the result to failure
        elseif ($this_item->recovery_options['success_rate'] == 0){
            // Set this item result as a failure
            $this_item->recovery_options['failure_rate'] = 100;
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise, use a weighted random generation to get the result
        else {
            // Calculate whether this attack was a success, based on the success vs. failure rate
            $this_item->item_results['this_result'] = $this_robot->battle->weighted_chance(
                array('success','failure'),
                array($this_item->recovery_options['success_rate'], $this_item->recovery_options['failure_rate'])
                );
        }

        // If this is ENERGY recovery and this robot is already at full health
        if ($this_item->recovery_options['recovery_kind'] == 'energy' && $this_robot->robot_energy >= $this_robot->robot_base_energy){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // If this is WEAPONS recovery and this robot is already at full ammo
        elseif ($this_item->recovery_options['recovery_kind'] == 'weapons' && $this_robot->robot_weapons >= $this_robot->robot_base_weapons){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise if ATTACK recovery but attack is already at 9999
        elseif ($this_item->recovery_options['recovery_kind'] == 'attack' && $this_robot->robot_attack >= MMRPG_SETTINGS_STATS_MAX){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise if DEFENSE recovery but defense is already at 9999
        elseif ($this_item->recovery_options['recovery_kind'] == 'defense' && $this_robot->robot_defense >= MMRPG_SETTINGS_STATS_MAX){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise if SPEED recovery but speed is already at 9999
        elseif ($this_item->recovery_options['recovery_kind'] == 'speed' && $this_robot->robot_speed >= MMRPG_SETTINGS_STATS_MAX){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }

        // If this robot has immunity to the item, hard-code a failure result
        if ($this_item->item_results['flag_immunity']){
            $this_item->item_results['this_result'] = 'failure';
            $this_robot->flags['triggered_immunity'] = true;
            // Generate the status text based on flags
            $this_flag_name = 'immunity_text';
            if (isset($this_item->recovery_options[$this_flag_name])){
                $this_item->item_results['this_text'] .= ' '.$this_item->recovery_options[$this_flag_name].'<br /> ';
            }
        }

        // If the attack was a success, proceed normally
        if ($this_item->item_results['this_result'] == 'success'){

            // Create the experience multiplier if not already set
            if (!isset($this_robot->field->field_multipliers['experience'])){ $this_robot->field->field_multipliers['experience'] = 1; }
            elseif ($this_robot->field->field_multipliers['experience'] < 0.1){ $this_robot->field->field_multipliers['experience'] = 0.1; }
            elseif ($this_robot->field->field_multipliers['experience'] > 9.9){ $this_robot->field->field_multipliers['experience'] = 9.9; }

            // If modifiers are not turned off
            if ($trigger_options['apply_modifiers'] != false){

                // Update this robot's internal flags based on item effects
                if (!empty($this_item->item_results['flag_weakness'])){
                    $this_robot->flags['triggered_weakness'] = true;
                    if (isset($this_robot->counters['triggered_weakness'])){ $this_robot->counters['triggered_weakness'] += 1; }
                    else { $this_robot->counters['triggered_weakness'] = 1; }
                    if ($this_item->recovery_options['recovery_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] += 0.1; }
                    }
                }
                if (!empty($this_item->item_results['flag_affinity'])){
                    $this_robot->flags['triggered_affinity'] = true;
                    if (isset($this_robot->counters['triggered_affinity'])){ $this_robot->counters['triggered_affinity'] += 1; }
                    else { $this_robot->counters['triggered_affinity'] = 1; }
                    if ($this_item->recovery_options['recovery_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] -= 0.1; }
                    }
                }
                if (!empty($this_item->item_results['flag_resistance'])){
                    $this_robot->flags['triggered_resistance'] = true;
                    if (isset($this_robot->counters['triggered_resistance'])){ $this_robot->counters['triggered_resistance'] += 1; }
                    else { $this_robot->counters['triggered_resistance'] = 1; }
                    if ($this_item->recovery_options['recovery_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] -= 0.1; }
                    }
                }
                if (!empty($this_item->item_results['flag_critical'])){
                    $this_robot->flags['triggered_critical'] = true;
                    if (isset($this_robot->counters['triggered_critical'])){ $this_robot->counters['triggered_critical'] += 1; }
                    else { $this_robot->counters['triggered_critical'] = 1; }
                    if ($this_item->recovery_options['recovery_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] += 0.1; }
                    }
                }

            }

            // Update the field session with any changes
            $this_robot->field->update_session();

            // Update this robot's frame based on recovery type
            $this_robot->robot_frame = $this_item->recovery_options['recovery_frame'];
            $this_robot->player->player_frame = ($this_robot->robot_id != $target_robot->robot_id || $trigger_options['referred_recovery']) ? 'taunt' : 'base';
            $this_item->item_frame = $this_item->recovery_options['item_success_frame'];
            $this_item->item_frame_span = $this_item->recovery_options['item_success_frame_span'];
            $this_item->item_frame_offset = $this_item->recovery_options['item_success_frame_offset'];

            // Display the success text, if text has been provided
            if (!empty($this_item->recovery_options['success_text'])){
                $this_item->item_results['this_text'] .= $this_item->recovery_options['success_text'];
            }

            // Collect the recovery amount argument from the function
            $this_item->item_results['this_amount'] = $options->recovery_amount;

            // Only apply core modifiers if allowed to
            if ($trigger_options['apply_core_modifiers'] != false){

                // If target robot has core boost for the item (based on type)
                if ($this_item->item_results['flag_coreboost']){
                    foreach ($item_coreboost_multipliers AS $temp_multiplier){
                        $this_item->item_results['this_amount'] = ceil($this_item->item_results['this_amount'] * $temp_multiplier);
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | apply_core_modifiers | x '.$temp_multiplier.' = '.$this_item->item_results['this_amount'].'');
                    }
                }

            }

            // If we're not dealing with a percentage-based amount, apply stat mods
            if ($trigger_options['apply_stat_modifiers'] != false && !$this_item->recovery_options['recovery_percent']){

                // Only apply ATTACK/DEFENSE mods if this robot is not targetting itself and it's ENERGY based recovery
                if ($this_item->recovery_options['recovery_kind'] == 'energy' && ($this_robot->robot_id != $target_robot->robot_id || $trigger_options['referred_recovery'])){

                    // Backup the current ammount before stat multipliers
                    $temp_amount_backup = $this_item->item_results['this_amount'];

                    // If this robot's defense is at absolute zero, and the target's attack isnt, OHKO
                    if ($this_robot->robot_defense <= 0 && $target_robot_stats['robot_attack'] >= 1){
                        // Set the new recovery amount to OHKO this robot
                        $temp_new_amount = $this_robot->robot_base_energy;
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | '.$this_robot->robot_token.'_defense_break | D:'.$this_robot->robot_defense.' | '.$this_item->item_results['this_amount'].' = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                    // Elseif the target robot's attack is at absolute zero, and the this's defense isnt, NOKO
                    elseif ($target_robot_stats['robot_attack'] <= 0 && $this_robot->robot_defense >= 1){
                        // Set the new recovery amount to NOKO this robot
                        $temp_new_amount = 0;
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | '.$target_robot->robot_token.'_attack_break | A:'.$target_robot_stats['robot_attack'].' | '.$this_item->item_results['this_amount'].' = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                    // Elseif this robot's defense is at absolute zero and the target's attack is too, NOKO
                    elseif ($this_robot->robot_defense <= 0 && $target_robot_stats['robot_attack'] <= 0){
                        // Set the new recovery amount to NOKO this robot
                        $temp_new_amount = 0;
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | '.$target_robot->robot_token.'_attack_break and '.$this_robot->robot_token.'_defense_break | A:'.$target_robot_stats['robot_attack'].' D:'.$this_robot->robot_defense.' | '.$this_item->item_results['this_amount'].' = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                    // Otherwise if both robots have normal stats, calculate the new amount normally
                    else {
                        // Set the new recovery amount relative to this robot's defense and the target robot's attack
                        $temp_new_amount = rpg_functions::round_ceil($this_item->item_results['this_amount'] * ($target_robot_stats['robot_attack'] / $this_robot->robot_defense));
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | normal_recovery | A:'.$target_robot_stats['robot_attack'].' D:'.$this_robot->robot_defense.' | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * ('.$target_robot_stats['robot_attack'].' / '.$this_robot->robot_defense.')) = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }

                    // If this robot started out above zero but is now absolute zero, round up
                    if ($temp_amount_backup > 0 && $this_item->item_results['this_amount'] == 0){ $this_item->item_results['this_amount'] = 1; }

                }

                // If this is a critical hit (random chance)
                $critical_rate = $this_item->recovery_options['critical_rate'];
                if ($this_robot->battle->critical_chance($critical_rate)){
                    $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] * $this_item->recovery_options['critical_multiplier'];
                    $this_item->item_results['flag_critical'] = true;
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_critical | x '.$this_item->recovery_options['critical_multiplier'].' = '.$this_item->item_results['this_amount'].'');
                } else {
                    $this_item->item_results['flag_critical'] = false;
                }

                // If any force flags have been applied, it's best to parse them now
                if (in_array('flag_critical', $trigger_options['force_flags'])){
                    $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] * $this_item->recovery_options['critical_multiplier'];
                    $this_item->item_results['flag_critical'] = true;
                }

            }

            // Only apply weakness, resistance, etc. if allowed to
            if ($trigger_options['apply_type_modifiers'] != false){

                // If this robot has an affinity to the item (based on type)
                if ($this_item->item_results['flag_affinity']){
                    $loop_count = $this_item->item_results['counter_affinities'] / ($this_item->item_results['total_strikes'] + 1);
                    for ($i = 1; $i <= $loop_count; $i++){
                        $temp_new_amount = rpg_functions::round_ceil($this_item->item_results['this_amount'] * $this_item->recovery_options['affinity_multiplier']);
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_affinity ('.$i.'/'.$loop_count.') | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * '.$this_item->recovery_options['affinity_multiplier'].') = '.$temp_new_amount.'');
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                }

                // If target robot resists the item (based on type)
                if ($this_item->item_results['flag_resistance']){
                    $loop_count = $this_item->item_results['counter_resistances'] / ($this_item->item_results['total_strikes'] + 1);
                    for ($i = 1; $i <= $loop_count; $i++){
                        $temp_new_amount = rpg_functions::round_ceil($this_item->item_results['this_amount'] * $this_item->recovery_options['resistance_multiplier']);
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_resistance ('.$i.'/'.$loop_count.') | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * '.$this_item->recovery_options['resistance_multiplier'].') = '.$temp_new_amount.'');
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                }

                // If target robot is immune to the item (based on type)
                if ($this_item->item_results['flag_immunity']){
                    $loop_count = $this_item->item_results['counter_immunities'] / ($this_item->item_results['total_strikes'] + 1);
                    for ($i = 1; $i <= $loop_count; $i++){
                        $this_item->item_results['this_amount'] = rpg_functions::round_ceil($this_item->item_results['this_amount'] * $this_item->recovery_options['immunity_multiplier']);
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_immunity ('.$i.'/'.$loop_count.') | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * '.$this_item->recovery_options['immunity_multiplier'].') = '.$temp_new_amount.'');
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                }

            }

            // Only apply attachment modifiers if allowed to and not referred
            if ($trigger_options['apply_modifiers'] != false
                && $trigger_options['referred_recovery'] == false
                && $trigger_options['apply_attachment_modifiers'] != false
                && $trigger_options['apply_attachment_recovery_modifiers'] != false
                ){

                // Pre-determine which attachment origin attachment modifiers we're allowed to apply
                $apply_origin_attachment_modifiers = isset($trigger_options['apply_origin_attachment_modifiers']) && $trigger_options['apply_origin_attachment_modifiers'] == false ? false : true;
                $apply_origin_attachment_recovery_breakers = isset($trigger_options['apply_origin_attachment_recovery_breakers']) && $trigger_options['apply_origin_attachment_recovery_breakers'] == false ? false : true;
                $apply_origin_attachment_recovery_boosters = isset($trigger_options['apply_origin_attachment_recovery_boosters']) && $trigger_options['apply_origin_attachment_recovery_boosters'] == false ? false : true;

                // If the target robot (origin of recovery) has an attachment with a recovery multiplier
                $target_robot_attachments = $target_robot->get_current_attachments();
                if ($apply_origin_attachment_modifiers
                    && !empty($target_robot_attachments)){

                    // Loop through the target robot's attachments one-by-one and apply their modifiers
                    foreach ($target_robot_attachments AS $temp_token => $temp_info){
                        $temp_token_debug = str_replace('item_', 'attachment_', $temp_token);

                        // First check to see if any basic breakers or boosters have been created for this robot
                        if (true){
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_origin_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_breaker'])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_origin_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_booster'])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery output breaker value set
                            if ($apply_origin_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_output_breaker'])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_output_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_output_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_output_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery output booster value set
                            if ($apply_origin_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_output_booster'])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_output_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_output_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_output_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($this_item->recovery_options['recovery_type'])){
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_origin_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_origin_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_booster_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_origin_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_origin_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($this_item->recovery_options['recovery_type2'])){
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_origin_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_origin_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_origin_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_output_breaker_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_origin_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_output_booster_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }

                    }

                }

                // Pre-determine which attachment target attachment modifiers we're allowed to apply
                $apply_target_attachment_modifiers = isset($trigger_options['apply_target_attachment_modifiers']) && $trigger_options['apply_target_attachment_modifiers'] == false ? false : true;
                $apply_target_attachment_recovery_breakers = isset($trigger_options['apply_target_attachment_recovery_breakers']) && $trigger_options['apply_target_attachment_recovery_breakers'] == false ? false : true;
                $apply_target_attachment_recovery_boosters = isset($trigger_options['apply_target_attachment_recovery_boosters']) && $trigger_options['apply_target_attachment_recovery_boosters'] == false ? false : true;

                // If this robot (target of recovery) has an attachment with a recovery multiplier
                $this_robot_attachments = $this_robot->get_current_attachments();
                if ($apply_target_attachment_modifiers
                    && !empty($this_robot_attachments)){

                    // Loop through this robot's attachments one-by-one and apply their modifiers
                    foreach ($this_robot_attachments AS $temp_token => $temp_info){
                        $temp_token_debug = str_replace('item_', 'attachment_', $temp_token);

                        // First check to see if any basic breakers or boosters have been created for this robot
                        if (true){
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_target_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_breaker'])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_target_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_booster'])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery input breaker value set
                            if ($apply_target_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_input_breaker'])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_input_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_input_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_input_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery input booster value set
                            if ($apply_target_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_input_booster'])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_input_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_input_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_input_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($this_item->recovery_options['recovery_type'])){
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_target_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_target_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_booster_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_target_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_target_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($this_item->recovery_options['recovery_type2'])){
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_target_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_breaker_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_target_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_booster_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery breaker value set
                            if ($apply_target_attachment_recovery_breakers
                                && isset($temp_info['attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery breaker multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_input_breaker_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a recovery booster value set
                            if ($apply_target_attachment_recovery_boosters
                                && isset($temp_info['attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type2']])){
                                // Apply the recovery booster multiplier to the current recovery amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type2']]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type2'].' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_recovery_input_booster_'.$this_item->recovery_options['recovery_type2']].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }

                    }

                }

                // Round the resulting recovery after applying all modifiers
                $temp_new_amount = rpg_functions::round_ceil($this_item->item_results['this_amount']);
                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' modifiers applied <br /> round up results | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].') = '.$temp_new_amount.'');
                $this_item->item_results['this_amount'] = $temp_new_amount;

            }

            // Generate the flag string for easier parsing
            $this_flag_string = array();
            if ($this_item->item_results['flag_immunity']){ $this_flag_string[] = 'immunity'; }
            elseif ($trigger_options['apply_type_modifiers'] != false){
                if (!empty($this_item->item_results['flag_weakness'])){ $this_flag_string[] = 'weakness'; }
                if (!empty($this_item->item_results['flag_affinity'])){ $this_flag_string[] = 'affinity'; }
                if (!empty($this_item->item_results['flag_resistance'])){ $this_flag_string[] = 'resistance'; }
                if ($trigger_options['apply_modifiers'] != false && !$this_item->recovery_options['recovery_percent']){
                if (!empty($this_item->item_results['flag_critical'])){ $this_flag_string[] = 'critical'; }
                }
            }
            $this_flag_name = (!empty($this_flag_string) ? implode('_', $this_flag_string).'_' : '').'text';

            // Generate the status text based on flags
            if (isset($this_item->recovery_options[$this_flag_name])){
                //$event_options['console_container_height'] = 2;
                //$this_item->item_results['this_text'] .= '<br />';
                $this_item->item_results['this_text'] .= ' '.$this_item->recovery_options[$this_flag_name];
            }

            // Display a break before the recovery amount if other text was generated
            if (!empty($this_item->item_results['this_text'])){
                $this_item->item_results['this_text'] .= '<br />';
            }

            // Ensure the recovery amount is always at least one, unless absolute zero
            if ($this_item->item_results['this_amount'] < 1 && $this_item->item_results['this_amount'] > 0){ $this_item->item_results['this_amount'] = 1; }

            // Reference the requested recovery kind with a shorter variable
            $this_item->recovery_options['recovery_kind'] = strtolower($this_item->recovery_options['recovery_kind']);
            $recovery_stat_name = 'robot_'.$this_item->recovery_options['recovery_kind'];

            // Inflict the approiate recovery type based on the recovery options
            switch ($recovery_stat_name){

                // If this is an ATTACK type recovery trigger
                case 'robot_attack': {
                    // Inflict attack recovery on the target's internal stat
                    $this_robot->robot_attack = $this_robot->robot_attack + $this_item->item_results['this_amount'];
                    // If the recovery put the robot's attack above 9999
                    if ($this_robot->robot_attack > MMRPG_SETTINGS_STATS_MAX){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = (MMRPG_SETTINGS_STATS_MAX - $this_robot->robot_attack) * -1;
                        // Calculate the actual recovery amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] - $this_item->item_results['this_overkill'];
                        // Max out the robots attack
                        $this_robot->robot_attack = MMRPG_SETTINGS_STATS_MAX;
                    }
                    // Break from the ATTACK case
                    break;
                }
                // If this is an DEFENSE type recovery trigger
                case 'robot_defense': {
                    // Inflict defense recovery on the target's internal stat
                    $this_robot->robot_defense = $this_robot->robot_defense + $this_item->item_results['this_amount'];
                    // If the recovery put the robot's defense above 9999
                    if ($this_robot->robot_defense > MMRPG_SETTINGS_STATS_MAX){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = (MMRPG_SETTINGS_STATS_MAX - $this_robot->robot_defense) * -1;
                        // Calculate the actual recovery amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] - $this_item->item_results['this_overkill'];
                        // Max out the robots defense
                        $this_robot->robot_defense = MMRPG_SETTINGS_STATS_MAX;
                    }
                    // Break from the DEFENSE case
                    break;
                }
                // If this is an SPEED type recovery trigger
                case 'robot_speed': {
                    // Inflict speed recovery on the target's internal stat
                    $this_robot->robot_speed = $this_robot->robot_speed + $this_item->item_results['this_amount'];
                    // If the recovery put the robot's speed above 9999
                    if ($this_robot->robot_speed > MMRPG_SETTINGS_STATS_MAX){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = (MMRPG_SETTINGS_STATS_MAX - $this_robot->robot_speed) * -1;
                        // Calculate the actual recovery amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] - $this_item->item_results['this_overkill'];
                        // Max out the robots speed
                        $this_robot->robot_speed = MMRPG_SETTINGS_STATS_MAX;
                    }
                    // Break from the SPEED case
                    break;
                }
                // If this is a WEAPONS type recovery trigger
                case 'robot_weapons': {
                    // Inflict weapon recovery on the target's internal stat
                    $this_robot->robot_weapons = $this_robot->robot_weapons + $this_item->item_results['this_amount'];
                    // If the recovery put the robot's weapons above the base
                    if ($this_robot->robot_weapons > $this_robot->robot_base_weapons){
                        // Calculate the overcure amount
                        $this_item->item_results['this_overkill'] = ($this_robot->robot_base_weapons - $this_robot->robot_weapons) * -1;
                        // Calculate the actual recovery amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] - $this_item->item_results['this_overkill'];
                        // Max out the robots weapons
                        $this_robot->robot_weapons = $this_robot->robot_base_weapons;
                    }
                    // Break from the WEAPONS case
                    break;
                }
                // If this is an ENERGY type recovery trigger
                case 'robot_energy': default: {
                    // Inflict the actual recovery on the robot
                    $this_robot->robot_energy = $this_robot->robot_energy + $this_item->item_results['this_amount'];
                    // If the recovery put the robot into overkill, recalculate the recovery
                    if ($this_robot->robot_energy > $this_robot->robot_base_energy){
                        // Calculate the overcure amount
                        $this_item->item_results['this_overkill'] = ($this_robot->robot_base_energy - $this_robot->robot_energy) * -1;
                        // Calculate the actual recovery amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] - $this_item->item_results['this_overkill'];
                        // Max out the robots energy
                        $this_robot->robot_energy = $this_robot->robot_base_energy;
                    }
                    // If the robot's energy has dropped to zero, disable them
                    if ($this_robot->robot_energy <= 0){
                        // Change the status to disabled
                        $this_robot->robot_status = 'disabled';
                        // Remove any attachments this robot has
                        if (!empty($this_robot->robot_attachments)){
                            foreach ($this_robot->robot_attachments AS $token => $info){
                                if (empty($info['sticky'])){ unset($this_robot->robot_attachments[$token]); }
                            }
                        }
                    }
                    // Break from the ENERGY case
                    break;
                }

            }

            // Define the print variables to return
            $this_item->item_results['print_strikes'] = '<span class="recovery_strikes">'.(!empty($this_item->item_results['total_strikes']) ? $this_item->item_results['total_strikes'] : 0).'</span>';
            $this_item->item_results['print_misses'] = '<span class="recovery_misses">'.(!empty($this_item->item_results['total_misses']) ? $this_item->item_results['total_misses'] : 0).'</span>';
            $this_item->item_results['print_result'] = '<span class="recovery_result">'.(!empty($this_item->item_results['total_result']) ? $this_item->item_results['total_result'] : 0).'</span>';
            $this_item->item_results['print_amount'] = '<span class="recovery_amount">'.(!empty($this_item->item_results['this_amount']) ? $this_item->item_results['this_amount'] : 0).'</span>';
            $this_item->item_results['print_overkill'] = '<span class="recovery_overkill">'.(!empty($this_item->item_results['this_overkill']) ? $this_item->item_results['this_overkill'] : 0).'</span>';

            // Add the final recovery text showing the amount based on life energy recovery
            if ($this_item->recovery_options['recovery_kind'] == 'energy'){
                $this_item->item_results['this_text'] .= "{$this_robot->print_name()} recovers {$this_item->item_results['print_amount']} life energy";
                //$this_item->item_results['this_text'] .= ($this_item->item_results['this_overkill'] > 0 ? " and {$this_item->item_results['print_overkill']} overkill" : '');
                $this_item->item_results['this_text'] .= '!<br />';
            }
            // Otherwise add the final recovery text showing the amount based on weapon energy recovery
            elseif ($this_item->recovery_options['recovery_kind'] == 'weapons'){
                $this_item->item_results['this_text'] .= "{$this_robot->print_name()} recovers {$this_item->item_results['print_amount']} weapon energy";
                $this_item->item_results['this_text'] .= '!<br />';
            }
            // Otherwise, if this is one of the robot's other internal stats
            elseif ($this_item->recovery_options['recovery_kind'] == 'attack'
                || $this_item->recovery_options['recovery_kind'] == 'defense'
                || $this_item->recovery_options['recovery_kind'] == 'speed'){
                // Print the result based on if the stat will go any lower
                if ($this_item->item_results['this_amount'] > 0){
                    $this_item->item_results['this_text'] .= "{$this_robot->print_name()}&#39;s {$this_item->recovery_options['recovery_kind']} rose by {$this_item->item_results['print_amount']}";
                    $this_item->item_results['this_text'] .= '!<br />';
                }
                // Otherwise if the stat wouldn't go any lower
                else {

                    // Update this robot's frame based on recovery type
                    $this_item->item_frame = $this_item->recovery_options['item_failure_frame'];
                    $this_item->item_frame_span = $this_item->recovery_options['item_failure_frame_span'];
                    $this_item->item_frame_offset = $this_item->recovery_options['item_failure_frame_offset'];

                    // Display the failure text, if text has been provided
                    if (!empty($this_item->recovery_options['failure_text'])){
                        $this_item->item_results['this_text'] .= $this_item->recovery_options['failure_text'].' ';
                    }
                }
            }

        }
        // Otherwise, if the attack was a failure
        else {

            // Update this robot's frame based on recovery type
            $this_item->item_frame = $this_item->recovery_options['item_failure_frame'];
            $this_item->item_frame_span = $this_item->recovery_options['item_failure_frame_span'];
            $this_item->item_frame_offset = $this_item->recovery_options['item_failure_frame_offset'];

            // Update the recovery and overkilll amounts to reflect zero recovery
            $this_item->item_results['this_amount'] = 0;
            $this_item->item_results['this_overkill'] = 0;

            // Display the failure text, if text has been provided
            if (!$this_item->item_results['flag_immunity'] && !empty($this_item->recovery_options['failure_text'])){
                $this_item->item_results['this_text'] .= $this_item->recovery_options['failure_text'].' ';
            }

        }

        // Update this robot's history with the triggered recovery amount
        $this_robot->history['triggered_recovery'][] = $this_item->item_results['this_amount'];
        $this_robot->history['triggered_recovery_by'][] = $this_item->item_token;

        // Update the robot's history with the triggered recovery types
        if (!empty($this_item->item_results['recovery_type'])){
            $temp_types = array();
            $temp_types[] = $this_item->item_results['recovery_type'];
            if (!empty($this_item->item_results['recovery_type2'])){ $temp_types[] = $this_item->item_results['recovery_type2']; }
            $this_robot->history['triggered_recovery_types'][] = $temp_types;
        } else {
            $this_robot->history['triggered_recovery_types'][] = null; //array();
        }

        // Update the recovery result total variables
        $this_item->item_results['total_amount'] += !empty($this_item->item_results['this_amount']) ? $this_item->item_results['this_amount'] : 0;
        $this_item->item_results['total_overkill'] += !empty($this_item->item_results['this_overkill']) ? $this_item->item_results['this_overkill'] : 0;
        if ($this_item->item_results['this_result'] == 'success'){ $this_item->item_results['total_strikes']++; }
        else { $this_item->item_results['total_misses']++; }
        $this_item->item_results['total_actions'] = $this_item->item_results['total_strikes'] + $this_item->item_results['total_misses'];
        if ($this_item->item_results['total_result'] != 'success'){ $this_item->item_results['total_result'] = $this_item->item_results['this_result']; }
        $event_options['this_item_results'] = $this_item->item_results;

        // Update internal variables
        $target_robot->update_session();
        $target_robot->player->update_session();
        $this_robot->update_session();
        $this_robot->player->update_session();

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-item_trigger-recovery_middle', $extra_objects);
        $target_robot->trigger_custom_function('rpg-item_trigger-recovery_middle', $extra_objects);
        if ($options->return_early){ return $options->return_value; }

        // Generate an event with the collected recovery results based on recovery type
        $temp_event_header = $this_item->recovery_options['recovery_header'];
        $temp_event_body = $this_item->item_results['this_text'];
        if ($this_robot->robot_id == $target_robot->robot_id){
            $event_options['console_show_target'] = false;
            $event_options['this_item_target'] = $this_robot->robot_id.'_'.$this_robot->robot_token;
            $temp_event_body = str_replace('{this_robot}', $this_robot->print_name(), $temp_event_body);
            $temp_event_body = str_replace('{target_robot}', $target_robot->print_name(), $temp_event_body);
            $this_robot->battle->events_create($target_robot, $this_robot, $this_item->recovery_options['recovery_header'], $this_item->item_results['this_text'], $event_options);
        } else {
            $event_options['console_show_target'] = false;
            $event_options['this_item_target'] = $this_robot->robot_id.'_'.$this_robot->robot_token;
            $temp_event_body = str_replace('{this_robot}', $target_robot->print_name(), $temp_event_body);
            $temp_event_body = str_replace('{target_robot}', $this_robot->print_name(), $temp_event_body);
            $this_robot->battle->events_create($this_robot, $target_robot, $this_item->recovery_options['recovery_header'], $this_item->item_results['this_text'], $event_options);
        }

        // Restore this and the target robot's frames to their backed up state
        $this_robot->robot_frame = $this_robot_backup_frame;
        $this_robot->player->player_frame = $this_player_backup_frame;
        $target_robot->robot_frame = $target_robot_backup_frame;
        $target_robot->player->player_frame = $target_player_backup_frame;
        $this_item->item_frame = $this_item_backup_frame;

        // Update internal variables
        $target_robot->update_session();
        $target_robot->player->update_session();
        $this_robot->update_session();
        $this_robot->player->update_session();
        $this_item->update_session();

        // If this robot has been disabled, add a defeat attachment
        if ($this_robot->robot_status == 'disabled'){

            // If the attachment doesn't already exists, add it to the robot
            $defeat_attachment = rpg_functions::get_defeat_attachment();
            if (!isset($this_robot->robot_attachments[$defeat_attachment['token']])){
                $this_robot->robot_attachments[$defeat_attachment['token']] =  $defeat_attachment['info'];
                $this_robot->update_session();
            }

        }

        // If this robot was disabled, process experience for the target
        if ($this_robot->robot_status == 'disabled' && $trigger_disabled){
            $this_robot->trigger_disabled($target_robot);
        }
        // Otherwise, if the target robot was not disabled
        elseif ($this_robot->robot_status != 'disabled'){

            // -- CHECK ATTACHMENTS -- //

            // Ensure the item was a success before checking attachments
            if ($this_item->item_results['this_result'] == 'success'){
                // If this robot has any attachments, loop through them
                $static_attachment_key = $this_robot->get_static_attachment_key();
                $this_robot_attachments = $this_robot->get_current_attachments();
                if (!empty($this_robot_attachments)){
                    $this_battle->events_debug(__FILE__, __LINE__, 'checkpoint has attachments');
                    foreach ($this_robot_attachments AS $attachment_token => $attachment_info){

                        // Ensure this item has a type before checking weaknesses, resistances, etc.
                        if (!empty($this_item->item_type)
                                || (isset($attachment_info['attachment_weaknesses']) && in_array('*', $attachment_info['attachment_weaknesses']))){

                            // If this attachment has weaknesses defined and this item is a match
                            if (!empty($attachment_info['attachment_weaknesses'])
                                && (in_array('*', $attachment_info['attachment_weaknesses'])
                                    || in_array($this_item->item_type, $attachment_info['attachment_weaknesses'])
                                    || in_array($this_item->item_type2, $attachment_info['attachment_weaknesses']))
                                && (!isset($attachment_info['attachment_weaknesses_trigger'])
                                    || $attachment_info['attachment_weaknesses_trigger'] === 'either'
                                    || $attachment_info['attachment_weaknesses_trigger'] === 'target')
                                    ){
                                $this_battle->events_debug(__FILE__, __LINE__, 'checkpoint weaknesses');
                                // Remove this attachment and inflict damage on the robot
                                unset($this_robot->robot_attachments[$attachment_token]);
                                unset($this_robot->battle->battle_attachments[$static_attachment_key][$attachment_token]);
                                $this_robot->update_session();
                                $this_robot->battle->update_session();
                                $attachment_destroy_info = isset($attachment_info['attachment_destroy_via_weaknesses']) ? $attachment_info['attachment_destroy_via_weaknesses'] : $attachment_info['attachment_destroy'];
                                if ($attachment_destroy_info !== false){
                                    $attachment_info['flags']['is_attachment'] = true;
                                    if (!isset($attachment_info['attachment_token'])){ $attachment_info['attachment_token'] = $attachment_token; }
                                    if (isset($attachment_info['ability_token'])){ $temp_attachment = rpg_game::get_ability($this_robot->battle, $this_robot->player, $this_robot, array('ability_token' => $attachment_info['ability_token'])); }
                                    elseif (isset($attachment_info['item_token'])){ $temp_attachment = rpg_game::get_item($this_robot->battle, $this_robot->player, $this_robot, array('item_token' => $attachment_info['item_token'])); }
                                    else { continue; }
                                    $temp_trigger_type = !empty($attachment_destroy_info['trigger']) ? $attachment_destroy_info['trigger'] : 'damage';
                                    //$this_battle->events_create(false, false, 'DEBUG', 'checkpoint has attachments '.$attachment_token.' trigger '.$temp_trigger_type.'!');
                                    //$this_battle->events_create(false, false, 'DEBUG', 'checkpoint has attachments '.$attachment_token.' trigger '.$temp_trigger_type.' info:<br />'.preg_replace('/\s+/', ' ', htmlentities(print_r($attachment_destroy_info, true), ENT_QUOTES, 'UTF-8', true)));
                                    if ($temp_trigger_type == 'damage'){
                                        $temp_attachment->damage_options_update($attachment_destroy_info);
                                        $temp_attachment->recovery_options_update($attachment_destroy_info);
                                        $temp_attachment->update_session();
                                        $temp_damage_kind = $attachment_destroy_info['kind'];
                                        if (isset($attachment_info['attachment_'.$temp_damage_kind])){
                                            $temp_damage_amount = $attachment_info['attachment_'.$temp_damage_kind];
                                            $temp_trigger_options = array('apply_modifiers' => false);
                                            $this_robot->trigger_damage($target_robot, $temp_attachment, $temp_damage_amount, false, $temp_trigger_options);
                                        }
                                    } elseif ($temp_trigger_type == 'recovery'){
                                        $temp_attachment->recovery_options_update($attachment_destroy_info);
                                        $temp_attachment->damage_options_update($attachment_destroy_info);
                                        $temp_attachment->update_session();
                                        $temp_recovery_kind = $attachment_destroy_info['kind'];
                                        if (isset($attachment_info['attachment_'.$temp_recovery_kind])){
                                            $temp_recovery_amount = $attachment_info['attachment_'.$temp_recovery_kind];
                                            $temp_trigger_options = array('apply_modifiers' => false);
                                            $this_robot->trigger_recovery($target_robot, $temp_attachment, $temp_recovery_amount, false, $temp_trigger_options);
                                        }
                                    } elseif ($temp_trigger_type == 'special'){
                                        $temp_attachment->target_options_update($attachment_destroy_info);
                                        $temp_attachment->recovery_options_update($attachment_destroy_info);
                                        $temp_attachment->damage_options_update($attachment_destroy_info);
                                        $temp_attachment->update_session();
                                        //$this_robot->trigger_damage($target_robot, $temp_attachment, 0, false);
                                        $this_robot->trigger_target($target_robot, $temp_attachment, array('canvas_show_this_item' => false, 'prevent_default_text' => true));
                                    }
                                }
                                // If this robot was disabled, process experience for the target
                                if ($this_robot->robot_status == 'disabled'){ break; }
                            }

                        }

                    }
                }

            }

        }

        // If this robot has an onrecovery function, trigger it
        $this_onrecovery_function = $this_robot->robot_function_onrecovery;
        $temp_result = $this_onrecovery_function(array(
            'this_battle' => $this_battle,
            'this_field' => $this_battle->battle_field,
            'this_player' => $this_robot->player,
            'this_robot' => $this_robot,
            'target_player' => $this_robot->player,
            'target_robot' => $target_robot,
            'this_item' => $this_item
            ));

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-item_trigger-recovery_after', $extra_objects);
        $target_robot->trigger_custom_function('rpg-item_trigger-recovery_after', $extra_objects);

        // Return the final recovery results
        return $this_item->item_results;

    }

}
?>