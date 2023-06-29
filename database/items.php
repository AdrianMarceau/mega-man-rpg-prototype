<?

// ITEM DATABASE

// Define the index of hidden items to not appear in the database
$hidden_database_items = array();
/*
$hidden_database_items = array_merge($hidden_database_items, array(
    'heart', //'star',
    'empty-core', 'empty-shard', 'empty-star',
    'energy-core', 'energy-shard', 'energy-star',
    'attack-core', 'attack-shard', 'attack-star',
    'defense-core', 'defense-shard', 'defense-star',
    'speed-core', 'speed-shard', 'speed-star',
    'none-star', 'copy-star'
    ));
*/
$hidden_database_items_count = !empty($hidden_database_items) ? count($hidden_database_items) : 0;

// Define the hidden item query condition
$temp_condition = '';
$temp_condition .= "AND items.item_class <> 'system' ";
if (!empty($hidden_database_items)){
    $temp_tokens = array();
    foreach ($hidden_database_items AS $token){ $temp_tokens[] = "'".$token."'"; }
    $temp_condition .= 'AND items.item_token NOT IN ('.implode(',', $temp_tokens).') ';
}
// If additional database filters were provided
$temp_condition_unfiltered = $temp_condition;
if (isset($mmrpg_database_items_filter)){
    if (!preg_match('/^\s?(AND|OR)\s+/i', $mmrpg_database_items_filter)){ $temp_condition .= 'AND ';  }
    $temp_condition .= $mmrpg_database_items_filter;
}

// Collect the database items
$item_fields = rpg_item::get_index_fields(true, 'items');
$mmrpg_database_items = $db->get_array_list("SELECT
    {$item_fields},
    groups.group_token AS item_group,
    tokens.token_order AS item_order
    FROM mmrpg_index_items AS items
    LEFT JOIN mmrpg_index_items_groups_tokens AS tokens ON tokens.item_token = items.item_token
    LEFT JOIN mmrpg_index_items_groups AS groups ON groups.group_token = tokens.group_token AND groups.group_class = items.item_class
    WHERE item_id <> 0
    AND items.item_flag_published = 1 AND (items.item_flag_hidden = 0 OR items.item_token = '{$this_current_token}') {$temp_condition}
    ORDER BY
    groups.group_order ASC,
    tokens.token_order ASC
    ;", 'item_token');

// Count the database items in total (without filters)
$mmrpg_database_items_count = $db->get_value("SELECT
    COUNT(items.item_id) AS item_count
    FROM mmrpg_index_items AS items
    WHERE items.item_flag_published = 1 AND items.item_flag_hidden = 0 {$temp_condition_unfiltered}
    ;", 'item_count');

// Select an ordered list of all items and then assign row numbers to them
$mmrpg_database_items_numbers = $db->get_array_list("SELECT
    items.item_token,
    0 AS item_key
    FROM mmrpg_index_items AS items
    LEFT JOIN mmrpg_index_items_groups_tokens AS tokens ON tokens.item_token = items.item_token
    LEFT JOIN mmrpg_index_items_groups AS groups ON groups.group_token = tokens.group_token AND groups.group_class = items.item_class
    WHERE item_id <> 0
    AND items.item_flag_published = 1 {$temp_condition_unfiltered}
    ORDER BY
    groups.group_order ASC,
    tokens.token_order ASC
    ;", 'item_token');
$item_key = 1;
foreach ($mmrpg_database_items_numbers AS $token => $info){
    $mmrpg_database_items_numbers[$token]['item_key'] = $item_key++;
}

// Remove unallowed items from the database, and increment counters
if (!empty($mmrpg_database_items)){
    foreach ($mmrpg_database_items AS $temp_token => $temp_info){

        // Define first item token if not set
        if (!isset($first_item_token)){ $first_item_token = $temp_token; }

        // Send this data through the item index parser
        $temp_info = rpg_item::parse_index_info($temp_info);

        // Collect this item's key in the index
        $temp_info['item_key'] = $mmrpg_database_items_numbers[$temp_token]['item_key'];

        // Ensure this item's image exists, else default to the placeholder
        $temp_image_token = isset($temp_info['item_image']) ? $temp_info['item_image'] : $temp_token;
        if ($temp_info['item_flag_complete']){ $mmrpg_database_items[$temp_token]['item_image'] = $temp_image_token; }
        else { $mmrpg_database_items[$temp_token]['item_image'] = 'item'; }

        // Update the main database array with the changes
        $mmrpg_database_items[$temp_token] = $temp_info;

    }
}

// Loop through the database and generate the links for these items
$key_counter = 0;
$last_game_code = '';
$mmrpg_database_items_links = '';
$mmrpg_database_items_links_index = array();
$mmrpg_database_items_links_counter = 0;
$mmrpg_database_items_count_complete = 0;

// Loop through the results and generate the links for these items
if (!empty($mmrpg_database_items)){
    foreach ($mmrpg_database_items AS $item_key => $item_info){

        //if (!preg_match('/^star-/i', $item_info['item_token'])){ continue; }

        // Do not show incomplete items in the link list
        $show_in_link_list = true;
        if (!$item_info['item_flag_complete'] && $item_info['item_token'] !== $this_current_token){ $show_in_link_list = false; }

        // If a type filter has been applied to the item page
        $temp_item_types = array();
        if (!empty($item_info['item_type'])){ $temp_item_types[] = $item_info['item_type']; }
        if (!empty($item_info['item_type2'])){ $temp_item_types[] = $item_info['item_type2']; }
        if (preg_match('/^super-(pellet|capsule)$/i', $item_info['item_token'])){ $temp_item_types[] = 'multi'; }
        if (empty($temp_item_types)){ $temp_item_types[] = 'none'; }
        if (isset($this_current_filter) && !in_array($this_current_filter, $temp_item_types)){ $key_counter++; continue; }

        // If this is the first in a new group
        $game_code = !empty($item_info['item_group']) ? $item_info['item_group'] : (!empty($item_info['item_game']) ? $item_info['item_game'] : 'MMRPG');
        if ($show_in_link_list && $game_code != $last_game_code){
            if ($key_counter != 0){ $mmrpg_database_items_links .= '</div>'; }
            $mmrpg_database_items_links .= '<div class="float link group" data-game="'.$game_code.'">';
            $last_game_code = $game_code;
        }

        // Collect the item sprite dimensions
        $item_token = $item_info['item_token'];
        $item_flag_complete = !empty($item_info['item_flag_complete']) ? true : false;
        $item_image_size = !empty($item_info['item_image_size']) ? $item_info['item_image_size'] : 40;
        $item_image_size_text = $item_image_size.'x'.$item_image_size;
        $item_image_token = !empty($item_info['item_image']) ? $item_info['item_image'] : $item_token;
        $item_image_incomplete = $item_image_token == 'item' ? true : false;
        $item_is_active = !empty($this_current_token) && $this_current_token == $item_info['item_token'] ? true : false;
        $item_title_text = $item_info['item_name'];
        $item_image_path = 'images/items/'.$item_image_token.'/icon_right_'.$item_image_size_text.'.png?'.MMRPG_CONFIG_CACHE_DATE;
        $item_type_class = !empty($item_info['item_type']) ? $item_info['item_type'] : 'none';
        if ($item_type_class != 'none' && !empty($item_info['item_type2'])){ $item_type_class .= '_'.$item_info['item_type2']; }
        elseif ($item_type_class == 'none' && !empty($item_info['item_type2'])){ $item_type_class = $item_info['item_type2'];  }

        // Start the output buffer and collect the generated markup
        ob_start();
        ?>
        <div title="<?= $item_title_text ?>" data-token="<?= $item_info['item_token'] ?>" class="float left link type <?= ($item_image_incomplete ? 'inactive ' : '').($item_type_class) ?>">
            <a class="sprite item link mugshot size<?= $item_image_size.($item_key == $first_item_token ? ' current' : '') ?>" href="<?= 'database/items/'.preg_replace('/^/i', '', $item_info['item_token']) ?>/" rel="<?= $item_image_incomplete ? 'nofollow' : 'follow' ?>">
                <?php if($item_image_token != 'item'): ?>
                    <img src="<?= $item_image_path ?>" width="<?= $item_image_size ?>" height="<?= $item_image_size ?>" alt="<?= $item_title_text ?>" />
                <?php else: ?>
                    <span><?= $item_info['item_name'] ?></span>
                <?php endif; ?>
            </a>
        </div>
        <?php
        if ($item_flag_complete){ $mmrpg_database_items_count_complete++; }
        $temp_markup = ob_get_clean();
        $mmrpg_database_items_links_index[$item_key] = $temp_markup;
        if ($show_in_link_list){ $mmrpg_database_items_links .= $temp_markup; }
        $mmrpg_database_items_links_counter++;
        $key_counter++;

    }
}

// End the groups, however many there were
$mmrpg_database_items_links .= '</div>';

?>