<?
// ITEM : COSSACK PROGRAM
$item = array(
    'item_name' => 'Cossack Program',
    'item_token' => 'cossack-program',
    'item_game' => 'MMRPG',
    'item_group' => 'MMRPG/Items/EventPrograms',
    'item_class' => 'item',
    'item_subclass' => 'event',
    'item_type' => '',
    'item_type2' => 'cossack',
    'item_description' => 'An intriguing program developed by Dr. Cossack for use in the prototype, this item allows the doctors to scan for and locate deposites of alien energy in the prototype.  This program also unlocks the "Star Fields" bonus chapter in the post-game.',
    'item_energy' => 0,
    'item_speed' => 10,
    'item_accuracy' => 100,
    'item_value' => 75000,
    'item_function' => function($objects){

        // Return true on success
        return true;

    }
    );
?>