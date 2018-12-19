<?
// ITEM : DEFENSE CAPSULE
$item = array(
    'item_name' => 'Defense Capsule',
    'item_token' => 'defense-capsule',
    'item_game' => 'MMRPG',
    'item_group' => 'MM00/Items/Defense',
    'item_class' => 'item',
    'item_subclass' => 'consumable',
    'item_type' => 'defense',
    'item_description' => 'A large shield capsule that drastically raises the defense stat of one robot on the user\'s side of the field.',
    'item_energy' => 0,
    'item_speed' => 10,
    'item_recovery' => 3,
    'item_accuracy' => 100,
    'item_price' => 2700,
    'item_target' => 'select_this',
    'item_function' => function($objects){

        // Call the global stat booster item function
        return rpg_item::item_function_stat_booster($objects);

        }
    );
?>