<?php
/*
 * INDEX PAGE : MECHAS/ROBOTS/BOSSES
 */

// Define object class tokens for MECHAS
if ($this_current_sub == 'mechas'){
    $object_class_token = 'mecha';
    $object_single_token = 'mecha';
    $object_multi_token = 'mechas';
}
// Define object class tokens for ROBOTS
elseif ($this_current_sub == 'robots'){
    $object_class_token = 'master';
    $object_single_token = 'robot';
    $object_multi_token = 'robots';
}
// Define object class tokens for BOSSES
elseif ($this_current_sub == 'bosses'){
    $object_class_token = 'boss';
    $object_single_token = 'boss';
    $object_multi_token = 'bosses';
}

// Define the object names based on tokens
$object_single_name = ucfirst($object_single_token);
$object_multi_name = ucfirst($object_multi_token);


// Collect the robot ID from the request header
$robot_id = isset($_GET['num']) && is_numeric($_GET['num']) ? (int)($_GET['num']) : false;

// Collect robot info based on the ID if available
$robot_fields = rpg_robot::get_index_fields(true);
if (!empty($robot_id)){ $robot_info = $db->get_array("SELECT {$robot_fields} FROM mmrpg_index_robots WHERE robot_id = {$robot_id} AND robot_class = '{$object_class_token}';"); }
elseif ($robot_id === 0){ $robot_info = $db->get_array("SELECT {$robot_fields} FROM mmrpg_index_robots WHERE robot_token = 'robot';"); }
else { $robot_info = array(); }

// Parse the robot info if it was collected
if (!empty($robot_info)){ $robot_info = rpg_robot::parse_index_info($robot_info); }

//echo('$robot_info['.$robot_id.'] = <pre>'.print_r($robot_info, true).'</pre><hr />');
//exit();

// Collect the type index for display and looping
$type_index = rpg_type::get_index(true);

// Generate form select options for the type index
$type_tokens = array_keys($type_index);
$type_index_options = '';
$type_index_options .= '<option value="">Neutral</option>'.PHP_EOL; // Manually add 'none' up top
foreach ($type_tokens AS $type_token){
    if ($type_token == 'none'){ continue; } // We already added 'none' above
    $type_info = $type_index[$type_token];
    $type_index_options .= '<option value="'.$type_token.'">'.$type_info['type_name'].'</option>'.PHP_EOL;
}

// Generate form select options for the elemental sub-index
$elemental_index_options = '';
$elemental_index_options .= '<option value="">Neutral</option>'.PHP_EOL; // Manually add 'none' up top
foreach ($type_tokens AS $type_token){
    $type_info = $type_index[$type_token];
    if ($type_info['type_class'] == 'special'){ continue; }
    $elemental_index_options .= '<option value="'.$type_token.'">'.$type_info['type_name'].'</option>'.PHP_EOL;
}

// Generate form select options for the stat sub-index
$stat_tokens = array('energy', 'weapons', 'attack', 'defense', 'speed');
$stat_index_options = '';
$stat_index_options .= '<option value="">Neutral</option>'.PHP_EOL; // Manually add 'none' up top
foreach ($stat_tokens AS $type_token){
    $type_info = $type_index[$type_token];
    $stat_index_options .= '<option value="'.$type_token.'">'.$type_info['type_name'].'</option>'.PHP_EOL;
}


// Require actions file for form processing
require_once(MMRPG_CONFIG_ROOTDIR.'pages/page.admin_robots_actions.php');


// -- ROBOT EDITOR -- //

// If a robot ID was provided, we should show the editor
if ($robot_id !== false && !empty($robot_info)){

    // Define the SEO variables for this page
    $this_seo_title = $robot_info['robot_name'].' | '.$object_single_name.' Index | Admin Panel | '.$this_seo_title;
    $this_seo_description = 'Admin '.$object_single_token.' editor for the Mega Man RPG Prototype.';
    $this_seo_robots = '';

    // Define the MARKUP variables for this page
    $this_markup_header = '';

    // Start generating the page markup
    ob_start();
    ?>

    <h2 class="subheader field_type_<?= MMRPG_SETTINGS_CURRENT_FIELDTYPE ?>">
        <a class="inline_link" href="admin/">Admin Panel</a> <span class="crumb">&raquo;</span>
        <a class="inline_link" href="admin/<?= $this_current_sub ?>/"><?= $object_single_name ?> Index</a> <span class="crumb">&raquo;</span>
        <a class="inline_link" href="admin/<?= $this_current_sub ?>/<?= $robot_id ?>/"><?= 'ID '.$robot_info['robot_id'] ?> : <?= $robot_info['robot_name'] ?></a>
    </h2>
    <div class="subbody">
        <p class="text">Use the <strong><?= $object_single_name ?> Editor</strong> to update the details and stats of <?= $object_multi_token == 'robots' ? 'unlockable '.$object_multi_token : 'fightable '.$object_multi_token ?> in the Mega Man RPG Prototype.  Please be careful and don't forget to save your work.</p>
    </div>

    <form class="editor robots" action="admin/<?= $this_current_sub ?>/<?= $robot_id ?>/" method="post" enctype="multipart/form-data">
        <div class="subbody">

            <div class="section inputs">
                <div class="field field_robot_id">
                    <label class="label"><?= $object_single_name ?> ID</label>
                    <input class="text" type="text" name="robot_id" value="<?= $robot_info['robot_id'] ?>" disabled="disabled" />
                    <input class="hidden" type="hidden" name="robot_id" value="<?= $robot_info['robot_id'] ?>" />
                </div>
                <div class="field field_robot_token">
                    <label class="label"><?= $object_single_name ?> Token</label>
                    <input class="text" type="text" name="robot_token" value="<?= $robot_info['robot_token'] ?>" maxlength="64" />
                </div>
                <div class="field field_robot_name">
                    <label class="label"><?= $object_single_name ?> Name</label>
                    <input class="text" type="text" name="robot_name" value="<?= $robot_info['robot_name'] ?>" maxlength="64" />
                </div>
                <div class="field field_robot_type">
                    <label class="label"><?= $object_single_name ?> Type</label>
                    <select class="select" name="robot_type">
                        <?= str_replace('value="'.$robot_info['robot_type'].'"', 'value="'.$robot_info['robot_type'].'" selected="selected"', $elemental_index_options) ?>
                    </select>
                </div>
            </div>

            <div class="section actions">
                <div class="buttons">
                    <input type="submit" class="save" name="save" value="Save Changes" />
                    <input type="button" class="delete" name="delete" value="Delete <?= $object_single_name ?>" />
                </div>
            </div>

        </div>
    </form>

    <div class="subbody">
        <pre>$robot_info = <?= print_r($robot_info, true) ?></pre>
    </div>


    <?php
    // Collect the buffer and define the page markup
    $this_markup_body = trim(ob_get_clean());

}

// -- ROBOT INDEX -- //

// Else if no robot ID was provided, we should show the index
else {

    // Define the SEO variables for this page
    $this_seo_title = $object_single_name.' Index | Admin Panel | '.$this_seo_title;
    $this_seo_description = 'Admin '.$object_single_token.' editor index for the Mega Man RPG Prototype.';
    $this_seo_robots = '';

    // Define the MARKUP variables for this page
    $this_markup_header = '';

    // Define the robot header columns for looping through
    // token => array(name, class, width, directions)
    $table_columns['id'] = array('ID', 'id', '75', 'asc/desc');
    $table_columns['name'] = array($object_single_name.' Name', 'name', '', 'asc/desc');
    $table_columns['group'] = array('Group', 'group', '', 'asc/desc');
    $table_columns['core'] = array('Core', 'cores', '140', 'asc/desc');
    $table_columns['hidden'] = array('Hidden', 'flags hidden', '90', 'desc/asc');
    $table_columns['complete'] = array('Complete', 'flags complete', '90', 'desc/asc');
    $table_columns['published'] = array('Published', 'flags published', '100', 'desc/asc');
    $table_columns['actions'] = array('', 'actions', '120', '');

    // Collect a list of all users in the database
    $robot_fields = rpg_robot::get_index_fields(true);
    $robot_query = "SELECT {$robot_fields} FROM mmrpg_index_robots WHERE robot_id <> 0 AND robot_token <> 'robot' AND robot_class = '{$object_class_token}' ORDER BY {$query_sort};";
    $robot_index = $db->get_array_list($robot_query, 'robot_id');
    $robot_count = !empty($robot_index) ? count($robot_index) : 0;

    // Collect a list of completed robot sprite tokens
    $random_sprite = $db->get_value("SELECT robot_image FROM mmrpg_index_robots WHERE robot_image <> 'robot' AND robot_class = '{$object_class_token}' AND robot_image_size = 40 AND robot_flag_complete = 1 ORDER BY RAND() LIMIT 1;", 'robot_image');

    // Start generating the page markup
    ob_start();
    ?>

    <h2 class="subheader field_type_<?= MMRPG_SETTINGS_CURRENT_FIELDTYPE ?>">
        <a class="inline_link" href="admin/">Admin Panel</a> <span class="crumb">&raquo;</span>
        <a class="inline_link" href="admin/<?= $this_current_sub ?>/"><?= $object_single_name ?> Index</a>
        <span class="count">( <?= $robot_count != 1 ? $robot_count.' Robots' : '1 Robot' ?> )</span>
    </h2>

    <div class="section full">
        <div class="subbody">
            <div class="float float_right"><div class="sprite sprite_80x80 sprite_80x80_command" style="background-image: url(images/robots/<?= $random_sprite ?>/sprite_left_80x80.png?<?= MMRPG_CONFIG_CACHE_DATE ?>);"></div></div>
            <p class="text">Use the robot index below to search and filter through all the <?= $object_multi_token == 'robots' ? 'unlockable '.$object_multi_token : 'fightable '.$object_multi_token ?> in the game and either view or edit using the provided links.</p>
        </div>
    </div>

    <div class="section full">
        <div class="subbody">
            <table data-table="robots" class="full">
                <colgroup>
                    <?
                    // Loop through and display column widths
                    foreach ($table_columns AS $token => $info){
                        list($name, $class, $width, $directions) = $info;
                        echo '<col width="'.$width.'" />'.PHP_EOL;
                    }
                    ?>
                </colgroup>
                <thead>
                    <tr class="head">
                        <?
                        // Loop through and display column headers
                        foreach ($table_columns AS $token => $info){
                            list($name, $class, $width, $directions) = $info;
                            if (!empty($name)){
                                $active = $sort_column == $token ? true : false;
                                $directions = explode('/', $directions);
                                $class .= $active ? ' active' : '';
                                $link = 'admin/'.$object_multi_token.'/sort='.$token.'-';
                                $link .= ($active && $sort_direction == $directions[0]) ? $directions[1] : $directions[0];
                                echo '<th class="'.$class.'">';
                                    echo '<a class="link_inline" href="'.$link.'">'.$name.'</a>';
                                    if ($active && $sort_direction == 'asc'){ echo ' <sup>&#8595;</sup>'; }
                                    elseif ($active && $sort_direction == 'desc'){ echo ' <sup>&#8593;</sup>'; }
                                echo '</th>'.PHP_EOL;
                            } else {
                                echo '<th class="'.$class.'">&nbsp;</th>'.PHP_EOL;
                            }
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?
                    // Loop through collected robots and list their details
                    if (!empty($robot_index)){
                        foreach ($robot_index AS $robot_id => $robot_info){
                            // Parse the robot info before displaying it
                            $robot_info = rpg_robot::parse_index_info($robot_info);
                            // Collect the display fields from the array
                            $robot_token = $robot_info['robot_token'];
                            $robot_name = $robot_info['robot_name'];
                            $robot_group = '<span class="token">'.$robot_info['robot_group'].'</span>';
                            $robot_type1 = !empty($robot_info['robot_core']) && !empty($type_index[$robot_info['robot_core']]) ? $type_index[$robot_info['robot_core']] : $type_index['none'];
                            $robot_type2 = !empty($robot_info['robot_core2']) && !empty($type_index[$robot_info['robot_core2']]) ? $type_index[$robot_info['robot_core2']] : false;
                            if (!empty($robot_type2)){ $type_string = '<span class="type '.$robot_type1['type_token'].'_'.$robot_type2['type_token'].'">'.$robot_type1['type_name'].' / '.$robot_type2['type_name'].'</span>'; }
                            else { $type_string = '<span class="type '.$robot_type1['type_token'].'">'.$robot_type1['type_name'].'</span>'; }
                            $edit_link = 'admin/'.$object_multi_token.'/'.$robot_id.'/';
                            $view_link = 'database/'.$object_multi_token.'/'.$robot_token.'/';
                            $complete = $robot_info['robot_flag_complete'] ? true : false;
                            $published = $robot_info['robot_flag_published'] ? true : false;
                            $hidden = $robot_info['robot_flag_hidden'] ? true : false;
                            // Print out the robot info as a table row
                            ?>
                            <tr class="object<?= !$published ? ' unpublished' : '' ?><?= !$complete ? ' incomplete' : '' ?>">
                                <td class="id"><?= $robot_id ?></td>
                                <td class="name"><a class="link_inline" href="<?= $edit_link ?>" title="Edit <?= $robot_name ?>" target="_editRobot<?= $robot_id ?>"><?= $robot_name ?></a></td>
                                <td class="group"><?= $robot_group ?></td>
                                <td class="cores"><?= $type_string ?></td>
                                <td class="flags hidden"><?= $hidden ? '<span class="true">&#9745;</span>' : '<span class="false">&#9744;</span>' ?></td>
                                <td class="flags complete"><?= $complete ? '<span class="true">&#x2713;</span>' : '<span class="false">&#x2717;</span>' ?></td>
                                <td class="flags published"><?= $published ? '<span class="type nature">Yes</span>' : '<span class="type flame">No</span>' ?></td>
                                <td class="actions">
                                    <a class="link_inline edit" href="<?= $edit_link ?>" target="_editRobot<?= $robot_id ?>">Edit</a>
                                    <? if ($published): ?>
                                        <a class="link_inline view" href="<?= $view_link ?>" target="_viewRobot<?= $robot_token ?>">View</a>
                                    <? endif; ?>
                                </td>
                            </tr>
                            <?
                        }
                    }
                    // Otherwise if robot index is empty show an empty table
                    else {
                        // Print an empty table row
                        ?>
                            <tr class="object incomplete">
                                <td class="id">-</td>
                                <td class="name">-</td>
                                <td class="core">-</td>
                                <td class="flags" colspan="3">-</td>
                                <td class="actions">-</td>
                            </tr>
                        <?
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="subbody">
        <p class="text right"><?= $robot_count != 1 ? $robot_count.' Robots' : '1 Robot' ?> Total</p>
    </div>


    <?php
    // Collect the buffer and define the page markup
    $this_markup_body = trim(ob_get_clean());

}

?>