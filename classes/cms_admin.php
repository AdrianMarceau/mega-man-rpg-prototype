<?
// Define the class that will act as the mmrpg index file
class cms_admin {

    // Define a function for checking the current sort
    public static function is_sort_link_active($name, $dir = ''){
        global $sort_data;
        if (empty($name)){ return false; }
        if ($name != $sort_data['name']){ return false; }
        if (!empty($dir) && $dir != $sort_data['dir']){ return false; }
        return true;
    }

    // Define a function for generating an href sort link
    public static function get_sort_link_href($name, $dir){
        global $this_page_action, $search_data;
        $sort_link = 'admin/'.$this_page_action.'/search/';
        if (!empty($search_data)){
            $arg_strings = array();
            foreach ($search_data AS $n => $v){ if ($v !== ''){ $arg_strings[] = $n.'='.urlencode($v); } }
            $sort_link .= '&'.implode('&', $arg_strings);
        }
        $sort_link .= '&order='.$name.':'.$dir;
        return $sort_link;
    }

    // Define a function for generating sort link markup
    public static function get_sort_link($name, $text = ''){
        global $sort_data;
        if (empty($text)){ $text = ucfirst($name); }
        $active = self::is_sort_link_active($name) ? true : false;
        $curr_dir = $active ? $sort_data['dir'] : '';
        $new_dir = $active && $curr_dir == 'asc' ? 'desc' : 'asc';
        $class = 'sort'.($active ? ' active '.$curr_dir : '');
        $href = self::get_sort_link_href($name, $new_dir);
        $link = '<a class="'.$class.'" href="'.$href.'"><span>'.$text.'</span></a>';
        return $link;
    }

    // Define a function for printing the totals in a table header/footer
    public static function get_totals_markup(){
        global $search_results_limit, $search_results_count, $search_results_total;
        $totals_markup = '';
        $totals_markup .= '<div class="totals_div">';
        if (!empty($search_results_limit)){ $totals_markup .= ('<span class="showing">Showing '.($search_results_count == 1 ? '1 Row' : $search_results_count.' Rows').'</span>');  }
        else {  $totals_markup .= ('<span class="showing">'.($search_results_count == 1 ? '1 Result' : $search_results_count.' Results').'</span>');  }
        if ($search_results_count != $search_results_total){ $totals_markup .= ('<span class="total">'.$search_results_total.' Total</span>'); }
        $totals_markup .= '</div>';
        return $totals_markup;
    }

    // Define a function for checking if a given string of PHP code has valid syntax
    public static function is_valid_php_syntax($fileContent){
        $filename = tempnam('/tmp', '_');
        file_put_contents($filename, $fileContent);
        exec("php -l {$filename}", $output, $return);
        $output = trim(implode(PHP_EOL, $output));
        unlink($filename);
        return $return === 0 ? true : false;
    }

    // Define a function for easily getting a contributors index for back-end puruposes
    public static function get_contributors_index($object_kind){
        global $db;
        // Ensure the provided object kind as allowed and determine the plural form
        $allowed_kinds = array('player', 'robot', 'field', 'ability', 'item');
        $count_object = in_array($object_kind, $allowed_kinds) ? $object_kind : $allowed_kinds[0];
        $count_object_plural = preg_match('/y$/i', $count_object) ? substr($count_object, 0, -1).'ies' : $count_object.'s';
        // Pull the contributor index different depending on the global constant (pre/post migration check)
        if (MMRPG_CONFIG_IMAGE_EDITOR_ID_FIELD === 'contributor_id'){
            $mmrpg_contributors_index = $db->get_array_list("SELECT
                contributors.contributor_id AS contributor_id,
                contributors.user_name AS user_name,
                contributors.user_name_public AS user_name_public,
                contributors.user_name_clean AS user_name_clean,
                uroles.role_level AS user_role_level,
                (CASE WHEN editors.{$count_object}_image_count IS NOT NULL THEN editors.{$count_object}_image_count ELSE 0 END) AS user_image_count,
                (CASE WHEN editors2.{$count_object}_image_count2 IS NOT NULL THEN editors2.{$count_object}_image_count2 ELSE 0 END) AS user_image_count2
                FROM
                mmrpg_users_contributors AS contributors
                LEFT JOIN mmrpg_users AS users ON users.user_name_clean = contributors.user_name_clean
                LEFT JOIN mmrpg_roles AS uroles ON uroles.role_id = users.role_id
                LEFT JOIN (SELECT
                        {$count_object}_image_editor AS {$count_object}_user_id,
                        COUNT({$count_object}_image_editor) AS {$count_object}_image_count
                        FROM mmrpg_index_{$count_object_plural}
                        GROUP BY {$count_object}_image_editor) AS editors ON editors.{$count_object}_user_id = contributors.contributor_id
                LEFT JOIN (SELECT
                        {$count_object}_image_editor2 AS {$count_object}_user_id,
                        COUNT({$count_object}_image_editor2) AS {$count_object}_image_count2
                        FROM mmrpg_index_{$count_object_plural}
                        GROUP BY {$count_object}_image_editor2) AS editors2 ON editors2.{$count_object}_user_id = contributors.contributor_id
                WHERE
                contributors.contributor_id <> 0
                ORDER BY
                uroles.role_level DESC,
                contributors.user_name_clean ASC
                ;", 'contributor_id');
        } else {
            $mmrpg_contributors_index = $db->get_array_list("SELECT
                users.user_id AS user_id,
                users.user_name AS user_name,
                users.user_name_public AS user_name_public,
                users.user_name_clean AS user_name_clean,
                uroles.role_level AS user_role_level,
                (CASE WHEN editors.{$count_object}_image_count IS NOT NULL THEN editors.{$count_object}_image_count ELSE 0 END) AS user_image_count,
                (CASE WHEN editors2.{$count_object}_image_count2 IS NOT NULL THEN editors2.{$count_object}_image_count2 ELSE 0 END) AS user_image_count2
                FROM
                mmrpg_users AS users
                LEFT JOIN mmrpg_roles AS uroles ON uroles.role_id = users.role_id
                LEFT JOIN (SELECT
                        {$count_object}_image_editor AS {$count_object}_user_id,
                        COUNT({$count_object}_image_editor) AS {$count_object}_image_count
                        FROM mmrpg_index_{$count_object_plural}
                        GROUP BY {$count_object}_image_editor) AS editors ON editors.{$count_object}_user_id = users.user_id
                LEFT JOIN (SELECT
                        {$count_object}_image_editor2 AS {$count_object}_user_id,
                        COUNT({$count_object}_image_editor2) AS {$count_object}_image_count2
                        FROM mmrpg_index_{$count_object_plural}
                        GROUP BY {$count_object}_image_editor2) AS editors2 ON editors2.{$count_object}_user_id = users.user_id
                WHERE
                users.user_id <> 0
                AND (uroles.role_level > 3
                    OR users.user_credit_line <> ''
                    OR users.user_credit_text <> ''
                    OR editors.{$count_object}_image_count IS NOT NULL
                    OR editors2.{$count_object}_image_count2 IS NOT NULL)
                ORDER BY
                uroles.role_level DESC,
                users.user_name_clean ASC
                ;", 'user_id');
        }
        // Return the generated list
        return $mmrpg_contributors_index;
    }

    // Define a function for printing the environment relationship header
    public static function print_env_rel_header($env1, $dir, $env2){
        if ($dir === '<' || $dir === 'left'){ $icon = 'angle-double-left'; }
        elseif ($dir === '>' || $dir === 'right'){ $icon = 'angle-double-right'; }
        else { $icon = 'question-circle'; }
        $markup = '';
            $markup .= '<span class="env-rel">'.PHP_EOL;
                if (true || MMRPG_CONFIG_SERVER_ENV !== 'local'){
                    $markup .= '<span class="env '.$env1.'">'.strtoupper($env1).'</span>'.PHP_EOL;
                    $markup .= '<i class="arrow fa fa-'.$icon.'"></i>'.PHP_EOL;
                    $markup .= '<span class="env '.$env2.'">'.strtoupper($env2).'</span>'.PHP_EOL;
                }
            $markup .= '</span>'.PHP_EOL;
        return $markup;
    }

    // Define a function for printing the full name of a given environment
    public static function print_env_name($env, $ucfirst = false){
        if ($env === 'prod'){ $name = 'production'; }
        elseif ($env === 'stage'){ $name = 'staging'; }
        elseif ($env === 'dev'){ $name = 'developer'; }
        else { $name = $env; }
        return $ucfirst ? ucfirst($name) : $name;
    }

    // Define a function for printing admin home group options, given the list isn't empty
    public static function print_admin_home_group_options($this_group_name, $this_group_options = array(), $this_group_name_subtext = ''){

        // If the options were empty, return an empty string now
        if (empty($this_group_options)){ return ''; }

        // Loop through the option and generate item markup for each of them
        ob_start();
        foreach ($this_group_options AS $option_key => $option_info){
            echo('<li class="item">'.PHP_EOL);
                $link_url = isset($option_info['link']['url']) ? ' href="'.$option_info['link']['url'].'"' : '';
                $link_target = isset($option_info['link']['target']) ? ' target="'.$option_info['link']['target'].'"' : '';
                $link_text = $option_info['link']['text'];
                echo('<div class="link"><a'.$link_url.$link_target.'>'.$link_text.'</a></div>'.PHP_EOL);
                $desc_text = $option_info['desc'];
                echo('<div class="desc"><em>'.$desc_text.'</em></div>'.PHP_EOL);
                if (!empty($option_info['buttons'])){
                    echo('<div class="buttons">'.PHP_EOL);
                    foreach ($option_info['buttons'] AS $button_key => $button_info){
                        $button_action = $button_info['action'];
                        $button_text = $button_info['text'];
                        echo('<a class="button" data-action="'.$button_action.'">'.$button_text.'</a>'.PHP_EOL);
                    }
                    echo('</div>'.PHP_EOL);
                }
            echo('</li>'.PHP_EOL);
        }
        $this_group_items_markup = trim(ob_get_clean());

        // If item markup was generated, wrap it in a list with the title on top
        ob_start();
        if (!empty($this_group_items_markup)){
            echo('<ul class="adminhome">'.PHP_EOL);
                echo('<li class="top">'.PHP_EOL);
                    echo('<strong>'.$this_group_name.'</strong>'.PHP_EOL);
                    if (!empty($this_group_name_subtext)){ echo($this_group_name_subtext.PHP_EOL); }
                echo('</li>'.PHP_EOL);
                echo($this_group_items_markup.PHP_EOL);
            echo('</ul>'.PHP_EOL);
        }
        $this_group_list_markup = trim(ob_get_clean());

        // Return generated group list markup, assuming it's not empty
        return $this_group_list_markup;

    }

}
?>