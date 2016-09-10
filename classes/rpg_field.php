<?
/**
 * Mega Man RPG Field Object
 * <p>The base class for all field objects in the Mega Man RPG Prototype.</p>
 */
class rpg_field extends rpg_object {

    // Define the constructor class
    public function rpg_field(){

        // Update the session keys for this object
        $this->session_key = 'FIELDS';
        $this->session_token = 'field_token';
        $this->session_id = 'field_id';
        $this->class = 'field';
        $this->multi = 'fields';

        // Collect any provided arguments
        $args = func_get_args();

        // Define the internal battle pointer
        $this->battle = isset($args[0]) ? $args[0] : array();
        $this->battle_id = $this->battle->battle_id;
        $this->battle_token = $this->battle->battle_token;

        // Collect current field data from the function if available
        $this_fieldinfo = isset($args[1]) ? $args[1] : array('field_id' => 0, 'field_token' => 'field');

        // Now load the field data from the session or index
        $this->field_load($this_fieldinfo);

        // Return true on success
        return true;

    }

    // Define a public function for manually loading data
    public function field_load($this_fieldinfo){
        // Pull in the global index
        global $mmrpg_index;

        // Collect current field data from the session if available
        $this_fieldinfo_backup = $this_fieldinfo;
        if (isset($_SESSION['FIELDS'][$this->battle->battle_id][$this_fieldinfo['field_id']])){
            $this_fieldinfo = $_SESSION['FIELDS'][$this->battle->battle_id][$this_fieldinfo['field_id']];
        }
        // Otherwise, collect field data from the index
        else {
            $this_fieldinfo = rpg_field::get_index_info($this_fieldinfo['field_token']);
        }
        $this_fieldinfo = array_replace($this_fieldinfo, $this_fieldinfo_backup);

        // Define the internal field values using the collected array
        $this->flags = isset($this_fieldinfo['flags']) ? $this_fieldinfo['flags'] : array();
        $this->counters = isset($this_fieldinfo['counters']) ? $this_fieldinfo['counters'] : array();
        $this->values = isset($this_fieldinfo['values']) ? $this_fieldinfo['values'] : array();
        $this->history = isset($this_fieldinfo['history']) ? $this_fieldinfo['history'] : array();
        $this->field_id = isset($this_fieldinfo['field_id']) ? $this_fieldinfo['field_id'] : 0;
        $this->field_name = isset($this_fieldinfo['field_name']) ? $this_fieldinfo['field_name'] : 'Field';
        $this->field_token = isset($this_fieldinfo['field_token']) ? $this_fieldinfo['field_token'] : 'field';
        $this->field_type = isset($this_fieldinfo['field_type']) ? $this_fieldinfo['field_type'] : '';
        $this->field_group = isset($this_fieldinfo['field_group']) ? $this_fieldinfo['field_group'] : '';
        $this->field_multipliers = isset($this_fieldinfo['field_multipliers']) ? $this_fieldinfo['field_multipliers'] : array();
        $this->field_overlays = isset($this_fieldinfo['field_overlays']) ? $this_fieldinfo['field_overlays'] : array();
        $this->field_mechas = isset($this_fieldinfo['field_mechas']) ? $this_fieldinfo['field_mechas'] : array();
        $this->field_description = isset($this_fieldinfo['field_description']) ? $this_fieldinfo['field_description'] : '';
        $this->field_background = isset($this_fieldinfo['field_background']) ? $this_fieldinfo['field_background'] : 'field';
        $this->field_foreground = isset($this_fieldinfo['field_foreground']) ? $this_fieldinfo['field_foreground'] : 'field';
        $this->field_background_attachments = isset($this_fieldinfo['field_background_attachments']) ? $this_fieldinfo['field_background_attachments'] : array();
        $this->field_foreground_attachments = isset($this_fieldinfo['field_foreground_attachments']) ? $this_fieldinfo['field_foreground_attachments'] : array();
        $this->field_music = isset($this_fieldinfo['field_music']) ? $this_fieldinfo['field_music'] : 'field';

        // Define the internal field base values using the fields index array
        $this->field_base_name = isset($this_fieldinfo['field_base_name']) ? $this_fieldinfo['field_base_name'] : $this->field_name;
        $this->field_base_token = isset($this_fieldinfo['field_base_token']) ? $this_fieldinfo['field_base_token'] : $this->field_token;
        $this->field_base_type = isset($this_fieldinfo['field_base_type']) ? $this_fieldinfo['field_base_type'] : $this->field_type;
        $this->field_base_multipliers = isset($this_fieldinfo['field_base_multipliers']) ? $this_fieldinfo['field_base_multipliers'] : $this->field_multipliers;
        $this->field_base_description = isset($this_fieldinfo['field_base_description']) ? $this_fieldinfo['field_base_description'] : $this->field_description;
        $this->field_base_background = isset($this_fieldinfo['field_base_background']) ? $this_fieldinfo['field_base_background'] : $this->field_background;
        $this->field_base_foreground = isset($this_fieldinfo['field_base_foreground']) ? $this_fieldinfo['field_base_foreground'] : $this->field_foreground;
        $this->field_base_background_attachments = isset($this_fieldinfo['field_base_background_attachments']) ? $this_fieldinfo['field_base_background_attachments'] : $this->field_background_attachments;
        $this->field_base_foreground_attachments = isset($this_fieldinfo['field_base_foreground_attachments']) ? $this_fieldinfo['field_base_foreground_attachments'] : $this->field_foreground_attachments;
        $this->field_base_music = isset($this_fieldinfo['field_base_music']) ? $this_fieldinfo['field_base_music'] : $this->field_music;

        // Update the session variable
        $this->update_session();

        // Return true on success
        return true;

    }

    // Define public print functions for markup generation
    public function print_field_name(){ return '<span class="field_name field_type field_type_'.(!empty($this->field_type) ? $this->field_type : 'none').'">'.$this->field_name.'</span>'; }
    //public function print_field_name(){ return '<span class="field_name field_type field_type_'.(!empty($this->field_type) ? $this->field_type : 'none').'">'.$this->field_name.'</span>'; }
    public function print_field_token(){ return '<span class="field_token">'.$this->field_token.'</span>'; }
    public function print_field_type(){ return '<span class="field_type field_type_'.(!empty($this->field_type) ? $this->field_type : 'none').'">'.!empty($this->field_type) ? ucfirst($this->field_type) : 'Neutral'.'</span>'; }
    public function print_field_group(){
        $temp_index = array('MMRPG' => 'Mega Man RPG Fields', 'MM00' => 'Mega Man 0 Fields', 'MM01' => 'Mega Man 1 Fields', 'MM02' => 'Mega Man 2 Fields', 'MM03' => 'Mega Man 3 Fields', 'MM04' => 'Mega Man 4 Fields');
        return '<span class="field_group field_group_'.(!empty($this->field_group) ? $this->field_group : 'MMRPG').'">'.!empty($this->field_group) ? $temp_index[$this->field_group] : 'Unknown'.'</span>';
    }
    public function print_field_description(){ return '<span class="field_description">'.$this->field_description.'</span>'; }
    public function print_field_background(){ return '<span class="field_background">'.$this->field_background.'</span>'; }
    public function print_field_foreground(){ return '<span class="field_foreground">'.$this->field_foreground.'</span>'; }


    // -- INDEX FUNCTIONS -- //

    /**
     * Get a list of all field index fields as an array or, optionally, imploded into a string
     * @param bool $implode
     * @return mixed
     */
    public static function get_index_fields($implode = false){

        // Define the various index fields for field objects
        $index_fields = array(
            'field_id',
            'field_token',
            'field_number',
            'field_name',
            'field_game',
            'field_group',
            'field_class',
            'field_master',
            'field_master2',
            'field_mechas',
            'field_editor',
            'field_image',
            'field_type',
            'field_type2',
            'field_multipliers',
            'field_description',
            'field_description2',
            'field_music',
            'field_music_name',
            'field_music_link',
            'field_background',
            'field_background_frame',
            'field_background_attachments',
            'field_foreground',
            'field_foreground_frame',
            'field_foreground_attachments',
            'field_functions',
            'field_flag_hidden',
            'field_flag_complete',
            'field_flag_published',
            'field_order'
            );

        // Implode the index fields into a string if requested
        if ($implode){
            $index_fields = implode(', ', $index_fields);
        }

        // Return the index fields, array or string
        return $index_fields;

    }

    /**
     * Get the entire field index array with parsed info
     * @param bool $parse_data
     * @return array
     */
    public static function get_index($include_hidden = false, $include_unpublished = false){

        // Pull in global variables
        $db = cms_database::get_database();

        // Define the query condition based on args
        $temp_where = '';
        if (!$include_hidden){ $temp_where .= 'AND field_flag_hidden = 0 '; }
        if (!$include_unpublished){ $temp_where .= 'AND field_flag_published = 1 '; }

        // Collect every type's info from the database index
        $field_fields = self::get_index_fields(true);
        $field_index = $db->get_array_list("SELECT {$field_fields} FROM mmrpg_index_fields WHERE field_id <> 0 {$temp_where};", 'field_token');

        // Parse and return the data if not empty, else nothing
        if (!empty($field_index)){
            $field_index = self::parse_index($field_index);
            return $field_index;
        } else {
            return array();
        }

    }

    /**
     * Get the tokens for all fields in the global index
     * @return array
     */
    public static function get_index_tokens($include_hidden = false, $include_unpublished = false){

        // Pull in global variables
        $db = cms_database::get_database();

        // Define the query condition based on args
        $temp_where = '';
        if (!$include_hidden){ $temp_where .= 'AND field_flag_hidden = 0 '; }
        if (!$include_unpublished){ $temp_where .= 'AND field_flag_published = 1 '; }

        // Collect an array of field tokens from the database
        $field_index = $db->get_array_list("SELECT field_token FROM mmrpg_index_fields WHERE field_id <> 0 {$temp_where};", 'field_token');

        // Return the tokens if not empty, else nothing
        if (!empty($field_index)){
            $field_tokens = array_keys($field_index);
            return $field_tokens;
        } else {
            return array();
        }

    }

    // Define a function for pulling a custom field index
    public static function get_index_custom($field_tokens = array()){

        // Pull in global variables
        $db = cms_database::get_database();

        // Generate a token string for the database query
        $field_tokens_string = array();
        foreach ($field_tokens AS $field_token){ $field_tokens_string[] = "'{$field_token}'"; }
        $field_tokens_string = implode(', ', $field_tokens_string);

        // Collect the requested field's info from the database index
        $field_fields = self::get_index_fields(true);
        $field_index = $db->get_array_list("SELECT {$field_fields} FROM mmrpg_index_fields WHERE field_token IN ({$field_tokens_string});", 'field_token');

        // Parse and return the data if not empty, else nothing
        if (!empty($field_index)){
            $field_index = self::parse_index($field_index);
            return $field_index;
        } else {
            return array();
        }

    }

    // Define a public function for collecting index data from the database
    public static function get_index_info($field_token){

        // Pull in global variables
        $db = cms_database::get_database();

        // Collect this field's info from the database index
        $lookup = !is_numeric($field_token) ? "field_token = '{$field_token}'" : "field_id = {$field_token}";
        $field_fields = self::get_index_fields(true);
        $field_index = $db->get_array("SELECT {$field_fields} FROM mmrpg_index_fields WHERE {$lookup};", 'field_token');

        // Parse and return the data if not empty, else nothing
        if (!empty($field_index)){
            $field_index = self::parse_index_info($field_index);
            return $field_index;
        } else {
            return array();
        }

    }

    // Define a public function for parsing a field index array in bulk
    public static function parse_index($field_index){

        // Loop through each entry and parse its data
        foreach ($field_index AS $token => $info){
            $field_index[$token] = self::parse_index_info($info);
        }

        // Return the parsed index
        return $field_index;

    }

    // Define a public function for reformatting database data into proper arrays
    public static function parse_index_info($field_info){

        // Return false if empty
        if (empty($field_info)){ return false; }

        // If the information has already been parsed, return as-is
        if (!empty($field_info['_parsed'])){ return $field_info; }
        else { $field_info['_parsed'] = true; }

        // Explode json encoded fields into expanded array objects
        $temp_field_names = array('field_master2', 'field_mechas', 'field_multipliers', 'field_music_link', 'field_background_frame', 'field_foreground_frame', 'field_background_attachments', 'field_foreground_attachments');
        foreach ($temp_field_names AS $field_name){
            if (!empty($field_info[$field_name])){ $field_info[$field_name] = json_decode($field_info[$field_name], true); }
            else { $field_info[$field_name] = array(); }
        }

        // Return the parsed field info
        return $field_info;
    }


    // -- SESSION FUNCTIONS -- //

    // Define a public function updating internal variables
    public function update_variables(){

        // Update parent objects first
        //$this->battle->update_variables();

        // Return true on success
        return true;

    }

    // Define a public function for updating this field's session
    public function update_session(){

        // Update any internal counters
        $this->update_variables();

        // Request parent battle object to update as well
        //$this->battle->update_session();

        // Update the session with the export array
        $this_data = $this->export_array();
        $_SESSION['FIELDS'][$this->battle->battle_id][$this->field_id] = $this_data;
        $this->battle->battle_field = $this;

        // Return true on success
        return true;

    }

    // Define a function for exporting the current data
    public function export_array(){

        // Return all internal field fields in array format
        return array(
            'battle_id' => $this->battle_id,
            'battle_token' => $this->battle_token,
            'field_id' => $this->field_id,
            'field_name' => $this->field_name,
            'field_token' => $this->field_token,
            'field_type' => $this->field_type,
            'field_group' => $this->field_group,
            'field_multipliers' => $this->field_multipliers,
            'field_mechas' => $this->field_mechas,
            'field_description' => $this->field_description,
            'field_background' => $this->field_background,
            'field_foreground' => $this->field_foreground,
            'field_background_attachments' => $this->field_background_attachments,
            'field_foreground_attachments' => $this->field_foreground_attachments,
            'field_music' => $this->field_music,
            'field_base_name' => $this->field_base_name,
            'field_base_token' => $this->field_base_token,
            'field_base_type' => $this->field_base_type,
            'field_base_multipliers' => $this->field_base_multipliers,
            'field_base_description' => $this->field_base_description,
            'field_base_background' => $this->field_base_background,
            'field_base_foreground' => $this->field_base_foreground,
            'field_base_background_attachments' => $this->field_base_background_attachments,
            'field_base_foreground_attachments' => $this->field_base_foreground_attachments,
            'field_base_music' => $this->field_base_music,
            'flags' => $this->flags,
            'counters' => $this->counters,
            'values' => $this->values,
            'history' => $this->history
            );

    }

    // Define a static function for printing out the field's database markup
    public static function print_database_markup($field_info, $print_options = array()){

        // Define the markup variable
        $this_markup = '';

        // Define the global variables
        global $db;
        global $mmrpg_index, $this_current_uri, $this_current_url;
        global $mmrpg_database_players, $mmrpg_database_robots, $mmrpg_database_mechas, $mmrpg_database_abilities, $mmrpg_database_types;

        // Define the print style defaults
        if (!isset($print_options['layout_style'])){ $print_options['layout_style'] = 'website'; }
        if ($print_options['layout_style'] == 'website'){
            if (!isset($print_options['show_basics'])){ $print_options['show_basics'] = true; }
            if (!isset($print_options['show_icon'])){ $print_options['show_icon'] = true; }
            if (!isset($print_options['show_description'])){ $print_options['show_description'] = true; }
            if (!isset($print_options['show_sprites'])){ $print_options['show_sprites'] = true; }
            if (!isset($print_options['show_records'])){ $print_options['show_records'] = true; }
            if (!isset($print_options['show_footer'])){ $print_options['show_footer'] = true; }
            if (!isset($print_options['show_key'])){ $print_options['show_key'] = false; }
        } elseif ($print_options['layout_style'] == 'website_compact'){
            if (!isset($print_options['show_basics'])){ $print_options['show_basics'] = true; }
            if (!isset($print_options['show_icon'])){ $print_options['show_icon'] = true; }
            if (!isset($print_options['show_description'])){ $print_options['show_description'] = false; }
            if (!isset($print_options['show_sprites'])){ $print_options['show_sprites'] = false; }
            if (!isset($print_options['show_records'])){ $print_options['show_records'] = false; }
            if (!isset($print_options['show_footer'])){ $print_options['show_footer'] = true; }
            if (!isset($print_options['show_key'])){ $print_options['show_key'] = false; }
        }

        // Collect the field sprite dimensions
        $field_image_size = !empty($field_info['field_image_size']) ? $field_info['field_image_size'] : 40;
        $field_image_size_text = $field_image_size.'x'.$field_image_size;
        $field_image_token = !empty($field_info['field_image']) ? $field_info['field_image'] : $field_info['field_token'];
        $field_type_token = !empty($field_info['field_type']) ? $field_info['field_type'] : 'none';
        if (!empty($field_info['field_type2'])){ $field_type_token .= '_'.$field_info['field_type2']; }

        // Define the sprite sheet alt and title text
        $field_sprite_size = $field_image_size * 2;
        $field_sprite_size_text = $field_sprite_size.'x'.$field_sprite_size;
        $field_sprite_title = $field_info['field_name'];
        //$field_sprite_title = $field_info['field_number'].' '.$field_info['field_name'];
        //$field_sprite_title .= ' Sprite Sheet | Robot Database | Mega Man RPG Prototype';

        // Define the sprite frame index for robot images
        $field_sprite_frames = array('base','taunt','victory','defeat','command','damage');

        // Collect the robot master info if applicable
        $robot_master_info = array();
        if (!empty($field_info['field_master']) && !empty($mmrpg_database_robots[$field_info['field_master']])){ $robot_master_info = $mmrpg_database_robots[$field_info['field_master']]; }

        // Collect the robot master info if applicable
        $robot_master_info_array = array();
        $temp_robot_masters = array();
        if (!empty($field_info['field_master']) && $field_info['field_master'] != 'robot'){ $temp_robot_masters[] = $field_info['field_master']; }
        if (!empty($field_info['field_master2'])){ $temp_robot_masters = array_merge($temp_robot_masters, $field_info['field_master2']); }
        if (!empty($temp_robot_masters)){
            foreach ($temp_robot_masters AS $key => $token){
                if (!empty($mmrpg_database_robots[$token])){
                    $robot_master_info = $mmrpg_database_robots[$token];
                    $robot_master_info_array[] = $robot_master_info;
                }
            }
        }

        // Collect the robot master info if applicable
        $robot_mecha_info_array = array();
        if (!empty($field_info['field_mechas'])){
            foreach ($field_info['field_mechas'] AS $key => $token){
                if (!empty($mmrpg_database_mechas[$token])){
                    $robot_mecha_info = $mmrpg_database_mechas[$token];
                    $robot_mecha_info_array[] = $robot_mecha_info;
                }
            }
        }

        // Start the output buffer
        ob_start();
        ?>
        <div class="database_container database_field_container" data-token="<?=$field_info['field_token']?>" style="<?= $print_options['layout_style'] == 'website_compact' ? 'margin-bottom: 2px !important;' : '' ?>">
            <a class="anchor" id="<?=$field_info['field_token']?>">&nbsp;</a>

            <div class="subbody event event_triple event_visible" data-token="<?=$field_info['field_token']?>" style="min-height: 90px; <?= $print_options['layout_style'] == 'website_compact' ? 'margin-bottom: 2px !important;' : '' ?>">

                <? if($print_options['show_icon']): ?>
                    <div class="this_sprite sprite_left" style="height: 40px;">
                        <? if($print_options['show_key'] !== false): ?>
                            <div class="mugshot field_type field_type_<?= !empty($field_info['field_type']) ? $field_info['field_type'] : 'none' ?>" style="font-size: 9px; line-height: 11px; text-align: center; margin-bottom: 2px; padding: 0 0 1px !important;"><?= 'No.'.($print_options['show_key'] + 1) ?></div>
                        <? endif; ?>
                        <? if ($field_image_token != 'field'){ ?>
                            <div class="mugshot field_type field_type_<?= !empty($field_info['field_type']) ? $field_info['field_type'] : 'none' ?>"><div style="background-image: url(images/fields/<?= $field_image_token ?>/battle-field_avatar.png?<?=MMRPG_CONFIG_CACHE_DATE?>); background-size: 50px 50px; background-position: -5px -5px;" class="sprite sprite_field sprite_40x40 sprite_40x40_mug sprite_size_<?= $field_image_size_text ?> sprite_size_<?= $field_image_size_text ?>_mug field_status_active field_position_active"><?=$field_info['field_name']?>'s Avatar</div></div>
                        <? } else { ?>
                            <div class="mugshot field_type field_type_<?= !empty($field_info['field_type']) ? $field_info['field_type'] : 'none' ?>"><div style="background-image: none; background-color: #000000; background-color: rgba(0, 0, 0, 0.6); " class="sprite sprite_field sprite_40x40 sprite_40x40_mug sprite_size_<?= $field_image_size_text ?> sprite_size_<?= $field_image_size_text ?>_mug field_status_active field_position_active">No Image</div></div>
                        <? }?>
                    </div>
                <? endif; ?>

                <? if($print_options['show_basics']): ?>
                    <h2 class="header header_left field_type_<?= $field_type_token ?>" style="margin-right: 0;">
                        <? if($print_options['layout_style'] == 'website_compact'): ?>
                            <a href="database/fields/<?= $field_info['field_token'] ?>/"><?= $field_info['field_name'] ?></a>
                        <? else: ?>
                            <?= $field_info['field_name'].(!preg_match('/s$/i', $field_info['field_name']) ? '&#39;s' : '&#39;') ?> Data
                        <? endif; ?>
                        <? if (!empty($field_info['field_type'])): ?>
                            <span class="header_core ability_type" style="border-color: rgba(0, 0, 0, 0.2) !important; background-color: rgba(0, 0, 0, 0.2) !important;"><?= ucfirst($field_info['field_type']) ?> Type</span>
                        <? else: ?>
                            <span class="header_core ability_type" style="border-color: rgba(0, 0, 0, 0.2) !important; background-color: rgba(0, 0, 0, 0.2) !important;">Neutral Type</span>
                        <? endif; ?>
                    </h2>
                    <div class="body body_left" style="margin-right: 0; padding: 2px 3px; min-height: 100px;">
                        <table class="full" style="margin: 5px auto 10px;">
                            <colgroup>
                                <col width="48%" />
                                <col width="1%" />
                                <col width="48%" />
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td class="right">
                                        <label style="display: block; float: left;">Name :</label>
                                        <span class="field_type"><?= $field_info['field_name'] ?></span>
                                    </td>
                                    <td class="middle">&nbsp;</td>
                                    <td class="right">
                                        <?
                                        // Define the source game string
                                        if ($field_info['field_token'] == 'intro-field'){ $temp_source_string = 'Mega Man RPG Prototype'; }
                                        elseif ($field_info['field_token'] == 'light-laboratory' || $field_info['field_token'] == 'wily-castle'){ $temp_source_string = 'Mega Man'; }
                                        elseif ($field_info['field_token'] == 'cossack-citadel'){ $temp_source_string = 'Mega Man 4'; }
                                        elseif ($field_info['field_token'] == 'oil-wells' || $field_info['field_token'] == 'clock-citadel'){ $temp_source_string = 'Mega Man Powered Up'; }
                                        elseif ($field_info['field_game'] == 'MM01'){ $temp_source_string = 'Mega Man'; }
                                        elseif ($field_info['field_game'] == 'MM00' || $field_info['field_game'] == 'MMRPG'){ $temp_source_string = 'Mega Man RPG Prototype'; }
                                        elseif (preg_match('/^MM([0-9]{2})$/', $field_info['field_game'])){ $temp_source_string = 'Mega Man '.ltrim(str_replace('MM', '', $field_info['field_game']), '0'); }
                                        else { $temp_source_string = '&hellip;'; }
                                        ?>
                                        <label style="display: block; float: left;">Source :</label>
                                        <span class="field_type"><?= $temp_source_string ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="right">
                                        <label style="display: block; float: left;">Type :</label>
                                        <a href="database/fields/<?= !empty($field_info['field_type']) ? $field_info['field_type'] : 'none' ?>/" class="field_type field_type_<?= !empty($field_info['field_type']) ? $field_info['field_type'] : 'none' ?>"><?= !empty($field_info['field_type']) ? ucfirst($field_info['field_type']) : 'Neutral' ?> Type</a>
                                    </td>
                                    <td class="middle">&nbsp;</td>
                                    <td class="right">
                                        <label style="display: block; float: left;">Music :</label>
                                        <? if(!empty($field_info['field_music_name']) && !empty($field_info['field_music_link'])): ?>
                                            <? if(is_array($field_info['field_music_link'])):?>
                                                <? foreach($field_info['field_music_link'] AS $key => $link): ?>
                                                    <a href="<?= $link ?>" target="_blank" class="field_type field_type_<?= !empty($field_info['field_type']) ? $field_info['field_type'] : 'none' ?>"><?= $key == 0 ? $field_info['field_music_name'] : $key + 1 ?></a>
                                                <? endforeach; ?>
                                            <? else: ?>
                                                <a href="<?= $field_info['field_music_link'] ?>" target="_blank" class="field_type field_type_<?= !empty($field_info['field_type']) ? $field_info['field_type'] : 'none' ?>"><?= $field_info['field_music_name'] ?></a>
                                            <? endif; ?>
                                        <? else: ?>
                                            <span class="field_type">???</span>
                                        <? endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td  class="right">
                                        <label style="display: block; float: left;"><?= empty($robot_master_info_array) || count($robot_master_info_array) == 1 ? 'Master' : 'Masters' ?> :</label>
                                        <?
                                        // Define the special stages
                                        $temp_special_stages = array('final-destination', 'final-destination-2', 'final-destination-3');
                                        // Loop through and display support master links
                                        if (!empty($robot_master_info_array)){
                                            foreach ($robot_master_info_array AS $key => $robot_master_info){
                                                 if ($robot_master_info['robot_class'] == 'master' && preg_match('/-[0-9]+$/', $robot_master_info['robot_token'])){ $robot_master_info['robot_name'] .= ' '.(preg_replace('/^(.*?)-([0-9]+)$/', '$2', $robot_master_info['robot_token'])); }
                                                 ?>
                                                     <a href="database/robots/<?= $robot_master_info['robot_token'] ?>/" class="field_type field_type_<?= (!empty($robot_master_info['robot_core']) ? $robot_master_info['robot_core'] : 'none').(!empty($robot_master_info['robot_type2']) ? '_'.$robot_master_info['robot_type2'] : '') ?>"><?= $robot_master_info['robot_name'] ?></a>
                                                 <?
                                            }
                                        }
                                        // Else if this was a special stage
                                        elseif (in_array($field_info['field_token'], $temp_special_stages)){
                                            ?>
                                                <span class="field_type">???</span>
                                            <?
                                        }
                                        // Else if there are none to display
                                        else {
                                            ?>
                                                <span class="field_type">None</span>
                                            <?
                                        }
                                        ?>
                                    </td>
                                    <td class="center">&nbsp;</td>
                                    <td  class="right">
                                        <label style="display: block; float: left;"><?= count($robot_mecha_info_array) == 1 ? 'Mecha' : 'Mechas' ?> :</label>
                                        <?
                                        // Define the special stages
                                        $temp_special_stages = array('final-destination', 'final-destination-2', 'final-destination-3', 'prototype-complete');
                                        // Loop through and display support mecha links
                                        if (!in_array($field_info['field_token'], $temp_special_stages) && !empty($robot_mecha_info_array)){
                                            foreach ($robot_mecha_info_array AS $key => $robot_mecha_info){
                                                 if ($robot_mecha_info['robot_class'] == 'mecha' && preg_match('/-[0-9]+$/', $robot_mecha_info['robot_token'])){ $robot_mecha_info['robot_name'] = (preg_replace('/^(.*?)-([0-9]+)$/', '$2', $robot_mecha_info['robot_token'])); }
                                                 ?>
                                                     <a href="database/mechas/<?= $robot_mecha_info['robot_token'] ?>/" class="robot_type robot_type_<?= (!empty($robot_mecha_info['robot_core']) ? $robot_mecha_info['robot_core'] : 'none').(!empty($robot_mecha_info['robot_type2']) ? '_'.$robot_mecha_info['robot_type2'] : '') ?>"><?= $robot_mecha_info['robot_name'] ?></a>
                                                 <?
                                            }
                                        }
                                        // Else if this was a special stage
                                        elseif (in_array($field_info['field_token'], $temp_special_stages)){
                                            ?>
                                                <span class="robot_type">???</span>
                                            <?
                                        }
                                        // Else if there are none to display
                                        else {
                                            ?>
                                                <span class="robot_type">None</span>
                                            <?
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="right" colspan="3" style="padding: 8px 5px;">
                                        <label style="display: block; float: left;">Multipliers :</label>
                                        <?
                                        if (!empty($field_info['field_multipliers'])){
                                            $temp_string = array();
                                            asort($field_info['field_multipliers']);
                                            $field_info['field_multipliers'] = array_reverse($field_info['field_multipliers']);
                                            foreach ($field_info['field_multipliers'] AS $temp_token => $temp_value){
                                                $temp_string[] = '<span style="padding: 4px 8px; line-height: 24px; " class="field_multiplier field_type field_type_'.$temp_token.'">'.$mmrpg_index['types'][$temp_token]['type_name'].' x '.number_format($temp_value, 1).'</span>';
                                            }
                                            echo implode(' ', $temp_string);
                                        } else {
                                            echo '<span style="padding: 4px 8px; line-height: 24px; " class="field_multiplier field_type field_type_none">None</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                <? endif; ?>

                <? if($print_options['show_description'] && !empty($field_info['field_description2'])): ?>

                    <h2 class="header header_left field_type_<?= $field_type_token ?>" style="margin-right: 0;">
                        <?= $field_info['field_name'] ?>&#39;s Description
                    </h2>
                    <div class="body body_left" style="margin-right: 0; margin-bottom: 5px; padding: 2px 0; min-height: 10px;">
                        <table class="full" style="margin: 5px auto 10px;">
                            <colgroup>
                                <col width="100%" />
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td class="right">
                                        <div class="field_description" style="text-align: justify; padding: 0 4px;"><?= $field_info['field_description2'] ?></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                <? endif; ?>


                <? if($print_options['show_sprites'] && $field_image_token != 'field'): ?>

                    <h2 class="header header_full field_type_<?= $field_type_token ?>" style="margin: 10px 0 0; text-align: left;">
                        <?= $field_info['field_name'].(!preg_match('/s$/i', $field_info['field_name']) ? '&#39;s' : '&#39;') ?> Sprites
                    </h2>
                    <div class="body body_full" style="margin: 0; padding: 10px; min-height: auto;">
                        <div id="sprite_container" style="border: 1px solid rgba(0, 0, 0, 0.20); border-radius: 0.5em; -moz-border-radius: 0.5em; -webkit-border-radius: 0.5em; background: #191919 none scroll repeat -10px -30px; overflow: hidden; padding: 0; margin-bottom: 10px;">
                            <div class="sprite_background" style="border: 0 none transparent; border-radius: 0.5em; -moz-border-radius: 0.5em; -webkit-border-radius: 0.5em; background: transparent url(images/fields/<?= $field_info['field_background'] ?>/battle-field_background_base.gif?<?= MMRPG_CONFIG_CACHE_DATE ?>) scroll repeat center center; overflow: hidden; height: 244px;">
                                <div class="sprite_foreground" style="border: 0 none transparent; border-radius: 0.5em; -moz-border-radius: 0.5em; -webkit-border-radius: 0.5em; background: transparent url(images/fields/<?= $field_info['field_background'] ?>/battle-field_foreground_base.png?<?= MMRPG_CONFIG_CACHE_DATE ?>) scroll repeat center center; overflow: hidden; height: 244px;">
                                    &nbsp;
                                </div>
                            </div>
                        </div>
                        <?
                        // Define the editor title based on ID
                        $temp_editor_title = 'Undefined';
                        $temp_final_divider = '<span style="color: #565656;"> | </span>';
                        if (empty($field_info['field_image_editor'])){ $field_info['field_image_editor'] = 412; }
                        if (!empty($field_info['field_image_editor'])){
                            $temp_break = false;
                            if ($field_info['field_image_editor'] == 412){ $temp_editor_title = 'Adrian Marceau / Ageman20XX'; }
                            elseif ($field_info['field_image_editor'] == 110){ $temp_break = true; $temp_editor_title = 'MetalMarioX100 / EliteP1</strong> <span style="color: #565656;"> | </span> Assembly by <strong>Adrian Marceau / Ageman20XX'; }
                            elseif ($field_info['field_image_editor'] == 18){ $temp_break = true; $temp_editor_title = 'Sean Adamson / MetalMan</strong> <span style="color: #565656;"> | </span> Assembly by <strong>Adrian Marceau / Ageman20XX'; }
                            elseif ($field_info['field_image_editor'] == 4117){ $temp_break = true; $temp_editor_title = 'Jonathan Backstrom / Rhythm_BCA</strong> <span style="color: #565656;"> | </span> Assembly by <strong>Adrian Marceau / Ageman20XX'; }
                            if ($temp_break){ $temp_final_divider = '<br />'; }
                        }
                        $temp_is_capcom = true;
                        $temp_is_original = array();
                        if (in_array($field_info['field_token'], $temp_is_original)){ $temp_is_capcom = false; }
                        if ($temp_is_capcom){
                            echo '<p class="text text_editor" style="text-align: center; color: #868686; font-size: 10px; line-height: 13px; margin-top: 6px;">Sprite Editing by <strong>'.$temp_editor_title.'</strong> '.$temp_final_divider.' Original Artwork by <strong>Capcom</strong></p>'."\n";
                        } else {
                            echo '<p class="text text_editor" style="text-align: center; color: #868686; font-size: 10px; line-height: 13px; margin-top: 6px;">Sprite Editing by <strong>'.$temp_editor_title.'</strong> '.$temp_final_divider.' Original Field by <strong>Adrian Marceau</strong></p>'."\n";
                        }
                        ?>
                    </div>

                <? endif; ?>

                <? if($print_options['show_footer'] && $print_options['layout_style'] == 'website'): ?>

                    <a class="link link_top" data-href="#top" rel="nofollow">^ Top</a>
                    <a class="link link_permalink permalink" href="database/fields/<?= $field_info['field_token'] ?>/" rel="permalink">+ Permalink</a>

                <? elseif($print_options['show_footer'] && $print_options['layout_style'] == 'website_compact'): ?>

                    <a class="link link_top" data-href="#top" rel="nofollow">^ Top</a>
                    <a class="link link_permalink permalink" href="database/fields/<?= $field_info['field_token'] ?>/" rel="permalink">+ View More</a>

                <? endif; ?>

            </div>
        </div>
        <?
        // Collect the outbut buffer contents
        $this_markup = trim(ob_get_clean());

        // Return the generated markup
        return $this_markup;

    }

    // Define a static function for printing out the field's title markup
    public static function print_editor_title_markup($player_info, $field_info){
        // Pull in global variables
        global $mmrpg_index, $db;
        // Collect the approriate database indexes
        $mmrpg_database_robots = $db->get_array_list("SELECT * FROM mmrpg_index_robots WHERE robot_flag_complete = 1;", 'robot_token');
        // Generate the field option markup
        $temp_player_token = $player_info['player_token'];
        if (!isset($mmrpg_index['players'][$temp_player_token])){ return false; }
        $player_info = $mmrpg_index['players'][$temp_player_token];
        $temp_field_token = $field_info['field_token'];
        $field_info = rpg_field::get_index_info($temp_field_token);
        if (empty($field_info)){ return false; }
        $player_flag_copycore = !empty($player_info['player_core']) && $player_info['player_core'] == 'copy' ? true : false;
        $temp_field_type = !empty($field_info['field_type']) ? $mmrpg_index['types'][$field_info['field_type']] : false;
        $temp_field_type2 = !empty($field_info['field_type2']) ? $mmrpg_index['types'][$field_info['field_type2']] : false;
        $temp_field_master = !empty($field_info['field_master']) ? rpg_robot::parse_index_info($mmrpg_database_robots[$field_info['field_master']]) : false;
        $temp_field_mechas = !empty($field_info['field_mechas']) ? $field_info['field_mechas'] : array();
        foreach ($temp_field_mechas AS $key => $token){
            $temp_mecha = rpg_robot::parse_index_info($mmrpg_database_robots[$token]);
            if (!empty($temp_mecha)){ $temp_field_mechas[$key] = $temp_mecha['robot_name'];  }
            else { unset($temp_field_mechas[$key]); }
        }
        $temp_field_title = $field_info['field_name'];
        if (!empty($temp_field_type)){ $temp_field_title .= ' ('.$temp_field_type['type_name'].' Type)'; }
        if (!empty($temp_field_type2)){ $temp_field_title = str_replace('Type', '/ '.$temp_field_type2['type_name'].' Type', $temp_field_title); }
        $temp_field_title .= '  // ';
        if (!empty($temp_field_master)){ $temp_field_title .= 'Robot : '.$temp_field_master['robot_name'].' // '; }
        if (!empty($temp_field_mechas)){ $temp_field_title .= 'Mecha : '.implode(', ', array_unique($temp_field_mechas)).' // '; }
        /*
        if (!empty($field_info['field_description'])){
            //$temp_find = array('{RECOVERY}', '{RECOVERY2}', '{DAMAGE}', '{DAMAGE2}');
            //$temp_replace = array($temp_field_recovery, $temp_field_recovery2, $temp_field_damage, $temp_field_damage2);
            //$temp_description = str_replace($temp_find, $temp_replace, $field_info['field_description']);
            //$temp_field_title .= ' <br />'.$temp_description;
            $temp_field_title .= $field_info['field_description'];
        }
        */
        // Return the generated option markup
        return $temp_field_title;
    }


    // Define a static function for printing out the field's title markup
    public static function print_editor_option_markup($player_info, $field_info){
        // Pull in global variables
        global $mmrpg_index;
        // Generate the field option markup
        $temp_player_token = $player_info['player_token'];
        if (!isset($mmrpg_index['players'][$temp_player_token])){ return false; }
        $player_info = $mmrpg_index['players'][$temp_player_token];
        $temp_field_token = $field_info['field_token'];
        $field_info = rpg_field::get_index_info($temp_field_token);
        if (empty($field_info)){ return false; }

        // DEBUG
        //if ($temp_player_token == 'oil-man' && $temp_field_token == 'oil-shooter'){ die('WHY?!'); }

        $temp_field_type = !empty($field_info['field_type']) ? $mmrpg_index['types'][$field_info['field_type']] : false;
        $temp_field_type2 = !empty($field_info['field_type2']) ? $mmrpg_index['types'][$field_info['field_type2']] : false;
        $temp_field_label = $field_info['field_name'];
        $temp_field_title = rpg_field::print_editor_title_markup($player_info, $field_info);
        $temp_field_title_plain = strip_tags(str_replace('<br />', '&#10;', $temp_field_title));
        $temp_field_title_tooltip = htmlentities($temp_field_title, ENT_QUOTES, 'UTF-8');
        $temp_field_option = $field_info['field_name'];
        if (!empty($temp_field_type)){ $temp_field_option .= ' | '.$temp_field_type['type_name']; }
        if (!empty($temp_field_type2)){ $temp_field_option .= ' / '.$temp_field_type2['type_name']; }
        //if (!empty($field_info['field_damage'])){ $temp_field_option .= ' | D:'.$field_info['field_damage']; }
        //if (!empty($field_info['field_recovery'])){ $temp_field_option .= ' | R:'.$field_info['field_recovery']; }
        //if (!empty($field_info['field_accuracy'])){ $temp_field_option .= ' | A:'.$field_info['field_accuracy']; }
        if (!empty($temp_field_energy)){ $temp_field_option .= ' | E:'.$temp_field_energy; }
        // Return the generated option markup
        $this_option_markup = '<option value="'.$temp_field_token.'" data-label="'.$temp_field_label.'" data-type="'.(!empty($temp_field_type) ? $temp_field_type['type_token'] : 'none').'" data-type2="'.(!empty($temp_field_type2) ? $temp_field_type2['type_token'] : '').'" title="'.$temp_field_title_plain.'" data-tooltip="'.$temp_field_title_tooltip.'">'.$temp_field_option.'</option>';

        // Return the generated option markup
        return $this_option_markup;

    }

}
?>