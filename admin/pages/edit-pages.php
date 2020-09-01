<? ob_start(); ?>

    <?

    /* -- Collect Dependant Indexes -- */

    // Collect an index of all existing pages for reference
    $mmrpg_pages_fields = cms_website_page::get_index_fields(true);
    $mmrpg_website_pages_index = $db->get_array_list("SELECT {$mmrpg_pages_fields} FROM mmrpg_website_pages WHERE page_id <> 0;", 'page_id');

    // Pre-check access permissions before continuing
    if (!in_array('*', $this_adminaccess)
        && !in_array('edit-pages', $this_adminaccess)){
        $form_messages[] = array('error', 'You do not have permission to edit pages!');
        redirect_form_action('admin/home/');
    }

    // Collect an index of file changes and updates via git
    $mmrpg_git_file_arrays = cms_admin::object_editor_get_git_file_arrays(MMRPG_CONFIG_PAGES_CONTENT_PATH, array(
        'table' => 'mmrpg_website_pages',
        'url' => 'page_url'
        ), 'url');

    // Explode the list of git files into separate array vars
    extract($mmrpg_git_file_arrays);

    /* -- Page Script/Style Dependencies  -- */

    // Define the extra stylesheets that must be included for this page
    if (!isset($admin_include_stylesheets)){ $admin_include_stylesheets = ''; }
    $admin_include_stylesheets .= '<link rel="stylesheet" href=".libs/codemirror/lib/codemirror.css?'.MMRPG_CONFIG_CACHE_DATE.'">'.PHP_EOL;

    // Define the extra javascript that must be included for this page
    if (!isset($admin_include_javascript)){ $admin_include_javascript = ''; }
    $admin_include_javascript .= '<script type="text/javascript" src=".libs/codemirror/lib/codemirror.js?'.MMRPG_CONFIG_CACHE_DATE.'"></script>'.PHP_EOL;
    $admin_include_javascript .= '<script type="text/javascript" src=".libs/codemirror/mode/xml/xml.js?'.MMRPG_CONFIG_CACHE_DATE.'"></script>'.PHP_EOL;
    $admin_include_javascript .= '<script type="text/javascript" src=".libs/codemirror/mode/css/css.js?'.MMRPG_CONFIG_CACHE_DATE.'"></script>'.PHP_EOL;
    $admin_include_javascript .= '<script type="text/javascript" src=".libs/codemirror/mode/javascript/javascript.js?'.MMRPG_CONFIG_CACHE_DATE.'"></script>'.PHP_EOL;
    $admin_include_javascript .= '<script type="text/javascript" src=".libs/codemirror/mode/htmlmixed/htmlmixed.js?'.MMRPG_CONFIG_CACHE_DATE.'"></script>'.PHP_EOL;


    /* -- Form Setup Actions -- */

    // Define a function for exiting a page edit action
    function exit_page_edit_action($page_id = 0){
        if (!empty($page_id)){ $location = 'admin/edit-pages/editor/page_id='.$page_id; }
        else { $location = 'admin/edit-pages/search/'; }
        redirect_form_action($location);
    }


    /* -- Admin Subpage Processing -- */

    // Collect or define current subaction
    $sub_action =  !empty($_GET['subaction']) ? $_GET['subaction'] : 'search';

    // Update the tab name with the page name
    $this_page_tabtitle = 'Edit Pages | '.$this_page_tabtitle;

    // If we're in delete mode, we need to remove some data
    $delete_data = array();
    if (false && $sub_action == 'delete' && !empty($_GET['page_id'])){

        // Collect form data for processing
        $delete_data['page_id'] = !empty($_GET['page_id']) && is_numeric($_GET['page_id']) ? trim($_GET['page_id']) : '';

        // Let's delete all of this page's data from the database
        $db->delete('mmrpg_website_pages', array('page_id' => $delete_data['page_id']));
        $form_messages[] = array('success', 'The requested page has been deleted from the database');
        exit_form_action('success');

    }

    // If we're in search mode, we might need to scan for results
    $search_data = array();
    $search_query = '';
    $search_results = array();
    $search_results_count = 0;
    $search_results_limit = 50;
    if ($sub_action == 'search'){

        // Collect the sorting order and direction
        //$sort_data = array('name' => 'page_id', 'dir' => 'desc');
        //$sort_data = array('name' => 'parent_id', 'dir' => 'asc');
        //$sort_data = array('name' => 'page_order', 'dir' => 'asc');
        $sort_data = array('name' => 'page_rel_order', 'dir' => 'asc');
        if (!empty($_GET['order'])
            && preg_match('/^([-_a-z0-9]+)\:(desc|asc)$/i', $_GET['order'])){
            list($r_name, $r_dir) = explode(':', trim($_GET['order']));
            $sort_data = array('name' => $r_name, 'dir' => $r_dir);
        }

        // Collect form data for processing
        $search_data['page_id'] = !empty($_GET['page_id']) && is_numeric($_GET['page_id']) ? trim($_GET['page_id']) : '';
        $search_data['page_token'] = !empty($_GET['page_token']) && preg_match('/[-_0-9a-z\.\*]+/i', $_GET['page_token']) ? trim(strtolower($_GET['page_token'])) : '';
        $search_data['page_name'] = !empty($_GET['page_name']) && preg_match('/[-_0-9a-z\.\*\s]+/i', $_GET['page_name']) ? trim(strtolower($_GET['page_name'])) : '';
        $search_data['page_url'] = !empty($_GET['page_url']) && preg_match('/[-_0-9a-z\.\/\*]+/i', $_GET['page_url']) ? trim(strtolower($_GET['page_url'])) : '';
        $search_data['page_title'] = !empty($_GET['page_title']) && preg_match('/[-_0-9a-z\.\*\s\&\!\?\$\/]+/i', $_GET['page_title']) ? trim(strtolower($_GET['page_title'])) : '';
        $search_data['page_content'] = !empty($_GET['page_content']) && preg_match('/[-_0-9a-z\.\*\s\&\!\?\$\/]+/i', $_GET['page_content']) ? trim(strtolower($_GET['page_content'])) : '';
        $search_data['page_flag_published'] = isset($_GET['page_flag_published']) && $_GET['page_flag_published'] !== '' ? (!empty($_GET['page_flag_published']) ? 1 : 0) : '';
        $search_data['page_flag_hidden'] = isset($_GET['page_flag_hidden']) && $_GET['page_flag_hidden'] !== '' ? (!empty($_GET['page_flag_hidden']) ? 1 : 0) : '';
        cms_admin::object_index_search_data_append_git_statuses($search_data, 'page');

        /* -- Collect Search Results -- */

        // Define the search query to use
        $search_query = "SELECT
            page.parent_id,
            page.page_id,
            page.page_token,
            page.page_name,
            page.page_url,
            page.page_title,
            page.page_content,
            page.page_seo_title,
            page.page_seo_description,
            page.page_seo_keywords,
            page.page_date_created,
            page.page_date_modified,
            page.page_flag_hidden,
            page.page_flag_published,
            page.page_order,
            (CASE
                WHEN page2.page_order IS NOT NULL
                    THEN CONCAT(page2.page_order, '_', page.page_order)
                ELSE page.page_order
            END) AS page_rel_order
            FROM mmrpg_website_pages AS page
            LEFT JOIN mmrpg_website_pages AS page2 ON page2.page_id = page.parent_id
            WHERE 1=1
            AND page.page_id <> 0
            ";

        // If the page ID was provided, we can search by exact match
        if (!empty($search_data['page_id'])){
            $page_id = $search_data['page_id'];
            $search_query .= "AND page_id = {$page_id} ";
            $search_results_limit = false;
        }

        // Else if the page name was provided, we can use wildcards
        if (!empty($search_data['page_name'])){
            $page_name = $search_data['page_name'];
            $page_name = str_replace(array(' ', '*', '%'), '%', $page_name);
            $page_name = preg_replace('/%+/', '%', $page_name);
            $page_name = '%'.$page_name.'%';
            $search_query .= "AND page_name LIKE '{$page_name}' ";
            $search_results_limit = false;
        }

        // Else if the page title was provided, we can use wildcards
        if (!empty($search_data['page_title'])){
            $page_title = $search_data['page_title'];
            $page_title = str_replace(array(' ', '*', '%'), '%', $page_title);
            $page_title = preg_replace('/%+/', '%', $page_title);
            $page_title = '%'.$page_title.'%';
            $search_query .= "AND page_title LIKE '{$page_title}' ";
            $search_results_limit = false;
        }

        // Else if the page content was provided, we can use wildcards
        if (!empty($search_data['page_content'])){
            $page_content = $search_data['page_content'];
            $page_content = str_replace(array(' ', '*', '%'), '%', $page_content);
            $page_content = preg_replace('/%+/', '%', $page_content);
            $page_content = '%'.$page_content.'%';
            $search_query .= "AND page_content LIKE '{$page_content}' ";
            $search_results_limit = false;
        }

        // Else if the page URL was provided, we can use wildcards
        if (!empty($search_data['page_url'])){
            $page_url = $search_data['page_url'];
            $page_url = str_replace(array(' ', '*', '%'), '%', $page_url);
            $page_url = preg_replace('/%+/', '%', $page_url);
            $page_url = '%'.$page_url.'%';
            $search_query .= "AND page_url LIKE '{$page_url}' ";
            $search_results_limit = false;
        }

        // If the page published flag was provided
        if ($search_data['page_flag_published'] !== ''){
            $search_query .= "AND page_flag_published = {$search_data['page_flag_published']} ";
            $search_results_limit = false;
        }

        // If the page post public flag was provided
        if ($search_data['page_flag_hidden'] !== ''){
            $search_query .= "AND page_flag_hidden = {$search_data['page_flag_hidden']} ";
            $search_results_limit = false;
        }

        // Append sorting parameters to the end of the query
        $order_by = array();
        if (!empty($sort_data)){ $order_by[] = $sort_data['name'].' '.strtoupper($sort_data['dir']); }
        $order_by[] = "page_rel_order ASC";
        //$order_by[] = "page_order ASC";
        $order_by[] = "page_name ASC";
        $order_by_string = implode(', ', $order_by);
        $search_query .= "ORDER BY {$order_by_string} ";

        // Impose a limit on the search results
        if (!empty($search_results_limit)){ $search_query .= "LIMIT {$search_results_limit} "; }

        // End the query now that we're done
        $search_query .= ";";

        // Collect search results from the database
        $search_results = $db->get_array_list($search_query);
        $search_results_count = is_array($search_results) ? count($search_results) : 0;
        cms_admin::object_index_search_results_filter_git_statuses($search_results, $search_results_count, $search_data, 'page', $mmrpg_git_file_arrays);

        // Collect a total number from the database
        $search_results_total = $db->get_value("SELECT COUNT(page_id) AS total FROM mmrpg_website_pages WHERE 1=1 AND page_id <> 0;", 'total');

    }

    // If we're in editor mode, we should collect page info from database
    $page_data = array();
    $editor_data = array();
    $is_backup_data = false;
    if ($sub_action == 'editor'
        && (!empty($_GET['page_id']) || !empty($_GET['backup_id']))){

        // Collect form data for processing
        $editor_data['page_id'] = !empty($_GET['page_id']) && is_numeric($_GET['page_id']) ? trim($_GET['page_id']) : '';
        if (empty($editor_data['page_id'])
            && !empty($_GET['backup_id'])
            && is_numeric($_GET['backup_id'])){
            $editor_data['backup_id'] = trim($_GET['backup_id']);
            $is_backup_data = true;
        }

        /* -- Collect Page Data -- */

        // Collect page details from the database
        $temp_page_fields = cms_website_page::get_fields(true);
        if (!$is_backup_data){
            $page_data = $db->get_array("SELECT {$temp_page_fields} FROM mmrpg_website_pages WHERE page_id = {$editor_data['page_id']};");
        } else {
            $temp_page_backup_fields = str_replace('page_id,', 'backup_id AS page_id,', $temp_page_fields);
            $temp_page_backup_fields .= ', backup_date_time';
            $page_data = $db->get_array("SELECT {$temp_page_backup_fields} FROM mmrpg_website_pages_backups WHERE backup_id = {$editor_data['backup_id']};");
        }

        // If page data could not be found, produce error and exit
        if (empty($page_data)){ exit_page_edit_action(); }

        // Collect the page's name(s) for display
        $page_name_display = $page_data['page_name'];
        $this_page_tabtitle = $page_name_display.' | '.$this_page_tabtitle;
        if ($is_backup_data){ $this_page_tabtitle = str_replace('Edit Pages', 'View Backups', $this_page_tabtitle); }

        // If form data has been submit for this page, we should process it
        $form_data = array();
        $form_success = true;
        $form_action = !empty($_POST['action']) ? trim($_POST['action']) : '';
        if ($form_action == 'edit-pages'){

            // Collect form data from the request and parse out simple rules

            $form_data['page_id'] = !empty($_POST['page_id']) && is_numeric($_POST['page_id']) ? trim($_POST['page_id']) : 0;
            $form_data['parent_id'] = !empty($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? trim($_POST['parent_id']) : 0;

            $form_data['page_token'] = !empty($_POST['page_token']) && preg_match('/^[-_0-9a-z\.]+$/i', $_POST['page_token']) ? trim(strtolower($_POST['page_token'])) : '';
            //$form_data['page_url'] = !empty($_POST['page_url']) && preg_match('/^[-_0-9a-z\.\/]+$/i', $_POST['page_url']) ? trim(strtolower($_POST['page_url'])) : '';
            $form_data['page_name'] = !empty($_POST['page_name']) && preg_match('/^[-_0-9a-z\.\*\s\&\!\?\$\/]+$/i', $_POST['page_name']) ? trim($_POST['page_name']) : '';
            $form_data['page_title'] = !empty($_POST['page_title']) && preg_match('/^[-_0-9a-z\.\*\s\&\!\?\$\/]+$/i', $_POST['page_title']) ? trim($_POST['page_title']) : '';
            $form_data['page_content'] = !empty($_POST['page_content']) ? trim($_POST['page_content']) : '';

            $form_data['page_seo_title'] = !empty($_POST['page_seo_title']) && preg_match('/^[-_0-9a-z\.\*\s\&\!\?\$\/]+$/i', $_POST['page_seo_title']) ? trim($_POST['page_seo_title']) : '';
            $form_data['page_seo_keywords'] = !empty($_POST['page_seo_keywords']) && preg_match('/^[-_0-9a-z\.\*\s\,]+$/i', $_POST['page_seo_keywords']) ? trim(strtolower($_POST['page_seo_keywords'])) : '';
            $form_data['page_seo_description'] = !empty($_POST['page_seo_description']) ? trim(strip_tags($_POST['page_seo_description'])) : '';

            $form_data['page_flag_published'] = isset($_POST['page_flag_published']) && is_numeric($_POST['page_flag_published']) ? trim($_POST['page_flag_published']) : 0;
            $form_data['page_flag_hidden'] = isset($_POST['page_flag_hidden']) && is_numeric($_POST['page_flag_hidden']) ? trim($_POST['page_flag_hidden']) : 0;

            $form_data['page_order'] = !empty($_POST['page_order']) && is_numeric($_POST['page_order']) ? trim($_POST['page_order']) : 0;

            // DEBUG
            //$form_messages[] = array('alert', '<pre>$_POST = '.print_r($_POST, true).'</pre>');

            // If the required USER ID field was empty, complete form failure
            if (empty($form_data['page_id'])){
                $form_messages[] = array('error', 'Page ID was not provided');
                $form_success = false;
            }

            // If the required PAGE TOKEN field was empty, complete form failure
            if (empty($form_data['page_token'])){
                $form_messages[] = array('error', 'Page token was not provided or was invalid');
                $form_success = false;
            }

            // If the required PAGE NAME field was empty, complete form failure
            if (empty($form_data['page_name'])){
                $form_messages[] = array('error', 'Page name was not provided or was invalid');
                $form_success = false;
            }

            // If the required PAGE URL field was empty, complete form failure
            // if (empty($form_data['page_url'])){
            //     $form_messages[] = array('error', 'Page URL was not provided or was invalid');
            //     $form_success = false;
            // }

            // If there were errors, we should exit now
            if (!$form_success){ exit_page_edit_action($form_data['page_id']); }

            // If trying to update the PAGE TITLE but it was invalid, do not update
            if (empty($form_data['page_title']) && !empty($_POST['page_title'])){
                $form_messages[] = array('warning', 'Page title was invalid and will not be updated');
                unset($form_data['page_title']);
            }

            // If trying to update the PAGE CONTENT but it was invalid, do not update
            if (empty($form_data['page_content']) && !empty($_POST['page_content'])){
                $form_messages[] = array('warning', 'Page content was invalid and will not be updated');
                unset($form_data['page_content']);
            }

            // Reformat the SEO keywords if provided
            if (!empty($form_data['page_seo_keywords'])){
                $seo_keywords = explode(',', $form_data['page_seo_keywords']);
                $seo_keywords = array_map(function($s){ return trim($s); }, $seo_keywords);
                $seo_keywords = array_unique($seo_keywords);
                $form_data['page_seo_keywords'] = implode(', ', $seo_keywords);
            }

            // Regenerate the URL based on the page token and parent
            if (!empty($form_data['page_token'])){
                $form_data['page_url'] = $form_data['page_token'].'/';
                if (!empty($form_data['parent_id'])
                    && !empty($mmrpg_website_pages_index[$form_data['parent_id']])){
                    $parent_page_url = $mmrpg_website_pages_index[$form_data['parent_id']]['page_url'];
                    if (!empty($parent_page_url)){ $form_data['page_url'] = rtrim($parent_page_url, '/').'/'.$form_data['page_url']; }
                }
            }

            // Loop through fields to create an update string
            $update_data = $form_data;
            $update_data['page_date_modified'] = time();
            unset($update_data['page_id']);
            $update_results = $db->update('mmrpg_website_pages', $update_data, array('page_id' => $form_data['page_id']));

            // If a recent backup of this data doesn't exist, create one now
            $backup_date_time = date('Ymd-Hi');
            $backup_exists = $db->get_value("SELECT backup_id FROM mmrpg_website_pages_backups WHERE page_id = '{$page_data['page_id']}' AND backup_date_time = '{$backup_date_time}';", 'backup_id');
            if (empty($backup_exists)){
                //$backup_data = array_merge($page_data, $update_data);
                $backup_data = $page_data;
                $backup_data['backup_date_time'] = $backup_date_time;
                $db->insert('mmrpg_website_pages_backups', $backup_data);
            }

            // DEBUG
            //$form_messages[] = array('alert', '<pre>$form_data = '.print_r($form_data, true).'</pre>');
            //$form_messages[] = array('alert', '<pre>$update_data = '.print_r($update_data, true).'</pre>');

            // If we made it this far, the update must have been a success
            if ($update_results !== false){ $form_messages[] = array('success', 'Page details were updated successfully'); }
            else { $form_messages[] = array('error', 'Page details could not be updated'); }

            // Update cache timestamp if changes were successful
            if ($form_success){
                list($date, $time) = explode('-', date('Ymd-Hi'));
                $db->update('mmrpg_config', array('config_value' => $date), "config_group = 'global' AND config_name = 'cache_date'");
                $db->update('mmrpg_config', array('config_value' => $time), "config_group = 'global' AND config_name = 'cache_time'");
            }

            // If successful, we need to update the JSON and HTML files
            if ($form_success){ cms_admin::object_editor_update_json_data_file('page', array_merge($page_data, $update_data)); }

            // We're done processing the form, we can exit
            exit_page_edit_action($form_data['page_id']);

            //echo('<pre>$form_action = '.print_r($form_action, true).'</pre>');
            //echo('<pre>$_POST = '.print_r($_POST, true).'</pre>');
            //exit();


        }

    }


    ?>

    <div class="breadcrumb">
        <a href="admin/">Admin Panel</a>
        &raquo; <a href="admin/edit-pages/">Edit Pages</a>
        <? if ($sub_action == 'editor' && !empty($page_data)): ?>
            <? if (!$is_backup_data){ ?>
                &raquo; <a href="admin/edit-pages/editor/page_id=<?= $page_data['page_id'] ?>"><?= $page_name_display ?></a>
            <? } else { ?>
                &raquo; <a><?= $page_name_display ?></a>
            <? } ?>
        <? endif; ?>
    </div>

    <?= !empty($this_error_markup) ? '<div style="margin: 0 auto 20px">'.$this_error_markup.'</div>' : '' ?>

    <div class="adminform edit-pages">

        <? if ($sub_action == 'search'): ?>

            <!-- SEARCH FORM -->

            <div class="search">

                <h3 class="header">Search Pages</h3>

                <? print_form_messages() ?>

                <form class="form" method="get">

                    <? /* <input type="hidden" name="action" value="edit-pages" /> */ ?>
                    <input type="hidden" name="subaction" value="search" />

                    <div class="field halfsize">
                        <strong class="label">By ID</strong>
                        <input class="textbox" type="text" name="page_id" value="<?= !empty($search_data['page_id']) ? $search_data['page_id'] : '' ?>" />
                    </div>

                    <div class="field halfsize">
                        <strong class="label">By Name </strong>
                        <input class="textbox" type="text" name="page_name" value="<?= !empty($search_data['page_name']) ? htmlentities($search_data['page_name'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>

                    <div class="field halfsize">
                        <strong class="label">By Title</strong>
                        <input class="textbox" type="text" name="page_title" value="<?= !empty($search_data['page_title']) ? htmlentities($search_data['page_title'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>

                    <div class="field halfsize">
                        <strong class="label">By URL</strong>
                        <input class="textbox" type="text" name="page_url" value="<?= !empty($search_data['page_url']) ? htmlentities($search_data['page_url'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>

                    <div class="field halfsize">
                        <strong class="label">By Content</strong>
                        <input class="textbox" type="text" name="page_content" value="<?= !empty($search_data['page_content']) ? htmlentities($search_data['page_content'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>

                    <div class="field fullsize has5cols flags">
                    <?
                    $flag_names = array(
                        'published' => array('icon' => 'fas fa-check-square', 'yes' => 'Published', 'no' => 'Unpublished'),
                        'hidden' => array('icon' => 'fas fa-eye-slash', 'yes' => 'Hidden', 'no' => 'Visible')
                        );
                    cms_admin::object_index_flag_names_append_git_statuses($flag_names);
                    foreach ($flag_names AS $flag_token => $flag_info){
                        if (isset($flag_info['break'])){ echo('<div class="break"></div>'); continue; }
                        $flag_name = 'page_flag_'.$flag_token;
                        $flag_label = isset($flag_info['label']) ? $flag_info['label'] : ucfirst($flag_token);
                        ?>
                        <div class="subfield">
                            <strong class="label"><?= $flag_label ?> <span class="<?= $flag_info['icon'] ?>"></span></strong>
                            <select class="select" name="<?= $flag_name ?>">
                                <option value=""<?= !isset($search_data[$flag_name]) || $search_data[$flag_name] === '' ? ' selected="selected"' : '' ?>></option>
                                <option value="1"<?= isset($search_data[$flag_name]) && $search_data[$flag_name] === 1 ? ' selected="selected"' : '' ?>><?= $flag_info['yes'] ?></option>
                                <option value="0"<?= isset($search_data[$flag_name]) && $search_data[$flag_name] === 0 ? ' selected="selected"' : '' ?>><?= $flag_info['no'] ?></option>
                            </select><span></span>
                        </div>
                        <?
                    }
                    ?>
                    </div>

                    <div class="buttons">
                        <input class="button" type="submit" value="Search" />
                        <input class="button" type="reset" value="Reset" onclick="javascript:window.location.href='admin/edit-pages/';" />
                    </div>

                </form>

            </div>

            <? if (!empty($search_results)): ?>

                <!-- SEARCH RESULTS -->

                <div class="results">

                    <table class="list" style="width: 100%;">
                        <colgroup>
                            <col class="id" width="60" />
                            <?/* <col class="token" width="80" /> */?>
                            <col class="name" width="100" />
                            <?/* <col class="title" width="" /> */?>
                            <col class="url" width="" />
                            <col class="date created" width="90" />
                            <col class="date modified" width="90" />
                            <col class="flag published" width="80" />
                            <col class="flag hidden" width="70" />
                            <col class="order" width="60" />
                            <col class="actions" width="100" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="id"><?= cms_admin::get_sort_link('page_id', 'ID') ?></th>
                                <?/* <th class="token"><?= cms_admin::get_sort_link('page_token', 'Token') ?></th> */ ?>
                                <th class="name"><?= cms_admin::get_sort_link('page_name', 'Name') ?></th>
                                <?/* <th class="title"><?= cms_admin::get_sort_link('page_name', 'Page Title') ?></th> */ ?>
                                <th class="url"><?= cms_admin::get_sort_link('page_url', 'URL') ?></th>
                                <th class="date created"><?= cms_admin::get_sort_link('page_date_created', 'Created') ?></th>
                                <th class="date modified"><?= cms_admin::get_sort_link('page_date_modified', 'Modified') ?></th>
                                <th class="flag published"><?= cms_admin::get_sort_link('page_flag_published', 'Published') ?></th>
                                <th class="flag hidden"><?= cms_admin::get_sort_link('page_flag_hidden', 'Hidden') ?></th>
                                <th class="order"><?= cms_admin::get_sort_link('page_rel_order', 'Order') ?></th>
                                <th class="actions">Actions</th>
                            </tr>
                            <tr>
                                <th class="head id"></th>
                                <?/* <th class="head token"></th> */ ?>
                                <th class="head name"></th>
                                <?/* <th class="head title"></th> */ ?>
                                <th class="head url"></th>
                                <th class="head date created"></th>
                                <th class="head date modified"></th>
                                <th class="head flag published"></th>
                                <th class="head flag hidden"></th>
                                <th class="head order"></th>
                                <th class="head count"><?= cms_admin::get_totals_markup() ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td class="foot id"></td>
                                <?/* <td class="foot token"></td> */ ?>
                                <td class="foot name"></td>
                                <?/* <td class="foot title"></td> */ ?>
                                <td class="foot url"></td>
                                <td class="foot date created"></td>
                                <td class="foot date modified"></td>
                                <td class="foot flag published"></td>
                                <td class="foot flag hidden"></td>
                                <td class="foot order"></td>
                                <td class="foot count"><?= cms_admin::get_totals_markup() ?></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?
                            $temp_class_colours = array(
                                'mecha' => array('speed', '<i class="fas fa-ghost"></i>'),
                                'master' => array('defense', '<i class="fas fa-robot"></i>'),
                                'boss' => array('space', '<i class="fas fa-skull"></i>')
                                );
                            foreach ($search_results AS $key => $page_data){

                                $page_id = $page_data['page_id'];
                                $page_token = $page_data['page_token'];
                                $page_url = $page_data['page_url'];
                                $page_name = $page_data['page_name'];
                                $page_title = $page_data['page_title'];
                                $page_order = $page_data['page_order'];
                                $page_rel_order = $page_data['page_rel_order'];
                                $page_created = !empty($page_data['page_date_created']) ? date('Y-m-d', $page_data['page_date_created']) : '-';
                                $page_modified = !empty($page_data['page_date_modified']) ? date('Y-m-d', $page_data['page_date_modified']) : '-';
                                $page_flag_published = !empty($page_data['page_flag_published']) ? '<i class="fas fa-check-square"></i>' : '-';
                                $page_flag_hidden = !empty($page_data['page_flag_hidden']) ? '<i class="fas fa-eye-slash"></i>' : '-';

                                // Collect the page's name(s) for display
                                //$page_name_display = $page_data['page_name'];
                                //if (!empty($page_data['page_title']) && $page_data['page_title'] != $page_data['page_name']){
                                //    $page_name_display = $page_name_display .' / '. $page_data['page_title'];
                                //}

                                $page_edit = 'admin/edit-pages/editor/page_id='.$page_id;
                                $page_view = MMRPG_CONFIG_ROOTURL.$page_url;

                                $page_actions = '';
                                $page_actions .= '<a class="link edit" href="'.$page_edit.'"><span>edit</span></a>';
                                $page_actions .= '<span class="link delete disabled"><span>delete</span></span>';
                                //$page_actions .= '<a class="link delete" data-delete="pages" data-page-id="'.$page_id.'"><span>delete</span></a>';

                                //$page_name = '<a class="link" href="'.$page_edit.'">'.$page_name_display.'</a>';
                                $page_name_link = '<a class="link" href="'.$page_edit.'">'.$page_name.'</a>';
                                cms_admin::object_index_links_append_git_statues($page_name_link, cms_admin::git_get_url_token('page', $page_url), $mmrpg_git_file_arrays);

                                $page_url_link = '<a class="link" href="'.$page_view.'" target="_blank">'.$page_url.'</a>';

                                if (!empty($page_data['parent_id'])
                                    && $sort_data['name'] == 'page_rel_order'){
                                    $page_name_link = '&raquo; '.$page_name_link;
                                }

                                echo '<tr>'.PHP_EOL;
                                    echo '<td class="id"><div>'.$page_id.'</div></td>'.PHP_EOL;
                                    //echo '<td class="token"><div class="wrap">'.$page_token.'</div></td>'.PHP_EOL;
                                    echo '<td class="name"><div class="wrap">'.$page_name_link.'</div></td>'.PHP_EOL;
                                    //echo '<td class="title"><div class="wrap">'.$page_title.'</div></td>'.PHP_EOL;
                                    echo '<td class="url"><div class="wrap">'.$page_url.'</div></td>'.PHP_EOL;
                                    echo '<td class="date created"><div>'.$page_created.'</div></td>'.PHP_EOL;
                                    echo '<td class="date modified"><div>'.$page_modified.'</div></td>'.PHP_EOL;
                                    echo '<td class="flag published"><div>'.$page_flag_published.'</div></td>'.PHP_EOL;
                                    echo '<td class="flag hidden"><div>'.$page_flag_hidden.'</div></td>'.PHP_EOL;
                                    //echo '<td class="order"><div class="wrap">'.$page_order.'</div></td>'.PHP_EOL;
                                    echo '<td class="order"><div class="wrap">'.$page_rel_order.'</div></td>'.PHP_EOL;
                                    echo '<td class="actions"><div>'.$page_actions.'</div></td>'.PHP_EOL;
                                echo '</tr>'.PHP_EOL;

                            }
                            ?>
                        </tbody>
                    </table>

                </div>

            <? endif; ?>

            <?

            //echo('<pre>$search_query = '.(!empty($search_query) ? htmlentities($search_query, ENT_QUOTES, 'UTF-8', true) : '&hellip;').'</pre>');
            //echo('<pre>$search_results = '.print_r($search_results, true).'</pre>');

            ?>

        <? endif; ?>

        <? if ($sub_action == 'editor'
            && (!empty($_GET['page_id']) || !empty($_GET['backup_id']))){

            // Capture editor markup in a buffer in case we need to modify
            if (true){
                ob_start();
                ?>

                <!-- EDITOR FORM -->

                <div class="editor">

                    <h3 class="header">
                        <span class="title"><?= !$is_backup_data ? 'Edit' : 'View' ?> Page &quot;<?= $page_name_display ?>&quot;</span>
                        <?
                        // If this is NOT backup data, we can generate links
                        if (!$is_backup_data){

                            // Print out any git-related statues to this header
                            cms_admin::object_editor_header_echo_git_statues(cms_admin::git_get_url_token('page', $page_data['page_url']), $mmrpg_git_file_arrays);

                            // If the page is published, generate and display a preview link
                            if (!empty($page_data['page_flag_published'])){
                                $preview_link = $page_data['page_url'];
                                echo '<a class="view" href="'.$preview_link.'" target="_blank">View <i class="fas fa-external-link-square-alt"></i></a>'.PHP_EOL;
                            }

                        }
                        // Otherwise we'll simply show the backup creation date
                        else {

                            // Print out the creation date in a readable form
                            echo '<span style="display: block; clear: left; font-size: 90%; font-weight: normal;">Backup Created '.date('Y/m/d @ g:s a', strtotime(preg_replace('/^([0-9]{4})([0-9]{2})([0-9]{2})-([0-9]{2})([0-9]{2})$/', '$1/$2/$3T$4:$5', $page_data['backup_date_time']))).'</span>';

                        }

                        ?>
                    </h3>

                    <? print_form_messages() ?>

                    <?
                    // Collect a list of backups for this page from the database, if any
                    $page_backup_list = $db->get_array_list("SELECT
                        backup_id, page_token, page_name, page_url, backup_date_time
                        FROM mmrpg_website_pages_backups
                        WHERE page_id = '{$page_data['page_id']}'
                        ORDER BY backup_date_time DESC
                        ;");
                    ?>

                    <div class="editor-tabs" data-tabgroup="page">
                        <a class="tab active" data-tab="basic">Basic</a><span></span>
                        <a class="tab" data-tab="seo">SEO</a><span></span>
                        <? if (!$is_backup_data && !empty($page_backup_list)){ ?>
                            <a class="tab" data-tab="backups">Backups</a><span></span>
                        <? } ?>
                    </div>

                    <form class="form" method="post">

                        <input type="hidden" name="action" value="edit-pages" />
                        <input type="hidden" name="subaction" value="editor" />

                        <div class="editor-panels" data-tabgroup="page">

                            <div class="panel active" data-tab="basic">

                                <div class="field halfsize">
                                    <strong class="label">Page ID</strong>
                                    <input type="hidden" name="page_id" value="<?= $page_data['page_id'] ?>" />
                                    <input class="textbox" type="text" name="page_id" value="<?= $page_data['page_id'] ?>" disabled="disabled" />
                                </div>

                                <div class="field halfsize">
                                    <strong class="label">Page Token</strong>
                                    <input type="hidden" name="page_token" value="<?= $page_data['page_token'] ?>" />
                                    <input class="textbox" type="text" name="page_token" value="<?= $page_data['page_token'] ?>" maxlength="64" disabled="disabled" />
                                </div>

                                <div class="field halfsize">
                                    <strong class="label">Page Parent</strong>
                                    <input type="hidden" name="parent_id" value="<?= $page_data['parent_id'] ?>" />
                                    <select class="select" name="parent_id" disabled="disabled">
                                        <option value="0" <?= empty($page_data['parent_id']) ? 'selected="selected"' : '' ?>>-</option>
                                        <? foreach ($mmrpg_website_pages_index AS $key => $parent_data){
                                            if (!empty($parent_data['parent_id'])){ continue; }
                                            $value = $parent_data['page_id'];
                                            $selected = $parent_data['page_id'] == $page_data['parent_id'] ? 'selected="selected"' : '';
                                            //$label = $parent_data['page_name'];
                                            $label = $parent_data['page_url']; //.' ('.$parent_data['page_name'].')';
                                            $title = $parent_data['page_name'].' | ID '.$parent_data['page_id'];
                                            //$label .= ' | ID '.$parent_data['page_id'].' ';
                                            echo('<option value="'.$value.'" title="'.$title.'" '.$selected.'>'.$label.'</option>');
                                        } ?>
                                    </select><span></span>
                                </div>

                                <div class="field halfsize">
                                    <div class="label">
                                        <strong>Page URL</strong>
                                        <em>auto-generated</em>
                                    </div>
                                    <input class="textbox" type="text" name="page_url" value="<?= $page_data['page_url'] ?>" maxlength="128" disabled="disabled" />
                                </div>

                                <div class="field halfsize">
                                    <div class="label">
                                        <strong>Page Name</strong>
                                        <em>appears in navbar</em>
                                    </div>
                                    <input class="textbox" type="text" name="page_name" value="<?= $page_data['page_name'] ?>" maxlength="128" />
                                </div>

                                <div class="field halfsize">
                                    <div class="label">
                                        <strong>Page Title</strong>
                                        <em>appears at top of page in header bar</em>
                                    </div>
                                    <input class="textbox" type="text" name="page_title" value="<?= $page_data['page_title'] ?>" maxlength="128" />
                                </div>

                                <hr />

                                <div class="field fullsize codemirror <?= $is_backup_data ? 'readonly' : '' ?>">
                                    <div class="label">
                                        <strong>Page Content</strong>
                                        <em>basic html and some psuedo-code allowed</em>
                                    </div>
                                    <textarea class="textarea" name="page_content" rows="20"><?= htmlentities(trim($page_data['page_content']), ENT_QUOTES, 'UTF-8', true) ?></textarea>
                                    <div class="label examples" style="font-size: 80%; padding-top: 4px;">
                                        <strong>Examples</strong>:
                                        <br />
                                        <code style="color: green;">&lt;!--&nbsp;MMRPG_CURRENT_FIELD_TYPE --&gt;</code>
                                        <br />
                                        <code style="color: green;">&lt;!-- MMRPG_ROBOT_FLOAT_SPRITE('mega-man', 'right', '03') --&gt;</code>
                                        <br />
                                        <code style="color: green;">&lt;!--&nbsp;MMRPG_LOAD_GALLERY_PAGE() --&gt;</code>
                                        then <code style="color: green;">&lt;!--&nbsp;MMRPG_SCREENSHOT_GALLERY_MARKUP --&gt;</code>
                                    </div>
                                </div>

                            </div>

                            <div class="panel" data-tab="seo">

                                <div class="field fullsize">
                                    <div class="label">
                                        <strong>Page SEO Title</strong>
                                        <em>page title used by search engines</em>
                                    </div>
                                    <input class="textbox" type="text" name="page_seo_title" value="<?= $page_data['page_seo_title'] ?>" maxlength="64" />
                                </div>

                                <div class="field fullsize">
                                    <div class="label">
                                        <strong>Page SEO Keywords</strong>
                                        <em>page keywords considered by search engines</em>
                                    </div>
                                    <input class="textbox" type="text" name="page_seo_keywords" value="<?= $page_data['page_seo_keywords'] ?>" maxlength="128" />
                                </div>

                                <div class="field fullsize">
                                    <div class="label">
                                        <strong>Page SEO Description</strong>
                                        <em>page description displayed in search engine results</em>
                                    </div>
                                    <textarea class="textarea" name="page_seo_description" rows="3" maxlength="256"><?= htmlentities($page_data['page_seo_description'], ENT_QUOTES, 'UTF-8', true) ?></textarea>
                                </div>

                            </div>

                            <? if (!$is_backup_data && !empty($page_backup_list)){ ?>
                                <div class="panel" data-tab="backups">
                                    <table class="backups">
                                        <colgroup>
                                            <col class="id" width="50" />
                                            <col class="name" width="" />
                                            <col class="url" width="" />
                                            <col class="date" width="100" />
                                            <col class="time" width="75" />
                                            <col class="actions" width="100" />
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th class="id">ID</th>
                                                <th class="name">Name</th>
                                                <th class="url">URL</th>
                                                <th class="date">Date</th>
                                                <th class="time">Time</th>
                                                <th class="actions">&nbsp;</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <? foreach ($page_backup_list AS $backup_key => $backup_info){ ?>
                                                <? $backup_unix_time = strtotime(preg_replace('/^([0-9]{4})([0-9]{2})([0-9]{2})-([0-9]{2})([0-9]{2})$/', '$1/$2/$3T$4:$5', $backup_info['backup_date_time'])); ?>
                                                <tr>
                                                    <td class="id"><?= $backup_info['backup_id'] ?></td>
                                                    <td class="name"><?= $backup_info['page_name'] ?></td>
                                                    <td class="url"><?= $backup_info['page_url'] ?></td>
                                                    <td class="date"><?= date('Y/m/d', $backup_unix_time) ?></td>
                                                    <td class="time"><?= date('g:i a', $backup_unix_time) ?></td>
                                                    <td class="actions">
                                                        <a href="admin/edit-pages/editor/backup_id=<?= $backup_info['backup_id'] ?>" target="_blank" style="text-decoration: none;">
                                                            <span style="text-decoration: underline;">View Backup</span>
                                                            <i class="fas fa-external-link-square-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <? } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <? } ?>

                        </div>

                        <hr />

                        <div class="options">

                            <div class="field checkwrap">
                                <label class="label">
                                    <strong>Published</strong>
                                    <input type="hidden" name="page_flag_published" value="0" checked="checked" />
                                    <input class="checkbox" type="checkbox" name="page_flag_published" value="1" <?= !empty($page_data['page_flag_published']) ? 'checked="checked"' : '' ?> />
                                </label>
                                <p class="subtext">Allow this page to be accessed</p>
                            </div>

                            <div class="field checkwrap">
                                <label class="label">
                                    <strong>Hidden</strong>
                                    <input type="hidden" name="page_flag_hidden" value="0" checked="checked" />
                                    <input class="checkbox" type="checkbox" name="page_flag_hidden" value="1" <?= !empty($page_data['page_flag_hidden']) ? 'checked="checked"' : '' ?> />
                                </label>
                                <p class="subtext">Hide this page from the navbar</p>
                            </div>

                            <div class="field checkwrap">
                                <label class="label">
                                    <strong>Order</strong>
                                    <input class="textbox" type="number" name="page_order" value="<?= $page_data['page_order'] ?>" maxlength="2" style="width: 50px; margin-top: -8px; top: -2px;" />
                                </label>
                                <p class="subtext">Navbar position for this page</p>
                            </div>

                        </div>

                        <hr />

                        <div class="formfoot">

                            <? if (!$is_backup_data){ ?>
                                <div class="buttons">
                                    <input class="button save" type="submit" value="Save Changes" />
                                    <input class="button cancel" type="button" value="Reset Changes" onclick="javascript:window.location.href='admin/edit-pages/editor/page_id=<?= $page_data['page_id'] ?>';" />
                                    <? /*
                                    <input class="button delete" type="button" value="Delete Page" data-delete="pages" data-page-id="<?= $page_data['page_id'] ?>" />
                                    */ ?>
                                </div>
                                <?= cms_admin::object_editor_print_git_footer_buttons('pages', cms_admin::git_get_url_token('page', $page_data['page_url']), $mmrpg_git_file_arrays) ?>
                            <? } ?>

                            <div class="metadata">
                                <div class="date"><strong>Created</strong>: <?= !empty($page_data['page_date_created']) ? str_replace('@', 'at', date('Y-m-d @ H:i', $page_data['page_date_created'])): '-' ?></div>
                                <div class="date"><strong>Modified</strong>: <?= !empty($page_data['page_date_modified']) ? str_replace('@', 'at', date('Y-m-d @ H:i', $page_data['page_date_modified'])) : '-' ?></div>
                            </div>

                        </div>

                    </form>

                </div>

                <?

                /*
                $debug_page_data = $page_data;
                $debug_page_data['page_profile_text'] = str_replace(PHP_EOL, '\\n', $debug_page_data['page_profile_text']);
                $debug_page_data['page_credit_text'] = str_replace(PHP_EOL, '\\n', $debug_page_data['page_credit_text']);
                echo('<pre>$page_data = '.(!empty($debug_page_data) ? htmlentities(print_r($debug_page_data, true), ENT_QUOTES, 'UTF-8', true) : '&hellip;').'</pre>');
                */

                ?>

                <?

                $temp_edit_markup = ob_get_clean();
                if ($is_backup_data){
                    $temp_edit_markup = str_replace('<input ', '<input readonly="readonly" disabled="disabled" ', $temp_edit_markup);
                    $temp_edit_markup = str_replace('<select ', '<select readonly="readonly" disabled="disabled" ', $temp_edit_markup);
                    $temp_edit_markup = str_replace('<textarea ', '<textarea readonly="readonly" ', $temp_edit_markup);
                }
                echo($temp_edit_markup).PHP_EOL;
            }

        }

        ?>

    </div>

<? $this_page_markup .= ob_get_clean(); ?>

