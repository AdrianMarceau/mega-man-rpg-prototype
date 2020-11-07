<?
/*
 * INDEX PAGE : COMMUNITY
 */

// Define the viewing as a moderator flag for global use
if (in_array($this_userinfo['role_id'], array(1, 6, 2, 7))){ define('COMMUNITY_VIEW_MODERATOR', true); }
else { define('COMMUNITY_VIEW_MODERATOR', false); }

// Define the SEO variables for this page
$this_seo_title = 'Community | '.$this_seo_title;
$this_seo_description = 'The community forums serve as a place for players and developers to connect and communicate with each other, providing feedback and relaying ideas in a forum-style bulletin board tied directly to player\'s save files.  The Mega Man RPG Prototype is a browser-based fangame that combines the mechanics of both the Pokémon and Mega Man series of video games into one strange and wonderful little time waster.';

// Define the Open Graph variables for this page
$this_graph_data['title'] = 'Community Forums';
$this_graph_data['description'] = 'The community forums serve as a place for players and developers to connect and communicate with each other, providing feedback and relaying ideas in a forum-style bulletin board tied directly to player\'s save files.';
//$this_graph_data['image'] = MMRPG_CONFIG_ROOTURL.'images/assets/mmrpg-prototype-logo-2k19.png';
//$this_graph_data['type'] = 'website';

// Define the MARKUP variables for this page
$this_markup_header = 'Mega Man RPG Prototype Community';


/*
 * COLLECT FORMACTIONS
 */

// Collect this player's battle point total
if (empty($_SESSION[mmrpg_game_token()]['DEMO'])){
    $community_battle_points = mmrpg_prototype_battle_points();
} else {
    $community_battle_points = 0;
}

// Collect all the categories from the index
$this_categories_index = mmrpg_website_community_index();
$this_categories_index_tokens = array();
if (!empty($this_categories_index)){
    foreach ($this_categories_index AS $token => $info){
        $this_categories_index_tokens[$info['category_id']] = $token;
    }
}

// Include the community form actions
require_once(MMRPG_CONFIG_ROOTDIR.'pages/db-logic/community_actions.php');


/*
 * COLLECT INDEXES
 */

// Define the view based on available data
$this_current_view = 'index';
if (!empty($this_current_cat)){ $this_current_view = 'category'; }
if ($this_current_id !== false && !empty($this_current_token)){ $this_current_view = 'thread'; }

// If a specific category has been requested, collect its info
$this_category_info = array();
if (!empty($this_current_cat) && !empty($this_categories_index[$this_current_cat])){
    // Collect this specific category from the database index
    $this_category_info = $this_categories_index[$this_current_cat];
}

// If a specific thread has been requested, collect its info
$this_thread_info = array();
// If this is a new thread, collect default info
if (empty($this_current_id) && $this_current_token == 'new'){

    // Collect this specific thread from the database
    $temp_thread_fields = mmrpg_community_thread_index_fields(true, 'threads');
    $this_thread_query = "SELECT {$temp_thread_fields}
        FROM mmrpg_threads AS threads
        LIMIT 1";
        //WHERE threads.thread_id = '0'";
    $this_thread_info = $db->get_array($this_thread_query);
    foreach ($this_thread_info AS $key => $info){ $this_thread_info[$key] = is_numeric($info) ? 0 : ''; }
    $this_thread_info['user_id'] = $this_userinfo['user_id'];
    $this_thread_info['user_name'] = $this_userinfo['user_name'];
    $this_thread_info['user_name_public'] = $this_userinfo['user_name_public'];
    //die('<pre>'.print_r($this_thread_info, true).'</pre>');

}
elseif (!empty($this_current_id) && !empty($this_current_token)){

    // Collect this specific thread from the database
    $this_thread_query = "SELECT threads.*,
        users.user_id,
        users.role_id,
        roles.role_token,
        roles.role_name,
        roles.role_icon,
        users.user_name,
        users.user_name_clean,
        users.user_name_public,
        users.user_gender,
        users.user_image_path,
        users.user_background_path,
        users.user_colour_token,
        users.user_email_address,
        users.user_website_address,
        users.user_date_created,
        users.user_date_accessed,
        users.user_date_modified,
        users.user_last_login,
        users.user_flag_postpublic
        FROM mmrpg_threads AS threads
        LEFT JOIN mmrpg_users AS users ON threads.user_id = users.user_id
        LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id
        WHERE threads.thread_id = '{$this_current_id}' AND threads.thread_token = '{$this_current_token}'";
    $this_thread_info = $db->get_array($this_thread_query);

    // If this thread has not already been viewed this session, increment the counter
    if (!empty($this_thread_info)){
        $temp_session_key = 'mmrpg_thread_viewed_'.$this_thread_info['thread_id'];
        if (!empty($_SESSION[$temp_session_key])){
            $temp_current_views = $this_thread_info['thread_views'];
            $temp_new_views = $temp_current_views + 1;
            $temp_update_session = $db->query("UPDATE mmrpg_threads SET thread_views = {$temp_new_views} WHERE thread_id = {$this_thread_info['thread_id']}");
            if (!empty($temp_update_session)){ $this_thread_info['thread_views'] = $temp_new_views; }
            $_SESSION[$temp_session_key] = true;
        }
    }

}

// If the current view is a specific thread
if ($this_current_view == 'thread'){

    // Check if we're creating a new thread or not
    if (
    (empty($this_current_id) && $this_current_token == 'new') ||
    (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && !empty($_REQUEST['thread_id']))
    ){
        // Require the community thread view
        require_once(MMRPG_CONFIG_ROOTDIR.'pages/db-logic/community_thread_new.php');
    } else {
        // Require the community thread view
        require_once(MMRPG_CONFIG_ROOTDIR.'pages/db-logic/community_thread.php');
    }

}
// Else if the current view is the category listing
elseif ($this_current_view == 'category' && empty($this_current_sub)){

    // Prevent logged-out users from viewing personal messages
    if (rpg_user::is_guest() && ($this_current_cat == 'personal')){
        header('Location: '.MMRPG_CONFIG_ROOTURL.'community/');
        exit();
    }

    //die(print_r($this_category_info, true));

    // If this if chat specifically, include separate file
    if ($this_current_cat == 'chat'){

        // Require the community chat view
        require_once(MMRPG_CONFIG_ROOTDIR.'pages/db-logic/community_chat.php');

    }
    // Otherwise, include the normal community category file
    else {

        // Require the community category view
        require_once(MMRPG_CONFIG_ROOTDIR.'pages/db-logic/community_category.php');

    }

}
// Else if the current view is the category listing
elseif ($this_current_view == 'category' && $this_current_sub == 'new'){

    // Require the community category recent view
    require_once(MMRPG_CONFIG_ROOTDIR.'pages/db-logic/community_category_recent.php');

}
// Else if the current view is the community index
elseif ($this_current_view == 'index'){

    // Require the community thread view
    require_once(MMRPG_CONFIG_ROOTDIR.'pages/db-logic/community_index.php');

}

// DEBUG

//$this_markup_body = '';
//$this_markup_body .= '<pre class="debug">$_GET<br />'.htmlentities(print_r($_GET, true), ENT_QUOTES, 'UTF-8', true).'</pre>';
//$this_markup_body .= '<pre class="debug">$_POST<br />'.htmlentities(print_r($_POST, true), ENT_QUOTES, 'UTF-8', true).'</pre>';
//$this_markup_body .= '<pre class="debug">'.htmlentities(print_r($insert_threads_array, true), ENT_QUOTES, 'UTF-8', true).'</pre>';
//$this_markup_body .= '<pre class="debug">'.htmlentities(print_r($this_userinfo, true), ENT_QUOTES, 'UTF-8', true).'</pre>';
//if (!empty($this_thread_info)){ $this_markup_body .= '<pre class="debug">$this_update_threads<br />'.htmlentities(print_r($this_thread_info, true), ENT_QUOTES, 'UTF-8', true).'</pre>'; }
//if (!empty($this_update_threads)){ $this_markup_body .= '<pre class="debug">$this_update_threads<br />'.htmlentities(print_r($this_update_threads, true), ENT_QUOTES, 'UTF-8', true).'</pre>'; }
//return;

/*
// Start the output buffer again
ob_start();
?>
<script type="text/javascript">
$(document).ready(function(){


    });
</script>
<?
// Collect the buffer and define the page markup
$this_markup_body .= trim(ob_get_clean());
*/
?>