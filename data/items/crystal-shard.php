<?
// ITEM : CRYSTAL SHARD
$ability = array(
  'ability_name' => 'Crystal Shard',
  'ability_token' => 'item-shard-crystal',
  'ability_game' => 'MMRPG',
  'ability_group' => 'MMRPG/Items/Crystal',
  'ability_class' => 'item',
  'ability_subclass' => 'collectible',
  'ability_type' => 'crystal',
  'ability_description' => 'A mysterious elemental shard that radiates with the Crystal type energy of a defeated support mecha.  Collect four of these items to generate a new core that can be held by a robot master to equip Crystal type abilities or traded in at the shop for a variable amount of Zenny.',
  'ability_energy' => 0,
  'ability_speed' => 10,
  'ability_accuracy' => 100,
  'ability_target' => 'auto',
  'ability_function' => function($objects){
    return rpg_ability::item_function_shard($objects);
  }
  );
?>