<?

// Start the output buffer to ensure stuff is printed in the right order
if (!ob_get_status()){ ob_start(); }

// Define a function for echoing debug info with a quick toggle var
$is_debug = true;
function debug_echo($echo){
    global $is_debug;
    if ($is_debug){ echo($echo.PHP_EOL); }
}

// Define a function for exiting the script with status first, debug last
function exit_action($status_line, $output = '', $data = array()){
    global $return_kind;
    $data = array();
    if (empty($data) && is_array($output)){ $data = $output; }
    if (!is_string($output)){ $output = ''; }
    if (!empty($output)){ $output .= PHP_EOL; }
    $output .= trim(ob_get_clean());
    if ($return_kind === 'html'){
        list($status_name, $status_text) = explode('|', $status_line);
        $status_colour = $status_name === 'success' ? 'green' : 'red';
        echo('<pre>'.PHP_EOL);
        echo('<strong style="color: '.$status_colour.';">'.$status_text.'</strong>'.PHP_EOL);
        echo(!empty($output) ? str_repeat('-', 50).PHP_EOL.$output : '');
        echo('</pre>'.PHP_EOL);
    } elseif ($return_kind === 'json'){
        $status_frags = explode('|', $status_line);
        list($status_name, $status_text) = explode('|', $status_line);
        header('Content-Type: application/json');
        echo(json_encode(array(
            'status' => !empty($status_frags[0]) ? $status_frags[0] : 'undefined',
            'message' => !empty($status_frags[1]) ? $status_frags[1] : '',
            'output' => $output,
            'data' => $data
            )));
    } else {
        echo(trim($status_line).PHP_EOL);
        echo(!empty($output) ? str_repeat('-', 50).PHP_EOL.$output : '');
    }
    exit();
}

?>