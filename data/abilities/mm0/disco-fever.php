<?
// DISCO FEVER
$ability = array(
    'ability_name' => 'Disco Fever',
    'ability_token' => 'disco-fever',
    'ability_game' => 'MMRPG',
    'ability_group' => 'MM00/Weapons/Disco',
    'ability_description' => 'The user summons a spinning disco ball that hovers above a target and diverts its attention. This causes the target deal half damage from attacks for the next six turns.',
    'ability_type' => 'laser',
    'ability_energy' => 4,
    'ability_accuracy' => 100,
    'ability_target' => 'select_target',
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the base attachment duration
        $base_attachment_duration = 6;
        $base_attachment_multiplier = 0.5;

        // Define this ability's overlay effect token
        $this_overlay_token = 'effect_'.$this_ability->ability_token.'_'.$target_robot->robot_id;
        $this_overlay_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => 'ability',
            'ability_image' => 'ability-effect_black-overlay',
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 1),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -20),
            'ability_frame_classes' => 'sprite_fullscreen ',
            'attachment_token' => $this_overlay_token
            );

        // Create the attachment object for this ability
        $this_overlay = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_overlay_info);

        // Update the ability image if the user is in their alt image
        $alt_image_triggers = array('disco_alt', 'disco_alt3', 'disco_alt5');
        if (in_array($this_robot->robot_image, $alt_image_triggers)){
            $this_ability->set_image($this_ability->ability_token.'-b');
        }

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_'.$target_robot->robot_id;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $base_attachment_duration,
            'attachment_damage_output_breaker' => $base_attachment_multiplier,
            'attachment_create' => array(
                'trigger' => 'special',
                'kind' => '',
                'percent' => true,
                'frame' => 'taunt',
                'rates' => array(100, 0, 0),
                'success' => array(0, 30, 0, 30, 'The '.$this_ability->print_name().' hypnotized '.$target_robot->print_name().'!<br /> '.$target_robot->print_name().'&#39;s weapons were compromised!'),
                'failure' => array(0, 30, 0, 30, 'The '.$this_ability->print_name().' hypnotized '.$target_robot->print_name().'!<br /> '.$target_robot->print_name().'&#39;s weapons were compromised!')
                ),
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(2, 30, 0, 30,  'The '.$this_ability->print_name().' faded away!<br /> '.$target_robot->print_name().'&#39;s weapons returned to normal!'),
                'failure' => array(2, 30, 0, 30, 'The '.$this_ability->print_name().' faded away!<br /> '.$target_robot->print_name().'&#39;s weapons returned to normal!')
                ),
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 1, 2, 1),
            'ability_frame_offset' => array('x' => 30, 'y' => 0, 'z' => 20)
            );

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_attachment_info);

        // Add the black background overlay attachment
        $target_robot->robot_attachments[$this_overlay_token] = $this_overlay_info;
        $target_robot->update_session();

        // If the ability flag was not set, attach the ability to the target
        if (!isset($target_robot->robot_attachments[$this_attachment_token])){

            // Target this robot's self
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -10, 0, -18, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // Target this robot's self
            $this_robot->robot_frame = 'base';
            $this_robot->update_session();
            $this_attachment->target_options_update($this_attachment_info['attachment_create']);
            $target_robot->trigger_target($target_robot, $this_attachment);

            // Attach this ability attachment to the robot using it
            $this_attachment_info['ability_frame_animate'] = array(0, 1, 2, 1);
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

        }
        // Else if the ability flag was set, reinforce the fever by one more duration point
        else {

            // Collect the attachment from the robot to back up its info
            $this_attachment_info = $target_robot->robot_attachments[$this_attachment_token];
            $this_attachment_info['attachment_duration'] = $base_attachment_duration;
            $this_attachment_info['attachment_damage_output_breaker'] = $this_attachment_info['attachment_damage_output_breaker'] * $base_attachment_multiplier;
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

            // Target the opposing robot
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(9, -10, 0, -10, $this_robot->print_name().' intensified the effects of the '.$this_ability->print_name().'!<br /> The duration of '.$target_robot->print_name().'&#39;s trance was extended!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

        }

        // Add the black background overlay attachment
        unset($target_robot->robot_attachments[$this_overlay_token]);
        $target_robot->update_session();

        // Either way, update this ability's settings to prevent recovery
        $this_attachment->damage_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->recovery_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->update_session();

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability image if the user is in their alt image
        $alt_image_triggers = array('disco_alt', 'disco_alt3', 'disco_alt5');
        if (in_array($this_robot->robot_image, $alt_image_triggers)){
            $this_ability->set_image($this_ability->ability_token.'-b');
        }

        // Return true on success
        return true;

        }
    );
?>