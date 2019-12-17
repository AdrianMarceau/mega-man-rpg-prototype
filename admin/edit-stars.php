<? ob_start(); ?>

    <?

    //<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/js/main.js"></script>

    /* -- Collect Dependant Indexes -- */

    // Collect an index of type colours for options
    $mmrpg_types_fields = rpg_type::get_index_fields(true);
    $mmrpg_types_index = $db->get_array_list("SELECT {$mmrpg_types_fields} FROM mmrpg_index_types ORDER BY type_order ASC", 'type_token');

    // Collect an index of all existing stars for reference
    $mmrpg_stars_fields = rpg_rogue_star::get_index_fields(true);
    $mmrpg_rogue_stars_index = $db->get_array_list("SELECT {$mmrpg_stars_fields} FROM mmrpg_rogue_stars WHERE star_id <> 0;", 'star_id');

    // Pre-check access permissions before continuing
    if (!in_array('*', $this_adminaccess)
        && !in_array('edit_stars', $this_adminaccess)){
        $form_messages[] = array('error', 'You do not have permission to edit stars!');
        redirect_form_action('admin.php?action=home');
    }

    // Define the extra stylesheets that must be included for this page
    if (!isset($admin_include_stylesheets)){ $admin_include_stylesheets = ''; }
    //$admin_include_stylesheets .= '<link rel="stylesheet" href="_ext/litepicker/dist/js/main.css?'.MMRPG_CONFIG_CACHE_DATE.'">'.PHP_EOL;

    // Define the extra javascript that must be included for this page
    if (!isset($admin_include_javascript)){ $admin_include_javascript = ''; }
    $admin_include_javascript .= '<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/js/main.js?'.MMRPG_CONFIG_CACHE_DATE.'"></script>'.PHP_EOL;


    /* -- Form Setup Actions -- */

    // Define a function for exiting a star edit action
    function exit_star_edit_action($star_id = 0){
        if (!empty($star_id)){ $location = 'admin.php?action=edit_stars&subaction=editor&star_id='.$star_id; }
        else { $location = 'admin.php?action=edit_stars&subaction=search'; }
        redirect_form_action($location);
    }

    /* -- Admin Substar Processing -- */

    // Collect or define current subaction
    $sub_action =  !empty($_GET['subaction']) ? $_GET['subaction'] : 'search';

    // Update the tab name with the star name
    $this_page_tabtitle = 'Edit Rogue Stars | '.$this_page_tabtitle;

    // If we're in delete mode, we need to remove some data
    $delete_data = array();
    if (false && $sub_action == 'delete' && !empty($_GET['star_id'])){

        // Collect form data for processing
        $delete_data['star_id'] = !empty($_GET['star_id']) && is_numeric($_GET['star_id']) ? trim($_GET['star_id']) : '';

        // Let's delete all of this star's data from the database
        $db->delete('mmrpg_rogue_stars', array('star_id' => $delete_data['star_id']));
        $form_messages[] = array('success', 'The requested star has been deleted from the database');
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
        $sort_data = array('name' => 'star_id', 'dir' => 'desc');
        //$sort_data = array('name' => 'star_from_date', 'dir' => 'desc');
        //$sort_data = array('name' => 'star_to_date', 'dir' => 'desc');
        //$sort_data = array('name' => 'star_order', 'dir' => 'asc');
        if (!empty($_GET['order'])
            && preg_match('/^([-_a-z0-9]+)\:(desc|asc)$/i', $_GET['order'])){
            list($r_name, $r_dir) = explode(':', trim($_GET['order']));
            $sort_data = array('name' => $r_name, 'dir' => $r_dir);
        }

        // Collect form data for processing
        $search_data['star_id'] = !empty($_GET['star_id']) && is_numeric($_GET['star_id']) ? trim($_GET['star_id']) : '';
        $search_data['star_type'] = !empty($_GET['star_type']) && preg_match('/[-_0-9a-z]+/i', $_GET['star_type']) ? trim(strtolower($_GET['star_type'])) : '';
        $search_data['star_from_date'] = !empty($_GET['star_from_date']) && preg_match('/[-_0-9\*]+/i', $_GET['star_from_date']) ? trim(strtolower($_GET['star_from_date'])) : '';
        $search_data['star_to_date'] = !empty($_GET['star_to_date']) && preg_match('/[-_0-9\*]+/i', $_GET['star_to_date']) ? trim(strtolower($_GET['star_to_date'])) : '';
        $search_data['star_power'] = !empty($_GET['star_power']) && is_numeric($_GET['star_power']) ? trim($_GET['star_power']) : '';
        $search_data['star_status'] = !empty($_GET['star_status']) && preg_match('/[-_0-9a-z]+/i', $_GET['star_status']) ? trim(strtolower($_GET['star_status'])) : '';
        $search_data['star_flag_enabled'] = isset($_GET['star_flag_enabled']) && $_GET['star_flag_enabled'] !== '' ? (!empty($_GET['star_flag_enabled']) ? 1 : 0) : '';

        /* -- Collect Search Results -- */

        // Define the search query to use
        $temp_now_date = date('Y-m-d');
        $temp_star_fields = rpg_rogue_star::get_index_fields(true, 'star');
        $search_query = "SELECT
            {$temp_star_fields},
            CONCAT('Rogue Star ', star_id) AS star_name,
            CONCAT(star.star_from_date, '_', star.star_to_date)
                AS star_date_range,
            (CASE
                WHEN star.star_to_date < '{$temp_now_date}'
                    THEN 'passed'
                WHEN star.star_from_date > '{$temp_now_date}'
                    THEN 'pending'
                ELSE 'current'
            END) AS star_status
            FROM mmrpg_rogue_stars AS star
            WHERE 1=1
            AND star.star_id <> 0
            ";

        // If the star ID was provided, we can search by exact match
        if (!empty($search_data['star_id'])){
            $star_id = $search_data['star_id'];
            $search_query .= "AND star_id = {$star_id} ";
            $search_results_limit = false;
        }

        // Else if the star type was provided, we can use wildcards
        if (!empty($search_data['star_type'])){
            $star_type = $search_data['star_type'];
            if ($star_type !== 'none'){ $search_query .= "AND star_type = '{$star_type}' "; }
            else { $search_query .= "AND star_type = '' "; }
            $search_results_limit = false;
        }

        // If the star power was provided, we can search by exact match
        if (!empty($search_data['star_power'])){
            $star_power = $search_data['star_power'];
            $search_query .= "AND star_power = {$star_power} ";
            $search_results_limit = false;
        }

        // Else if the star to date was provided, we can use wildcards
        if (!empty($search_data['star_to_date'])){
            $star_to_date = $search_data['star_to_date'];
            $star_to_date = str_replace(array(' ', '*', '%'), '%', $star_to_date);
            $star_to_date = preg_replace('/%+/', '%', $star_to_date);
            $star_to_date = '%'.$star_to_date.'%';
            $search_query .= "AND star_to_date LIKE '{$star_to_date}' ";
            $search_results_limit = false;
        }

        // Else if the star from date was provided, we can use wildcards
        if (!empty($search_data['star_from_date'])){
            $star_from_date = $search_data['star_from_date'];
            $star_from_date = str_replace(array(' ', '*', '%'), '%', $star_from_date);
            $star_from_date = preg_replace('/%+/', '%', $star_from_date);
            $star_from_date = '%'.$star_from_date.'%';
            $search_query .= "AND star_from_date LIKE '{$star_from_date}' ";
            $search_results_limit = false;
        }

        // Else if the star status was provided, we can use wildcards
        if (!empty($search_data['star_status'])){
            $star_status = $search_data['star_status'];
            if ($star_status !== 'none'){ $search_query .= "AND star_status = '{$star_status}' "; }
            else { $search_query .= "AND star_status = '' "; }
            $search_results_limit = false;
            $search_query = str_replace('WHERE 1=1', 'HAVING 1=1', $search_query);
        }

        // If the star enabled flag was provided
        if ($search_data['star_flag_enabled'] !== ''){
            $search_query .= "AND star_flag_enabled = {$search_data['star_flag_enabled']} ";
            $search_results_limit = false;
        }

        // Append sorting parameters to the end of the query
        $order_by = array();
        if (!empty($sort_data)){ $order_by[] = $sort_data['name'].' '.strtoupper($sort_data['dir']); }
        $order_by[] = "star_date_range DESC";
        //$order_by[] = "star_order ASC";
        //$order_by[] = "star_type ASC";
        $order_by_string = implode(', ', $order_by);
        $search_query .= "ORDER BY {$order_by_string} ";

        // Impose a limit on the search results
        if (!empty($search_results_limit)){ $search_query .= "LIMIT {$search_results_limit} "; }

        // End the query now that we're done
        $search_query .= ";";

        // Collect search results from the database
        $search_results = $db->get_array_list($search_query);
        $search_results_count = is_array($search_results) ? count($search_results) : 0;

        // Collect a total number from the database
        $search_results_total = $db->get_value("SELECT COUNT(star_id) AS total FROM mmrpg_rogue_stars WHERE 1=1 AND star_id <> 0;", 'total');

    }

    // If we're in editor mode, we should collect star info from database
    $star_data = array();
    $editor_data = array();
    if ($sub_action == 'editor' && !empty($_GET['star_id'])){

        // Collect form data for processing
        $editor_data['star_id'] = !empty($_GET['star_id']) && is_numeric($_GET['star_id']) ? trim($_GET['star_id']) : '';

        /* -- Collect Star Data -- */

        // Collect star details from the database
        $temp_star_fields = rpg_rogue_star::get_fields(true);
        $star_data = $db->get_array("SELECT {$temp_star_fields} FROM mmrpg_rogue_stars WHERE star_id = {$editor_data['star_id']};");

        // If star data could not be found, produce error and exit
        if (empty($star_data)){ exit_star_edit_action(); }

        // Collect the star's name(s) for display
        $star_name_display = 'Rogue Star '.$editor_data['star_id'];

        $this_page_tabtitle = $star_name_display.' | '.$this_page_tabtitle;

        // If form data has been submit for this star, we should process it
        $form_data = array();
        $form_success = true;
        $form_action = !empty($_POST['action']) ? trim($_POST['action']) : '';
        if ($form_action == 'edit_stars'){

            // Collect form data from the request and parse out simple rules

            $form_data['star_id'] = !empty($_POST['star_id']) && is_numeric($_POST['star_id']) ? trim($_POST['star_id']) : 0;

            $form_data['star_type'] = !empty($_POST['star_type']) && preg_match('/^[-_0-9a-z\.]+$/i', $_POST['star_type']) ? trim(strtolower($_POST['star_type'])) : '';
            $form_data['star_from_date'] = !empty($_POST['star_from_date']) && preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/i', $_POST['star_from_date']) ? trim($_POST['star_from_date']) : '';
            $form_data['star_from_date_time'] = !empty($_POST['star_from_date_time']) && preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/i', $_POST['star_from_date_time']) ? trim($_POST['star_from_date_time']) : '';
            $form_data['star_to_date'] = !empty($_POST['star_to_date']) && preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/i', $_POST['star_to_date']) ? trim($_POST['star_to_date']) : '';
            $form_data['star_to_date_time'] = !empty($_POST['star_to_date_time']) && preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/i', $_POST['star_to_date_time']) ? trim($_POST['star_to_date_time']) : '';
            $form_data['star_power'] = !empty($_POST['star_power']) && is_numeric($_POST['star_power']) ? trim($_POST['star_power']) : 0;

            $form_data['star_flag_enabled'] = isset($_POST['star_flag_enabled']) && is_numeric($_POST['star_flag_enabled']) ? trim($_POST['star_flag_enabled']) : 0;

            // DEBUG
            //$form_messages[] = array('alert', '<pre>$_POST = '.print_r($_POST, true).'</pre>');

            // If the required USER ID field was empty, complete form failure
            if (empty($form_data['star_id'])){
                $form_messages[] = array('error', 'Star ID was not provided');
                $form_success = false;
            }

            // If the required STAR TYPE field was empty, complete form failure
            if (empty($form_data['star_type'])){
                $form_messages[] = array('error', 'Star type was not provided or was invalid');
                $form_success = false;
            }

            // If the required STAR FROM DATE or TIME fields were empty, complete form failure
            if (empty($form_data['star_from_date'])){
                $form_messages[] = array('error', 'Star from-date field was not provided or was invalid');
                $form_success = false;
            } elseif (empty($form_data['star_from_date_time'])){
                $form_messages[] = array('error', 'Star from-time field was not provided or was invalid');
                $form_success = false;
            }

            // If the required STAR TO DATE or TIME fields were empty, complete form failure
            if (empty($form_data['star_to_date'])){
                $form_messages[] = array('error', 'Star to-date field was not provided or was invalid');
                $form_success = false;
            } elseif (empty($form_data['star_to_date_time'])){
                $form_messages[] = array('error', 'Star to-time field was not provided or was invalid');
                $form_success = false;
            }

            // If the required STAR POWER field was empty, complete form failure
            if (empty($form_data['star_power'])){
                $form_messages[] = array('error', 'Star power was not provided');
                $form_success = false;
            }

            // If there were errors, we should exit now
            if (!$form_success){ exit_star_edit_action($form_data['star_id']); }

            // If trying to update the STAR TYPE but it was invalid, do not update
            if (empty($form_data['star_type']) && !empty($_POST['star_type'])){
                $form_messages[] = array('warning', 'Star type was invalid and will not be updated');
                unset($form_data['star_type']);
            }

            // Loop through fields to create an update string
            $update_data = $form_data;
            unset($update_data['star_id']);
            $update_results = $db->update('mmrpg_rogue_stars', $update_data, array('star_id' => $form_data['star_id']));

            // DEBUG
            //$form_messages[] = array('alert', '<pre>$form_data = '.print_r($form_data, true).'</pre>');
            //$form_messages[] = array('alert', '<pre>$update_data = '.print_r($update_data, true).'</pre>');

            // If we made it this far, the update must have been a success
            if ($update_results !== false){ $form_messages[] = array('success', 'Star details were updated successfully'); }
            else { $form_messages[] = array('error', 'Star details could not be updated'); }

            // We're done processing the form, we can exit
            exit_star_edit_action($form_data['star_id']);

            //echo('<pre>$form_action = '.print_r($form_action, true).'</pre>');
            //echo('<pre>$_POST = '.print_r($_POST, true).'</pre>');
            //exit();


        }

    }


    ?>

    <div class="breadcrumb">
        <a href="admin.php">Admin Panel</a>
        &raquo; <a href="admin.php?action=edit_stars">Edit Rogue Stars</a>
        <? if ($sub_action == 'editor' && !empty($star_data)): ?>
            &raquo; <a href="admin.php?action=edit_stars&amp;subaction=editor&amp;star_id=<?= $star_data['star_id'] ?>"><?= $star_name_display ?></a>
        <? endif; ?>
    </div>

    <?= !empty($this_error_markup) ? '<div style="margin: 0 auto 20px">'.$this_error_markup.'</div>' : '' ?>

    <div class="adminform edit_stars">

        <? if ($sub_action == 'search'): ?>

            <!-- SEARCH FORM -->

            <div class="search">

                <h3 class="header">Search Stars</h3>

                <? print_form_messages() ?>

                <form class="form" method="get">

                    <input type="hidden" name="action" value="edit_stars" />
                    <input type="hidden" name="subaction" value="search" />

                    <div class="field threesize">
                        <strong class="label">By ID</strong>
                        <input class="textbox" type="text" name="star_id" value="<?= !empty($search_data['star_id']) ? $search_data['star_id'] : '' ?>" />
                    </div>

                    <div class="field threesize">
                        <strong class="label">By Type</strong>
                        <select class="select" name="star_type"><option value=""></option><?
                            foreach ($mmrpg_types_index AS $type_token => $type_info){
                                if ($type_info['type_class'] === 'special' && $type_token !== 'none'){ continue; }
                                ?><option value="<?= $type_token ?>"<?= !empty($search_data['star_type']) && $search_data['star_type'] === $type_token ? ' selected="selected"' : '' ?>><?= $type_token === 'none' ? 'Neutral' : ucfirst($type_token) ?></option><?
                                } ?>
                        </select><span></span>
                    </div>

                    <div class="field threesize">
                        <strong class="label">By Status</strong>
                        <select class="select" name="star_status">
                            <option value=""></option>
                            <option value="current"<?= !empty($search_data['star_status']) && $search_data['star_status'] === 'current' ? ' selected="selected"' : '' ?>>Current</option>
                            <option value="pending"<?= !empty($search_data['star_status']) && $search_data['star_status'] === 'pending' ? ' selected="selected"' : '' ?>>Pending</option>
                            <option value="passed"<?= !empty($search_data['star_status']) && $search_data['star_status'] === 'passed' ? ' selected="selected"' : '' ?>>Passed</option>
                        </select><span></span>
                    </div>

                    <div class="field threesize">
                        <strong class="label">By Power</strong>
                        <input class="textbox" type="text" name="star_power" value="<?= !empty($search_data['star_power']) ? $search_data['star_power'] : '' ?>" />
                    </div>

                    <? /*
                    <div class="field foursize">
                        <div class="label">
                            <strong>By Start Date</strong>
                            <em>YYYY-MM-DD</em>
                        </div>
                        <input class="textbox" type="text" name="star_from_date" value="<?= !empty($search_data['star_from_date']) ? htmlentities($search_data['star_from_date'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>

                    <div class="field foursize">
                        <div class="label">
                            <strong>By End Date</strong>
                            <em>YYYY-MM-DD</em>
                        </div>
                        <input class="textbox" type="text" name="star_to_date" value="<?= !empty($search_data['star_to_date']) ? htmlentities($search_data['star_to_date'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>
                    */ ?>

                    <div class="field threesize has2cols flags">
                    <?
                    $flag_names = array(
                        'enabled' => array('icon' => 'fas fa-check-square', 'yes' => 'Enabled', 'no' => 'Disabled'),
                        );
                    foreach ($flag_names AS $flag_token => $flag_info){
                        $flag_name = 'star_flag_'.$flag_token;
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
                        <input class="button" type="reset" value="Reset" onclick="javascript:window.location.href='admin.php?action=edit_stars';" />
                    </div>

                </form>

            </div>

            <? if (!empty($search_results)): ?>

                <!-- SEARCH RESULTS -->

                <div class="results">

                    <table class="list" style="width: 100%;">
                        <colgroup>
                            <col class="id" width="60" />
                            <col class="name" width="" />
                            <col class="range" width="" />
                            <? /*
                            <col class="date from" width="100" />
                            <col class="date to" width="100" />
                            */ ?>
                            <col class="type" width="120" />
                            <col class="power" width="60" />
                            <col class="status" width="100" />
                            <col class="flag enabled" width="80" />
                            <col class="actions" width="100" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="id"><?= cms_admin::get_sort_link('star_id', 'ID') ?></th>
                                <th class="name"><?= cms_admin::get_sort_link('star_name', 'Name') ?></th>
                                <th class="range"><?= cms_admin::get_sort_link('star_date_range', 'Date(s)') ?></th>
                                <? /*
                                <th class="date from"><?= cms_admin::get_sort_link('star_from_date', 'From') ?></th>
                                <th class="date to"><?= cms_admin::get_sort_link('star_to_date', 'To') ?></th>
                                */ ?>
                                <th class="type"><?= cms_admin::get_sort_link('star_type', 'Type') ?></th>
                                <th class="power"><?= cms_admin::get_sort_link('star_power', 'Power') ?></th>
                                <th class="status"><?= cms_admin::get_sort_link('star_status', 'Status') ?></th>
                                <th class="flag enabled"><?= cms_admin::get_sort_link('star_flag_enabled', 'Enabled') ?></th>
                                <th class="actions">Actions</th>
                            </tr>
                            <tr>
                                <th class="head id"></th>
                                <th class="head name"></th>
                                <th class="head range"></th>
                                <? /*
                                <th class="head date from"></th>
                                <th class="head date to"></th>
                                */ ?>
                                <th class="head type"></th>
                                <th class="head power"></th>
                                <th class="head status"></th>
                                <th class="head flag enabled"></th>
                                <th class="head count"><?= cms_admin::get_totals_markup() ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td class="foot id"></td>
                                <td class="foot name"></td>
                                <td class="foot range"></td>
                                <? /*
                                <td class="foot date from"></td>
                                <td class="foot date to"></td>
                                */ ?>
                                <td class="foot type"></td>
                                <td class="foot power"></td>
                                <td class="foot status"></td>
                                <td class="foot flag enabled"></td>
                                <td class="foot count"><?= cms_admin::get_totals_markup() ?></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?
                            $temp_status_colours = array(
                                'pending' => 'defense',
                                'passed' => 'attack',
                                'current' => 'energy'
                                );
                            foreach ($search_results AS $key => $star_data){

                                $star_id = $star_data['star_id'];
                                $star_name = $star_data['star_name'];
                                $star_power = $star_data['star_power'];
                                $star_flag_enabled = !empty($star_data['star_flag_enabled']) ? '<i class="fas fa-check-square"></i>' : '-';

                                $star_status = ucfirst($star_data['star_status']);
                                $star_status_span = '<span class="type_span type_'.$temp_status_colours[$star_data['star_status']].'">'.$star_status.'</span>';

                                $star_from_date = !empty($star_data['star_from_date']) ? $star_data['star_from_date'] : '-';
                                $star_to_date = !empty($star_data['star_to_date']) ? $star_data['star_to_date'] : '-';

                                $star_date_range = rpg_rogue_star::get_date_range_string($star_from_date, $star_to_date);

                                $star_type = $star_data['star_type'];
                                $star_type_info = !empty($mmrpg_types_index[$star_type]) ? $mmrpg_types_index[$star_type] : array();
                                $star_type_name = !empty($star_type_info) ? $star_type_info['type_name'] : ucfirst($star_type);
                                $star_type_span = '<span class="type_span type_'.(!empty($star_data['star_type']) ? $star_data['star_type'] : 'none').'">'.$star_type_name.'</span>';

                                $star_edit = 'admin.php?action=edit_stars&subaction=editor&star_id='.$star_id;

                                $star_actions = '';
                                $star_actions .= '<a class="link edit" href="'.$star_edit.'"><span>edit</span></a>';
                                $star_actions .= '<a class="link delete" data-delete="stars" data-star-id="'.$star_id.'"><span>delete</span></a>';

                                //$star_range_link = '<a class="link" href="'.$star_edit.'">'.$star_date_range.'</a>';
                                $star_name_link = '<a class="link" href="'.$star_edit.'">'.$star_name.'</a>';

                                if (!empty($star_data['parent_id'])
                                    && $sort_data['name'] == 'star_rel_order'){
                                    $star_name_link = '&raquo; '.$star_name_link;
                                }

                                echo '<tr>'.PHP_EOL;
                                    echo '<td class="id"><div>'.$star_id.'</div></td>'.PHP_EOL;
                                    echo '<td class="name"><div>'.$star_name_link.'</div></td>'.PHP_EOL;
                                    echo '<td class="range"><div>'.$star_date_range.'</div></td>'.PHP_EOL;
                                    //echo '<td class="range"><div>'.$star_range_link.'</div></td>'.PHP_EOL;
                                    //echo '<td class="date from"><div>'.$star_from_date.'</div></td>'.PHP_EOL;
                                    //echo '<td class="date to"><div>'.$star_to_date.'</div></td>'.PHP_EOL;
                                    echo '<td class="type"><div class="wrap">'.$star_type_span.'</div></td>'.PHP_EOL;
                                    echo '<td class="power"><div class="wrap">'.$star_power.'</div></td>'.PHP_EOL;
                                    echo '<td class="status"><div class="wrap">'.$star_status_span.'</div></td>'.PHP_EOL;
                                    echo '<td class="flag enabled"><div>'.$star_flag_enabled.'</div></td>'.PHP_EOL;
                                    echo '<td class="actions"><div>'.$star_actions.'</div></td>'.PHP_EOL;
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

        <? if ($sub_action == 'editor' && !empty($_GET['star_id'])){

            // Capture editor markup in a buffer in case we need to modify
            if (true){
                ob_start();
                ?>

                <!-- EDITOR FORM -->

                <div class="editor">

                    <h3 class="header type_span type_<?= !empty($star_data['star_type']) ? $star_data['star_type'] : 'none' ?>" data-auto="field-type" data-field-type="star_type">
                        <span class="title">Editing &quot;<?= $star_name_display ?>&quot;</span>
                    </h3>

                    <? print_form_messages() ?>

                    <form class="form" method="post">

                        <input type="hidden" name="action" value="edit_stars" />
                        <input type="hidden" name="subaction" value="editor" />

                        <div class="field halfsize">
                            <strong class="label">Star ID</strong>
                            <input type="hidden" name="star_id" value="<?= $star_data['star_id'] ?>" />
                            <input class="textbox" type="text" name="star_id" value="<?= $star_data['star_id'] ?>" disabled="disabled" />
                        </div>

                        <div class="field halfsize">
                            <strong class="label">Star Type</strong>
                            <select class="select" name="star_type">
                                <option value="0" <?= empty($star_data['star_type']) ? 'selected="selected"' : '' ?>>-</option>
                                <? foreach ($mmrpg_types_index AS $key => $type_info){
                                    if ($type_info['type_class'] !== 'normal'){ continue; }
                                    $value = $type_info['type_token'];
                                    $selected = $type_info['type_token'] == $star_data['star_type'] ? 'selected="selected"' : '';
                                    $label = $type_info['type_name'];
                                    echo('<option value="'.$value.'" title="'.$title.'" '.$selected.'>'.$label.'</option>');
                                } ?>
                            </select><span></span>
                        </div>

                        <div class="field halfsize">
                            <strong class="label">Star Power</strong>
                            <input class="textbox" type="number" name="star_power" value="<?= $star_data['star_power'] ?>" maxlength="4"  min="100" max="1000" step="100" />
                        </div>

                        <div class="field foursize litepicker">
                            <div class="label">
                                <strong>Start Date</strong>
                                <em>YYYY-MM-DD inclusive</em>
                            </div>
                            <input class="textbox" type="text" name="star_from_date" data-next-name="star_to_date" value="<?= $star_data['star_from_date'] ?>" maxlength="10" />
                            <input class="textbox" type="hidden" name="star_from_date_time" data-next-name="star_to_date_time" value="<?= !empty($star_data['star_from_date_time']) ? $star_data['star_from_date_time'] : '00:00:00' ?>" maxlength="8" />
                        </div>

                        <div class="field foursize litepicker">
                            <div class="label">
                                <strong>End Date</strong>
                                <em>YYYY-MM-DD inclusive</em>
                            </div>
                            <input class="textbox" type="text" name="star_to_date" value="<?= $star_data['star_to_date'] ?>" maxlength="10" />
                            <input class="textbox" type="hidden" name="star_to_date_time" value="<?= !empty($star_data['star_to_date_time']) ? $star_data['star_to_date_time'] : '23:59:59' ?>" maxlength="8" />
                        </div>

                        <hr />

                        <div class="options">

                            <div class="field checkwrap">
                                <label class="label">
                                    <strong>Enabled</strong>
                                    <input type="hidden" name="star_flag_enabled" value="0" checked="checked" />
                                    <input class="checkbox" type="checkbox" name="star_flag_enabled" value="1" <?= !empty($star_data['star_flag_enabled']) ? 'checked="checked"' : '' ?> />
                                </label>
                                <p class="subtext">Allow this star to be appear in-game</p>
                            </div>

                        </div>

                        <hr />

                        <div class="formfoot">

                            <div class="buttons">
                                <input class="button save" type="submit" value="Save Changes" />
                                <input class="button reset" type="button" value="Reset Changes" onclick="javascript:window.location.href='admin.php?action=edit_stars&subaction=editor&star_id=<?= $star_data['star_id'] ?>';" />
                                <input class="button delete" type="button" value="Delete Star" data-delete="stars" data-star-id="<?= $star_data['star_id'] ?>" />
                            </div>

                            <? /*
                            <div class="metadata">
                                <div class="date"><strong>Created</strong>: <?= !empty($star_data['star_date_created']) ? str_replace('@', 'at', date('Y-m-d @ H:i', $star_data['star_date_created'])): '-' ?></div>
                                <div class="date"><strong>Modified</strong>: <?= !empty($star_data['star_date_modified']) ? str_replace('@', 'at', date('Y-m-d @ H:i', $star_data['star_date_modified'])) : '-' ?></div>
                            </div>
                            */ ?>

                        </div>

                    </form>

                </div>

                <?

                /*
                $debug_star_data = $star_data;
                $debug_star_data['star_profile_text'] = str_replace(PHP_EOL, '\\n', $debug_star_data['star_profile_text']);
                $debug_star_data['star_credit_text'] = str_replace(PHP_EOL, '\\n', $debug_star_data['star_credit_text']);
                echo('<pre>$star_data = '.(!empty($debug_star_data) ? htmlentities(print_r($debug_star_data, true), ENT_QUOTES, 'UTF-8', true) : '&hellip;').'</pre>');
                */

                ?>

                <?

                $temp_edit_markup = ob_get_clean();
                echo($temp_edit_markup).PHP_EOL;
            }

        }

        ?>

    </div>

<? $this_page_markup .= ob_get_clean(); ?>

