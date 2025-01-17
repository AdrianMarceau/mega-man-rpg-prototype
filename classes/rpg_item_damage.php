<?
/**
 * Mega Man RPG Item Damage
 * <p>The item-specific battle damage class for the Mega Man RPG Prototype.</p>
 */
class rpg_item_damage extends rpg_damage {

    // Define a trigger for inflicting all types of damage on this robot
    public static function trigger_robot_damage($this_robot, $target_robot, $this_item, $damage_amount, $trigger_disabled = true, $trigger_options = array()){
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
        if (!isset($trigger_options['apply_attachment_damage_modifiers']) || $trigger_options['apply_modifiers'] == false){ $trigger_options['apply_attachment_damage_modifiers'] = $trigger_options['apply_modifiers']; }
        if (!isset($trigger_options['referred_damage'])){ $trigger_options['referred_damage'] = false; }
        if (!isset($trigger_options['referred_damage_id'])){ $trigger_options['referred_damage_id'] = 0; }
        if (!isset($trigger_options['referred_damage_stats'])){ $trigger_options['referred_damage_stats'] = array(); }
        if (!isset($trigger_options['force_flags'])){ $trigger_options['force_flags'] = array(); }

        // If this is referred damage, collect the actual target
        if (!empty($trigger_options['referred_damage']) && !empty($trigger_options['referred_damage_id'])){
            //$debug .= "<br /> referred_damage is true and created by robot ID {$trigger_options['referred_damage_id']} ";
            $new_target_robot = $this_robot->battle->find_target_robot($trigger_options['referred_damage_id']);
            if (!empty($new_target_robot) && isset($new_target_robot->robot_token)){
                //$debug .= "<br /> \$new_target_robot was found! {$new_target_robot->robot_token} ";
                unset($target_player, $target_robot);
                $target_player = $new_target_robot->player;
                $target_robot = $new_target_robot;
            } else {
                //$debug .= "<br /> \$new_target_robot returned ".print_r($new_target_robot, true)." ";
                $trigger_options['referred_damage'] = false;
                $trigger_options['referred_damage_id'] = false;
                $trigger_options['referred_damage_stats'] = array();
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
        if (!empty($trigger_options['referred_damage_stats'])){
            $target_robot_stats = array_merge($target_robot_stats, $trigger_options['referred_damage_stats']);
        }

        // Check if this robot is at full health before triggering
        $this_robot_energy_start = $this_robot->robot_energy;
        $this_robot_energy_start_max = $this_robot_energy_start >= $this_robot->robot_base_energy ? true : false;

        // Define the event console options
        $event_options = array();
        $event_options['console_container_height'] = 1;
        $event_options['this_other_item'] = $this_item;
        $event_options['this_item_trigger'] = 'damage';
        $event_options['this_item_results'] = array();

        // Apply appropriate camera action flags to the event options
        rpg_canvas::apply_camera_action_flags($event_options, $this_robot, $this_item, 'damage');

        // Create an options object for this function and populate
        $options = rpg_game::new_options_object();
        $options->damage_target = $this_robot;
        $options->damage_initiator = $target_robot;
        $options->damage_amount = $damage_amount;
        $options->trigger_options = &$trigger_options;
        $options->event_options = &$event_options;
        $extra_objects = array(
            //'this_robot' => $this_robot,
            //'target_robot' => $target_robot,
            'this_item' => $this_item,
            'options' => $options
            );
        $extra_objects_for_this_robot = $extra_objects;
        $extra_objects_for_this_robot['this_player'] = $this_robot->player;
        $extra_objects_for_this_robot['this_robot'] = $this_robot;
        $extra_objects_for_this_robot['target_player'] = $target_robot->player;
        $extra_objects_for_this_robot['target_robot'] = $target_robot;
        $extra_objects_for_other_robot = $extra_objects;
        $extra_objects_for_other_robot['this_player'] = $target_robot->player;
        $extra_objects_for_other_robot['this_robot'] = $target_robot;
        $extra_objects_for_other_robot['target_player'] = $this_robot->player;
        $extra_objects_for_other_robot['target_robot'] = $this_robot;

        // Empty any text from the previous item result
        $this_item->item_results['this_text'] = '';

        // Update the damage to whatever was supplied in the argument
        //if ($this_item->damage_options['damage_percent'] && $options->damage_amount > 100){ $options->damage_amount = 100; }
        $this_item->damage_options['damage_amount'] = $options->damage_amount;

        // Collect the damage amount argument from the function
        $this_item->item_results['this_amount'] = $options->damage_amount;
        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | to('.$this_robot->robot_id.':'.$this_robot->robot_token.') vs from('.$target_robot->robot_id.':'.$target_robot->robot_token.') | damage_start_amount |<br /> '.'amount:'.$this_item->item_results['this_amount'].' | '.'percent:'.($this_item->damage_options['damage_percent'] ? 'true' : 'false').' | '.'kind:'.$this_item->damage_options['damage_kind'].' | type1:'.(!empty($this_item->damage_options['damage_type']) ? $this_item->damage_options['damage_type'] : 'none').' | type2:'.(!empty($this_item->damage_options['damage_type2']) ? $this_item->damage_options['damage_type2'] : 'none').'');

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-item_trigger-damage_before', $extra_objects_for_this_robot);
        $target_robot->trigger_custom_function('rpg-item_trigger-damage_before', $extra_objects_for_other_robot);
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
        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' : damage_trigger_options : '.$debug);


        // Only apply modifiers if they have not been disabled
        if ($trigger_options['apply_modifiers'] != false){

            // Skip all weakness, resistance, etc. calculations if robot is targetting self
            if ($trigger_options['apply_type_modifiers'] != false && ($this_robot->robot_id != $target_robot->robot_id || $trigger_options['referred_damage'])){

                // If target robot has affinity to the item (based on type)
                if ($this_robot->has_affinity($this_item->damage_options['damage_type']) && !$this_robot->has_weakness($this_item->damage_options['damage_type2'])){
                    //$this_item->item_results['counter_affinities'] += 1;
                    //$this_item->item_results['flag_affinity'] = true;
                    return $this_robot->trigger_recovery($target_robot, $this_item, $options->damage_amount);
                } else {
                    $this_item->item_results['flag_affinity'] = false;
                }

                // If target robot has affinity to the item (based on type2)
                if ($this_robot->has_affinity($this_item->damage_options['damage_type2']) && !$this_robot->has_weakness($this_item->damage_options['damage_type'])){
                    $this_item->item_results['counter_affinities'] += 1;
                    $this_item->item_results['flag_affinity'] = true;
                    return $this_robot->trigger_recovery($target_robot, $this_item, $options->damage_amount);
                }

                // If this robot has weakness to the item (based on type)
                if ($this_robot->has_weakness($this_item->damage_options['damage_type']) && !$this_robot->has_affinity($this_item->damage_options['damage_type2'])){
                    $this_item->item_results['counter_weaknesses'] += 1;
                    $this_item->item_results['flag_weakness'] = true;
                } else {
                    $this_item->item_results['flag_weakness'] = false;
                }

                // If this robot has weakness to the item (based on type2)
                if ($this_robot->has_weakness($this_item->damage_options['damage_type2']) && !$this_robot->has_affinity($this_item->damage_options['damage_type'])){
                    $this_item->item_results['counter_weaknesses'] += 1;
                    $this_item->item_results['flag_weakness'] = true;
                }

                // If target robot has resistance tp the item (based on type)
                if ($this_robot->has_resistance($this_item->damage_options['damage_type'])){
                    $this_item->item_results['counter_resistances'] += 1;
                    $this_item->item_results['flag_resistance'] = true;
                } else {
                    $this_item->item_results['flag_resistance'] = false;
                }

                // If target robot has resistance tp the item (based on type2)
                if ($this_robot->has_resistance($this_item->damage_options['damage_type2'])){
                    $this_item->item_results['counter_resistances'] += 1;
                    $this_item->item_results['flag_resistance'] = true;
                }

                // If target robot has immunity to the item (based on type)
                if ($this_robot->has_immunity($this_item->damage_options['damage_type'])){
                    $this_item->item_results['counter_immunities'] += 1;
                    $this_item->item_results['flag_immunity'] = true;
                } else {
                    $this_item->item_results['flag_immunity'] = false;
                }

                // If target robot has immunity to the item (based on type2)
                if ($this_robot->has_immunity($this_item->damage_options['damage_type2'])){
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
                $item_type_token = !empty($this_item->damage_options['damage_type']) ? $this_item->damage_options['damage_type'] : 'none';
                $item_type_token2 = !empty($this_item->damage_options['damage_type2']) ? $this_item->damage_options['damage_type2'] : '';

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
                    // Collect the current key of the robot and apply damage mods
                    $temp_damage_key = $this_robot->robot_key + 1;
                    $temp_damage_resistor = (10 - $temp_damage_key) / 10;
                    $new_damage_amount = rpg_functions::round_ceil($options->damage_amount * $temp_damage_resistor);
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | position_modifier_damage | '.$options->damage_amount.' = rpg_functions::round_ceil('.$options->damage_amount.' * '.$temp_damage_resistor.') = '.$new_damage_amount.'');
                    $options->damage_amount = $new_damage_amount;
                }

            }

        }

        // Apply field multipliers preemtively if there are any
        if ($trigger_options['apply_field_modifiers'] != false && $this_item->damage_options['damage_modifiers'] && !empty($this_robot->field->field_multipliers)){

            // Collect the multipliters for easier
            $field_multipliers = $this_robot->field->field_multipliers;

            // Collect the item types else "none" for multipliers
            $temp_item_damage_type = !empty($this_item->damage_options['damage_type']) ? $this_item->damage_options['damage_type'] : 'none';
            $temp_item_damage_type2 = !empty($this_item->damage_options['damage_type2']) ? $this_item->damage_options['damage_type2'] : '';

            // If there's a damage booster, apply that first
            if (isset($field_multipliers['damage'])){
                $new_damage_amount = rpg_functions::round_ceil($options->damage_amount * $field_multipliers['damage']);
                $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | field_multiplier_damage | '.$options->damage_amount.' = rpg_functions::round_ceil('.$options->damage_amount.' * '.$field_multipliers['damage'].') = '.$new_damage_amount.'');
                $options->damage_amount = $new_damage_amount;
            }

            // Loop through all the other type multipliers one by one if this item has a type
            $skip_types = array('damage', 'recovery', 'experience');
            foreach ($field_multipliers AS $temp_type => $temp_multiplier){
                // Skip non-type and special fields for this calculation
                if (in_array($temp_type, $skip_types)){ continue; }
                // If this item's type matches the multiplier, apply it
                if ($temp_item_damage_type == $temp_type){
                    $new_damage_amount = rpg_functions::round_ceil($options->damage_amount * $temp_multiplier);
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | field_multiplier_'.$temp_type.' | '.$options->damage_amount.' = rpg_functions::round_ceil('.$options->damage_amount.' * '.$temp_multiplier.') = '.$new_damage_amount.'');
                    $options->damage_amount = $new_damage_amount;
                }
                // If this item's type2 matches the multiplier, apply it
                if ($temp_item_damage_type2 == $temp_type){
                    $new_damage_amount = rpg_functions::round_ceil($options->damage_amount * $temp_multiplier);
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | field_multiplier_'.$temp_type.' | '.$options->damage_amount.' = rpg_functions::round_ceil('.$options->damage_amount.' * '.$temp_multiplier.') = '.$new_damage_amount.'');
                    $options->damage_amount = $new_damage_amount;
                }
            }


        }

        // Update the item results with the the trigger kind and damage details
        $this_item->item_results['trigger_kind'] = 'damage';
        $this_item->item_results['damage_kind'] = $this_item->damage_options['damage_kind'];
        $this_item->item_results['damage_type'] = $this_item->damage_options['damage_type'];
        $this_item->item_results['damage_type2'] = !empty($this_item->damage_options['damage_type2']) ? $this_item->damage_options['damage_type2'] : '';

        // If the success rate was not provided, auto-calculate
        if ($this_item->damage_options['success_rate'] == 'auto'){

            // If this robot is targetting itself, default to item accuracy
            if ($this_robot->robot_id == $target_robot->robot_id){
                // Update the success rate to the item accuracy value
                $this_item->damage_options['success_rate'] = $this_item->item_accuracy;
            }
            // Otherwise, if this robot is in speed break or item accuracy 100%
            elseif ($target_robot_stats['robot_speed'] <= 0 && $this_robot->robot_speed > 0){
                // Hard-code the success rate at 100% accuracy
                    $this_item->damage_options['success_rate'] = 0;
            }
            // Otherwise, if this robot is in speed break or item accuracy 100%
            elseif ($this_robot->robot_speed <= 0 || $this_item->item_accuracy == 100){
                // Hard-code the success rate at 100% accuracy
                    $this_item->damage_options['success_rate'] = 100;
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
                $this_item->damage_options['success_rate'] = $this_item_accuracy;
                //$this_item->item_results['this_text'] .= '';
            }

            // Check to see if affection values play into this at all (infatuation makes moves more likely to hit successfully)
            if (!empty($this_robot->counters['affection'][$target_robot->robot_token])
                || !empty($target_robot->counters['affection'][$this_robot->robot_token])){
                //error_log('this robot ('.$this_robot->robot_string.') is being damaged by the target robot ('.$target_robot->robot_string.')');
                //error_log('$this_item->damage_options[success_rate](base) = '.$this_item->damage_options['success_rate']);

                // (does this robot like the target robot?)
                if (!empty($this_robot->counters['affection'][$target_robot->robot_token])
                    && $this_item->damage_options['success_rate'] < 100){
                    //error_log('this robot ('.$this_robot->robot_string.') likes the target robot ('.$target_robot->robot_string.')');
                    $this_affection_value = $this_robot->counters['affection'][$target_robot->robot_token];
                    if ($this_robot->robot_class === 'mecha'){ $this_affection_value = $this_affection_value / 2; }
                    elseif ($this_robot->robot_class === 'master'){ $this_affection_value = $this_affection_value / 3; }
                    elseif ($this_robot->robot_class === 'boss'){ $this_affection_value = $this_affection_value / 4; }
                    $this_affection_value = min($this_affection_value, 25);
                    //error_log('$this_affection_value = '.$this_affection_value);
                    // this robot is being hit by the target, but they're infatuated, so it increase the success relative to their affection
                    $this_modifier = ceil($this_item->damage_options['success_rate'] * ($this_affection_value / 10));
                    $this_item->damage_options['success_rate'] += $this_modifier;
                    if ($this_item->damage_options['success_rate'] >= 100){ $this_item->damage_options['success_rate'] = 99; }
                    //error_log('$this_modifier = '.$this_modifier);
                    //error_log('$this_item->damage_options[success_rate] = '.$this_item->damage_options['success_rate']);
                }
                // (or does the target robot like this one?)
                elseif (!empty($target_robot->counters['affection'][$this_robot->robot_token])
                    && $this_item->damage_options['success_rate'] > 0){
                    //error_log('target robot ('.$target_robot->robot_string.') likes this robot ('.$this_robot->robot_string.')');
                    $target_affection_value = $target_robot->counters['affection'][$this_robot->robot_token];
                    if ($target_robot->robot_class === 'mecha'){ $target_affection_value = $target_affection_value / 2; }
                    elseif ($target_robot->robot_class === 'master'){ $target_affection_value = $target_affection_value / 3; }
                    elseif ($target_robot->robot_class === 'boss'){ $target_affection_value = $target_affection_value / 4; }
                    $target_affection_value = min($target_affection_value, 25);
                    //error_log('$target_affection_value = '.$target_affection_value);
                    // the target is trying to hit this robot, but they like it too much, so it reduces their success relative to their affection
                    $this_modifier = ceil($this_item->damage_options['success_rate'] * ($target_affection_value / 10));
                    $this_item->damage_options['success_rate'] -= $this_modifier;
                    if ($this_item->damage_options['success_rate'] <= 0){ $this_item->damage_options['success_rate'] = 1; }
                    //error_log('$this_modifier = '.$this_modifier);
                    //error_log('$this_item->damage_options[success_rate] = '.$this_item->damage_options['success_rate']);
                }

            }



        }

        // If the failure rate was not provided, auto-calculate
        if ($this_item->damage_options['failure_rate'] == 'auto'){
            // Set the failure rate to the difference of success vs failure (100% base)
            $this_item->damage_options['failure_rate'] = 100 - $this_item->damage_options['success_rate'];
            if ($this_item->damage_options['failure_rate'] < 0){
                $this_item->damage_options['failure_rate'] = 0;
            }
        }

        // If this robot is in speed break, increase success rate, reduce failure
        if ($this_robot->robot_speed == 0 && $this_item->damage_options['success_rate'] > 0){
            $this_item->damage_options['success_rate'] = ceil($this_item->damage_options['success_rate'] * 2);
            $this_item->damage_options['failure_rate'] = ceil($this_item->damage_options['failure_rate'] / 2);
        }
        // If the target robot is in speed break, decease the success rate, increase failure
        elseif ($target_robot_stats['robot_speed'] == 0 && $this_item->damage_options['success_rate'] > 0){
            $this_item->damage_options['success_rate'] = ceil($this_item->damage_options['success_rate'] / 2);
            $this_item->damage_options['failure_rate'] = ceil($this_item->damage_options['failure_rate'] * 2);
        }

        // If success rate is at 100%, auto-set the result to success
        if ($this_item->damage_options['success_rate'] == 100){
            // Set this item result as a success
            $this_item->damage_options['failure_rate'] = 0;
            $this_item->item_results['this_result'] = 'success';
        }
        // Else if the success rate is at 0%, auto-set the result to failure
        elseif ($this_item->damage_options['success_rate'] == 0){
            // Set this item result as a failure
            $this_item->damage_options['failure_rate'] = 100;
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise, use a weighted random generation to get the result
        else {
            // Calculate whether this attack was a success, based on the success vs. failure rate
            $this_item->item_results['this_result'] = $this_robot->battle->weighted_chance(
                array('success','failure'),
                array($this_item->damage_options['success_rate'], $this_item->damage_options['failure_rate'])
                );
        }

        // If this is ENERGY damage and this robot is already disabled
        if ($this_item->damage_options['damage_kind'] == 'energy' && $this_robot->robot_energy <= 0){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // If this is WEAPONS recovery and this robot is already at empty ammo
        elseif ($this_item->damage_options['damage_kind'] == 'weapons' && $this_robot->robot_weapons <= 0){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise if ATTACK damage but attack is already zero
        elseif ($this_item->damage_options['damage_kind'] == 'attack' && $this_robot->robot_attack <= 0){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise if DEFENSE damage but defense is already zero
        elseif ($this_item->damage_options['damage_kind'] == 'defense' && $this_robot->robot_defense <= 0){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }
        // Otherwise if SPEED damage but speed is already zero
        elseif ($this_item->damage_options['damage_kind'] == 'speed' && $this_robot->robot_speed <= 0){
            // Hard code the result to failure
            $this_item->item_results['this_result'] = 'failure';
        }

        // If this robot has immunity to the item, hard-code a failure result
        if ($this_item->item_results['flag_immunity']){
            $this_item->item_results['this_result'] = 'failure';
            $this_robot->flags['triggered_immunity'] = true;
            // Generate the status text based on flags
            $this_flag_name = 'immunity_text';
            if (isset($this_item->damage_options[$this_flag_name])){
                $this_item->item_results['this_text'] .= ' '.$this_item->damage_options[$this_flag_name].'<br /> ';
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
                    if ($this_item->damage_options['damage_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] += 0.1; }
                        $this_item->damage_options['damage_kickback']['x'] = ceil($this_item->damage_options['damage_kickback']['x'] * 2);
                    }
                }
                if (!empty($this_item->item_results['flag_affinity'])){
                    $this_robot->flags['triggered_affinity'] = true;
                    if (isset($this_robot->counters['triggered_affinity'])){ $this_robot->counters['triggered_affinity'] += 1; }
                    else { $this_robot->counters['triggered_affinity'] = 1; }
                    if ($this_item->damage_options['damage_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] -= 0.1; }
                    }
                }
                if (!empty($this_item->item_results['flag_resistance'])){
                    $this_robot->flags['triggered_resistance'] = true;
                    if (isset($this_robot->counters['triggered_resistance'])){ $this_robot->counters['triggered_resistance'] += 1; }
                    else { $this_robot->counters['triggered_resistance'] = 1; }
                    if ($this_item->damage_options['damage_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] -= 0.1; }
                    }
                }
                if (!empty($this_item->item_results['flag_critical'])){
                    $this_robot->flags['triggered_critical'] = true;
                    if (isset($this_robot->counters['triggered_critical'])){ $this_robot->counters['triggered_critical'] += 1; }
                    else { $this_robot->counters['triggered_critical'] = 1; }
                    if ($this_item->damage_options['damage_kind'] == 'energy' && $this_robot->player->player_side == 'right'){
                        if ($this_battle->flags['allow_experience_points']){ $this_robot->field->field_multipliers['experience'] += 0.1; }
                        $this_item->damage_options['damage_kickback']['x'] = ceil($this_item->damage_options['damage_kickback']['x'] * 2);
                    }
                }

            }

            // Update the field session with any changes
            $this_robot->field->update_session();

            // Update this robot's frame based on damage type
            $this_robot->robot_frame = $this_item->damage_options['damage_frame'];
            $this_robot->player->player_frame = ($this_robot->robot_id != $target_robot->robot_id || $trigger_options['referred_damage']) ? 'damage' : 'base';
            $this_item->item_frame = $this_item->damage_options['item_success_frame'];
            $this_item->item_frame_span = $this_item->damage_options['item_success_frame_span'];
            $this_item->item_frame_offset = $this_item->damage_options['item_success_frame_offset'];

            // Display the success text, if text has been provided
            if (!empty($this_item->damage_options['success_text'])){
                $this_item->item_results['this_text'] .= $this_item->damage_options['success_text'];
            }

            // Collect the damage amount argument from the function
            $this_item->item_results['this_amount'] = $options->damage_amount;

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
            if ($trigger_options['apply_stat_modifiers'] != false && !$this_item->damage_options['damage_percent']){

                // Only apply ATTACK/DEFENSE mods if this robot is not targetting itself and it's ENERGY based damage
                if ($this_item->damage_options['damage_kind'] == 'energy' && ($this_robot->robot_id != $target_robot->robot_id || $trigger_options['referred_damage'])){

                    // Backup the current ammount before stat multipliers
                    $temp_amount_backup = $this_item->item_results['this_amount'];

                    // If this robot's defense is at absolute zero, and the target's attack isnt, OHKO
                    if ($this_robot->robot_defense <= 0 && $target_robot_stats['robot_attack'] >= 1){
                        // Set the new damage amount to OHKO this robot
                        $temp_new_amount = $this_robot->robot_base_energy;
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | '.$this_robot->robot_token.'_defense_break | D:'.$this_robot->robot_defense.' | '.$this_item->item_results['this_amount'].' = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                    // Elseif the target robot's attack is at absolute zero, and the this's defense isnt, NOKO
                    elseif ($target_robot_stats['robot_attack'] <= 0 && $this_robot->robot_defense >= 1){
                        // Set the new damage amount to NOKO this robot
                        $temp_new_amount = 0;
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | '.$target_robot->robot_token.'_attack_break | A:'.$target_robot_stats['robot_attack'].' | '.$this_item->item_results['this_amount'].' = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                    // Elseif this robot's defense is at absolute zero and the target's attack is too, NOKO
                    elseif ($this_robot->robot_defense <= 0 && $target_robot_stats['robot_attack'] <= 0){
                        // Set the new damage amount to NOKO this robot
                        $temp_new_amount = 0;
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | '.$target_robot->robot_token.'_attack_break and '.$this_robot->robot_token.'_defense_break | A:'.$target_robot_stats['robot_attack'].' D:'.$this_robot->robot_defense.' | '.$this_item->item_results['this_amount'].' = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                    // Otherwise if both robots have normal stats, calculate the new amount normally
                    else {
                        // Set the new damage amount relative to this robot's defense and the target robot's attack
                        $temp_new_amount = rpg_functions::round_ceil($this_item->item_results['this_amount'] * ($target_robot_stats['robot_attack'] / $this_robot->robot_defense));
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | normal_damage | A:'.$target_robot_stats['robot_attack'].' D:'.$this_robot->robot_defense.' | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * ('.$target_robot_stats['robot_attack'].' / '.$this_robot->robot_defense.')) = '.$temp_new_amount.'');
                        // Update the amount with the new calculation
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }

                    // If this robot started out above zero but is now absolute zero, round up
                    if ($temp_amount_backup > 0 && $this_item->item_results['this_amount'] == 0){ $this_item->item_results['this_amount'] = 1; }

                }

                // If this is a critical hit (random chance)
                $critical_rate = $this_item->damage_options['critical_rate'];
                if ($this_robot->battle->critical_chance($critical_rate)){
                    $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] * $this_item->damage_options['critical_multiplier'];
                    $this_item->item_results['flag_critical'] = true;
                    $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_critical | x '.$this_item->damage_options['critical_multiplier'].' = '.$this_item->item_results['this_amount'].'');
                } else {
                    $this_item->item_results['flag_critical'] = false;
                }

                // If any force flags have been applied, it's best to parse them now
                if (in_array('flag_critical', $trigger_options['force_flags'])){
                    $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] * $this_item->damage_options['critical_multiplier'];
                    $this_item->item_results['flag_critical'] = true;
                }

            }

            // Only apply weakness, resistance, etc. if allowed to
            if ($trigger_options['apply_type_modifiers'] != false){

                // If this robot has a weakness to the item (based on type)
                if ($this_item->item_results['flag_weakness']){
                    $loop_count = $this_item->item_results['counter_weaknesses'] / ($this_item->item_results['total_strikes'] + 1);
                    for ($i = 1; $i <= $loop_count; $i++){
                        $temp_new_amount = rpg_functions::round_ceil($this_item->item_results['this_amount'] * $this_item->damage_options['weakness_multiplier']);
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_weakness ('.$i.'/'.$loop_count.') | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * '.$this_item->damage_options['weakness_multiplier'].') = '.$temp_new_amount.'');
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                }

                // If target robot resists the item (based on type)
                if ($this_item->item_results['flag_resistance']){
                    $loop_count = $this_item->item_results['counter_resistances'] / ($this_item->item_results['total_strikes'] + 1);
                    for ($i = 1; $i <= $loop_count; $i++){
                        $temp_new_amount = rpg_functions::round_ceil($this_item->item_results['this_amount'] * $this_item->damage_options['resistance_multiplier']);
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_resistance ('.$i.'/'.$loop_count.') | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * '.$this_item->damage_options['resistance_multiplier'].') = '.$temp_new_amount.'');
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                }

                // If target robot is immune to the item (based on type)
                if ($this_item->item_results['flag_immunity']){
                    $loop_count = $this_item->item_results['counter_immunities'] / ($this_item->item_results['total_strikes'] + 1);
                    for ($i = 1; $i <= $loop_count; $i++){
                        $this_item->item_results['this_amount'] = rpg_functions::round_ceil($this_item->item_results['this_amount'] * $this_item->damage_options['immunity_multiplier']);
                        $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | flag_immunity ('.$i.'/'.$loop_count.') | '.$this_item->item_results['this_amount'].' = rpg_functions::round_ceil('.$this_item->item_results['this_amount'].' * '.$this_item->damage_options['immunity_multiplier'].') = '.$temp_new_amount.'');
                        $this_item->item_results['this_amount'] = $temp_new_amount;
                    }
                }

            }

            // Only apply attachment modifiers if allowed to and not referred
            if ($trigger_options['apply_modifiers'] != false
                && $trigger_options['referred_damage'] == false
                && $trigger_options['apply_attachment_modifiers'] != false
                && $trigger_options['apply_attachment_damage_modifiers'] != false
                ){

                // Collect the item types else "none" for multipliers
                $temp_item_damage_type = !empty($this_item->damage_options['damage_type']) ? $this_item->damage_options['damage_type'] : 'none';
                $temp_item_damage_type2 = !empty($this_item->damage_options['damage_type2']) ? $this_item->damage_options['damage_type2'] : '';

                // Pre-determine which attachment origin attachment modifiers we're allowed to apply
                $apply_origin_attachment_modifiers = isset($trigger_options['apply_origin_attachment_modifiers']) && $trigger_options['apply_origin_attachment_modifiers'] == false ? false : true;
                $apply_origin_attachment_damage_breakers = isset($trigger_options['apply_origin_attachment_damage_breakers']) && $trigger_options['apply_origin_attachment_damage_breakers'] == false ? false : true;
                $apply_origin_attachment_damage_boosters = isset($trigger_options['apply_origin_attachment_damage_boosters']) && $trigger_options['apply_origin_attachment_damage_boosters'] == false ? false : true;

                // If the target robot (origin of damage) has an attachment with a damage multiplier
                $target_robot_attachments = $target_robot->get_current_attachments();
                if ($apply_origin_attachment_modifiers
                    && !empty($target_robot_attachments)){

                    // Loop through the target robot's attachments one-by-one and apply their modifiers
                    foreach ($target_robot_attachments AS $temp_token => $temp_info){
                        $temp_token_debug = str_replace('item_', 'attachment_', $temp_token);
                        if (!empty($temp_info['attachment_supressed'])){ continue; }

                        // First check to see if any basic breakers or boosters have been created for this robot
                        if (true){
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_origin_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_breaker'])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_origin_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_booster'])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage output breaker value set
                            if ($apply_origin_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_output_breaker'])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_output_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_output_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_output_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage output booster value set
                            if ($apply_origin_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_output_booster'])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_output_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_output_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_output_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($temp_item_damage_type)){
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_origin_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_breaker_'.$temp_item_damage_type])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_breaker_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_breaker_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_breaker_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_origin_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_booster_'.$temp_item_damage_type])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_booster_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_booster_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_booster_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_origin_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_output_breaker_'.$temp_item_damage_type])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_output_breaker_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_output_breaker_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_output_breaker_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_origin_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_output_booster_'.$temp_item_damage_type])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_output_booster_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_output_booster_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_output_booster_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($temp_item_damage_type2)){
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_origin_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_breaker_'.$temp_item_damage_type2])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_breaker_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_breaker_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_breaker_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_origin_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_booster_'.$temp_item_damage_type2])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_booster_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_booster_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_booster_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_origin_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_output_breaker_'.$temp_item_damage_type2])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_output_breaker_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_output_breaker_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_output_breaker_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_origin_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_output_booster_'.$temp_item_damage_type2])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_output_booster_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_output_booster_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_output_booster_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }

                    }

                }

                // Pre-determine which attachment target attachment modifiers we're allowed to apply
                $apply_target_attachment_modifiers = isset($trigger_options['apply_target_attachment_modifiers']) && $trigger_options['apply_target_attachment_modifiers'] == false ? false : true;
                $apply_target_attachment_damage_breakers = isset($trigger_options['apply_target_attachment_damage_breakers']) && $trigger_options['apply_target_attachment_damage_breakers'] == false ? false : true;
                $apply_target_attachment_damage_boosters = isset($trigger_options['apply_target_attachment_damage_boosters']) && $trigger_options['apply_target_attachment_damage_boosters'] == false ? false : true;

                // If this robot (target of damage) has an attachment with a damage multiplier
                $this_robot_attachments = $this_robot->get_current_attachments();
                if ($apply_target_attachment_modifiers
                    && !empty($this_robot_attachments)){

                    // Loop through this robot's attachments one-by-one and apply their modifiers
                    foreach ($this_robot_attachments AS $temp_token => $temp_info){
                        $temp_token_debug = str_replace('item_', 'attachment_', $temp_token);
                        if (!empty($temp_info['attachment_supressed'])){ continue; }

                        // First check to see if any basic breakers or boosters have been created for this robot
                        if (true){
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_target_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_breaker'])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_target_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_booster'])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage input breaker value set
                            if ($apply_target_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_input_breaker'])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_input_breaker']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_input_breaker | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_input_breaker'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage input booster value set
                            if ($apply_target_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_input_booster'])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_input_booster']);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_input_booster | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_input_booster'].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($temp_item_damage_type)){
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_target_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_breaker_'.$temp_item_damage_type])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_breaker_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_breaker_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_breaker_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_target_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_booster_'.$temp_item_damage_type])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_booster_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_booster_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_booster_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_target_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_input_breaker_'.$temp_item_damage_type])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_input_breaker_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_input_breaker_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_input_breaker_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_target_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_input_booster_'.$temp_item_damage_type])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_input_booster_'.$temp_item_damage_type]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_input_booster_'.$temp_item_damage_type.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_input_booster_'.$temp_item_damage_type].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }
                        // Next check to see if any breakers or boosters for either of this item's types
                        if (!empty($temp_item_damage_type2)){
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_target_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_breaker_'.$temp_item_damage_type2])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_breaker_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_breaker_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_breaker_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_target_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_booster_'.$temp_item_damage_type2])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_booster_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_booster_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_booster_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage breaker value set
                            if ($apply_target_attachment_damage_breakers
                                && isset($temp_info['attachment_damage_input_breaker_'.$temp_item_damage_type2])){
                                // Apply the damage breaker multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_input_breaker_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_input_breaker_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_input_breaker_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                            // If this robot's attachment has a damage booster value set
                            if ($apply_target_attachment_damage_boosters
                                && isset($temp_info['attachment_damage_input_booster_'.$temp_item_damage_type2])){
                                // Apply the damage booster multiplier to the current damage amount
                                $temp_new_amount = ($this_item->item_results['this_amount'] * $temp_info['attachment_damage_input_booster_'.$temp_item_damage_type2]);
                                $this_battle->events_debug(__FILE__, __LINE__, 'item_'.$this_item->item_token.' vs. '.$temp_token_debug.' <br /> attachment_damage_input_booster_'.$temp_item_damage_type2.' | '.$this_item->item_results['this_amount'].' = ('.$this_item->item_results['this_amount'].' * '.$temp_info['attachment_damage_input_booster_'.$temp_item_damage_type2].') = '.$temp_new_amount.'');
                                $this_item->item_results['this_amount'] = $temp_new_amount;
                            }
                        }

                    }

                }

                // Round the resulting damage after applying all modifiers
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
                if ($trigger_options['apply_modifiers'] != false && !$this_item->damage_options['damage_percent']){
                if (!empty($this_item->item_results['flag_critical'])){ $this_flag_string[] = 'critical'; }
                }
            }
            $this_flag_name = (!empty($this_flag_string) ? implode('_', $this_flag_string).'_' : '').'text';

            // Generate the status text based on flags
            if (isset($this_item->damage_options[$this_flag_name])){
                //$event_options['console_container_height'] = 2;
                //$this_item->item_results['this_text'] .= '<br />';
                $this_item->item_results['this_text'] .= ' '.$this_item->damage_options[$this_flag_name];
            }

            // Display a break before the damage amount if other text was generated
            if (!empty($this_item->item_results['this_text'])){
                $this_item->item_results['this_text'] .= '<br />';
            }

            // Ensure the damage amount is always at least one, unless absolute zero
            if ($this_item->item_results['this_amount'] < 1 && $this_item->item_results['this_amount'] > 0){ $this_item->item_results['this_amount'] = 1; }

            // Reference the requested damage kind with a shorter variable
            $this_item->damage_options['damage_kind'] = strtolower($this_item->damage_options['damage_kind']);
            $damage_stat_name = 'robot_'.$this_item->damage_options['damage_kind'];

            // Inflict the approiate damage type based on the damage options
            switch ($damage_stat_name){

                // If this is an ATTACK type damage trigger
                case 'robot_attack': {
                    // Inflict attack damage on the target's internal stat
                    $this_robot->robot_attack = $this_robot->robot_attack - $this_item->item_results['this_amount'];
                    // If the damage put the robot's attack below zero
                    if ($this_robot->robot_attack < MMRPG_SETTINGS_STATS_MIN){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = $this_robot->robot_attack * -1;
                        // Calculate the actual damage amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] + $this_robot->robot_attack;
                        // Zero out the robots attack
                        $this_robot->robot_attack = MMRPG_SETTINGS_STATS_MIN;
                    }
                    // Break from the ATTACK case
                    break;
                }
                // If this is an DEFENSE type damage trigger
                case 'robot_defense': {
                    // Inflict defense damage on the target's internal stat
                    $this_robot->robot_defense = $this_robot->robot_defense - $this_item->item_results['this_amount'];
                    // If the damage put the robot's defense below zero
                    if ($this_robot->robot_defense < MMRPG_SETTINGS_STATS_MIN){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = $this_robot->robot_defense * -1;
                        // Calculate the actual damage amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] + $this_robot->robot_defense;
                        // Zero out the robots defense
                        $this_robot->robot_defense = MMRPG_SETTINGS_STATS_MIN;
                    }
                    // Break from the DEFENSE case
                    break;
                }
                // If this is an SPEED type damage trigger
                case 'robot_speed': {
                    // Inflict attack damage on the target's internal stat
                    $this_robot->robot_speed = $this_robot->robot_speed - $this_item->item_results['this_amount'];
                    // If the damage put the robot's speed below zero
                    if ($this_robot->robot_speed < MMRPG_SETTINGS_STATS_MIN){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = $this_robot->robot_speed * -1;
                        // Calculate the actual damage amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] + $this_robot->robot_speed;
                        // Zero out the robots speed
                        $this_robot->robot_speed = MMRPG_SETTINGS_STATS_MIN;
                    }
                    // Break from the SPEED case
                    break;
                }
                // If this is a WEAPONS type damage trigger
                case 'robot_weapons': {
                    // Inflict weapon damage on the target's internal stat
                    $this_robot->robot_weapons = $this_robot->robot_weapons - $this_item->item_results['this_amount'];
                    // If the damage put the robot's weapons below zero
                    if ($this_robot->robot_weapons < MMRPG_SETTINGS_STATS_MIN){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = $this_robot->robot_weapons * -1;
                        // Calculate the actual damage amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] + $this_robot->robot_weapons;
                        // Zero out the robots weapons
                        $this_robot->robot_weapons = MMRPG_SETTINGS_STATS_MIN;
                    }
                    // Break from the WEAPONS case
                    break;
                }
                // If this is an ENERGY type damage trigger
                case 'robot_energy': default: {
                    // Inflict the actual damage on the robot
                    $this_robot->robot_energy = $this_robot->robot_energy - $this_item->item_results['this_amount'];
                    // If the damage put the robot into overkill, recalculate the damage
                    if ($this_robot->robot_energy < MMRPG_SETTINGS_STATS_MIN){
                        // Calculate the overkill amount
                        $this_item->item_results['this_overkill'] = $this_robot->robot_energy * -1;
                        // Calculate the actual damage amount
                        $this_item->item_results['this_amount'] = $this_item->item_results['this_amount'] + $this_robot->robot_energy;
                        // Zero out the robots energy
                        $this_robot->robot_energy = MMRPG_SETTINGS_STATS_MIN;
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

            // Check to see if affection values play into this at all (being hit knocks some of the infatuations out of the robot)
            if (!empty($this_robot->counters['affection'][$target_robot->robot_token])
                || !empty($target_robot->counters['affection'][$this_robot->robot_token])){
                //error_log('this robot ('.$this_robot->robot_string.') is being damaged by the target robot ('.$target_robot->robot_string.')');

                // (does this robot like the target robot? NOT ANYMORE!)
                if (!empty($this_robot->counters['affection'][$target_robot->robot_token])){
                    //error_log('this robot ('.$this_robot->robot_string.') used to like the target robot ('.$target_robot->robot_string.')');
                    $old_affection_value = $this_robot->counters['affection'][$target_robot->robot_token];
                    //error_log('$old_affection_value = '.$old_affection_value);
                    // this robot is being hit by the target, which decreases the affection value toward them
                    $new_affection_value = $old_affection_value - $this_item->item_results['this_amount'];
                    //error_log('$new_affection_value = '.$new_affection_value);
                    if ($new_affection_value > 0){ $this_robot->counters['affection'][$target_robot->robot_token] = $new_affection_value; }
                    else { unset($this_robot->counters['affection'][$target_robot->robot_token]); }
                    //error_log('affection '.(isset($this_robot->counters['affection'][$target_robot->robot_token]) ? 'persists' : 'removed'));
                }

            }

            // Define the print variables to return
            $this_item->item_results['print_strikes'] = '<span class="damage_strikes">'.(!empty($this_item->item_results['total_strikes']) ? $this_item->item_results['total_strikes'] : 0).'</span>';
            $this_item->item_results['print_misses'] = '<span class="damage_misses">'.(!empty($this_item->item_results['total_misses']) ? $this_item->item_results['total_misses'] : 0).'</span>';
            $this_item->item_results['print_result'] = '<span class="damage_result">'.(!empty($this_item->item_results['total_result']) ? $this_item->item_results['total_result'] : 0).'</span>';
            $this_item->item_results['print_amount'] = '<span class="damage_amount">'.(!empty($this_item->item_results['this_amount']) ? $this_item->item_results['this_amount'] : 0).'</span>';
            $this_item->item_results['print_overkill'] = '<span class="damage_overkill">'.(!empty($this_item->item_results['this_overkill']) ? $this_item->item_results['this_overkill'] : 0).'</span>';

            // Add the final damage text showing the amount based on damage type
            if ($this_item->damage_options['damage_kind'] == 'energy'){
                $this_item->item_results['this_text'] .= "{$this_robot->print_name()} takes {$this_item->item_results['print_amount']} life energy damage";
                $this_item->item_results['this_text'] .= ($this_item->item_results['this_overkill'] > 0 && $this_robot->player->player_side == 'right' ? " and {$this_item->item_results['print_overkill']} overkill" : '');
                $this_item->item_results['this_text'] .= '!<br />';
            }
            // Otherwise add the final damage text showing the amount based on weapon energy damage
            elseif ($this_item->damage_options['damage_kind'] == 'weapons'){
                $this_item->item_results['this_text'] .= "{$this_robot->print_name()} takes {$this_item->item_results['print_amount']} weapon energy damage";
                $this_item->item_results['this_text'] .= '!<br />';
            }
            // Otherwise, if this is one of the robot's other internal stats
            elseif ($this_item->damage_options['damage_kind'] == 'attack'
                || $this_item->damage_options['damage_kind'] == 'defense'
                || $this_item->damage_options['damage_kind'] == 'speed'){
                // Print the result based on if the stat will go any lower
                if ($this_item->item_results['this_amount'] > 0){
                    $this_item->item_results['this_text'] .= "{$this_robot->print_name()}&#39;s {$this_item->damage_options['damage_kind']} fell by {$this_item->item_results['print_amount']}";
                    $this_item->item_results['this_text'] .= '!<br />';
                }
                // Otherwise if the stat wouldn't go any lower
                else {

                    // Update this robot's frame based on damage type
                    $this_item->item_frame = $this_item->damage_options['item_failure_frame'];
                    $this_item->item_frame_span = $this_item->damage_options['item_failure_frame_span'];
                    $this_item->item_frame_offset = $this_item->damage_options['item_failure_frame_offset'];

                    // Display the failure text, if text has been provided
                    if (!empty($this_item->damage_options['failure_text'])){
                        $this_item->item_results['this_text'] .= $this_item->damage_options['failure_text'].' ';
                    }
                }
            }

        }
        // Otherwise, if the attack was a failure
        else {

            // Update this robot's frame based on damage type
            $this_item->item_frame = $this_item->damage_options['item_failure_frame'];
            $this_item->item_frame_span = $this_item->damage_options['item_failure_frame_span'];
            $this_item->item_frame_offset = $this_item->damage_options['item_failure_frame_offset'];

            // Update the damage and overkilll amounts to reflect zero damage
            $this_item->item_results['this_amount'] = 0;
            $this_item->item_results['this_overkill'] = 0;

            // Display the failure text, if text has been provided
            if (!$this_item->item_results['flag_immunity'] && !empty($this_item->damage_options['failure_text'])){
                $this_item->item_results['this_text'] .= $this_item->damage_options['failure_text'].' ';
            }

        }

        // Only update triggered damage history if damage was actually dealt
        if ($this_item->item_results['this_amount'] > 0){

            // Update this robot's history with the triggered damage amount
            $this_robot->history['triggered_damage'][] = $this_item->item_results['this_amount'];
            $this_robot->history['triggered_damage_by'][] = $this_item->item_token;

            // Update the robot's history with the triggered damage types
            if (!empty($this_item->item_results['damage_type'])){
                $temp_types = array();
                $temp_types[] = $this_item->item_results['damage_type'];
                if (!empty($this_item->item_results['damage_type2'])){ $temp_types[] = $this_item->item_results['damage_type2']; }
                $this_robot->history['triggered_damage_types'][] = $temp_types;
            } else {
                $this_robot->history['triggered_damage_types'][] = null; //array();
            }

        }

        // Check to see if damage overkill was inflicted by the target
        if (!empty($this_item->item_results['this_overkill'])){

            // Collect the overkill amount to boost
            $overkill_value = $this_item->item_results['this_overkill'];

            $this_battle->events_debug(__FILE__, __LINE__, $this_robot->robot_token.' | item overkill | value : '.$overkill_value);

            // Update this robot's history with the overkill if applicable
            if (isset($this_robot->counters['defeat_overkill'])){ $this_robot->counters['defeat_overkill'] += $overkill_value; }
            else { $this_robot->counters['defeat_overkill'] = $overkill_value; }

            $this_battle->events_debug(__FILE__, __LINE__, $this_robot->robot_token.' | robot default overkill | mod : +'.$overkill_value.' | new_value : '.$this_robot->counters['defeat_overkill']);

            // Update the other player's history with the overkill bonus if applicable
            if (isset($target_robot->player->counters['overkill_bonus'])){ $target_robot->player->counters['overkill_bonus'] += $overkill_value; }
            else { $target_robot->player->counters['overkill_bonus'] = $overkill_value; }

            $this_battle->events_debug(__FILE__, __LINE__, $target_robot->player->player_token.' | player overkill bonus | mod : +'.$overkill_value.' | new_value : '.$target_robot->player->counters['overkill_bonus']);

        }

        // Update the damage result total variables
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

        // If this robot was at full energy but is now at zero, it's a OHKO
        $this_item->item_results['energy_ohko'] = false;
        if ($this_robot->robot_energy <= 0 && $this_robot_energy_start_max){
            $this_battle->events_debug(__FILE__, __LINE__, $this_item->item_token.' | damage_result_OHKO! | Start:'.$this_robot_energy_start.' '.($this_robot_energy_start_max ? '(MAX!)' : '-').' | Finish:'.$this_robot->robot_energy);
            // Ensure the attacking player was a human
            if ($this_robot->player->player_side == 'right'){
                $this_item->item_results['energy_ohko'] = true;
            }
        }

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-item_trigger-damage_middle', $extra_objects_for_this_robot);
        $target_robot->trigger_custom_function('rpg-item_trigger-damage_middle', $extra_objects_for_other_robot);
        if ($options->return_early){ return $options->return_value; }

        // Define the sound effects for this damage event so it plays for the player
        $damage_sounds = array();
        if ($this_item->item_results['this_amount'] > 0){
            $damage_sounds = array();
            if (!empty($this_item->item_results['flag_weakness'])
                || !empty($this_item->item_results['flag_critical'])){
                $damage_sounds[] = array('name' => 'damage-critical', 'volume' => 0.9);
                $damage_sounds[] = array('name' => 'damage-reverb', 'volume' => 1.0, 'delay' => 100);
                $damage_sounds[] = array('name' => 'damage-reverb', 'volume' => 0.8, 'delay' => 200);
            } elseif (!empty($this_item->item_results['flag_resistance'])){
                $damage_sounds[] = array('name' => 'damage-reduced', 'volume' => 0.9);
            } elseif ($this_item->item_results['this_amount'] === 1){
                $damage_sounds[] = array('name' => 'damage-hindered', 'volume' => 0.9);
            } else {
                $damage_sounds[] = array('name' => 'damage', 'volume' => 0.9);
                $damage_sounds[] = array('name' => 'damage-reverb', 'volume' => 0.8, 'delay' => 100);
            }
            if (!empty($this_item->item_results['energy_ohko'])){
                $delay = count($damage_sounds) * 100;
                $damage_sounds[] = array('name' => 'damage-reverb', 'volume' => 0.8, 'delay' => $delay + 100);
                $damage_sounds[] = array('name' => 'damage-reverb', 'volume' => 0.8, 'delay' => $delay + 200);
                $damage_sounds[] = array('name' => 'damage-reverb', 'volume' => 0.8, 'delay' => $delay + 300);
            }
        } else {
            $damage_sounds[] = array('name' => 'no-effect');
        }
        foreach ($damage_sounds AS $damage_sound){
            $this_battle->queue_sound_effect($damage_sound);
        }

        // Generate an event with the collected damage results based on damage type
        $temp_event_header = $this_item->damage_options['damage_header'];
        $temp_event_body = $this_item->item_results['this_text'];
        if ($this_robot->robot_id == $target_robot->robot_id){
            $event_options['console_show_target'] = false;
            $event_options['this_item_target'] = $this_robot->robot_id.'_'.$this_robot->robot_token;
            $temp_event_body = str_replace('{this_robot}', $this_robot->print_name(), $temp_event_body);
            $temp_event_body = str_replace('{target_robot}', $target_robot->print_name(), $temp_event_body);
            $this_robot->battle->events_create($target_robot, $this_robot, $this_item->damage_options['damage_header'], $this_item->item_results['this_text'], $event_options);
        } else {
            $event_options['console_show_target'] = false;
            $event_options['this_item_target'] = $this_robot->robot_id.'_'.$this_robot->robot_token;
            $temp_event_body = str_replace('{this_robot}', $target_robot->print_name(), $temp_event_body);
            $temp_event_body = str_replace('{target_robot}', $this_robot->print_name(), $temp_event_body);
            $this_robot->battle->events_create($this_robot, $target_robot, $this_item->damage_options['damage_header'], $this_item->item_results['this_text'], $event_options);
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
            $disabled_trigger_options = array();
            if ($this_item->item_results['energy_ohko']){ $disabled_trigger_options['item_multiplier'] = 2.0; }
            $this_robot->trigger_disabled($target_robot, $disabled_trigger_options);
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

        // If this robot has an ondamage function, trigger it
        $this_ondamage_function = $this_robot->robot_function_ondamage;
        $temp_result = $this_ondamage_function(array(
            'this_battle' => $this_battle,
            'this_field' => $this_battle->battle_field,
            'this_player' => $this_robot->player,
            'this_robot' => $this_robot,
            'target_player' => $this_robot->player,
            'target_robot' => $target_robot,
            'this_item' => $this_item
            ));

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-item_trigger-damage_after', $extra_objects_for_this_robot);
        $target_robot->trigger_custom_function('rpg-item_trigger-damage_after', $extra_objects_for_other_robot);

        // Return the final damage results
        return $this_item->item_results;

    }

}
?>