<?
// ENERGY BOOST
$ability = array(
    'ability_name' => 'Energy Boost',
    'ability_token' => 'energy-boost',
    'ability_game' => 'MMRPG',
    'ability_group' => 'MMRPG/Support/Energy',
    'ability_description' => 'The user manually repairs its own body to restore life energy by up to {RECOVERY}%!',
    'ability_energy' => 4,
    'ability_recovery' => 20,
    'ability_recovery_percent' => true,
    'ability_accuracy' => 100,
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target this robot's self
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 10, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($this_robot, $this_ability);

        // If the target of this ability is not the user
        if ($target_robot->robot_id != $this_robot->robot_id){

            // Increase this robot's energy stat
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'modifiers' => true,
                'frame' => 'taunt',
                'success' => array(0, -2, 0, -10, $target_robot->print_name().'&#39;s energy was restored!'),
                'failure' => array(9, -2, 0, -10, $target_robot->print_name().'&#39;s energy was not affected&hellip;')
                ));
            $energy_recovery_amount = ceil($target_robot->robot_base_energy * ($this_ability->ability_recovery / 100));
            $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
            $target_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount, true, $trigger_options);

        }
        // Otherwise if the user if targeting themselves
        else {

            // Increase the target robot's energy stat
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'frame' => 'taunt',
                'success' => array(0, -2, 0, -10, $this_robot->print_name().'&#39;s energy was restored!'),
                'failure' => array(9, -2, 0, -10, $this_robot->print_name().'&#39;s energy was not affected&hellip;')
                ));
            $energy_recovery_amount = ceil($this_robot->robot_base_energy * ($this_ability->ability_recovery / 100));
            $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
            $this_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount, true, $trigger_options);

        }

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