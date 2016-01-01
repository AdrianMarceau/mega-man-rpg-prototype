<?
// ITEM : ATTACK BOOSTER
$ability = array(
  'ability_name' => 'Attack Booster',
  'ability_token' => 'item-attack-booster',
  'ability_game' => 'MM00',
  'ability_group' => 'MM00/Items/StatBoosters',
  'ability_class' => 'item',
  'ability_subclass' => 'holdable',
  'ability_type' => 'attack',
  'ability_description' => 'A mysterious disc containing some kind of attack booster program.  When held by a robot master, this item increases the user\'s attack stat by {RECOVERY2}% at end of each turn in battle.',
  'ability_energy' => 0,
  'ability_recovery2' => 20,
  'ability_recovery2_percent' => true,
  'ability_speed' => 10,
  'ability_accuracy' => 100,
  'ability_function' => function($objects){

    // Return true on success
    return true;

  }
  );
?>