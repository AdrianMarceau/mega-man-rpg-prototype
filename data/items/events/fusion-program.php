<?
// ITEM : FUSION PROGRAM
$item = array(
    'item_name' => 'Fusion Program',
    'item_token' => 'fusion-program',
    'item_game' => 'MMRPG',
    'item_group' => 'MMRPG/Items/Events',
    'item_class' => 'item',
    'item_subclass' => 'event',
    'item_type' => '',
    'item_type2' => 'energy',
    'item_description' => 'A new program developed by Drs. Light, Wily, and Cossack for use in the prototype, this item allows the user to swap out and rearrange their target battle fields to generate new field and fusion stars.',
    'item_energy' => 0,
    'item_speed' => 10,
    'item_accuracy' => 100,
    'item_function' => function($objects){

        // Return true on success
        return true;

    }
    );
?>