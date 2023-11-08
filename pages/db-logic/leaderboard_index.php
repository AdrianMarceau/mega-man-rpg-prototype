<?

// Define the SEO variables for this page
if ($this_current_num > 1){ $this_seo_title = str_replace('Leaderboard | ', 'Leaderboard | Page '.$this_current_num.' | ', $this_seo_title); }
$this_seo_description = 'The Mega Man RPG Prototype currently has '.(!empty($this_leaderboard_count) ? ($this_leaderboard_count == 1 ? '1 player' : $this_leaderboard_count.' players') : '0 players').' players and that number is growing all the time. '.$this_seo_description;

// Update the GET variables with the current page num
$this_display_limit_default = 50;
$this_num_offset = $this_current_num - 1;
$_GET['start'] = 0 + ($this_num_offset * $this_display_limit_default);
$_GET['limit'] = $this_display_limit_default + ($this_num_offset * $this_display_limit_default);

// Require the leaderboard data file
require_once(MMRPG_CONFIG_ROOTDIR.'includes/leaderboard.php');

// Update the MARKUP variables for this page
$this_markup_counter = '<span class="count count_header">( '.(!empty($this_leaderboard_count) ? ($this_leaderboard_count == 1 ? '1 Player' : $this_leaderboard_count.' Players') : '0 Players').($this_leaderboard_online_count > 0 ? ' <span style="opacity: 0.25;">|</span> <span style="text-shadow: 0 0 5px lime;">'.$this_leaderboard_online_count.' Online</span>' : '').' )</span>';

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_COUNT -->
$find = '<!-- MMRPG_LEADERBOARD_COUNT -->';
if (strstr($page_content_parsed, $find)){
    $replace = !empty($this_leaderboard_count) ? $this_leaderboard_count : 0;
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_SUBHEAD_FILTER -->
$find = '<!-- MMRPG_LEADERBOARD_SUBHEAD_FILTER -->';
if (strstr($page_content_parsed, $find)){
    $replace = '';
    $replace = '(Ranked by '.$this_leaderboard_metric_info['name'].')';
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_METRIC_NAME -->
$find = '<!-- MMRPG_LEADERBOARD_METRIC_NAME -->';
if (strstr($page_content_parsed, $find)){
    $replace = $this_leaderboard_metric_info['name'];
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_METRIC_SHORTNAME -->
$find = '<!-- MMRPG_LEADERBOARD_METRIC_SHORTNAME -->';
if (strstr($page_content_parsed, $find)){
    $replace = ucfirst($this_leaderboard_metric_info['url']);
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_METRIC_TEXT -->
$find = '<!-- MMRPG_LEADERBOARD_METRIC_TEXT -->';
if (strstr($page_content_parsed, $find)){
    $replace = strtolower($this_leaderboard_metric_info['name']);
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_METRIC_METHOD -->
$find = '<!-- MMRPG_LEADERBOARD_METRIC_METHOD -->';
if (strstr($page_content_parsed, $find)){
    $replace = $this_leaderboard_metric_info['text'];
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_FILTER_OPTIONS -->
$find = '<!-- MMRPG_LEADERBOARD_FILTER_OPTIONS -->';
if (strstr($page_content_parsed, $find)){
    ob_start();
    if (true){
        ?>
        <div class="ranking-options">
            <div class="event text">
                <strong class="label"><span>Ranked By</span>:</strong>
                <?
                $leaderboard_metric_index_tokens = array_keys($leaderboard_metric_index);
                foreach ($leaderboard_metric_index_tokens AS $key => $token){
                    if ($key > 0){ echo('<span class="pipe">|</span> '); }
                    $info = $leaderboard_metric_index[$token];
                    $active = $token === $this_leaderboard_metric ? true : false;
                    $class = 'ranking';
                    $class .= ($active ? ' active type_span type_'.MMRPG_SETTINGS_CURRENT_FIELDTYPE : '');
                    $url = $base_leaderboard_url.($token !== MMRPG_SETTINGS_DEFAULT_LEADERBOARD_METRIC ? 'by-'.$info['url'].'/' : '');
                    echo('<a class="'.$class.'" href="'.$url.'">');
                        echo('<strong>'.ucfirst($info['url']).'</strong>');
                        if (!empty($info['icon'])){ echo(' <i class="fa fa-fw '.$info['icon'].'"></i>'); }
                        else { echo(' <i class="fa fa-fw pfa-'.str_replace('_', '-', $token).'"></i>'); }
                    echo('</a> ');
                } ?>
            </div>
        </div>
        <?
    }
    $replace = ob_get_clean();
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_ONLINE_PLAYERS -->
$find = '<!-- MMRPG_LEADERBOARD_ONLINE_PLAYERS -->';
if (strstr($page_content_parsed, $find)){
    ob_start();
    if (!empty($this_leaderboard_online_players)){
        ?>
        <div class="online-players">
            <div class="event text">
                <strong class="label">Online Players</strong>
                <? foreach ($this_leaderboard_online_players AS $key => $info){
                    if (empty($info['image'])){ $info['image'] = 'robots/mega-man/40'; }
                    list($path, $token, $size) = explode('/', $info['image']);
                    $frame = $info['placeint'] <= 3 ? 'victory' : 'base';
                    $colour = $info['colour'].(!empty($info['colour']) && !empty($info['colour2']) ? '_'.$info['colour2'] : '');
                    //if ($key > 0 && $key % 5 == 0){ echo '<br />'; }
                    echo('<a data-playerid="'.$info['id'].'" class="player_type player_type_'.$colour.'" href="'.$current_leaderboard_url.$info['token'].'/">');
                        echo('<span class="sprite-wrap"><span class="sprite sprite_'.$size.'x'.$size.' sprite_'.$size.'x'.$size.'_'.$frame.'" style="left: '.($size == 40 ? -4 : -26).'px; background-image: url(images/'.$path.'/'.$token.'/sprite_left_'.$size.'x'.$size.'.png?'.MMRPG_CONFIG_CACHE_DATE.');">&nbsp;</span></span>');
                        echo('<span class="name-wrap">'.strip_tags($info['place']).' : '.$info['name'].'</span>');
                    echo('</a> ');
                } ?>
            </div>
        </div>
        <?
    }
    $replace = ob_get_clean();
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Parse the pseudo-code tag <!-- MMRPG_LEADERBOARD_INDEX_MARKUP -->
$find = '<!-- MMRPG_LEADERBOARD_INDEX_MARKUP -->';
if (strstr($page_content_parsed, $find)){
    ob_start();
    ?>
    <div class="leaderboard">
            <div class="wrapper">
            <?

            // Print out the generated leaderboard markup
            //echo $this_leaderboard_markup;
            //die('<pre>'.print_r($this_leaderboard_markup, true).'</pre>');
            //die('$this_start_key = '.$this_start_key.'; $this_display_limit = '.$this_display_limit.'; ');
            if (!empty($this_leaderboard_markup)){

                    // GENERATE MARKUP //

                    // Define the start and end pages based on total numbers
                    $display_range = 2;
                    $display_range2 = 8;
                    $first_page_num = 1;
                    $last_page_num = ceil($this_leaderboard_count / $this_display_limit_default);

                    // Define the variable to hold the pagelink markup
                    $playerlink_markup = '';
                    ob_start();

                        // Loop through and print out the leaderboard player links
                        $last_key = 0;
                        $this_start_key = $_GET['start'];
                        $this_display_limit = $_GET['limit'];
                        foreach ($this_leaderboard_markup AS $key => $leaderboard_markup){
                            // If this key is below the start limit, don't display
                            if (empty($leaderboard_markup)){ continue; }
                            // Update the last key variable
                            $last_key = $key;
                            // Display this save file's markup
                            echo $leaderboard_markup;
                        }
                        // Define the start key for the next batch of players
                        $start_key = $last_key + 1;

                    // Collect the pagelink markup
                    $playerlink_markup = trim(ob_get_clean());

                    // Define the variable to hold the pagelink markup
                    $pagelink_markup = '';
                    ob_start();

                        // If we're not on the first page, create a link to go back one
                        if ($this_display_limit > $this_display_limit_default){
                            $new_display_limit = $this_display_limit - $this_display_limit_default;
                            $new_start_key = $start_key - $this_display_limit_default - $this_display_limit_default;
                            if ($new_display_limit < $this_display_limit_default){ $new_display_limit = 0; }
                            if ($new_start_key < 0){ $new_start_key = 0; }
                            $previous_page_num = $this_current_num - 1;
                            echo '<a class="link prev" href="'.$current_leaderboard_url.$previous_page_num.'/" >&laquo; Prev</a>';
                        }

                        // If not displaying all players, create a link to show more
                        if ($this_display_limit < $this_leaderboard_count){
                            $new_display_limit = $this_display_limit + $this_display_limit_default;
                            if ($new_display_limit > $this_leaderboard_count){ $new_display_limit = $this_leaderboard_count; }
                            $next_page_num = $this_current_num + 1;
                            echo '<a class="link next" href="'.$current_leaderboard_url.$next_page_num.'/" >Next &raquo;</a>';
                        }
                        // If we're already on the last page, display a link to go to the first
                        elseif ($this_display_limit >= $this_leaderboard_count){
                            echo '<a class="link next" href="'.$current_leaderboard_url.'">First &raquo;</a>';
                        }

                        // Create links for all the page numbers one by one
                        if ($this_leaderboard_count > $this_display_limit_default){
                            // Loop through and generate the page number markup
                            for ($this_page_num = $first_page_num; $this_page_num <= $last_page_num; $this_page_num++){
                                $show_page_num = false;
                                if ($this_page_num == $this_current_num){ $show_page_num = true; }
                                elseif ($this_page_num <= $this_current_num + $display_range && $this_page_num >= $this_current_num - $display_range){ $show_page_num = true; }
                                elseif ($this_page_num <= $first_page_num + $display_range2 && $this_page_num >= $first_page_num - $display_range2){ $show_page_num = true; }
                                elseif ($this_page_num <= $last_page_num + $display_range2 && $this_page_num >= $last_page_num - $display_range2){ $show_page_num = true; }
                                $show_num_text = $show_page_num ? $this_page_num : '.';
                                $show_num_type = $show_page_num ? 'number' : 'bullet';
                                $show_online = in_array($this_page_num, $this_leaderboard_online_pages) ? true : false;
                                if ($this_current_num == $this_page_num){ echo '<a class="link '.$show_num_type.' active '.($show_online ? 'field_type field_type_nature' : '').'"><span>'.$this_page_num.'</span></a>'; }
                                else { echo '<a class="link '.$show_num_type.' '.($show_online ? 'field_type field_type_nature' : '').'" href="'.$current_leaderboard_url.($this_page_num > 1 ? $this_page_num.'/' : '').'" ><span>'.$this_page_num.'</span></a>'; }
                            }
                        }

                    // Collect the pagelink markup
                    $pagelink_markup = trim(ob_get_clean());


                    // PRINT MARKUP //

                    // Print out pagelinks for the header
                    echo '<div class="pagelinks head">';
                        echo $pagelink_markup;
                    echo '</div>';

                    // Print out the opening tag for the container dig
                    echo '<div class="container playerlinks">';
                        echo $playerlink_markup;
                    echo '</div>';

                    // Print out pagelinks for the footer
                    echo '<div class="pagelinks foot">';
                        echo $pagelink_markup;
                    echo '</div>';


                }

            ?>
            </div>
    </div>
    <?
    $replace = ob_get_clean();
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

?>