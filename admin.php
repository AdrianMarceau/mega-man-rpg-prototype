<?

// Require the application top file
define('MMRPG_ADMIN_PANEL', true);
require_once('top.php');
require_once('classes/cms_admin.php');

// Define the page title and markup variables
$this_page_title = 'MMRPG Admin Panel';
$this_page_markup = '';

// Collect the current action from the URL if set
$this_page_action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'home';
$this_page_tabtitle = 'Admin';


/*
 * SAVE UPDATES REQUEST
 * If this is a save updating request, get to it!
 */

// Prevent timeouts and memory leakages
@ini_set('memory_limit', '128M'); //100MB
@ini_set('max_execution_time', 300); //300 seconds = 5 minutes

// Define the form messages and collect any from session
$form_messages = array();
if (!empty($_SESSION['mmrpg_admin']['form_messages'])){
    $form_messages = $_SESSION['mmrpg_admin']['form_messages'];
}

// Define the form messages and collect any from session
$backup_form_data = array();
if (!empty($_SESSION['mmrpg_admin']['form_data'])){
    $backup_form_data = $_SESSION['mmrpg_admin']['form_data'];
    unset($_SESSION['mmrpg_admin']['form_data']);
}

// Define a function for saving form messages to session
function backup_form_messages(){
    global $form_messages;
    $_SESSION['mmrpg_admin']['form_messages'] = $form_messages;
}

// Define a function for saving form messages to session
function backup_form_data(){
    global $form_data;
    $_SESSION['mmrpg_admin']['form_data'] = $form_data;
}

// Define a function for generating form messages
function print_form_messages($print = true, $clear = true){
    global $form_messages;
    $this_message_markup = '';
    if (!empty($form_messages)){
        $this_message_markup .= '<ul class="list">'.PHP_EOL;
        foreach ($form_messages AS $key => $message){
            list($type, $text) = $message;
            $this_message_markup .= '<li class="message '.$type.'">';
                //$this_message_markup .= ucfirst($type).' : ';
                $this_message_markup .= $text;
            $this_message_markup .= '</li>'.PHP_EOL;
        }
        $this_message_markup .= '</ul>'.PHP_EOL;
        if ($clear){ $_SESSION['mmrpg_admin']['form_messages'] = array(); }
    }
    if (!empty($this_message_markup)){
        $this_message_markup = '<div class="messages">'.$this_message_markup.'</div>';
    }
    if ($print){ echo $this_message_markup; }
    else { return $this_message_markup; }
}

// Define a function for exiting a form action
function redirect_form_action($location){
    backup_form_messages();
    backup_form_data();
    if (!empty($location)){ header('Location: '.$location); }
    exit();
}

// Define a function for exiting a form action
function exit_form_action($output = ''){
    backup_form_messages();
    backup_form_data();
    exit($output);
}

// Collect details for this admin user from the database
$this_admininfo = array();
if (!empty($_SESSION['admin_id'])){
    $this_admininfo = $db->get_array("SELECT
        users.user_id,
        users.user_name,
        users.user_name_public,
        users.user_name_clean,
        roles.role_id,
        roles.role_name,
        roles.role_token,
        roles.role_level
        FROM mmrpg_users AS users
        LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id
        WHERE
        users.user_id = '{$_SESSION['admin_id']}'
        ORDER BY
        user_id ASC
        ;");
}

// Define which pages this specific user has access to
$this_adminaccess = array();
$this_adminaccess[] = 'home';
if (!empty($this_admininfo)
    && MMRPG_CONFIG_ADMIN_PERMS_LIST !== ''){
    $temp_admin_perms = json_decode(MMRPG_CONFIG_ADMIN_PERMS_LIST, true);
    if (isset($temp_admin_perms[$this_admininfo['user_id']])){
        $this_adminaccess = array_merge($this_adminaccess, $temp_admin_perms[$this_admininfo['user_id']]);
    }
}

// If we're not logged in yet
if (!MMRPG_CONFIG_ADMIN_MODE){
    // Require the admin home file
    require(MMRPG_CONFIG_ROOTDIR.'admin/login.php');
}
// Else if we're logging out now
elseif ($this_page_action == 'exit'){
    // Unset session variables and refresh page
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_username_display']);
    redirect_form_action('admin.php');
}
// If this is the HOME request
elseif ($this_page_action == 'home'){
    // Require the admin home file
    require(MMRPG_CONFIG_ROOTDIR.'admin/home.php');
}
// Else if this is an UPDATE request
elseif ($this_page_action == 'update'){
    // Require the update file
    require(MMRPG_CONFIG_ROOTDIR.'admin/update.php');
}
// Else if this is a PRURGE request
elseif ($this_page_action == 'purge'){
    // Require the purge file
    require(MMRPG_CONFIG_ROOTDIR.'admin/purge.php');
}
// Else if this is an IMPORT PLAYERS request
elseif ($this_page_action == 'import_players'){
    // Require the import players file
    require(MMRPG_CONFIG_ROOTDIR.'admin/import-players.php');
}
// Else if this is an IMPORT ROBOTS request
elseif ($this_page_action == 'import_robots'){
    // Require the import robots file
    require(MMRPG_CONFIG_ROOTDIR.'admin/import-robots.php');
}
// Else if this is an IMPORT ABILITIES request
elseif ($this_page_action == 'import_abilities'){
    // Require the import abilities file
    require(MMRPG_CONFIG_ROOTDIR.'admin/import-abilities.php');
}
// Else if this is an IMPORT ITEMS request
elseif ($this_page_action == 'import_items'){
    // Require the import items file
    require(MMRPG_CONFIG_ROOTDIR.'admin/import-items.php');
}
// Else if this is an IMPORT FIELDS request
elseif ($this_page_action == 'import_fields'){
    // Require the import fields file
    require(MMRPG_CONFIG_ROOTDIR.'admin/import-fields.php');
}
// Else if this is an DELETE CACHE request
elseif ($this_page_action == 'delete_cache'){
    // Require the delete cache file
    require(MMRPG_CONFIG_ROOTDIR.'admin/delete-cache.php');
}
// Else if this is an CLEAR SESSIONS request
elseif ($this_page_action == 'clear_sessions'){
    // Require the clear sessions file
    require(MMRPG_CONFIG_ROOTDIR.'admin/clear-sessions.php');
}
// Else if this is an EDIT USERS request
elseif ($this_page_action == 'edit_users'){
    // Require the edit users file
    require(MMRPG_CONFIG_ROOTDIR.'admin/edit-users.php');
}
// Else if this is an EDIT PAGES request
elseif ($this_page_action == 'edit_pages'){
    // Require the edit pages file
    require(MMRPG_CONFIG_ROOTDIR.'admin/edit-pages.php');
}
// Else if this is an EDIT ROBOT MASTERS request
elseif ($this_page_action == 'edit_robots'){
    // Require the edit robots file
    require(MMRPG_CONFIG_ROOTDIR.'admin/edit-robots.php');
}
// Else if this is an EDIT PLAYER CHARACTERS request
elseif ($this_page_action == 'edit_players'){
    // Require the edit robots file
    require(MMRPG_CONFIG_ROOTDIR.'admin/edit-players.php');
}
// Else if this is an EDIT BATTLE FIELDS request
elseif ($this_page_action == 'edit_fields'){
    // Require the edit robots file
    require(MMRPG_CONFIG_ROOTDIR.'admin/edit-fields.php');
}
// Else if this is an EDIT CHALLENGE MISSIONS request
elseif ($this_page_action == 'edit_challenges'){
    // Require the edit challenges file
    require(MMRPG_CONFIG_ROOTDIR.'admin/edit-challenges.php');
}
// Else if this is an EDIT ROGUE STARS request
elseif ($this_page_action == 'edit_stars'){
    // Require the edit stars file
    require(MMRPG_CONFIG_ROOTDIR.'admin/edit-stars.php');
}
// Otherwise, not a valid page
else {
    // Define error 404 text to print
    $this_error_markup = '<strong>Error 404</strong><br />Page Not Found';
    // Require the admin home file
    require(MMRPG_CONFIG_ROOTDIR.'admin/home.php');
}

// Unset the database variable
unset($db);


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title><?= $this_page_tabtitle ?> | Mega Man RPG Prototype | Last Updated <?= mmrpg_print_cache_date() ?></title>
<base href="<?=MMRPG_CONFIG_ROOTURL?>" />
<meta name="robots" content="noindex,nofollow" />
<meta name="format-detection" content="telephone=no" />
<link rel="apple-touch-icon" sizes="72x72" href="images/assets/ipad-icon_72x72.png" />
<meta name="viewport" content="user-scalable=yes, width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="image/x-icon" href="images/assets/favicon<?= !MMRPG_CONFIG_IS_LIVE ? '-local' : '' ?>.ico">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/solid.css" integrity="sha384-+0VIRx+yz1WBcCTXBkVQYIBVNEFH1eP6Zknm16roZCyeNg2maWEpk/l/KsyFKs7G" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/fontawesome.css" integrity="sha384-jLuaxTTBR42U2qJ/pm4JRouHkEDHkVqH0T1nyQXn1mZ7Snycpf6Rl25VBNthU4z0" crossorigin="anonymous">
<? if (!empty($admin_include_stylesheets)){ ?>
    <?= $admin_include_stylesheets ?>
<? } ?>
<link type="text/css" href="styles/style.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/prototype.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/file.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/admin.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/admin-responsive.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<?if($flag_wap):?>
<link type="text/css" href="styles/style-mobile.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<link type="text/css" href="styles/prototype-mobile.css?<?=MMRPG_CONFIG_CACHE_DATE?>" rel="stylesheet" />
<?endif;?>
</head>
<body id="mmrpg">
    <div id="admin">
        <h1 class="header"><?= $this_page_title ?></h1>
        <div class="content">
            <? if (!empty($_SESSION['admin_username_display'])): ?>
                <div class="userinfo">
                    <strong class="welcome">Welcome, <?= $_SESSION['admin_username_display'] ?></strong>
                    <span class="pipe">|</span>
                    <a class="link" href="admin.php?action=exit">Exit</a>
                </div>
            <? endif; ?>
            <?= $this_page_markup ?>
        </div>
    </div>
    <? if(false){ ?>
        <pre style="text-align: left; padding: 20px;">
        <? foreach ($_SESSION['GAME']['values']['battle_settings'] AS $player_token => $battle_settings){
            echo '<h1>'.$player_token.'</h1>'."\n";
            echo htmlentities(print_r($battle_settings), ENT_QUOTES, 'UTF-8', true);
        } ?>
        <?= htmlentities(print_r($_REQUEST), ENT_QUOTES, 'UTF-8', true) ?>
        </pre>
    <? } ?>
<script type="text/javascript" src="scripts/jquery.js?<?=MMRPG_CONFIG_CACHE_DATE?>"></script>
<? if (!empty($admin_include_javascript)){ ?>
    <?= $admin_include_javascript ?>
<? } ?>
<script type="text/javascript" src="scripts/admin.js?<?=MMRPG_CONFIG_CACHE_DATE?>"></script>
<? if (!empty($admin_inline_javascript)){ ?>
    <?= $admin_inline_javascript ?>
<? } ?>
</body>
</html>