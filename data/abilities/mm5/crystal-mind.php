<?
// CRYSTAL MIND
$ability = array(
    'ability_name' => 'Crystal Mind',
    'ability_token' => 'crystal-mind',
    'ability_game' => 'MM05',
    //'ability_group' => 'MM05/Weapons/040',
    'ability_group' => 'MM05/Weapons/033T1',
    'ability_description' => 'The user enters a trance-like meditative state and stays there until the end of the turn.  Once the user has completed their meditation, all of their stats are raised by one stage!',
    'ability_type' => 'crystal',
    'ability_energy' => 4,
    'ability_accuracy' => 100,
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 1, 2, 3, 4),
            'ability_frame_offset' => array('x' => 5, 'y' => 40, 'z' => 30)
            );

        // If this ability has not been summoned yet, do the action and then queue a conclusion move
        $summoned_flag_token = $this_ability->ability_token.'_summoned';
        if (empty($this_robot->flags[$summoned_flag_token])){

            // Set the summoned flag on this robot and save
            $this_robot->set_flag($summoned_flag_token, true);

            // Update this robot's sprite to show them with an inverted pallet
            $this_robot->set_frame_styles('-moz-filter: invert(1); -webkit-filter: invert(1); filter: invert(1); ');

            // Attach this ability to the summoning robot
            $this_robot->set_attachment($this_attachment_token, $this_attachment_info);

            // Target the opposing robot
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, 9999, 9999, -9999,
                    $this_robot->print_name().' used the '.$this_ability->print_name().' technique! '.
                    '<br /> '.ucfirst($this_robot->get_pronoun('subject')).' fell into a deep trance...'
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

            // Queue another use of this ability at the end of turn
            $this_battle->actions_append(
                $this_player,
                $this_robot,
                $target_player,
                $target_robot,
                'ability',
                $this_ability->ability_id.'_'.$this_ability->ability_token,
                true
                );

        }
        // The ability has already been summoned, so we can finish executing it now and deal damage
        else {

            // Remove the summoned flag from this robot and save
            $this_robot->unset_flag($summoned_flag_token);

            // Remove the inverted styles from this robot's sprite
            $this_robot->set_frame_styles('');

            // Remove the attachment from the summoner
            $this_robot->unset_attachment($this_attachment_token);

            // Target the opposing robot
            $this_ability->target_options_update(array(
                'frame' => 'taunt',
                'success' => array(0, 9999, 9999, -9999,
                    $this_robot->print_name().' woke up from '.$this_robot->get_pronoun('possessive2').' trance... '.
                    '<br /> The '.$this_ability->print_name().' raises all of '.$this_robot->print_name().'\'s stats! '
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

            // Only raise the this robot's stats if they're not disabled
            if ($this_robot->robot_status != 'disabled'){

                // Call the global stat break functions with customized options
                rpg_ability::ability_function_stat_boost($this_robot, 'attack', 1);
                rpg_ability::ability_function_stat_boost($this_robot, 'defense', 1);
                rpg_ability::ability_function_stat_boost($this_robot, 'speed', 1);

            }

        }

        // Return true on success
        return true;

        }
    );
?>