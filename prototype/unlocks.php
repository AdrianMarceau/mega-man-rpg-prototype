<?
/*
 * COMMON UNLOCK MESSAGES
 */

// Define a function for generating a common "Prototype Complete" message w/ records
function generate_prototype_complete_message($player_token){

    global $session_token;
    global $mmrpg_index_players, $mmrpg_index_robots;
    if (empty($mmrpg_index_players)){ $mmrpg_index_players = rpg_player::get_index(true); }
    if (empty($mmrpg_index_robots)){ $mmrpg_index_robots = rpg_robot::get_index(true); }

    // Define the player's battle points total, battles complete, and other details
    //$player_token = 'dr-light';
    $player_info = $mmrpg_index_players[$player_token];
    $player_info['player_points'] = mmrpg_prototype_player_points($player_token);
    $player_info['player_battles_complete'] = mmrpg_prototype_battles_complete($player_token);
    $player_info['player_battles_complete_total'] = mmrpg_prototype_battles_complete($player_token, false);
    $player_info['player_battles_failure'] = mmrpg_prototype_battles_failure($player_token);
    $player_info['player_battles_failure_total'] = mmrpg_prototype_battles_failure($player_token, false);
    $player_info['player_robots_count'] = 0;
    $player_info['player_abilities_count'] = mmrpg_prototype_abilities_unlocked($player_token);
    $player_info['player_field_stars'] = mmrpg_prototype_stars_unlocked($player_token, 'field');
    $player_info['player_fusion_stars'] = mmrpg_prototype_stars_unlocked($player_token, 'fusion');
    $player_info['battle_turns_player_total'] = !empty($_SESSION[$session_token]['counters']['battle_turns_'.$player_info['player_token'].'_total']) ? $_SESSION[$session_token]['counters']['battle_turns_'.$player_info['player_token'].'_total'] : 0;
    $player_info['battle_turns_total'] = !empty($_SESSION[$session_token]['counters']['battle_turns_total']) ? $_SESSION[$session_token]['counters']['battle_turns_total'] : 0;


    // Define the player's experience points total and collect other robot details for display
    $player_info['player_experience'] = 0;
    $temp_robot_sprite_markup = array();
    if (!empty($_SESSION[$session_token]['values']['battle_rewards'])){
        foreach ($_SESSION[$session_token]['values']['battle_rewards'] AS $temp_player => $temp_player_info){
                if (!empty($_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'])){
                    $temp_player_robot_rewards = $_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'];
                    $temp_player_robot_settings = $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'];
                    if (empty($temp_player_robot_rewards) || empty($temp_player_robot_settings)){
                        unset($_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots']);
                        unset($_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots']);
                        continue;
                    }
                    foreach ($temp_player_robot_rewards AS $temp_key => $temp_robot_info){
                        if (empty($temp_robot_info['robot_token'])){
                            unset($_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'][$temp_key]);
                            unset($_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'][$temp_key]);
                            continue;
                        }
                        $temp_robot_token = $temp_robot_info['robot_token'];
                        $temp_robot_index = $mmrpg_index_robots[$temp_robot_token];
                        $temp_robot_settings = $temp_player_robot_settings[$temp_robot_token];
                        $temp_robot_rewards = $temp_player_robot_settings[$temp_robot_token];
                        if (empty($temp_robot_settings['original_player']) && $temp_player != $player_token){ continue; }
                        if ($temp_robot_settings['original_player'] != $player_token){ continue; }
                        $player_info['player_robots_count']++;
                        if (!empty($temp_robot_info['robot_level'])){ $player_info['player_experience'] += $temp_robot_info['robot_level'] * MMRPG_SETTINGS_BATTLEPOINTS_PERLEVEL; }
                        if (!empty($temp_robot_info['robot_experience'])){ $player_info['player_experience'] += $temp_robot_info['robot_experience']; }

                        $temp_size = (!empty($temp_robot_index['robot_image_size']) ? $temp_robot_index['robot_image_size'] : 40) * 2;
                        $temp_xsize = $temp_size.'x'.$temp_size;
                        $temp_position_y = 40 - ceil($player_info['player_robots_count'] * 2);
                        $temp_position_x = 80 + ceil($player_info['player_robots_count'] * 40);
                        if ($temp_size > 80){ $temp_position_x -= ceil(($temp_size - 80) / 2); }
                        $temp_sprite_styles = 'background-image: url(images/robots/'.$temp_robot_token.'/sprite_left_'.$temp_xsize.'.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: '.$temp_position_y.'px; left: '.$temp_position_x.'px;';
                        $temp_robot_sprite_markup[] = '<div class="sprite sprite_'.$temp_xsize.' sprite_'.$temp_xsize.'_02" style="'.$temp_sprite_styles.'"></div>';
                    }
                }
        }
    }

    // Define the actual markup for the unlock event
    ob_start();
    ?>
    <div class="database_container database_robot_container">
        <div class="subbody event event_double event_visible" style="margin: 0 !important; ">
            <h2 class="header header_left player_type player_type_<?= $player_info['player_type'] ?>" style="margin-right: 0; margin-left: 0; ">
                <?= $player_info['player_name'] ?>&#39;s Records
            </h2>
            <div class="body body_left" style="margin-left: 0; margin-right: 0; margin-bottom: 5px; padding: 2px 0; min-height: auto; font-size: 10px; min-height: 90px; ">
                <table class="full" style="margin: 5px auto -2px;">
                    <colgroup>
                            <col width="52%" />
                            <col width="1%" />
                            <col width="47%" />
                    </colgroup>
                    <tbody>
                        <tr>
                            <td  class="right">
                                <label style="display: block; float: left;">Exp Points :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_experience']) ? 'cutter' : 'none' ?>"><?= number_format($player_info['player_experience'], 0, '.', ',') ?> EXP</span>
                            </td>
                            <td class="center">&nbsp;</td>
                            <td  class="right">
                                <label style="display: block; float: left;">Unlocked Robots :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_robots_count']) ? 'cutter' : 'none' ?>"><?= $player_info['player_robots_count'].' '.($player_info['player_robots_count'] == 1 ? 'Robot' : 'Robots') ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td  class="right">
                                <label style="display: block; float: left;">Battle Points :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_points']) ? 'cutter' : 'none' ?>"><?= !empty($_SESSION[$session_token]['counters']['battle_points']) ? number_format($_SESSION[$session_token]['counters']['battle_points'], 0, '.', ',') : 0 ?> BP</span>
                            </td>
                            <td class="center">&nbsp;</td>
                            <td  class="right">
                                <label style="display: block; float: left;">Unlocked Abilities :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_abilities_count']) ? 'cutter' : 'none' ?>"><?= $player_info['player_abilities_count'].' '.($player_info['player_abilities_count'] == 1 ? 'Ability' : 'Abilities') ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td  class="right">
                                <label style="display: block; float: left;">Missions Completed :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_battles_complete']) ? 'energy' : 'none' ?>"><?= $player_info['player_battles_complete'] ?> Missions</span>
                            </td>
                            <td class="center">&nbsp;</td>
                            <td  class="right">
                                <label style="display: block; float: left;">Total Victories :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_battles_complete_total']) ? 'energy' : 'none' ?>"><?= $player_info['player_battles_complete_total'] ?> Victories</span>
                            </td>
                        </tr>
                        <tr>
                            <td  class="right">
                                <label style="display: block; float: left;">Missions Failed :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_battles_failure']) ? 'attack' : 'none' ?>"><?= $player_info['player_battles_failure'] ?> Missions</span>
                            </td>
                            <td class="center">&nbsp;</td>
                            <td  class="right">
                                <label style="display: block; float: left;">Total Defeats :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['player_battles_failure_total']) ? 'attack' : 'none' ?>"><?= $player_info['player_battles_failure_total'] ?> Defeats</span>
                            </td>
                        </tr>
                        <tr>
                            <td  class="right">
                                <label style="display: block; float: left;">Total Turns :</label>
                                <span class="player_stat player_type player_type_<?= !empty($player_info['battle_turns_player_total']) ? 'cutter' : 'none' ?>" title="<?= $player_info['battle_turns_player_total'].' of '.$player_info['battle_turns_total'].' Turns Overall' ?>"><?= $player_info['battle_turns_player_total'] == 1 ? '1 Turn' : $player_info['battle_turns_player_total'].' Turns'  ?></span>
                            </td>
                            <td class="center">&nbsp;</td>
                            <td  class="right">
                                <? if(!empty($player_info['player_field_stars'])
                                    || !empty($player_info['player_fusion_stars'])): ?>
                                    <label style="display: block; float: left;">Stars Collected :</label>
                                    <? $total_stars_collected = $player_info['player_field_stars'] + $player_info['player_fusion_stars']; ?>
                                    <span class="player_stat player_type player_type_cutter" title="<?= 'Field x'.$player_info['player_field_stars'].' | Fusion x'.$player_info['player_fusion_stars'] ?>"><?= $total_stars_collected.' '.($total_stars_collected == 1 ? 'Star' : 'Stars') ?></span>
                                <? else: ?>
                                    <label style="display: block; float: left; opacity: 0.5; filter: alpha(opacity=50); ">??? :</label>
                                    <span class="player_stat player_type player_type_empty" style=" opacity: 0.5; filter: alpha(opacity=50); ">0</span>
                                <? endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?
    $temp_player_records = ob_get_clean();

    // Generate the canvas markup with the player standing with and their team of robots
    $temp_canvas_markup = '';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/prototype-complete/battle-field_background_base.gif?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -50px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/prototype-complete/battle-field_foreground_base.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -45px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_02" style="background-image: url(images/players/'.$player_token.'/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 30px; left: '.(60 - ($player_info['player_robots_count'] * 4)).'px;"></div>';
    $temp_canvas_markup .= implode('', $temp_robot_sprite_markup);

    // Generate the console markup with the congratulations message and the records text
    $temp_user_name = !empty($_SESSION[$session_token]['USER']['displayname']) ? $_SESSION[$session_token]['USER']['displayname'] : $_SESSION[$session_token]['USER']['username'];
    $temp_user_colour = !empty($_SESSION[$session_token]['USER']['colourtoken']) ? $_SESSION[$session_token]['USER']['colourtoken'] : $player_info['player_type'];
    if (!empty($_SESSION[$session_token]['USER']['colourtoken2'])){ $temp_user_colour .= '_'.$_SESSION[$session_token]['USER']['colourtoken2']; }
    if ($player_token === 'dr-light'){ $temp_icon_code = '<span class="sprite achievement_icon achievement_dr-light-complete" style="display: inline-block; position: relative; bottom: 4px;" data-click-tooltip="Light Campaign Complete!" data-tooltip-type="player_type player_type_defense">&hearts;</span>'; }
    elseif ($player_token === 'dr-wily'){ $temp_icon_code = '<span class="sprite achievement_icon achievement_dr-wily-complete" style="display: inline-block; position: relative; bottom: 4px;" data-click-tooltip="Wily Campaign Complete!" data-tooltip-type="player_type player_type_attack">&clubs;</span>'; }
    elseif ($player_token === 'dr-cossack'){ $temp_icon_code = '<span class="sprite achievement_icon achievement_dr-cossack-complete" style="display: inline-block; position: relative; bottom: 4px;" data-click-tooltip="Cossack Campaign Complete!" data-tooltip-type="player_type player_type_speed">&diams;</span>'; }
    $temp_console_markup = '';
    $temp_console_markup .= '<p><strong class="ability_type ability_type_'.$temp_user_colour.'">Congratulations, '.$temp_user_name.'!</strong>  You\'ve completed the <strong>Mega Man RPG Prototype</strong> as <strong>'.$player_info['player_name'].'</strong> and his robots! '.rpg_battle::random_victory_quote().'! Your completion records are as follows:</p>';
    $temp_console_markup .= '<div id="console" style="width: auto; height: auto;"><div class="extra"><div class="extra2">'.preg_replace('/\s+/', ' ', $temp_player_records).'</div></div></div>';
    $temp_console_markup .= '<p>A special <strong class="ability_type ability_type_'.$player_info['player_type'].'">'.$temp_icon_code.'</strong> icon has been added to your profile to commemorate the event!</p>';

    // Return the generated canvas and console markup for the player
    return array(
        'canvas_markup' => $temp_canvas_markup,
        'console_markup' => $temp_console_markup,
        'player_token' => $player_token,
        'event_type' => 'prototype-complete'
        );

}

// Define a function for generating a common "Post-Game Overview" message w/ details
function generate_prototype_postgame_message($player_token){
    global $session_token;

    global $mmrpg_index_players, $mmrpg_index_robots;
    if (empty($mmrpg_index_players)){ $mmrpg_index_players = rpg_player::get_index(true); }
    if (empty($mmrpg_index_robots)){ $mmrpg_index_robots = rpg_robot::get_index(true); }

    // Collect index details on this particular player
    $player_info = $mmrpg_index_players[$player_token];
    $player_type = $player_info['player_type'];
    if ($player_token === 'dr-light'){ $partner_robots = array('mega-man', 'roll'); $star_types = array('water', 'flame', 'time'); }
    elseif ($player_token === 'dr-wily'){ $partner_robots = array('bass', 'disco'); $star_types = array('flame', 'time', 'water'); }
    elseif ($player_token === 'dr-cossack'){ $partner_robots = array('proto-man', 'rhythm'); $star_types = array('time', 'water', 'flame'); }

    // Generate the console markup with the congratulations message and the records text
    $temp_canvas_markup = '<div class="sprite sprite_80x80" style="background-image: url(images/fields/final-destination-3/battle-field_background_base.gif?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -32px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto; opacity: 0.2; filter: alpha(opacity=20); "></div>';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/final-destination-3/battle-field_foreground_base.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -45px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';

    $temp_print_star = function($type1, $type2, $x, $y, $brightness = false, $opacity = false) use($player_info){
        $filters = '';
        if ($brightness !== false){ $filters .= '-webkit-filter: brightness('.$brightness.'%); filter: brightness('.$brightness.'%); '; }
        if ($opacity !== false){ $filters .= 'opacity: '.$opacity.'; '; }
        return '<div class="sprite sprite_80x80 sprite_80x80_01" style="background-image: url(images/items/fusion-star_'.$type2.'/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); left: '.$x.'px; bottom: '.$y.'px; '.$filters.'"></div>'.
            '<div class="sprite sprite_80x80 sprite_80x80_02" style="background-image: url(images/items/fusion-star_'.$type1.'/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); left: '.$x.'px; bottom: '.$y.'px; '.$filters.'"></div>';
        };
    $temp_canvas_center = 248;
    $temp_canvas_markup .= $temp_print_star($star_types[2], $star_types[0], ($temp_canvas_center - 140), 68, 80, 0.8); // sub2
    $temp_canvas_markup .= $temp_print_star($star_types[1], $star_types[0], ($temp_canvas_center + 140), 68, 80, 0.8); // sub1
    $temp_canvas_markup .= $temp_print_star($star_types[0], $star_types[0], ($temp_canvas_center),       74, 90, 0.9); // main

    $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_01" style="background-image: url(images/robots/'.$partner_robots[0].'/sprite_left_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 30px; left: '.($temp_canvas_center - 80).'px;"></div>';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_01" style="background-image: url(images/robots/'.$partner_robots[1].'/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 30px; left: '.($temp_canvas_center + 80).'px;"></div>';

    $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_01" style="background-image: url(images/players/'.$player_token.'/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 15px; left: '.$temp_canvas_center.'px;"></div>';

    // Generate the markup for bonus chapters unlocks
    $temp_bonus_chapter_markup = array();
    $temp_bonus_chapter_markup[] = '<p>The <strong class="ability_type ability_type_speed">Mission Randomizer</strong> chapter lets you to refight past foes in unlikey pairings. These missions are perfect for trying out new strategies or grinding for cores!</p>';
    if (mmrpg_prototype_item_unlocked('light-program')){ $temp_bonus_chapter_markup[] = '<p>The <strong class="ability_type ability_type_attack">Player Battles</strong> chapter contains missions against the ghost-data of other users from the leaderboard. These missions are great for grinding lots of zenny!</p>'; }
    if (mmrpg_prototype_item_unlocked('cossack-program')){ $temp_bonus_chapter_markup[] = '<p>The <strong class="ability_type ability_type_defense">Star Fields</strong> chapter locates and displays any Field Star or Fusion Star missions that you\'ve yet to complete.  Collecting stars helps your robots grow stronger!</p>'; }
    if (mmrpg_prototype_item_unlocked('wily-program')){ $temp_bonus_chapter_markup[] = '<p>The <strong class="ability_type ability_type_energy">Challenge Mode</strong> chapter offers a collection of unique missions designed by the MMRPG staff. These are hard but have great rewards! <span style="font-size: 70%;position: relative;bottom: 4px;left: 2px;">(Try the Endless Attack Mode!)</span></p>'; }

    // Generate the canvas markup with the player standing with and their team of robots
    $temp_console_markup = '';
    $temp_console_markup .= '<p>';
        $temp_console_markup .= '<strong>'.$player_info['player_name'].'</strong>\'s story has come to an end, but there\'s still more to do and discover!<br /> ';
        $temp_console_markup .= 'As thanks for playing, <strong>'.count($temp_bonus_chapter_markup).' new bonus chapters</strong> have been unlocked in his campaign! ';
    $temp_console_markup .= '</p>';
    $temp_console_markup .= '<div style="padding: 10px; margin: 5px auto; border-top: 1px solid #212121; border-bottom: 1px solid #090909;">';
        $temp_console_markup .= implode('', $temp_bonus_chapter_markup);
    $temp_console_markup .= '</div>';
    $temp_console_markup .= '<p>';
        $temp_console_markup .= 'We hope you enjoyed the <strong class="ability_type ability_type_shield">Mega Man RPG Prototype</strong> and we encourage you to join our ever-growing ';
        $temp_console_markup .= '<a href="'. MMRPG_CONFIG_ROOTURL .'community/" target="_blank" style="font-weight: normal; text-decoration: underline;">community</a> of fans! ';
        $temp_console_markup .= 'Please leave feedback if you can, and thanks again!';
    $temp_console_markup .= '</p>';

    // Return the generated canvas and console markup for the player
    return array(
        'canvas_markup' => $temp_canvas_markup,
        'console_markup' => $temp_console_markup,
        'player_token' => $player_token,
        'event_type' => 'prototype-postgame'
        );

}


/*
 * DR. LIGHT UNLOCKS
 */

/*

// DISABLING THIS UNTIL WE HAVE A REAL STORY

// UNLOCK EVENT : PHASE ONE ROBOTS (LIGHT)

// Once the player has completed the first battle, display some story to them
if ($battle_complete_counter_light >= 1 && $battle_complete_counter_light < 2){

    // Create the event flag and unset the player select variable to force main menu
    $temp_event_flag = 'dr-light-event-00_phase-zero-complete';
    if (empty($temp_game_flags['events'][$temp_event_flag])){

        $intro_field = rpg_player::get_intro_field('dr-light');
        $temp_game_flags['events'][$temp_event_flag] = true;
        $temp_canvas_markup = '<div class="sprite sprite_80x80" style="background-image: url(images/fields/'.$intro_field.'/battle-field_background_base.gif?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -50px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;">Intro Field</div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/'.$intro_field.'/battle-field_foreground_base.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -45px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;">Intro Field</div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_02" style="background-image: url(images/players/dr-light/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 40px; left: 180px;">Dr. Light</div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_defend" style="background-image: url(images/robots/mega-man/sprite_left_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 40px; right: 180px;">Mega Man</div>';
        $temp_console_markup = '<p>Mega Man! Thank you for saving us! I&#39;m not sure how it happened, but it looks like we&#39;ve been digitized and transported <em>inside</em> of the prototype! That Met was made of pure data, and it looks like we are now too&hellip;</p>';
        $temp_console_markup .= '<p>We have to find Dr. Cossack and make our way back to the real world, but I&#39;m afraid it won\'t be easy. Sensors detect a high concentration of enemy robot data active on this server, and we\'ll need to clear them out before we can continue on our mission.</p>';
        array_push($_SESSION[$session_token]['EVENTS'], array(
            'canvas_markup' => $temp_canvas_markup,
            'console_markup' => $temp_console_markup,
            'player_token' => 'dr-light',
            'event_type' => 'other'
            ));

        //$temp_game_flags['events'][$temp_event_flag] = true;
        //$_SESSION[$session_token]['battle_settings']['this_player_token'] = false;
    }

}

*/


// UNLOCK ROBOT : ROLL

// If the player has failured at least one battle, unlock Roll as a playable character
if ($battle_failure_counter_light >= 1 && !mmrpg_prototype_robot_unlocked(false, 'roll')){

    // Unlock Roll as a playable character
    $unlock_player_info = $mmrpg_index_players['dr-light'];
    $unlock_robot_info = rpg_robot::get_index_info('roll');
    $unlock_robot_info['robot_level'] = MMRPG_SETTINGS_GAMESTORY1_STARTLEVEL;
    $unlock_robot_info['robot_experience'] = 999;
    mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, true);

}

// UNLOCK EVENT : PHASE TWO CHAPTERS (WILY)

// If Dr. Light has completed all of his second phase, open Dr. Wily's second
if ($battle_complete_counter_light >= MMRPG_SETTINGS_CHAPTER5_MISSIONCOUNT){

    // Create the event flag and unset the player select variable to force main menu
    $temp_event_flag = 'dr-light-event-97_phase-one-complete';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;
        $_SESSION[$session_token]['battle_settings']['this_player_token'] = false;
    }

}

// UNLOCK EVENT : PROTOTYPE COMPLETE (LIGHT)

// If the player has completed the entire prototype campaign, display window event
if ($battle_complete_counter_light >= MMRPG_SETTINGS_CHAPTER5_MISSIONCOUNT){

    // Display the prototype complete message, showing the doctor and their unlocked robots
    $temp_event_flag = 'dr-light-event-99_prototype-complete-new';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;

        // Generate the prototype complete message and append it
        $event_markup = generate_prototype_complete_message('dr-light');
        array_push($_SESSION[$session_token]['EVENTS'], $event_markup);

        // Display the prototype postgame message, showing the doctor and hero + support bots
        $temp_event_flag = 'dr-light-event-99_prototype-postgame-new';
        if (empty($temp_game_flags['events'][$temp_event_flag])){
            $temp_game_flags['events'][$temp_event_flag] = true;

            // Generate the prototype complete message and append it
            $event_markup = generate_prototype_postgame_message('dr-light');
            array_push($_SESSION[$session_token]['EVENTS'], $event_markup);

        }

    }

}



/*
 * DR. WILY OPTIONS
 */

// UNLOCK PLAYER : DR. WILY

// If Dr. Light has completed phase1 of his battles, unlock Dr. Wily
if (!$unlock_flag_wily && mmrpg_prototype_complete('dr-light')){

    // Unlock Dr. Wily as a playable character
    $unlock_player_info = $mmrpg_index_players['dr-wily'];
    mmrpg_game_unlock_player($unlock_player_info, false, true);
    $_SESSION[$session_token]['values']['battle_rewards']['dr-wily']['player_points'] = 0;

    // Ensure Bass hasn't already been unlocked by the player
    if (!mmrpg_prototype_robot_unlocked(false, 'bass')){
        // Unlock Bass as a playable character
        $unlock_robot_info = rpg_robot::get_index_info('bass');
        $unlock_robot_info['robot_level'] = MMRPG_SETTINGS_GAMESTORY2_STARTLEVEL;
        $unlock_robot_info['robot_experience'] = 999;
        //$unlock_robot_info['robot_experience'] = 4000;
        mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, false);
        //$_SESSION[$session_token]['values']['battle_rewards']['dr-wily']['player_robots']['bass']['robot_experience'] = 4000;
    }
    // If Bass has already been unlocked by another doctor, reassign it to Wily's team
    elseif (mmrpg_prototype_robot_unlocked(false, 'bass') &&
        !mmrpg_prototype_robot_unlocked('dr-wily', 'bass')){
        // Loop through the player rewards and collect Bass' info
        foreach ($_SESSION[$session_token]['values']['battle_rewards'] AS $temp_player => $temp_playerinfo){
            if ($temp_player == 'dr-wily'){ continue; }
            foreach ($temp_playerinfo['player_robots'] AS $temp_robot => $temp_robotinfo){
                if ($temp_robot != 'bass'){ continue; }
                // Bass was found, so collect the rewards and settings
                $temp_robotinfo_rewards = $_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'][$temp_robot];
                $temp_robotinfo_settings = $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'][$temp_robot];
                // Assign Bass's rewards and settings to Dr. Wily's player array
                $_SESSION[$session_token]['values']['battle_rewards']['dr-wily']['player_robots'][$temp_robot] = $temp_robotinfo_rewards;
                $_SESSION[$session_token]['values']['battle_settings']['dr-wily']['player_robots'][$temp_robot] = $temp_robotinfo_settings;
                // Unset the original Bass data from this player's session
                unset($_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'][$temp_robot]);
                unset($_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'][$temp_robot]);
                // Break now that we're done
                break;
            }
        }
    }

    // Redirect back to this page to recalculate menus
    $unlock_flag_wily = true;
    unset($_SESSION[$session_token]['battle_settings']['this_player_token']);
    header('Location: prototype.php?wap='.($flag_wap ? 'true' : 'false'));
    exit();

}


// UNLOCK ROBOT : DISCO

// If the player has failed at least two battles, unlock Disco as a playable character
if ($battle_failure_counter_wily >= 2 && !mmrpg_prototype_robot_unlocked(false, 'disco')){

    // Unlock Disco as a playable character
    $unlock_player_info = $mmrpg_index_players['dr-wily'];
    $unlock_robot_info = rpg_robot::get_index_info('disco');
    $unlock_robot_info['robot_level'] = MMRPG_SETTINGS_GAMESTORY2_STARTLEVEL;
    $unlock_robot_info['robot_experience'] = 999;
    mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, true);

}

// UNLOCK EVENT : PHASE THREE CHAPTERS (COSSACK)

// If Dr. Wily has completed all of his second phase, open Dr. Cossack's third
if ($battle_complete_counter_wily >= MMRPG_SETTINGS_CHAPTER4_MISSIONCOUNT){

    // Create the event flag and unset the player select variable to force main menu
    $temp_event_flag = 'dr-wily-event-97_phase-one-complete';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;
        $_SESSION[$session_token]['battle_settings']['this_player_token'] = false;
    }

}

// UNLOCK EVENT : PROTOTYPE COMPLETE (WILY)

// If the player completed the first battle and leveled up, display window event
if ($battle_complete_counter_wily >= MMRPG_SETTINGS_CHAPTER5_MISSIONCOUNT){

    // Display the prototype complete message, showing the doctor and their unlocked robots
    $temp_event_flag = 'dr-wily-event-99_prototype-complete-new';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;

        // Generate the prototype complete message and append it
        $event_markup = generate_prototype_complete_message('dr-wily');
        array_push($_SESSION[$session_token]['EVENTS'], $event_markup);

        // Display the prototype postgame message, showing the doctor and hero + support bots
        $temp_event_flag = 'dr-wily-event-99_prototype-postgame-new';
        if (empty($temp_game_flags['events'][$temp_event_flag])){
            $temp_game_flags['events'][$temp_event_flag] = true;

            // Generate the prototype complete message and append it
            $event_markup = generate_prototype_postgame_message('dr-wily');
            array_push($_SESSION[$session_token]['EVENTS'], $event_markup);

        }

    }

}


/*
 * DR. COSSACK OPTIONS
 */

// UNLOCK PLAYER : DR. COSSACK

// If Dr. Light has completed phase1 of his battles, unlock Dr. Cossack
if (!$unlock_flag_cossack && mmrpg_prototype_complete('dr-wily')){

    // Unlock Dr. Cossack as a playable character
    $unlock_player_info = $mmrpg_index_players['dr-cossack'];
    mmrpg_game_unlock_player($unlock_player_info, false, true);
    $_SESSION[$session_token]['values']['battle_rewards']['dr-cossack']['player_points'] = 0;

    // Ensure Proto Man hasn't already been unlocked by the player
    if (!mmrpg_prototype_robot_unlocked(false, 'proto-man')){
        // Unlock Proto Man as a playable character
        $unlock_robot_info = rpg_robot::get_index_info('proto-man');
        $unlock_robot_info['robot_level'] = MMRPG_SETTINGS_GAMESTORY3_STARTLEVEL;
        $unlock_robot_info['robot_experience'] = 999;
        //$unlock_robot_info['robot_experience'] = 4000;
        mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, false);
        //$_SESSION[$session_token]['values']['battle_rewards']['dr-cossack']['player_robots']['proto-man']['robot_experience'] = 4000;
    }
    // If Proto Man has already been unlocked by another doctor, reassign it to Cossack's team
    elseif (mmrpg_prototype_robot_unlocked(false, 'proto-man') &&
        !mmrpg_prototype_robot_unlocked('dr-cossack', 'proto-man')){
        // Loop through the player rewards and collect Proto Man' info
        foreach ($_SESSION[$session_token]['values']['battle_rewards'] AS $temp_player => $temp_playerinfo){
            if ($temp_player == 'dr-cossack'){ continue; }
            foreach ($temp_playerinfo['player_robots'] AS $temp_robot => $temp_robotinfo){
                if ($temp_robot != 'proto-man'){ continue; }
                // Proto Man was found, so collect the rewards and settings
                $temp_robotinfo_rewards = $_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'][$temp_robot];
                $temp_robotinfo_settings = $_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'][$temp_robot];
                // Assign Proto Man's rewards and settings to Dr. Cossack's player array
                $_SESSION[$session_token]['values']['battle_rewards']['dr-cossack']['player_robots'][$temp_robot] = $temp_robotinfo_rewards;
                $_SESSION[$session_token]['values']['battle_settings']['dr-cossack']['player_robots'][$temp_robot] = $temp_robotinfo_settings;
                // Unset the original Proto Man data from this player's session
                unset($_SESSION[$session_token]['values']['battle_rewards'][$temp_player]['player_robots'][$temp_robot]);
                unset($_SESSION[$session_token]['values']['battle_settings'][$temp_player]['player_robots'][$temp_robot]);
                // Break now that we're done
                break;
            }
        }
    }

    // Redirect back to this page to recalculate menus
    $unlock_flag_cossack = true;
    unset($_SESSION[$session_token]['battle_settings']['this_player_token']);
    header('Location: prototype.php?wap='.($flag_wap ? 'true' : 'false'));
    exit();

}

// UNLOCK ROBOT : RHYTHM

// If the player has failed at least three battles, unlock Rhythm as a playable character
if ($battle_failure_counter_cossack >= 3 && !mmrpg_prototype_robot_unlocked(false, 'rhythm')){

    // Unlock Rhythm as a playable character
    $unlock_player_info = $mmrpg_index_players['dr-cossack'];
    $unlock_robot_info = rpg_robot::get_index_info('rhythm');
    $unlock_robot_info['robot_level'] = MMRPG_SETTINGS_GAMESTORY3_STARTLEVEL;
    $unlock_robot_info['robot_experience'] = 999;
    mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, true);

}

// UNLOCK EVENT : PHASE TWO CHAPTERS (LIGHT)

// If Dr. Cossack has completed all of his first phase, open Dr. Light's second
if ($battle_complete_counter_cossack >= MMRPG_SETTINGS_CHAPTER3_MISSIONCOUNT){

    // Create the event flag and unset the player select variable to force main menu
    $temp_event_flag = 'dr-cossack-event-97_phase-one-complete';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;
        $_SESSION[$session_token]['battle_settings']['this_player_token'] = false;
    }

}

// UNLOCK EVENT : PHASE THREE CHAPTERS (ALL)

// If Dr. Cossack has completed all of his second phase, open all other third
if ($battle_complete_counter_cossack >= MMRPG_SETTINGS_CHAPTER4_MISSIONCOUNT){

    // Create the event flag and unset the player select variable to force main menu
    $temp_event_flag = 'dr-cossack-event-97_phase-two-complete';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;
        $_SESSION[$session_token]['battle_settings']['this_player_token'] = false;
    }

}

// UNLOCK EVENT : PROTOTYPE COMPLETE (COSSACK)

// If the player completed the first battle and leveled up, display window event
if ($battle_complete_counter_cossack >= MMRPG_SETTINGS_CHAPTER5_MISSIONCOUNT){

    // Display the prototype complete message, showing the doctor and their unlocked robots
    $temp_event_flag = 'dr-cossack-event-99_prototype-complete-new';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;

        // Generate the prototype complete message and append it
        $event_markup = generate_prototype_complete_message('dr-cossack');
        array_push($_SESSION[$session_token]['EVENTS'], $event_markup);

        // Display the prototype postgame message, showing the doctor and hero + support bots
        $temp_event_flag = 'dr-cossack-event-99_prototype-postgame-new';
        if (empty($temp_game_flags['events'][$temp_event_flag])){
            $temp_game_flags['events'][$temp_event_flag] = true;

            // Generate the prototype complete message and append it
            $event_markup = generate_prototype_postgame_message('dr-cossack');
            array_push($_SESSION[$session_token]['EVENTS'], $event_markup);

        }

    }

}


/*
 * DR. LIGHT EVENT ITEMS
 */

// Unlock the AUTO LINK after Dr. Light has completed all of Chapter One
if (!mmrpg_prototype_item_unlocked('auto-link')
    && $chapters_unlocked_light['1']
    ){

    // Unlock the Auto Link and generate the required event details
    mmrpg_game_unlock_item('auto-link', array(
        'event_text' => '{player} made contact! <br /> The {item} has been established!',
        'player_token' => 'dr-light',
        'shop_token' => 'auto',
        'show_images' => array('player', 'shop'),
        'field_background' => 'light-laboratory',
        'field_foreground' => 'light-laboratory'
        ));

    // Make sure Auto pops up in the ready-room with the rest of his family
    rpg_prototype::mark_robot_as_pending_entrance_animation('auto');

}
// Unlock the ITEM CODES immediately after the Auto Link has been unlocked
if (mmrpg_prototype_item_unlocked('auto-link')
    && !mmrpg_prototype_item_unlocked('item-codes')){

    // Unlock the Item Codes and generate the required event details
    mmrpg_game_unlock_item('item-codes', array(
        'event_text' => '', // no popup for this one
        'player_token' => 'dr-light',
        'shop_token' => 'auto',
        'show_images' => array('shop'),
        'field_background' => 'light-laboratory',
        'field_foreground' => 'light-laboratory'
        ));

}
// Unlock the EQUIP CODES after Dr. Light has completed at least half of Chapter Two
$required_missions = MMRPG_SETTINGS_CHAPTER1_MISSIONS;
$required_missions += round((MMRPG_SETTINGS_CHAPTER2_MISSIONS - 1) / 2);
if (!mmrpg_prototype_item_unlocked('equip-codes')
    && mmrpg_prototype_battles_complete('dr-light') >= $required_missions
    ){

    // Unlock the Equip Codes and generate the required event details
    mmrpg_game_unlock_item('equip-codes', array(
        'event_text' => '{shop} made a discovery! <br /> The {item} have been unlocked!',
        'player_token' => 'dr-light',
        'shop_token' => 'auto',
        'show_images' => array('shop'),
        'field_background' => 'light-laboratory',
        'field_foreground' => 'light-laboratory'
        ));

}

// Unlock the LIGHT/SHARE PROGRAM after Dr. Light has finished their campaign (when we unlock Dr. Wily)
if (mmrpg_prototype_complete('dr-light')
    && !mmrpg_prototype_item_unlocked('light-program')
    ){

    // Unlock the Light Program and generate the required event details
    mmrpg_game_unlock_item('light-program', array(
        'event_text' => '{player} discovered how to share! <br /> The {item} has been activated!',
        'player_token' => 'dr-light',
        'show_images' => array('player'),
        'field_background' => 'light-laboratory',
        'field_foreground' => 'light-laboratory'
        ));

}


/*
 * DR. WILY EVENT ITEMS
 */

// Unlock the REGGAE LINK after Dr. Wily has completed all of Chapter One
if (!mmrpg_prototype_item_unlocked('reggae-link')
    && $chapters_unlocked_wily['1']
    ){

    // Unlock the Reggae Link and generate the required event details
    mmrpg_game_unlock_item('reggae-link', array(
        'event_text' => '{player} made contact! <br /> The {item} has been established!',
        'player_token' => 'dr-wily',
        'shop_token' => 'reggae',
        'show_images' => array('player', 'shop'),
        'field_background' => 'wily-castle',
        'field_foreground' => 'wily-castle'
        ));

    // Make sure Reggae pops up in the ready-room with the rest of his family
    rpg_prototype::mark_robot_as_pending_entrance_animation('reggae');

}
// Unlock the ABILITY CODES immediately after the Reggae Link has been unlocked
if (mmrpg_prototype_item_unlocked('reggae-link')
    && !mmrpg_prototype_item_unlocked('ability-codes')){

    // Unlock the Ability Codes and generate the required event details
    mmrpg_game_unlock_item('ability-codes', array(
        'event_text' => '', // no popup for this one
        'player_token' => 'dr-wily',
        'shop_token' => 'reggae',
        'show_images' => array('shop'),
        'field_background' => 'wily-castle',
        'field_foreground' => 'wily-castle'
        ));

}
// Unlock the WEAPON CODES after Dr. Wily has completed at least half of Chapter Two
$required_missions = MMRPG_SETTINGS_CHAPTER1_MISSIONS;
$required_missions += round((MMRPG_SETTINGS_CHAPTER2_MISSIONS - 1) / 2);
if (!mmrpg_prototype_item_unlocked('weapon-codes')
    && mmrpg_prototype_battles_complete('dr-wily') >= $required_missions
    ){

    // Unlock the Weapon Codes and generate the required event details
    mmrpg_game_unlock_item('weapon-codes', array(
        'event_text' => '{shop} made a discovery! <br /> The {item} have been unlocked!',
        'player_token' => 'dr-wily',
        'shop_token' => 'reggae',
        'show_images' => array('shop'),
        'field_background' => 'wily-castle',
        'field_foreground' => 'wily-castle'
        ));

}

// Unlock the WILY/TRANSFER PROGRAM after Dr. Wily has finished their campaign (when we unlock Dr. Cossack)
if (mmrpg_prototype_complete('dr-wily')
    && !mmrpg_prototype_item_unlocked('wily-program')
    ){

    // Unlock the Wily Program and generate the required event details
    mmrpg_game_unlock_item('wily-program', array(
        'event_text' => '{player} discovered how to transfer! <br /> The {item} has been activated!',
        'player_token' => 'dr-wily',
        'show_images' => array('player'),
        'field_background' => 'wily-castle',
        'field_foreground' => 'wily-castle'
        ));

}


/*
 * DR. COSSACK EVENT ITEMS
 */

// Unlock the KALINKA LINK after Dr. Cossack has completed at least half of Chapter Two
if (!mmrpg_prototype_item_unlocked('kalinka-link')
    && $chapters_unlocked_cossack['1']
    ){

    // Unlock the Kalinka Link and generate the required event details
    mmrpg_game_unlock_item('kalinka-link', array(
        'event_text' => '{player} made contact! <br /> The {item} has been established!',
        'player_token' => 'dr-cossack',
        'shop_token' => 'kalinka',
        'show_images' => array('player', 'shop'),
        'field_background' => 'cossack-citadel',
        'field_foreground' => 'cossack-citadel'
        ));

    // Make sure Kalinka pops up in the ready-room with the rest of his family
    rpg_prototype::mark_player_as_pending_entrance_animation('kalinka');

}
// Unlock the MASTER CODES immediately after the Kalinka Link has been unlocked
if (mmrpg_prototype_item_unlocked('kalinka-link')
    && !mmrpg_prototype_item_unlocked('master-codes')){

    // Unlock the Master Codes and generate the required event details
    mmrpg_game_unlock_item('master-codes', array(
        'event_text' => '', // no popup for this one
        'player_token' => 'dr-cossack',
        'shop_token' => 'kalinka',
        'show_images' => array('shop'),
        'field_background' => 'cossack-citadel',
        'field_foreground' => 'cossack-citadel'
        ));

}
// Unlock the DRESS CODES after Dr. Cossack has completed at least half of Chapter Two
$required_missions = MMRPG_SETTINGS_CHAPTER1_MISSIONS;
$required_missions += round((MMRPG_SETTINGS_CHAPTER2_MISSIONS - 1) / 2);
if (!mmrpg_prototype_item_unlocked('dress-codes')
    && mmrpg_prototype_battles_complete('dr-cossack') >= $required_missions
    ){

    // Unlock the Legacy Codes and generate the required event details
    mmrpg_game_unlock_item('dress-codes', array(
        'event_text' => '{shop} made a discovery! <br /> The {item} have been unlocked!',
        'player_token' => 'dr-cossack',
        'shop_token' => 'kalinka',
        'show_images' => array('shop'),
        'field_background' => 'cossack-citadel',
        'field_foreground' => 'cossack-citadel'
        ));

}

// Unlock the COSSACK/SEARCH PROGRAM after Dr. Cossack has finished their campaign
if (mmrpg_prototype_complete('dr-cossack')
    && !mmrpg_prototype_item_unlocked('cossack-program')
    ){

    // Unlock the Cossack Program and generate the required event details
    mmrpg_game_unlock_item('cossack-program', array(
        'event_text' => '{player} discovered how to search! <br /> The {item} has been activated!',
        'player_token' => 'dr-cossack',
        'show_images' => array('player'),
        'field_background' => 'cossack-citadel',
        'field_foreground' => 'cossack-citadel'
        ));

}


/*
 * MULTI DR. EVENT ITEMS
 */

// Define the index of chapter-relavant players and their chapter details
$chapter_unlock_players = array('dr-light', 'dr-wily', 'dr-cossack');
$chapter_unlock_players_config = array(
    'dr-light' => array(
        'welcome' => 'Welcome to the Prototype!',
        'letsgo_robot' => array('token' => 'mega-man', 'name' => 'Mega Man'),
        'letsgo_template' => 'Let\'s go {ROBOT}!<br /> I\'m right here beside you!'
        ),
    'dr-wily' => array(
        'welcome' => 'Rivals of the Prototype!',
        'letsgo_robot' => array('token' => 'bass', 'name' => 'Bass'),
        'letsgo_template' => 'It\'s time, {ROBOT}!<br /> Show them your power!'
        ),
    'dr-cossack' => array(
        'welcome' => 'Hacking the Prototype!',
        'letsgo_robot' => array('token' => 'proto-man', 'name' => 'Proto Man'),
        'letsgo_template' => 'Alright {ROBOT}!<br /> There\'s work to be done!'
        )
    );
$chapter_unlock_popup_index = array();

// Unlock the LIGHT BUSTER / WILY BUSTER / COSSACK BUSTER if the player has unlocked at least three robots (hero + support + robot master)
foreach ($chapter_unlock_players AS $player_token){
    // If the player has a doctor unlocked without also having their buster, unlock it now
    $buster_token = str_replace('dr-', '', $player_token).'-buster';
    if (mmrpg_prototype_player_unlocked($player_token)
        && !mmrpg_prototype_ability_unlocked($player_token, '', $buster_token)
        && mmrpg_prototype_robots_unlocked($player_token) >= 3){
        //error_log('unlocking '.$buster_token.' for '.$player_token.' apparently ');
        $unlock_player_info = $mmrpg_index_players[$player_token];
        mmrpg_game_unlock_ability($unlock_player_info, '', array('ability_token' => $buster_token), true);
    }
}

// Main-Game Chapters
$chapter_unlock_popup_index[] = array('chapter_key' => '0', 'chapter_token' => 'chapter-1', 'chapter_name' => 'Chapter 1', 'chapter_subname' => 'Chapter One : An Unexpected Attack');
$chapter_unlock_popup_index[] = array('chapter_key' => '1', 'chapter_token' => 'chapter-2', 'chapter_name' => 'Chapter 2', 'chapter_subname' => 'Chapter Two : Robot Master Revival');
$chapter_unlock_popup_index[] = array('chapter_key' => '2', 'chapter_token' => 'chapter-3', 'chapter_name' => 'Chapter 3', 'chapter_subname' => 'Chapter Three : The Rival Challengers');
$chapter_unlock_popup_index[] = array('chapter_key' => '3', 'chapter_token' => 'chapter-4', 'chapter_name' => 'Chapter 4', 'chapter_subname' => 'Chapter Four : Battle Field Fusions');
$chapter_unlock_popup_index[] = array('chapter_key' => '4a', 'chapter_token' => 'chapter-5', 'chapter_name' => 'Chapter 5', 'chapter_subname' => 'Chapter Five : The Final Battles', 'chapter_is_endgame' => true);

// Post-Game Chapters
$chapter_unlock_popup_index[] = array('chapter_key' => '6', 'chapter_token' => 'chapter-random', 'chapter_name' => 'Random', 'chapter_subname' => 'Bonus Chapter : Mission Randomizer', 'chapter_is_bonus' => true);
$chapter_unlock_popup_index[] = array('chapter_key' => '7', 'chapter_token' => 'chapter-stars', 'chapter_name' => 'Stars', 'chapter_subname' => 'Bonus Chapter : Star Fields', 'chapter_is_bonus' => true);
$chapter_unlock_popup_index[] = array('chapter_key' => '5', 'chapter_token' => 'chapter-players', 'chapter_name' => 'Players', 'chapter_subname' => 'Bonus Chapter : Player Battles', 'chapter_is_bonus' => true);
$chapter_unlock_popup_index[] = array('chapter_key' => '8', 'chapter_token' => 'chapter-challenges', 'chapter_name' => 'Challenges', 'chapter_subname' => 'Bonus Chapter : Challenge Mode', 'chapter_is_bonus' => true);

// Loop through each unlocked player and get ready to process their chapters
foreach ($chapter_unlock_players AS $player_key => $player_token){
    if (!mmrpg_prototype_player_unlocked($player_token)){ continue; } // continue if player not unlocked yet

    // Now loop through and display chapter unlock messages where relevant
    foreach ($chapter_unlock_popup_index AS $key => $chapter_info){
        $chapter_key = $chapter_info['chapter_key'];
        $chapter_token = $chapter_info['chapter_token'];
        $chapter_name = $chapter_info['chapter_name'];
        $chapter_subname = $chapter_info['chapter_subname'];
        $chapter_is_intro = $chapter_key === '0' ? true : false;
        $chapter_is_endgame = !empty($chapter_info['chapter_is_endgame']) ? true : false;
        $chapter_is_bonus = !empty($chapter_info['chapter_is_bonus']) ? true : false;
        $chapter_unlock_text = $chapter_is_intro ? 'starts' : 'unlocked';
        $next_chapter_info = isset($chapter_unlock_popup_index[$key + 1]) ? $chapter_unlock_popup_index[$key + 1] : false;
        $next_chapter_key = isset($next_chapter_info['chapter_key']) ? $next_chapter_info['chapter_key'] : false;
        if (!$chapters_unlocked_index[$player_token][$chapter_key]){ continue; } // continue if chapter not unlocked yet
        $temp_event_flag = $player_token.'_'.$chapter_token.'-unlocked';
        if (empty($temp_game_flags['events'][$temp_event_flag])){
            $temp_game_flags['events'][$temp_event_flag] = true;
            if (!$chapter_is_bonus && $next_chapter_key !== false && $chapters_unlocked_index[$player_token][$next_chapter_key]){ continue; } // continue if already unlocked next and not bonus
            elseif ($chapter_is_endgame && mmrpg_prototype_complete($player_token)){ continue; } // continue if final chapter but player has already completed prototype
            $chapter_unlock_config = $chapter_unlock_players_config[$player_token];
            $player_info = $mmrpg_index_players[$player_token];
            $player_name = $player_info['player_name'];
            $player_type = $player_info['player_type'];
            $banner_image_path = 'images/events/event-banner_'.$chapter_token.'-unlocked_'.$player_token.'.png?'.MMRPG_CONFIG_CACHE_DATE;
            $headline_text = $chapter_is_intro ? $chapter_unlock_config['welcome'] : 'Congratulations!';
            $temp_canvas_markup = '';
            $temp_canvas_markup .= '<div class="sprite event_banner sprite_80x80 smooth-scaling" style="background-image: url('.$banner_image_path.'); background-size: cover; background-position: center top; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
            $temp_console_markup = '';
            $temp_console_markup .= '<p class="headline ability_type ability_type_'.$player_type.'"><strong>'.$headline_text.'</strong></p>';
            $temp_console_markup .= '<div class="inset_panel">';
                $temp_console_markup .= '<p class="bigtext centertext"><strong class="ability_type ability_type_'.$player_type.'">'.$player_name.'</strong> '.$chapter_unlock_text.' a new chapter!</p>';
                $temp_console_markup .= '<p class="bigtext centertext">&quot;<strong>'.$chapter_subname.'</strong>&quot;'.(!$chapter_is_intro ? '<br /> is now available!' : '').'</p>';
            $temp_console_markup .= '</div>';
            if (!$chapter_is_bonus){
                if (!$chapter_is_intro){
                    $temp_limit_hearts = mmrpg_prototype_limit_hearts_earned($player_token);
                    $temp_limit_hearts_icons = trim(str_repeat('<i class="fa fa-heart"></i> ', $temp_limit_hearts));
                    $temp_console_markup .= '<div class="inset_panel compact">';
                        $temp_console_markup .= '<p class="smalltext centertext">The doctor also earned a '.rpg_type::print_span('copy', '<i class="fa fas fa-heart"></i> Limit Heart').' for his progress!</p>';
                        $temp_console_markup .= '<p class="smalltext centertext">He feels strong enough to bring <strong>'.$temp_limit_hearts.' robots '.$temp_limit_hearts_icons.'</strong> into battle now!</p>';
                    $temp_console_markup .= '</div>';
                } else {
                    $letsgo_template_text = $chapter_unlock_config['letsgo_template'];
                    $letsgo_robot_token = $chapter_unlock_config['letsgo_robot']['token'];
                    $letsgo_robot_name = $chapter_unlock_config['letsgo_robot']['name'];
                    $letsgo_text = str_replace('{ROBOT}', ' '.rpg_type::print_span('copy', $letsgo_robot_name).' ', $letsgo_template_text);
                    $temp_console_markup .= '<div class="inset_panel compact">';
                        $temp_console_markup .= '<span class="sprite sprite_40x40 sprite_40x40_taunt float_left" style="background-image: url(images/players/'.$player_token.'/sprite_right_40x40.png?'.MMRPG_CONFIG_CACHE_DATE.');"></span>';
                        $temp_console_markup .= '<p class="smalltext centertext">'.$letsgo_text.'</p>';
                        $temp_console_markup .= '<span class="sprite sprite_40x40 sprite_40x40_defend float_right" style="background-image: url(images/robots/'.$letsgo_robot_token.'/sprite_left_40x40.png?'.MMRPG_CONFIG_CACHE_DATE.');"></span>';
                    $temp_console_markup .= '</div>';
                }
            }
            array_push($_SESSION[$session_token]['EVENTS'], array(
                'canvas_markup' => $temp_canvas_markup,
                'console_markup' => $temp_console_markup,
                'player_token' => $player_token,
                'event_type' => 'new-chapter'
                ));
        }
    }

}

// If Wily was unlocked, but the player has not yet seen the unlock event, display it
if ($unlock_flag_wily){

    // Display the first level-up event showing Bass and the Proto Buster
    $temp_event_flag = 'dr-wily-event-00_player-unlocked';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;
        $temp_canvas_markup = '';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/wily-castle/battle-field_background_base.gif?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -50px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto; filter: blur(1px) brightness(0.8);"></div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/wily-castle/battle-field_foreground_base.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -45px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_01" style="background-image: url(images/players/dr-wily/sprite_left_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 20px; left: calc(50% - 20px); transform: scale(1.5) translate(-50%, 0); transform-origin: bottom right;"></div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_taunt" style="background-image: url(images/robots/bass/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 40px; left: calc(50% + 20px); transform: scale(1) translate(-50%, 0); transform-origin: bottom left; filter: brightness(0.9);"></div>';
        $temp_console_markup = '';
        $temp_console_markup .= '<p class="ability_type ability_type_attack" style="margin: 5px auto 10px; text-align: center;">Congratulations!</p>';
        $temp_console_markup .= '<p style="margin: 5px auto 10px; text-align: center;">'.rpg_type::print_span('attack', 'Dr. Wily').' has been unlocked as a player character!</p>';
        $temp_console_markup .= '<p style="margin: 5px auto 10px; text-align: center;">Play through the game as <strong>Dr. Wily</strong> and <strong>Bass</strong> to continue the story from their perspective.  Unlock even more new robots, abilities, and items as you continue your fight against the prototype\'s army of powered up opponents!</p>';
        $temp_console_markup .= '<p style="margin: 5px auto 10px; text-align: center; font-size: 90%; line-height: 1.6; color: #d6d6d6;">Select <strong class="ability_type ability_type_attack">Dr. Wily</strong> from the player select menu to continue the campaign.<br />  You can also go back to replay <strong class="ability_type ability_type_defense">Dr. Light</strong> missions at any time.</p>';
        array_push($_SESSION[$session_token]['EVENTS'], array(
            'canvas_markup' => $temp_canvas_markup,
            'console_markup' => $temp_console_markup,
            'player_token' => 'dr-wily',
            'event_type' => 'new-player'
            ));
        $clear_seen_frame_token = 'edit_players';
        rpg_prototype::mark_menu_frame_as_unseen($clear_seen_frame_token);
    }

    // If Wily has been unlocked but somehow Bass was not
    if (!mmrpg_prototype_robot_unlocked(false, 'bass')){
        // Unlock Bass as a playable character
        $unlock_player_info = $mmrpg_index_players['dr-wily'];
        $unlock_robot_info = rpg_robot::get_index_info('bass');
        $unlock_robot_info['robot_level'] = MMRPG_SETTINGS_GAMESTORY2_STARTLEVEL;
        $unlock_robot_info['robot_experience'] = 999;
        mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, true);
    }

}

// If Cossack was unlocked, but the player has not yet seen the unlock event, display it
if ($unlock_flag_cossack){

    // Display the first level-up event showing Proto Man and the Proto Buster
    $temp_event_flag = 'dr-cossack-event-00_player-unlocked';
    if (empty($temp_game_flags['events'][$temp_event_flag])){
        $temp_game_flags['events'][$temp_event_flag] = true;
        $temp_canvas_markup = '';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/cossack-citadel/battle-field_background_base.gif?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -50px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/cossack-citadel/battle-field_foreground_base.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -45px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_01" style="background-image: url(images/players/dr-cossack/sprite_left_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 20px; left: calc(50% - 20px); transform: scale(1.5) translate(-50%, 0); transform-origin: bottom right;"></div>';
        $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_taunt" style="background-image: url(images/robots/proto-man/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 40px; left: calc(50% + 20px); transform: scale(1) translate(-50%, 0); transform-origin: bottom left; filter: brightness(0.9);"></div>';
        $temp_console_markup = '';
        $temp_console_markup .= '<p class="ability_type ability_type_speed" style="margin: 5px auto 10px; text-align: center;">Congratulations!</p>';
        $temp_console_markup .= '<p style="margin: 5px auto 10px; text-align: center;">'.rpg_type::print_span('speed', 'Dr. Cossack').' has been unlocked as a player character!</p>';
        $temp_console_markup .= '<p style="margin: 5px auto 10px; text-align: center;">Play through the game as <strong>Dr. Cossack</strong> and <strong>Proto Man</strong> to continue the story from their perspective.  Unlock even more new robots, abilities, and items as you continue your fight against the prototype\'s army of powered up opponents!</p>';
        $temp_console_markup .= '<p style="margin: 5px auto 10px; text-align: center; font-size: 90%; line-height: 1.6; color: #d6d6d6;">Select <strong class="ability_type ability_type_speed">Dr. Cossack</strong> from the player select menu to continue the campaign.<br />  You can also go back to replay <strong class="ability_type ability_type_defense">Dr. Light</strong> or <strong class="ability_type ability_type_attack">Dr. Wily</strong> missions at any time.</p>';
        array_push($_SESSION[$session_token]['EVENTS'], array(
            'canvas_markup' => $temp_canvas_markup,
            'console_markup' => $temp_console_markup,
            'player_token' => 'dr-cossack',
            'event_type' => 'new-player'
            ));
        $clear_seen_frame_token = 'edit_players';
        rpg_prototype::mark_menu_frame_as_unseen($clear_seen_frame_token);
    }

    // If Cossack has been unlocked but somehow Proto Man was not
    if (!mmrpg_prototype_robot_unlocked(false, 'proto-man')){
        // Unlock Proto Man as a playable character
        $unlock_player_info = $mmrpg_index_players['dr-cossack'];
        $unlock_robot_info = rpg_robot::get_index_info('proto-man');
        $unlock_robot_info['robot_level'] = MMRPG_SETTINGS_GAMESTORY3_STARTLEVEL;
        $unlock_robot_info['robot_experience'] = 999;
        mmrpg_game_unlock_robot($unlock_player_info, $unlock_robot_info, true, true);
    }

}

// Unlock the OMEGA SEED after all three Drs. have completed the prototype
if (!mmrpg_prototype_item_unlocked('omega-seed')
    && mmrpg_prototype_complete() >= 3
    ){

    // Unlock the Omega Seed and generate the required event details
    mmrpg_game_unlock_item('omega-seed', array(
        'positive_word' => 'What\'s this?',
        'event_text' => 'A new item appears to have been unlocked...',
        'field_background' => 'prototype-subspace',
        'field_foreground' => 'prototype-subspace'
        ));

}


/*
// DEBUG DEBUG DEBUG
// Print this message over and over to test message functionality if you want
if (true){
    $temp_canvas_markup = '';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/cossack-citadel/battle-field_background_base.gif?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -50px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80" style="background-image: url(images/fields/cossack-citadel/battle-field_foreground_base.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-position: center -45px; top: 0; right: 0; bottom: 0; left: 0; width: auto; height: auto;"></div>';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_01" style="background-image: url(images/players/dr-cossack/sprite_left_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 40px; left: 180px;"></div>';
    $temp_canvas_markup .= '<div class="sprite sprite_80x80 sprite_80x80_taunt" style="background-image: url(images/robots/proto-man/sprite_right_80x80.png?'.MMRPG_CONFIG_CACHE_DATE.'); bottom: 40px; right: 180px;"></div>';
    $temp_console_markup = '';
    $temp_console_markup .= '<p class="ability_type ability_type_speed" style="margin: 5px auto 10px;">Lorem ipsum dolar sit amet!</p>';
    $temp_console_markup .= '<p>Play through the game as <strong>Foobar</strong> and <strong>Lorem</strong> to continue the story from their perspective.  Unlock even more new robots, abilities, and items as you continue your fight against the prototype\'s army of powered up opponents!</p>';
    $temp_console_markup .= '<p style="margin: 5px auto 0; font-size: 90%; line-height: 1.6; color: #d6d6d6;">Select <strong class="ability_type ability_type_speed">Dr. Cossack</strong> from the player select menu to continue the campaign.<br />  You can also go back to replay <strong class="ability_type ability_type_defense">Dr. Light</strong> or <strong class="ability_type ability_type_attack">Dr. Wily</strong> missions at any time.</p>';
    array_unshift($_SESSION[$session_token]['EVENTS'], array(
        'canvas_markup' => $temp_canvas_markup,
        'console_markup' => $temp_console_markup,
        'player_token' => 'foobar',
        'event_type' => 'other'
        ));
}
*/


?>