<?

// Define the leaderboard metric index in order of sorting priority
$leaderboard_metric_index = mmrpg_prototype_leaderboard_metric_index();
$leaderboard_metric_priority = array_keys($leaderboard_metric_index);
$leaderboard_metric_index_urls = array();
foreach ($leaderboard_metric_index as $key => $value) {
    $leaderboard_metric_index_urls[$value['url']] = $key;
}

// Check to see which metric we'll be sorting based on
if (!isset($this_leaderboard_metric)){ $this_leaderboard_metric = MMRPG_SETTINGS_CURRENT_LEADERBOARD_METRIC; }
if (!empty($_GET['metric'])
    && is_string($_GET['metric'])
    && !empty($leaderboard_metric_index_urls[$_GET['metric']])){
    $this_leaderboard_metric = $leaderboard_metric_index_urls[$_GET['metric']];
}

// Collect current leaderboard metric info
$this_leaderboard_metric_info = $leaderboard_metric_index[$this_leaderboard_metric];

// Define a variable to hold the base leaderboard URL in case of filters
$base_leaderboard_url = 'leaderboard/';
$current_leaderboard_url = $base_leaderboard_url;
if (!empty($this_leaderboard_metric)
    && $this_leaderboard_metric !== MMRPG_SETTINGS_DEFAULT_LEADERBOARD_METRIC){
    // Update current page URL w/ filter fragment
    $current_leaderboard_url .= 'by-'.$this_leaderboard_metric_info['url'].'/';
    // Update the page title w/ filter text
    $this_seo_title = str_replace('Leaderboard | ', 'Leaderboard ('.$this_leaderboard_metric_info['name'].') | ', $this_seo_title);
    // Update the page header w/ filter text
    $this_markup_header = $this_seo_title = str_replace('Leaderboard', ucfirst($this_leaderboard_metric_info['url']).' Leaderboard', $this_markup_header);
}

//error_log('$_GET = '.print_r($_GET, true));
//error_log('$leaderboard_metric_index_urls = '.print_r($leaderboard_metric_index_urls, true));
//error_log('$this_leaderboard_metric = '.print_r($this_leaderboard_metric, true));
//error_log('$this_leaderboard_metric_info = '.print_r($this_leaderboard_metric_info, true));

// Collect and define the display limit if set
$this_start_key = !empty($_GET['start']) ? trim($_GET['start']) : 0;
if (!isset($this_display_limit_default)){ $this_display_limit_default = 50; }
$this_display_limit = !empty($_GET['limit']) ? trim($_GET['limit']) : $this_display_limit_default;

// Define a function for parsing the leaderboard data
function mmrpg_leaderboard_parse_index($key, $board, $place_counter){

    global $db;
    global $this_userid, $this_userinfo, $this_boardinfo;
    global $this_display_limit, $this_num_offset;
    global $this_time, $this_start_key, $this_display_limit_default;
    global $leaderboard_metric_index, $current_leaderboard_url;
    global $this_leaderboard_metric, $this_leaderboard_metric_info;

    global $mmrpg_index_players;
    if (empty($mmrpg_index_players)){ $mmrpg_index_players = rpg_player::get_index(true); }

    static $z_index;
    if (empty($z_index)){ $z_index = $this_display_limit_default + 1; }
    $z_index -= 1;


    $board_key = $key;

    // Collect the points/zenny/etc.
    $this_points = 0;
    $this_points = $board[$this_leaderboard_metric_info['key']];

    // Define the awards strong and default to empty
    $this_user_awards = ' ';

    // Collect this player's robots
    $board_points_count = !empty($board['board_points']) ? $board['board_points'] : 0;
    $board_zenny_count = !empty($board['board_zenny']) ? $board['board_zenny'] : 0;
    $board_robots_count = !empty($board['board_robots_count']) ? $board['board_robots_count'] : 0;
    $board_items_count = !empty($board['board_items']) ? $board['board_items'] : 0;
    $board_abilities_count = !empty($board['board_abilities']) ? $board['board_abilities'] : 0;
    $board_stars_count = !empty($board['board_stars']) ? $board['board_stars'] : 0;
    $board_tokens_count = !empty($board['board_tokens_count']) ? $board['board_tokens_count'] : 0;
    $board_medals_count = !empty($board['board_medals_count']) ? $board['board_medals_count'] : 0;
    $board_waves_count = !empty($board['board_waves_count']) ? $board['board_waves_count'] : 0;
    $this_awards = !empty($board['board_awards']) ? explode(',', $board['board_awards']) : array();
    $this_first_save = !empty($board['board_date_created']) ? $board['board_date_created'] : 0;
    $this_last_save = !empty($board['board_date_modified']) ? $board['board_date_modified'] : 0;
    $this_last_access = !empty($board['user_date_accessed']) ? $board['user_date_accessed'] : 0;
    $this_is_online = !empty($board['user_is_online']) ? true : false;
    $this_last_save = !empty($this_last_save) ? date('Y/m/d @ H:i', $this_last_save) : '????-??-?? ??:??';
    $this_style = $this_is_online ? 'border-color: green; ' : '';
    $this_style .= 'z-index: '.$z_index.'; ';
    $this_username = !empty($board['user_name_public']) && !empty($board['user_flag_postpublic']) ? $board['user_name_public'] : $board['user_name'];
    $this_username = htmlentities($this_username, ENT_QUOTES, 'UTF-8', true);
    $this_user_id = !empty($board['user_id']) ? $board['user_id'] : 0;
    if (rpg_user::is_guest()
        && $this_user_id == $_SESSION['GAME']['USER']['userid']
        && $this_leaderboard_metric === MMRPG_SETTINGS_DEFAULT_LEADERBOARD_METRIC){
        $this_boardinfo['board_rank'] = $place_counter;
    }

    // Only continue if markup is special constants have not been defined
    if (!defined('MMRPG_SKIP_MARKUP') || defined('MMRPG_SHOW_MARKUP_'.$this_user_id)){

        // Define a quick function for formatting numbers
        $func_format_thousands_value = function($count){ return number_format($count, 0, '.', ','); };
        $func_format_millions_value = function($count) {
            if ($count < 1000) {
                return number_format($count, 0, '.', ',');
            } elseif ($count < 1000000) {
                return number_format($count/1000, 1, '.', ',').'k';
            } elseif ($count < 1000000000) {
                return number_format($count/1000000, 1, '.', ',').'m';
            } else {
                return number_format($count/1000000000, 1, '.', ',').'b';
            }
        };

        // Only generate markup if we're withing the viewing range
        if ($board_key >= $this_start_key && $board_key < $this_display_limit || defined('MMRPG_SHOW_MARKUP_'.$this_user_id)){

            $points_count_text = ($board_points_count === 1 ? '1 Battle Point' : $func_format_thousands_value($board_points_count).' Battle Points');
            $zenny_count_text = ($board_zenny_count === 1 ? '1 Zenny' : $func_format_thousands_value($board_zenny_count).' Zenny');
            $robots_count_text = ($board_robots_count === 1 ? '1 Robot' : $board_robots_count.' Robots').' Unlocked';
            $abilities_count_text = ($board_abilities_count === 1 ? '1 Ability' : $board_abilities_count.' Abilities').' Unlocked';
            $items_count_text = ($board_items_count === 1 ? '1 Item' : $board_items_count.' Items').' Cataloged';
            $stars_count_text = ($board_stars_count === 1 ? '1 Star' : $board_stars_count.' Stars').' Collected';
            $tokens_count_text = $board_tokens_count === 1 ? '1 Player Token' : $board_tokens_count.' Player Tokens';
            $medals_count_text = $board_medals_count === 1 ? '1 Challenge Medal' : $board_medals_count.' Challenge Medals';
            $waves_count_text = $board_waves_count === 1 ? '1 Endless Wave' : $board_waves_count.' Endless Waves';

            $this_records_html = array();
            if ($this_leaderboard_metric !== 'robots_unlocked' && !empty($board_robots_count)){
                $icon = $leaderboard_metric_index['robots_unlocked']['icon'];
                $this_records_html[] = '<span class="count robots" title="'.$robots_count_text.'">'.$board_robots_count.' <i class="fa fa-fw '.$icon.'"></i></span>';
            }
            if ($this_leaderboard_metric !== 'abilities_unlocked' && !empty($board_abilities_count)){
                $icon = $leaderboard_metric_index['abilities_unlocked']['icon'];
                $this_records_html[] = '<span class="count abilities" title="'.$abilities_count_text.'">'.$board_abilities_count.' <i class="fa fa-fw '.$icon.'"></i></span>';
            }
            if ($this_leaderboard_metric !== 'items_cataloged' && !empty($board_items_count)){
                $icon = $leaderboard_metric_index['items_cataloged']['icon'];
                $this_records_html[] = '<span class="count items" title="'.$items_count_text.'">'.$board_items_count.' <i class="fa fa-fw '.$icon.'"></i></span>';
            }
            if ($this_leaderboard_metric !== 'stars_collected' && !empty($board_stars_count)){
                $icon = $leaderboard_metric_index['stars_collected']['icon'];
                $this_records_html[] = '<span class="count stars" title="'.$stars_count_text.'">'.$board_stars_count.' <i class="fa fa-fw '.$icon.'"></i></span>';
            }
            if ($this_leaderboard_metric !== 'player_tokens' && !empty($board_tokens_count)){
                $icon = $leaderboard_metric_index['player_tokens']['icon'];
                $this_records_html[] = '<span class="count tokens" title="'.$tokens_count_text.'">'.$board_tokens_count.' <i class="fa fa-fw '.$icon.'"></i></span>';
            }
            if ($this_leaderboard_metric !== 'challenges_medals' && !empty($board_medals_count)){
                $icon = $leaderboard_metric_index['challenges_medals']['icon'];
                $this_records_html[] = '<span class="count medals" title="'.$medals_count_text.'">'.$board_medals_count.' <i class="fa fa-fw '.$icon.'"></i></span>';
            }
            if ($this_leaderboard_metric !== 'endless_waves' && !empty($board_waves_count)){
                $icon = $leaderboard_metric_index['endless_waves']['icon'];
                $this_records_html[] = '<span class="count waves" title="'.$waves_count_text.'">'.$func_format_thousands_value($board_waves_count).' <i class="fa fa-fw '.$icon.'"></i></span>';
            }
            if ($this_leaderboard_metric !== 'battle_points' && !empty($board_points_count)){
                $unit = $leaderboard_metric_index['battle_points']['unit'];
                $this_records_html[] = '<span class="count bpoints" title="'.$points_count_text.'">'.$func_format_millions_value($board_points_count).' '.$unit.'</span>';
            }
            if (false && $this_leaderboard_metric !== 'battle_zenny' && !empty($board_zenny_count)){
                $unit = $leaderboard_metric_index['battle_points']['unit'];
                $this_records_html[] = '<span class="count zenny" title="'.$zenny_count_text.'">'.$func_format_millions_value($board_zenny_count).' '.$unit.'</span>';
            }
            $this_records_html = implode(' <span class="pipe">|</span> ', $this_records_html);

            $this_points_html = '<span class="value">'.(!empty($this_points) ? $func_format_thousands_value($this_points) : 0).'</span>';
            $this_points_plain = (!empty($this_points) ? $func_format_thousands_value($this_points) : 0);

            if (!empty($this_leaderboard_metric_info['unit'])){
                $this_points_html .= ' '.$this_leaderboard_metric_info['unit'];
                $this_points_plain .= ' '.(!empty($this_leaderboard_metric_info['unit_plain']) ? $this_leaderboard_metric_info['unit_plain'] : $this_leaderboard_metric_info['unit']);
            } else {
                if (!empty($this_leaderboard_metric_info['icon'])){
                    $this_points_html .= ' <i class="fa fa-fw '.$this_leaderboard_metric_info['icon'].'"></i>';
                }
                if (!empty($this_leaderboard_metric_info['label'])){
                    list($singular, $plural) = strstr($this_leaderboard_metric_info['label'], '/') ? explode('/', $this_leaderboard_metric_info['label']) : array($this_leaderboard_metric_info['label'], $this_leaderboard_metric_info['label']);
                    if (empty($this_leaderboard_metric_info['icon'])){ $this_points_html .= ' '.($this_points == 1 ? $singular : $plural); }
                    $this_points_plain .= ' '.($this_points == 1 ? $singular : $plural);
                }
            }

            $this_details = ''.$this_last_save;

            // If this player is in first/second/third place but hasn't received the award...
            $this_awards_string = '';
            if ($place_counter == 1 && !in_array('ranking_first_place', $this_awards)){
                // FIRST PLACE
                $this_awards[] = 'ranking_first_place';
                $this_awards_string = implode(',', $this_awards);
            } elseif ($place_counter == 2 && !in_array('ranking_second_place', $this_awards)){
                // SECOND PLACE
                $this_awards[] = 'ranking_second_place';
                $this_awards_string = implode(',', $this_awards);
            } elseif ($place_counter == 3 && !in_array('ranking_third_place', $this_awards)){
                // THIRD PLACE
                $this_awards[] = 'ranking_third_place';
                $this_awards_string = implode(',', $this_awards);
            }
            if (!empty($this_awards_string)
                && $this_leaderboard_metric === MMRPG_SETTINGS_DEFAULT_LEADERBOARD_METRIC){
                $db->query("UPDATE `mmrpg_leaderboard` SET `board_awards` = '{$this_awards_string}' WHERE `user_id` = {$board['user_id']};");
            }

            // -- LEADERBOARD MARKUP -- //

            // Add the prototype complete flags if applicable
            $pos = 0;
            if (in_array('prototype_complete_light', $this_awards)){ $pos++; $this_user_awards .= '<span class="sprite achievement_icon achievement_dr-light-complete'.($pos ? ' pos'.$pos : '').'" data-tooltip="Light Campaign Complete!" data-tooltip-type="player_type player_type_defense">&hearts;</span>'; }
            if (in_array('prototype_complete_wily', $this_awards)){ $pos++; $this_user_awards .= '<span class="sprite achievement_icon achievement_dr-wily-complete'.($pos ? ' pos'.$pos : '').'" data-tooltip="Wily Campaign Complete!" data-tooltip-type="player_type player_type_attack">&clubs;</span>'; }
            if (in_array('prototype_complete_cossack', $this_awards)){ $pos++; $this_user_awards .= '<span class="sprite achievement_icon achievement_dr-cossack-complete'.($pos ? ' pos'.$pos : '').'" data-tooltip="Cossack Campaign Complete!" data-tooltip-type="player_type player_type_speed">&diams;</span>'; }
            // Add the first place flag if applicable
            $this_user_awards_sticky = '';
            if (in_array('ranking_first_place', $this_awards)){ $this_user_awards_sticky .= '<span class="sprite achievement_icon achievement_'.($place_counter == 1 ? 'is' : 'reached').'-first-place" data-tooltip="Reached First Place!" data-tooltip-type="player_type player_type_level">&#9733;</span>'; }


            // Start the output buffer
            ob_start();

            // Display the user's save file listing
            $this_place = mmrpg_number_suffix($place_counter, true, true);
            $this_colour = !empty($board['user_colour_token']) ? $board['user_colour_token'] : '';
            if (!empty($this_colour) && !empty($board['user_colour_token2'])){ $this_colour .= '_'.$board['user_colour_token2']; }
            if (empty($this_colour)){ $this_colour = 'none'; }
            if (!empty($board['user_image_path'])){ list($avatar_class, $avatar_token, $avatar_base_size) = explode('/', $board['user_image_path']); }
            else { $avatar_class = 'robots'; $avatar_token = 'mega-man'; $avatar_base_size = 40; }
            if (!empty($board['user_background_path'])){ list($background_class, $background_token) = explode('/', $board['user_background_path']); }
            else { $background_class = 'fields'; $background_token = rpg_player::get_intro_field('player'); }
            $avatar_size = $avatar_base_size * 2;
            $place_frame = 'base';
            //if ($place_counter == 3){ $place_frame = 'summon'; }
            //elseif ($place_counter == 2){ $place_frame = 'taunt'; }
            //elseif ($place_counter == 1){ $place_frame = 'victory'; }
            if ($place_counter <= 3){ $place_frame = 'victory'; }
            $y_offset = 0;
            if (strstr($avatar_token, 'astro-man') && $this_place > 1){ $y_offset = -18; }
            elseif (strstr($avatar_token, 'cloud-man') && $this_place > 1){ $y_offset = -19; }
            elseif (strstr($avatar_token, 'guts-man') && $this_place > 1){ $y_offset = -12; }
            elseif (strstr($avatar_token, 'sword-man') && $this_place > 1){ $y_offset = -12; }
            elseif (strstr($avatar_token, 'splash-woman') && $this_place > 1){ $y_offset = -9; }
            elseif (strstr($avatar_token, 'frost-man') && $this_place > 1){ $y_offset = -12; }
            $character_token = preg_replace('/_([-a-z0-9]+)$/i', '', $avatar_token);
            $avatar_animation_duration = rpg_robot::get_css_animation_duration($character_token);
            if (empty($avatar_animation_duration)){ $avatar_animation_duration = 1.0; }
            echo '<a data-id="'.$board['user_id'].'" data-player="'.$board['user_name_clean'].'" class="file file_'.strip_tags($this_place).'" name="file_'.$key.'" style="'.$this_style.'" href="'.$current_leaderboard_url.$board['user_name_clean'].'/">'."\n";
                echo '<div class="inset player_type type_'.$this_colour.'" style="animation-delay: '.($board_key % 2 === 0 ? -5 : -10).'s;">'."\n";
                    echo '<span class="place">'.$this_place.'</span>'."\n";
                    echo '<span class="userinfo"><span class="username">'.$this_username.$this_user_awards.'</span><span class="details">'.$this_details.'</span></span>'."\n";
                    echo '<span class="points">'.$this_points_html.'</span>'."\n";
                    echo '<span class="records">'.$this_records_html.'</span>'."\n";
                echo '</div>'."\n";
                if (!empty($this_user_awards_sticky)){ echo $this_user_awards_sticky."\n"; }
                echo '<span class="avatar"><span class="avatar_wrapper" style="bottom: '.$y_offset.'px; animation-duration: '.$avatar_animation_duration.'s;">';
                    echo '<span class="sprite sprite_shadow sprite_'.$avatar_size.'x'.$avatar_size.' sprite_shadow_'.$avatar_size.'x'.$avatar_size.' sprite_'.$avatar_size.'x'.$avatar_size.'_'.$place_frame.'" style="background-image: url(images/'.$avatar_class.'/'.preg_replace('/^([-a-z0-9]+)(_[a-z0-9]+)?$/i', '$1', $avatar_token).'/sprite_left_'.$avatar_base_size.'x'.$avatar_base_size.'.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-size: auto '.$avatar_size.'px;">'.$this_username.'</span>';
                    echo '<span class="sprite sprite_'.$avatar_size.'x'.$avatar_size.' sprite_'.$avatar_size.'x'.$avatar_size.'_'.$place_frame.'" style="background-image: url(images/'.$avatar_class.'/'.$avatar_token.'/sprite_left_'.$avatar_base_size.'x'.$avatar_base_size.'.png?'.MMRPG_CONFIG_CACHE_DATE.'); background-size: auto '.$avatar_size.'px;">'.$this_username.'</span>';
                echo '</span></span>'."\n";
            echo '</a>'."\n";

            // Collect the output from the buffer and return
            $this_leaderboard_markup = preg_replace('/\s+/', ' ', ob_get_clean());
            return $this_leaderboard_markup;

        }

    }

}

// Query the database and collect the array list of all non-bogus players
$this_online_timeout = MMRPG_SETTINGS_ONLINE_TIMEOUT;
$this_sort_field = $this_leaderboard_metric_info['col'];
$this_sort_field2 = 'board.board_points';
//if ($this_current_page == 'home'){ $temp_leaderboard_query = mmrpg_prototype_leaderboard_index_query($this_leaderboard_metric, $this_display_limit_default); }
//else { $temp_leaderboard_query = mmrpg_prototype_leaderboard_index_query($this_leaderboard_metric); }
$this_leaderboard_index = mmrpg_prototype_leaderboard_index($this_leaderboard_metric); //$db->get_array_list($temp_leaderboard_query);
//error_log('$temp_leaderboard_query = '.print_r($temp_leaderboard_query, true));
//error_log('$this_leaderboard_index('.count($this_leaderboard_index).') = [...]');

// Loop through the save file directory and generate an index
$this_leaderboard_count = count($this_leaderboard_index);
$this_leaderboard_online_count = 0;
$this_leaderboard_online_players = array();
$this_leaderboard_online_pages = array();
$this_leaderboard_markup = array();

// If we're on the home page, we need to collect count independantly
if ($this_current_page == 'home'){
    $this_leaderboard_count_query = "SELECT
        COUNT(*) AS num_players
        FROM mmrpg_users AS users
        LEFT JOIN mmrpg_leaderboard AS board ON users.user_id = board.user_id
        LEFT JOIN mmrpg_saves AS saves ON saves.user_id = board.user_id
        WHERE
        users.user_flag_approved = 1
        AND {$this_sort_field2} > 0
        ORDER BY
        {$this_sort_field} DESC,
        {$this_sort_field2} DESC,
        saves.save_date_modified DESC
        ;";
    //error_log('$this_leaderboard_count_query = '.print_r($this_leaderboard_count_query, true));
    $this_leaderboard_count = $db->get_value($this_leaderboard_count_query, 'num_players');
}

// Ensure the leaderboard array is not empty before continuing
if (!empty($this_leaderboard_index)){
    $this_time = time();
    $last_points = 0;

    // Collect a rank index from which to reference in the next step
    $leaderboard_rank_index = mmrpg_prototype_leaderboard_rank_index($this_leaderboard_metric);
    //error_log('$leaderboard_rank_index for '.$this_leaderboard_metric.' = '.print_r($leaderboard_rank_index, true));
    usort($this_leaderboard_index, function($a, $b) use ($leaderboard_rank_index) {
        $a_rank = isset($leaderboard_rank_index[$a['user_id']]) ? $leaderboard_rank_index[$a['user_id']] : 0;
        $b_rank = isset($leaderboard_rank_index[$b['user_id']]) ? $leaderboard_rank_index[$b['user_id']] : 0;
        if ($a_rank == $b_rank){ return 0; }
        return ($a_rank < $b_rank) ? -1 : 1;
    });

    // Loop through the leaderboard array and print out any markup
    foreach ($this_leaderboard_index AS $key => $board){
        //echo("\n\n<!-- \$this_leaderboard_index[{$key}] -->\n");

        // Collect the points and increment the place counter if necessary
        //$this_points = $board['board_points'];
        $this_points = $board[$this_leaderboard_metric_info['key']];
        if ($this_points != $last_points){ $last_points = $this_points; }

        // Always collect the place counter (the rank) from the index so it's consistent
        $place_counter = isset($leaderboard_rank_index[$board['user_id']]) ? $leaderboard_rank_index[$board['user_id']] : count($leaderboard_rank_index) + 1;

        // Define the variable for this leaderboard markup
        $this_markup = '';

        // If this user is online, at least track it's data
        if (!empty($board['user_is_online'])){
            //echo("<!-- !empty(\$board['user_is_online']) -->\n");
            $this_leaderboard_online_count++;
            $this_current_page_number = ceil(($key + 1) / $this_display_limit_default);
            if (!in_array($this_current_page_number, $this_leaderboard_online_pages)){ $this_leaderboard_online_pages[] = $this_current_page_number; }
            $this_leaderboard_online_players[] = array(
                'id' => $board['user_id'],
                'name' => !empty($board['user_name_public']) ? $board['user_name_public'] : $board['user_name'],
                'token' => $board['user_name_clean'],
                'place' => mmrpg_number_suffix($place_counter, true, true),
                'placeint' => $place_counter,
                'colour' => $board['user_colour_token'],
                'colour2' => $board['user_colour_token2'],
                'image' => $board['user_image_path'],
                'page' => $this_current_page_number
                );
        }

        // If this user was requested specifically, generate markup
        if (defined('MMRPG_SHOW_MARKUP_'.$board['user_id'])){
            //echo("<!-- defined('MMRPG_SHOW_MARKUP_{$board['user_id']}') -->\n");
            $this_markup = mmrpg_leaderboard_parse_index($key, $board, $place_counter);
        }
        // Otherwise if the page is in range and can be shown normally
        elseif (!defined('MMRPG_SKIP_MARKUP') && $key >= $this_start_key && $key < $this_display_limit){
            //echo("<!-- !defined('MMRPG_SKIP_MARKUP') && {$key} >= {$this_start_key} && {$key} < {$this_display_limit} -->\n");
            $this_markup = mmrpg_leaderboard_parse_index($key, $board, $place_counter);
        }

        // Add this markup to the leaderboard array
        $this_leaderboard_markup[] = $this_markup;

    }
}

?>