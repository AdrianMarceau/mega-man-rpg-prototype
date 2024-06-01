<?php

// Define the for the API cache file, then include API functionality
$api_request_path = 'robots/index';
require(MMRPG_CONFIG_API_ROOTDIR.'api-common.php');

// Include the database file for robots and then parse necessary data
define('FORCE_INCLUDE_TEMPLATE_ROBOT', $api_include_templates);
define('FORCE_INCLUDE_HIDDEN_ROBOTS', $api_include_hidden);
define('FORCE_INCLUDE_INCOMPLETE_ROBOTS', $api_include_incomplete);
require_once(MMRPG_CONFIG_ROOTDIR.'database/types.php');
require_once(MMRPG_CONFIG_ROOTDIR.'database/robots.php');
if (empty($mmrpg_database_robots)){ print_error_and_quit('The robot database could not be loaded'); }
$robots_index = !empty($mmrpg_database_robots) ? $mmrpg_database_robots : array();

// If not empty, go through and remove or reformat api-incompatible data
if (!empty($robots_index)){
    foreach ($robots_index AS $robot_token => $robot_data){
        unset($robot_data['_parsed']);
        unset($robot_data['robot_id']);
        unset($robot_data['robot_functions']);
        $robot_data['robot_abilities_rewards'] = !empty($robot_data['robot_rewards']['abilities']) ? $robot_data['robot_rewards']['abilities'] : array();
        $robot_data['robot_abilities_compatible'] = !empty($robot_data['robot_abilities']) ? $robot_data['robot_abilities'] : array();
        unset($robot_data['robot_abilities'], $robot_data['robot_rewards']);
        $backup_robot_data = $robot_data; $robot_data = array();
        foreach ($backup_robot_data AS $k => $v){ $robot_data[str_replace('robot_', '', $k)] = $v; }
        $robots_index[$robot_token] = $robot_data;
    }
}

// Print out the robots index as JSON so others can use them
if (!empty($robots_index)){ print_success_and_update_api_cache(array('robots' => $robots_index, 'total' => count($robots_index))); }
else { print_error_and_quit('The robot index array was empty'); }

?>
