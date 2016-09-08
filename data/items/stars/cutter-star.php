<?
// ITEM : CUTTER STAR
$item = array(
    'item_name' => 'Cutter Star',
    'item_token' => 'cutter-star',
    'item_game' => 'MMRPG',
    'item_group' => 'MMRPG/Items/Cutter',
    'item_class' => 'item',
    'item_subclass' => 'holdable',
    'item_type' => 'cutter',
    'item_description' => 'A mysterious elemental star that radiates with the Cutter type energy of a distant planet. Collecting a single one of these items permanently grants +10% toward all Cutter type damage and recovery.  A certain character is said to be researching these items and would likely trade a respectable amount of Zenny to study one up close.',
    'item_energy' => 0,
    'item_speed' => 10,
    'item_accuracy' => 100,
    'item_target' => 'auto',
    'item_function' => function($objects){
        return rpg_item::item_function_core($objects);
    }
    );
?>