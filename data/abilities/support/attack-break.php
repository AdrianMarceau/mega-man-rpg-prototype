<?
// ATTACK BREAK
$ability = array(
    'ability_name' => 'Attack Break',
    'ability_token' => 'attack-break',
    'ability_game' => 'MMRPG',
    'ability_group' => 'MMRPG/Support/Attack',
    'ability_description' => 'The user breaks down the target\'s weapon systems sharply lowering its attack stat! When used by a support robot, this ability can optionally target the bench instead of the active position!',
    'ability_energy' => 6,
    'ability_accuracy' => 100,
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array('frame' => 'summon', 'success' => array(0, -2, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Call the global stat break function with customized options
        rpg_ability::ability_function_stat_break($target_robot, 'attack', 2, $this_ability);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If used by support robot OR the has a Target Module, allow bench targetting
        if ($this_robot->robot_core === '' || $this_robot->robot_class == 'mecha'){ $this_ability->set_target('select_target'); }
        elseif ($this_robot->has_item('target-module')){ $this_ability->set_target('select_target'); }
        else { $this_ability->set_target('auto'); }

        // Return true on success
        return true;

        }
    );
?>