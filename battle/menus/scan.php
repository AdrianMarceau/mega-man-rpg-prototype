<?

// Generate the markup for the action scan panel
ob_start();

    // Define and start the order counter
    $temp_order_counter = 1;

    // Display container for the main actions
    ?><div class="main_actions main_actions_hastitle"><span class="main_actions_title">Select Scan Target</span><?

    // Ensure there are robots to display
    if (!empty($target_player->player_robots)){

        // Count the total number of robots
        $num_robots = count($target_player->player_robots);
        $robot_direction = $target_player->player_side == 'left' ? 'right' : 'left';

        // Collect the target robot options and sort them
        $target_player_robots = $target_player->player_robots;
        usort($target_player_robots, 'rpg_prototype::sort_robots_for_battle_menu');

        // Collect the temp item index
        $mmrpg_items_index = rpg_item::get_index(true);

        // Loop through each robot and display its target button
        foreach ($target_player_robots AS $robot_key => $scan_robotinfo){
            // Ensure this is an actual switch in the index
            if (!empty($switch_robotinfo['robot_token'])){

                // Default the allow button flag to true
                $allow_button = true;

                // Create the scan object using the session/index data
                $temp_robot = rpg_game::get_robot($this_battle, $target_player, $scan_robotinfo);

                // If this robot is disabled, disable the button
                if ($temp_robot->robot_status == 'disabled'){ $allow_button = false; }

                // Define the title hover for the robot
                $temp_robot_title = $temp_robot->robot_name.'  (Lv. '.$temp_robot->robot_level.')';
                //$temp_robot_title .= ' | '.$temp_robot->robot_id.'';
                $temp_robot_title .= ' <br />'.(!empty($temp_robot->robot_core) ? ucfirst($temp_robot->robot_core).' Core' : 'Neutral Core').' | '.ucfirst($temp_robot->robot_position).' Position';

                // Display the robot's item if it exists
                if (!empty($temp_robot->robot_item) && !empty($mmrpg_items_index[$temp_robot->robot_item])){ $temp_robot_title .= ' | + '.$mmrpg_items_index[$temp_robot->robot_item]['item_name'].' '; }

                // Display the robot's life and weapon energy current and base
                $temp_robot_title .= ' <br />'.$temp_robot->robot_energy.' / '.$temp_robot->robot_base_energy.' LE';
                $temp_robot_title .= ' | '.$temp_robot->robot_weapons.' / '.$temp_robot->robot_base_weapons.' WE';
                if ($robot_direction == 'right' && $temp_robot->robot_class != 'mecha'){
                    $temp_robot_title .= ' | '.$temp_robot->robot_experience.' / 1000 EXP';
                }
                $temp_robot_title .= ' <br />'.$temp_robot->robot_attack.' / '.$temp_robot->robot_base_attack.' AT';
                $temp_robot_title .= ' | '.$temp_robot->robot_defense.' / '.$temp_robot->robot_base_defense.' DF';
                $temp_robot_title .= ' | '.$temp_robot->robot_speed.' / '.$temp_robot->robot_base_speed.' SP';

                // Encode the tooltip for markup insertion and create a plain one too
                $temp_robot_title_plain = strip_tags(str_replace('<br />', '&#10;', $temp_robot_title));
                $temp_robot_title_tooltip = htmlentities($temp_robot_title, ENT_QUOTES, 'UTF-8');

                // Collect the robot's core types for display
                $temp_robot_core_type = !empty($temp_robot->robot_core) ? $temp_robot->robot_core : 'none';
                $temp_robot_core2_type = !empty($temp_robot->robot_core2) ? $temp_robot->robot_core2 : '';
                if (!empty($temp_robot->robot_item) && preg_match('/-core$/', $temp_robot->robot_item)){
                    $temp_item_core_type = preg_replace('/-core$/', '', $temp_robot->robot_item);
                    if (empty($temp_robot_core2_type) && $temp_robot_core_type != $temp_item_core_type){ $temp_robot_core2_type = $temp_item_core_type; }
                }

                // Collect the energy and weapon percent so we know how they're doing
                $temp_energy_class = rpg_prototype::calculate_percentage_tier($temp_robot->robot_energy, $temp_robot->robot_base_energy);
                $temp_weapons_class = rpg_prototype::calculate_percentage_tier($temp_robot->robot_weapons, $temp_robot->robot_base_weapons);

                // Define the robot button text variables
                $temp_robot_label = '<span class="multi">';
                    $temp_robot_label .= '<span class="maintext">'.$temp_robot->robot_name.' <sup class="level">Lv. '.$temp_robot->robot_level.'</sup></span>';
                    $temp_robot_label .= '<span class="subtext">';
                        $temp_robot_label .= '<span class="stat_is_'.$temp_energy_class.'"><strong>'.$temp_robot->robot_energy.'</strong>/'.$temp_robot->robot_base_energy.' LE</span>';
                    $temp_robot_label .= '</span>';
                    $temp_robot_label .= '<span class="subtext">';
                        $temp_robot_label .= '<span class="stat_is_'.$temp_weapons_class.'"><strong>'.$temp_robot->robot_weapons.'</strong>/'.$temp_robot->robot_base_weapons.' WE</span>';
                    $temp_robot_label .= '</span>';
                $temp_robot_label .= '</span>';

                // Define the robot sprite variables
                $temp_robot_sprite = array();
                $temp_robot_sprite['name'] = $temp_robot->robot_name;
                $temp_robot_sprite['image'] = $temp_robot->robot_image;
                $temp_robot_sprite['image_size'] = $temp_robot->robot_image_size;
                $temp_robot_sprite['image_size_text'] = $temp_robot_sprite['image_size'].'x'.$temp_robot_sprite['image_size'];
                $temp_robot_sprite['image_size_zoom'] = $temp_robot->robot_image_size * 2;
                $temp_robot_sprite['image_size_zoom_text'] = $temp_robot_sprite['image_size'].'x'.$temp_robot_sprite['image_size'];
                $temp_robot_sprite['url'] = 'images/robots/'.$temp_robot->robot_image.'/sprite_'.$robot_direction.'_'.$temp_robot_sprite['image_size_text'].'.png';
                $temp_robot_sprite['preload'] = 'images/robots/'.$temp_robot->robot_image.'/sprite_'.$robot_direction.'_'.$temp_robot_sprite['image_size_zoom_text'].'.png';
                $temp_robot_sprite['class'] = 'sprite sprite_'.$temp_robot_sprite['image_size_text'].' sprite_'.$temp_robot_sprite['image_size_text'].'_'.($temp_robot->robot_energy > 0 ? ($temp_robot->robot_energy > ($temp_robot->robot_base_energy/2) ? 'base' : 'defend') : 'defeat').' ';
                $temp_robot_sprite['style'] = 'background-image: url('.$temp_robot_sprite['url'].'?'.MMRPG_CONFIG_CACHE_DATE.');  top: 5px; left: 5px; ';
                if ($temp_robot->robot_position == 'active'){ $temp_robot_sprite['style'] .= 'border-color: #ababab; '; }
                $temp_robot_sprite['class'] .= 'sprite_'.$temp_robot_sprite['image_size_text'].'_energy_'.$temp_energy_class.' ';
                $temp_robot_sprite['markup'] = '<span class="'.$temp_robot_sprite['class'].'" style="'.$temp_robot_sprite['style'].'">'.$temp_robot_sprite['name'].'</span>';

                // Update the order button if necessary
                $order_button_markup = $allow_button ? 'data-order="'.$temp_order_counter.'"' : '';
                $temp_order_counter += $allow_button ? 1 : 0;

                // Now use the new object to generate a snapshot of this switch button
                $btn_type = 'robot_type robot_type_'.(!empty($temp_robot->robot_core) ? $temp_robot->robot_core : 'none').(!empty($temp_robot->robot_core2_type) ? '_'.$temp_robot->robot_core2_type : '');
                $btn_class = 'button action_scan scan_'.$temp_robot->robot_token.' '.$btn_type.' block_'.($robot_key + 1).' ';
                $btn_action = 'scan_'.$temp_robot->robot_id.'_'.$temp_robot->robot_token;
                $btn_info_circle = '<span class="info" data-click-tooltip="'.$temp_robot_title_tooltip.'" data-tooltip-type="'.$btn_type.'"><i class="fa fas fa-info-circle"></i></span>';
                if ($allow_button){
                    echo('<a type="button" class="'.$btn_class.'" data-action="'.$btn_action.'" data-preload="'.$temp_robot_sprite['preload'].'" data-position="'.$temp_robot->robot_position.'/'.$temp_robot->robot_key.'" '.$order_button_markup.'>'.
                            '<label>'.
                                $btn_info_circle.
                                $temp_robot_sprite['markup'].
                                $temp_robot_label.
                            '</label>'.
                        '</a>');
                } else {
                    $btn_class .= 'button_disabled ';
                    echo('<a type="button" class="'.$btn_class.'">'.
                            '<label>'.
                                $btn_info_circle.
                                $temp_robot_sprite['markup'].
                                $temp_robot_label.
                            '</label>'.
                        '</a>');
                }

            }
        }

        // If there were less than 8 robots, fill in the empty spaces
        if ($num_robots < 8){
            for ($i = $num_robots; $i < 8; $i++){
                // Display an empty button placeholder
                ?><a class="button action_scan button_disabled block_<?= $i + 1 ?>" type="button">&nbsp;</a><?
            }
        }

    }

    // End the main action container tag
    ?></div><?

    // Display the back button by default
    ?><div class="sub_actions"><a data-order="<?=$temp_order_counter?>" class="button action_back" type="button" data-panel="battle"><label>Back</label></a></div><?

    // Increment the order counter
    $temp_order_counter++;

$actions_markup['scan'] = trim(ob_get_clean());
$actions_markup['scan'] = preg_replace('#\s+#', ' ', $actions_markup['scan']);
?>