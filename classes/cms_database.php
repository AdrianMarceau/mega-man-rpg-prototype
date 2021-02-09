<?php
// Define the cms_database() class
class cms_database {

    // Define the private variables
    private $LINK = false;
    private $CACHE = array();
    private $TABLES = array();
    // Define the public variables
    public $CONNECT = true;
    public $HOST;
    public $USERNAME;
    public $PASSWORD;
    public $CHARSET;
    public $DBNAME;
    public $MYSQL_RESULT;
    public $INDEX;
    public $DEBUG;
    // DEfine the public settings
    public $log_queries = false;


    /*
     * CONSTRUCTOR
     */

    // Define the constructor for the class
    public function __construct(){
        // Collect the initializer arguments
        if (php_sapi_name() === 'cli'){ $this->HOST = MMRPG_CONFIG_IS_LIVE ? MMRPG_CONFIG_DBHOST : '127.0.0.1'; }
        else { $this->HOST = MMRPG_CONFIG_DBHOST; }
        //$this->HOST = MMRPG_CONFIG_IS_LIVE ? MMRPG_CONFIG_DBHOST : '127.0.0.1';
        //$this->HOST = MMRPG_CONFIG_DBHOST;
        $this->USERNAME = MMRPG_CONFIG_DBUSERNAME;
        $this->PASSWORD = MMRPG_CONFIG_DBPASSWORD;
        $this->CHARSET = MMRPG_CONFIG_DBCHARSET;
        $this->DBNAME = MMRPG_CONFIG_DBNAME;
        // First initialize the database connection
        $this->CONNECT = $this->db_connect();
        if ($this->CONNECT === false){ $this->CONNECT = false; return $this->CONNECT; }
        // Now select the currently active database
        //$this->CONNECT = $this->db_select();
        //if ($this->CONNECT === false){ $this->CONNECT = false; return $this->CONNECT; }
        // Set the names and character set
        //$this->CONNECT = $this->query("SET NAMES {$this->CHARSET};");
        $this->CONNECT = $this->LINK->set_charset($this->CHARSET);
        if ($this->CONNECT === false){ $this->CONNECT = false; return $this->CONNECT; }
        // Clear any links or whatever this function does not
        $this->CONNECT = $this->clear();
        if ($this->CONNECT === false){ $this->CONNECT = false; return $this->CONNECT; }
    }

    // Define the constructor for the class
    public function __destruct(){
        // Empty the database cache
        $this->CACHE = array();
        // Clear any current links
        $this->clear();
        // Close the database connection
        $this->db_close();
    }

    /**
     * Request a reference to the current database object
     * @return cms_database
     */
    public static function get_database(){
        if (isset($GLOBALS['DB'])){ $db = $GLOBALS['DB'];  }
        elseif (isset($GLOBALS['db'])){ $db = $GLOBALS['db'];  }
        else { $db = false; }
        if (empty($db)){ $db = new cms_database(); }
        return $db;
    }

    /*
     * CONNECT / DISCONNECT FUNCTIONS
     */

    // Define the error handler for when the database goes bye bye
    private function critical_error($message){
        error_log('['.date('Y-m-d @ H:i:s').'] ('.$_SERVER['REMOTE_ADDR'].') - '.PHP_EOL.strip_tags(nl2br(htmlspecialchars_decode($message))).PHP_EOL);
        if (!MMRPG_CONFIG_IS_LIVE){
            if (php_sapi_name() === 'cli'){ echo(date('Y-m-d @ H:i:s').' ('.$_SERVER['REMOTE_ADDR'].') - '.strip_tags(nl2br(htmlspecialchars_decode($message))).PHP_EOL); }
            else { echo('<pre style="display: block; clear: both; float: none; background-color: #f2f2f2; color: #292929; text-shadow: 0 0 0 transparent; white-space: normal; padding: 10px; text-align: left;">'.$message.'</pre>'); }
        }
        return false;
    }

    // Define the private function for initializing the database connection
    private function db_connect(){
        // Clear any leftover data
        $this->clear();
        // Attempt to open the connection to the MySQL database
        if (!isset($this->LINK) || $this->LINK === false){
            $this->LINK = new mysqli($this->HOST, $this->USERNAME, $this->PASSWORD, $this->DBNAME);
        }
        // If the connection was not successful, return false
        if ($this->LINK === false
            || $this->LINK->connect_errno){
            if (MMRPG_CONFIG_IS_LIVE && (!defined('MMRPG_CONFIG_ADMIN_MODE') || MMRPG_CONFIG_ADMIN_MODE !== true)){
                $this->critical_error("<strong>cms_database::db_connect</strong> : Critical error! \n".
                    "Unable to connect to the database!  \n".
                    "[MySQLi Error {$this->LINK->connect_errno}] : ".
                    "&quot;".htmlentities($this->LINK->connect_error, ENT_QUOTES, 'UTF-8', true)."&quot;"
                    );
            } else {
                $this->critical_error("<strong>cms_database::db_connect</strong> : Critical error!  \n".
                    "Unable to connect to the database (".$this->USERNAME.":******@".$this->HOST.")!  \n".
                    "[MySQLi Error {$this->LINK->connect_errno}] : ".
                    "&quot;".htmlentities($this->LINK->connect_error, ENT_QUOTES, 'UTF-8', true)."&quot;"
                    );
            }
            return false;
        }
        // Automatically refresh the internal list of cached table names
        $this->table_list();
        // Return true
        return true;
    }

    // Define the private function for closing the database connection
    private function db_close(){
        // Return immediately if DB is not available
        if (!$this->CONNECT){ return false; }
        // Close the open connection to the database
        if (isset($this->LINK) && $this->LINK != false){ $close = mysqli_close($this->LINK); }
        else { $close = true; }
        // If the closing was not successful, return false
        if ($close === false){
            $this->critical_error("<strong>cms_database::db_close</strong> : Critical error! Unable to close the database connection for host &lt;{$this->HOST}&gt;!<br />[MySQL Error ".mysqli_errno($this->LINK)."] : &quot;".mysqli_errno($this->LINK)."&quot;");
            return false;
        }
        // Return true
        return true;
    }

    // Define the private function for selecting the database
    private function db_select(){
        // Return immediately if DB is not available
        if (!$this->CONNECT){ return false; }
        // Attempt to select the database by name
        $select = mysqli_select_db($this->LINK, $this->DBNAME);
        // If the select was not successful, return false
        if ($select === false){
            $this->critical_error("<strong>cms_database::db_select</strong> : Critical error! Unable to select the database &lt;{$this->DBNAME}&gt;!<br />[MySQL Error ".mysqli_errno($this->LINK)."] : &quot;".mysqli_errno($this->LINK)."&quot;");
            return false;
        }
        // Return true
        return true;
    }

    /*
     * DATABASE QUERY FUNCTIONS
     */

    // Define the function for querying the database
    public function query($query_string, &$affected_rows = 0){

        // Return immediately if DB is not available
        if (!$this->CONNECT){ return false; }

        // Execute the query against the database
        $this->MYSQL_RESULT = mysqli_query($this->LINK, $query_string);
        if ($this->log_queries){ error_log('QUERY:'.PHP_EOL.$query_string); }

        // If a result was not found, produce an error message and return false
        if ($this->MYSQL_RESULT === false){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $caller2 = array_shift($bt);
            $caller3 = array_shift($bt);
            $caller['file'] = basename(dirname($caller['file'])).'/'.basename($caller['file']);
            $caller2['file'] = basename(dirname($caller2['file'])).'/'.basename($caller2['file']);
            $caller3['file'] = basename(dirname($caller3['file'])).'/'.basename($caller3['file']);
            if (!isset($caller['line'])){ $caller['line'] = 0; }
            if (!isset($caller2['line'])){ $caller2['line'] = 0; }
            if (!isset($caller3['line'])){ $caller3['line'] = 0; }
            $backtrace = "{$caller['file']}:{$caller['line']} <br /> {$caller2['file']}:{$caller2['line']} <br /> {$caller3['file']}:{$caller3['line']}";
            $this->critical_error("[[cms_database::query]] : Unable to run the requested query. <br /> ".PHP_EOL.
                "[Error ".mysqli_errno($this->LINK)." | ".mysqli_error($this->LINK)."]. <br /> ".PHP_EOL.
                "The query was &laquo;".str_replace('&#039;', "'", htmlentities(preg_replace('/\s+/', ' ', $query_string), ENT_QUOTES, 'UTF-8'))."&raquo;. <br /> ".PHP_EOL.
                "{$backtrace}"
                );
            return false;
        }

        // Populate the affected rows, if any
        $affected_rows = mysqli_affected_rows($this->LINK);

        // Return the results if there are any
        if (!empty($this->MYSQL_RESULT)){

            // If this is an INSERT statement, return the new ID of the inserted row
            if (preg_match('/^INSERT INTO /i', $query_string)){
                return mysqli_insert_id($this->LINK);
            }
            // If this is an INSERT statement, return the number of affected row
            elseif (preg_match('/^UPDATE /i', $query_string)){
                return mysqli_affected_rows($this->LINK);
            }
            // If this is a SELECT statement, return the number of collected rows
            elseif (preg_match('/^SELECT /i', $query_string)){
                return mysqli_num_rows($this->MYSQL_RESULT);
            }
            // Otherwise return true
            else {
                return $this->MYSQL_RESULT;
            }

        } else {
            return false;
        }

    }

    // Define the function for clearing the results
    public function clear(){
        // Attempt to release the MySQL result
        if (is_resource($this->MYSQL_RESULT)){
            mysqli_free_result($this->LINK, $this->MYSQL_RESULT);
        }
        // Return true
        return true;
    }

    // Define a function for selecting a single row as an array
    public function get_array($query_string){
        // Ensure this is a string
        if (empty($query_string) || !is_string($query_string)) { return false; }
        // Define the md5 of this query string
        $temp_query_hash = 'get_array_'.md5(preg_replace('/\s+/', ' ', $query_string));
        $temp_query_cacheable = preg_match('/mmrpg_index_/i', $query_string) ? true : false;
        // Check if there's a chached copy of this data and decode if so
        if (!empty($this->CACHE[$temp_query_hash])){
            // Collect and decode the results and return that
            $result_array = $this->CACHE[$temp_query_hash]; //json_decode($this->CACHE[$temp_query_hash], true);
            return $result_array;
        }
        // Run the query against the database
        $this->query($query_string);
        // If the result is empty NULL or empty, return false
        if (!$this->MYSQL_RESULT || mysqli_num_rows($this->MYSQL_RESULT) < 1) { return false; }
        // Otherwise, pull an array from the result
        $result_array = mysqli_fetch_array($this->MYSQL_RESULT, MYSQLI_ASSOC);
        // Fix numeric values in result array
        $result_array = $this->fix_numeric_values_in_result_array($result_array);
        // Free the results of the query
        $this->clear();
        // Check to see if this is a cacheable result, and encode if so
        if ($temp_query_cacheable){
            // Serialize and cache the result before we return it
            $this->CACHE[$temp_query_hash] = $result_array; //json_encode($result_array);
        }
        // Now return the resulting array
        return $result_array;
    }
    // Define a function for selecting a single row as an object (converted array)
    public function get_object($query_string){
        // Ensure this is a string
        if (empty($query_string) || !is_string($query_string)) { return false; }
        // Now return the resulting array, casted as an object
        return (object)($this->get_array($query_string));
    }

    // Define a function for selecting a list of rows as arrays
    public function get_array_list($query_string, $index = false, &$record_count = 0){
        // Ensure this is a string
        if (empty($query_string) || !is_string($query_string)) { return false; }
        // Ensure the $index is a string, else set it to false
        if ($index) { $index = is_string($index) ? trim($index) : false; }
        // Define the md5 of this query string
        $temp_query_hash = 'get_array_list_'.md5(preg_replace('/\s+/', ' ', $query_string));
        $temp_query_cacheable = preg_match('/mmrpg_index_/i', $query_string) ? true : false;
        // Check if there's a chached copy of this data and decode if so
        if (!empty($this->CACHE[$temp_query_hash])){
            // Collect and decode the results and return that
            $array_list = $this->CACHE[$temp_query_hash]; //json_decode($this->CACHE[$temp_query_hash], true);
            return $array_list;
        }
        // Run the query against the database
        $this->query($query_string);
        // If the result is empty NULL or empty, return false
        if (!$this->MYSQL_RESULT || mysqli_num_rows($this->MYSQL_RESULT) < 1) { return false; }
        // Create the list array to hold all the rows
        $array_list = array();
        // Now loop through the result rows, pulling associative arrays
        while ($result_array = mysqli_fetch_array($this->MYSQL_RESULT, MYSQLI_ASSOC)){
            // Fix numeric values in result array
            $result_array = $this->fix_numeric_values_in_result_array($result_array);
            // If there was an index defined, assign the array to a specific key in the list
            if ($index) { $array_list[$result_array[$index]] = $result_array; }
            // Otherwise, append the array to the end of the list
            else { $array_list[] = $result_array; }
        }
        // Free the results of the query
        $this->clear();
        // Check to see if this is a cacheable result, and encode if so
        if ($temp_query_cacheable){
            // Encode and cache the result before we return it
            $this->CACHE[$temp_query_hash] = $array_list; //json_encode($array_list);
        }
        // Update the $record_count variable
        $record_count = is_array($array_list) ? count($array_list) : 0;
        // Now return the resulting array
        return $array_list;
    }
    // Define a function for selecting a list of rows as a objects (converted arrays)
    public function get_object_list($query_string, $index = false, &$record_count = 0){
        // Ensure this is a string
        if (empty($query_string) || !is_string($query_string)) { return false; }
        // Ensure the $index is a string, else set it to false
        if ($index) { $index = is_string($index) ? trim($index) : false; }
        // Pull the object list
        $object_list = $this->get_array_list($query_string, $index);
        // Loop through and convert all arrays to objects
        if (is_array($object_list)){
            foreach ($object_list AS $key => $array){
                $object_list[$key] = (object)($array);
            }
        }
        // Update the $record_count variable
        $record_count = is_array($object_list) ? count($object_list) : 0;
        // Now return the resulting object list, casted from arrays
        return $object_list;
    }
    // Define a function for pulling a single value from a database
    public function get_value($query_string, $field_name = 'value'){
        // Ensure this is a string
        if (empty($query_string) || !is_string($query_string)) { return false; }
        // Define the md5 of this query string
        $temp_query_hash = 'get_value_'.md5($query_string);
        $temp_query_cacheable = preg_match('/mmrpg_index_/i', $query_string) ? true : false;
        // Check if there's a chached copy of this data and decode if so
        if (!empty($this->CACHE[$temp_query_hash])){
            // Collect and decode the results and return that
            $result_array = $this->CACHE[$temp_query_hash]; //json_decode($this->CACHE[$temp_query_hash], true);
            return $result_array;
        }
        // Run the query against the database
        $this->query($query_string);
        // If the result is empty NULL or empty, return false
        if (!$this->MYSQL_RESULT || mysqli_num_rows($this->MYSQL_RESULT) < 1) { return false; }
        // Otherwise, pull an array from the result
        $result_array = mysqli_fetch_array($this->MYSQL_RESULT, MYSQLI_ASSOC);
        // Fix numeric values in result array
        $result_array = $this->fix_numeric_values_in_result_array($result_array);
        // Free the results of the query
        $this->clear();
        // Check to see if this is a cacheable result, and encode if so
        if ($temp_query_cacheable){
            // Encode and cache the result before we return it
            $this->CACHE[$temp_query_hash] = $result_array; //json_encode($result_array, true);
        }
        // Now return the resulting array
        return isset($result_array[$field_name]) ? $result_array[$field_name] : false;
    }
    // Define a function for parsing result arrays for numbers and auto-casting them
    public function fix_numeric_values_in_result_array($result_array){
        if (empty($result_array) || !is_array($result_array)){ return $result_array; }
        foreach ($result_array AS $field => $value){
            if (is_numeric($value)){
                if (strstr($value, '.')){ $result_array[$field] = floatval($value); }
                else { $result_array[$field] = intval($value); }
                continue;
            }
        }
        return $result_array;
    }

    // Define a function for inserting a record into the database
    public function insert($table_name, $insert_data){
        // Ensure proper data types have been received
        if (empty($table_name) || !is_string($table_name)) { return false; }
        if (empty($insert_data) || (!is_array($insert_data) && !is_string($insert_data))) { return false; }
        // Create the $insert_fields and $insert_values arrays
        $insert_fields = array();
        $insert_values = array();
        // Initialize in insert string
        $insert_string = '';
        // If the insert_data was an array
        if (is_array($insert_data)){
            // Loop through the $insert_data array and separate the keys/values
            foreach ($insert_data AS $field => $value)
            {
                // Skip fields that aren't named or have empty keys
                if (empty($field) || !is_string($field)) { continue; }
                // Otherwise, add to the insert_field and the insert_value lists
                $insert_fields[] = "`{$field}`";
                $insert_values[] = "'".str_replace("'", "\'", $value)."'";
            }
            // Implode into an the insert strings
            $insert_string = "(".implode(', ', $insert_fields).") VALUES (".implode(', ', $insert_values).")";
        }
        // Else, if the $insert_data is a string
        elseif (is_string($insert_data)){
            // Add this preformatted value to the insert string
            $insert_string = $insert_data;
        }
        // Create the insert query to run against the database
        $table_name_string = self::wrap_table_name($table_name);
        $insert_query = "INSERT INTO {$table_name_string} {$insert_string}";
        // Execute the insert query against the database
        $affected_rows = 0;
        $return = $this->query($insert_query, $affected_rows);
        // If success, return the affected number of rows
        if ($this->MYSQL_RESULT !== false){
            $this->clear();
            return $return;
        } else {
            $this->clear();
            return false;
        }
    }

    // Define a function for generating a bulk insert SQL
    public function get_bulk_insert_sql($table_name, $insert_rows){
        if (empty($table_name)){ return false; }
        if (empty($insert_rows)){ return false; }
        $insert_rows = array_values($insert_rows);
        $bulk_insert_sql = '';
        $bulk_insert_sql .= 'INSERT INTO `'.$table_name.'` (`'.implode('`, `', array_keys($insert_rows[0])).'`) VALUES'.PHP_EOL;
        foreach ($insert_rows AS $row_key => $row_data){
            if ($row_key > 0){ $bulk_insert_sql .= ','.PHP_EOL; }
            $values = array();
            foreach ($row_data AS $fkey => $fval){
                $sanitized_fval = $fval;
                $sanitized_fval = str_replace("'", "\\'", $sanitized_fval);
                $sanitized_fval = str_replace(PHP_EOL, '\\r\\n', $sanitized_fval);
                $values[] = is_numeric($fval) ? $fval : "'".$sanitized_fval."'";
            }
            $bulk_insert_sql .= '  ('.implode(', ', $values).')';
        }
        $bulk_insert_sql .= PHP_EOL.'  ;';
        return $bulk_insert_sql;
    }

    // Define a function for updating a record in the database
    public function update($table_name, $update_data, $condition_data){
        // Ensure proper data types have been received
        if (empty($table_name) || !is_string($table_name)) { return false; }
        if (empty($update_data) || (!is_array($update_data) && !is_string($update_data))) { return false; }
        if (empty($condition_data) || (!is_array($condition_data) && !is_string($condition_data))) { return false; }
        // Initialize the update string
        $update_string = '';
        // If the update_data is an array object
        if (is_array($update_data)){
            // Create the update blocks array
            $update_blocks = array();
            // Loop through the $update_data array and separate the keys/values
            $find = "'"; $replace = "\'";
            foreach ($update_data AS $field => $value){
                // Skip fields that aren't named or have empty keys
                if (empty($field) || !is_string($field)) { continue; }
                // Otherwise, add to the update_blocks list
                $update_blocks[] = "`{$field}` = '".str_replace($find, $replace, $value)."'";
                }
            // Clear the update data to free memory
            unset($update_data, $field, $value);
            // Implode into an update string
            $update_string = implode(', ', $update_blocks);
            unset($update_blocks);
            }
        // Else, if the $update_data is a string
        elseif (is_string($update_data)){
            // Add this preformatted value to the update string
            $update_string = $update_data;
            // Clear the update data to free memory
            unset($update_data);
            }
        // Initialize the condition string
        $condition_string = '';
        // If the condition_data is an array object
        if (is_array($condition_data)){
            // Create the condition blocks array
            $condition_blocks = array();
            // Loop through the $condition_data array and separate the keys/values
            foreach ($condition_data AS $field => $value){
                // Skip fields that aren't named or have empty keys
                if (empty($field) || !is_string($field)) { continue; }
                // Otherwise, add to the condition_blocks list
                $condition_blocks[] = "`{$field}` = '".str_replace("'", "\'", $value)."'";
                }
            // Clear the condition data to free memory
            unset($condition_data, $field, $value);
            // Implode into an condition string
            $condition_string = implode(' AND ', $condition_blocks);
            unset($condition_blocks);
            }
        elseif (is_string($condition_data)){
            // Add this preformatted value to the condition string
            $condition_string = $condition_data;
            // Clear the condition data to free memory
            unset($condition_data);
            }
        // Now put together the update query to run against the database
        $table_name_string = self::wrap_table_name($table_name);
        $update_query = "UPDATE {$table_name_string} SET {$update_string} WHERE {$condition_string}";
        unset($update_string, $condition_string);
        // Execute the update query against the database
        $affected_rows = 0;
        $this->query($update_query, $affected_rows);
        // If success, return the affected number of rows
        if ($this->MYSQL_RESULT !== false){ $this->clear(); return $affected_rows; }
        else { $this->clear(); return false; }
    }

    // Define a function for deleting a record (or records) from the database
    public function delete($table_name, $condition_data){
        // Ensure proper data types have been received
        if (empty($table_name) || !is_string($table_name)) { return false; }
        if (empty($condition_data) || (!is_array($condition_data) && !is_string($condition_data))) { return false; }
        // Initialize the condition string
        $condition_string = '';
        // If the condition_data is an array object
        if (is_array($condition_data)){
            // Create the condition blocks array
            $condition_blocks = array();
            // Loop through the $condition_data array and separate the keys/values
            foreach ($condition_data AS $field => $value){
                // Skip fields that aren't named or have empty keys
                if (empty($field) || !is_string($field)) { continue; }
                // Otherwise, add to the condition_blocks list
                $condition_blocks[] = "`{$field}` = '".str_replace("'", "\'", $value)."'";
            }
            // Implode into an condition string
            $condition_string = implode(' AND ', $condition_blocks);
        }
        elseif (is_string($condition_data)){
            // Add this preformatted value to the condition string
            $condition_string = $condition_data;
        }
        // Now put together the delete query to run against the database
        $table_name_string = self::wrap_table_name($table_name);
        $delete_query = "DELETE FROM {$table_name_string} WHERE {$condition_string}";
        // Execute the delete query against the database
        $affected_rows = 0;
        $this->query($delete_query, $affected_rows);
        // If success, return the affected number of rows
        if ($this->MYSQL_RESULT !== false){ $this->clear(); return $affected_rows; }
        else { $this->clear(); return false; }
    }

    // Define a function for pulling the list of database tables
    public function table_list(){
        // Assuming we're successfull, immediately get a list of valid tables in this database
        $raw_table_names = $this->get_array_list("SELECT table_name FROM information_schema.tables WHERE table_schema = '{$this->DBNAME}';");
        if (!empty($raw_table_names)){ $this->TABLES = array_map(function($a){ return $a['table_name']; }, $raw_table_names); }
        // Return the list of cached table names
        return $this->TABLES;
    }

    // Define a function for pulling a list of columns for a given database table
    public function table_column_list($table_name){
        $raw_column_names = $this->get_array_list("SELECT column_name FROM information_schema.columns WHERE table_schema = '{$this->DBNAME}' AND table_name = '{$table_name}';", 'column_name');
        return !empty($raw_column_names) ? array_keys($raw_column_names) : array();
    }

    // Define a function for checking if a database table exists
    public function table_exists($table_name){
        // Return true if table in cached list
        return in_array($table_name, $this->TABLES);
    }

    // Define a function for collection the maximum field value of a given table
    public function max_value($table_name, $field_name, $condition_data = false){
        // Ensure proper data types have been received
        if (empty($table_name) || !is_string($table_name)) { return false; }
        if (empty($field_name) || !is_string($field_name)) { return false; }
        if ($condition_data != false && (!is_array($condition_data) && !is_string($condition_data))) { return false; }
        // Initialize the condition string
        $condition_string = '';
        // If the condition_data is an array object
        if (is_array($condition_data)){
            // Create the condition blocks array
            $condition_blocks = array();
            // Loop through the $condition_data array and separate the keys/values
            foreach ($condition_data AS $field => $value){
                // Skip fields that aren't named or have empty keys
                if (empty($field) || !is_string($field)) { continue; }
                // Otherwise, add to the condition_blocks list
                $condition_blocks[] = "$field = '".str_replace("'", "\'", $value)."'";
            }
            // Implode into an condition string
            $condition_string = "WHERE ".implode(' AND ', $condition_blocks);
        }
        elseif (is_string($condition_data)){
            // Add this preformatted value to the condition string
            $condition_string = "WHERE ".$condition_data;
        }
        // Pull the max valued array from the database
        $table_name_string = self::wrap_table_name($table_name);
        $max_array = $this->get_array("SELECT MAX({$field_name}) as max_value FROM {$table_name_string} {$condition_string} ORDER BY {$field_name} DESC LIMIT 1");
        // Return the value for the $max_array
        return !empty($max_array['max_value']) ? $max_array['max_value'] : 0;
    }

    // Define a function for collection the minimum field value of a given table
    public function min_value($table_name, $field_name, $condition_data = false){
        // Ensure proper data types have been received
        if (empty($table_name) || !is_string($table_name)) { return false; }
        if (empty($field_name) || !is_string($field_name)) { return false; }
        if ($condition_data != false && (!is_array($condition_data) && !is_string($condition_data))) { return false; }
        // Initialize the condition string
        $condition_string = '';
        // If the condition_data is an array object
        if (is_array($condition_data)){
            // Create the condition blocks array
            $condition_blocks = array();
            // Loop through the $condition_data array and separate the keys/values
            foreach ($condition_data AS $field => $value){
                // Skip fields that aren't named or have empty keys
                if (empty($field) || !is_string($field)) { continue; }
                // Otherwise, add to the condition_blocks list
                $condition_blocks[] = "$field = '".str_replace("'", "\'", $value)."'";
            }
            // Implode into an condition string
            $condition_string = "WHERE ".implode(' AND ', $condition_blocks);
        }
        elseif (is_string($condition_data)){
            // Add this preformatted value to the condition string
            $condition_string = "WHERE ".$condition_data;
        }
        // Pull the min valued array from the database
        $table_name_string = self::wrap_table_name($table_name);
        $min_array = $this->get_array("SELECT MIN({$field_name}) as min_value FROM {$table_name_string} {$condition_string} ORDER BY {$field_name} ASC LIMIT 1");
        // Return the value for the $min_array
        return $min_array['min_value'];
    }

    // Define a public function for resetting the auto increment of a table
    public function reset_table($table_name, $auto_increment = false, $order_by = false){
        // Ensure proper data types have been received
        if (empty($table_name) || !is_string($table_name)) { return false; }
        if (empty($auto_increment) && empty($order_by)) { return false; }
        // Execute the alter table queries against the database and collect the successes or failures
        $table_name_string = self::wrap_table_name($table_name);
        if (is_numeric($auto_increment)){
            $this->query("ALTER TABLE {$table_name_string} AUTO_INCREMENT = {$auto_increment}");
            $success1 = $this->MYSQL_RESULT ? 1 : 0;
            $this->clear();
        }
        if (is_string($order_by)){
            $this->query("ALTER TABLE {$table_name_string} ORDER BY {$order_by}");
            $success2 = $this->MYSQL_RESULT ? 1 : 0;
            $this->clear();
        }
        return ($success1 + $success2);
    }

    // Define a function for safely wrapping a table name string in backquotes for query use
    public static function wrap_table_name($table_name){
        if (strstr($table_name, '`')){ return $table_name; }
        elseif (strstr($table_name, '.')){ return '`'.implode('`.`', explode('.', $table_name)).'`'; }
        else { return '`'.$table_name.'`'; }
    }

    // Define a function for importing an SQL file directory
    public function import_sql_file($path){
        // Return false if file does not exist
        if (!file_exists($path)){
            $this->critical_error("<strong>cms_database::db_import_file</strong> : Request error! Provided file path \"{$path}\" does not exist!");
            return false;
        }
        // Return false ifthe file is not an SQL file
        if (substr($path, -4, 4) !== '.sql'){
            $this->critical_error("<strong>cms_database::db_import_file</strong> : Request error! Provided path \"{$path}\" is not an sql file!");
            return false;
        }
        // Otherwise attempt to import the file via the system level
        shell_exec('mysql --user='.$this->USERNAME.' --password='.$this->PASSWORD.' '.$this->DBNAME.' < '.$path.' 2>&1');
        return;
    }

    // Define a function for getting a table definition for export to an SQL file
    public static function get_create_table_sql($table_name, $table_settings){
        if (!isset($table_settings['export_table']) || $table_settings['export_table'] !== true){ return false; }
        global $db;
        $table_name_string = self::wrap_table_name($table_name);
        $table_def_sql = $db->get_value("SHOW CREATE TABLE {$table_name_string};", 'Create Table');
        $table_def_sql = preg_replace('/(\s+AUTO_INCREMENT)=(?:[0-9]+)(\s+)/', '$1=0$2', $table_def_sql);
        $table_def_sql = preg_replace('/^CREATE TABLE `/', 'CREATE TABLE IF NOT EXISTS `', $table_def_sql);
        $table_def_sql = rtrim($table_def_sql, ';').';';
        $final_table_def_sql = self::$table_def_sql_template;
        $final_table_def_sql = str_replace('{{TABLE_NAME}}', $table_name, $final_table_def_sql);
        $final_table_def_sql = str_replace('{{TABLE_DEF_SQL}}', $table_def_sql, $final_table_def_sql);
        return $final_table_def_sql;
    }

    // Define a function for getting a table definition for export to an SQL file
    public static function get_insert_table_data_sql($table_name, $table_settings){
        if (!isset($table_settings['export_data']) || $table_settings['export_data'] !== true){ return false; }
        global $db;
        $table_name_string = self::wrap_table_name($table_name);
        $select_query = "SELECT * FROM {$table_name_string};";
        if (!empty($table_settings['export_filter'])){
            $row_filter = array('1=1');
            $filters = $table_settings['export_filter'];
            foreach ($filters AS $fkey => $fval){ $row_filter[] = "{$fkey} = ".(is_numeric($fval) ? $fval : "'{$fval}'"); }
            $row_filter = implode(' AND ', $row_filter);
            $select_query = str_replace(';', " WHERE {$row_filter};", $select_query);
        }
        $table_rows = $db->get_array_list($select_query);
        if (empty($table_rows)){ return false; }
        $table_rows_sql = $db->get_bulk_insert_sql($table_name, $table_rows);
        if (empty($table_rows_sql)){ return false; }
        $final_table_rows_sql = self::$table_row_sql_template;
        $final_table_rows_sql = str_replace('{{TABLE_NAME}}', $table_name, $final_table_rows_sql);
        $final_table_rows_sql = str_replace('{{TABLE_ROWS_SQL}}', $table_rows_sql, $final_table_rows_sql);
        return $final_table_rows_sql;
    }

    // Define a function for getting sample table data (if exists) for export to an SQL file
    public static function get_sample_table_data_sql($table_name, $table_settings){
        if (!isset($table_settings['sample_data']) || $table_settings['sample_data'] !== true){ return false; }
        $sample_data_dir = MMRPG_CONFIG_ROOTDIR.'admin/.setup/sample-data/';
        $sample_data_path = $sample_data_dir.$table_name.'.sql';
        if (!file_exists($sample_data_path)){ return false; }
        $table_rows_sql = file_get_contents($sample_data_path);
        return $table_rows_sql;
    }

// Define templates for use with the SQL export functions
private static $table_def_sql_template = <<<'XSQL'
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

{{TABLE_DEF_SQL}}

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
XSQL;

private static $table_row_sql_template = <<<'XSQL'
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

/*!40000 ALTER TABLE `{{TABLE_NAME}}` DISABLE KEYS */;
{{TABLE_ROWS_SQL}}
/*!40000 ALTER TABLE `{{TABLE_NAME}}` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
XSQL;

}

?>