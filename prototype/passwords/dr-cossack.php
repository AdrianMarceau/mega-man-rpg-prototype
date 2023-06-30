<?

// -- DR. COSSACK PASSWORDS -- //

// Collect the players index if not already populated
if (!isset($mmrpg_index_players) || empty($mmrpg_index_players)){
    $mmrpg_index_players = rpg_player::get_index(true);
}

// Collect the temp battle flags
$temp_flags = !empty($_SESSION['GAME']['flags']) ? $_SESSION['GAME']['flags'] : array();
//error_log(basename(__FILE__).'//$temp_flags: '.print_r($temp_flags, true));

// If the UNLOCK RHYTHM password was created
if (!empty($temp_flags['drcossack_password_robotgetrhythmandblues'])){
    if (!mmrpg_prototype_robot_unlocked(false, 'rhythm')){
        // Unlock Roll as a playable character
        $unlock_player_info = $mmrpg_index_players['dr-cossack'];
        $unlock_robot_info = rpg_robot::get_index_info('rhythm');
        $unlock_robot_info['robot_level'] = 1;
        $unlock_robot_info['robot_experience'] = 999;
        mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true);
        header('Location: '.MMRPG_CONFIG_ROOTURL.'prototype.php');
        exit();
    }
}

// If the UNLOCK BUBBLE BOMB password was created
if (!empty($temp_flags['drcossack_password_abilitygetbubblebombsaway'])){
    if (!mmrpg_prototype_ability_unlocked('dr-cossack', false, 'bubble-bomb')){
        // Unlock Bubble Bomb as an equippable ability
        mmrpg_game_unlock_ability($mmrpg_index_players['dr-cossack'], false, array('ability_token' => 'bubble-bomb'), true);
        header('Location: '.MMRPG_CONFIG_ROOTURL.'prototype.php');
        exit();
    }
}

// If the UNLOCK SHADOW BLADE password was created
if (!empty($temp_flags['drcossack_password_abilitygetcutterofdarkness'])){
    if (!mmrpg_prototype_ability_unlocked('dr-cossack', false, 'shadow-blade')){
        // Unlock Bubble Bomb as an equippable ability
        mmrpg_game_unlock_ability($mmrpg_index_players['dr-cossack'], false, array('ability_token' => 'shadow-blade'), true);
        header('Location: '.MMRPG_CONFIG_ROOTURL.'prototype.php');
        exit();
    }
}

// If the UNLOCK COPY SHOT password was created
if (!empty($temp_flags['drcossack_password_abilitygetnowivegotyourpower'])){
    if (!mmrpg_prototype_ability_unlocked('dr-cossack', false, 'copy-shot')){
        // Unlock Copy Shot as an equippable ability
        mmrpg_game_unlock_ability($mmrpg_index_players['dr-cossack'], false, array('ability_token' => 'copy-shot'), true);
        header('Location: '.MMRPG_CONFIG_ROOTURL.'prototype.php');
        exit();
    }
}

// If the UNLOCK COPY SOUL password was created
if (!empty($temp_flags['drlight_password_abilitygetwithallmyheartandsoul'])){
    if (!mmrpg_prototype_ability_unlocked('dr-cossack', false, 'copy-soul')){
        // Unlock Copy Shot as an equippable ability
        mmrpg_game_unlock_ability($mmrpg_index_players['dr-cossack'], false, array('ability_token' => 'copy-soul'), true);
        header('Location: '.MMRPG_CONFIG_ROOTURL.'prototype.php');
        exit();
    }
}

// No rival to unlock at the moment....

?>