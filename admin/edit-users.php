<? ob_start(); ?>

    <?

    // Collect or define current subaction
    $sub_action =  !empty($_GET['subaction']) ? $_GET['subaction'] : 'search';

    // If we're in search mode, we might need to scan for results
    $search_data = array();
    $search_query = '';
    $search_results = array();
    $search_results_count = 0;
    if ($sub_action == 'search'
        && (!empty($_GET['user_id']) || !empty($_GET['user_name'])|| !empty($_GET['user_email']))
        ){

        // Collect form data for processing
        $search_data['user_id'] = !empty($_GET['user_id']) && is_numeric($_GET['user_id']) ? trim($_GET['user_id']) : '';
        $search_data['user_name'] = !empty($_GET['user_name']) && preg_match('/[-_0-9a-z\.\*]+/i', $_GET['user_name']) ? trim(strtolower($_GET['user_name'])) : '';
        $search_data['user_email'] = !empty($_GET['user_email']) && preg_match('/[-_0-9a-z\.@\*]+/i', $_GET['user_email']) ? trim(strtolower($_GET['user_email'])) : '';


        /* -- Collect Search Results -- */

        // Define the search query to use
        $search_query = "SELECT
            user.user_id,
            user.user_name,
            user.user_name_clean,
            user.user_name_public,
            user.user_email_address,
            user.user_date_created,
            user.user_date_modified,
            role.role_id,
            role.role_name
            FROM mmrpg_users AS user
            LEFT JOIN mmrpg_roles AS role ON role.role_id = user.role_id
            WHERE 1=1
            ";

        // If the user ID was provided, we can search by exact match
        if (!empty($search_data['user_id'])){
            $user_id = $search_data['user_id'];
            $search_query .= "AND user_id = {$user_id} ";
        }

        // Else if the user name was provided, we can use wildcards
        if (!empty($search_data['user_name'])){
            $user_name = $search_data['user_name'];
            $user_name = str_replace(array(' ', '*', '%'), '%', $user_name);
            $user_name = preg_replace('/%+/', '%', $user_name);
            $user_name = '%'.$user_name.'%';
            $search_query .= "AND (user_name LIKE '{$user_name}' OR user_name_public LIKE '{$user_name}') ";
        }

        // Else if the user email was provided, we can use wildcards
        if (!empty($search_data['user_email'])){
            $user_email = $search_data['user_email'];
            $user_email = str_replace(array(' ', '*', '%'), '%', $user_email);
            $user_email = preg_replace('/%+/', '%', $user_email);
            $user_email = '%'.$user_email.'%';
            $search_query .= "AND user_email_address LIKE '{$user_email}' ";
        }

        // Append sorting parameters to the end of the query
        $search_query .= "ORDER BY user_name ASC; ";

        // Collect search results from the database
        $search_results = $db->get_array_list($search_query);
        $search_results_count = is_array($search_results) ? count($search_results) : 0;


    }

    // If we're in editor mode, we should collect user info from database
    $user_data = array();
    $editor_data = array();
    if ($sub_action == 'editor' && !empty($_GET['user_id'])){

        // Collect form data for processing
        $editor_data['user_id'] = !empty($_GET['user_id']) && is_numeric($_GET['user_id']) ? trim($_GET['user_id']) : '';


        /* -- Collect Editor Indexes -- */

        // Collect an index of user roles for options
        $mmrpg_roles_fields = rpg_user_role::get_index_fields(true);
        $mmrpg_roles_index = $db->get_array_list("SELECT {$mmrpg_roles_fields} FROM mmrpg_roles ORDER BY role_level ASC", 'role_id');

        // Collect an index of type colours for options
        $mmrpg_types_fields = rpg_type::get_index_fields(true);
        $mmrpg_types_index = $db->get_array_list("SELECT {$mmrpg_types_fields} FROM mmrpg_index_types ORDER BY type_order ASC", 'type_token');

        // Collect an index of battle fields for options
        $mmrpg_fields_fields = rpg_field::get_index_fields(true);
        $mmrpg_fields_index = $db->get_array_list("SELECT {$mmrpg_fields_fields} FROM mmrpg_index_fields ORDER BY field_order ASC", 'field_token');

        // Collect an index of player colours for options
        $mmrpg_players_fields = rpg_player::get_index_fields(true);
        $mmrpg_players_index = $db->get_array_list("SELECT {$mmrpg_players_fields} FROM mmrpg_index_players ORDER BY player_order ASC", 'player_token');

        // Collect an index of robot colours for options
        $mmrpg_robots_fields = rpg_robot::get_index_fields(true);
        $mmrpg_robots_index = $db->get_array_list("SELECT {$mmrpg_robots_fields} FROM mmrpg_index_robots WHERE robot_class = 'master' ORDER BY robot_order ASC", 'robot_token');


        /* -- Collect User Data -- */

        // Collect user details from the database
        $user_fields = rpg_user::get_fields(true);
        $user_data = $db->get_array("SELECT {$user_fields} FROM mmrpg_users WHERE user_id = {$editor_data['user_id']};");

        // Collect the user's name(s) for display
        $user_name_display = $user_data['user_name'];
        if (!empty($user_data['user_name_public']) && $user_data['user_name_public'] != $user_data['user_name']){
            $user_name_display = $user_data['user_name_public'] .' / '. $user_name_display;
        }


        /* -- Process Form Actions -- */

        // Define the form messages and collect any from session
        $form_messages = array();
        if (!empty($_SESSION['mmrpg_admin']['form_messages'])){
            $form_messages = $_SESSION['mmrpg_admin']['form_messages'];
        }

        // DEBUG
        //$form_messages[] = array('success', 'This is a success message!');
        //$form_messages[] = array('warning', 'This is a warning message!');
        //$form_messages[] = array('alert', 'This is an alert message!');
        //$form_messages[] = array('error', 'This is an error message!');

        // Define a function for breaking early from a form submission
        function exit_user_edit_action($user_id){
            global $form_messages;
            $_SESSION['mmrpg_admin']['form_messages'] = $form_messages;
            header('Location: admin.php?action=edit_users&subaction=editor&user_id='.$user_id);
            exit();
        }

        // If form data has been submit for this user, we should process it
        $form_data = array();
        $form_success = true;
        $form_action = !empty($_POST['action']) ? trim($_POST['action']) : '';
        if ($form_action == 'edit_users'){

            // Collect form data from the request and parse out simple rules
            $form_data['user_id'] = !empty($_POST['user_id']) && is_numeric($_POST['user_id']) ? trim($_POST['user_id']) : 0;
            $form_data['role_id'] = !empty($_POST['role_id']) && is_numeric($_POST['role_id']) ? trim($_POST['role_id']) : 0;
            $form_data['user_name_clean'] = !empty($_POST['user_name_clean']) && preg_match('/^[-_0-9a-z\.\*]+$/i', $_POST['user_name_clean']) ? trim(strtolower($_POST['user_name_clean'])) : '';
            $form_data['user_name'] = !empty($_POST['user_name']) && preg_match('/^[-_0-9a-z\.\*]+$/i', $_POST['user_name']) ? trim($_POST['user_name']) : '';
            $form_data['user_name_public'] = !empty($_POST['user_name_public']) && preg_match('/^[-_0-9a-z\.\s]+$/i', $_POST['user_name_public']) ? trim($_POST['user_name_public']) : '';
            $form_data['user_date_birth'] = !empty($_POST['user_date_birth']) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_POST['user_date_birth']) ? trim($_POST['user_date_birth']) : '';
            $form_data['user_gender'] = !empty($_POST['user_gender']) && preg_match('/^(male|female|other)$/', $_POST['user_gender']) ? trim(strtolower($_POST['user_gender'])) : '';

            $form_data['user_colour_token'] = !empty($_POST['user_colour_token']) && preg_match('/^[-_a-z0-9]+$/i', $_POST['user_colour_token']) ? trim(strtolower($_POST['user_colour_token'])) : '';
            $form_data['user_background_path'] = !empty($_POST['user_background_path']) && preg_match('/^[-_a-z0-9\/]+$/i', $_POST['user_background_path']) ? trim(strtolower($_POST['user_background_path'])) : '';
            $form_data['user_image_path'] = !empty($_POST['user_image_path']) && preg_match('/^[-_a-z0-9\/]+$/i', $_POST['user_image_path']) ? trim(strtolower($_POST['user_image_path'])) : '';

            $form_data['user_email_address'] = !empty($_POST['user_email_address']) && preg_match('/^[-_0-9a-z\.]+@[-_0-9a-z\.]+\.[-_0-9a-z\.]+$/i', $_POST['user_email_address']) ? trim(strtolower($_POST['user_email_address'])) : '';
            $form_data['user_website_address'] = !empty($_POST['user_website_address']) && preg_match('/^(https?:\/\/)?[-_0-9a-z\.]+\.[-_0-9a-z\.]+/i', $_POST['user_website_address']) ? trim(strtolower($_POST['user_website_address'])) : '';
            $form_data['user_ip_addresses'] = !empty($_POST['user_ip_addresses']) && preg_match('/^((([0-9]{1,3}\.){3}([0-9]{1,3}){1}),?\s?)+$/i', $_POST['user_ip_addresses']) ? trim($_POST['user_ip_addresses']) : '';

            // DEBUG
            $form_messages[] = array('alert', '<pre>$_POST = '.print_r($_POST, true).'</pre>');

            // If any of the required fields are empty, complete form failure
            if (empty($form_data['user_id'])){
                $form_messages[] = array('error', 'User ID was not provided');
                $form_success = false;
            }
            if (empty($form_data['role_id'])){
                $form_messages[] = array('error', 'Account Type was not provided');
                $form_success = false;
            }
            if (empty($form_data['user_name_clean'])){
                $form_messages[] = array('error', 'Username token was not provided or was invalid');
                $form_success = false;
            }
            if (empty($form_data['user_name'])){
                $form_messages[] = array('error', 'Login username was not provided or was invalid');
                $form_success = false;
            }

            // If there were errors, we should exit now
            if (!$form_success){ exit_user_edit_action($form_data['user_id']); }

            // If any of the optional fields are empty, remove from update data and/or warning
            if (empty($form_data['user_name_public']) && !empty($_POST['user_name_public'])){
                $form_messages[] = array('warning', 'Public username was invalid and will not be updated');
                unset($form_data['user_name_public']);
            }
            if (empty($form_data['user_date_birth']) && !empty($_POST['user_date_birth'])){
                $form_messages[] = array('warning', 'Date of birth was invalid and will not be updated');
                unset($form_data['user_date_birth']);
            }
            if (empty($form_data['user_gender']) && !empty($_POST['user_gender'])){
                $form_messages[] = array('warning', 'Gender identity was invalid and will not be updated');
                unset($form_data['user_gender']);
            }
            if (empty($form_data['user_colour_token']) && !empty($_POST['user_colour_token'])){
                $form_messages[] = array('warning', 'Player colour was invalid and will not be updated');
                unset($form_data['user_colour_token']);
            }
            if (empty($form_data['user_background_path']) && !empty($_POST['user_background_path'])){
                $form_messages[] = array('warning', 'Player background was invalid and will not be updated');
                unset($form_data['user_background_path']);
            }
            if (empty($form_data['user_image_path']) && !empty($_POST['user_image_path'])){
                $form_messages[] = array('warning', 'Player avatar was invalid and will not be updated');
                unset($form_data['user_image_path']);
            }
            if (empty($form_data['user_email_address']) && !empty($_POST['user_email_address'])){
                $form_messages[] = array('warning', 'Email address was invalid and will not be updated');
                unset($form_data['user_email_address']);
            }
            if (empty($form_data['user_website_address']) && !empty($_POST['user_website_address'])){
                $form_messages[] = array('warning', 'Website address was invalid and will not be updated');
                unset($form_data['user_website_address']);
            }
            if (empty($form_data['user_ip_addresses']) && !empty($_POST['user_ip_addresses'])){
                $form_messages[] = array('warning', 'IP addresses were invalid and will not be updated');
                unset($form_data['user_ip_addresses']);
            }

            // If there were errors, we should exit now
            if (!$form_success){ exit_user_edit_action($form_data['user_id']); }

            // Update the user name token using the new user name string
            if (!empty($form_data['user_name'])){
                $form_data['user_name_clean'] = preg_replace('/[^-a-z0-9]+/i', '', strtolower($form_data['user_name']));
            }

            // Convert date values to database-compatible formats
            if (!empty($form_data['user_date_birth'])){
                list($yyyy, $mm, $dd) = explode('-', $form_data['user_date_birth']);
                $form_data['user_date_birth'] = mktime(0, 0, 0, $mm, $dd, $yyyy);
            }

            // Update the website URL with a prefix if not already there
            if (!empty($form_data['user_website_address'])){
                $website = $form_data['user_website_address'];
                $website = preg_replace('/^https?:\/\//i', '', trim($website));
                if (!strstr($website, '/')){ $website .= '/'; }
                $website = 'http://'.$website;
                $form_data['user_website_address'] = $website;
            }

            // Loop through fields to create an update string
            $update_data = $form_data;
            unset($update_data['user_id']);
            $update_results = $db->update('mmrpg_users', $update_data, array('user_id' => $form_data['user_id']));

            // DEBUG
            $form_messages[] = array('alert', '<pre>$form_data = '.print_r($form_data, true).'</pre>');

            // If we made it this far, the update must have been a success
            if ($update_results !== false){ $form_messages[] = array('success', 'Updated user details have been saved to the database'); }
            else { $form_messages[] = array('error', 'Updated user details could not be saved to the database'); }

            // We're done processing the form, we can exit
            exit_user_edit_action($form_data['user_id']);

            //echo('<pre>$form_action = '.print_r($form_action, true).'</pre>');
            //echo('<pre>$_POST = '.print_r($_POST, true).'</pre>');
            //exit();


        }


        /* -- Generate Form Messages -- */

        // If there were form messages, generate the message markup
        $this_message_markup = '';
        if (!empty($form_messages)){
            $this_message_markup .= '<ul class="list">'.PHP_EOL;
            foreach ($form_messages AS $key => $message){
                list($type, $text) = $message;
                $this_message_markup .= '<li class="message '.$type.'">'.ucfirst($type).' : '.$text.'</li>'.PHP_EOL;
            }
            $this_message_markup .= '</ul>'.PHP_EOL;
            $_SESSION['mmrpg_admin']['form_messages'] = array();
        }

    }


    ?>

    <div class="breadcrumb">
        <a href="admin.php">Admin Panel</a>
        &raquo; <a href="admin.php?action=edit_users">Edit Users</a>
        <? if ($sub_action == 'editor' && !empty($user_data)): ?>
            &raquo; <a href="admin.php?action=edit_users&amp;subaction=editor&amp;user_id=<?= $user_data['user_id'] ?>"><?= $user_name_display ?></a>
        <? endif; ?>
    </div>

    <?= !empty($this_error_markup) ? '<div style="margin: 0 auto 20px">'.$this_error_markup.'</div>' : '' ?>

    <div class="adminform edit_users">

        <? if ($sub_action == 'search'): ?>

            <!-- SEARCH FORM -->

            <div class="search">

                <h3 class="header">Search Users</h3>

                <form class="form" method="get">

                    <input type="hidden" name="action" value="edit_users" />
                    <input type="hidden" name="subaction" value="search" />

                    <div class="field">
                        <strong class="label">By ID</strong>
                        <input class="textbox" type="text" name="user_id" value="<?= !empty($search_data['user_id']) ? $search_data['user_id'] : '' ?>" />
                    </div>

                    <div class="field">
                        <strong class="label">By User Name</strong>
                        <input class="textbox" type="text" name="user_name" value="<?= !empty($search_data['user_name']) ? htmlentities($search_data['user_name'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>

                    <div class="field">
                        <strong class="label">By Email Address</strong>
                        <input class="textbox" type="text" name="user_email" value="<?= !empty($search_data['user_email']) ? htmlentities($search_data['user_email'], ENT_QUOTES, 'UTF-8', true) : '' ?>" />
                    </div>

                    <div class="buttons">
                        <input class="button" type="submit" value="Search" />
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
                            <col class="email" width="" />
                            <col class="role" width="90" />
                            <col class="created" width="90" />
                            <col class="modified" width="90" />
                            <col class="actions" width="120" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="id">ID</th>
                                <th class="name">Name</th>
                                <th class="email">Email</th>
                                <th class="role">Role</th>
                                <th class="created">Created</th>
                                <th class="modified">Modified</th>
                                <th class="actions">Actions</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td class="foot id"></td>
                                <td class="foot name"></td>
                                <td class="foot email"></td>
                                <td class="foot role"></td>
                                <td class="foot created"></td>
                                <td class="foot modified"></td>
                                <td class="foot actions count">
                                    <?= $search_results_count == 1 ? '1 Result' : $search_results_count.' Results' ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?
                            foreach ($search_results AS $key => $user_data){

                                $user_id = $user_data['user_id'];
                                $user_name = $user_data['user_name_clean'];
                                $user_email = !empty($user_data['user_email_address']) ? $user_data['user_email_address'] : '-';
                                $user_role = $user_data['role_name'];
                                $user_created = !empty($user_data['user_date_created']) ? date('Y-m-d', $user_data['user_date_created']) : '-';
                                $user_modified = !empty($user_data['user_date_modified']) ? date('Y-m-d', $user_data['user_date_modified']) : '-';


                                // Collect the user's name(s) for display
                                $user_name_display = $user_data['user_name'];
                                if (!empty($user_data['user_name_public']) && $user_data['user_name_public'] != $user_data['user_name']){
                                    $user_name_display = $user_data['user_name_public'] .' / '. $user_name_display;
                                }

                                $user_edit = 'admin.php?action=edit_users&subaction=editor&user_id='.$user_id;
                                $user_delete = 'admin.php?action=edit_users&subaction=delete&user_id='.$user_id;

                                $user_actions = '';
                                $user_actions .= '<a class="link edit" href="'.$user_edit.'"><span>edit</span></a>';
                                $user_actions .= '<a class="link delete" href="#"><span>delete</span></a>';

                                $user_name = '<a class="link" href="'.$user_edit.'">'.$user_name_display.'</a>';

                                echo '<tr>'.PHP_EOL;
                                    echo '<td class="id"><div>'.$user_id.'</div></td>'.PHP_EOL;
                                    echo '<td class="name"><div class="wrap">'.$user_name.'</div></td>'.PHP_EOL;
                                    echo '<td class="email"><div class="wrap">'.$user_email.'</div></td>'.PHP_EOL;
                                    echo '<td class="role"><div class="wrap">'.$user_role.'</div></td>'.PHP_EOL;
                                    echo '<td class="created"><div>'.$user_created.'</div></td>'.PHP_EOL;
                                    echo '<td class="modified"><div>'.$user_modified.'</div></td>'.PHP_EOL;
                                    echo '<td class="actions"><div>'.$user_actions.'</div></td>'.PHP_EOL;
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

        <? if ($sub_action == 'editor' && !empty($_GET['user_id'])): ?>

            <!-- EDITOR FORM -->

            <div class="editor">

                <h3 class="header">Edit User &quot;<?= $user_name_display ?>&quot;</h3>

                <?= !empty($this_message_markup) ? '<div class="messages">'.$this_message_markup.'</div>' : '' ?>

                <form class="form" method="post">

                    <input type="hidden" name="action" value="edit_users" />
                    <input type="hidden" name="subaction" value="editor" />

                    <div class="field">
                        <strong class="label">User ID</strong>
                        <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>" />
                        <input class="textbox" type="text" name="user_id" value="<?= $user_data['user_id'] ?>" disabled="disabled" />
                    </div>

                    <div class="field">
                        <div class="label">
                            <strong>Login Username</strong>
                            <em>avoid changing</em>
                        </div>
                        <input type="hidden" name="user_name_clean" value="<?= $user_data['user_name_clean'] ?>" />
                        <input class="textbox" type="text" name="user_name" value="<?= $user_data['user_name'] ?>" maxlength="64" />
                    </div>

                    <div class="field">
                        <strong class="label">Public Username</strong>
                        <input class="textbox" type="text" name="user_name_public" value="<?= $user_data['user_name_public'] ?>" maxlength="64" />
                    </div>

                    <div class="field">
                        <div class="label">
                            <strong>Date of Birth</strong>
                            <em>yyyy-mm-dd</em>
                        </div>
                        <input class="textbox" type="text" name="user_date_birth" value="<?= !empty($user_data['user_date_birth']) ? date('Y-m-d', $user_data['user_date_birth']) : '' ?>" maxlength="10" placeholder="YYYY-MM-DD" />
                    </div>

                    <div class="field">
                        <strong class="label">Gender Identity</strong>
                        <select class="select" name="user_gender">
                            <option value="" <?= empty($user_data['user_gender']) ? 'selected="selected"' : '' ?>>- none -</option>
                            <option value="male" <?= $user_data['user_gender'] == 'male' ? 'selected="selected"' : '' ?>>Male</option>
                            <option value="female" <?= $user_data['user_gender'] == 'female' ? 'selected="selected"' : '' ?>>Female</option>
                            <option value="other" <?= $user_data['user_gender'] == 'other' ? 'selected="selected"' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="field">
                        <strong class="label">Account Type</strong>
                        <select class="select" name="role_id">
                            <?
                            foreach ($mmrpg_roles_index AS $role_id => $role_data){
                                $label = $role_data['role_name'];
                                $selected = !empty($user_data['role_id']) && $user_data['role_id'] == $role_id ? 'selected="selected"' : '';
                                echo('<option value="'.$role_id.'" '.$selected.'>'.$label.'</option>'.PHP_EOL);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="field">
                        <strong class="label">Player Colour</strong>
                        <select class="select" name="user_colour_token">
                            <?
                            echo('<option value=""'.(empty($user_data['user_colour_token']) ? 'selected="selected"' : '').'>- none -</option>');
                            foreach ($mmrpg_types_index AS $type_token => $type_data){
                                $label = $type_data['type_name'];
                                $selected = !empty($user_data['user_colour_token']) && $user_data['user_colour_token'] == $type_token ? 'selected="selected"' : '';
                                echo('<option value="'.$type_token.'" '.$selected.'>'.$label.'</option>'.PHP_EOL);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="field">
                        <strong class="label">Player Background</strong>
                        <select class="select" name="user_background_path">
                            <?
                            echo('<option value=""'.(empty($user_data['user_background_path']) ? 'selected="selected"' : '').'>- none -</option>');
                            foreach ($mmrpg_fields_index AS $field_token => $field_data){
                                $field_path = 'fields/'.$field_token;
                                $label = $field_data['field_name'];
                                $selected = !empty($user_data['user_background_path']) && $user_data['user_background_path'] == $field_path ? 'selected="selected"' : '';
                                echo('<option value="'.$field_path.'" '.$selected.'>'.$label.'</option>'.PHP_EOL);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="field">
                        <strong class="label">Player Avatar</strong>
                        <select class="select" name="user_image_path">
                            <?
                            echo('<option value=""'.(empty($user_data['user_image_path']) ? 'selected="selected"' : '').'>- none -</option>');
                            foreach ($mmrpg_robots_index AS $robot_token => $robot_data){
                                if (!file_exists(MMRPG_CONFIG_ROOTDIR.'images/robots/'.$robot_token.'/')){ continue; }
                                $robot_path = 'robots/'.$robot_token.'/'.$robot_data['robot_image_size'];
                                $label = $robot_data['robot_number'].' '.$robot_data['robot_name'];
                                $selected = !empty($user_data['user_image_path']) && $user_data['user_image_path'] == $robot_path ? 'selected="selected"' : '';
                                echo('<option value="'.$robot_path.'" '.$selected.'>'.$label.'</option>'.PHP_EOL);
                                if (!empty($robot_data['robot_image_alts'])){
                                    $image_alts = json_decode($robot_data['robot_image_alts'], true);
                                    foreach ($image_alts AS $alt_data){
                                        $alt_token = $alt_data['token'];
                                        $alt_path = 'robots/'.$robot_token.'_'.$alt_token.'/'.$robot_data['robot_image_size'];
                                        $label = $robot_data['robot_number'].' '.$alt_data['name'];
                                        $selected = !empty($user_data['user_image_path']) && $user_data['user_image_path'] == $alt_path ? 'selected="selected"' : '';
                                        echo('<option value="'.$alt_path.'" '.$selected.'>'.$label.'</option>'.PHP_EOL);
                                    }
                                }
                            }
                            foreach ($mmrpg_players_index AS $player_token => $player_data){
                                if (!file_exists(MMRPG_CONFIG_ROOTDIR.'images/players/'.$player_token.'/')){ continue; }
                                $player_path = 'players/'.$player_token.'/'.$player_data['player_image_size'];
                                $label = $player_data['player_name'];
                                $selected = !empty($user_data['user_image_path']) && $user_data['user_image_path'] == $player_path ? 'selected="selected"' : '';
                                echo('<option value="'.$player_path.'" '.$selected.'>'.$label.'</option>'.PHP_EOL);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="field">
                        <strong class="label">Email Address</strong>
                        <input class="textbox" type="text" name="user_email_address" value="<?= $user_data['user_email_address'] ?>" maxlength="128" />
                    </div>

                    <div class="field">
                        <strong class="label">Website Address</strong>
                        <input class="textbox" type="text" name="user_website_address" value="<?= $user_data['user_website_address'] ?>" maxlength="128" />
                    </div>

                    <div class="field">
                        <div class="label">
                            <strong>IPv4 Address</strong>
                            <em>0.0.0.0</em>
                        </div>
                        <input class="textbox" type="text" name="user_ip_addresses" value="<?= $user_data['user_ip_addresses'] ?>" maxlength="256" />
                    </div>

                    <div class="field fullsize">
                        <div class="label">
                            <strong>Profile Text</strong>
                            <em>public, displayed on leaderboard page</em>
                        </div>
                        <textarea class="textarea" name="user_profile_text" rows="10"><?= htmlentities($user_data['user_profile_text'], ENT_QUOTES, 'UTF-8', true) ?></textarea>
                    </div>

                    <hr />

                    <div class="field fullsize">
                        <div class="label">
                            <strong>Credit Line</strong>
                            <em>public, displayed on credits page</em>
                        </div>
                        <strong class="label"></strong>
                        <input class="textbox" type="text" name="user_credit_line" value="<?= htmlentities($user_data['user_credit_line'], ENT_QUOTES, 'UTF-8', true) ?>" maxlength="32" />
                    </div>

                    <div class="field fullsize">
                        <div class="label">
                            <strong>Credit Text</strong>
                            <em>public, displayed on credits page</em>
                        </div>
                        <textarea class="textarea" name="user_credit_text" rows="10"><?= htmlentities($user_data['user_credit_text'], ENT_QUOTES, 'UTF-8', true) ?></textarea>
                    </div>

                    <hr />

                    <div class="field fullsize">
                        <div class="label">
                            <strong>Moderates Notes</strong>
                            <em>private, only visible to staff</em>
                        </div>
                        <textarea class="textarea" name="user_admin_text" rows="10"><?= htmlentities($user_data['user_admin_text'], ENT_QUOTES, 'UTF-8', true) ?></textarea>
                    </div>

                    <hr />

                    <div class="field">
                        <div class="label">
                            <strong>Omega Sequence</strong>
                            <em>procedural generator seed</em>
                        </div>
                        <input type="hidden" name="user_omega" value="<?= $user_data['user_omega'] ?>" />
                        <input class="textbox" type="text" name="user_omega" value="<?= $user_data['user_omega'] ?>" maxlength="32" disabled="disabled" />
                    </div>

                    <div class="field">
                        <div class="label">
                            <strong>Change Password</strong>
                            <em>optional</em>
                        </div>
                        <input class="textbox" type="text" name="user_password_new" value="" maxlength="64" />
                    </div>

                    <div class="field">
                        <strong class="label">Retype Password</strong>
                        <input class="textbox" type="text" name="user_password_new2" value="" maxlength="64" />
                    </div>

                    <hr />

                    <div class="options">

                        <div class="field checkwrap">
                            <label class="label">
                                <strong>Approved User</strong>
                                <input type="hidden" name="user_flag_approved" value="0" checked="checked" />
                                <input class="checkbox" type="checkbox" name="user_flag_approved" value="1" <?= !empty($user_data['user_flag_approved']) ? 'checked="checked"' : '' ?> />
                            </label>
                            <p class="subtext">Allow user to access their game</p>
                        </div>

                        <div class="field checkwrap">
                            <label class="label">
                                <strong>Post Public</strong>
                                <input type="hidden" name="user_flag_postpublic" value="0" checked="checked" />
                                <input class="checkbox" type="checkbox" name="user_flag_postpublic" value="1" <?= !empty($user_data['user_flag_postpublic']) ? 'checked="checked"' : '' ?> />
                            </label>
                            <p class="subtext">Allow user to make community posts</p>
                        </div>

                        <div class="field checkwrap">
                            <label class="label">
                                <strong>Post Private</strong>
                                <input type="hidden" name="user_flag_postprivate" value="0" checked="checked" />
                                <input class="checkbox" type="checkbox" name="user_flag_postprivate" value="1" <?= !empty($user_data['user_flag_postprivate']) ? 'checked="checked"' : '' ?> />
                            </label>
                            <p class="subtext">Allow user to send private messages</p>
                        </div>

                        <div class="field checkwrap">
                            <label class="label">
                                <strong>Allow Chat</strong>
                                <input type="hidden" name="user_flag_allowchat" value="0" checked="checked" />
                                <input class="checkbox" type="checkbox" name="user_flag_allowchat" value="1" <?= !empty($user_data['user_flag_allowchat']) ? 'checked="checked"' : '' ?> />
                            </label>
                            <p class="subtext">Allow user to access the chat</p>
                        </div>

                    </div>

                    <hr />

                    <div class="formfoot">

                        <div class="buttons">
                            <input class="button" type="submit" value="Save Changes" />
                            <input class="button" type="submit" value="Delete User" />
                        </div>

                        <div class="metadata">
                            <div class="date"><strong>Created</strong>: <?= !empty($user_data['user_date_created']) ? str_replace('@', 'at', date('Y-m-d @ H:i', $user_data['user_date_created'])): '-' ?></div>
                            <div class="date"><strong>Modified</strong>: <?= !empty($user_data['user_date_modified']) ? str_replace('@', 'at', date('Y-m-d @ H:i', $user_data['user_date_modified'])) : '-' ?></div>
                            <div class="date"><strong>Last Login</strong>: <?= !empty($user_data['user_last_login']) ? str_replace('@', 'at', date('Y-m-d @ H:i', $user_data['user_last_login'])) : '-' ?></div>
                        </div>

                    </div>

                </form>

            </div>

            <?

            /*
            $debug_user_data = $user_data;
            $debug_user_data['user_profile_text'] = str_replace(PHP_EOL, '\\n', $debug_user_data['user_profile_text']);
            $debug_user_data['user_credit_text'] = str_replace(PHP_EOL, '\\n', $debug_user_data['user_credit_text']);
            echo('<pre>$user_data = '.(!empty($debug_user_data) ? htmlentities(print_r($debug_user_data, true), ENT_QUOTES, 'UTF-8', true) : '&hellip;').'</pre>');
            */

            ?>


        <? endif; ?>

    </div>

<? $this_page_markup .= ob_get_clean(); ?>