<?
// BLIZZARD ATTACK
$ability = array(
    'ability_name' => 'Blizzard Attack',
    'ability_token' => 'blizzard-attack',
    'ability_game' => 'MM06',
    'ability_group' => 'MM06/Weapons/041',
    'ability_image_sheets' => 2,
    'ability_description' => 'The user summons a powerful blizzard that covers the screen and damages all robots on the opponent\'s side of the field!',
    'ability_type' => 'freeze',
    'ability_energy' => 4,
    'ability_damage' => 8,
    'ability_accuracy' => 98,
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Count the number of active robots on the target's side of the  field
        $target_robots_active_count = $target_player->counters['robots_active'];
        $target_robot_ids = array($target_robot->robot_id);
        $get_next_target_robot = function() use($this_battle, $target_player, &$target_robot_ids){
            foreach ($target_player->values['robots_active'] AS $key => $info){
                if (!in_array($info['robot_id'], $target_robot_ids)){
                    $target_robot_ids[] = $info['robot_id'];
                    $next_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                    return $next_target_robot;
                    }
                }
            };

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_base_image,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2, 3),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 100),
            'ability_frame_classes' => ' '
            );

        // Count the number of active robots on the target's side of the field
        $target_robots_active = $target_player->counters['robots_active'];

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_base_image;
        $this_ability->ability_frame_classes = '';
        $this_ability->update_session();

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(1, 0, 100, 10, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true, 'prevent_stats_text' => true));

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_base_image.'-2';
        $this_ability->ability_frame_classes = 'sprite_fullscreen ';
        $this_ability->update_session();

        // Ensure this robot stays in the summon position for the duration of the attack
        $this_robot->robot_frame = 'summon';
        $this_robot->update_session();


        // -- DAMAGE TARGETS -- //

        // Inflict damage on the opposing robot
        $target_robot->set_attachment($this_attachment_token.'_fx', $this_attachment_info);
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, 0, 99, 'The '.$this_ability->print_name().' battered a target with ice!'),
            'failure' => array(0, -5, 0, -10,'The '. $this_ability->print_name().' missed the first target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, 0, 9, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(0, -5, 0, 9, 'The '.$this_ability->print_name().' had no effect on the first target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
        $target_robot->unset_attachment($this_attachment_token.'_fx');

        // Loop through the target's benched robots, inflicting damage to each
        $backup_target_robots_active = $target_player->values['robots_active'];
        foreach ($backup_target_robots_active AS $key => $info){
            if ($info['robot_id'] == $target_robot->robot_id){ continue; }
            $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
            $temp_target_robot->set_attachment($this_attachment_token.'_fx', $this_attachment_info);
            $this_ability->ability_results_reset();
            $temp_positive_word = rpg_battle::random_positive_word();
            $temp_negative_word = rpg_battle::random_negative_word();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'kickback' => array(5, 0, 0),
                'success' => array(($key % 2), -5, 0, 99, ($target_player->player_side === 'right' ? $temp_positive_word : $temp_negative_word).' The attack hit another robot!'),
                'failure' => array(($key % 2), -5, 0, 99, 'The attack had no effect on '.$temp_target_robot->print_name().'&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'frame' => 'taunt',
                'kickback' => array(5, 0, 0),
                'success' => array(($key % 2), -5, 0, 9, ($target_player->player_side === 'right' ? $temp_negative_word : $temp_positive_word).' The attack was absorbed by the target!'),
                'failure' => array(($key % 2), -5, 0, 9, 'The attack had no effect on '.$temp_target_robot->print_name().'&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            $temp_target_robot->unset_attachment($this_attachment_token.'_fx');
        }

        // REMOVE ATTACHMENTS
        if (true){

            // Attach this ability to all robots on this player's side of the field
            $backup_robots_active = $this_player->values['robots_active'];
            $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
            if ($backup_robots_active_count > 0){
                $this_key = 0;
                foreach ($backup_robots_active AS $key => $info){
                    if ($info['robot_id'] == $this_robot->robot_id){ continue; }
                    $info2 = array('robot_id' => $info['robot_id'], 'robot_token' => $info['robot_token']);
                    $temp_this_robot = rpg_game::get_robot($this_battle, $this_player, $info2);
                    $temp_this_robot->robot_frame = 'base';
                    unset($temp_this_robot->robot_attachments[$temp_attachment_token]);
                    $temp_this_robot->update_session();
                    $this_key++;
                }
            }

        }


        // -- DISABLE FALLEN -- //

        // Trigger the disabled event on the targets now if necessary
        if ($target_robot->robot_status == 'disabled'){
            $target_robot->trigger_disabled($this_robot);
        }
        else { $target_robot->robot_frame = 'base'; }
        $target_robot->update_session();
        foreach ($backup_target_robots_active AS $key => $info){
            if ($info['robot_id'] == $target_robot->robot_id){ continue; }
            $info2 = array('robot_id' => $info['robot_id'], 'robot_token' => $info['robot_token']);
            $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info2);
            if ($temp_target_robot->robot_energy <= 0 || $temp_target_robot->robot_status == 'disabled'){
                $temp_target_robot->trigger_disabled($this_robot);
            }
            else { $temp_target_robot->robot_frame = 'base'; }
            $temp_target_robot->update_session();
        }

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_base_image;
        $this_ability->ability_frame_classes = '';
        $this_ability->update_session();

        // Return true on success
        return true;


        }
    );
?>