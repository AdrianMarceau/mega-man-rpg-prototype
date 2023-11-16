<?
/**
 * Mega Man RPG Battle Object
 * <p>The base class for all battle objects in the Mega Man RPG Prototype.</p>
 */
class rpg_battle extends rpg_object {

    // Define global class variables
    public $events;
    public $actions;
    public $queue;
    public $endofturn_actions;

    // Define the constructor class
    public function __construct(){

        // Update the session keys for this object
        $this->session_key = 'BATTLES';
        $this->session_token = 'battle_token';
        $this->session_id = 'battle_id';
        $this->class = 'battle';
        $this->multi = 'battles';

        // Create any required sub-objects
        $this->queue['sound_effects'] = array();

        // Collect any provided arguments
        $args = func_get_args();

        // Collect current battle data from the function if available
        $this_battleinfo = isset($args[0]) ? $args[0] : array('battle_id' => 0, 'battle_token' => 'battle');

        // Now load the battle data from the session or index
        $this->battle_load($this_battleinfo);

        // Return true on success
        return true;

    }

    /**
     * Return a reference to the global battle object
     * @return rpg_battle
     */
    public static function get_battle(){
        $this_battle = isset($GLOBALS['this_battle']) ? $GLOBALS['this_battle'] : new rpg_battle();
        $this_battle->trigger_onload();
        return $this_battle;
    }

    // Define a public function for updating index info
    public static function update_index_info($battle_token, &$battle_info){
        global $db;

        // If the internal index has not been created yet, load it into memory
        if (!isset($db->INDEX['BATTLES'])){ rpg_battle::load_battle_index(); }

        // If prototype context is provided, we should add it to the battle
        global $this_prototype_data;
        if (!empty($this_prototype_data)){
            rpg_mission::insert_context($battle_info, $this_prototype_data);
        } else {
            rpg_mission::insert_context($battle_info, array(
                'this_player_token' => 'player',
                'this_current_chapter' => 1,
                'battle_phase' => 1,
                'battle_round' => 1
                ));
        }

        // Update and/or overwrite the current info in the index
        $db->INDEX['BATTLES'][$battle_token] = json_encode($battle_info);
        // Update the data in the session as well with provided
        $_SESSION['GAME']['values']['battle_index'][$battle_token] = json_encode($battle_info);

        // Return true on success
        return true;

    }

    // Define a public function requesting a battle index entry
    public static function get_index_info($battle_token, $raw = false){
        global $db;

        // If the internal index has not been created yet, load it into memory
        if (!isset($db->INDEX['BATTLES'])){ rpg_battle::load_battle_index(); }

        // If the requested index is not empty, return the entry
        if ($raw && !empty($db->INDEX['BATTLES_RAW'][$battle_token])){
            // Decode the info and return the array
            $battle_info = json_decode($db->INDEX['BATTLES_RAW'][$battle_token], true);
            //die('$battle_info = <pre>'.print_r($battle_info, true).'</pre>');
            return $battle_info;
        } elseif (!empty($db->INDEX['BATTLES'][$battle_token])){
            // Decode the info and return the array
            $battle_info = json_decode($db->INDEX['BATTLES'][$battle_token], true);
            //die('$battle_info = <pre>'.print_r($battle_info, true).'</pre>');
            return $battle_info;
        }
        // Otherwise if the battle index doesn't exist at all
        else {
            // Return false on failure
            return array();
        }

    }

    // Define a function for loading the battle index cache file
    public static function load_battle_index(){

        // Otherwise, continue normally
        global $db;

        // Create the index as an empty array
        $db->INDEX['BATTLES'] = array();

        // Default the battles index to an empty array
        $mmrpg_battles_index = array();

        // Define the cache file name and path given everything we've learned
        $cache_file_name = 'cache.battles.json';
        $cache_file_path = MMRPG_CONFIG_CACHE_PATH.'indexes/'.$cache_file_name;
        // Check to see if a file already exists and collect its last-modified date
        if (file_exists($cache_file_path)){ $cache_file_exists = true; $cache_file_date = date('Ymd-Hi', filemtime($cache_file_path)); }
        else { $cache_file_exists = false; $cache_file_date = '00000000-0000'; }

        // LOAD FROM CACHE if data exists and is current, otherwise continue so index can refresh and replace
        if (MMRPG_CONFIG_CACHE_INDEXES && $cache_file_exists && $cache_file_date >= MMRPG_CONFIG_CACHE_DATE){

            // Pull the battle index markup from the JSON file and decompress
            $cache_file_markup = file_get_contents($cache_file_path);
            $mmrpg_battles_index = json_decode($cache_file_markup, true);

        } else {

            // Indexing the battle data files and collect the generated markup
            $mmrpg_battles_index = rpg_battle::index_battle_data(false);
            $battles_cache_markup = json_encode($mmrpg_battles_index);

            // Write the index to a cache file, if caching is enabled
            if (MMRPG_CONFIG_CACHE_INDEXES === true){
                // Write the index to a cache file, if caching is enabled
                $this_cache_file = fopen($cache_file_path, 'w');
                fwrite($this_cache_file, $battles_cache_markup);
                fclose($this_cache_file);
            }

        }

        // Loop through the battles and index them after serializing
        foreach ($mmrpg_battles_index AS $token => $array){ $db->INDEX['BATTLES'][$token] = json_encode($array); }
        $db->INDEX['BATTLES_RAW'] = $db->INDEX['BATTLES'];

        // Additionally, include any dynamic session-based battles
        if (!empty($_SESSION['GAME']['values']['battle_index'])){
            // The session-based battles exist, so merge them with the index
            $db->INDEX['BATTLES'] = array_merge($db->INDEX['BATTLES'], $_SESSION['GAME']['values']['battle_index']);
        }

        // Return true on success
        return true;

    }

    // Define the function used for scanning the battle directory
    public static function index_battle_data($return_json = true){

        // Default the battles markup index to an empty array
        $battles_cache_markup = array();

        // Get a list of directories in the battle content path so we can loop
        $battles_base_path = MMRPG_CONFIG_BATTLES_CONTENT_PATH;
        $battle_paths_list = scandir($battles_base_path);
        $battle_paths_list = array_filter($battle_paths_list, function($s){ if ($s !== '.' && $s !== '..' && substr($s, 0, 1) !== '.'){ return true; } else { return false; } });

        // Remove any paths that aren't directories or don't have JSON files in them
        foreach ($battle_paths_list AS $key => $path){
            if (!is_dir($battles_base_path.$path)){ unset($battle_paths_list[$key]); continue; }
            if (!file_exists($battles_base_path.$path.'/data.json')){ unset($battle_paths_list[$key]); continue; }
        }

        // Now loop through and collect data for all battles in the index
        $this_battle_index = array();
        foreach ($battle_paths_list AS $battle_token){
            $battle_json = file_get_contents($battles_base_path.$battle_token.'/data.json');
            $battle_data = json_decode($battle_json, true);
            $this_battle_index[$battle_token] = $battle_data;
        }

        //echo 'Scanning '.$battles_base_path.' for directories...'.PHP_EOL;
        //echo('$battle_paths_list = '.print_r($battle_paths_list, true).PHP_EOL);
        //echo('$this_battle_index = '.print_r($this_battle_index, true).PHP_EOL);
        //exit();

        // Now return the index, either compressed on not given function args
        if ($return_json){
            $battles_cache_markup = json_encode($this_battle_index);
            return $battles_cache_markup;
        } else {
            return $this_battle_index;
        }

    }

    // Define a public function for manually loading data
    public function battle_load($this_battleinfo){

        // Collect current battle data from the session if available
        $this_battleinfo_backup = $this_battleinfo;
        if (isset($_SESSION['BATTLES'][$this_battleinfo['battle_id']])){
            $this_battleinfo = $_SESSION['BATTLES'][$this_battleinfo['battle_id']];
        }
        // Otherwise, collect battle data from the index
        else {
            //die(print_r($this_battleinfo, true));
            $this_battleinfo = rpg_battle::get_index_info($this_battleinfo['battle_token']);
        }
        $this_battleinfo = array_replace($this_battleinfo, $this_battleinfo_backup);

        // Define the internal battle values using the provided array
        $this->flags = isset($this_battleinfo['flags']) ? $this_battleinfo['flags'] : array();
        $this->counters = isset($this_battleinfo['counters']) ? $this_battleinfo['counters'] : array();
        $this->values = isset($this_battleinfo['values']) ? $this_battleinfo['values'] : array();
        $this->history = isset($this_battleinfo['history']) ? $this_battleinfo['history'] : array();
        $this->events = isset($this_battleinfo['events']) ? $this_battleinfo['events'] : array();
        $this->battle_id = isset($this_battleinfo['battle_id']) ? $this_battleinfo['battle_id'] : 0;
        $this->battle_name = isset($this_battleinfo['battle_name']) ? $this_battleinfo['battle_name'] : 'Default';
        $this->battle_token = isset($this_battleinfo['battle_token']) ? $this_battleinfo['battle_token'] : 'default';
        $this->battle_description = isset($this_battleinfo['battle_description']) ? $this_battleinfo['battle_description'] : '';
        $this->battle_turns = isset($this_battleinfo['battle_turns']) ? $this_battleinfo['battle_turns'] : 1;
        $this->battle_counts = isset($this_battleinfo['battle_counts']) ? $this_battleinfo['battle_counts'] : true;
        $this->battle_status = isset($this_battleinfo['battle_status']) ? $this_battleinfo['battle_status'] : 'active';
        $this->battle_result = isset($this_battleinfo['battle_result']) ? $this_battleinfo['battle_result'] : 'pending';
        $this->battle_robot_limit = isset($this_battleinfo['battle_robot_limit']) ? $this_battleinfo['battle_robot_limit'] : false;
        $this->battle_field_base = isset($this_battleinfo['battle_field_base']) ? $this_battleinfo['battle_field_base'] : array();
        $this->battle_target_player = isset($this_battleinfo['battle_target_player']) ? $this_battleinfo['battle_target_player'] : array();
        $this->battle_rewards = isset($this_battleinfo['battle_rewards']) ? $this_battleinfo['battle_rewards'] : array();
        $this->battle_zenny = isset($this_battleinfo['battle_zenny']) ? $this_battleinfo['battle_zenny'] : 0;
        $this->battle_level = isset($this_battleinfo['battle_level']) ? $this_battleinfo['battle_level'] : 0;
        $this->battle_attachments = isset($this_battleinfo['battle_attachments']) ? $this_battleinfo['battle_attachments'] : array();

        // Define the internal robot base values using the robots index array
        $this->battle_base_name = isset($this_battleinfo['battle_base_name']) ? $this_battleinfo['battle_base_name'] : $this->battle_name;
        $this->battle_base_token = isset($this_battleinfo['battle_base_token']) ? $this_battleinfo['battle_base_token'] : $this->battle_token;
        $this->battle_base_description = isset($this_battleinfo['battle_base_description']) ? $this_battleinfo['battle_base_description'] : $this->battle_description;
        $this->battle_base_turns = isset($this_battleinfo['battle_base_turns']) ? $this_battleinfo['battle_base_turns'] : $this->battle_turns;
        $this->battle_base_rewards = isset($this_battleinfo['battle_base_rewards']) ? $this_battleinfo['battle_base_rewards'] : $this->battle_rewards;
        $this->battle_base_zenny = isset($this_battleinfo['battle_base_zenny']) ? $this_battleinfo['battle_base_zenny'] : $this->battle_zenny;
        $this->battle_base_level = isset($this_battleinfo['battle_base_level']) ? $this_battleinfo['battle_base_level'] : $this->battle_level;
        $this->battle_base_attachments = isset($this_battleinfo['battle_base_attachments']) ? $this_battleinfo['battle_base_attachments'] : $this->battle_attachments;

        // Collect any battle-complete tokens or seeds to generate with
        $this->battle_complete_redirect_token = !empty($this_battleinfo['battle_complete_redirect_token']) ? $this_battleinfo['battle_complete_redirect_token'] : '';
        $this->battle_complete_redirect_seed = !empty($this_battleinfo['battle_complete_redirect_seed']) ? $this_battleinfo['battle_complete_redirect_seed'] : array();

        // Collect any functions associated with this battle
        $temp_functions_path = MMRPG_CONFIG_BATTLES_CONTENT_PATH.$this->battle_token.'/functions.php';
        if (file_exists($temp_functions_path)){ require($temp_functions_path); }
        else { $functions = array(); }
        $this->battle_function = isset($functions['battle_function']) ? $functions['battle_function'] : function(){};
        $this->battle_function_onload = isset($functions['battle_function_onload']) ? $functions['battle_function_onload'] : function(){};
        unset($functions);

        // Ensure values exist for necessary battle properties
        if (!isset($this->values['context'])){
            $this->values['context'] = array(
                'player' => 'player',
                'chapter' => 1,
                'phase' => 1,
                'round' => 1
                );
        }

        // Trigger the onload function if it exists
        $this->trigger_onload();

        // Update the session variable
        $this->update_session();

        // Return true on success
        return true;

    }

    // Define a function for refreshing this battle and running onload actions
    public function trigger_onload(){

        // Trigger the onload function if it exists
        $temp_function = $this->battle_function_onload;
        $temp_result = $temp_function(array(
            'this_battle' => $this
            ));

    }

    public function get_attachment($key, $token){ return $this->get_info('battle_attachments', $key, $token); }
    public function has_attachment($key, $token){ return $this->get_info('battle_attachments', $key, $token) ? true : false; }
    public function set_attachment($key, $token, $value){ $this->set_info('battle_attachments', $key, $token, $value); }
    public function unset_attachment($key, $token){ return $this->unset_info('battle_attachments', $key, $token); }

    public function get_attachments(){ return $this->get_info('battle_attachments'); }
    public function set_attachments($value){ $this->set_info('battle_attachments', $value); }
    public function has_attachments(){ return $this->get_info('battle_attachments') ? true : false; }

    // Define public print functions for markup generation
    //public function print_name(){ return '<span class="battle_name battle_type battle_type_none">'.$this->battle_name.'</span>'; }
    public function print_name(){ return '<span class="battle_name battle_type">'.$this->battle_name.'</span>'; }
    public function print_token(){ return '<span class="battle_token">'.$this->battle_token.'</span>'; }
    public function print_description(){ return '<span class="battle_description">'.$this->battle_description.'</span>'; }
    public function print_zenny(){ return '<span class="battle_zenny">'.$this->battle_zenny.'</span>'; }

    // Define a function for checking if that battle in particular is endgame content
    public function has_endgame_context(){
        $is_endgame = false;
        $context = $this->values['context'];
        if (rpg_mission::is_endgame($context)){ $is_endgame = true; }
        return $is_endgame;
    }

    // Define a static public function for encouraging battle words
    public static function random_positive_word(){
        $temp_text_options = array('Awesome!', 'Nice!', 'Fantastic!', 'Yeah!', 'Yay!', 'Yes!', 'Great!', 'Super!', 'Rock on!', 'Amazing!', 'Fabulous!', 'Wild!', 'Sweet!', 'Wow!', 'Oh my!', 'Excellent!', 'Wonderful!');
        $temp_text = $temp_text_options[array_rand($temp_text_options)];
        return $temp_text;
    }

// Define a static public function for encouraging battle victory quotes
    public static function random_victory_quote(){
        $temp_text_options = array('Awesome work!', 'Nice work!', 'Fantastic work!', 'Great work!', 'Super work!', 'Amazing work!', 'Fabulous work!');
        $temp_text = $temp_text_options[array_rand($temp_text_options)];
        return $temp_text;
    }

    // Define a static public function for discouraging battle words
    public static function random_negative_word(){
        $temp_text_options = array('Yikes!', 'Oh no!', 'Ouch...', 'Awwwww...', 'Bummer...', 'Boooo...', 'Harsh!', 'Sorry...');
        $temp_text = $temp_text_options[array_rand($temp_text_options)];
        return $temp_text;
    }

    // Define a static public function for discouraging battle defeat quotes
    public static function random_defeat_quote(){
        $temp_text_options = array('Maybe try again?', 'Bad luck maybe?', 'Maybe try another stage?', 'Better luck next time?', 'At least you tried... right?');
        $temp_text = $temp_text_options[array_rand($temp_text_options)];
        return $temp_text;
    }

    // Define a public function for extracting actions from the queue
    public function actions_extract($filters){

        $extracted_actions = array();
        foreach($this->actions AS $action_key => $action_array){
            $is_match = true;
            if (!empty($filters['this_player_id']) && $action_array['this_player']->player_id != $filters['this_player_id']){ $is_match = false; }
            if (!empty($filters['this_robot_id']) && $action_array['this_robot']->robot_id != $filters['this_robot_id']){ $is_match = false; }
            if (!empty($filters['target_player_id']) && $action_array['target_player']->player_id != $filters['target_player_id']){ $is_match = false; }
            if (!empty($filters['target_robot_id']) && $action_array['target_robot']->robot_id != $filters['target_robot_id']){ $is_match = false; }
            if (!empty($filters['this_action']) && $action_array['this_action'] != $filters['this_action']){ $is_match = false; }
            if (!empty($filters['this_action_token']) && $action_array['this_action_token'] != $filters['this_action_token']){ $is_match = false; }
            if ($is_match){ $extracted_actions = array_slice($this->actions, $action_key, 1, false); }
        }
        return $extracted_actions;

    }

    // Define a public function for inserting actions into the queue
    public function actions_insert($inserted_actions){

        if (!empty($inserted_actions)){
            $this->actions = array_merge($this->actions, $inserted_actions);
        }

    }

    // Define a public function for prepending to the action array
    public function actions_prepend($this_player, $this_robot, $target_player, $target_robot, $this_action, $this_action_token){

        // Prepend the new action to the array
        array_unshift($this->actions, array(
            'this_field' => $this->battle_field,
            'this_player' => $this_player,
            'this_robot' => $this_robot,
            'target_player' => $target_player,
            'target_robot' => $target_robot,
            'this_action' => $this_action,
            'this_action_token' => $this_action_token
            ));

        // Return the resulting array
        return $this->actions;

    }

    // Define a public function for appending to the action array
    public function actions_append($this_player, $this_robot, $target_player, $target_robot, $this_action, $this_action_token, $end_of_turn = false){

        // Append the new action to the array
        if ($end_of_turn === true){
            $this->endofturn_actions[] = array(
                'this_field' => $this->battle_field,
                'this_player' => $this_player,
                'this_robot' => $this_robot,
                'target_player' => $target_player,
                'target_robot' => $target_robot,
                'this_action' => $this_action,
                'this_action_token' => $this_action_token
                );
        } else {
            $this->actions[] = array(
                'this_field' => $this->battle_field,
                'this_player' => $this_player,
                'this_robot' => $this_robot,
                'target_player' => $target_player,
                'target_robot' => $target_robot,
                'this_action' => $this_action,
                'this_action_token' => $this_action_token
                );
        }

        // Return the resulting array
        return $this->actions;

    }

    // Define a public function for emptying the actions array
    public function actions_empty(){

        // Empty the internal actions array
        $this->actions = array();

        // Return the resulting array
        return $this->actions;

    }

    // Define a public function for execution queued items in the actions array
    public function actions_execute($endofturn_actions = false){

        // Back up the IDs of this and the target robot in the global space
        $temp_this_robot_backup = array('robot_id' => $GLOBALS['this_robot']->robot_id, 'robot_token' => $GLOBALS['this_robot']->robot_token);
        $temp_target_robot_backup = array('robot_id' => $GLOBALS['target_robot']->robot_id, 'robot_token' => $GLOBALS['target_robot']->robot_token);

        // Prevent duplicate switch actions from the same side
        if (!empty($this->actions)){
            $temp_switch_strings = array();
            foreach($this->actions AS $key => $action){
                if ($action['this_action'] == 'switch'){
                    $switch_string = $action['this_action'].'-'.$action['this_player']->player_id.'-w-'.$action['this_robot']->robot_id;
                    if (!in_array($switch_string, $temp_switch_strings)){ $temp_switch_strings[] = $switch_string; continue; }
                    unset($this->actions[$key]);
                }
            }
        }

        // Loop through the non-empty action queue and trigger actions
        $this_actions_array = $endofturn_actions === true ? $this->endofturn_actions : $this->actions;
        while (!empty($this_actions_array) && $this->battle_status != 'complete'){

            // Shift and collect the oldest action from the queue
            $current_action = array_shift($this_actions_array);
            if ($endofturn_actions === true){ array_shift($this->endofturn_actions); }
            else { array_shift($this->actions); }

            // If the robot's player is on autopilot and the action is empty, automate input
            if (empty($current_action['this_action']) && $current_action['this_player']->player_autopilot == true){
                $current_action['this_action'] = 'ability';
            }

            // Based on the action type, trigger the appropriate battle function
            switch ($current_action['this_action']){
                // If the battle start action was called
                case 'start': {
                    // Initiate the battle start event for this robot
                    $battle_action = $this->actions_trigger(
                        $current_action['this_player'],
                        $current_action['this_robot'],
                        $current_action['target_player'],
                        $current_action['target_robot'],
                        'start',
                        ''
                        );
                    break;
                }
                // If the robot ability action was called
                case 'ability': {
                    // Initiate the ability event for this player's robot
                    $battle_action = $this->actions_trigger(
                        $current_action['this_player'],
                        $current_action['this_robot'],
                        $current_action['target_player'],
                        $current_action['target_robot'],
                        'ability',
                        $current_action['this_action_token']
                        );
                    break;
                }
                // If the robot item action was called
                case 'item': {
                    // Initiate the item event for this player's robot
                    $battle_action = $this->actions_trigger(
                        $current_action['this_player'],
                        $current_action['this_robot'],
                        $current_action['target_player'],
                        $current_action['target_robot'],
                        'item',
                        $current_action['this_action_token']
                        );
                    break;
                }
                // If the robot switch action was called
                case 'switch': {
                    // Initiate the switch event for this player's robot
                    $battle_action = $this->actions_trigger(
                        $current_action['this_player'],
                        $current_action['this_robot'],
                        $current_action['target_player'],
                        $current_action['target_robot'],
                        'switch',
                        $current_action['this_action_token']
                        );
                    break;
                }
                // If the robot scan action was called
                case 'scan': {
                    // Initiate the scan event for this player's robot
                    $battle_action = $this->actions_trigger(
                        $current_action['this_player'],
                        $current_action['this_robot'],
                        $current_action['target_player'],
                        $current_action['target_robot'],
                        'scan',
                        $current_action['this_action_token']
                        );
                    break;
                }
            }

            // Create a closing event with robots in base frames, if the battle is not over
            if ($this->battle_status != 'complete'){
                $temp_this_robot = false;
                $temp_target_robot = false;
                if (!empty($current_action['this_robot'])){
                    $current_action['this_robot']->robot_frame = 'base';
                    $current_action['this_player']->player_frame = 'base';
                    if ($current_action['this_robot']->robot_status === 'disabled'
                        && empty($current_action['this_robot']->flags['is_recruited'])){
                        $current_action['this_robot']->robot_frame = 'defeat';
                        $current_action['this_player']->player_frame = 'defeat';
                    }
                    $current_action['this_robot']->update_session();
                    $current_action['this_player']->update_session();
                    $temp_this_robot = $current_action['this_robot'];
                }
                if (!empty($current_action['target_robot'])){
                    $current_action['target_robot']->robot_frame = 'base';
                    $current_action['target_player']->player_frame = 'base';
                    if ($current_action['target_robot']->robot_status === 'disabled'
                        && empty($current_action['target_robot']->flags['is_recruited'])){
                        $current_action['target_robot']->robot_frame = 'defeat';
                        $current_action['target_player']->player_frame = 'defeat';
                    }
                    $current_action['target_robot']->update_session();
                    $current_action['target_player']->update_session();
                    $temp_target_robot = $current_action['target_robot'];
                }
                if (!empty($battle_action) && $battle_action != 'start'){
                    $this->events_create(false, false, '', '');
                }
                if (!empty($current_action['this_player'])){
                    $current_action['this_player']->reset_frame();
                }
                if (!empty($current_action['target_player'])){
                    $current_action['target_player']->reset_frame();
                }
            }

        }

        // Recreate this and the target robot in the global space with backed up info
        if (empty($GLOBALS['this_robot'])){ $GLOBALS['this_robot'] = rpg_game::get_robot($this, $GLOBALS['this_player'], $temp_this_robot_backup); }
        if (empty($GLOBALS['target_robot'])){ $GLOBALS['target_robot'] = rpg_game::get_robot($this, $GLOBALS['target_player'], $temp_target_robot_backup); }

        // Return true on loop completion
        return true;
    }

    // Define a public function for triggering battle actions
    public function battle_complete_trigger($this_player, $this_robot, $target_player, $target_robot, $this_action = '', $this_token = ''){
        //error_log('rpg_battle::battle_complete_trigger()');

        global $db;
        // DEBUG
        //$this->events_create(false, false, 'DEBUG', 'Battle complete trigger triggered!');

        // Define a variable for forcing zenny rewards if required
        $force_zenny_rewards = false;

        // Return false if anything is missing
        if (empty($this_player) || empty($this_robot)){ return false; }
        if (empty($target_player) || empty($target_robot)){ return false; }

        // Return true if the battle status is already complete
        if ($this->battle_status == 'complete'){ return true; }

        // Update the battle status to complete
        $this->battle_status = 'complete';
        if ($this->battle_result == 'pending'){
            $this->battle_result = $target_player->player_side == 'right' ? 'victory' : 'defeat';
            $this->update_session();
        }
        $event_options = array();
        $this->events_create(false, false, '', '', $event_options);

        // Define variables for the human's rewards in this scenario
        $temp_human_token = $target_player->player_side == 'left' ? $target_player->player_token : $this_player->player_token;
        $temp_human_rewards = array();
        $temp_human_rewards['battle_zenny'] = 0;
        $temp_human_rewards['battle_complete'] = mmrpg_prototype_battle_complete($temp_human_token, $this->battle_token);
        $temp_human_rewards['battle_failure'] = mmrpg_prototype_battle_failure($temp_human_token, $this->battle_token);
        $temp_human_rewards['checkpoint'] = 'start: ';

        // Check to see if this is a player battle
        $this_is_player_battle = false;
        $this_user_id = $this_player->player_id;
        $target_user_id = $target_player->player_id;
        if (strstr($this_user_id, 'x')){ list($this_user_id) = explode('x', $this_user_id); }
        if (strstr($target_user_id, 'x')){ list($target_user_id) = explode('x', $target_user_id); }
        if ($this_player->player_side == 'right' && $this_user_id != MMRPG_SETTINGS_TARGET_PLAYERID){
            $this_is_player_battle = true;
        } elseif ($target_player->player_side == 'right' && $target_user_id != MMRPG_SETTINGS_TARGET_PLAYERID){
            $this_is_player_battle = true;
        }

        // Check if this battle's records count
        $this_mission_counts = $this->battle_counts ? true : false;

        // (HUMAN) TARGET DEFEATED
        // Check if the target was the human character
        if ($target_player->player_side == 'left'){

            // DEBUG
            //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;

            // Calculate the number of battle zenny for the target player
            $this_base_zenny = 0; //$this->battle_zenny;
            $this_turn_zenny = 100 * $this->counters['battle_turn'];
            $this_stat_zenny = 0;
            $target_battle_zenny = $this_base_zenny + $this_turn_zenny + $this_stat_zenny;
            // Prevent players from loosing zenny
            if ($target_battle_zenny == 0){ $target_battle_zenny = 1; }
            elseif ($target_battle_zenny < 0){ $target_battle_zenny = -1 * $target_battle_zenny; }

            // Update the global variable with the zenny reward
            $temp_human_rewards['battle_zenny'] = $target_battle_zenny;

            // Update the GAME session variable with the failed battle token
            if ($this->battle_counts){

                // Create the new session array from scratch to ensure all values exist
                $new_failure_count = 0;

                // Back up the current session array for this battle failure counter
                $old_failure_count = isset($_SESSION['GAME']['values']['battle_failure'][$target_player->player_token][$this->battle_token]) ? $_SESSION['GAME']['values']['battle_failure'][$target_player->player_token][$this->battle_token] : 0;
                if (is_array($old_failure_count) && isset($old_failure_count['battle_count'])){ $old_failure_count = $old_failure_count['battle_count']; }
                elseif (!is_numeric($old_failure_count)){ $old_failure_count = 0; }
                if (!empty($old_failure_count)){ $new_failure_count = $old_failure_count; }

                // Update and/or increment the appropriate battle variables in the new array
                $new_failure_count++;

                // Update the session variable for this player with the updated battle values
                $_SESSION['GAME']['values']['battle_failure'][$target_player->player_token][$this->battle_token] = $new_failure_count;
                $temp_human_rewards['battle_failure'] = $_SESSION['GAME']['values']['battle_failure'][$target_player->player_token][$this->battle_token];
            }

            // Recalculate the overall battle points total with new values
            mmrpg_prototype_calculate_battle_points(true);

        }
        // (GHOST/COMPUTER) TARGET DEFEATED
        // Otherwise if the target was a computer-controlled human character
        elseif ($target_user_id != MMRPG_SETTINGS_TARGET_PLAYERID){

            // Calculate the battle zenny based on how many turns they lasted
            $target_battle_zenny = ceil($this->counters['battle_turn'] * 100 * MMRPG_SETTINGS_BATTLEPOINTS_PERZENNY_MULTIPLIER);

        }
        // (COMPUTER) TARGET DEFEATED
        // Otherwise, zero target battle zenny
        else {
            // DEBUG
            //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;
            // Target is computer, no battle zenny for them
            $target_battle_zenny = 0;
        }


        // NON-INVISIBLE PLAYER DEFEATED
        // Display the defeat message for the target character if not default/hidden
        if ($target_player->player_visible){

            // DEBUG
            //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;

            // (HUMAN) TARGET DEFEATED BY (INVISIBLE/COMPUTER)
            // If this was a player battle and the human user lost against the ghost target (this/computer/victory | target/human/defeat)
            if ($this_user_id == MMRPG_SETTINGS_TARGET_PLAYERID && $target_player->player_side == 'left' && $this_robot->robot_class != 'mecha'){

                // Calculate how many zenny the other player is rewarded for winning
                $target_player_robots = $target_player->values['robots_disabled'];
                $target_player_robots_count = count($target_player_robots);
                $other_player_zenny = 0;
                $other_player_turns = $target_player_robots_count * MMRPG_SETTINGS_BATTLETURNS_PERROBOT;
                foreach ($target_player_robots AS $disabled_robotinfo){
                    $other_player_zenny += $disabled_robotinfo['robot_level'] * MMRPG_SETTINGS_BATTLEPOINTS_PERLEVEL * MMRPG_SETTINGS_BATTLEPOINTS_PLAYERBATTLE_MULTIPLIER;
                }

                // Collect the battle zenny from the function
                $other_battle_zenny_modded = $this->calculate_battle_zenny($target_player, $other_player_zenny, $other_player_turns);

                // Create the victory event for the target player
                $this_robot->robot_frame = 'victory';
                $this_robot->update_session();
                $event_header = $this_robot->robot_name.' Undefeated';
                $event_body = '';
                $event_body .= $this_robot->print_name().' could not be defeated! ';
                $event_body .= '<br />';
                $event_options = array();
                $event_options['console_show_this_robot'] = true;
                $event_options['console_show_target'] = false;
                $event_options['event_flag_defeat'] = true;
                $event_options['this_header_float'] = $event_options['this_body_float'] = $this_robot->player->player_side;
                if ($this_robot->robot_token != 'robot'
                    && isset($this_robot->robot_quotes['battle_victory'])){
                    $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                    $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                    $event_body .= $this_robot->print_quote('battle_victory', $this_find, $this_replace);
                }
                $this->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);

            }

            $target_player->player_frame = 'defeat';
            $target_robot->update_session();
            $target_player->update_session();
            $event_header = $target_player->player_name.' Defeated';
            $event_body = $target_player->print_name().' was defeated'.($target_player->player_side == 'left' ? '&hellip;' : '!').' ';
            $event_body .= '<br />';
            $event_options = array();
            $event_options['console_show_this_player'] = true;
            $event_options['console_show_target'] = false;
            $event_options['event_flag_defeat'] = true;
            $event_options['this_header_float'] = $event_options['this_body_float'] = $target_player->player_side;
            if ($target_player->player_visible
                && isset($target_player->player_quotes['battle_defeat'])){
                $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                $this_replace = array($this_player->player_name, $this_robot->robot_name, $target_player->player_name, $target_robot->robot_name);
                $this_quote_text = str_replace($this_find, $this_replace, $target_player->player_quotes['battle_defeat']);
                $event_body .= $target_player->print_quote('battle_defeat', $this_find, $this_replace);
            }
            $this->events_create($target_robot, $this_robot, $event_header, $event_body, $event_options);

            // (HUMAN) TARGET DEFEATED BY (GHOST/COMPUTER)
            // If this was a player battle and the human user lost against the ghost target (this/computer/victory | target/human/defeat)
            if ($this_user_id != MMRPG_SETTINGS_TARGET_PLAYERID && $target_player->player_side == 'left'){

                // Calculate how many zenny the other player is rewarded for winning
                $target_player_robots = $target_player->values['robots_disabled'];
                $target_player_robots_count = count($target_player_robots);
                $other_player_zenny = 0;
                $other_player_turns = $target_player_robots_count * MMRPG_SETTINGS_BATTLETURNS_PERROBOT;
                foreach ($target_player_robots AS $disabled_robotinfo){
                    $other_player_zenny += $disabled_robotinfo['robot_level'] * MMRPG_SETTINGS_BATTLEPOINTS_PERLEVEL * MMRPG_SETTINGS_BATTLEPOINTS_PLAYERBATTLE_MULTIPLIER;
                }

                // Collect the battle zenny from the function
                $other_battle_zenny_modded = $this->calculate_battle_zenny($target_player, $other_player_zenny, $other_player_turns);
                $this->counters['final_zenny_reward'] = $other_battle_zenny_modded;

                // Create the victory event for the target player
                $this_player->player_frame = 'victory';
                $target_robot->update_session();
                $this_player->update_session();
                $event_header = $this_player->player_name.' Victorious';
                $event_body = $this_player->print_name().' was victorious! ';
                $event_body .= $this_player->print_name().' collects <span class="recovery_amount">'.number_format($other_battle_zenny_modded, 0, '.', ',').'</span> zenny!';
                $event_body .= '<br />';
                $event_options = array();
                $event_options['console_show_this_player'] = true;
                $event_options['console_show_target'] = false;
                $event_options['event_flag_defeat'] = true;
                $event_options['this_header_float'] = $event_options['this_body_float'] = $this_player->player_side;
                $event_options['event_flag_sound_effects'] = array(
                    // maybe nothing?
                    );
                if ($this_player->player_visible
                    && isset($this_player->player_quotes['battle_victory'])){
                    $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                    $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                    $event_body .= $this_player->print_quote('battle_victory', $this_find, $this_replace);
                }
                $this->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);

                // Create the temp robot sprites for the database
                $temp_this_player_robots = array();
                $temp_target_player_robots = array();
                foreach ($target_player->player_robots AS $key => $info){ $temp_this_player_robots[] = '['.$info['robot_token'].':'.$info['robot_level'].']'; }
                foreach ($this_player->player_robots AS $key => $info){ $temp_target_player_robots[] = '['.$info['robot_token'].':'.$info['robot_level'].']'; }
                $temp_this_player_robots = !empty($temp_this_player_robots) ? implode(',', $temp_this_player_robots) : '';
                $temp_target_player_robots = !empty($temp_target_player_robots) ? implode(',', $temp_target_player_robots) : '';
                // Collect the userinfo for the target player
                //$target_player_userinfo = $db->get_array("SELECT user_name, user_name_clean, user_name_public FROM mmrpg_users WHERE user_id = {$target_player->player_id};");
                //if (!isset($_SESSION['PROTOTYPE_TEMP']['player_targets_defeated'])){ $_SESSION['PROTOTYPE_TEMP']['player_targets_defeated'] = array(); }
                //$_SESSION['PROTOTYPE_TEMP']['player_targets_defeated'][] = $target_player_userinfo['user_name_clean'];

                // 2023/11/14: There is no reason why we should still be saving defeats to the database in 2023
                // It's not used for anything and it's just wasting space in the database.  Removed going forward.

            }

        }


        // (HUMAN) TARGET DEFEATED BY (COMPUTER)
        // Check if the target was the human character (and they LOST)
        if ($target_player->player_side == 'left'){

            // Nothing specific happened...

        }

        // (COMPUTER) TARGET DEFEATED BY (HUMAN)
        // Check if this player was the human player (and they WON)
        if ($this_player->player_side == 'left'){

            // DEBUG
            //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;

            // Collect the battle zenny from the function
            $this_battle_zenny = $this->calculate_battle_zenny($this_player, $this->battle_zenny, $this->battle_turns);

            // Recalculate the overall battle points total with new values
            mmrpg_prototype_calculate_battle_points(true);

            // Reference the number of zenny this player gets
            $this_player_zenny = $this_battle_zenny;

            // Collect the player token and export array as reference
            $player_token = $this_player->player_token;
            $player_info = $this_player->export_array();

            // Update the global variable with the zenny reward
            $temp_human_rewards['battle_zenny'] = $this_player_zenny;

            // If the player was visible, we can display the victory as their own
            if ($this_player->player_visible){

                // Display the win message for this player with battle zenny
                $this_robot->robot_frame = 'victory';
                $this_player->player_frame = 'victory';
                $this_robot->update_session();
                $this_player->update_session();
                $event_header = $this_player->player_name.' Victorious';
                $event_body = $this_player->print_name().' was victorious! ';
                $event_body .= 'The '.($target_player->counters['robots_disabled'] > 1 ? 'targets were' : 'target was').' defeated!';
                $event_body .= '<br />';
                $event_options = array();
                $event_options['console_show_this_player'] = true;
                $event_options['console_show_target'] = false;
                $event_options['this_header_float'] = $event_options['this_body_float'] = $this_player->player_side;
                if ($this_player->player_visible
                    && isset($this_player->player_quotes['battle_victory'])){
                    $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                    $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                    $event_body .= $this_player->print_quote('battle_victory', $this_find, $this_replace);
                    //$this_quote_text = str_replace($this_find, $this_replace, $this_player->player_quotes['battle_victory']);
                    //$event_body .= '&quot;<em>'.$this_quote_text.'</em>&quot;';
                }
                $this->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);

            }
            // Otherwise, we need to attribute the victory to the lead robot instead
            else {

                // DEBUG
                //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;

                // Display the win message for this player with battle zenny
                $this_robot->robot_frame = 'victory';
                $this_robot->update_session();
                $event_header = $this_robot->robot_name.' Victorious';
                $event_body = $this_robot->print_name().' was victorious! ';
                $event_body .= 'The '.($target_player->counters['robots_disabled'] > 1 ? 'targets were' : 'target was').' defeated!';
                $event_body .= '<br />';
                $event_options = array();
                $event_options['console_show_this_robot'] = true;
                $event_options['console_show_target'] = false;
                $event_options['this_header_float'] = $event_options['this_body_float'] = $this_robot->player->player_side;
                if ($this_robot->robot_token != 'robot'
                    && isset($this_robot->robot_quotes['battle_victory'])){
                    $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                    $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                    $event_body .= $this_robot->print_quote('battle_victory', $this_find, $this_replace);
                }
                $this->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);

            }

            // If this was a PLAYER BATTLE and the human user won against them (this/human/victory | target/computer/defeat)
            $target_user_id = $target_player->player_id;
            if (strstr($target_user_id, 'x')){ list($target_user_id) = explode('x', $target_user_id); }
            if ($target_user_id != MMRPG_SETTINGS_TARGET_PLAYERID && $this_player->player_side == 'left'){

                // DEBUG
                //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;

                // Ensure the system knows to reward zenny instead of zenny
                $force_zenny_rewards = true;

                // Create the temp robot sprites for the database
                $temp_this_player_robots = array();
                $temp_target_player_robots = array();
                foreach ($this_player->player_robots AS $key => $info){ $temp_this_player_robots[] = '['.$info['robot_token'].':'.$info['robot_level'].']'; }
                foreach ($target_player->player_robots AS $key => $info){ $temp_target_player_robots[] = '['.$info['robot_token'].':'.$info['robot_level'].']'; }
                $temp_this_player_robots = !empty($temp_this_player_robots) ? implode(',', $temp_this_player_robots) : '';
                $temp_target_player_robots = !empty($temp_target_player_robots) ? implode(',', $temp_target_player_robots) : '';
                // Collect the userinfo for the target player
                $target_player_userinfo = $db->get_array("SELECT user_name, user_name_clean, user_name_public FROM mmrpg_users WHERE user_id = {$target_user_id};");
                if (!isset($_SESSION['LEADERBOARD']['player_targets_defeated'])){ $_SESSION['LEADERBOARD']['player_targets_defeated'] = array(); }
                $_SESSION['LEADERBOARD']['player_targets_defeated'][] = $target_player_userinfo['user_name_clean'];

                // Pre-collect this and the target player's battle points beforehand for comparrison
                $matchup_array_query = "SELECT
                    `board`.`user_id` AS `user_id`,
                    `board`.`board_points` AS `battle_points`,
                    `victories`.`max_battle_id` AS `battle_victory_id`,
                    `defeats`.`max_battle_id` AS `battle_defeat_id`
                    FROM `mmrpg_leaderboard` AS `board`
                    LEFT JOIN (SELECT
                        MAX(`battles`.`battle_id`) AS `max_battle_id`,
                        `battles`.`this_user_id`,
                        `battles`.`target_user_id`,
                        `battles`.`this_player_result`,
                        `battles`.`target_player_result`
                        FROM `mmrpg_battles` AS `battles`
                        WHERE
                        `battles`.`this_user_id` = {$this_user_id}
                        AND `battles`.`target_user_id` = {$target_user_id}
                        AND `battles`.`this_player_result` = 'victory'
                        AND `battles`.`battle_flag_legacy` = 0
                        ) AS `victories` ON `victories`.`this_user_id` = `board`.`user_id`
                    LEFT JOIN (SELECT
                        MAX(`battles`.`battle_id`) AS `max_battle_id`,
                        `battles`.`this_user_id`,
                        `battles`.`target_user_id`,
                        `battles`.`this_player_result`,
                        `battles`.`target_player_result`
                        FROM `mmrpg_battles` AS `battles`
                        WHERE
                        `battles`.`this_user_id` = {$target_user_id}
                        AND `battles`.`target_user_id` = {$this_user_id}
                        AND `battles`.`this_player_result` = 'victory'
                        AND `battles`.`battle_flag_legacy` = 0
                        ) AS `defeats` ON `defeats`.`this_user_id` = `board`.`user_id`
                    WHERE
                    `board`.`user_id` IN ({$this_user_id}, {$target_user_id})
                    ;";
                //error_log('$matchup_array_query = '.print_r($matchup_array_query, true));
                $matchup_array_index = $db->get_array_list($matchup_array_query, 'user_id');
                //error_log('$matchup_array_index = '.print_r($matchup_array_index, true));
                $this_user_matchup = !empty($matchup_array_index[$this_user_id]) ? $matchup_array_index[$this_user_id] : array();
                $target_user_matchup = !empty($matchup_array_index[$target_user_id]) ? $matchup_array_index[$target_user_id] : array();
                //error_log('$this_user_matchup = '.print_r($this_user_matchup, true));
                //error_log('$target_user_matchup = '.print_r($target_user_matchup, true));

                // Check to see if this battle is going to increase our score at all, given context
                $this_user_has_victory_already = !empty($this_user_matchup['battle_victory_id']) ? true : false;
                $target_user_has_victory_already = !empty($target_user_matchup['battle_victory_id']) ? true : false;
                $update_this_user_score = !$this_user_has_victory_already ? true : false;
                $update_target_user_score = $target_user_has_victory_already ? true : false;
                //error_log('$this_user_has_victory_already = '.print_r($this_user_has_victory_already, true));
                //error_log('$target_user_has_victory_already = '.print_r($target_user_has_victory_already, true));
                //error_log('$update_this_user_score = '.print_r($update_this_user_score, true));
                //error_log('$update_target_user_score = '.print_r($update_target_user_score, true));

                // Make sure we set any previous battles against this user as legacy so they don't count anymore
                $db->update('mmrpg_battles', array('battle_flag_legacy' => 1),
                    "battle_flag_legacy = 0 AND (
                        (this_user_id = {$this_user_id} AND target_user_id = {$target_user_id})
                        OR (this_user_id = {$target_user_id} AND target_user_id = {$this_user_id})
                        )"
                    );

                // Update the database with these pending rewards for each player
                global $db;
                $player_battle_victory_record = array(
                    'battle_field_name' => $this->battle_field->field_name,
                    'battle_field_background' => $this->battle_field->field_background,
                    'battle_field_foreground' => $this->battle_field->field_foreground,
                    'battle_turns' => $this->counters['battle_turn'],
                    'this_user_id' => $this_user_id,
                    'this_player_token' => $this_player->player_token,
                    'this_player_robots' => $temp_this_player_robots,
                    'this_player_zenny' => $this_player_zenny,
                    'this_player_result' => 'victory',
                    'this_reward_pending' => 0,
                    'target_user_id' => $target_user_id,
                    'target_player_token' => $target_player->player_token,
                    'target_player_robots' => $temp_target_player_robots,
                    'target_player_zenny' => $target_battle_zenny,
                    'target_player_result' => 'defeat',
                    'target_reward_pending' => 1
                    );
                //error_log('inserting on line '.__LINE__.' $player_battle_victory_record: '.print_r($player_battle_victory_record, true));
                $db->insert('mmrpg_battles', $player_battle_victory_record);

                // If we're supposed to be updating the user's score, simply increase it by the required amount
                if ($update_this_user_score
                    && !empty($this_user_matchup['battle_points'])){
                    $new_user_points = $this_user_matchup['battle_points'] + MMRPG_SETTINGS_BATTLEPOINTS_PERPLAYER;
                    $db->update('mmrpg_leaderboard', array('board_points' => $new_user_points), "user_id = {$this_user_id}");
                }

                // If we're supposed to be updating the target's score, simply decrease it by the required amount
                if ($update_target_user_score
                    && !empty($target_user_matchup['battle_points'])){
                    $new_user_points = $target_user_matchup['battle_points'] - MMRPG_SETTINGS_BATTLEPOINTS_PERPLAYER;
                    if ($new_user_points < 0){ $new_user_points = 0; }
                    $db->update('mmrpg_leaderboard', array('board_points' => $new_user_points), "user_id = {$target_user_id}");
                }


            }


            /*
             * PLAYER REWARDS
             */

            // Check if the the player was a human character
            if ($this_player->player_side == 'left'){

                // Nothing specific happens

            }


            /*
             * ROBOT DATABASE UPDATE
             */

            // Loop through all the target robot's and add them to the database
            /*
            if (!empty($target_player->values['robots_disabled'])){
                foreach ($target_player->values['robots_disabled'] AS $temp_key => $temp_info){
                    // Add this robot to the global robot database array
                    if (!isset($_SESSION['GAME']['values']['robot_database'][$temp_info['robot_token']])){ $_SESSION['GAME']['values']['robot_database'][$temp_info['robot_token']] = array('robot_token' => $temp_info['robot_token']); }
                    if (!isset($_SESSION['GAME']['values']['robot_database'][$temp_info['robot_token']]['robot_defeated'])){ $_SESSION['GAME']['values']['robot_database'][$temp_info['robot_token']]['robot_defeated'] = 0; }
                    $_SESSION['GAME']['values']['robot_database'][$temp_info['robot_token']]['robot_defeated']++;
                }
            }
            */



        }


        /*
         * BATTLE REWARDS
         */

        // Check if this player was the human player
        if ($this_player->player_side == 'left'){

            // DEBUG
            //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;

            // Update the GAME session variable with the completed battle token
            if ($this->battle_counts){
                // DEBUG
                //$temp_human_rewards['checkpoint'] .= '; '.__LINE__;

                // Create the new session array from scratch to ensure all values exist
                $new_complete_count = 0;

                // Back up the current session array for this battle complete counter
                $old_complete_count = isset($_SESSION['GAME']['values']['battle_complete'][$this_player->player_token][$this->battle_token]) ? $_SESSION['GAME']['values']['battle_complete'][$this_player->player_token][$this->battle_token] : 0;
                if (is_array($old_complete_count) && isset($old_complete_count['battle_count'])){ $old_complete_count = $old_complete_count['battle_count']; }
                elseif (!is_numeric($old_complete_count)){ $old_complete_count = 0; }
                if (!empty($old_complete_count)){ $new_complete_count = $old_complete_count; }

                // Update and/or increment the appropriate battle variables in the new array
                $new_complete_count++;

                // Update the session variable for this player with the updated battle values
                $_SESSION['GAME']['values']['battle_complete'][$this_player->player_token][$this->battle_token] = $new_complete_count;
                $temp_human_rewards['battle_complete'] = $_SESSION['GAME']['values']['battle_complete'][$this_player->player_token][$this->battle_token];

                // Recalculate the overall battle points total with new values
                mmrpg_prototype_calculate_battle_points(true);

            }

            // Collect or define the player variables
            $this_player_token = $this_player->player_token;
            $this_player_info = $this_player->export_array();

            // ROBOT REWARDS

            // Loop through any robot rewards for this battle
            $this_robot_rewards = !empty($this->battle_rewards['robots']) ? $this->battle_rewards['robots'] : array();
            if (!empty($this_robot_rewards)){
                foreach ($this_robot_rewards AS $robot_reward_key => $robot_reward_info){

                    // If this is the copy shot ability and we're in DEMO mode, continue
                    if (!empty($_SESSION['GAME']['DEMO'])){ continue; }

                    // If this robot has already been unlocked, continue
                    //if (mmrpg_prototype_robot_unlocked($this_player_token, $robot_reward_info['token'])){ continue; }

                    // If this robot has already been unlocked by anyone, continue
                    if (mmrpg_prototype_robot_unlocked(false, $robot_reward_info['token'])){ continue; }

                    // Collect the robot info from the index
                    $robot_info = rpg_robot::get_index_info($robot_reward_info['token']);
                    // Search this player's base robots for the robot ID
                    $robot_info['robot_id'] = 0;
                    foreach ($this_player->player_base_robots AS $base_robot){
                        if ($robot_info['robot_token'] == $base_robot['robot_token']){
                            $robot_info['robot_id'] = $base_robot['robot_id'];
                            break;
                        }
                    }
                    // Create the temporary robot object for event creation
                    $temp_robot = rpg_game::get_robot($this, $this_player, $robot_info);

                    // Collect or define the robot zenny and robot rewards variables
                    $this_robot_token = $robot_reward_info['token'];
                    $this_robot_level = !empty($robot_reward_info['level']) ? $robot_reward_info['level'] : 1;
                    $this_robot_experience = !empty($robot_reward_info['experience']) ? $robot_reward_info['experience'] : 0;
                    $this_robot_rewards = !empty($robot_info['robot_rewards']) ? $robot_info['robot_rewards'] : array();

                    // Automatically unlock this robot for use in battle
                    $this_reward = $robot_info;
                    $this_reward['robot_level'] = $this_robot_level;
                    $this_reward['robot_experience'] = $this_robot_experience;
                    mmrpg_game_unlock_robot($this_player_info, $this_reward, true, true);

                }
            }

            // ABILITY REWARDS

            // Loop through any ability rewards for this battle
            $this_ability_rewards = !empty($this->battle_rewards['abilities']) ? $this->battle_rewards['abilities'] : array();
            if (!empty($this_ability_rewards) && empty($_SESSION['GAME']['DEMO'])){
                $temp_abilities_index = rpg_ability::get_index(true);
                foreach ($this_ability_rewards AS $ability_reward_key => $ability_reward_info){

                    // Collect the ability info from the index
                    $ability_info = $temp_abilities_index[$ability_reward_info['token']];
                    // Create the temporary robot object for event creation
                    $temp_ability = rpg_game::get_ability($this, $this_player, $this_robot, $ability_info);

                    // Collect or define the robot zenny and robot rewards variables
                    $this_ability_token = $ability_info['ability_token'];

                    // Now loop through all active robots on this side of the field
                    foreach ($this_player_info['values']['robots_active'] AS $temp_key => $temp_info){
                        // DEBUG
                        //$this->events_create(false, false, 'DEBUG', 'Checking '.$temp_info['robot_name'].' for compatibility with the '.$ability_info['ability_name']);
                        //$debug_fragment = '';
                        // If this robot is a mecha, skip it!
                        if (!empty($temp_info['robot_class']) && $temp_info['robot_class'] == 'mecha'){ continue; }
                        // Equip this ability to the robot is there was a match found
                        if (rpg_robot::has_ability_compatibility($temp_info['robot_token'], $ability_info['ability_token'])){
                            if (!isset( $_SESSION['GAME']['values']['battle_settings'][$this_player_info['player_token']]['player_robots'][$temp_info['robot_token']]['robot_abilities'] )){ $_SESSION['GAME']['values']['battle_settings'][$this_player_info['player_token']]['player_robots'][$temp_info['robot_token']]['robot_abilities'] = array(); }
                            if (count($_SESSION['GAME']['values']['battle_settings'][$this_player_info['player_token']]['player_robots'][$temp_info['robot_token']]['robot_abilities']) < 8){ $_SESSION['GAME']['values']['battle_settings'][$this_player_info['player_token']]['player_robots'][$temp_info['robot_token']]['robot_abilities'][$ability_info['ability_token']] = array('ability_token' => $ability_info['ability_token']); }
                        }
                    }

                    // If this ability has already been unlocked by the player, continue
                    if (mmrpg_prototype_ability_unlocked($this_player_token, false, $ability_reward_info['token'])){ continue; }

                    // Automatically unlock this ability for use in battle
                    $this_reward = array('ability_token' => $this_ability_token);
                    mmrpg_game_unlock_ability($this_player_info, false, $this_reward, true);

                    // Display the robot reward message markup
                    $event_header = $ability_info['ability_name'].' Unlocked';
                    $event_body = rpg_battle::random_positive_word().' <span class="player_name">'.$this_player_info['player_name'].'</span> unlocked a new ability!<br />';
                    $event_body .= ''.$temp_ability->print_name().' can now be used in battle!';
                    $event_options = array();
                    $event_options['console_show_target'] = false;
                    $event_options['this_header_float'] = $this_player->player_side;
                    $event_options['this_body_float'] = $this_player->player_side;
                    $event_options['this_ability'] = $temp_ability;
                    $event_options['this_ability_image'] = 'icon';
                    $event_options['console_show_this_player'] = false;
                    $event_options['console_show_this_robot'] = false;
                    $event_options['console_show_this_ability'] = true;
                    $event_options['canvas_show_this_ability'] = false;
                    $event_options['event_flag_camera_action'] = true;
                    $event_options['event_flag_camera_side'] = $this_robot->player->player_side;
                    $event_options['event_flag_camera_focus'] = 'active';
                    $event_options['event_flag_camera_depth'] = 0;
                    $event_options['event_flag_camera_offset'] = 0;
                    $event_options['event_flag_sound_effects'] = array(
                        array('name' => 'get-big-item', 'volume' => 1.0)
                        );
                    $this_player->player_frame = 'victory';
                    $this_player->update_session();
                    $temp_ability->ability_frame = 'base';
                    $temp_ability->update_session();
                    $this->events_create($this_robot, false, $event_header, $event_body, $event_options);

                }
            }




        } // end of BATTLE REWARDS

        // Check if there is a field star for this stage to collect
        if ($this->battle_result == 'victory' && !empty($this->values['field_star'])){

            // Collect the field star data for this battle
            $temp_field_star = $this->values['field_star'];

            // Print out the event for collecting the new field star
            $temp_name_markup = '<span class="field_name field_type field_type_'.(!empty($temp_field_star['star_type']) ? $temp_field_star['star_type'] : 'none').(!empty($temp_field_star['star_type2']) ? '_'.$temp_field_star['star_type2'] : '').'">'.$temp_field_star['star_name'].' Star</span>';
            $temp_event_header = $this_player->player_name.'&#39;s '.ucfirst($temp_field_star['star_kind']).' Star';
            $temp_event_body = $this_player->print_name().' collected the '.$temp_name_markup.'!<br />';
            $temp_event_body .= 'The new '.ucfirst($temp_field_star['star_kind']).' Star was added to your collection!';
            $temp_event_options = array();
            $temp_event_options['console_show_this_player'] = false;
            $temp_event_options['console_show_target_player'] = false;
            $temp_event_options['console_show_this_robot'] = false;
            $temp_event_options['console_show_target_robot'] = false;
            $temp_event_options['console_show_this_ability'] = false;
            $temp_event_options['console_show_this'] = true;
            $temp_event_options['console_show_this_star'] = true;
            $temp_event_options['this_header_float'] = $temp_event_options['this_body_float'] = $this_player->player_side;
            $temp_event_options['this_star'] = $temp_field_star;
            $temp_event_options['this_ability'] = false;
            $this->events_create(false, false, $temp_event_header, $temp_event_body, $temp_event_options);

            // Update the session with this field star data
            $_SESSION['GAME']['values']['battle_stars'][$temp_field_star['star_token']] = $temp_field_star;

            // DEBUG DEBUG
            //$this->events_create($this_robot, $target_robot, 'DEBUG FIELD STAR', 'You got a field star! The field star names '.implode(' | ', $temp_field_star));

        }

        // If this was an ENDLESS ATTACK BATTLE we should pre-collect certain personal and global records
        $is_endless_battle = false;
        $current_user_id = rpg_user::get_current_userid();
        $wave_value = MMRPG_SETTINGS_BATTLEPOINTS_PERWAVE;
        $old_wave_record = array();
        $new_wave_record = array();
        $global_wave_record = array();
        $current_wave_number = 0;
        $current_robots_used = 0;
        $current_turns_used = 0;
        $current_team_config = '';
        if (!empty($this->flags['challenge_battle'])
            && !empty($this->flags['endless_battle'])){
            $is_endless_battle = true;

            // Collect the endless mission wave number for reference
            $this_battle_chain = !empty($_SESSION['BATTLES_CHAIN'][$this->battle_token]) ? $_SESSION['BATTLES_CHAIN'][$this->battle_token] : array();
            $current_wave_number = !empty($this_battle_chain) ? (count($this_battle_chain) + 1) : 1;
            if (!empty($this_battle_chain)){
                $current_robots_used = $this_battle_chain[0]['battle_robots_used'];
                $current_team_config = $this_battle_chain[0]['battle_team_config'];
            } elseif (!empty($this_player->counters['robots_start_total'])
                && !empty($this_player->values['robots_start_team'])){
                $current_robots_used = $this_player->counters['robots_start_total'];
                $current_team_config = $this_player->player_token.'::'.implode(',', $this_player->values['robots_start_team']);
            }
            if (!empty($this_battle_chain)){ foreach ($this_battle_chain AS $key => $record){ $current_turns_used += $record['battle_turns_used']; } }
            if (!empty($this->counters['battle_turn'])){ $current_turns_used += $this->counters['battle_turn']; }

            // Collect the global wave record for reference
            $global_wave_record = $db->get_array("SELECT
                board1.challenge_waves_completed AS max_waves_completed,
                board1.user_id,
                board1.challenge_result,
                board1.challenge_robots_used,
                board1.challenge_turns_used,
                board1.challenge_team_config,
                board1.challenge_date_firstclear,
                board1.challenge_date_lastclear,
                @base_points := (board1.challenge_waves_completed * {$wave_value}) AS challenge_points_base,
                @robot_points := CEIL(@base_points / board1.challenge_robots_used) AS challenge_points_robot_bonus,
                @turn_points := CEIL(@base_points / (board1.challenge_turns_used / board1.challenge_waves_completed)) AS challenge_points_turn_bonus,
                CEIL(@base_points + @robot_points + @turn_points) AS challenge_points_total
                FROM
                    mmrpg_challenges_waveboard AS board1
                INNER JOIN (
                    SELECT MAX(challenge_waves_completed) AS max_waves_completed
                    FROM mmrpg_challenges_waveboard
                    WHERE challenge_result = 'victory'
                ) AS board2
                ON board1.challenge_waves_completed = board2.max_waves_completed
                WHERE board1.challenge_result = 'victory'
                LIMIT 1
                ;");
            //error_log('$global_wave_record = '.print_r($global_wave_record, true));

            // Check to see if there's an existing record for this user and this challenge
            $old_wave_record = $db->get_array("SELECT
                `board`.`board_id`,
                `board`.`user_id`,
                `board`.`challenge_result`,
                `board`.`challenge_waves_completed`,
                `board`.`challenge_robots_used`,
                `board`.`challenge_turns_used`,
                `board`.`challenge_team_config`,
                `board`.`challenge_date_firstclear`,
                `board`.`challenge_date_lastclear`,
                @base_points := (`board`.`challenge_waves_completed` * {$wave_value}) AS `challenge_points_base`,
                @robot_points := CEIL(@base_points / `board`.`challenge_robots_used`) AS `challenge_points_robot_bonus`,
                @turn_points := CEIL(@base_points / (`board`.`challenge_turns_used` / `board`.`challenge_waves_completed`)) AS `challenge_points_turn_bonus`,
                CEIL(@base_points + @robot_points + @turn_points) AS `challenge_points_total`
                FROM `mmrpg_challenges_waveboard` AS `board`
                WHERE
                `user_id` = {$current_user_id}
                AND `board`.`challenge_waves_completed` > 0
                AND `board`.`challenge_robots_used` > 0
                AND `board`.`challenge_turns_used` > 0
                ;");
            //error_log('$old_wave_record = '.print_r($old_wave_record, true));

            // Manually create an array representing the new wave record with current values
            $new_wave_record = array(
                'user_id' => $current_user_id,
                'challenge_result' => 'victory',
                'challenge_waves_completed' => $current_wave_number,
                'challenge_robots_used' => $current_robots_used,
                'challenge_turns_used' => $current_turns_used,
                'challenge_team_config' => $current_team_config,
                'challenge_date_firstclear' => date('Y-m-d H:i:s'),
                'challenge_date_lastclear' => date('Y-m-d H:i:s'),
                'challenge_points_base' => 0,
                'challenge_points_robot_bonus' => 0,
                'challenge_points_turn_bonus' => 0,
                'challenge_points_total' => 0
                );
            $new_wave_record['challenge_points_base'] = $current_wave_number * $wave_value;
            $new_wave_record['challenge_points_robot_bonus'] = ceil($new_wave_record['challenge_points_base'] / $current_robots_used);
            $new_wave_record['challenge_points_turn_bonus'] = ceil($new_wave_record['challenge_points_base'] / ($current_turns_used / $current_wave_number));
            $new_wave_record['challenge_points_total'] = ceil($new_wave_record['challenge_points_base'] + $new_wave_record['challenge_points_robot_bonus'] + $new_wave_record['challenge_points_turn_bonus']);
            if ($this->battle_result !== 'victory'){ $new_wave_record['challenge_points_total'] = 0; }
            //error_log('$new_wave_record = '.print_r($new_wave_record, true));

        }

        // Define the first event body markup, regardless of player type
        $first_event_header = $this->battle_name.($this->battle_result == 'victory' ? ' Complete' : ' Failure').' <span style="opacity:0.25;">|</span> '.$this->battle_field->field_name;
        $is_final_battle = empty($this->battle_complete_redirect_token) && empty($this->battle_complete_redirect_seed) ? true : false;
        if ($this->battle_result == 'victory'){
            $first_event_body_head = $is_final_battle ? 'Mission complete! ' : 'Battle complete! ';
            $first_event_body_head .= rpg_battle::random_victory_quote().' ';
            // If this is a STAR FIELD battle, we should show the total collected so far
            if (!empty($this->flags['starfield_mission'])){
                $temp_possible_stars = mmrpg_prototype_possible_stars(false);
                $temp_remaining_stars = mmrpg_prototype_remaining_stars(false, $temp_possible_stars);
                $temp_possible_stars_total = count($temp_possible_stars);
                $temp_remaining_stars_total = count($temp_remaining_stars);
                $temp_collected_stars = $temp_possible_stars_total - $temp_remaining_stars_total;
                $temp_print_number = number_format($temp_collected_stars, 0, '.', ',');
                if ($temp_collected_stars == $temp_possible_stars_total){ $temp_print_number = rpg_type::print_span('electric', $temp_print_number); }
                $first_event_body_head .= 'That\'s '.($temp_collected_stars == 1 ? $temp_print_number.' star so far' : $temp_print_number.' stars now').'! ';
            }
            // Otherwise if normal battle, we should show the number of times completed
            else {
                $first_event_body_head .= $temp_human_rewards['battle_complete'] > 1 ? ' That\'s '.$temp_human_rewards['battle_complete'].' times now! ' : '';
            }
            $first_event_body_head .= rpg_battle::random_positive_word().' ';
        } elseif ($this->battle_result == 'defeat'){
            // If this is an ENDLESS ATTACK MODE battle, show a special more positive message
            if (!empty($this->flags['challenge_battle']) && !empty($this->flags['endless_battle'])){
                $first_event_body_head = 'Mission failure.  Wave #'.$current_wave_number.' not completed.  Maybe try again? ';
            }
            // Otherwise if this is a normal battle, defeat is a little bit more netaive
            else {
                $first_event_body_head = $is_final_battle ? 'Mission failure. ' : 'Battle failure. ';
                $first_event_body_head .= rpg_battle::random_defeat_quote().($temp_human_rewards['battle_failure'] > 1 ? '<br /> That&#39;s '.$temp_human_rewards['battle_failure'].' times now&hellip; ' : '');
            }
        }
        //$first_event_body = '<div style="border-bottom: 1px solid rgba(0, 0, 0, 0.1); padding: 0 0 3px; margin: 0 0 3px;">'.$first_event_body.'</div> ';
        //$first_event_body .= '<br />';

        // If this is an ENDLESS ATTACK MODE battle, show the current record
        if ($is_endless_battle){
            $base_value = number_format($wave_value, 0, '.', ',');
            $endless_record_details = array();
            if (!empty($global_wave_record)){
                $wave = number_format($global_wave_record['max_waves_completed'], 0, '.', ',');
                $robots = number_format($global_wave_record['challenge_robots_used'], 0, '.', ',');
                $turns = number_format($global_wave_record['challenge_turns_used'], 0, '.', ',');
                $points = number_format($global_wave_record['challenge_points_total'], 0, '.', ',');
                $endless_record_details[] = '<td class="left">Global Record:</td>'.
                    '<td>Waves ('.$wave.')</td>'.
                    //'<td>&times;</td>'.
                    //'<td>BP ('.$base_value.')</td>'.
                    '<td>&bull;</td>'.
                    '<td>Robots ('.$robots.')</td>'.
                    '<td>&bull;</td>'.
                    '<td>Turns ('.$turns.')</td>'.
                    '<td>=</td>'.
                    '<td class="right">'.$points.' BP</td>'
                    ;
            }
            if (!empty($old_wave_record)){
                $wave = number_format($old_wave_record['challenge_waves_completed'], 0, '.', ',');
                $robots = number_format($old_wave_record['challenge_robots_used'], 0, '.', ',');
                $turns = number_format($old_wave_record['challenge_turns_used'], 0, '.', ',');
                $points = number_format($old_wave_record['challenge_points_total'], 0, '.', ',');
                if (!empty($new_wave_record['challenge_points_total'])
                    && $old_wave_record['challenge_points_total'] > $new_wave_record['challenge_points_total']){
                    $points = '<strong>'.$points.'</strong>';
                }
                $endless_record_details[] = '<td class="left">Your Record:</td>'.
                    '<td>Waves ('.$wave.')</td>'.
                    //'<td>&times;</td>'.
                    //'<td>BP ('.$base_value.')</td>'.
                    '<td>&bull;</td>'.
                    '<td>Robots ('.$robots.')</td>'.
                    '<td>&bull;</td>'.
                    '<td>Turns ('.$turns.')</td>'.
                    '<td>=</td>'.
                    '<td class="right">'.$points.' BP</td>'
                    ;
            } else {
                $endless_record_details[] = '<td class="left">Your Record:</td>'.
                    '<td>-</td>'.
                    //'<td>&times;</td>'.
                    //'<td>BP ('.$base_value.')</td>'.
                    '<td>&bull;</td>'.
                    '<td>-</td>'.
                    '<td>&bull;</td>'.
                    '<td>-</td>'.
                    '<td>=</td>'.
                    '<td class="right">-</td>'
                    ;

            }
            if (!empty($new_wave_record)){
                $wave = number_format($new_wave_record['challenge_waves_completed'], 0, '.', ',');
                $robots = number_format($new_wave_record['challenge_robots_used'], 0, '.', ',');
                $turns = number_format($new_wave_record['challenge_turns_used'], 0, '.', ',');
                $points = number_format($new_wave_record['challenge_points_total'], 0, '.', ',');
                if (!empty($old_wave_record['challenge_points_total'])
                    && $new_wave_record['challenge_points_total'] > $old_wave_record['challenge_points_total']){
                    $points = '<strong>'.$points.'</strong>';
                }
                $endless_record_details[] = '<td class="left">Current Run:</td>'.
                    '<td>Waves ('.$wave.')</td>'.
                    //'<td>&times;</td>'.
                    //'<td>BP ('.$base_value.')</td>'.
                    '<td>&bull;</td>'.
                    '<td>Robots ('.$robots.')</td>'.
                    '<td>&bull;</td>'.
                    '<td>Turns ('.$turns.')</td>'.
                    '<td>=</td>'.
                    '<td class="right">'.$points.' BP</td>'
                    ;
            }
            $first_event_body_details = '';
            $first_event_body_details .= '<table style="width: 100%;"><tbody><tr>';
            $first_event_body_details .= implode('</tr><tr>', $endless_record_details);
            $first_event_body_details .= '</tr></tbody></table>';
        }
        // Otherwise print out the base reward amount
        else {
            $first_event_body_details = 'Base Reward: '.number_format($this->battle_zenny, 0, '.', ',').'&#438;';
        }

        // Print out the bonus and rewards based on the above stats
        if ($this->battle_result == 'victory'){

            // Define the star rating and start at one
            $this_star_rating = 1;

            // Define the zenny reward amount if not empty
            $total_zenny_rewards = $this->battle_zenny;

            // If the winning player had any overkill bonuses, award zenny as well
            if (!empty($this_player->counters['overkill_bonus'])){
                $temp_overkill_bonus = ceil($this_player->counters['overkill_bonus'] / 10);
                $total_zenny_rewards += $temp_overkill_bonus;
                $this_star_rating += 1;
                if (!$is_endless_battle){
                    $first_event_body_details .= ' <span style="opacity:0.25;">|</span> Overkill Bonus: +'.number_format($temp_overkill_bonus, 0, '.', ',').'&#438;';
                }
            }

            // Create an options object for this function and populate
            $options = rpg_game::new_options_object();
            $options->total_zenny_rewards_base = $total_zenny_rewards;
            $options->total_zenny_rewards = &$total_zenny_rewards;
            $options->item_bonuses = array();
            $extra_objects = array('options' => $options);

            // Trigger any robot's item functions if they have been defined for this context
            $active_robots = $this_player->get_robots_active();
            foreach ($active_robots AS $key => $temp_robot){
                $temp_robot->trigger_custom_function('rpg-battle_complete-trigger_victory', $extra_objects);
            }

            // If there were any item bonuses, loop through and display their details now
            if (!$is_endless_battle
                && !empty($options->item_bonuses)){
                foreach ($options->item_bonuses AS $kind => $info){
                    if (!empty($info['count']) && !empty($info['amount'])){
                        $label = ucfirst($kind).' Bonus'.($info['count'] > 1 ? 'es' : '');
                        $percent = ceil(($info['amount'] / $options->total_zenny_rewards_base) * 100);
                        $first_event_body_details .= ' <span style="opacity:0.25;">|</span> '.$label.': +'.$percent.'%';
                    }
                }
            }

            // Print out the current vs allowed turns for this mission and the penalty or bonus, if any
            $reward_mod_strings = array();
            $reward_mod_strings[] = ' Turns vs Goal: '.$this->counters['battle_turn'].' / '.$this->battle_turns;
            if ($this->counters['battle_turn'] != $this->battle_turns){
                $temp_bonus_multiplier = number_format(round(($this->battle_turns / $this->counters['battle_turn']), 2), 1, '.', ',');
                if ($this->counters['battle_turn'] < $this->battle_turns){  $this_star_rating += 1; $reward_mod_strings[] = 'Turn Bonus: x'.$temp_bonus_multiplier.''; }
                else { $this_star_rating -= 1; $reward_mod_strings[] = 'Turn Penalty: x'.$temp_bonus_multiplier.''; }
                $total_zenny_rewards = ceil($total_zenny_rewards * $temp_bonus_multiplier);
            } else {
                $reward_mod_strings[] = 'Turn Bonus: ---';
            }
            if (!$is_endless_battle
                && !empty($reward_mod_strings)){
                $first_event_body_details .= '<br /> ';
                $first_event_body_details .= implode(' <span style="opacity:0.25;">|</span> ', $reward_mod_strings);
            }

            // Print out the current vs allowed robots for this mission and the penalty or bonus, if any
            $temp_target_robot_limit = !empty($this->battle_robot_limit) ? $this->battle_robot_limit : $target_player->counters['robots_start_total'];
            $temp_target_limit_kind = !empty($this->battle_robot_limit) ? 'Goal' : 'Target';
            $reward_mod_strings = array();
            $reward_mod_strings[] = ' Robots vs '.$temp_target_limit_kind.': '.$this_player->counters['robots_start_total'].' / '.$temp_target_robot_limit;
            if ($this_player->counters['robots_start_total'] != $temp_target_robot_limit){
                $temp_bonus_multiplier = number_format(round(($temp_target_robot_limit / $this_player->counters['robots_start_total']), 2), 1, '.', ',');
                if ($this_player->counters['robots_start_total'] < $temp_target_robot_limit){  $this_star_rating += 1; $reward_mod_strings[] = 'Team Bonus: x'.$temp_bonus_multiplier.''; }
                else { $this_star_rating -= 1; $reward_mod_strings[] = 'Team Penalty: x'.$temp_bonus_multiplier.''; }
                $total_zenny_rewards = ceil($total_zenny_rewards * $temp_bonus_multiplier);
            } else {
                if ($temp_target_robot_limit == 1 && $this_player->counters['robots_start_total'] == 1){ $this_star_rating += 1; }
                $reward_mod_strings[] = 'Team Bonus: ---';
            }
            if (!$is_endless_battle
                && !empty($reward_mod_strings)){
                $first_event_body_details .= '<br /> ';
                $first_event_body_details .= implode(' <span style="opacity:0.25;">|</span> ', $reward_mod_strings);
            }

            // If the player hasn't lost any robots, we can give them another star, else remove one
            if (empty($this_player->counters['robots_disabled'])){ $this_star_rating += 1;  }
            else { $this_star_rating -= 1;  }

            // Define the victory results for calculating
            if (!empty($this->flags['challenge_battle'])){
                $victory_results = array(
                    'challenge_turns_used' => $this->counters['battle_turn'],
                    'challenge_turn_limit' => $this->battle_turns,
                    'challenge_robots_used' => $this_player->counters['robots_start_total'],
                    'challenge_robot_limit' => $temp_target_robot_limit
                    );
                $victory_points = rpg_mission_challenge::calculate_challenge_reward_points(
                    $this->values['challenge_battle_kind'],
                    $victory_results,
                    $victory_percent,
                    $victory_rank
                    );
            }

            // Print out the final zenny reward amounts after mods (if not empty)
            $first_event_body_foot = '';
            if ($is_endless_battle){ $first_event_body_head .= '<span style="opacity:0.25;">|</span> Reward: '.number_format($total_zenny_rewards, 0, '.', ',').'&#438;'; }
            else { $first_event_body_foot .= 'Final Reward: '.number_format($total_zenny_rewards, 0, '.', ',').'&#438;'; }
            if (!isset($_SESSION['GAME']['counters']['battle_zenny'])){ $_SESSION['GAME']['counters']['battle_zenny'] = 0; }
            $_SESSION['GAME']['counters']['battle_zenny'] += $total_zenny_rewards;
            $this->counters['final_zenny_reward'] = $total_zenny_rewards;

            // Print out the star rating if a NORMAL BATTLE based on how the user did
            if (empty($this->flags['challenge_battle'])){
                if ($this_star_rating < 1){ $this_star_rating = 1; }
                elseif ($this_star_rating > 5){ $this_star_rating = 5; }
                if (!empty($first_event_body_foot)){ $first_event_body_foot .= ' <span style="opacity:0.25;">|</span> '; }
                for ($i = 0; $i < $this_star_rating; $i++){ $first_event_body_foot .= '<span>&#9733;</span>'; }
                for ($i = 0; $i < (5 - $this_star_rating); $i++){ $first_event_body_foot .= '<span style="opacity:0.25;">&#9734;</span>'; }
            }
            // If this is a normal CHALLENGE BATTLE, display the rank for this victory
            elseif (!empty($this->flags['challenge_battle'])
                && empty($this->flags['endless_battle'])){

                // Print out the rank and collected BP for this attempt
                $first_event_body_foot .= ' <span style="opacity:0.25;">|</span> ';
                $first_event_body_foot .= ' <span>'.$victory_rank.'-Rank Clear! </span>';
                $first_event_body_foot .= ' <span style="opacity:0.25;">|</span>';
                $first_event_body_foot .= ' <span>Score: '.number_format($victory_points, 0, '.', ',').' BP ('.$victory_percent.'%)</span>';

            }
            // Otherwise, if this is an ENDLESS ATTACK MODE, display the total victory count
            elseif ($is_endless_battle){
                //error_log('endless battle has ended, check stats');

                // Collect the current mission number so we now where we are
                $this_loop_size = 18;
                $this_battle_chain = !empty($_SESSION['BATTLES_CHAIN'][$this->battle_token]) ? $_SESSION['BATTLES_CHAIN'][$this->battle_token] : array();
                $this_mission_number = count($this_battle_chain) + 1;
                $this_phase_number = floor($this_mission_number / $this_loop_size) + 1;
                $this_battle_number = $this_mission_number > $this_loop_size ? ($this_mission_number % $this_loop_size) : $this_mission_number;

                // Pre-collect some of the points totals for reference
                $global_points_record = !empty($global_wave_record['challenge_points_total']) ? $global_wave_record['challenge_points_total'] : 0;
                $personal_points_record = !empty($old_wave_record['challenge_points_total']) ? $old_wave_record['challenge_points_total'] : 0;
                $current_points_total = $new_wave_record['challenge_points_total'];

                // Print out the percent (for the zenny) and then the completed mission number
                //$first_event_body_foot .= ' ('.$victory_percent.'%)';
                if (!empty($first_event_body_foot)){ $first_event_body_foot .= ' <span style="opacity:0.25;">|</span> '; }
                $first_event_body_foot .= ' <span>Wave #'.$this_mission_number.' Clear! </span>';
                if (!empty($first_event_body_foot)){ $first_event_body_foot .= ' <span style="opacity:0.25;">|</span> '; }
                $first_event_body_foot .= ' <span>Current Score: '.number_format($current_points_total, 0, '.', ',').' BP</span>';

                // Check to see if there's an existing record and print high score if we're better
                if ($current_points_total > $global_points_record){
                    $first_event_body_foot .= ' <span style="opacity:0.25;">|</span>';
                    $first_event_body_foot .= ' <span data-click-tooltip="Previous Record: '.number_format($global_points_record, 0, '.', ',').' BP">'.rpg_type::print_span('electric_water', 'New Global Record!').'</span>';
                } elseif (!empty($old_wave_record)){
                    if ($current_points_total > $personal_points_record){
                        $first_event_body_foot .= ' <span style="opacity:0.25;">|</span>';
                        $first_event_body_foot .= ' <span data-click-tooltip="Previous Record: '.number_format($personal_points_record, 0, '.', ',').' BP">'.rpg_type::print_span('electric', 'New Personal Record!').'</span>';
                    } else {
                        $first_event_body_foot .= ' <span style="opacity:0.25;">|</span>';
                        $first_event_body_foot .= ' <span>Personal Record: '.number_format($personal_points_record, 0, '.', ',').' BP</span>';
                    }
                } else {
                    $first_event_body_foot .= ' <span style="opacity:0.25;">|</span>';
                    $first_event_body_foot .= ' <span>'.rpg_type::print_span('electric', 'New Personal Record!').'</span>';
                }

            }

        }
        // Otherwise if defeated, do nothing
        else {

            // If this is an ENDLESS ATTACK MODE battle, show the sum of ALL battles as the final zenny reward
            if (!empty($this->flags['challenge_battle'])
                && !empty($this->flags['endless_battle'])){
                //$temp_zenny_value = 0;
                //$temp_battle_chain = !empty($_SESSION['BATTLES_CHAIN'][$this->battle_token]) ? $_SESSION['BATTLES_CHAIN'][$this->battle_token] : array();
                //foreach ($temp_battle_chain AS $key => $info){ $temp_zenny_value += $info['battle_zenny_earned']; }
                //$first_event_body_foot = 'At Least You Earned : '.number_format($temp_zenny_value, 0, '.', ',').'&#438;';
                $first_event_body_foot = '<i class="fa fas fa-infinity"></i>';
            }
            // Otherwise if normal battle, show the values of THIS battle as the final zenny reward
            else {
                $first_event_body_foot = 'Final Reward: 0&#438;';
            }

        }

        $first_event_body = '<div class="results">';
            $first_event_body .= '<div class="head">'.$first_event_body_head.'</div> ';
            $first_event_body .= '<div class="details">'.$first_event_body_details.'</div> ';
            $first_event_body .= '<div class="foot">'.$first_event_body_foot.'</div> ';
        $first_event_body .= '</div>';

        // Charge up STAR SUPPORT if relevant to do at this time
        if ($this->battle_result == 'victory'
            && rpg_prototype::star_support_unlocked()
            && empty($this->flags['star_support_summoned'])){
            $current_cooldown = rpg_prototype::get_star_support_cooldown();
            if ($current_cooldown > 0){
                $force_amount = rpg_prototype::get_star_support_force();
                //error_log('battle victory charging star support by $force_amount = '.$force_amount);
                rpg_prototype::decrease_star_support_cooldown($force_amount);
            }
        }

        // Print the battle complete message
        $event_options = array();
        $event_options['this_header_float'] = 'center';
        $event_options['this_body_float'] = 'center';
        $event_options['this_event_class'] = false;
        $event_options['console_show_this'] = false;
        $event_options['console_show_target'] = false;
        $event_options['event_flag_is_special'] = true;
        $event_options['event_flag_sound_effects'] = array();
        if ($this->battle_result === 'victory'){
            $event_options['event_flag_victory'] = true;
            $event_options['event_flag_sound_effects'][] = array('name' => 'victory-result', 'volume' => 1.0);
        } elseif ($this->battle_result === 'defeat'){
            $event_options['event_flag_defeat'] = true;
            $event_options['event_flag_sound_effects'][] = array('name' => 'failure-result', 'volume' => 1.0);
        }
        $event_options['console_container_classes'] = 'field_type field_type_event field_type_'.($this->battle_result == 'victory' ? 'nature' : 'flame');
        $this->events_create($target_robot, $this_robot, $first_event_header, $first_event_body, $event_options);

        // Create one final frame for the blank frame
        //$this->events_create(false, false, '', '');

    }

    // Define a public function for triggering battle actions
    public function actions_trigger($this_player, $this_robot, $target_player, $target_robot, $this_action, $this_token = ''){
        global $db;

        // Default the return variable to false
        $this_return = false;

        // Reload all variables to ensure values are fresh
        $this_player = rpg_game::get_player($this, array('player_id' => $this_player->player_id, 'player_token' => $this_player->player_token));
        $target_player = rpg_game::get_player($this, array('player_id' => $target_player->player_id, 'player_token' => $target_player->player_token));
        $this_robot = rpg_game::get_robot($this, $this_player, array('robot_id' => $this_robot->robot_id, 'robot_token' => $this_robot->robot_token));
        $target_robot = rpg_game::get_robot($this, $target_player, array('robot_id' => $target_robot->robot_id, 'robot_token' => $target_robot->robot_token));

        // Create the action array in the history object if not exist
        if (!isset($this_player->history['actions'])){
            $this_player->history['actions'] = array();
        }

        // Update the session with recent changes
        $this_player->update_session();

        // If the target player does not have any robots left
        if ($target_player->counters['robots_active'] == 0){

            // Trigger the battle complete action to update status and result
            $this->battle_complete_trigger($this_player, $this_robot, $target_player, $target_robot, $this_action, $this_token);

        }

        // Start the battle loop to allow breaking
        $battle_loop = true;
        while ($battle_loop == true && $this->battle_status != 'complete'){

            // If the battle is just starting
            if ($this_action == 'start'){

                // Ensure this is an actual player
                if ($this_player->player_visible){

                    // If there's a whole team, we should display it as a team entrance
                    if ($this_player->counters['robots_active'] > 1){

                        // Create the enter event for the target player's active robot
                        $event_header = ''.$this_player->player_name.'\'s Robots';
                        $event_body = $this_player->print_name_s().' robots appear on the battle field!<br />';
                        //if (isset($this_player->player_quotes['battle_start'])){ $event_body .= '&quot;<em>'.$this_player->player_quotes['battle_start'].'</em>&quot;'; }
                        if ($this_player->player_visible
                            && isset($this_player->player_quotes['battle_start'])){
                            $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                            $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                            $event_body .= $this_player->print_quote('battle_start', $this_find, $this_replace);
                        }
                        $event_options = array();
                        $event_options['this_header_float'] = $event_options['this_body_float'] = $this_player->player_side;
                        $event_options['console_show_this_player'] = true;
                        $event_options['console_show_target'] = false;
                        $event_options['console_show_target_player'] = false;
                        $event_options['event_flag_camera_action'] = true;
                        $event_options['event_flag_camera_side'] = $this_player->player_side;
                        $event_options['event_flag_camera_focus'] = 'active';
                        $event_options['event_flag_sound_effects'] = array(
                            array('name' => $this_robot->robot_class.'-teleport-in', 'volume' => 1.0)
                            );
                        $this_player->set_frame('taunt');
                        $this_robot->set_frame('taunt');
                        $this_robot->set_frame_styles('');
                        $this_robot->set_detail_styles('');
                        $this_robot->set_position('active');
                        $this->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);
                        $this_player->set_frame('base');
                        $this_robot->set_frame('base');

                    }
                    // Otherwise, we can display this as a heroic single-robot entrance
                    else {

                        // Create the enter event for this player's robots
                        $event_header = ''.$this_player->player_name.'\'s '.$this_robot->robot_name;
                        $event_body = $this_robot->print_name().' '.($this_player->player_side === 'left' ? 'joins' : 'enters').' the battle!<br />';
                        if ($this_robot->robot_token != 'robot'
                            && isset($this_robot->robot_quotes['battle_start'])){
                            $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                            $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                            $event_body .= $this_robot->print_quote('battle_start', $this_find, $this_replace);
                        }
                        $event_options = array();
                        $event_options['this_header_float'] = $this_player->player_side;
                        $event_options['this_body_float'] = $this_player->player_side;
                        $event_options['canvas_show_this'] = true;
                        $event_options['canvas_show_target'] = $event_options['console_show_target'] = false;
                        $event_options['console_show_this_robot'] = true;
                        $event_options['console_show_target_player'] = false;
                        $event_options['event_flag_camera_action'] = true;
                        $event_options['event_flag_camera_side'] = $this_player->player_side;
                        $event_options['event_flag_camera_focus'] = 'active';
                        $event_options['event_flag_sound_effects'] = array(
                            array('name' => $this_robot->robot_class.'-teleport-in', 'volume' => 1.0)
                            );
                        $this_player->set_frame('taunt');
                        $this_robot->set_frame('taunt');
                        $this_robot->set_frame_styles('');
                        $this_robot->set_detail_styles('');
                        $this_robot->set_position('active');
                        $this->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);
                        $this_player->set_frame('base');
                        $this_robot->set_frame('base');

                    }

                }
                // Otherwise, if the player is empty
                else {

                    // Create the enter event for this robot
                    $event_header = ''.$this_robot->robot_name;
                    if ($this_player->counters['robots_active'] > 1){ $event_header .= ' & Team'; }
                    $event_body = "{$this_robot->print_name()} wants to fight!<br />";
                    $this_robot->set_frame('defend');
                    $this_robot->set_frame_styles('');
                    $this_robot->set_detail_styles('');
                    $this_robot->set_position('active');
                    if (isset($this_robot->robot_quotes['battle_start'])){
                        $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                        $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                        $event_body .= $this_robot->print_quote('battle_start', $this_find, $this_replace);
                    }
                    $this_player->update_variables();
                    $event_options = array();
                    $event_options['canvas_show_target'] = false;
                    $event_options['console_show_target'] = false;
                    $event_options['event_flag_camera_action'] = true;
                    $event_options['event_flag_camera_side'] = $this_robot->player->player_side;
                    $event_options['event_flag_camera_focus'] = $this_robot->robot_position;
                    $event_options['event_flag_sound_effects'] = array(
                        array('name' => $this_robot->robot_class.'-teleport-in', 'volume' => 1.0)
                        );
                    $this->events_create($this_robot, false, $event_header, $event_body, $event_options);

                    // Create an event for this robot teleporting in
                    if ($this_player->counters['robots_active'] == 1
                        || $this_robot->robot_position = 'active'){
                        $event_options = array();
                        $event_options['event_flag_camera_action'] = true;
                        $event_options['event_flag_camera_side'] = $this_robot->player->player_side;
                        $event_options['event_flag_camera_focus'] = $this_robot->robot_position;
                        $event_options['event_flag_sound_effects'] = array(
                            array('name' => $this_robot->robot_class.'-taunt-sound', 'volume' => 1.0)
                            );
                        $this_robot->set_frame('taunt');
                        $this->events_create(false, false, '', '', $event_options);
                    }
                    $this_robot->set_frame('base');
                    $this_robot->set_frame_styles('');
                    $this_robot->set_detail_styles('');

                }

                // Change all this player's robot sprite to their taunt
                foreach ($this_player->values['robots_active'] AS $key => $info){
                    if (!preg_match('/display:\s?none;/i', $info['robot_frame_styles'])){ continue; }
                    if ($this_robot->robot_id == $info['robot_id']){
                        $this_robot->set_frame('defend');
                        $this_robot->set_frame_styles('');
                        $this_robot->set_detail_styles('');
                    } else {
                        $temp_robot = rpg_game::get_robot($this, $this_player, $info);
                        $temp_robot->set_frame('taunt');
                        $temp_robot->set_frame_styles('');
                        $temp_robot->set_detail_styles('');
                    }
                }

                // Create an event to show the robots in their taunt sprites
                $num_benched_robots = count($this_player->values['robots_active']) - 1;
                $has_benched_robots = $num_benched_robots > 0 ? true : false;
                $event_options = array();
                $event_options['event_flag_camera_action'] = true;
                $event_options['event_flag_camera_side'] = $this_player->player_side;
                $event_options['event_flag_camera_focus'] = 'active';
                if ($has_benched_robots){
                    $event_options['event_flag_camera_focus'] = 'bench';
                    $event_options['event_flag_sound_effects'] = array();
                    foreach ($this_player->values['robots_active'] AS $key => $info){
                        if ($this_robot->robot_id == $info['robot_id']){ continue; }
                        $temp_robot = rpg_game::get_robot($this, $this_player, $info);
                        $event_options['event_flag_sound_effects'][] = array(
                            'name' => $temp_robot->robot_class.'-teleport-in',
                            'volume' => 1.0,
                            'delay' => ($key * 100)
                            );

                    }
                }
                $this->events_create(false, false, '', '', $event_options);

                // Change all this player's robot sprite back to their base, then update
                foreach ($this_player->values['robots_active'] AS $key => $info){
                    if ($this_robot->robot_id == $info['robot_id']){
                        $this_robot->set_frame('base');
                    } else {
                        $temp_robot = rpg_game::get_robot($this, $this_player, $info);
                        $temp_robot->set_frame('base');
                    }
                }

                // Ensure this robot has abilities to loop through
                if (!isset($this_robot->flags['ability_startup']) && !empty($this_robot->robot_abilities)){
                    // Loop through each of this robot's abilities and trigger the start event
                    $temp_abilities_index = rpg_ability::get_index(true);
                    foreach ($this_robot->robot_abilities AS $this_key => $this_token){
                        // Define the current ability object using the loaded ability data
                        if (!isset($temp_abilities_index[$this_token])){
                            unset($this_robot->robot_abilities[$this_key]);
                            continue;
                        }
                        $temp_abilityinfo = $temp_abilities_index[$this_token];
                        $temp_ability = rpg_game::get_ability($this, $this_player, $this_robot, $temp_abilityinfo);
                    }
                    // And now update the robot with the flag
                    $this_robot->set_flag('ability_startup', true);
                }

                // Set this token to the ID and token of the starting robot
                $this_token = $this_robot->robot_id.'_'.$this_robot->robot_token;

                // Return from the battle function with the start results
                $this_return = true;
                break;

            }
            // Else if the player has chosen to use an ability
            elseif ($this_action == 'ability'){

                // Combine into the actions index
                $temp_actions_index = rpg_ability::get_index(true);

                // DEFINE ABILITY TOKEN

                // If an ability token was not collected
                if (empty($this_token)){
                    // Collect the ability choice from the robot
                    $temp_token = rpg_robot::robot_choices_abilities(array(
                        'this_battle' => $this,
                        'this_field' => $this->battle_field,
                        'this_player' => $this_player,
                        'this_robot' => $this_robot,
                        'target_player' => $target_player,
                        'target_robot' => $target_robot
                        ));
                    $temp_id = $this->index['abilities'][$temp_token]['ability_id'];//array_search($temp_token, $this_robot->robot_abilities);
                    $temp_id = $this_robot->robot_id.str_pad($temp_id, '3', '0', STR_PAD_LEFT);
                    //$this_token = array('ability_id' => $temp_id, 'ability_token' => $temp_token);
                    $this_token = $temp_actions_index[$temp_token];
                    $this_token['ability_id'] = $temp_id;
                }
                // Otherwise, parse the token for data
                else {
                    // Define the ability choice data for this robot
                    list($temp_id, $temp_token) = explode('_', $this_token);
                    //$this_token = array('ability_id' => $temp_id, 'ability_token' => $temp_token);
                    $this_token = $temp_actions_index[$temp_token];
                    $this_token['ability_id'] = $temp_id;
                }

                // If the current robot has been already disabled
                if ($this_robot->robot_status == 'disabled'){
                    // Break from this queued action as the robot cannot fight
                    break;
                }

                // Define the current ability object using the loaded ability data
                $this_ability = rpg_game::get_ability($this, $this_player, $this_robot, $this_token);
                $this_ability->reset_all();

                // Double-check this ability's target if switching has occured to prevent issues
                if ($target_robot->player->player_id != $this_robot->player->player_id
                    && $target_robot->robot_position == 'bench'
                    && $this_ability->ability_target != 'select_target'){
                    //$this->events_create(false, false, 'debug', 'we should correct the target...');
                    $actual_target_robot = $target_player->get_active_robot();
                } else {
                    $actual_target_robot = $target_robot;
                }

                // Trigger this robot's ability
                $this_robot->set_flag('robot_is_using_ability', true);
                $this_ability->ability_results = $this_robot->trigger_ability($actual_target_robot, $this_ability);
                $this_ability->trigger_onload();

                // Ensure the battle has not completed before triggering the taunt event
                if ($this->battle_status != 'complete'){

                    // Check to ensure this robot hasn't taunted already
                    if (!isset($this_robot->flags['robot_quotes']['battle_taunt'])
                        && isset($this_robot->robot_quotes['battle_taunt'])
                        && $this_robot->robot_quotes['battle_taunt'] != '...'
                        && $this_ability->ability_results['this_amount'] > 0
                        && $actual_target_robot->robot_status != 'disabled'
                        && $this->critical_chance(3)){
                        // Generate this robot's taunt event after dealing damage, which only happens once per battle
                        $event_header = ($this_player->player_visible ? $this_player->player_name.'&#39;s ' : '').$this_robot->robot_name;
                        $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                        $this_replace = array($target_player->player_name, $actual_target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                        //$this_quote_text = str_replace($this_find, $this_replace, $this_robot->robot_quotes['battle_taunt']);
                        $event_body = ($this_player->player_visible ? $this_player->print_name().'&#39;s ' : '').$this_robot->print_name().' taunts the opponent!<br />';
                        $event_body .= $this_robot->print_quote('battle_taunt', $this_find, $this_replace);
                        $event_options = array();
                        $event_options['console_show_target'] = false;
                        $event_options['event_flag_sound_effects'] = array(
                            array('name' => 'lets-go', 'volume' => 1.0)
                            );
                        //$event_body .= '&quot;<em>'.$this_quote_text.'</em>&quot;';
                        $this_robot->set_frame('taunt');
                        $actual_target_robot->set_frame('base');
                        $this->events_create($this_robot, $actual_target_robot, $event_header, $event_body, $event_options);
                        $this_robot->set_frame('base');
                        // Create the quote flag to ensure robots don't repeat themselves
                        $this_robot->set_flag('robot_quotes', 'battle_taunt', true);
                    }

                }

                // We're done using this robot's ability
                $this_robot->unset_flag('robot_is_using_ability');

                // If this robot has a Gemini Clone attached, we need to do the ability again
                if ($this->battle_status != 'complete'
                    && $actual_target_robot->robot_status != 'disabled'
                    && isset($this_robot->robot_attachments['ability_gemini-clone'])
                    // ensure this robot has enough weapon energy to use the ability again
                    && $this_robot->robot_weapons >= $this_robot->calculate_weapon_energy($this_ability)
                    // ensure this is not a restricted ability that might cause bugs / be useless
                    && rpg_ability::allow_auto_trigger_with_gemini_clone($this_ability->ability_token)
                    ){

                    // Trigger this Gemini Clone's ability
                    $name_backup = $this_robot->robot_name;
                    //$this_robot->set_name($name_backup.' 2');
                    $this_robot->set_flag('gemini-clone_is_using_ability', true);
                    $this_ability->ability_results = $this_robot->trigger_ability($actual_target_robot, $this_ability);

                    // Ensure the battle has not completed before triggering the taunt event
                    if ($this->battle_status != 'complete'){

                        // Check to ensure this robot hasn't taunted already
                        if (!isset($this_robot->flags['robot_quotes']['battle_taunt'])
                            && isset($this_robot->robot_quotes['battle_taunt'])
                            && $this_robot->robot_quotes['battle_taunt'] != '...'
                            && $this_ability->ability_results['this_amount'] > 0
                            && $actual_target_robot->robot_status != 'disabled'
                            && $this->critical_chance(3)){
                            // Generate this robot's taunt event after dealing damage, which only happens once per battle
                            $event_header = ($this_player->player_visible ? $this_player->player_name.'&#39;s ' : '').$this_robot->robot_name;
                            $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                            $this_replace = array($target_player->player_name, $actual_target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                            //$this_quote_text = str_replace($this_find, $this_replace, $this_robot->robot_quotes['battle_taunt']);
                            $event_body = ($this_player->player_visible ? $this_player->print_name().'&#39;s ' : '').$this_robot->print_name().' taunts the opponent!<br />';
                            $event_body .= $this_robot->print_quote('battle_taunt', $this_find, $this_replace);
                            $event_options = array();
                            $event_options['console_show_target'] = false;
                            $event_options['event_flag_sound_effects'] = array(
                                array('name' => 'lets-go', 'volume' => 1.0)
                                );
                            //$event_body .= '&quot;<em>'.$this_quote_text.'</em>&quot;';
                            $this_robot->set_frame('taunt');
                            $actual_target_robot->set_frame('base');
                            $this->events_create($this_robot, $actual_target_robot, $event_header, $event_body, $event_options);
                            $this_robot->set_frame('base');
                            // Create the quote flag to ensure robots don't repeat themselves
                            $this_robot->set_flag('robot_quotes', 'battle_taunt', true);
                        }

                    }

                    // We're done using this Gemini Clone's ability
                    $this_robot->unset_flag('gemini-clone_is_using_ability');
                    //$this_robot->set_name($name_backup);

                }

                // Set this token to the ID and token of the triggered ability
                $this_token = $this_token['ability_id'].'_'.$this_token['ability_token'];

                // Return from the battle function with the used ability
                $this_return = $this_ability;
                break;

            }
            // Else if the player has chosen to use an item
            elseif ($this_action == 'item'){

                // Combine into the actions index
                $temp_actions_index = rpg_item::get_index(true);

                // DEFINE ABILITY TOKEN

                // If an item token was not collected
                if (empty($this_token)){
                    // Collect the item choice from the robot
                    $temp_token = rpg_robot::robot_choices_items(array(
                        'this_battle' => $this,
                        'this_field' => $this->battle_field,
                        'this_player' => $this_player,
                        'this_robot' => $this_robot,
                        'target_player' => $target_player,
                        'target_robot' => $target_robot
                        ));
                    $temp_id = $this->index['items'][$temp_token]['item_id'];//array_search($temp_token, $this_robot->robot_items);
                    $temp_id = $this_robot->robot_id.str_pad($temp_id, '3', '0', STR_PAD_LEFT);
                    //$this_token = array('item_id' => $temp_id, 'item_token' => $temp_token);
                    $this_token = $temp_actions_index[$temp_token];
                    $this_token['item_id'] = $temp_id;
                }
                // Otherwise, parse the token for data
                else {
                    // Define the item choice data for this robot
                    list($temp_id, $temp_token) = explode('_', $this_token);
                    //$this_token = array('item_id' => $temp_id, 'item_token' => $temp_token);
                    $this_token = rpg_item::parse_index_info($temp_actions_index[$temp_token]);
                    $this_token['item_id'] = $temp_id;
                }

                // If the current robot has been already disabled
                if ($this_robot->robot_status == 'disabled'){
                    // Break from this queued action as the robot cannot fight
                    break;
                }

                // Define the current item object using the loaded item data
                $this_item = rpg_game::get_item($this, $this_player, $this_robot, $this_token);
                // Trigger this robot's item
                $this_item->item_results = $this_robot->trigger_item($target_robot, $this_item);

                // Ensure the battle has not completed before triggering the taunt event
                if ($this->battle_status != 'complete'){

                    // Check to ensure this robot hasn't taunted already
                    if (!isset($this_robot->flags['robot_quotes']['battle_taunt'])
                        && isset($this_robot->robot_quotes['battle_taunt'])
                        && $this_robot->robot_quotes['battle_taunt'] != '...'
                        && $this_item->item_results['this_amount'] > 0
                        && $target_robot->robot_status != 'disabled'
                        && $this->critical_chance(3)){
                        // Generate this robot's taunt event after dealing damage, which only happens once per battle
                        $event_header = ($this_player->player_visible ? $this_player->player_name.'&#39;s ' : '').$this_robot->robot_name;
                        $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                        $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $this_robot->robot_name);
                        //$this_quote_text = str_replace($this_find, $this_replace, $this_robot->robot_quotes['battle_taunt']);
                        $event_body = ($this_player->player_visible ? $this_player->print_name().'&#39;s ' : '').$this_robot->print_name().' taunts the opponent!<br />';
                        $event_body .= $this_robot->print_quote('battle_taunt', $this_find, $this_replace);
                        //$event_body .= '&quot;<em>'.$this_quote_text.'</em>&quot;';
                        $event_options = array();
                        $event_options['console_show_target'] = false;
                        $event_options['event_flag_sound_effects'] = array(
                            array('name' => $this_robot->robot_class.'-taunt-sound', 'volume' => 1.0)
                            );
                        $this_robot->set_frame('taunt');
                        $target_robot->set_frame('base');
                        $this->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);
                        $this_robot->set_frame('base');
                        // Create the quote flag to ensure robots don't repeat themselves
                        $this_robot->set_flag('robot_quotes', 'battle_taunt', true);
                    }

                }

                // Set this token to the ID and token of the triggered item
                $this_token = $this_token['item_id'].'_'.$this_token['item_token'];

                // Return from the battle function with the used item
                $this_return = $this_item;
                break;

            }
            // Else if the player has chosen to switch
            elseif ($this_action == 'switch'){

                // If this player already switched this turn, break now
                if (!empty($this_player->flags['switched_this_turn'])
                    && $this_robot->robot_status != 'disabled'
                    && $this_robot->robot_energy > 0){
                    $this_return = false;
                    break;
                }

                // Collect this player's last action if it exists
                if (!empty($this_player->history['actions'])){
                    $this_recent_switches = array_slice($this_player->history['actions'], -5, 5, false);
                    foreach ($this_recent_switches AS $key => $info){
                        if ($info['this_action'] == 'switch' || $info['this_action'] == 'start'){ $this_recent_switches[$key] = $info['this_action_token']; } //$info['this_action_token'];
                        else { unset($this_recent_switches[$key]); }
                    }
                    $this_recent_switches = array_values($this_recent_switches);
                    $this_recent_switches_count = count($this_recent_switches);
                }
                // Otherwise define an empty action
                else {
                    $this_recent_switches = array();
                    $this_recent_switches_count = 0;
                }

                // If the robot token was not collected and this player is NOT on autopilot
                if (empty($this_token) && $this_player->player_side == 'left'){

                    // Clear any pending actions
                    $this->actions_empty();
                    // Return from the battle function
                    $this_return = true;
                    break;

                }
                // Else If a robot token was not collected and this player IS on autopilot
                elseif (empty($this_token) && $this_player->player_side == 'right'){

                    // Decide which robot the target should use (random)
                    $this_player->update_session();
                    $active_robot_count = count($this_player->values['robots_active']);
                    if ($active_robot_count == 1){
                        $new_robotinfo = $this_player->values['robots_active'][0];
                    } elseif ($active_robot_count > 1){
                        $this_last_switch = !empty($this_recent_switches) ? array_slice($this_recent_switches, -1, 1, false) : array('');
                        $this_last_switch = $this_last_switch[0];
                        $this_current_token = $this_robot->robot_id.'_'.$this_robot->robot_token;
                        do {
                            $new_robotinfo = $this_player->values['robots_active'][mt_rand(0, ($active_robot_count - 1))];
                            if ($new_robotinfo['robot_id'] == $this_robot->robot_id){ continue; }
                            elseif ($new_robotinfo['robot_token'] == 'robot'){ continue; }
                            $this_temp_token = $new_robotinfo['robot_id'].'_'.$new_robotinfo['robot_token'];
                            //$this->events_create(false, false, 'DEBUG', '!empty('.$this_last_switch.') && '.$this_temp_token.' == '.$this_last_switch);
                        } while(empty($this_temp_token));
                    } else {
                        $new_robotinfo = array('robot_id' => 0, 'robot_token' => 'robot');
                        return false;
                    }
                    //$this->events_create(false, false, 'DEBUG', 'auto switch picked ['.print_r($new_robotinfo['robot_name'], true).'] | recent : ['.preg_replace('#\s+#', ' ', print_r($this_recent_switches, true)).']');
                }
                // Otherwise, parse the token for data
                else {
                    list($temp_id, $temp_token) = explode('_', $this_token);
                    $new_robotinfo = array('robot_id' => $temp_id, 'robot_token' => $temp_token);
                }

                //$this->events_create(false, false, 'DEBUG', 'switch picked ['.print_r($new_robotinfo['robot_token'], true).'] | other : []');

                // Update this player and robot's session data before switching
                $this_player->update_session();
                $this_robot->update_session();

                // Define a closure function for switching and return the new robot object
                $switch_function = function($this_battle, $this_player, $old_robot, $new_robotinfo) use ($target_player, $target_robot) {

                    // Define the switch reason based on if this robot is disabled
                    $this_switch_reason = $old_robot->robot_status != 'disabled' ? 'withdrawn' : 'removed';
                    //if ($old_robot->robot_position == 'bench'){ $this_switch_reason = 'auto'; }

                    /*
                    $this_battle->events_create(false, false, 'DEBUG',
                        '$this_switch_reason = '.$this_switch_reason.'<br />'.
                        '$this_player->values[\'current_robot\'] = '.$this_player->values['current_robot'].'<br />'.
                        '$this_player->values[\'current_robot_enter\'] = '.$this_player->values['current_robot_enter'].'<br />'.
                        '');
                    */

                    // Collect a temp version of the new robot for key reading
                    $temp_new_robot = rpg_game::get_robot($this_battle, $this_player, $new_robotinfo);
                    $temp_new_robot_key = $temp_new_robot->robot_key;

                    // If the new robot is not valid for some reason, return false
                    if ($temp_new_robot->robot_token == 'robot'){ return false; }

                    // If this robot is being withdrawn on the same turn it entered, return false
                    if ($this_player->player_side == 'right' && $this_switch_reason == 'withdrawn' && $this_player->values['current_robot_enter'] == $this_battle->counters['battle_turn']){
                        // Return false to cancel the switch action
                        return false;
                    }

                    // If the switch reason was removal, make sure this robot stays hidden
                    if ($this_switch_reason == 'removed' && $this_player->player_side == 'right'){
                        $old_robot->set_flag('hidden', true);
                    }

                    // Create an options object for this function and populate
                    $options = rpg_game::new_options_object();
                    $extra_objects = array('options' => $options);
                    $extra_objects['switchout_robot'] = $old_robot;
                    $extra_objects['switchin_robot'] = $temp_new_robot;

                    // Trigger this and/or the target robot's custom function if one has been defined for this context
                    $old_robot->trigger_custom_function('rpg-battle_switch-out_before', $extra_objects);
                    $temp_new_robot->trigger_custom_function('rpg-battle_switch-in_before', $extra_objects);

                    // Check if the old robot has a custom hook for onswitch and run it if true
                    $temp_switch_function = $old_robot->robot_function_onswitch;
                    $temp_result = $temp_switch_function(array(
                        'this_battle' => $old_robot->player->battle,
                        'this_field' => $old_robot->player->battle->battle_field,
                        'this_player' => $old_robot->player,
                        'this_robot' => $old_robot,
                        'target_player' => $target_player,
                        'target_robot' => $target_robot
                        ));

                    // Check if the new robot has a custom hook for onswitch and run it if true
                    $temp_switch_function = $temp_new_robot->robot_function_onswitch;
                    $temp_result = $temp_switch_function(array(
                        'this_battle' => $temp_new_robot->player->battle,
                        'this_field' => $temp_new_robot->player->battle->battle_field,
                        'this_player' => $temp_new_robot->player,
                        'this_robot' => $temp_new_robot,
                        'target_player' => $target_player,
                        'target_robot' => $target_robot
                        ));

                    // Define the horizontal shift amount for the benched robot switch animation
                    $temp_shift_amount = $this_player->player_side == 'left' ? 50 : -50;

                    // Withdraw the player's robot and display an event for it
                    if ($old_robot->robot_position != 'bench'){
                        $skip_switch_message = false;
                        if (!empty($old_robot->flags['is_friendly'])
                            && !empty($old_robot->flags['is_recruited'])){
                            $old_robot->set_frame('base');
                            $skip_switch_message = true;
                            //error_log('switch a friendly target out');
                        } else {
                            $old_robot->set_frame($old_robot->robot_status !== 'disabled' ? 'base' : 'defeat');
                            $old_robot->set_frame_styles('transform: translate('.$temp_shift_amount.'%, 0); -webkit-transform: translate('.$temp_shift_amount.'%, 0); ');
                            //error_log('switch a normal target out');
                        }
                        $old_robot->set_position('bench');
                        $old_robot->set_key($temp_new_robot_key);
                        $this_player->set_frame('base');
                        $this_player->set_value('current_robot', false);
                        $this_player->set_value('current_robot_enter', false);
                        $event_header = ($this_player->player_visible ? $this_player->player_name.'&#39;s ' : '').$old_robot->robot_name;
                        $event_body = $old_robot->print_name().' is '.$this_switch_reason.' from battle!';
                        $event_options = array();
                        $event_options['canvas_show_disabled_bench'] = $old_robot->robot_id.'_'.$old_robot->robot_token;
                        $event_options['event_flag_camera_action'] = true;
                        $event_options['event_flag_camera_side'] = $old_robot->player->player_side;
                        $event_options['event_flag_camera_focus'] = 'active';
                        $event_options['event_flag_camera_depth'] = 0;
                        $event_options['event_flag_camera_offset'] = 0;
                        if ($old_robot->robot_status != 'disabled' && isset($old_robot->robot_quotes['battle_retreat'])){
                            $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                            $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $old_robot->robot_name);
                            $event_body .= $old_robot->print_quote('battle_retreat', $this_find, $this_replace);
                            //$this_quote_text = str_replace($this_find, $this_replace, $old_robot->robot_quotes['battle_retreat']);
                            //$event_body .= '&quot;<em>'.$this_quote_text.'</em>&quot;';
                        }
                        // Only show the removed event or the withdraw event if there's more than one robot
                        if ($skip_switch_message){ $event_header = $event_body = ''; }
                        if ($this_switch_reason == 'removed' || $this_player->counters['robots_active'] > 1){
                            $this_battle->events_create($old_robot, false, $event_header, $event_body, $event_options);
                        }
                        $old_robot->set_frame_styles('');
                    }

                    // If the switch reason was removal, hide the robot from view
                    if ($this_switch_reason == 'removed'){
                        $old_robot->set_flag('hidden', true);
                    }

                    // Ensure all robots have been withdrawn to the bench at this point
                    if (!empty($this_player->player_robots)){
                        foreach ($this_player->player_robots AS $temp_key => $temp_robotinfo){
                            $temp_robot = rpg_game::get_robot($this_battle, $this_player, $temp_robotinfo);
                            $temp_robot->set_position('bench');
                        }
                    }

                    // Switch in the player's new robot and display an event for it
                    if ($temp_new_robot->robot_position != 'active'){
                        $temp_new_robot->set_position('active');
                        $temp_new_robot->set_key(0);
                        $this_player->set_frame('command');
                        $this_player->set_value('current_robot', $temp_new_robot->robot_string);
                        $this_player->set_value('current_robot_enter', $this_battle->counters['battle_turn']);
                        $event_header = ($this_player->player_visible ? $this_player->player_name.'&#39;s ' : '').$temp_new_robot->robot_name;
                        $event_body = "{$temp_new_robot->print_name()} ".($this_player->player_side === 'left' ? 'joins' : 'enters')." the battle!<br />";
                        $event_options = array();
                        rpg_canvas::apply_camera_action_flags($event_options, $temp_new_robot);
                        if (isset($temp_new_robot->robot_quotes['battle_start'])){
                            $temp_new_robot->set_frame('taunt');
                            $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                            $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $temp_new_robot->robot_name);
                            $event_body .= $temp_new_robot->print_quote('battle_start', $this_find, $this_replace);
                        }

                        // Only show the enter event if the switch reason was removed or if there is more then one robot
                        if ($this_switch_reason == 'removed' || $this_player->counters['robots_active'] > 1){
                            $event_options['event_flag_sound_effects'] = array(
                                array('name' => $old_robot->robot_class.'-switch-in', 'volume' => 1.0),
                                array('name' => $temp_new_robot->robot_class.'-teleport-in', 'volume' => 1.0, 'delay' => 200)
                                );
                            $this_battle->events_create($temp_new_robot, false, $event_header, $event_body, $event_options);
                            $this_battle->events_create(false, false, '', '');
                        }

                    }

                    // Ensure this robot has abilities to loop through
                    if (!isset($temp_new_robot->flags['ability_startup']) && !empty($temp_new_robot->robot_abilities)){
                        // Loop through each of this robot's abilities and trigger the start event
                        $temp_abilities_index = rpg_ability::get_index(true);
                        foreach ($temp_new_robot->robot_abilities AS $this_key => $this_token){
                            if (!isset($temp_abilities_index[$this_token])){ continue; }
                            // Define the current ability object using the loaded ability data
                            $temp_abilityinfo = $temp_abilities_index[$this_token];
                            $temp_ability = rpg_game::get_ability($this_battle, $this_player, $temp_new_robot, $temp_abilityinfo);
                        }
                        // And now update the robot with the flag
                        $temp_new_robot->set_flag('ability_startup', true);
                    }

                    // Now we can update the current robot's frame regardless of what happened
                    $temp_new_robot->set_frame($temp_new_robot->robot_status != 'disabled' ? 'base' : 'defeat');

                    // Update the owning player's session variables with the change
                    $this_player->update_variables();

                    // Check if the old robot has a custom hook for onswitchout and run it if true
                    $temp_switchout_function = $old_robot->robot_function_onswitchout;
                    $temp_result = $temp_switchout_function(array(
                        'this_battle' => $old_robot->player->battle,
                        'this_field' => $old_robot->player->battle->battle_field,
                        'this_player' => $old_robot->player,
                        'this_robot' => $old_robot,
                        'target_player' => $target_player,
                        'target_robot' => $target_robot
                        ));

                    // Check if the new robot has a custom hook for onswitchin and run it if true
                    $temp_switchin_function = $temp_new_robot->robot_function_onswitchin;
                    $temp_result = $temp_switchin_function(array(
                        'this_battle' => $temp_new_robot->player->battle,
                        'this_field' => $temp_new_robot->player->battle->battle_field,
                        'this_player' => $temp_new_robot->player,
                        'this_robot' => $temp_new_robot,
                        'target_player' => $target_player,
                        'target_robot' => $target_robot
                        ));

                    // Trigger this and/or the target robot's custom function if one has been defined for this context
                    $old_robot->trigger_custom_function('rpg-battle_switch-out_after', $extra_objects);
                    $temp_new_robot->trigger_custom_function('rpg-battle_switch-in_after', $extra_objects);

                    // Return the new robot we've switched to
                    return $temp_new_robot;

                    };

                // Switch the old robot for the new one and collect the reference
                $this_robot = $switch_function($this, $this_player, $this_robot, $new_robotinfo);
                if ($this_player->player_side == 'left'){ $GLOBALS['this_robot'] = $this_robot; }
                elseif ($this_player->player_side == 'right'){ $GLOBALS['target_robot'] = $this_robot; }

                // Set this token to the ID and token of the newly switched robot
                //$this_token = $new_robotinfo['robot_id'].'_'.$new_robotinfo['robot_token'];
                $this_token = $this_robot->robot_id.'_'.$this_robot->robot_token;

                //$this->events_create(false, false, 'DEBUG', 'checkpoint ['.$this_token.'] | other : []');

                // Set a flag on this player so they don't switch again
                $this_player->set_flag('switched_this_turn', true);

                // Return from the battle function
                $this_return = true;
                break;
            }
            // Else if the player has chosen to scan the target
            elseif ($this_action == 'scan'){

                // Otherwise, parse the token for data
                if (!empty($this_token)){
                    list($temp_id, $temp_token) = explode('_', $this_token);
                    $this_token = array('robot_id' => $temp_id, 'robot_token' => $temp_token);
                }

                // If an ability token was not collected
                if (empty($this_token)){
                    // Decide which robot should be scanned
                    foreach ($target_player->player_robots AS $this_key => $this_robotinfo){
                        if ($this_robotinfo['robot_position'] == 'active'){ $this_token = $this_robotinfo;  }
                    }
                }

                //die('<pre>'.print_r($temp_target_robot_info, true).'</pre>');

                // Create the temporary target player and robot objects
                $temp_target_robot_info = !empty($this->values['robots'][$this_token['robot_id']]) ? $this->values['robots'][$this_token['robot_id']] : array();
                $temp_target_player_info = !empty($this->values['players'][$temp_target_robot_info['player_id']]) ? $this->values['players'][$temp_target_robot_info['player_id']] : array();
                $temp_target_player = rpg_game::get_player($this, $temp_target_player_info);
                $temp_target_robot = rpg_game::get_robot($this, $temp_target_player, $temp_target_robot_info);
                //die('<pre>'.print_r($temp_target_robot, true).'</pre>');

                // Ensure the target robot's frame is set to its base
                $temp_target_robot->set_frame('base');

                // Collect the weakness, resistsance, affinity, and immunity text
                $temp_target_robot_weaknesses = $temp_target_robot->print_weaknesses();
                $temp_target_robot_resistances = $temp_target_robot->print_resistances();
                $temp_target_robot_affinities = $temp_target_robot->print_affinities();
                $temp_target_robot_immunities = $temp_target_robot->print_immunities();

                // Now change the target robot's frame is set to its taunt
                $temp_target_robot->set_frame('taunt');

                $temp_stat_padding_total = 300;
                $temp_stat_counter_total = $temp_target_robot->robot_energy + $temp_target_robot->robot_attack + $temp_target_robot->robot_defense + $temp_target_robot->robot_speed;
                $temp_stat_counter_base_total = $temp_target_robot->robot_base_energy + $temp_target_robot->robot_base_attack + $temp_target_robot->robot_base_defense + $temp_target_robot->robot_base_speed;

                $temp_energy_padding = ceil(($temp_target_robot->robot_energy / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_energy_base_padding = ceil(($temp_target_robot->robot_base_energy / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_energy_base_padding = $temp_energy_base_padding - $temp_energy_padding;

                $temp_attack_padding = ceil((min($temp_target_robot->robot_attack, $temp_target_robot->robot_base_attack) / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_attack_base_padding = ceil(($temp_target_robot->robot_base_attack / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_attack_base_padding = $temp_attack_base_padding - $temp_attack_padding;
                if ($temp_attack_padding < 1){ $temp_attack_padding = 0; }
                elseif ($temp_attack_padding > $temp_stat_padding_total){ $temp_attack_padding = $temp_stat_padding_total; }
                if ($temp_attack_base_padding < 1){ $temp_attack_base_padding = 0; }
                elseif ($temp_attack_base_padding > $temp_stat_padding_total){ $temp_attack_base_padding = $temp_stat_padding_total; }
                $temp_attack_mod_icon = $temp_target_robot->counters['attack_mods'] > 0 ? '&#x25b2;' : ($temp_target_robot->counters['attack_mods'] < 0 ? '&#x25bc;' : '');
                if (!empty($temp_attack_mod_icon)){ $temp_attack_mod_icon = '<sub style="display:inline-block;font-size:60%;transform:translate(0,-2px);">'.$temp_attack_mod_icon.'</sub>'; }

                $temp_defense_padding = ceil((min($temp_target_robot->robot_defense, $temp_target_robot->robot_base_defense) / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_defense_base_padding = ceil(($temp_target_robot->robot_base_defense / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_defense_base_padding = $temp_defense_base_padding - $temp_defense_padding;
                if ($temp_defense_padding < 1){ $temp_defense_padding = 0; }
                elseif ($temp_defense_padding > $temp_stat_padding_total){ $temp_defense_padding = $temp_stat_padding_total; }
                if ($temp_defense_base_padding < 1){ $temp_defense_base_padding = 0; }
                elseif ($temp_defense_base_padding > $temp_stat_padding_total){ $temp_defense_base_padding = $temp_stat_padding_total; }
                $temp_defense_mod_icon = $temp_target_robot->counters['defense_mods'] > 0 ? '&#x25b2;' : ($temp_target_robot->counters['defense_mods'] < 0 ? '&#x25bc;' : '');
                if (!empty($temp_defense_mod_icon)){ $temp_defense_mod_icon = '<sub style="display:inline-block;font-size:60%;transform:translate(0,-2px);">'.$temp_defense_mod_icon.'</sub>'; }

                $temp_speed_padding = ceil((min($temp_target_robot->robot_speed, $temp_target_robot->robot_base_speed) / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_speed_base_padding = ceil(($temp_target_robot->robot_base_speed / $temp_stat_counter_base_total) * $temp_stat_padding_total);
                $temp_speed_base_padding = $temp_speed_base_padding - $temp_speed_padding;
                if ($temp_speed_padding < 1){ $temp_speed_padding = 0; }
                elseif ($temp_speed_padding > $temp_stat_padding_total){ $temp_speed_padding = $temp_stat_padding_total; }
                if ($temp_speed_base_padding < 1){ $temp_speed_base_padding = 0; }
                elseif ($temp_speed_base_padding > $temp_stat_padding_total){ $temp_speed_base_padding = $temp_stat_padding_total; }
                $temp_speed_mod_icon = $temp_target_robot->counters['speed_mods'] > 0 ? '&#x25b2;' : ($temp_target_robot->counters['speed_mods'] < 0 ? '&#x25bc;' : '');
                if (!empty($temp_speed_mod_icon)){ $temp_speed_mod_icon = '<sub style="display:inline-block;font-size:60%;transform:translate(0,-2px);">'.$temp_speed_mod_icon.'</sub>'; }

                // Create an options object for this function and populate
                $options = rpg_game::new_options_object();
                $options->show_skills = $this_player->get_flag('hyperscan_enabled') ? true : false;
                $options->show_abilities = $this_player->get_flag('hyperscan_enabled') ? true : false;
                $extra_objects = array('options' => $options);
                $extra_objects['initiator_robot'] = $this_robot;
                $extra_objects['recipient_robot'] = $temp_target_robot;

                // Check to see if this player has any hyperscan robots still active on the field
                $hyperscan_robots = $this_player->get_value('hyperscan_robots');
                if (!empty($hyperscan_robots)){
                    $active_ids = $this_player->get_active_robot_ids();
                    foreach ($hyperscan_robots AS $rid){
                        if (in_array($rid, $active_ids)){
                            $options->show_skills = true;
                            $options->show_abilities = true;
                        }
                    }
                }

                // Trigger this and/or the target robot's custom function if one has been defined for this context
                $this_robot->trigger_custom_function('rpg-battle_scan-target_before', $extra_objects);
                $temp_target_robot->trigger_custom_function('rpg-battle_scan-target_before', $extra_objects);

                // Check to see if this is a "new" scan for this player
                $is_new_scan = false;
                if (empty($_SESSION['GAME']['values']['robot_database'][$temp_target_robot->robot_token]['robot_scanned'])){ $is_new_scan = true; }

                // Create an event showing the scanned robot's data
                $event_header = ($temp_target_player->player_visible ? $temp_target_player->player_name.'&#39;s ' : '').$temp_target_robot->robot_name;
                if ($is_new_scan){ $event_header .= '  <span class="robot_stat robot_type robot_type_electric" style="font-size: 90%; top: -1px;">New!</span>'; }
                $event_body = '';
                ob_start();
                ?>
                    <div class="target_scan_table">
                        <table class="full">
                            <colgroup>
                                <col width="20%" />
                                <col width="43%" />
                                <col width="4%" />
                                <col width="13%" />
                                <col width="20%" />
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td class="left">Name  : </td>
                                    <td  class="right"><?= $temp_target_robot->print_number() ?> <?= $temp_target_robot->print_name() ?></td>
                                    <td class="center">&nbsp;</td>
                                    <td class="left">Core : </td>
                                    <td  class="right"><?= $temp_target_robot->print_core() ?></td>
                                </tr>
                                <tr>
                                    <td class="left">Weaknesses : </td>
                                    <td  class="right"><?= !empty($temp_target_robot_weaknesses) ? $temp_target_robot_weaknesses : '<span class="robot_weakness">None</span>' ?></td>
                                    <td class="center">&nbsp;</td>
                                    <td class="left">Energy : </td>
                                    <td  class="right"><span data-click-tooltip="<?= floor(($temp_target_robot->robot_energy / $temp_target_robot->robot_base_energy) * 100).'% | '.$temp_target_robot->robot_energy.' / '.$temp_target_robot->robot_base_energy ?>"data-tooltip-type="robot_type robot_type_energy" data-tooltip-align="right" class="robot_stat robot_type robot_type_empty" style="padding: 0 0 0 <?= $temp_energy_base_padding ?>px;"><span class="robot_stat robot_type robot_type_energy" style="padding-left: <?= $temp_energy_padding ?>px;"><?= $temp_target_robot->robot_energy ?></span></span></td>
                                </tr>
                                <tr>
                                    <td class="left">Resistances : </td>
                                    <td  class="right"><?= !empty($temp_target_robot_resistances) ? $temp_target_robot_resistances : '<span class="robot_resistance">None</span>' ?></td>
                                    <td class="center">&nbsp;</td>
                                    <td class="left">Attack : </td>
                                    <td  class="right"><span data-click-tooltip="<?= floor(($temp_target_robot->robot_attack / $temp_target_robot->robot_base_attack) * 100).'% | '.$temp_target_robot->robot_attack.' / '.$temp_target_robot->robot_base_attack ?>"data-tooltip-type="robot_type robot_type_attack" data-tooltip-align="right" class="robot_stat robot_type robot_type_empty" style="padding: 0 0 0 <?= $temp_attack_base_padding ?>px;"><span class="robot_stat robot_type robot_type_attack" style="padding-left: <?= $temp_attack_padding ?>px;"><?= $temp_attack_mod_icon.' '.$temp_target_robot->robot_attack ?></span></span></td>
                                </tr>
                                <tr>
                                    <td class="left">Affinities : </td>
                                    <td  class="right"><?= !empty($temp_target_robot_affinities) ? $temp_target_robot_affinities : '<span class="robot_affinity">None</span>' ?></td>
                                    <td class="center">&nbsp;</td>
                                    <td class="left">Defense : </td>
                                    <td  class="right"><span data-click-tooltip="<?= floor(($temp_target_robot->robot_defense / $temp_target_robot->robot_base_defense) * 100).'% | '.$temp_target_robot->robot_defense.' / '.$temp_target_robot->robot_base_defense ?>"data-tooltip-type="robot_type robot_type_defense" data-tooltip-align="right" class="robot_stat robot_type robot_type_empty" style="padding: 0 0 0 <?= $temp_defense_base_padding ?>px;"><span class="robot_stat robot_type robot_type_defense" style="padding-left: <?= $temp_defense_padding ?>px;"><?= $temp_defense_mod_icon.' '.$temp_target_robot->robot_defense ?></span></span></td>
                                </tr>
                                <tr>
                                    <td class="left">Immunities : </td>
                                    <td  class="right"><?= !empty($temp_target_robot_immunities) ? $temp_target_robot_immunities : '<span class="robot_immunity">None</span>' ?></td>
                                    <td class="center">&nbsp;</td>
                                    <td class="left">Speed : </td>
                                    <td  class="right"><span data-click-tooltip="<?= floor(($temp_target_robot->robot_speed / $temp_target_robot->robot_base_speed) * 100).'% | '.$temp_target_robot->robot_speed.' / '.$temp_target_robot->robot_base_speed ?>"data-tooltip-type="robot_type robot_type_speed" data-tooltip-align="right" class="robot_stat robot_type robot_type_empty" style="padding: 0 0 0 <?= $temp_speed_base_padding ?>px;"><span class="robot_stat robot_type robot_type_speed" style="padding-left: <?= $temp_speed_padding ?>px;"><?= $temp_speed_mod_icon.' '.$temp_target_robot->robot_speed ?></span></span></td>
                                </tr>
                                <? if (($options->show_skills || MMRPG_CONFIG_DEBUG_MODE) && !empty($temp_target_robot->robot_skill)){ ?>
                                    <?
                                    $temp_target_robot_skill = $temp_target_robot->get_skill_info();
                                    ?>
                                    <tr>
                                        <td class="right" colspan="5">
                                            <span style="float: left;">Skill :</span>
                                            <div class="skill_bubble">
                                                <strong class="skill_name type type_<?= $temp_target_robot_skill['skill_display_type'] ?>"><?= $temp_target_robot_skill['skill_name'] ?></strong>
                                                <p class="skill_stat skill_desc type type_empty"><?= $temp_target_robot_skill['skill_description'] ?></p>
                                            </div>
                                        </td>
                                    </tr>
                                <? } ?>
                                <? if ($options->show_abilities || MMRPG_CONFIG_DEBUG_MODE){ ?>
                                    <?
                                    $temp_target_robot_abilities = $temp_target_robot->print_abilities(false);
                                    while (count($temp_target_robot_abilities) < 8){ $temp_target_robot_abilities[] = '<span class="ability_name type type_empty">&nbsp;</span>'; }
                                    $temp_target_robot_abilities = '<div class="ability_cell">'.implode('</div><div class="ability_cell">', $temp_target_robot_abilities).'</div>';
                                    ?>
                                    <tr>
                                        <td class="right" colspan="5">
                                            <span style="float: left;">Abilities :</span>
                                            <?= !empty($temp_target_robot_abilities) ? '<div class="ability_list">'.$temp_target_robot_abilities.'</div>' : '<span class="robot_ability">None</span>' ?>
                                        </td>
                                    </tr>
                                <? } ?>
                                <? if (MMRPG_CONFIG_DEBUG_MODE){ ?>
                                    <? $pre_styles = 'float: left; clear: both; box-sizing: border-box; padding: 10px; text-align: left; width: 100%; border: 0 none transparent; background-color: rgba(0, 0, 0, 0.1); color: #efefef; margin-bottom: 4px;'; ?>
                                    <? if (MMRPG_CONFIG_IS_LIVE === false){ ?>
                                        <tr>
                                            <td class="right" colspan="5">
                                                <span style="float: left;">Robot Export Array :</span>
                                                <input type="text"
                                                    readonly="readonly"
                                                    style="<?= $pre_styles ?>"
                                                    value="<?= str_replace('"', '&quot;', json_encode( $temp_target_robot->export_array() )) ?>"
                                                    />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="right" colspan="5">
                                            <span style="float: left;">Target Player Counters Array :</span>
                                            <input type="text"
                                                readonly="readonly"
                                                style="<?= $pre_styles ?>"
                                                value="<?= str_replace('"', '&quot;', json_encode( $temp_target_player->counters )) ?>"
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="right" colspan="5">
                                                <span style="float: left;">Target Player Starforce Array :</span>
                                                <input type="text"
                                                    readonly="readonly"
                                                    style="<?= $pre_styles ?>"
                                                    value="<?= str_replace('"', '&quot;', json_encode( $temp_target_player->player_starforce )) ?>"
                                                    />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="right" colspan="5">
                                                <span style="float: left;">Your Player Starforce Array :</span>
                                                <input type="text"
                                                    readonly="readonly"
                                                    style="<?= $pre_styles ?>"
                                                    value="<?= str_replace('"', '&quot;', json_encode( $this_player->player_starforce )) ?>"
                                                    />
                                            </td>
                                        </tr>
                                    <? } ?>
                                <? } ?>
                            </tbody>
                        </table>
                    </div>
                <?
                $event_body .= preg_replace('#\s+#', ' ', trim(ob_get_clean()));
                $event_options = array();
                $event_options['console_container_height'] = 2;
                $event_options['canvas_show_this'] = false;
                $event_options['event_flag_camera_action'] = true;
                $event_options['event_flag_camera_side'] = $temp_target_robot->player->player_side;
                $event_options['event_flag_camera_focus'] = $temp_target_robot->robot_position;
                $event_options['event_flag_camera_depth'] = $temp_target_robot->robot_key;
                $event_options['event_flag_sound_effects'] = array(
                    array('name' => 'scan-start', 'volume' => 1.0)
                    );
                $temp_target_robot->set_frame('defend');
                $this->events_create($temp_target_robot, false, $event_header, $event_body, $event_options);

                // Ensure the target robot's frame is set to its base
                $event_options['event_flag_sound_effects'] = array();
                $event_options['event_flag_sound_effects'][] = array('name' => 'scan-success', 'volume' => 1.0);
                if ($is_new_scan){ $event_options['event_flag_sound_effects'][] = array('name' => 'scan-success-new', 'volume' => 1.0, 'delay' => 150); }
                $temp_target_robot->set_frame('taunt');
                $this->events_create(false, false, '', '', $event_options);
                $temp_target_robot->set_frame('base');
                $this->events_create(false, false, '', '');

                // Add this robot to the global robot database array
                if (!isset($_SESSION['GAME']['values']['robot_database'][$temp_target_robot->robot_token])){ $_SESSION['GAME']['values']['robot_database'][$temp_target_robot->robot_token] = array('robot_token' => $temp_target_robot->robot_token); }
                if (!isset($_SESSION['GAME']['values']['robot_database'][$temp_target_robot->robot_token]['robot_scanned'])){ $_SESSION['GAME']['values']['robot_database'][$temp_target_robot->robot_token]['robot_scanned'] = 0; }
                $_SESSION['GAME']['values']['robot_database'][$temp_target_robot->robot_token]['robot_scanned']++;

                // Trigger this and/or the target robot's custom function if one has been defined for this context
                $this_robot->trigger_custom_function('rpg-battle_scan-target_after', $extra_objects);
                $temp_target_robot->trigger_custom_function('rpg-battle_scan-target_after', $extra_objects);

                // Set this token to the ID and token of the triggered ability
                $this_token = $this_token['robot_id'].'_'.$this_token['robot_token'];

                // Return from the battle function with the scanned robot
                $this_return = true;
                break;

            }

            // Break out of the battle loop by default
            break;
        }

        // Set the hidden flag on this robot if necessary
        if ($this_robot->robot_position == 'bench' && ($this_robot->robot_status == 'disabled' || $this_robot->robot_energy < 1)){
            $this_robot->flags['apply_disabled_state'] = true;
            $this_robot->flags['hidden'] = true;
            $this_robot->update_session();
        }

        // Set the hidden flag on the target robot if necessary
        if ($target_robot->robot_position == 'bench' && ($target_robot->robot_status == 'disabled' || $target_robot->robot_energy < 1)){
            $target_robot->flags['apply_disabled_state'] = true;
            $target_robot->flags['hidden'] = true;
            $target_robot->update_session();
        }

        // If the target player does not have any robots left
        if ($target_player->counters['robots_active'] == 0){

            // Trigger the battle complete action to update status and result
            $this->battle_complete_trigger($this_player, $this_robot, $target_player, $target_robot, $this_action, $this_token);

        }

        // Update this player's history object with this action
        $this_player->history['actions'][] = array(
                'this_action' => $this_action,
                'this_action_token' => $this_token
                );

        // Update this battle's session data
        $this->update_session();

        // Update this player's session data
        $this_player->update_session();
        // Update the target player's session data
        $target_player->update_session();

        // Update this robot's session data
        $this_robot->update_session();
        // Update the target robot's session data
        $target_robot->update_session();

        // Update the current ability's session data
        if (isset($this_ability)){
            $this_ability->update_session();
        }

        // Return the result for this battle function
        return $this_return;

    }

    /**
     * Create a new debug entry in the global battle event queue
     * @param string $file_name
     * @param int $line_number
     * @param string $debug_message
     */
    public function events_debug($file_name, $line_number, $debug_message){
        if (MMRPG_CONFIG_DEBUG_MODE){
            $file_name = basename($file_name);
            $line_number = 'Line '.$line_number;
            $this->events_create(false, false, 'DEBUG | '.$file_name.' | '.$line_number, $debug_message);
        }
    }

    // Define a publicfunction for adding to the event array
    public function events_create($this_robot = false, $target_robot = false, $event_header = '', $event_body = '', $event_options = array()){

        // Clone or define the event objects
        $this_battle = $this;
        $this_field = $this->battle_field; //array_slice($this->values['fields'];
        $this_player = false;
        $this_robot = !empty($this_robot) ? $this_robot : false;
        if (!empty($this_robot)){ $this_player = rpg_game::get_player($this, $this->values['players'][$this_robot->player_id]); }
        $target_player = false;
        $target_robot = !empty($target_robot) ? $target_robot : false;
        if (!empty($target_robot)){ $target_player = rpg_game::get_player($this, $this->values['players'][$target_robot->player_id]); }

        // Increment the internal events counter
        if (!isset($this->counters['events'])){ $this->counters['events'] = 1; }
        else { $this->counters['events']++; }

        // Create the event body and header
        $event_header = preg_replace('/\s+/i', ' ', $event_header);
        $event_body = preg_replace('/\s+/i', ' ', $event_body);

        // Generate the event markup and add it to the array
        $this->events[] = $this->events_markup_generate(array(
            'this_battle' => $this_battle,
            'this_field' => $this_field,
            'this_player' => $this_player,
            'this_robot' => $this_robot,
            'target_player' => $target_player,
            'target_robot' => $target_robot,
            'event_header' => $event_header,
            'event_body' => $event_body,
            'event_options' => $event_options
            ));

        // Return the resulting array
        return $this->events;

    }

    // Define a public function for emptying the events array
    public function events_empty(){

        // Empty the internal events array
        $this->events = array();

        // Return the resulting array
        return $this->events;

    }

    // Define a function for generating canvas scene markup
    public function canvas_markup($eventinfo, $options = array()){

        // Delegate markup generation to the canvas class
        return rpg_canvas::battle_markup($this, $eventinfo, $options);

    }

    // Define a function for generating console message markup
    public function console_markup($eventinfo, $options = array()){

        // Delegate markup generation to the console class
        return rpg_console::battle_markup($this, $eventinfo, $options);

    }

    // Define a public function for calculating canvas markup offsets w/ perspective
    public function canvas_markup_offset($sprite_key, $sprite_position, $sprite_size, $team_size = 1){
        return self::calculate_canvas_markup_offset($sprite_key, $sprite_position, $sprite_size, $team_size);
    }

    // Define a public function for calculating canvas markup offsets w/ perspective
    public static function calculate_canvas_markup_offset($sprite_key, $sprite_position, $sprite_size, $team_size = 1){

        // Define the max bench size so we can shift later
        $max_team_size = 8;
        $max_bench_size = 7;
        $base_sprite_size = 40;
        $zoom_sprite_size = $base_sprite_size * 2;

        // Define the size of the grid (in pixels)
        $grid_width = ceil(750 / 2); // Half the canvas width
        $grid_width -= ceil($grid_width / 9); // Pull back for the "middle" tile
        $grid_height = 84; // Grid height
        $grid_offset_bottom = 35; // Offset from the bottom of the canvas

        // Define minimum and maximum Z-offset for sprites.
        $z_min = 4900; // Smallest Z-offset for sprites at the farthest row
        $z_max = 5100; // Largest Z-offset for sprites at the closest row

        // Define the number of cells in the grid
        $grid_columns = 4;
        $grid_rows = 7;
        $grid_row_middle = ceil($grid_rows / 2);
        $grid_column_offsets = array();

        // Manually define the height of rows because it's just not working
        $grid_row_tilt = 26; // degrees
        $grid_row_heights = array(19, 16, 13, 10, 9, 8, 6);
        $grid_col_widths = array(132, 117, 106, 98, 91, 85, 79);
        $grid_row_height = function($row = 1, $column = 1) use ($grid_row_heights) { return $row >= 1 ? $grid_row_heights[$row - 1] : 0; };
        $grid_col_width = function($row = 1, $column = 1) use ($grid_col_widths) { return $row >= 1 ? $grid_col_widths[$row - 1] : 0; };
        $grid_row_heights_total = function($row = 1, $column = 1) use ($grid_row_heights) { return $row >= 1 ? array_sum(array_slice($grid_row_heights, 0, ($row - 1))) : 0; };
        $grid_col_widths_total = function($row = 1, $column = 1) use ($grid_col_widths) { return $row >= 1 && $column >= 1 ? ($grid_col_widths[$row - 1] * ($column - 1)) : 0; };

        // Define minimum and maximum scale factors for sprites
        $scale_base = 1.0;
        $scale_shift = 0.2;
        $scale_min = $scale_base - ($scale_base * $scale_shift);  // Smallest scale for sprites at the farthest row
        $scale_max = $scale_base + ($scale_base * $scale_shift);  // Largest scale for sprites at the closest row

        // Define the size of a cell (in pixels)
        $cell_width = $grid_width / $grid_columns;
        $cell_height = $grid_height / $grid_rows;

        // Define the default row and column for a sprite
        $sprite_row = 1;
        $sprite_column = 1;

        // Push all sprites are pushed from the middle column
        $sprite_column += 1;

        // Further adjust the row and column based on the sprite's position (active/bench) and position key
        //error_log('|| $sprite key ('.$sprite_key.') && position ('.$sprite_position.') ('.$sprite_row.', '.$sprite_column.') w/ team-size ('.$team_size.') ');
        if ($sprite_position == 'active') {
            $sprite_row = $grid_row_middle;
            //error_log('--> adjusted (A) '.$sprite_position.' $sprite position ('.$sprite_row.', '.$sprite_column.')');
        } elseif ($sprite_position == 'bench'){
            $sprite_column += 1;
            $sprite_row += ($sprite_key - 1);
            if ($team_size > 1){
                $current_bench_size = $team_size - 1;
                $extra_bench_slots = $current_bench_size < $max_bench_size ? ($max_bench_size - $current_bench_size) : 0;
                $bench_shift_amount = $extra_bench_slots >= 2 ? floor($extra_bench_slots / 2) : 0;
                // adjust position on non-odd numbered teams for centering purposes
                if ($current_bench_size % 2 === 0 && $sprite_key > ($current_bench_size / 2)){
                    //error_log('- shift position on non-odd numbered teams for centering purposes');
                    $bench_shift_amount += 1;
                }
                //error_log('- $current_bench_size = '.$current_bench_size);
                //error_log('- $extra_bench_slots = '.$extra_bench_slots);
                //error_log('- $bench_shift_amount = '.$bench_shift_amount);
                if ($bench_shift_amount){ $sprite_row += $bench_shift_amount; }
                if ($sprite_row > $grid_rows){ $sprite_row = $grid_rows - $sprite_row; }
                //error_log('--> adjusted (B) '.$sprite_position.' $sprite position ('.$sprite_row.', '.$sprite_column.')');
            }
        }
        //error_log('--> $sprite key ('.$sprite_key.') && position ('.$sprite_position.') ('.$sprite_row.', '.$sprite_column.') w/ team-size ('.$team_size.') ');

        /*
        // DEBUG DEBUG DEBUG DEBUG DEBUG DEBUG
        // DEBUG (just to test positions) DEBUG
        $sprite_row = 1; //7;
        $sprite_column = 2; //($sprite_key + 1);
        */

        // Calculate how much the scale should change per row
        $scale_step = round(($scale_max - $scale_min) / ($grid_rows - 1), 2);
        $canvas_scale = ($scale_max - ($sprite_row - 1) * $scale_step);
        //error_log('$scale_step: '.$scale_step);
        //error_log('$canvas_scale: '.$canvas_scale);
        $rel_sprite_size = ($canvas_scale * $sprite_size);
        $rel_base_sprite_size = ($canvas_scale * $base_sprite_size);
        $rel_zoom_sprite_size = ($canvas_scale * $zoom_sprite_size);
        $rel_cell_height = ($canvas_scale * $cell_height);
        $rel_cell_width = ($canvas_scale * $cell_width);

        // Calculate the canvas Y-offset based on the sprite's size first and foremost
        $canvas_offset_y = 0;
        $canvas_offset_y += $grid_offset_bottom; // start at the outer edge
        $canvas_offset_y += ($grid_row_height($sprite_row, $sprite_column) / 2); // push them up half the height of their panel to vertically align
        $canvas_offset_y += $grid_row_heights_total($sprite_row, $sprite_column); // push them up for all the rows beneath theirs

        // Calculate the canvas X-offset based on the sprite's size first and foremost
        $canvas_offset_x = 0; // start at the outer edge
        $canvas_offset_x += $grid_width; // push them all the way to the inner middle
        $canvas_offset_x -= $grid_col_widths_total($sprite_row, $sprite_column); // pull them back for all the columns to their left
        if ($sprite_size > $zoom_sprite_size){
            $temp_size_diff = $sprite_size - $zoom_sprite_size;
            $canvas_offset_x -= ($temp_size_diff / 2);
        }

        // Adjust the X and Y to ensure pixel-ratio compatible values
        $canvas_offset_y = round($canvas_offset_y) % 2 === 0 ? round($canvas_offset_y) : floor($canvas_offset_y);
        $canvas_offset_x = round($canvas_offset_x) % 2 === 0 ? round($canvas_offset_x) : floor($canvas_offset_x);

        // Calculate how much the Z-offset should change per row
        $z_step = (($z_max - $z_min) / ($grid_rows - 1));
        $canvas_offset_z = ceil($z_max - (($sprite_row - 1) * $z_step));

        // Calculate the depth and focus values based on field depth to use as needed
        $canvas_depth = 1 - ($sprite_row * 0.04);

        // Calculate the focus and focus values based on field depth to use as needed
        $canvas_focus = 1;
        if ($sprite_row > $grid_row_middle){ $focus_shift = ($sprite_row - $grid_row_middle); }
        elseif ($sprite_row < $grid_row_middle){ $focus_shift = ($grid_row_middle - $sprite_row); }
        if (isset($focus_shift)){ $canvas_focus -= $focus_shift * 0.05; }

        // Put it all together to define the canvas offset values
        $offset_values = array(
            'canvas_grid_column' => $sprite_column,
            'canvas_grid_row' => $sprite_row,
            'canvas_scale' => 1, //$canvas_scale,
            'canvas_depth' => $canvas_depth,
            'canvas_focus' => $canvas_focus,
            'canvas_offset_x' => $canvas_offset_x,
            'canvas_offset_y' => $canvas_offset_y,
            'canvas_offset_z' => $canvas_offset_z
            );

        // Return the canvas offsets
        //error_log(PHP_EOL.'$sprite_key: '.$sprite_key.PHP_EOL.'$sprite_size:'.$sprite_size.PHP_EOL.'$sprite_position: '.$sprite_position);
        //error_log('canvas_offset_perspective: ' . print_r($offset_values, true));
        return $offset_values;

    }

    // Define a public function for generating event markup
    public function events_markup_generate($eventinfo){

        // Create the frames counter if not exists
        if (!isset($this->counters['event_frames'])){ $this->counters['event_frames'] = 0; }

        // Define defaults for event options
        $options = array();
        $options['event_flag_autoplay'] = isset($eventinfo['event_options']['event_flag_autoplay']) ? $eventinfo['event_options']['event_flag_autoplay'] : true;
        $options['event_flag_victory'] = isset($eventinfo['event_options']['event_flag_victory']) ? $eventinfo['event_options']['event_flag_victory'] : false;
        $options['event_flag_defeat'] = isset($eventinfo['event_options']['event_flag_defeat']) ? $eventinfo['event_options']['event_flag_defeat'] : false;
        $options['console_container_height'] = isset($eventinfo['event_options']['console_container_height']) ? $eventinfo['event_options']['console_container_height'] : 1;
        $options['console_container_classes'] = isset($eventinfo['event_options']['console_container_classes']) ? $eventinfo['event_options']['console_container_classes'] : '';
        $options['console_container_styles'] = isset($eventinfo['event_options']['console_container_styles']) ? $eventinfo['event_options']['console_container_styles'] : '';
        $options['console_header_float'] = isset($eventinfo['event_options']['this_header_float']) ? $eventinfo['event_options']['this_header_float'] : '';
        $options['console_body_float'] = isset($eventinfo['event_options']['this_body_float']) ? $eventinfo['event_options']['this_body_float'] : '';
        $options['console_show_this'] = isset($eventinfo['event_options']['console_show_this']) ? $eventinfo['event_options']['console_show_this'] : true;
        $options['console_show_this_player'] = isset($eventinfo['event_options']['console_show_this_player']) ? $eventinfo['event_options']['console_show_this_player'] : false;
        $options['console_show_this_robot'] = isset($eventinfo['event_options']['console_show_this_robot']) ? $eventinfo['event_options']['console_show_this_robot'] : true;
        $options['console_show_this_ability'] = isset($eventinfo['event_options']['console_show_this_ability']) ? $eventinfo['event_options']['console_show_this_ability'] : false;
        $options['console_show_this_item'] = isset($eventinfo['event_options']['console_show_this_item']) ? $eventinfo['event_options']['console_show_this_item'] : false;
        $options['console_show_this_skill'] = isset($eventinfo['event_options']['console_show_this_skill']) ? $eventinfo['event_options']['console_show_this_skill'] : false;
        $options['console_show_this_star'] = isset($eventinfo['event_options']['console_show_this_star']) ? $eventinfo['event_options']['console_show_this_star'] : false;
        $options['console_show_target'] = isset($eventinfo['event_options']['console_show_target']) ? $eventinfo['event_options']['console_show_target'] : true;
        $options['console_show_target_player'] = isset($eventinfo['event_options']['console_show_target_player']) ? $eventinfo['event_options']['console_show_target_player'] : true;
        $options['console_show_target_robot'] = isset($eventinfo['event_options']['console_show_target_robot']) ? $eventinfo['event_options']['console_show_target_robot'] : true;
        $options['console_show_target_ability'] = isset($eventinfo['event_options']['console_show_target_ability']) ? $eventinfo['event_options']['console_show_target_ability'] : true;
        $options['canvas_show_this'] = isset($eventinfo['event_options']['canvas_show_this']) ? $eventinfo['event_options']['canvas_show_this'] : true;
        $options['canvas_show_this_robots'] = isset($eventinfo['event_options']['canvas_show_this_robots']) ? $eventinfo['event_options']['canvas_show_this_robots'] : true;
        $options['canvas_show_this_ability'] = isset($eventinfo['event_options']['canvas_show_this_ability']) ? $eventinfo['event_options']['canvas_show_this_ability'] : true;
        $options['canvas_show_this_ability_overlay'] = isset($eventinfo['event_options']['canvas_show_this_ability_overlay']) ? $eventinfo['event_options']['canvas_show_this_ability_overlay'] : false;
        $options['canvas_show_this_item'] = isset($eventinfo['event_options']['canvas_show_this_item']) ? $eventinfo['event_options']['canvas_show_this_item'] : true;
        $options['canvas_show_this_item_overlay'] = isset($eventinfo['event_options']['canvas_show_this_item_overlay']) ? $eventinfo['event_options']['canvas_show_this_item_overlay'] : false;
        $options['canvas_show_this_item_underlay'] = isset($eventinfo['event_options']['canvas_show_this_item_underlay']) ? $eventinfo['event_options']['canvas_show_this_item_underlay'] : true;
        $options['canvas_show_this_skill'] = isset($eventinfo['event_options']['canvas_show_this_skill']) ? $eventinfo['event_options']['canvas_show_this_skill'] : true;
        $options['canvas_show_this_skill_overlay'] = isset($eventinfo['event_options']['canvas_show_this_skill_overlay']) ? $eventinfo['event_options']['canvas_show_this_skill_overlay'] : false;
        $options['canvas_show_this_skill_underlay'] = isset($eventinfo['event_options']['canvas_show_this_skill_underlay']) ? $eventinfo['event_options']['canvas_show_this_skill_underlay'] : true;
        $options['canvas_show_target'] = isset($eventinfo['event_options']['canvas_show_target']) ? $eventinfo['event_options']['canvas_show_target'] : true;
        $options['canvas_show_target_robots'] = isset($eventinfo['event_options']['canvas_show_target_robots']) ? $eventinfo['event_options']['canvas_show_target_robots'] : true;
        $options['canvas_show_target_ability'] = isset($eventinfo['event_options']['canvas_show_target_ability']) ? $eventinfo['event_options']['canvas_show_target_ability'] : true;
        $options['canvas_show_target_item'] = isset($eventinfo['event_options']['canvas_show_target_item']) ? $eventinfo['event_options']['canvas_show_target_item'] : true;
        $options['canvas_show_target_skill'] = isset($eventinfo['event_options']['canvas_show_target_skill']) ? $eventinfo['event_options']['canvas_show_target_skill'] : true;
        $options['this_ability'] = isset($eventinfo['event_options']['this_ability']) ? $eventinfo['event_options']['this_ability'] : false;
        $options['this_ability_target'] = isset($eventinfo['event_options']['this_ability_target']) ? $eventinfo['event_options']['this_ability_target'] : false;
        $options['this_ability_target_key'] = isset($eventinfo['event_options']['this_ability_target_key']) ? $eventinfo['event_options']['this_ability_target_key'] : 0;
        $options['this_ability_target_position'] = isset($eventinfo['event_options']['this_ability_target_position']) ? $eventinfo['event_options']['this_ability_target_position'] : 'active';
        $options['this_ability_results'] = isset($eventinfo['event_options']['this_ability_results']) ? $eventinfo['event_options']['this_ability_results'] : false;
        $options['this_item'] = isset($eventinfo['event_options']['this_item']) ? $eventinfo['event_options']['this_item'] : false;
        $options['this_item_quantity'] = isset($eventinfo['event_options']['this_item_quantity']) ? $eventinfo['event_options']['this_item_quantity'] : 0;
        $options['this_item_target'] = isset($eventinfo['event_options']['this_item_target']) ? $eventinfo['event_options']['this_item_target'] : false;
        $options['this_item_target_key'] = isset($eventinfo['event_options']['this_item_target_key']) ? $eventinfo['event_options']['this_item_target_key'] : 0;
        $options['this_item_target_position'] = isset($eventinfo['event_options']['this_item_target_position']) ? $eventinfo['event_options']['this_item_target_position'] : 'active';
        $options['this_item_results'] = isset($eventinfo['event_options']['this_item_results']) ? $eventinfo['event_options']['this_item_results'] : false;
        $options['this_skill'] = isset($eventinfo['event_options']['this_skill']) ? $eventinfo['event_options']['this_skill'] : false;
        $options['this_skill_quantity'] = isset($eventinfo['event_options']['this_skill_quantity']) ? $eventinfo['event_options']['this_skill_quantity'] : 0;
        $options['this_skill_target'] = isset($eventinfo['event_options']['this_skill_target']) ? $eventinfo['event_options']['this_skill_target'] : false;
        $options['this_skill_target_key'] = isset($eventinfo['event_options']['this_skill_target_key']) ? $eventinfo['event_options']['this_skill_target_key'] : 0;
        $options['this_skill_target_position'] = isset($eventinfo['event_options']['this_skill_target_position']) ? $eventinfo['event_options']['this_skill_target_position'] : 'active';
        $options['this_skill_results'] = isset($eventinfo['event_options']['this_skill_results']) ? $eventinfo['event_options']['this_skill_results'] : false;
        $options['this_star'] = isset($eventinfo['event_options']['this_star']) ? $eventinfo['event_options']['this_star'] : false;
        $options['this_player_image'] = isset($eventinfo['event_options']['this_player_image']) ? $eventinfo['event_options']['this_player_image'] : 'sprite';
        $options['this_robot_image'] = isset($eventinfo['event_options']['this_robot_image']) ? $eventinfo['event_options']['this_robot_image'] : 'sprite';
        $options['this_ability_image'] = isset($eventinfo['event_options']['this_ability_image']) ? $eventinfo['event_options']['this_ability_image'] : 'sprite';
        $options['this_item_image'] = isset($eventinfo['event_options']['this_item_image']) ? $eventinfo['event_options']['this_item_image'] : 'sprite';
        $options['this_skill_image'] = isset($eventinfo['event_options']['this_skill_image']) ? $eventinfo['event_options']['this_skill_image'] : 'sprite';
        $options['event_flag_is_special'] = isset($eventinfo['event_options']['event_flag_is_special']) ? $eventinfo['event_options']['event_flag_is_special'] : false;
        $options['event_flag_camera_action'] = isset($eventinfo['event_options']['event_flag_camera_action']) ? $eventinfo['event_options']['event_flag_camera_action'] : false;
        $options['event_flag_camera_reaction'] = isset($eventinfo['event_options']['event_flag_camera_reaction']) ? $eventinfo['event_options']['event_flag_camera_reaction'] : false;
        $options['event_flag_camera_side'] = isset($eventinfo['event_options']['event_flag_camera_side']) ? $eventinfo['event_options']['event_flag_camera_side'] : 'left';
        $options['event_flag_camera_focus'] = isset($eventinfo['event_options']['event_flag_camera_focus']) ? $eventinfo['event_options']['event_flag_camera_focus'] : 'active';
        $options['event_flag_camera_depth'] = isset($eventinfo['event_options']['event_flag_camera_depth']) ? $eventinfo['event_options']['event_flag_camera_depth'] : 0;
        $options['event_flag_camera_offset'] = isset($eventinfo['event_options']['event_flag_camera_offset']) ? $eventinfo['event_options']['event_flag_camera_offset'] : 0;
        $options['event_flag_sound_effects'] = isset($eventinfo['event_options']['event_flag_sound_effects']) ? $eventinfo['event_options']['event_flag_sound_effects'] : false;

        // If it doesn't exist or a camera reset was provided, we should reset the array to empty
        if (!isset($this->values['last_camera_action'])
            || !empty($eventinfo['event_options']['event_flag_camera_reset'])){
            //error_log('creating last_camera_action ('.(!isset($this->values['last_camera_action']) ? 'not exists' : 'reset requested').')');
            $this->values['last_camera_action'] = array();
        }

        // Check to see if any camera action settings were provided in the event info
        $camera_action_provided = false;
        if (isset($eventinfo['event_options']['event_flag_camera_action'])
            || isset($eventinfo['event_options']['event_flag_camera_reaction'])
            || isset($eventinfo['event_options']['event_flag_camera_side'])
            || isset($eventinfo['event_options']['event_flag_camera_focus'])
            || isset($eventinfo['event_options']['event_flag_camera_depth'])
            || isset($eventinfo['event_options']['event_flag_camera_offset'])
            || isset($eventinfo['event_options']['event_flag_camera_reset'])
            ){
            $camera_action_provided = true;
        }

        // Define the variable to collect markup
        $this_markup = array();

        // Generate the event flags markup
        $event_flags = array();

        $event_flags['autoplay'] = $options['event_flag_autoplay'];

        // Collect the victory and defeat event flags
        $event_flags['victory'] = $options['event_flag_victory'];
        $event_flags['defeat'] = $options['event_flag_defeat'];

        // Collect the camera action flags if provided in the event options
        $event_flags['camera'] = array();
        $event_flags['camera']['action'] = $options['event_flag_camera_action'];
        $event_flags['camera']['reaction'] = $options['event_flag_camera_reaction'];
        $event_flags['camera']['side'] = $options['event_flag_camera_side'];
        $event_flags['camera']['focus'] = $options['event_flag_camera_focus'];
        $event_flags['camera']['depth'] = $options['event_flag_camera_depth'];
        $event_flags['camera']['offset'] = $options['event_flag_camera_offset'];
        if (!$event_flags['camera']['action'] && !$event_flags['camera']['reaction']){ $event_flags['camera'] = false; }

        // Otherwise, save this camera action for next time, if necessary
        if (!empty($event_flags['camera'])){
            $this->values['last_camera_action'] = $event_flags['camera'];
        }

        // If no camera action was provided, and we have backup settings, let's use those
        if (!$camera_action_provided
            && !empty($this->values['last_camera_action'])){
            //error_log('applying saved camera action');
            $event_flags['camera'] = $this->values['last_camera_action'];
            $event_flags['camera']['offset'] = 0;
            $this->values['last_camera_action'] = array();
        }

        // Collect the camera action flags if they've been queued up in the sound queue
        $event_flags['sounds'] = array();
        if (!empty($this->queue['sound_effects'])){
            do {
                $effect = array_shift($this->queue['sound_effects']);
                if (empty($effect['name'])){ continue; }
                $event_flags['sounds'][] = $effect;
            } while (count($this->queue['sound_effects']) > 0);
        }
        if (!empty($options['event_flag_sound_effects'])){
            foreach ($options['event_flag_sound_effects'] AS $key => $effect){
                if (empty($effect['name'])){ continue; }
                $event_flags['sounds'][] = $effect;
            }
        }
        if (empty($event_flags['sounds'])
            && !empty($eventinfo['event_header'])
            && !empty($eventinfo['event_body'])
            ){
            $event_flags['sounds'][] = array('name' => 'text-sound', 'volume' => 1.0);
        }
        if (empty($event_flags['sounds'])){ $event_flags['sounds'] = false; }

        // Compress all these flags into JSON so we can send them to the client
        $this_markup['flags'] = json_encode($event_flags);

        // Generate the console message markup
        $this_markup['console'] = $this->console_markup($eventinfo, $options);

        // Generate the canvas scene markup
        $this_markup['canvas'] = $this->canvas_markup($eventinfo, $options);

        // Generate the jSON encoded event data markup
        $this_markup['data'] = array();
        $this_markup['data']['this_battle'] = '';
        $this_markup['data']['this_field'] = '';
        $this_markup['data']['this_player'] = '';
        $this_markup['data']['this_robot'] = '';
        $this_markup['data']['target_player'] = '';
        $this_markup['data']['target_robot'] = '';
        $this_markup['data'] = json_encode($this_markup['data']);

        // Increment this battle's frames counter
        $this->counters['event_frames'] += 1;
        $this->update_session();

        // Return the generated event markup
        return $this_markup;

    }

    // Define a public function for collecting event markup
    public function events_markup_collect(){

        // Return the events markup array
        return $this->events;

    }

    // Define an event for queueing sound effects to be played at the next canvas and/or console event
    public function queue_sound_effect($effect_config){
        //error_log('rpg_battle::queue_sound_effect($effect_config:' . print_r($effect_config, true) . ')');
        if (empty($effect_config)){ return false; }

        // If no sound name is provided return false
        if (is_string($effect_config)){ $effect_config = array('name' => $effect_config); }
        else if (empty($effect_config['name'])){ return false; }

        // Otherwise simply sanitize the results a bit (volume: 0 - 1, rate: 0.1 - 4.0)
        if (isset($effect_config['volume'])){
            if ($effect_config['volume'] > 1){ $effect_config['volume'] = 1; }
            elseif ($effect_config['volume'] < 0){ $effect_config['volume'] = 0; }
        }
        if (isset($effect_config['rate'])){
            if ($effect_config['rate'] > 4){ $effect_config['rate'] = 4; }
            elseif ($effect_config['rate'] < 0.1){ $effect_config['rate'] = 0.1; }
        }

        // Add the sound to the event sounds queue
        if (!isset($this->queue['sound_effects'])){ $this->queue['sound_effects'] = array(); }
        $this->queue['sound_effects'][] = $effect_config;

        // Return true
        return true;

    }


    // Define a function for calculating the amount of BATTLE POINTS a player gets in battle
    public function calculate_battle_zenny($this_player, $base_zenny = 0, $base_turns = 0){

        // Calculate the number of turn zenny for this player using the base amounts
        $this_base_zenny = $base_zenny;
        if ($this->counters['battle_turn'] < $base_turns
            || $this->counters['battle_turn'] > $base_turns){
            //$this_half_zenny = $base_zenny * 0.10;
            //$this_turn_zenny = ceil($this_half_zenny * ($base_turns / $this->counters['battle_turn']));
            $this_base_zenny = ceil($this_base_zenny * ($base_turns / $this->counters['battle_turn']));
        }

        //$this_battle_zenny = $this_base_zenny + $this_turn_zenny + $this_stat_zenny;
        $this_battle_zenny = $this_base_zenny;

        // Prevent players from loosing zenny
        if ($this_battle_zenny == 0){ $this_battle_zenny = 1; }
        elseif ($this_battle_zenny < 0){ $this_battle_zenny = -1 * $this_battle_zenny; }


        // Return the calculated battle zenny
        return $this_battle_zenny;

    }

    // Define a function for returning a weighted random chance
    public function weighted_chance($values, $weights = array(), $debug = ''){

        /*
        $debug2 = array();
        foreach ($values AS $k => $v){ $debug2[$v] = $weights[$k]; }
        $this->events_create(false, false, 'DEBUG', trim(preg_replace('/\s+/', ' ', (
            (!empty($debug) ? '$debug:'.$debug.'<br />' : '').
            '$values/weights:'.nl2br(print_r($debug2, true)).'<br />'.
            ''
            ))));
        */

        // Count the number of values passed
        $value_amount = count($values);

        // If no weights have been defined, auto-generate
        if (empty($weights)){
            $weights = array();
            for ($i = 0; $i < $value_amount; $i++){
                $weights[] = 1;
            }
        }

        // Calculate the sum of all weights
        $weight_sum = array_sum($weights);

        // Define the two counter variables
        $value_counter = 0;
        $weight_counter = 0;

        // Randomly generate a number from zero to the sum of weights
        $random_number = mt_rand(0, array_sum($weights));
        while($value_counter < $value_amount){
            $weight_counter += $weights[$value_counter];
            if ($weight_counter >= $random_number){ break; }
            $value_counter++;
        }

        //$debug = array('$values' => $values, '$weights' => $weights);
        //$this->events_create(false, false, 'DEBUG', '<pre>'.preg_replace('#\s+#', ' ', print_r($debug, true)).'</pre>');

        // Return the random element
        return $values[$value_counter];

    }

    // Define a function for returning a critical chance
    public function critical_chance($chance_percent = 10){

        // Invert if negative for some reason
        if ($chance_percent < 0){ $chance_percent = -1 * $chance_percent; }
        // Round up to a whole number
        $chance_percent = ceil($chance_percent);
        // If zero, automatically return false
        if ($chance_percent == 0){ return false; }
        // Return true of false at random
        $random_int = mt_rand(1, 100);
        return ($random_int <= $chance_percent) ? true : false;

    }

    // Define a function for finding a target player based on field side
    public function find_target_player($side_or_id){

        // If a string argument was provided in left/right, search that way
        if (is_string($side_or_id) && in_array($side_or_id, array('left', 'right'))){ $target_side = $side_or_id; }
        elseif (is_numeric($side_or_id) && !empty($side_or_id)){ $target_id = $side_or_id; }
        else { return false; }

        // Define the target player variable to start
        $target_player = false;

        // If this search is based on player side, loop and filter
        if (isset($target_side)){

            // Ensure the player array is not empty
            if (!empty($this->values['players'])){
                // Loop through the battle's player characters one by one
                foreach ($this->values['players'] AS $player_id => $player_info){
                    // If the player matches the request side, return the player
                    if ($player_info['player_side'] == $target_side){
                        $target_player = rpg_game::get_player($this, $player_info);
                    }
                }
            }

        }
        // Otherwise if we're searching for a player based on ID
        elseif (isset($target_id)){

            // If the ID was empty, return false
            if (empty($target_id)){ return false; }
            // If the player does not exists in the battle, return false
            elseif (!isset($this->values['players'][$target_id])){ return false; }
            // Otherwise collect the player info from the battle
            $player_info = $this->values['players'][$target_id];
            // Create the robot object and return to caller
            $target_player = rpg_game::get_player($this, $player_info);
        }

        // Return the final value of the target player
        return $target_player;
    }

    // Define a function for finding a target robot based on field side
    public function find_target_robot($side_or_id_or_player){

        // If a string argument was provided in left/right, search that way
        if (is_string($side_or_id_or_player) && in_array($side_or_id_or_player, array('left', 'right'))){ $target_side = $side_or_id_or_player; }
        elseif (is_numeric($side_or_id_or_player) && !empty($side_or_id_or_player)){ $target_id = $side_or_id_or_player; }
        elseif (is_object($side_or_id_or_player) && !empty($side_or_id_or_player)){
            $target_player = $side_or_id_or_player;
            $target_side = $target_player->player_side;
            $target_id = $target_player->player_id;
        } else {
            return false;
        }

        // Define the target robot variable to start
        $target_robot = false;

        // If this search is was provided a player object already
        if (isset($target_player)){

            // Ensure the robot array is not empty
            if (!empty($target_player) && !empty($this->values['robots'])){
                // Loop through the battle's robot characters one by one
                foreach ($this->values['robots'] AS $robot_id => $robot_info){
                    // If the robot matches the request side, return the robot
                    if ($robot_info['player_id'] == $target_player->player_id && $robot_info['robot_position'] == 'active'){
                        return rpg_game::get_robot($this, $target_player, $robot_info);
                    }
                }
            }

        }
        // Else if this search is based on robot side, loop and filter
        elseif (isset($target_side)){

            // Collect the target player object if not provided by the function
            if (empty($target_player)){ $target_player = $this->find_target_player($target_side); }
            // Ensure the robot array is not empty
            if (!empty($target_player) && !empty($this->values['robots'])){
                // Loop through the battle's robot characters one by one
                foreach ($this->values['robots'] AS $robot_id => $robot_info){
                    // If the robot matches the request side, return the robot
                    if ($robot_info['player_id'] == $target_player->player_id && $robot_info['robot_position'] == 'active'){
                        return rpg_game::get_robot($this, $target_player, $robot_info);
                    }
                }
            }

        }
        // Otherwise if we're searching for a robot based on ID
        elseif (isset($target_id)){

            // If the ID was empty, return false
            if (empty($target_id)){ return false; }
            // If the robot does not exists in the battle, return false
            elseif (!isset($this->values['robots'][$target_id])){ return false; }
            // Otherwise collect the robot info from the battle
            $robot_info = $this->values['robots'][$target_id];
            // Collect the target player object if not provided by the function
            if (empty($target_player)){ $target_player = $this->find_target_player($robot_info['player_id']); }
            // Create the robot object and return to caller
            if (!empty($target_player)){ return rpg_game::get_robot($this, $target_player, $robot_info); }

        }

        // Return false if nothing was found
        return false;
    }

    // Define a function for checking to see if there are any robots in a specific skill group on the field
    public function check_for_skill_group_robots($group_token){
        //error_log('check_for_skill_group_robots('.$group_token.')');
        $skill_group_robots = array();
        if (empty($group_token)){ return $skill_group_robots; }
        if (substr($group_token, -7) !== '_robots'){ $group_token .= '_robots'; }
        //error_log('checking players for '.$group_token);
        $player_sides = array('left', 'right');
        foreach ($player_sides AS $player_side){
            $player_object = $this->find_target_player($player_side);
            if (isset($player_object)){
                $raw_skill_group_robots = $player_object->get_value($group_token);
                if (!empty($raw_skill_group_robots)){
                    //error_log($group_token.' found for this player!');
                    //error_log('$raw_skill_group_robots = '.print_r($raw_skill_group_robots, true));
                    foreach ($raw_skill_group_robots AS $key => $id){ $skill_group_robots[$id] = $player_side; }
                }
            }
        }
        //error_log('$skill_group_robots = '.print_r($skill_group_robots, true));
        return $skill_group_robots;
    }

    // Define a function for checking if inventory access is allowed in this battle
    public function allow_inventory_access(){
        if (!empty($this->flags['player_battle'])){ return false; }
        elseif (!empty($this->flags['challenge_battle'])){ return false; }
        return true;
    }

    // Define a function for generating star console variables
    public function star_console_markup($options, $player_data, $robot_data){

        // Define the variable to hold the console star data
        $this_data = array();

        // Collect the star image info from the index based on type
        $temp_star_kind = $options['star_kind'];
        $temp_field_type_1 = !empty($options['star_type']) ? $options['star_type'] : 'none';
        $temp_field_type_2 = !empty($options['star_type2']) ? $options['star_type2'] : $temp_field_type_1;
        if ($temp_star_kind == 'field'){
            $temp_star_front = array('path' => 'images/items/field-star_'.$temp_field_type_1.'/sprite_left_40x40.png?'.MMRPG_CONFIG_CACHE_DATE, 'frame' => '02', 'size' => 40);
            $temp_star_back = array('path' => 'images/items/field-star_'.$temp_field_type_2.'/sprite_left_40x40.png?'.MMRPG_CONFIG_CACHE_DATE, 'frame' => '01', 'size' => 40);
        } elseif ($temp_star_kind == 'fusion'){
            $temp_star_front = array('path' => 'images/items/fusion-star_'.$temp_field_type_1.'/sprite_left_40x40.png?'.MMRPG_CONFIG_CACHE_DATE, 'frame' => '02', 'size' => 40);
            $temp_star_back = array('path' => 'images/items/fusion-star_'.$temp_field_type_2.'/sprite_left_40x40.png?'.MMRPG_CONFIG_CACHE_DATE, 'frame' => '01', 'size' => 40);
        }

        // Define and calculate the simpler markup and positioning variables for this star
        $this_data['star_name'] = isset($options['star_name']) ? $options['star_name'] : 'Battle Star';
        $this_data['star_title'] = $this_data['star_name'];
        $this_data['star_token'] = $options['star_token'];
        $this_data['container_class'] = 'this_sprite sprite_left';
        $this_data['container_style'] = '';

        // Define the back star's markup
        $this_data['star_markup_class'] = 'sprite sprite_star sprite_star_sprite sprite_40x40 sprite_40x40_'.$temp_star_back['frame'].' ';
        $this_data['star_markup_style'] = 'background-image: url('.$temp_star_back['path'].'); margin-top: 5px; ';
        $temp_back_markup = '<div class="'.$this_data['star_markup_class'].'" style="'.$this_data['star_markup_style'].'" data-click-tooltip="'.$this_data['star_title'].'">'.$this_data['star_title'].'</div>';

        // Define the back star's markup
        $this_data['star_markup_class'] = 'sprite sprite_star sprite_star_sprite sprite_40x40 sprite_40x40_'.$temp_star_front['frame'].' ';
        $this_data['star_markup_style'] = 'background-image: url('.$temp_star_front['path'].'); margin-top: -42px; ';
        $temp_front_markup = '<div class="'.$this_data['star_markup_class'].'" style="'.$this_data['star_markup_style'].'" data-click-tooltip="'.$this_data['star_title'].'">'.$this_data['star_title'].'</div>';

        // Generate the final markup for the console star
        $this_data['star_markup'] = '';
        $this_data['star_markup'] .= '<div class="'.$this_data['container_class'].'" style="'.$this_data['container_style'].'">';
        $this_data['star_markup'] .= $temp_back_markup;
        $this_data['star_markup'] .= $temp_front_markup;
        $this_data['star_markup'] .= '</div>';

        // Return the star console data
        return $this_data;

    }

    // Define a public function for recalculating internal counters
    public function update_variables(){

        // Calculate this battle's count variables
        $perside_max = 0;
        if (!empty($this->values['players'])){
            foreach ($this->values['players'] AS $id => $player){
                $max = $player['counters']['robots_total'];
                if ($max > $perside_max){ $perside_max = $max; }
            }
        }
        $this->counters['robots_perside_max'] = $perside_max;

        // Define whether we're allowed to use experience or not
        $this->flags['allow_experience_points'] = true;
        if (!empty($this->flags['player_battle'])
            || !empty($this->flags['challenge_battle'])){
            $this->flags['allow_experience_points'] = false;
        }

        // Return true on success
        return true;

    }

    // Define a public function for updating this player's session
    public function update_session(){

        // Update any internal counters
        $this->update_variables();

        // Update the session with the export array
        $this_data = $this->export_array();
        $_SESSION['BATTLES'][$this->battle_id] = $this_data;

        // Return true on success
        return true;

    }

    // Define a function for exporting the current data
    public function export_array(){

        // Return all internal ability fields in array format
        return array(
            'battle_id' => $this->battle_id,
            'battle_name' => $this->battle_name,
            'battle_token' => $this->battle_token,
            'battle_description' => $this->battle_description,
            'battle_turns' => $this->battle_turns,
            'battle_rewards' => $this->battle_rewards,
            'battle_zenny' => $this->battle_zenny,
            'battle_level' => $this->battle_level,
            'battle_attachments' => $this->battle_attachments,
            'battle_base_name' => $this->battle_base_name,
            'battle_base_token' => $this->battle_base_token,
            'battle_base_description' => $this->battle_base_description,
            'battle_base_turns' => $this->battle_base_turns,
            'battle_base_rewards' => $this->battle_base_rewards,
            'battle_base_zenny' => $this->battle_base_zenny,
            'battle_base_level' => $this->battle_base_level,
            'battle_base_attachments' => $this->battle_base_attachments,
            'battle_counts' => $this->battle_counts,
            'battle_status' => $this->battle_status,
            'battle_result' => $this->battle_result,
            'battle_robot_limit' => $this->battle_robot_limit,
            'battle_field_base' => $this->battle_field_base,
            'battle_target_player' => $this->battle_target_player,
            'battle_complete_redirect_token' => $this->battle_complete_redirect_token,
            'battle_complete_redirect_seed' => $this->battle_complete_redirect_seed,
            'flags' => $this->flags,
            'counters' => $this->counters,
            'values' => $this->values,
            'history' => $this->history
            );

    }

}
?>