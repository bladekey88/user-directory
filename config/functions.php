<?php
require_once("constants.php");

class MysqlConnection
{
    var $connection = false;

    public function connect($db_hostname = null, $db_username = null, $db_password = null, $db_database = null)
    {
        if (!extension_loaded('mysqli')) {
            echo "Cannot proceed due to error";
            return null;
        }

        // Create a mysqli object and then connect
        $this->connection = mysqli_init();
        @$db = mysqli_real_connect($this->connection, $db_hostname, $db_username, $db_password, $db_database);
        return $db;
    }

    public function close_connection()
    {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
        $this->connection = false;
    }


    public function query($query)
    {
        if (!$this->connection) {
            return False;
        } else {
            return  $this->connection->query($query);
        }
    }
}

class LDAPConnection
{
    var $connection = False;

    function __construct()
    {
        // connect to ldap server
        @$ldapconn = ldap_connect("ldap://" . LDAP_URI)
            or die("Unable to  connect to LDAP server.");

        if ($ldapconn) {
            ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, LDAP_VERSION);

            // binding to ldap server
            $ldapbind = @ldap_bind($ldapconn, LDAP_USER_DN, LDAP_PW);

            // verify binding
            if ($ldapbind) {
                return $this->connection = $ldapconn;
            } else {
                return False;
            }
        }
    }


    public function search(string $base = null, string $filter = null, array $fields = null)
    {
        // Uses null coalescing operator : ?? to replace pattern $var = isset($a) ? $a : $b
        $search_base = $base ?? LDAP_DN_SEARCH_BASE;
        $search_filter = $filter ?? "uid=*";
        $return_fields = $fields;

        // Validate input parameters
        if (!is_string($search_base) || (!is_string($search_filter) && !is_null($search_filter)) || (!is_array($return_fields)) && !is_null($return_fields)) {
            throw new InvalidArgumentException('Invalid input parameters.');
        }

        if ($this->connection) {
            if ($return_fields !== null) {
                $search = ldap_search($this->connection, $search_base, $search_filter, $return_fields);
            } else {
                $search = ldap_search($this->connection, $search_base, $search_filter);
            }

            if ($search) {
                $result = ldap_get_entries($this->connection, $search);
                if ($result["count"] > 0 || ldap_count_entries($this->connection, $search) > 0) {
                    $flattened_array =  $this->parse_openldap_result($result);
                    return $flattened_array !== false ? $flattened_array : $result;
                }
            }
        }
        return False;
    }

    private function parse_openldap_result($result)
    {
        if ($result["count"] > 0) {
            $details = array();
            for ($i = 0; $i < $result["count"]; $i++) {
                $entry = array();
                for ($j = 0; $j < $result[$i]["count"]; $j++) {
                    $key =  $result[$i][$j];
                    $value = $result[$i][$key][0];

                    // Store key-value pair in the $entry array
                    $entry[$key] = $value;
                }
                $details[] = $entry;
            }
            if (count($details) > 0) {
                return $details;
            }
        }
        return False;
    }

    public function close_connection()
    {
        if ($this->connection) {
            return ldap_unbind($this->connection);
        }
    }
}

function check_user_permission($permission)
{
    $get_user_permissions = run_sql(get_user_permissions($_SESSION["userid"]));
    $result = mysqli_fetch_assoc($get_user_permissions);
    if ($result) {
        return $result["bitmask"] & $permission;
    }
    return False;
}


function check_user_role($role, $userid = null)
{
    $user_id = isset($userid) ? $userid : $_SESSION["userid"];
    $get_user_role = run_sql(get_user_role($user_id));
    $result = mysqli_fetch_assoc($get_user_role);
    if ($result) {
        return $result["role_name"] === strtoupper($role);
    }
    return False;
}


function sanitise_user_input($input, $type = 'text')
{
    // Remove leading and trailing whitespaces
    $input = trim($input);

    // Define allowed types
    $allowedTypes = ['text', 'email', 'numeric', 'url', 'username'];

    // Check if the specified type is allowed
    if (!in_array($type, $allowedTypes)) {
        throw new InvalidArgumentException('Invalid input type specified.');
    }

    // Perform type-specific validation and sanitisation
    switch ($type) {
        case 'text':
            // Basic text input, allow alphanumeric characters, spaces, and common symbols
            $input = preg_replace('/[^a-zA-Z0-9\s!@#$%^&*()_+=\-,.?]/', '', $input);
            break;
        case 'email':
            // Validate and sanitize email address
            $input = filter_var($input, FILTER_SANITIZE_EMAIL);
            $input = filter_var($input, FILTER_VALIDATE_EMAIL);
            break;
        case 'numeric':
            // Allow only numeric characters
            $input = preg_replace('/[^0-9]/', '', $input);
            break;
        case 'url':
            // Validate and sanitize URL
            $input = filter_var($input, FILTER_SANITIZE_URL);
            $input = filter_var($input, FILTER_VALIDATE_URL);
            break;
            // Add more cases for additional types if needed
        case 'username':
            $input = preg_replace('/[^a-zA-Z0-9.-]/', '', $input);
            break;
    }
    return $input;
}


function _run_ldap(string $filter, array $fields = null): array

/** Private(ish) function to run queries against LDAP.
 *  Use by other function, but could be used directly if necessary */

{
    $ldap = new LDAPConnection();
    if (!$ldap->connection) {
        return [ERROR_LDAP_CONNECTION];
    }
    if ($fields) {
        $data = $ldap->search(LDAP_DN_SEARCH_BASE, $filter, $fields);
    } else {
        $data = $ldap->search(LDAP_DN_SEARCH_BASE, $filter);
    }
    $ldap->close_connection();


    return $data ? $data : ["User does not exist in LDAP"];
}

function ldap_get_user_info(string $username = null): array|string
/** Gets user details from LDAP. 
 * If no username is supplied it falls back to session username */
{
    $user = $username ?? $_SESSION["username"];
    return _run_ldap("uid=$user");
}

function ldap_parse_user_photo($img_data)
{
    // Create a Fileinfo object
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    // Get the MIME type
    $mime_type = finfo_buffer($finfo, $img_data);

    // Close the Fileinfo object
    finfo_close($finfo);

    //Convert the binary data to base64 encoding then create the data URI 
    $base64ImageData = base64_encode($img_data);
    $dataUri = 'data:' . $mime_type . ';base64,' . $base64ImageData;

    return $dataUri;
}

function validate_client_certificate()
{
    if ($_SERVER['SSL_CLIENT_VERIFY'] == "NONE") {
        return ['success' => false, 'reason' => 'No certificate presented'];
    } else if (
        !isset($_SERVER['SSL_CLIENT_M_SERIAL'])
        || !isset($_SERVER['SSL_CLIENT_V_END'])
        || !isset($_SERVER['SSL_CLIENT_VERIFY'])
        || $_SERVER['SSL_CLIENT_VERIFY'] !== 'SUCCESS'
        || !isset($_SERVER['SSL_CLIENT_I_DN'])
    ) {
        return ['success' => false, 'reason' => 'Incomplete or invalid SSL certificate information'];
    }

    if ($_SERVER['SSL_CLIENT_V_REMAIN'] <= 0) {
        return ['success' => false, 'reason' => 'SSL certificate has expired'];
    }

    return ['success' => true, 'reason' => 'SSL certificate appears to be valid'];
}


function get_certificate_information(): array|string
{
    $userid = $_SESSION["userid"];
    $get_cert_info = run_sql(get_user_certificate($userid));
    $result = mysqli_fetch_assoc($get_cert_info);
    if ($result) {
        return $result;
    }
    return False;
}

function check_certificate_exists(string $serial)
{
    $certificate = get_certificate_information();
    if ($certificate) {
        return $serial && $certificate["certificate_serial"];
    }
    return False;
}

function redirect(string $url, string $custom_header = null)
{
    // Always redirect using absolute path
    $webroot_present = preg_match("#" . WEBROOT . "#", $url);
    if (!$webroot_present) {
        $url =  WEBROOT . "$url";
    }
    // If scheme is not provided, then can add it in
    // Only expect http and https schemes (use # as delim)
    $scheme_present = preg_match("#http(?:s)?://#i", $url);
    if (!$scheme_present) {
        $match_scheme = preg_match("#[a-z]+?://#i", WEBROOT, $scheme_from_url);
        $new_url = $scheme_from_url[0] . $url;
    } else {
        $new_url = $url;
    }
    $custom_header !== null ?  header($custom_header) : null;
    header("Location: $new_url");
    exit();
}


require_once(FILEROOT . "/config/sql.php");
