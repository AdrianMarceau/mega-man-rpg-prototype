<?php

// For the game itself, because it's high priority, we increase the memory limit
ini_set('memory_limit', '256M');

/*
 * SESSION VARIABLES
 */

// If we're in a file page, prevent userinfo caching
if (preg_match('/file.php$/i', basename(__FILE__))
    || preg_match('/settings.php$/i', basename(__FILE__))){
    // Prevent userinfo caching for this page
    unset($_SESSION['GAME']['USER']['userinfo']);
}

// Create mandatory session variables if they do not exist
if (!isset($_SESSION['BATTLES'])){ $_SESSION['BATTLES'] = array(); }
if (!isset($_SESSION['FIELDS'])){ $_SESSION['FIELDS'] = array(); }
if (!isset($_SESSION['PLAYERS'])){ $_SESSION['PLAYERS'] = array(); }
if (!isset($_SESSION['ROBOTS'])){ $_SESSION['ROBOTS'] = array(); }
if (!isset($_SESSION['ABILITIES'])){ $_SESSION['ABILITIES'] = array(); }
if (!isset($_SESSION['ITEMS'])){ $_SESSION['ITEMS'] = array(); }
if (!isset($_SESSION['SKILLS'])){ $_SESSION['SKILLS'] = array(); }
// Define the COMMUNITY session trackers if they do not exist
if (!isset($_SESSION['COMMUNITY'])){ $_SESSION['COMMUNITY']['threads_viewed'] = array(); }


/*
 * BROWSER FLAGS
 */

// Define the WAP flag to false
$flag_wap = false;
$flag_ipad = false;
$flag_iphone = false;
// Collect the WAP flag if set in the URL query
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
if (isset($_GET['wap'])){ $flag_wap = $_GET['wap'] == 'true' ? true : false; }
//Otherwise, check if this is an iPhone or iPad browser
elseif (!empty($_GET['iphone']) || strpos($user_agent, 'iPhone') !== FALSE){ $flag_iphone = true; }
elseif (!empty($_GET['ipad']) || strpos($user_agent, 'iPad')){  $flag_ipad = $flag_wap = true; }
unset($user_agent);

/*
 * GAME SAVING AND LOADING
 */

// Only continue with saving/loading functions if we're NOT in critical mode
if (!defined('MMRPG_CRITICAL_ERROR')){

    // Disable the memory limit for this script
    //ini_set('memory_limit', '128M');
    //ini_set('memory_limit', '-1');

    // Reset the session completed if user ID not set
    if (empty($_SESSION['GAME']['USER']['userid'])){

        // Exit the game and enter demo mode
        rpg_game::start_session();

    }

    // Collect a reference to the user info for later
    $this_user = $_SESSION['GAME']['USER'];

}

/*
 * PAGE REQUESTS
 */

// Print out request to error log for debugging
//error_log('$_GET (before) = '.print_r($_GET, true));

// Collect an index of valid pages if available
$mmrpg_page_index = array();
if (!defined('MMRPG_CRITICAL_ERROR')){
    $temp_page_fields = cms_website_page::get_index_fields(true);
    $mmrpg_page_index_query = "SELECT
            {$temp_page_fields}
            FROM mmrpg_website_pages
            WHERE page_flag_published = 1
            ORDER BY page_order ASC
            ;";
    $cache_token = md5($mmrpg_page_index_query);
    $cached_index = rpg_object::load_cached_index('website.pages', $cache_token);
    if (!empty($cached_index)){
        $mmrpg_page_index = $cached_index;
        unset($cached_index);
    } else {
        $mmrpg_page_index = $db->get_array_list($mmrpg_page_index_query, 'page_url');
        rpg_object::save_cached_index('website.pages', $cache_token, $mmrpg_page_index);
    }
    //die('<pre>$mmrpg_page_index = '.print_r($mmrpg_page_index, true).'</pre>');
}

// Collect the current page from the header if set
$this_allowed_pages = array('home', 'about', 'gallery', 'database', 'leaderboard', 'community', 'prototype', 'credits', 'contact', 'file', 'error', 'dev', 'test');
if (!empty($mmrpg_page_index)){
    //$more_allowed_pages = array_keys($mmrpg_page_index);
    $more_allowed_pages = array_map(function($a){ return $a['page_token']; }, $mmrpg_page_index);
    if (!empty($more_allowed_pages)){ $this_allowed_pages = array_unique(array_merge($this_allowed_pages, $more_allowed_pages)); }
    //die('<pre>$this_allowed_pages = '.print_r($this_allowed_pages, true).'</pre>');
}
$this_current_page = $_GET['page'] = !empty($_GET['page']) ? strtolower($_GET['page']) : false;
$this_current_sub = $_GET['sub'] = !empty($_GET['sub']) && !is_numeric($_GET['sub']) ? strtolower($_GET['sub']) : false;
$this_current_cat = $_GET['cat'] = !empty($_GET['cat']) && !is_numeric($_GET['cat']) ? strtolower($_GET['cat']) : false;
$this_current_num = $_GET['num'] = !empty($_GET['num']) && is_numeric($_GET['num']) ? $_GET['num'] : 1;
$this_current_token = $_GET['token'] = !empty($_GET['token']) ? $_GET['token'] : '';

// Generate the current URI based on the presence of the page and/or sub tokens (not not for home)
$this_current_uri = !empty($this_current_page) && $this_current_page != 'home' ? $this_current_page.'/' : '';
$this_current_uri .= !empty($this_current_sub) && $this_current_sub != 'home' ? $this_current_sub.'/' : '';

// Append the current num to the URI as long as we're not on a community page (it's added later)
if ($this_current_page != 'community'){
    $this_current_uri .= !empty($this_current_num) && $this_current_num > 1 ? $this_current_num.'/' : '';
}

// Trigger specific actions if we're on the pseudo-HOME page
if (isset($_GET['home']) || $this_current_sub == 'home' || $this_current_page == 'home'){
    $_GET['this_redirect'] = $this_current_url;
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: '.$this_current_url);
    exit();
}
// Trigger specific actions if we're on the UPDATES page
elseif ($this_current_page == 'updates'){
    $this_current_id = !empty($_GET['id']) ? strtolower($_GET['id']) : 0;
    $this_current_token = !empty($_GET['token']) ? $_GET['token'] : '';
    if ($this_current_id !== false && !empty($this_current_token)){
        $this_current_uri .= $this_current_id.'/'.$this_current_token.'/';
    }
}
// Trigger specific actions if we're on the COMMUNITY page
elseif ($this_current_page == 'community'){
    $this_current_cat = !empty($_GET['cat']) ? strtolower($_GET['cat']) : '';
    $this_current_id = !empty($_GET['id']) ? strtolower($_GET['id']) : 0;
    $this_current_token = !empty($_GET['token']) ? $_GET['token'] : '';
    $this_current_target = !empty($_GET['target']) ? $_GET['target'] : '';
    if (!empty($this_current_cat) && $this_current_id !== false && !empty($this_current_token)){
        $this_current_uri .= $this_current_cat.'/'.$this_current_id.'/'.$this_current_token.'/';
    } elseif (!empty($this_current_cat) && !empty($this_current_sub)){
        $this_current_uri = $this_current_page.'/'.$this_current_cat.'/'.$this_current_sub.'/';
    } elseif (!empty($this_current_cat)){
        $this_current_uri .= $this_current_cat.'/';
    }
    if ($this_current_cat == 'personal' && !empty($this_current_target)){
        $this_current_uri .= $this_current_target.'/';
    }
    if ($this_current_cat != 'personal' && $this_current_num > 1){
        $this_current_uri .= $this_current_num.'/';
    }
}
// Trigger specific actions if we're on the DATABASE page
elseif ($this_current_page == 'database'){
    if (!isset($mmrpg_index_types)){ $mmrpg_index_types = rpg_type::get_index(); }
    $this_current_token = !empty($_GET['token']) ? $_GET['token'] : '';
    if (!empty($this_current_token) && isset($mmrpg_index_types[$this_current_token])){
        $this_current_filter = $_GET['filter'] = $this_current_token;
        $this_current_filter_name = $this_current_filter == 'none' ? 'Neutral' : ucfirst($this_current_filter);
        $this_current_uri .= $this_current_filter.'/';
        $this_current_token = $_GET['token'] = '';
    } elseif (!empty($this_current_token)){
        $this_current_uri .= $this_current_token.'/';
    }
}
// Trigger specific actions if we're on the LEADERBOARD page
elseif ($this_current_page == 'leaderboard'){
    $this_current_token = !empty($_GET['token']) ? $_GET['token'] : '';
    if (!empty($this_current_token)){
        $this_current_uri .= $this_current_token.'/';
        $this_current_player = !empty($_GET['player']) ? $_GET['player'] : '';
        if (!empty($this_current_player)){
            $this_current_uri .= $this_current_player.'/';
        }
    }
}
// Trigger specific actions if we're on the FILE page
elseif ($this_current_page == 'file'){
    $this_current_token = !empty($_GET['token']) ? $_GET['token'] : '';
    if (!empty($this_current_token)){
        $this_current_uri .= $this_current_token.'/';
    }
}

// Generate the full URL using the above URI string and update the GET
$this_current_url = MMRPG_CONFIG_ROOTURL.$this_current_uri;
$_GET['this_current_uri'] = $this_current_uri; //urlencode($this_current_uri);
$_GET['this_current_url'] = $this_current_url; //urlencode($this_current_url);
//die('<pre>$_GET = '.print_r($_GET, true).'</pre>');

// Now that all the redirecting is done, if the current page it totally empty, it's ACTUALLY home
if (empty($this_current_page) || !in_array($this_current_page, $this_allowed_pages)){ $this_current_page = 'home'; }


/*
 * USERINFO COLLECTION
 */

// If we're NOT viewing the session info
$this_userid = MMRPG_SETTINGS_GUEST_ID;
if (!defined('MMRPG_CRITICAL_ERROR') && !defined('MMRPG_INDEX_SESSION') && !defined('MMRPG_INDEX_SESSION') && !defined('MMRPG_INDEX_STYLES')){

    // If the user session is already in progress, collect the details
    if (!rpg_user::is_guest()){

        // Collect this userinfo from the database
        $this_userid = (int)($_SESSION['GAME']['USER']['userid']);
        if (empty($_SESSION['GAME']['USER']['userinfo'])){
            $temp_user_fields = rpg_user::get_index_fields(true, 'users');
            $temp_user_role_fields = rpg_user_role::get_index_fields(true, 'roles');
            $this_userinfo = $db->get_array("SELECT {$temp_user_fields}, {$temp_user_role_fields} FROM mmrpg_users AS users LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id WHERE users.user_id = '{$this_userid}' LIMIT 1");
            $_SESSION['GAME']['USER']['userinfo'] = $this_userinfo;
            $_SESSION['GAME']['USER']['userinfo']['user_password_encoded'] = '';
        } else {
            $this_userinfo = $_SESSION['GAME']['USER']['userinfo'];
        }

        if (!defined('MMRPG_SCRIPT_REQUEST')){
            if (empty($_SESSION['GAME']['BOARD']['boardinfo'])
                || empty($_SESSION['GAME']['BOARD']['boardinfo']['board_id'])
                || empty($_SESSION['GAME']['BOARD']['boardrank'])
                || empty($_SESSION['GAME']['BOARD']['boardtime'])
                || $_SESSION['GAME']['BOARD']['boardtime'] < $this_userinfo['user_date_modified']){
                $this_boardinfo = $db->get_array("SELECT
                    board_id, user_id, save_id,
                    board_points, board_points_dr_light, board_points_dr_wily, board_points_dr_cossack,
                    board_points_pending, -- board_points_pending_dr_light, board_points_pending_dr_wily, board_points_pending_dr_cossack,
                    board_points_legacy, -- board_points_dr_light_legacy, board_points_dr_wily_legacy, board_points_dr_cossack_legacy,
                    board_robots, board_robots_dr_light, board_robots_dr_wily, board_robots_dr_cossack,
                    board_battles, board_battles_dr_light, board_battles_dr_wily, board_battles_dr_cossack,
                    board_awards, board_awards_dr_light, board_awards_dr_wily, board_awards_dr_cossack,
                    board_stars, board_stars_dr_light, board_stars_dr_wily, board_stars_dr_cossack,
                    board_abilities, board_abilities_dr_light, board_abilities_dr_wily, board_abilities_dr_cossack,
                    board_missions, board_missions_dr_light, board_missions_dr_wily, board_missions_dr_cossack,
                    board_zenny,
                    board_date_created, board_date_modified
                    FROM mmrpg_leaderboard
                    WHERE
                    user_id = {$this_userid}
                    ;");
                $this_boardid = $this_boardinfo['board_id'];
                $this_boardinfo['board_rank'] = mmrpg_prototype_leaderboard_rank($this_userid);
                $_SESSION['GAME']['BOARD']['boardinfo'] = $this_boardinfo;
                $_SESSION['GAME']['BOARD']['boardtime'] = $this_userinfo['user_date_modified'];
                $_SESSION['GAME']['BOARD']['boardrank'] = $this_boardinfo['board_rank'];
            } else {
                $this_boardinfo = $_SESSION['GAME']['BOARD']['boardinfo'];
                $this_boardid = $this_boardinfo['board_id'];
            }
        }

    }
    // Otherwise, generate some details user details
    else {

        // Collect the guest userinfo from the database
        $this_userid = MMRPG_SETTINGS_GUEST_ID;
        if (empty($_SESSION['GAME']['USER']['userinfo'])){
            $temp_user_fields = rpg_user::get_index_fields(true, 'users');
            $temp_user_role_fields = rpg_user_role::get_index_fields(true, 'roles');
            $this_userinfo = $db->get_array("SELECT {$temp_user_fields}, {$temp_user_role_fields} FROM mmrpg_users AS users LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id WHERE users.user_id = '{$this_userid}' LIMIT 1");
            $_SESSION['GAME']['USER']['userinfo'] = $this_userinfo;
        } else {
            $this_userinfo = $_SESSION['GAME']['USER']['userinfo'];
        }

        if (!defined('MMRPG_SCRIPT_REQUEST')){
            $this_boardinfo = array();
            $this_boardinfo['board_rank'] = 0;
            $this_boardid = 0;
        }

    }

} else {
    // Create the userinfo array anyway to prevent errors
    $this_userinfo = array();
}


/*
 * WEBSITE THEME GENERATION
 */

// If we're NOT viewing the session info
if (!defined('MMRPG_INDEX_SESSION') && !defined('MMRPG_INDEX_STYLES')){

    // If this user is a member, collect background and type settings from the account
    if (rpg_game::is_user()){

        // Collect theme settings from the user's profile settings
        $temp_field_path = !empty($this_userinfo['user_background_path']) ? $this_userinfo['user_background_path'] : 'fields/'.rpg_player::get_intro_field();
        $temp_field_type = !empty($this_userinfo['user_colour_token']) ? $this_userinfo['user_colour_token'] : '';
        if (!empty($temp_field_type) && !empty($this_userinfo['user_colour_token2'])){ $temp_field_type .= '_'.$this_userinfo['user_colour_token2']; }

    }
    // Otherwise if guest we need to select a randomized field background for a time interval
    else {

        // Collect the date-stamp for holiday themes
        $date_month = (int)(date('m'));
        $date_month_name = strtolower(date('F'));
        $date_day = (int)(date('d'));
        //error_log('$date_month = '.print_r($date_month, true));
        //error_log('$date_month_name = '.print_r($date_month_name, true));
        //error_log('$date_day = '.print_r($date_day, true));


        // Include the list of monthly themes we can pick from
        $mmrpg_monthly_themes = array();
        require(MMRPG_CONFIG_ROOTDIR.'includes/themes.php');
        //error_log('$mmrpg_monthly_themes = '.print_r($mmrpg_monthly_themes, true));
        if (!empty($_GET['month']) && isset($mmrpg_monthly_themes[$_GET['month']])){
            $date_month_name = $_GET['month'];
        }

        // If an appropriate theme exists for this month, use it
        if (isset($mmrpg_monthly_themes[$date_month_name])){
            $temp_theme = $mmrpg_monthly_themes[$date_month_name];
            //error_log('$temp_theme = '.print_r($temp_theme, true));
            $temp_field_path = !empty($temp_theme['field']) ? $temp_theme['field'] : 'fields/gentle-countryside';
            $temp_field_type = !empty($temp_theme['type']) ? $temp_theme['type'] : 'none';
            if (!empty($temp_theme['type2'])){ $temp_field_type .= '_'.$temp_theme['type2']; }
            $temp_mecha_tokens = !empty($temp_theme['mechas']) ? $temp_theme['mechas'] : array();
        }
        // Otherwise, we can refuse normal theme functionality
        else {

            // Define the theme timeout for auto updating
            $theme_timeout = 60 * 60 * 1; // 60s x 60m = 1 hr
            if (!isset($_SESSION['INDEX']['theme_cache']) || (time() - $_SESSION['INDEX']['theme_cache']) > $theme_timeout){
                // Hard code the type to none but collect a ranzomized field token
                $temp_field_info = $db->get_array("SELECT
                    field_token,
                    CONCAT('fields/', field_token) AS field_path,
                    field_type
                    FROM mmrpg_index_fields
                    WHERE field_flag_complete = 1 AND field_flag_published = 1 AND field_flag_hidden = 0 AND field_game IN ('MM1', 'MM2', 'MM3', 'MM4') AND field_type <> ''
                    ORDER BY RAND() LIMIT 1
                    ;");
                $temp_field_type = $temp_field_info['field_type'];
                $temp_field_path = $temp_field_info['field_path'];
                $temp_mecha_tokens = $db->get_array_list("SELECT
                    robot_token AS mecha_token
                    FROM mmrpg_index_robots
                    WHERE robot_flag_complete = 1 AND robot_flag_published = 1 AND robot_flag_hidden = 0 AND robot_class = 'mecha' AND robot_core = '{$temp_field_type}' AND robot_game IN ('MM1', 'MM2', 'MM3', 'MM4')
                    ORDER BY RAND()
                    ;", 'mecha_token');
                $temp_mecha_tokens = !empty($temp_mecha_tokens) ? array_keys($temp_mecha_tokens) : array();
                // Autocast the field type to none for logged-out users
                $temp_field_type = 'none';
                // Update the session with these settings
                $_SESSION['INDEX']['theme_cache'] = time();
                $_SESSION['INDEX']['theme_field_path'] = $temp_field_path;
                $_SESSION['INDEX']['theme_field_type'] = $temp_field_type;
                $_SESSION['INDEX']['theme_mecha_tokens'] = $temp_mecha_tokens;
            } else {
                // Collect existing theme settings from the session
                $temp_field_path = $_SESSION['INDEX']['theme_field_path'];
                $temp_field_type = $_SESSION['INDEX']['theme_field_type'];
                $temp_mecha_tokens = $_SESSION['INDEX']['theme_mecha_tokens'];
            }

        }

    }

    // Collect the info for the chosen temp field
    if (empty($temp_field_path)){ $temp_field_path = 'fields/'.rpg_player::get_intro_field(); }
    list($temp_field_kind, $temp_field_token) = explode('/', $temp_field_path);
    $temp_field_data = rpg_field::get_index_info($temp_field_token);
    if (!empty($temp_mecha_tokens) && !empty($temp_field_data)){
        $temp_field_data['field_mechas'] = array_merge($temp_field_data['field_mechas'], $temp_mecha_tokens);
        $temp_field_data['field_mechas'] = array_unique($temp_field_data['field_mechas']);
    }

    // Define the current field token for the index
    define('MMRPG_SETTINGS_CURRENT_FIELDTOKEN', $temp_field_data['field_token']);
    define('MMRPG_SETTINGS_CURRENT_FIELDTYPE', (!empty($temp_field_type) ? $temp_field_type : (!empty($temp_field_data['field_type']) ? $temp_field_data['field_type'] : 'none')));
    define('MMRPG_SETTINGS_CURRENT_FIELDFRAMES', count($temp_field_data['field_background_frame']));
    define('MMRPG_SETTINGS_CURRENT_FIELDMECHA', (!empty($temp_field_data['field_mechas']) ? $temp_field_data['field_mechas'][0] : 'met'));

}

// Print out request to error log for debugging
//error_log('$_GET (after) = '.print_r($_GET, true));

?>