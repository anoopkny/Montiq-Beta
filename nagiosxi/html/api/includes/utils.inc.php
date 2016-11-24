<?php
//
// New REST API utils and classes for Nagios XI 5
//

abstract class NagiosAPI
{
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';

    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $verb = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = array();

    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = null;

    protected $input = array();

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct($request)
    {
        $this->args = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);
        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->verb = array_shift($this->args);
        }

        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        switch ($this->method)
        {
            case 'POST':
                $this->request = $this->_clean_inputs($_POST);
                $tmp = $this->_clean_inputs($_GET);
                if (!array_key_exists('apikey', $this->request) && array_key_exists('apikey', $tmp)) {
                    $this->request['apikey'] = $tmp['apikey'];
                }
                break;
            case 'GET':
                $this->request = $this->_clean_inputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->_clean_inputs($_GET);
                $this->input = json_decode(file_get_contents("php://input"));
                break;
            case 'DELETE':
                $this->request = $this->_clean_inputs($_GET);
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    private function _response($data, $status = 200)
    {
        header("HTTP/1.1 " . $status . " " . $this->_request_status($status));
        if (is_array($data) || is_object($data)) {
            if (!empty($_GET['pretty'])) {
                return $this->json_format(json_encode($data))."\n";
            }
            return json_encode($data)."\n";
        }
        if (!empty($_GET['pretty'])) {
            return $this->json_format($data)."\n";
        }
        return $data."\n";
    }

    private function _clean_inputs($data)
    {
        $clean_input = array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_clean_inputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _request_status($code)
    {
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error'
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }

    public function process_api()
    {
        /* check for custom endpoint/verb first! */
        $custom = do_custom_api_callback($this->endpoint, $this->verb, $this->args);
        if ($custom !== false) {
            return $this->_response($custom);
        }
        if (method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}($this->verb, $this->args));
        }
        return $this->_response("No Endpoint: $this->endpoint", 404);
    }

    /**
    * Format a flat JSON string to make it more human-readable
    *
    * @param string $json The original JSON string to process
    *        When the input is not a string it is assumed the input is RAW
    *        and should be converted to JSON first of all.
    * @return string Indented version of the original JSON string
    */
    function json_format($json)
    {
        if (!is_string($json)) {
            if (phpversion() && phpversion() >= 5.4) {
                return json_encode($json, JSON_PRETTY_PRINT);
            }
            $json = json_encode($json);
        }
        $result      = '';
        $pos         = 0;               // indentation level
        $strLen      = strlen($json);
        $indentStr   = "    ";
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;
        for ($i = 0; $i < $strLen; $i++) {
            // Speedup: copy blocks of input which don't matter re string detection and formatting.
            $copyLen = strcspn($json, $outOfQuotes ? " \t\r\n\",:[{}]" : "\\\"", $i);
            if ($copyLen >= 1) {
                $copyStr = substr($json, $i, $copyLen);
                // Also reset the tracker for escapes: we won't be hitting any right now
                // and the next round is the first time an 'escape' character can be seen again at the input.
                $prevChar = '';
                $result .= $copyStr;
                $i += $copyLen - 1;      // correct for the for(;;) loop
                continue;
            }
        
            // Grab the next character in the string
            $char = substr($json, $i, 1);
            
            // Are we inside a quoted string encountering an escape sequence?
            if (!$outOfQuotes && $prevChar === '\\') {
                // Add the escaped character to the result string and ignore it for the string enter/exit detection:
                $result .= $char;
                $prevChar = '';
                continue;
            }

            // Are we entering/exiting a quoted string?
            if ($char === '"' && $prevChar !== '\\') {
                $outOfQuotes = !$outOfQuotes;
            }
            // If this character is the end of an element,
            // output a new line and indent the next line
            else if ($outOfQuotes && ($char === '}' || $char === ']')) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            // eat all non-essential whitespace in the input as we do our own here and it would only mess up our process
            else if ($outOfQuotes && false !== strpos(" \t\r\n", $char)) {
                continue;
            }
        
            // Add the character to the result string
            $result .= $char;
       
            // always add a space after a field colon:
            if ($outOfQuotes && $char === ':') {
                $result .= ' ';
            }
            // If the last character was the beginning of an element,
            // output a new line and indent the next line
            else if ($outOfQuotes && ($char === ',' || $char === '{' || $char === '[')) {
                $result .= $newLine;
                if ($char === '{' || $char === '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            $prevChar = $char;
        }
        return $result;
    }

}

class APIKey
{
    public function verify_key($apikey, $origin, &$reason)
    {
        $apikey = escape_sql_param($apikey, DB_NAGIOSXI);

        // Get the user based on apikey and ticket
        $rs = exec_sql_query(DB_NAGIOSXI, "SELECT user_id, username, enabled, api_enabled FROM xi_users WHERE api_key = '$apikey';");

        foreach ($rs as $r) {
            if ($r['api_enabled'] == 1 && $r['enabled'] == 1) {
                $_SESSION['user_id'] = $r['user_id'];
                $_SESSION['username'] = $r['username'];
                return true;

            } else if ($r['enabled'] != 1) {
                $reason = _('Account is disabled');

            } else if ($r['api_enabled'] != 1) {
                $reason = _('API Access is disabled');
            }
            return false;
        }

        $reason = _('Invalid API Key');
        return false;
    }

}