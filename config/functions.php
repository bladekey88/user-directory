<?php
require_once("constants.php");

class DatabaseConnection
{
    private $pdo;

    public function __construct($db_type, $db_hostname, $db_username, $db_password = null, $db_name)
    {
        try {
            $dsn = "$db_type:host=$db_hostname;dbname=$db_name;";
            $this->pdo = new PDO($dsn, $db_username, $db_password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Check if the error code indicates a connection failure
            echo ("Connection failed: An unexpected error occurred:  " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        // Close the database connection
        $this->pdo = null;
    }

    public function isConnected(): bool
    {
        // Check if the PDO object is instantiated and not null
        return $this->pdo instanceof PDO && $this->pdo !== null;
    }

    public function select($sql, $where_params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            if ($where_params) {
                foreach ($where_params as $key => $value) {
                    $stmt->bindValue(":$key", $value);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error executing query: " . $e->getMessage());
        }
    }

    public function insert($table, $data)
    // Constructs and executes the INSERT SQL query with named placeholders
    {
        try {
            $keys = implode(', ', array_keys($data));
            // Create named placeholders for values
            $values = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO $table ($keys) VALUES ($values)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error executing query: " . $e->getMessage());
        }
    }

    ## TODO update function to handle complex where logic
    ## ALSO CHANGE DELETE FUNCTION TOO
    public function update($table, $data, $where)
    // Constructs and executes the UPDATE SQL query with named placeholders
    {
        $set = implode(', ', array_map(fn ($k) => "$k = :$k", array_keys($data)));
        $where_clase = implode(' AND ', array_map(fn ($k) => "$k = :where_$k", array_keys($where)));
        $sql = "UPDATE $table SET $set WHERE $where_clase";
        $stmt = $this->pdo->prepare($sql);

        // Bind the values from $data as parameters
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        // Bind the values from $where as parameters
        foreach ($where as $key => $value) {
            $stmt->bindValue(":where_$key", $value);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function delete($table, $where)
    // Constructs and executes the DELETE SQL query with named placeholders
    {
        $whereClause = implode(' AND ', array_map(fn ($k) => "$k = :where_$k", array_keys($where)));
        $sql = "DELETE FROM $table WHERE $whereClause";
        $stmt = $this->pdo->prepare($sql);

        // Bind the values from $where as parameters
        foreach ($where as $key => $value) {
            $stmt->bindValue(":where_$key", $value);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }
}




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

    function __construct($host, $port = 389, $timeout = 0.1)
    {
        // Check if the host starts with ldap:// or ldaps://
        if (strpos($host, 'ldap://') !== 0 && strpos($host, 'ldaps://') !== 0) {
            throw new Exception("Invalid host provided: '$host'. Only LDAP hosts are supported.");
        }
        // Extract the host from the provided URL
        $host = str_replace(['ldap://', 'ldaps://'], '', $host);
        $errno = $errstr = 0;

        // Use the method fsockopen to test TCP connect. No way to ignore SSL certificate errors with this method!
        $op = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $result = $op ? true : false;

        if ($op) {
            fclose($op); // Explicitly close open socket connection
            $this->connect();
        }
        return $result;
    }

    private function connect()
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
    $get_user_permissions = run_sql2(get_user_permissions($_SESSION["userid"]));
    $result = $get_user_permissions[0];
    if ($result) {
        return $result["bitmask"] & $permission;
    }
    return False;
}


function check_user_role($role, $userid = null)
{
    $user_id = isset($userid) ? $userid : $_SESSION["userid"];
    $get_user_role = run_sql2(get_user_role($user_id));
    $result = $get_user_role[0];
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
        case 'alpha':
            $input =  preg_replace('/[^a-zA-Z]/', '', $input);
            break;
        case 'email':
            // Validate and sanitize email address
            $input = filter_var($input, FILTER_SANITIZE_EMAIL);
            $input = filter_var($input, FILTER_VALIDATE_EMAIL);
            break;
        case 'numeric':
            // Allow only numeric characters
            $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            $input = filter_var($input, FILTER_VALIDATE_INT);
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


function _run_ldap(string $filter, array $fields = null): string|array

/** Private(ish) function to run queries against LDAP.
 *  Use by other function, but could be used directly if necessary */

{
    try {
        $ldap = new LDAPConnection(LDAP_PROTOCOL . LDAP_HOST);
    } catch (Exception $e) {
        // Handle any exceptions thrown by the LDAPConnection constructor
        return ["Error: " . $e->getMessage()];
    }
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


function get_certificate_information(): array|bool
{
    $userid = $_SESSION["userid"];
    $get_cert_info = run_sql2(get_user_certificate($userid));
    if ($get_cert_info) {
        $result = [0];
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
