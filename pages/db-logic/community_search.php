<?

// Prevent this page from being indexed
$this_seo_robots = 'noindex,nofollow';

// Parse the pseudo-code tag <!-- MMRPG_COMMUNITY_CATEGORY_SUBHEADER_LINKS -->
$find = '<!-- MMRPG_COMMUNITY_CATEGORY_SUBHEADER_LINKS -->';
if (strstr($page_content_parsed, $find)){
    ob_start();
    ?>
        <a class="link" style="display: inline;" href="<?= str_replace($db_page_info['page_token'].'/', '', $_GET['this_current_url']) ?>">Community</a> <span class="pipe">&nbsp;&raquo;&nbsp;</span>
        <a class="link" style="display: inline;" href="<?= $_GET['this_current_url'] ?>"><?= $db_page_info['page_name'] ?></a>
    <?
    $replace = ob_get_clean();
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

// Define the global variables for posts
$this_posts_array = array();
$this_posts_count = 0;

// Define the empty search variables for posts
$post_search_query = '';
$post_search_array = array();
$post_search_count = 0;

// Define the empty index variables for posts
$post_index_query = '';
$post_index_array = array();
$post_index_array_required = array();
$post_index_count = 0;

// Define the global variables for posts
$this_threads_array = array();
$this_threads_count = 0;

// Define the empty search variables for threads
$thread_search_query = '';
$thread_search_array = array();
$thread_search_count = 0;

// Define the empty index variables for threads
$thread_index_query = '';
$thread_index_array = array();
$thread_index_array_required = array();
$thread_index_count = 0;

// Define the temp filter by labale and default to empty
$temp_filter_data = array();
$temp_filter_result_count = 0;

// Add DISPLAY variables to the filter if provided in the header
if (!empty($_REQUEST['display'])){
  $temp_request = in_array($_REQUEST['display'], array('threads', 'posts')) ? $_REQUEST['display'] : 'threads';
  $temp_filter_data['display'] = $temp_request;
} else {
  $temp_filter_data['display'] = 'threads';
}

// Add LIMIT variables to the filter if provided in the header
if (!empty($_REQUEST['limit'])){
  $temp_request = in_array($_REQUEST['limit'], array('all', 'threads', 'posts')) ? $_REQUEST['limit'] : 'all';
  $temp_filter_data['limit'] = $temp_request;
} else {
  $temp_filter_data['limit'] = 'all';
}

// Add LIMIT variables to the filter if provided in the header
if (!empty($_REQUEST['sort'])){
  $temp_request = in_array($_REQUEST['sort'], array('asc', 'desc')) ? $_REQUEST['sort'] : 'desc';
  $temp_filter_data['sort'] = $temp_request;
} else {
  $temp_filter_data['sort'] = 'desc';
}


// Pre-collect field lists for common tables
$temp_user_fields = rpg_user::get_index_fields(true, 'users');
$temp_user_role_fields = rpg_user_role::get_index_fields(true, 'roles');
$temp_thread_fields = cms_thread::get_index_fields(true, 'threads');
$temp_thread_post_fields = cms_thread_post::get_index_fields(true, 'posts');
$temp_thread_category_fields = cms_thread_category::get_index_fields(true, 'categories');


// -- SEARCH MATCHING POSTS -- //

// Search the database for matching posts
if (true){

    // Default the filter variable to empty
    $temp_filter_by = '';

    // Add TEXT variables to the filter if provided in the header
    if (!empty($_REQUEST['text'])){
        $temp_text = strip_tags(strtolower($_REQUEST['text']));
        $temp_text = trim(str_replace('/[^-_a-z0-9\s*]+/i', '', $temp_text));
        if (!empty($temp_text)){
            $temp_filter_data['text'] = $temp_text;
            $temp_text = str_replace('*', '%', $temp_text);
            $temp_limit = !empty($_REQUEST['text_limit']) ? $_REQUEST['text_limit'] : 'all';
            $temp_filter_data['text_limit'] = $temp_limit;
            $temp_filter_by .= 'AND (posts.post_body LIKE \'%'.$temp_text.'%\') ';
        }
    }

    // Add PLAYER variables to the filter if provided in the header
    if (!empty($_REQUEST['player'])){
        $temp_player = strip_tags(strtolower($_REQUEST['player']));
        $temp_player = trim(str_replace('/[^-_a-z0-9\s]+/i', '', $temp_player));
        if (!empty($temp_player)){
            $temp_filter_data['player'] = $temp_player;
            $temp_player = str_replace(' ', '%', $temp_player);
            $temp_strict = !empty($_REQUEST['player_strict']) ? true : false;
            $temp_filter_data['player_strict'] = $temp_strict;
            if ($temp_strict == true){ $temp_filter_by .= 'AND (users.user_name LIKE \''.$temp_player.'\' OR users.user_name_public LIKE \''.$temp_player.'\') '; }
            elseif ($temp_strict == false){ $temp_filter_by .= 'AND (users.user_name LIKE \'%'.$temp_player.'%\' OR users.user_name_public LIKE \'%'.$temp_player.'%\') '; }
        }
    }

    // Add CATEGORY variables to the filter if provided in the header
    if (!empty($_REQUEST['category'])){
        $temp_id_includes = array();
        $temp_category_tokens = is_array($_REQUEST['category']) ? $_REQUEST['category'] : explode(',', $_REQUEST['category']);
        foreach ($temp_category_tokens AS $temp_cat_token){
            if (!empty($this_categories_index[$temp_cat_token])){
                $temp_id_includes[] = $this_categories_index[$temp_cat_token]['category_id'];
            }
        }
        if (!empty($temp_id_includes)){
            $temp_filter_by .= 'AND threads.category_id IN ('.implode(',', $temp_id_includes).') ';
            $temp_filter_data['category'] = $temp_category_tokens;
        }
    }

    // Collect all the posts matching this query from the database
    if (!empty($temp_filter_data['text']) || !empty($temp_filter_data['player'])){
        $temp_order_by = 'posts.post_date '.strtoupper($temp_filter_data['sort']);
        $post_search_query = "SELECT
            {$temp_thread_post_fields},
            {$temp_user_fields},
            {$temp_user_role_fields},
            {$temp_thread_category_fields}
            FROM mmrpg_posts AS posts
            LEFT JOIN mmrpg_users AS users ON posts.user_id = users.user_id
            LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id
            LEFT JOIN mmrpg_categories AS categories ON posts.category_id = categories.category_id
            LEFT JOIN mmrpg_threads AS threads ON threads.thread_id = posts.thread_id
            WHERE threads.category_id <> 0 AND posts.post_deleted = 0
            {$temp_filter_by}
            AND 1 = 1
            ORDER BY {$temp_order_by}
            ;";
        $post_search_array = $db->get_array_list($post_search_query);
        $post_search_count = !empty($post_search_array) ? count($post_search_array) : 0;
    }

    // Loop through the posts results and collect required IDs
    if (!empty($post_search_array)){
        foreach ($post_search_array AS $key => $info){
            if (!in_array($info['post_id'], $post_index_array_required)){ $post_index_array_required[] = $info['post_id']; }
            if (!in_array($info['thread_id'], $thread_index_array_required)){ $thread_index_array_required[] = $info['thread_id']; }
            if (!isset($post_index_array[$info['post_id']])){ $post_index_array[$info['post_id']] = $info; }
        }
    }

}


// -- SEARCH MATCHING THREADS -- //

// Search the database for matching posts
if (true){

    // Default the filter variable to empty
    $temp_filter_by = '';

    // Add TEXT variables to the filter if provided in the header
    if (!empty($_REQUEST['text'])){
        $temp_text = strip_tags(strtolower($_REQUEST['text']));
        $temp_text = trim(str_replace('/[^-_a-z0-9\s*]+/i', '', $temp_text));
        if (!empty($temp_text)){
            $temp_filter_data['text'] = $temp_text;
            $temp_text = str_replace('*', '%', $temp_text);
            $temp_limit = !empty($_REQUEST['text_limit']) ? $_REQUEST['text_limit'] : 'all';
            $temp_filter_data['text_limit'] = $temp_limit;
            if ($temp_limit == 'all'){ $temp_filter_by .= 'AND (threads.thread_name LIKE \'%'.$temp_text.'%\' OR threads.thread_body LIKE \'%'.$temp_text.'%\') '; }
            elseif ($temp_limit == 'name'){ $temp_filter_by .= 'AND threads.thread_name LIKE \'%'.$temp_text.'%\' '; }
            elseif ($temp_limit == 'body'){ $temp_filter_by .= 'AND threads.thread_body LIKE \'%'.$temp_text.'%\' '; }
        }
    }

    // Add PLAYER variables to the filter if provided in the header
    if (!empty($_REQUEST['player'])){
        $temp_player = strip_tags(strtolower($_REQUEST['player']));
        $temp_player = trim(str_replace('/[^-_a-z0-9\s]+/i', '', $temp_player));
        if (!empty($temp_player)){
            $temp_filter_data['player'] = $temp_player;
            $temp_player = str_replace(' ', '%', $temp_player);
            $temp_strict = !empty($_REQUEST['player_strict']) ? true : false;
            $temp_filter_data['player_strict'] = $temp_strict;
            if ($temp_strict == true){ $temp_filter_by .= 'AND (users.user_name LIKE \''.$temp_player.'\' OR users.user_name_public LIKE \''.$temp_player.'\') '; }
            elseif ($temp_strict == false){ $temp_filter_by .= 'AND (users.user_name LIKE \'%'.$temp_player.'%\' OR users.user_name_public LIKE \'%'.$temp_player.'%\') '; }
        }
    }

    // Add CATEGORY variables to the filter if provided in the header
    if (!empty($_REQUEST['category'])){
        $temp_id_includes = array();
        $temp_category_tokens = is_array($_REQUEST['category']) ? $_REQUEST['category'] : explode(',', $_REQUEST['category']);
        foreach ($temp_category_tokens AS $temp_cat_token){
            if (!empty($this_categories_index[$temp_cat_token])){
                $temp_id_includes[] = $this_categories_index[$temp_cat_token]['category_id'];
            }
        }
        if (!empty($temp_id_includes)){
            $temp_filter_by .= 'AND threads.category_id IN ('.implode(',', $temp_id_includes).') ';
            $temp_filter_data['category'] = $temp_category_tokens;
        }
    }

    // Collect all the threads matching this query from the database
    if (!empty($temp_filter_data['text']) || !empty($temp_filter_data['player'])){
        $temp_order_by = 'threads.thread_date '.strtoupper($temp_filter_data['sort']);
        $thread_search_query = "SELECT
            {$temp_thread_fields},
            {$temp_thread_category_fields},
            {$temp_user_fields},
            {$temp_user_role_fields},
            users2.mod_user_id,
                users2.mod_user_name,
                users2.mod_user_name_public,
                users2.mod_user_name_clean,
                users2.mod_user_colour_token,
            users3.target_user_id,
                users3.target_user_name,
                users3.target_user_name_public,
                users3.target_user_name_clean,
                users3.target_user_colour_token,
                users3.target_user_image_path,
                users3.target_user_background_path,
            posts.post_count
            FROM mmrpg_threads AS threads
            LEFT JOIN mmrpg_users AS users ON threads.user_id = users.user_id
            LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id
            LEFT JOIN (SELECT
                user_id AS mod_user_id,
                user_name AS mod_user_name,
                user_name_public AS mod_user_name_public,
                user_name_clean AS mod_user_name_clean,
                user_colour_token AS mod_user_colour_token
                FROM mmrpg_users) AS users2 ON threads.thread_mod_user = users2.mod_user_id
            LEFT JOIN (SELECT
                user_id AS target_user_id,
                user_name AS target_user_name,
                user_name_public AS target_user_name_public,
                user_name_clean AS target_user_name_clean,
                user_colour_token AS target_user_colour_token,
                user_image_path AS target_user_image_path,
                user_background_path AS target_user_background_path
                FROM mmrpg_users) AS users3 ON threads.thread_target = users3.target_user_id
            LEFT JOIN mmrpg_categories AS categories ON threads.category_id = categories.category_id
            LEFT JOIN (
                SELECT posts.thread_id, count(1) AS post_count
                FROM mmrpg_posts AS posts
                GROUP BY posts.thread_id) AS posts ON threads.thread_id = posts.thread_id
            WHERE threads.category_id <> 0 AND threads.thread_published = 1
            {$temp_filter_by}
            AND 1 = 1
            ORDER BY {$temp_order_by}
            ;";
        $thread_search_array = $db->get_array_list($thread_search_query);
        $thread_search_count = !empty($thread_search_array) ? count($thread_search_array) : 0;
    }

    // Loop through the threads results and collect required IDs
    if (!empty($thread_search_array)){
        foreach ($thread_search_array AS $key => $info){
            if (!in_array($info['thread_id'], $thread_index_array_required)){ $thread_index_array_required[] = $info['thread_id']; }
            if (!isset($thread_index_array[$info['thread_id']])){ $thread_index_array[$info['thread_id']] = $info; }
        }
    }

}

// -- POPULATE THREAD INDEX -- //

if (count($thread_index_array) < count($thread_index_array_required)){

    // Collect the IDs that are still pending to be collected
    $temp_id_complete = array_keys($thread_index_array);
    $temp_id_pending = array_diff($thread_index_array_required, $temp_id_complete);

    // Collect all the threads matching these IDs from the database
    $temp_order_by = 'threads.thread_date '.strtoupper($temp_filter_data['sort']);
    $temp_filter_by = 'AND threads.thread_id IN ('.implode(',', $temp_id_pending).') ';
    $temp_index_query = "SELECT
        {$temp_thread_fields},
        {$temp_thread_category_fields},
        {$temp_user_fields},
        {$temp_user_role_fields},
        users2.mod_user_id,
            users2.mod_user_name,
            users2.mod_user_name_public,
            users2.mod_user_name_clean,
            users2.mod_user_colour_token,
        users3.target_user_id,
            users3.target_user_name,
            users3.target_user_name_public,
            users3.target_user_name_clean,
            users3.target_user_colour_token,
            users3.target_user_image_path,
            users3.target_user_background_path,
        posts.post_count
        FROM mmrpg_threads AS threads
        LEFT JOIN mmrpg_users AS users ON threads.user_id = users.user_id
        LEFT JOIN mmrpg_roles AS roles ON roles.role_id = users.role_id
        LEFT JOIN (SELECT
            user_id AS mod_user_id,
            user_name AS mod_user_name,
            user_name_public AS mod_user_name_public,
            user_name_clean AS mod_user_name_clean,
            user_colour_token AS mod_user_colour_token
            FROM mmrpg_users) AS users2 ON threads.thread_mod_user = users2.mod_user_id
        LEFT JOIN (SELECT
            user_id AS target_user_id,
            user_name AS target_user_name,
            user_name_public AS target_user_name_public,
            user_name_clean AS target_user_name_clean,
            user_colour_token AS target_user_colour_token,
            user_image_path AS target_user_image_path,
            user_background_path AS target_user_background_path
            FROM mmrpg_users) AS users3 ON threads.thread_target = users3.target_user_id
        LEFT JOIN mmrpg_categories AS categories ON threads.category_id = categories.category_id
        LEFT JOIN (
            SELECT posts.thread_id, count(1) AS post_count
            FROM mmrpg_posts AS posts
            GROUP BY posts.thread_id) AS posts ON threads.thread_id = posts.thread_id
        WHERE threads.category_id <> 0 AND threads.thread_published = 1
        {$temp_filter_by}
        AND 1 = 1
        ORDER BY {$temp_order_by}
        ;";
    $temp_index_array = $db->get_array_list($temp_index_query);
    $temp_index_count = !empty($temp_index_array) ? count($temp_index_array) : 0;

    // If the thread indexes were found, add to main index
    if (!empty($temp_index_array)){
        foreach ($temp_index_array AS $key => $info){
            if (!isset($thread_index_array[$info['thread_id']])){ $thread_index_array[$info['thread_id']] = $info; }
            unset($temp_index_array[$key]);
        }
    }

}

// -- COLLECT USER COUNTS -- //

// Define the array to hold user IDs
$this_user_ids_array = array();

// If the there were posts, loop through and collect users
if (!empty($post_index_array)){
  foreach ($post_index_array AS $key => $info){
    if (!in_array($info['user_id'], $this_user_ids_array)){
      $this_user_ids_array[] = $info['user_id'];
    }
  }
}

// If the there were threads, loop through and collect users
if (!empty($thread_index_array)){
  foreach ($thread_index_array AS $key => $info){
    if (!in_array($info['user_id'], $this_user_ids_array)){
      $this_user_ids_array[] = $info['user_id'];
    }
  }
}

// Collect the user post and thread count index plus leaderboard points for display
$temp_id_includes = !empty($this_user_ids_array) ? 'AND mmrpg_users.user_id IN ('.implode(', ', $this_user_ids_array).')' : '';
if (!empty($temp_id_includes)){
  $this_user_countindex = $db->get_array_list("SELECT
    mmrpg_users.user_id,
    mmrpg_leaderboard.board_points,
    mmrpg_threads.thread_count,
    mmrpg_posts.post_count
    FROM mmrpg_users
    LEFT JOIN mmrpg_leaderboard ON mmrpg_leaderboard.user_id = mmrpg_users.user_id
    LEFT JOIN (
        SELECT user_id, COUNT(thread_id) AS thread_count FROM mmrpg_threads WHERE mmrpg_threads.thread_target = 0 AND thread_published = 1 GROUP BY mmrpg_threads.user_id
        ) mmrpg_threads ON mmrpg_threads.user_id = mmrpg_users.user_id
    LEFT JOIN (
        SELECT user_id, COUNT(post_id) AS post_count FROM mmrpg_posts WHERE mmrpg_posts.post_target = 0 AND post_deleted = 0 GROUP BY mmrpg_posts.user_id
        ) mmrpg_posts ON mmrpg_posts.user_id = mmrpg_users.user_id
    WHERE
    mmrpg_leaderboard.board_points > 0
    {$temp_id_includes}
    ;", 'user_id');
} else {
  $this_user_countindex = array();
}


// -- COLLECT DISPLAY POSTS -- //

// If the user has requested to view results as threads
if ($temp_filter_data['display'] == 'posts'){

    // If the user has requested only searching threads
    if ($temp_filter_data['limit'] == 'threads'){

        // Add the searched for threads to the main array
        if (!empty($thread_search_array)){
            foreach ($thread_search_array AS $key => $info){
                $new_key = count($this_posts_array);
                $new_post_array = array();
                foreach ($info AS $field => $value){
                    if ($field == 'thread_id'){ $value = $value.'_01'; }
                    $new_field = preg_replace('/^thread_/', 'post_', $field);
                    $new_post_array[$new_field] = $value;
                }
                $new_post_array['thread_id'] = $info['thread_id'];
                $new_post_array['post_is_thread'] = 1;
                $this_posts_array[] = $new_post_array;
                $this_posts_count++;
            }
        }

    }
    // Else if the user has requested only searching posts
    elseif ($temp_filter_data['limit'] == 'posts'){

        // Add the searched for posts to the main array
        if (!empty($post_search_array)){
            foreach ($post_search_array AS $key => $info){
                $new_post_array = $post_index_array[$info['post_id']];
                $new_post_array['post_is_thread'] = 1;
                $this_posts_array[] = $new_post_array;
                $this_posts_count++;
            }
        }

    }
    // Else if the user wants to see everything
    else {

        // Add the searched for threads to the main array
        if (!empty($thread_search_array)){
            foreach ($thread_search_array AS $key => $info){
                $new_key = count($this_posts_array);
                $new_post_array = array();
                foreach ($info AS $field => $value){
                    if ($field == 'thread_id'){ $value = $value.'_01'; }
                    $new_field = preg_replace('/^thread_/', 'post_', $field);
                    $new_post_array[$new_field] = $value;
                }
                $new_post_array['thread_id'] = $info['thread_id'];
                $new_post_array['post_is_thread'] = 1;
                $this_posts_array[] = $new_post_array;
                $this_posts_count++;
            }
        }
        // Add the searched for posts to the main array
        if (!empty($post_search_array)){
            foreach ($post_search_array AS $key => $info){
                $this_posts_array[] = $post_index_array[$info['post_id']];
                $this_posts_count++;
            }
        }

    }

}


// -- COLLECT DISPLAY THREADS -- //

// If the user has requested to view results as threads
if ($temp_filter_data['display'] == 'threads'){

    // If the user has requested only searching threads
    if ($temp_filter_data['limit'] == 'threads'){

        // Add the searched for threads to the main array
        if (!empty($thread_search_array)){
            $temp_thread_ids = array();
            foreach ($thread_search_array AS $key => $info){
                if (in_array($info['thread_id'], $temp_thread_ids)){ continue; }
                $this_threads_array[] = $thread_index_array[$info['thread_id']];
                $temp_thread_ids[] = $info['thread_id'];
                $this_threads_count++;
            }
        }

    }
    // Else if the user has requested only searching posts
    elseif ($temp_filter_data['limit'] == 'posts'){

        // Add the searched for posts to the main array
        if (!empty($post_search_array)){
            $temp_thread_ids = array();
            foreach ($post_search_array AS $key => $info){
                if (in_array($info['thread_id'], $temp_thread_ids)){ continue; }
                $this_threads_array[] = $thread_index_array[$info['thread_id']];
                $temp_thread_ids[] = $info['thread_id'];
                $this_threads_count++;
            }
        }

    }
    // Else if the user wants to see everything
    else {

        // Add the searched for threads to the main array
        if (!empty($thread_search_array)){
            $temp_thread_ids = array();
            foreach ($thread_search_array AS $key => $info){
                if (in_array($info['thread_id'], $temp_thread_ids)){ continue; }
                $this_threads_array[] = $thread_index_array[$info['thread_id']];
                $temp_thread_ids[] = $info['thread_id'];
                $this_threads_count++;
            }
        }
        // Add the searched for posts to the main array
        if (!empty($post_search_array)){
            $temp_thread_ids = array();
            foreach ($post_search_array AS $key => $info){
                if (in_array($info['thread_id'], $temp_thread_ids)){ continue; }
                $this_threads_array[] = $thread_index_array[$info['thread_id']];
                $temp_thread_ids[] = $info['thread_id'];
                $this_threads_count++;
            }
        }

    }

}

// Parse the pseudo-code tag <!-- MMRPG_COMMUNITY_SEARCH_FORM_MARKUP -->
$find = '<!-- MMRPG_COMMUNITY_SEARCH_FORM_MARKUP -->';
if (strstr($page_content_parsed, $find)){
    ob_start();

    ?>
    <div class="subbody">
        <form class="search_form" method="get">

            <div class="section left">
                <h3 class="subheader field_type_empty">Search by Keyword</h3>
                <div class="field field_text">
                    <input class="textinput" type="text" name="text" value="<?= !empty($temp_filter_data['text']) ? $temp_filter_data['text'] : '' ?>" style="width: 98%; " />
                </div>
                <div class="field field_limit">
                    <div class="option option_all">
                        <input class="radio" type="radio" id="option_limit_all" name="text_limit" value="all" <?= empty($temp_filter_data['text_limit']) || $temp_filter_data['text_limit'] == 'all' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_limit_all">Search entire post</label>
                    </div>
                    <div class="option option_name">
                        <input class="radio" type="radio" id="option_limit_name" name="text_limit" value="name" <?= !empty($temp_filter_data['text_limit']) && $temp_filter_data['text_limit'] == 'name' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_limit_name">Search titles only</label>
                    </div>
                    <div class="option option_body">
                        <input class="radio" type="radio" id="option_limit_body" name="text_limit" value="body" <?= !empty($temp_filter_data['text_limit']) && $temp_filter_data['text_limit'] == 'body' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_limit_body">Search content only</label>
                    </div>
                </div>
            </div>

            <div class="section right">
                <h3 class="subheader field_type_empty">Search by Username</h3>
                <div class="field field_player">
                    <input class="textinput" type="text" name="player" value="<?= !empty($temp_filter_data['player']) ? $temp_filter_data['player'] : '' ?>" style="width: 98%; " />
                </div>
                <div class="field field_strict">
                    <div class="option option_true">
                        <input class="checkbox" type="checkbox" id="option_strict_true" name="player_strict" value="true" <?= !empty($temp_filter_data['player_strict']) ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_strict_true">Match exact username</label>
                    </div>
                </div>
            </div>

            <div class="section left">
                <h3 class="subheader field_type_empty">Filter by Category</h3>
                <div class="field field_category">
                    <?
                    // Loop through and generate categories as options
                    $category_options = array();
                    $category_options[] = '<option value=""'.(empty($temp_filter_data['category']) ? ' selected="selected"' : '').'>Any Category</option>';
                    $category_options[] = '<option value="" disabled="disabled">----------</option>';
                    if (!empty($this_categories_index)){
                        foreach ($this_categories_index AS $token => $info){
                            if (in_array($token, array('personal', 'chat', 'search'))){ continue; }
                            $temp_selected = !empty($temp_filter_data['category']) && (in_array($token, $temp_filter_data['category']) || $temp_filter_data['category'] == $token)  ? ' selected="selected"' : '';
                            $category_options[] = '<option value="'.$token.'"'.$temp_selected.'>'.$info['category_name'].'</option>';
                        }
                    }
                    ?>
                    <select class="select" name="category[]" size="<?= count($category_options) ?>" multiple="multiple">
                        <?= implode(PHP_EOL, $category_options) ?>
                    </select>
                </div>
            </div>

            <div class="section right">
                <h3 class="subheader field_type_empty">Search Options</h3>
                <div class="field field_display">
                    <div class="option option_threads">
                        <input class="radio" type="radio" id="option_display_threads" name="display" value="threads" <?= empty($temp_filter_data['display']) || $temp_filter_data['display'] == 'threads' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_display_threads">Display results as threads</label>
                    </div>
                    <div class="option option_posts">
                        <input class="radio" type="radio" id="option_display_posts" name="display" value="posts" <?= !empty($temp_filter_data['display']) && $temp_filter_data['display'] == 'posts' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_display_posts">Display results as posts</label>
                    </div>
                </div>
                <hr class="divider" />
                <div class="field field_sort">
                    <div class="option option_desc">
                        <input class="radio" type="radio" id="option_sort_desc" name="sort" value="desc" <?= empty($temp_filter_data['sort']) || $temp_filter_data['sort'] == 'desc' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_sort_desc">Sort results by newest first</label>
                    </div>
                    <div class="option option_asc">
                        <input class="radio" type="radio" id="option_sort_asc" name="sort" value="asc" <?= !empty($temp_filter_data['sort']) && $temp_filter_data['sort'] == 'asc' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_sort_asc">Sort results by oldest first</label>
                    </div>
                </div>
                <hr class="divider" />
                <div class="field field_limit">
                    <div class="option option_threads">
                        <input class="radio" type="radio" id="option_limit_all" name="limit" value="all" <?= empty($temp_filter_data['limit']) || $temp_filter_data['limit'] == 'all' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_limit_all">Do not limit my search results</label>
                    </div>
                    <div class="option option_threads">
                        <input class="radio" type="radio" id="option_limit_threads" name="limit" value="threads" <?= !empty($temp_filter_data['limit']) && $temp_filter_data['limit'] == 'threads' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_limit_threads">Limit search to threads only</label>
                    </div>
                    <div class="option option_posts">
                        <input class="radio" type="radio" id="option_limit_posts" name="limit" value="posts" <?= !empty($temp_filter_data['limit']) && $temp_filter_data['limit'] == 'posts' ? 'checked="checked"' : '' ?> />
                        <label class="label" for="option_limit_posts">Limit search to posts only</label>
                    </div>
                    <?/*
                    <hr class="divider" />
                    <div class="field field_count">
                        <select class="select" name="count">
                            <option value="50" <?= empty($temp_filter_data['count']) || $temp_filter_data['count'] == 50 ? 'selected="selected"' : '' ?>>Show 50 Results</option>
                            <option value="100" <?= !empty($temp_filter_data['count']) && $temp_filter_data['count'] == 100 ? 'selected="selected"' : '' ?>>Show 100 Results</option>
                            <option value="250" <?= !empty($temp_filter_data['count']) && $temp_filter_data['count'] == 250 ? 'selected="selected"' : '' ?>>Show 250 Results</option>
                            <option value="500" <?= !empty($temp_filter_data['count']) && $temp_filter_data['count'] == 500 ? 'selected="selected"' : '' ?>>Show 500 Results</option>
                            <option value="all" <?= !empty($temp_filter_data['count']) && $temp_filter_data['count'] == 'all' ? 'selected="selected"' : '' ?>>Show All Results</option>
                        </select>
                    </div>
                    */?>
                </div>
            </div>

            <?
            // Collect the current page num for the search results, and define min/max keys
            $search_page_num =  !empty($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ? $_REQUEST['pg'] : 1;
            $search_page_result_count = $temp_filter_data['display'] == 'threads' ? $this_threads_count : $this_posts_count;
            $search_page_result_limit = $temp_filter_data['display'] == 'threads' ? MMRPG_SETTINGS_THREADS_PERPAGE : MMRPG_SETTINGS_POSTS_PERPAGE;
            $search_page_link_count = $search_page_result_count > $search_page_result_limit ? ceil($search_page_result_count / $search_page_result_limit) : 1;
            $search_page_result_key_start = ($search_page_num * $search_page_result_limit) - $search_page_result_limit;
            $search_page_result_key_break = $search_page_result_key_start + $search_page_result_limit - 1;
            ?>

            <div class="buttons" style="float: none; clear: left; text-align: center; margin-top: 20px;">
                <?/*<input class="hidden" type="hidden" name="pg" value="<?= $search_page_num ?>" />*/?>
                <input class="button submit" type="submit" value="Search" />
                <a class="button reset" href="<?= $_GET['this_current_url'] ?>">Reset</a>
            </div>

            <? if (!empty($search_page_result_count)): ?>
                <div class="results" style="font-size: 120%; ">
                    <div class="count"><?
                        echo 'Found ';
                        if ($temp_filter_data['limit'] == 'threads'){ echo $thread_search_count.' Threads'; }
                        elseif ($temp_filter_data['limit'] == 'posts'){ echo $post_search_count.' Posts'; }
                        else {
                            if (!empty($thread_search_count)){ echo $thread_search_count.' Threads'; }
                            if (!empty($thread_search_count) && !empty($post_search_count)){ echo ', '; }
                            if (!empty($post_search_count)){ echo $post_search_count.' Posts'; }
                        }
                        echo ' <span class="total">'.$search_page_result_count.' Results Total</span>';
                    ?></div>
                    <? if($search_page_link_count > 1): ?>
                        <div class="pages">
                            <span class="label">Pages</span>
                            <?
                            // Gather all the other fields into a single query string
                            $temp_query_string = array();
                            if (isset($temp_filter_data['text'])){ $temp_query_string[] = 'text='.$temp_filter_data['text']; }
                            if (isset($temp_filter_data['text_limit'])){ $temp_query_string[] = 'text_limit='.$temp_filter_data['text_limit']; }
                            if (isset($temp_filter_data['player'])){ $temp_query_string[] = 'player='.$temp_filter_data['player']; }
                            if (isset($temp_filter_data['category'])){ $temp_query_string[] = 'category='.(is_array($temp_filter_data['category']) ? implode(',', $temp_filter_data['category']) : $temp_filter_data['category']); }
                            if (isset($temp_filter_data['display'])){ $temp_query_string[] = 'display='.$temp_filter_data['display']; }
                            if (isset($temp_filter_data['sort'])){ $temp_query_string[] = 'sort='.$temp_filter_data['sort']; }
                            if (isset($temp_filter_data['limit'])){ $temp_query_string[] = 'limit='.$temp_filter_data['limit']; }
                            $temp_query_string = implode('&amp;', $temp_query_string);
                            // Loop through and print links for page nums
                            for ($num = 1; $num <= $search_page_link_count; $num++){
                                $class = 'link'.($num == $search_page_num ? ' active' : '');
                                $href = $_GET['this_current_url'].'?'.$temp_query_string.'&amp;pg='.$num;
                                echo '<a class="'.$class.'" href="'.$href.'">'.$num.'</a>';
                            }
                            ?>
                        </div>
                    <? endif; ?>
                </div>
            <? endif; ?>

        </form>
    </div>
    <?

    // If there are results to display from the search, show them now
    if (!empty($_REQUEST['display'])){

        // Else if the display filter is set to display threads
        if ($temp_filter_data['display'] == 'threads'){


            // Loop through the thread array and display its contents
            if (!empty($this_threads_array)){
                foreach ($this_threads_array AS $this_thread_key => $this_thread_info){

                    //error_log('$this_thread_info = '.print_r($this_thread_info, true));

                    // Check the key to see if we should display this result
                    if ($this_thread_key < $search_page_result_key_start){ continue; }
                    elseif ($this_thread_key >= $search_page_result_key_break){ break; }

                    // Collect markup for this thread from the function
                    $this_category_info = $this_categories_index[$this_thread_info['category_token']];
                    $temp_markup = mmrpg_website_community_thread_linkblock($this_thread_key, $this_thread_info, false, false, true);
                    echo $temp_markup."\n";

                }
            } else {
                ?>
                <div class="subbody">
                    <? if (!empty($temp_filter_data['limit']) && $temp_filter_data['limit'] == 'posts'): ?>
                        <p class="text">- there are no posts to display -</p>
                    <? elseif (!empty($temp_filter_data['limit']) && $temp_filter_data['limit'] == 'threads'): ?>
                        <p class="text">- there are no threads to display -</p>
                    <? else : ?>
                        <p class="text">- there are no results to display -</p>
                    <? endif; ?>
                </div>
                <?
            }

        }
        // Otherwise, if the display filter is set to display posts
        elseif ($temp_filter_data['display'] == 'posts'){

            // Loop through the post array and display its contents
            if (!empty($this_posts_array)){
                foreach ($this_posts_array AS $this_post_key => $this_post_info){

                    // Check the key to see if we should display this result
                    if ($this_post_key < $search_page_result_key_start){ continue; }
                    elseif ($this_post_key >= $search_page_result_key_break){ break; }

                    // Collect markup for this post from the function
                    $temp_thread_info = $thread_index_array[$this_post_info['thread_id']];
                    $this_category_info = $this_categories_index[$temp_thread_info['category_token']];
                    $temp_markup = mmrpg_website_community_postblock($this_post_key, $this_post_info, $temp_thread_info, $this_category_info);
                    echo $temp_markup."\n";

                }
            } else {
                ?>
                <div class="subbody">
                    <? if (!empty($temp_filter_data['limit']) && $temp_filter_data['limit'] == 'posts'): ?>
                        <p class="text">- there are no posts to display -</p>
                    <? elseif (!empty($temp_filter_data['limit']) && $temp_filter_data['limit'] == 'threads'): ?>
                        <p class="text">- there are no threads to display -</p>
                    <? else : ?>
                        <p class="text">- there are no results to display -</p>
                    <? endif; ?>
                </div>
                <?
            }

        }

    }

    $replace = ob_get_clean();
    $page_content_parsed = str_replace($find, $replace, $page_content_parsed);
}

?>