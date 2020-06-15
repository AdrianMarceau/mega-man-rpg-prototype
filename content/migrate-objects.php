<?

// Require the top file for paths and stuff
require('../top.php');

// Define the header type so it's easier to display stuff
header('Content-type: text/plain;');

// ONLY allow this file to run locally
if (defined('MMRPG_CONFIG_IS_LIVE') && MMRPG_CONFIG_IS_LIVE === true){
    die('This migration script can ONLY be run locally!!!');
}

// Start the output buffer now, we'll flush manually as we go
ob_implicit_flush(true);
ob_start();

// Define a quick function for immediately printing an echo statement
function ob_echo($echo, $silent = false){ if (!$silent){ echo($echo.PHP_EOL); } ob_flush(); }

// Define a function for cleaning a path of the root dir for printing
function clean_path($path){ return str_replace(MMRPG_CONFIG_ROOTDIR, '/', $path); }

// Define a quick function for copying rpg object sprites from one directory to another
function copy_sprites_to_new_dir($base_token, $count_string, $new_sprite_path, $exclude_sprites = array(), $delete_existing = true, $silent_mode = false){
    global $migration_kind, $migration_kind_singular, $migration_limit;
    $kind = $migration_kind_singular;
    $kind_plural = $migration_kind;
    ob_echo('----------', $silent_mode);
    ob_echo('Processing '.$kind.' sprites for "'.$base_token.'" '.$count_string, $silent_mode);
    ob_flush();
    if (!strstr($new_sprite_path, MMRPG_CONFIG_ROOTDIR)){ $new_sprite_path = MMRPG_CONFIG_ROOTDIR.ltrim($new_sprite_path, '/'); }
    $base_sprite_path = MMRPG_CONFIG_ROOTDIR.'images/'.$kind_plural.'/'.$base_token.'/';
    //ob_echo('-- $base_sprite_path = '.clean_path($base_sprite_path), $silent_mode);
    if (!file_exists($base_sprite_path)){
        ob_echo('- '.clean_path($base_sprite_path).' does not exist', $silent_mode);
        return false;
    }
    //ob_echo('-- $new_sprite_path = '.clean_path($new_sprite_path), $silent_mode);
    if ($delete_existing && file_exists($new_sprite_path)){ deleteDir($new_sprite_path); }
    if (!file_exists($new_sprite_path)){ mkdir($new_sprite_path); }
    ob_echo('- copy '.clean_path($base_sprite_path).'* to '.clean_path($new_sprite_path), $silent_mode);
    recurseCopy($base_sprite_path, $new_sprite_path, $exclude_sprites);
    $global_image_directories_copied = $kind.'_image_directories_copied';
    global $$global_image_directories_copied;
    ${$global_image_directories_copied}[] = basename($base_sprite_path);
    return true;
    };

// Define a function for parsing an object file's markup into actual data vs functions
function get_parsed_object_file_markup($object_file_path){
    // First make sure the file actually exists
    if (!file_exists($object_file_path)){
        ob_echo('- object file '.clean_path($object_file_path).' does not exist');
        return false;
    }
    // Now open the file and collect its contents into a line-by-line array
    $file_contents = trim(file_get_contents($object_file_path));
    $file_contents_array = explode(PHP_EOL, $file_contents);
    $file_contents_array_size = count($file_contents_array);
    // Pre-populate the markup arrays for the data vs functions lines, we'll clean later
    $data_markup_array = $file_contents_array;
    $functions_markup_array = $file_contents_array;
    // Define the object kinds pattern for use in strings below
    $okinds = '(?:ability|battle|field|item|player|robot|type)';
    // Remove all the non-function markup from the function markup arrow
    foreach ($functions_markup_array AS $line_key => $line_markup){
        if ($line_markup == '<?' || $line_markup == '<?php' || $line_markup == '?>'){ continue; }
        if (preg_match('/^\/\/ [A-Z]+/', $line_markup)){
            unset($data_markup_array[$line_key]);
            unset($functions_markup_array[$line_key]);
            continue;
        }
        if (preg_match('/^\$'.$okinds.' = array\(/', $line_markup)){
            $data_markup_array[$line_key] = '$data = array(';
            $functions_markup_array[$line_key] = '$functions = array(';
            continue;
        }
        if (preg_match('/^\s+(\/\/)?\''.$okinds.'_([_a-z0-9]+)\' =>\s/', $line_markup)){
            if (!preg_match('/^\s+(\/\/)?\''.$okinds.'_function(_[a-z0-9]+)?\' =>\s/', $line_markup)){
                unset($functions_markup_array[$line_key]);
                continue;
            } else {
                $data_markup_array[$line_key - 1] = rtrim($data_markup_array[$line_key - 1], ',');
                for ($i = $line_key; $i < ($file_contents_array_size - 2); $i++){ unset($data_markup_array[$i]); }
                $data_markup_array[$file_contents_array_size - 2] = ltrim($data_markup_array[$file_contents_array_size - 2], ' ');
                $functions_markup_array[$file_contents_array_size - 2] = ltrim($functions_markup_array[$file_contents_array_size - 2], ' ');
                break;
            }
        }
    }
    //ob_echo('- $file_contents_array = '.print_r($file_contents_array, true));
    //ob_echo('- $data_markup_array = '.print_r($data_markup_array, true));
    //ob_echo('- $functions_markup_array = '.print_r($functions_markup_array, true));
    return array(
        'data' => implode(PHP_EOL, $data_markup_array),
        'functions' => implode(PHP_EOL, $functions_markup_array)
        );
}

// Proceed based on the KIND of object we're migrating
$allowed_modes = array('full', 'update');
$allowed_migration_kinds = array('abilities', 'battles', 'fields', 'items', 'players', 'robots', 'types');
$migration_kind = !empty($_REQUEST['kind']) && in_array($_REQUEST['kind'], $allowed_migration_kinds) ? trim($_REQUEST['kind']) : false;
$migration_limit = !empty($_REQUEST['limit']) && is_numeric($_REQUEST['limit']) && $_REQUEST['limit'] > 0 ? (int)(trim($_REQUEST['limit'])) : 0;
$migration_filter = !empty($_REQUEST['filter']) && is_string($_REQUEST['filter']) ? explode(',', strtolower(trim($_REQUEST['filter']))) : array();
$migration_mode = !empty($_REQUEST['mode']) ? strtolower(trim($_REQUEST['mode'])) : $allowed_modes[0];
if (!empty($migration_kind)){ $migration_kind_singular = substr($migration_kind, -3, 3) === 'ies' ? str_replace('ies', 'y', $migration_kind) : rtrim($migration_kind, 's'); }
else { $migration_kind_singular = false; }
if (!empty($migration_kind) && file_exists('migrate-objects_'.$migration_kind.'.php')){
    require_once('migrate-objects_'.$migration_kind.'.php');
} else {
    ob_echo('Migration kind "'.$migration_kind.'" not supported or file not ready yet!');
}

// Empty the output buffer (or whatever is left)
ob_end_flush();


?>