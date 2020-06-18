<?

ob_echo('');
ob_echo('=============================');
ob_echo('|  START FIELD MIGRATION  |');
ob_echo('=============================');
ob_echo('');

// Predefine any deprecated fields or field sprites so we can ignore them
$deprecated_fields = array(
    );

// Collect an index of all valid fields from the database
$field_fields = rpg_field::get_index_fields(true);
$field_index = $db->get_array_list("SELECT {$field_fields} FROM mmrpg_index_fields ORDER BY field_token ASC", 'field_token');

// If there's a filter present, remove all tokens not in the filter
if (!empty($migration_filter)){
    $old_field_index = $field_index;
    $field_index = array();
    foreach ($migration_filter AS $field_token){
        if (isset($old_field_index[$field_token])){
            $field_index[$field_token] = $old_field_index[$field_token];
        }
    }
    unset($old_field_index);
}

// Pre-collect an index of field alts so we don't have to scan each later
$field_sprites_list = scandir(MMRPG_CONFIG_ROOTDIR.'images/fields/');
$field_sprites_list = array_filter($field_sprites_list, function($s){ if ($s !== '.' && $s !== '..' && substr($s, 0, 1) !== '.'){ return true; } else { return false; } });
//echo('$field_sprites_list = '.print_r($field_sprites_list, true));

// Manually remove deprecated fields from the sprite and index lists
foreach ($deprecated_fields AS $token){
    $rm_key = array_search($token, $field_sprites_list);
    if ($rm_key !== false){ unset($field_sprites_list[$rm_key]); }
    if (isset($field_index[$token])){ unset($field_index[$token]); }
}

// Loop through sprites and pre-collect any alts for later looping
$field_sprites_alts_list = array();
foreach ($field_sprites_list AS $key => $token){
    if (strstr($token, '_')){
        list($token1, $token2) = explode('_', $token);
        if (!isset($field_sprites_alts_list[$token1])){ $field_sprites_alts_list[$token1] = array(); }
        $field_sprites_alts_list[$token1][] = $token2;
    }
}
//echo('$field_sprites_alts_list = '.print_r($field_sprites_alts_list, true));
//exit();

// Pre-define the base field image dir and the new field content dir
define('MMRPG_FIELDS_IMAGES_DIR', MMRPG_CONFIG_ROOTDIR.'images/fields/');
define('MMRPG_FIELDS_CONTENT_DIR', MMRPG_CONFIG_ROOTDIR.'content/fields/');

// Predefine a whitelist of sprites we can copy over for the fields
$field_sprite_whitelist = array(
    'battle-field_avatar.png',
    'battle-field_background_base.gif', 'battle-field_background_base.png',
    'battle-field_foreground_base.png',
    'battle-field_preview.png'
    );

// Count the number of fields that we'll be looping through
$field_index_size = count($field_index);
$field_sprite_directories_total = count($field_sprites_list);
$count_pad_length = strlen($field_index_size);

// Print out the stats before we start
ob_echo('Total Fields in Database: '.$field_index_size);
ob_echo('Total Sprites in ImageDir: '.$field_sprite_directories_total);
ob_echo('');

sleep(1);

$field_data_files_copied = array();
$field_image_directories_copied = array();

// MIGRATE ACTUAL FIELDS
$field_key = -1; $field_num = 0;
foreach ($field_index AS $field_token => $field_data){
    $field_key++; $field_num++;
    $count_string = '('.$field_num.' of '.$field_index_size.')';

    ob_echo('----------');
    ob_echo('Processing field data and sprites "'.$field_token.'" '.$count_string);
    ob_flush();

    $function_path = rtrim(dirname($field_data['field_functions']), '/').'/';
    //ob_echo('-- $function_path = '.$function_path);

    $sprite_path = MMRPG_CONFIG_ROOTDIR.'images/fields/'.$field_token.'/';
    //ob_echo('-- $sprite_path = '.clean_path($sprite_path));
    $data_path = MMRPG_CONFIG_ROOTDIR.'data/'.$function_path.$field_token.'.php';
    //ob_echo('-- $data_path = '.clean_path($data_path));

    $content_path = MMRPG_FIELDS_CONTENT_DIR.($field_token === 'field' ? '.field' : $field_token).'/';
    //ob_echo('-- $content_path = '.clean_path($content_path));
    if (file_exists($content_path)){ deleteDir($content_path); }
    mkdir($content_path);

    // Ensure the base sprite exists first and copy if so
    if (file_exists($sprite_path)){
        $content_images_path = $content_path.'sprites/';
        if (file_exists($content_images_path)){ deleteDir($content_images_path); }
        mkdir($content_images_path);
        ob_echo('- copy '.clean_path($sprite_path).'* to '.clean_path($content_images_path));
        recurseCopyWithWhitelist($sprite_path, $content_images_path, $field_sprite_whitelist);
        $field_image_directories_copied[] = basename($sprite_path);
    }

    // Ensure the data file exists before attempting to extract functions from it
    if (file_exists($data_path)){
        $functions_file_markup = get_empty_functions_file_markup('field');
        if (!empty($functions_file_markup)){
            $content_data_path = $content_path.'functions.php';
            ob_echo('- extract '.clean_path($data_path).' functions into '.clean_path($content_data_path));
            $h = fopen($content_data_path, 'w');
            fwrite($h, $functions_file_markup);
            fclose($h);
        }
        $field_data_files_copied[] = basename($data_path); // not actually copied but here for tracking
    }

    // And then write the rest of the non-function data into a json file
    $content_json_path = $content_path.'data.json';
    $content_json_data = $field_data;
    unset($content_json_data['field_id']);
    unset($content_json_data['field_functions']);
    ob_echo('- export all other data to '.clean_path($content_json_path));
    $h = fopen($content_json_path, 'w');
    fwrite($h, json_encode($content_json_data, JSON_PRETTY_PRINT));
    fclose($h);

    if ($migration_limit && $field_num >= $migration_limit){ break; }

}



// MIGRATE OTHER FIELDS

// Only migrate other fields if a filter isn't present
if (empty($migration_filter)){

    // Define the objects base path
    $object_base_path = MMRPG_CONFIG_ROOTDIR.'images/objects/';

}

ob_echo('----------');

$field_image_directories_copied = array_unique($field_image_directories_copied);

ob_echo('');
ob_echo('Field Data Files Copied: '.count($field_data_files_copied).' / '.$field_index_size);
ob_echo('Field Image Directories Copied: '.count($field_image_directories_copied).' / '.$field_sprite_directories_total);
if (!($migration_limit > 0) && empty($migration_filter)){
    ob_echo('');
    ob_echo('Field Images Not Copied: '.print_r(array_diff($field_sprites_list, $field_image_directories_copied), true));
}
//ob_echo('$field_sprites_list: '.print_r($field_sprites_list, true));
//b_echo('$field_image_directories_copied: '.print_r($field_image_directories_copied, true));

sleep(1);

ob_echo('');
ob_echo('=============================');
ob_echo('|   END FIELD MIGRATION   |');
ob_echo('=============================');



?>