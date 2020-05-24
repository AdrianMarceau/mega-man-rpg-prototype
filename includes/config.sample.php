<?

// Define the global cache date and settings
define('MMRPG_CONFIG_CACHE_DATE', '20140830-02');
define('MMRPG_CONFIG_DEBUG_MODE', false);

// Define the global path variables for this installation
define('MMRPG_CONFIG_ROOTDIR', '/var/www/html/');
define('MMRPG_CONFIG_ROOTURL', 'http://'.$_SERVER['HTTP_HOST'].'/');
define('MMRPG_CONFIG_CACHE_INDEXES', false);
define('MMRPG_CONFIG_IS_LIVE', false);

// Define the global database credentials for this installation
define('MMRPG_CONFIG_DBHOST', 'localhost');
define('MMRPG_CONFIG_DBUSERNAME', 'username');
define('MMRPG_CONFIG_DBPASSWORD', 'password');
define('MMRPG_CONFIG_DBCHARSET', 'utf8');
define('MMRPG_CONFIG_DBNAME', 'mmrpg2k11');

// Define the analytics account ID
define('LEGACY_MMRPG_GA_ACCOUNTID', 'UA-00000000-0');

// Define the PASSWORD SALT and OMEGA SEED string values
define('MMRPG_SETTINGS_PASSWORD_SALT', 'password-salt');
define('MMRPG_SETTINGS_OMEGA_SEED', 'omega-seed-salt');

// Define some SALT for the IMAGEPROXY hash values
define('MMRPG_SETTINGS_IMAGEPROXY_SALT', 'image-proxy-salt');

// Define the list of administrator-approved remote addresses
define('MMRPG_CONFIG_ADMIN_LIST', '127.0.0.1,999.999.999.999');

?>