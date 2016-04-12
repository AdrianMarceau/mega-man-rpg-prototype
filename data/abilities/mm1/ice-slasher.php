<?
// ICE SLASHER
$ability = array(
  'ability_name' => 'Ice Slasher',
  'ability_token' => 'ice-slasher',
  'ability_game' => 'MM01',
  'ability_description' => 'The user fires a blast of razor-sharp ice at the target, inflicting damage and occasionally lowering its speed by {DAMAGE2}%!',
  'ability_type' => 'freeze',
  'ability_type2' => 'cutter',
  'ability_energy' => 8,
  'ability_damage' => 26,
  'ability_damage2' => 10,
  'ability_damage2_percent' => true,
  'ability_accuracy' => 96,
  'ability_function' => function($objects){

    // Extract all objects into the current scope
    extract($objects);

    // Target the opposing robot
    $this_ability->target_options_update(array(
      'frame' => 'shoot',
      'success' => array(0, 110, 0, 10, $this_robot->print_robot_name().' fires an '.$this_ability->print_ability_name().'!')
      ));
    $this_robot->trigger_target($target_robot, $this_ability);

    // Inflict damage on the opposing robot
    $this_ability->damage_options_update(array(
      'kind' => 'energy',
      'kickback' => array(10, 0, 0),
      'success' => array(4, -90, 0, 10, 'The '.$this_ability->print_ability_name().' cut into the target!'),
      'failure' => array(4, -100, 0, -10, 'The '.$this_ability->print_ability_name().' missed&hellip;')
      ));
    $this_ability->recovery_options_update(array(
      'kind' => 'energy',
      'frame' => 'taunt',
      'kickback' => array(0, 0, 0),
      'success' => array(4, -45, 0, 10, 'The '.$this_ability->print_ability_name().' was absorbed by the target!'),
      'failure' => array(4, -100, 0, -10, 'The '.$this_ability->print_ability_name().' had no effect&hellip;')
      ));
    $energy_damage_amount = $this_ability->ability_damage;
    $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

    // Randomly trigger a speed break if the ability was successful
    if ($target_robot->robot_status != 'disabled'
      && $target_robot->robot_speed > 0
      && $this_ability->ability_results['this_result'] != 'failure' && $this_ability->ability_results['this_amount'] > 0
      && $this_battle->critical_chance(50)){
      // Decrease the target robot's speed stat
      $this_ability->damage_options_update(array(
        'kind' => 'speed',
        'frame' => 'defend',
        'percent' => true,
        'kickback' => array(10, 0, 0),
        'success' => array(8, 0, -6, 10, $target_robot->print_robot_name().'&#39;s mobility was damaged!'),
        'failure' => array(8, 0, -6, -10, '')
        ));
      $this_ability->recovery_options_update(array(
        'kind' => 'speed',
        'frame' => 'taunt',
        'percent' => true,
        'kickback' => array(0, 0, 0),
        'success' => array(8, 0, -6, 10, $target_robot->print_robot_name().'&#39;s mobility improved!'),
        'failure' => array(8, 0, -6, -9999, '')
        ));
      $speed_damage_amount = ceil($target_robot->robot_speed * ($this_ability->ability_damage2 / 100));
      $trigger_options = array('apply_modifiers' => false);
      $target_robot->trigger_damage($this_robot, $this_ability, $speed_damage_amount, true, $trigger_options);
    }

    // Return true on success
    return true;


    }
  );
?>