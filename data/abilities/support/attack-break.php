<?
// ATTACK BREAK
$ability = array(
    'ability_name' => 'Attack Break',
    'ability_token' => 'attack-break',
    'ability_game' => 'MMRPG',
    'ability_group' => 'MMRPG/Support/Attack',
    'ability_description' => 'The user breaks down the target&#39;s weapons, lowering its attack by {DAMAGE}%!',
    'ability_energy' => 4,
    'ability_damage' => 15,
    'ability_damage_percent' => true,
    'ability_accuracy' => 95,
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, -2, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Decrease the target robot's attack stat
        $this_ability->damage_options_update(array(
            'kind' => 'attack',
            'percent' => true,
            'kickback' => array(10, 0, 0),
            'success' => array(0, -2, 0, -10, $target_robot->print_name().'&#39;s weapons were damaged!'),
            'failure' => array(9, -2, 0, -10, 'It had no effect on '.$target_robot->print_name().'&hellip;')
            ));
        $attack_damage_amount = ceil($target_robot->robot_attack * ($this_ability->ability_damage / 100));
        $target_robot->trigger_damage($this_robot, $this_ability, $attack_damage_amount);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this ability is being used by a special support robot, allow targetting
        $temp_support_robots = array('roll', 'disco', 'rhythm');
        if (in_array($this_robot->robot_token, $temp_support_robots) && $target_player->counters['robots_active'] > 1){

            // Update this ability's targetting setting
            $this_ability->ability_target = 'select_target';
            $this_ability->update_session();

        }
        // Else if the ability attachment is not there, change the target back to auto
        else {

            // Update this ability's targetting setting
            $this_ability->ability_target = 'auto';
            $this_ability->update_session();

        }

        // Return true on success
        return true;

        }
    );
?>