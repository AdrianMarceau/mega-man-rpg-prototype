<?
// DEFENSE BOOST
$ability = array(
    'ability_name' => 'Defense Boost',
    'ability_token' => 'defense-boost',
    'ability_game' => 'MMRPG',
    'ability_group' => 'MMRPG/Support/Defense',
    'ability_description' => 'The user optimizes internal systems to improve shields and sharpy raise its defense stat! When used by a support robot, this ability can optionally boost an ally instead of the user!',
    'ability_energy' => 4,
    'ability_accuracy' => 100,
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target this robot's self
        $this_ability->target_options_update(array('frame' => 'summon', 'success' => array(0, 0, 10, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')));
        $this_robot->trigger_target($this_robot, $this_ability);

        // Create a reference to the target robot, whichever one it is
        if ($this_robot->player_id == $target_robot->player_id){ $temp_target_robot = $target_robot; }
        else { $temp_target_robot = $this_robot; }

        // Call the global stat boost function with customized options
        rpg_ability::ability_function_stat_boost($temp_target_robot, 'defense', 2, $this_ability);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If used by support robot OR the has a Target Module, allow bench targetting
        if ($this_robot->robot_core === '' || $this_robot->robot_class == 'mecha'){ $this_ability->set_target('select_this'); }
        elseif ($this_robot->has_item('target-module')){ $this_ability->set_target('select_this'); }
        else { $this_ability->set_target('auto'); }

        // Return true on success
        return true;

        }
    );
?>