<?php

define("SELECT", "SELECT");
define("INSERT", "INSERT");
define("UPDATE", "UPDATE");
define("DELETE", "DELETE");

/**
 * Generates SQL query and parameters to retrieve all users.
 *
 * @param int|null $hidden If provided, filters users by hidden status.
 * @return array An associative array containing the SQL query and parameters.
 */
function get_all_users(int|null $hidden = null): array
{
    $sql = "SELECT userid, username, email, firstname, lastname, commonname, middlename, house, year, quidditch, idnumber, institution, country, city, locked, last_updated, hidden";
    $params = [];

    if (!$hidden) {
        $sql .= " FROM users WHERE hidden IS :hidden";
    } else {
        $sql .= " FROM users WHERE hidden IS NOT :hidden";
    }
    $params['hidden'] = Null;
    $sql .= " ORDER BY locked DESC, username ASC";
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_specific_user(string $username, bool $exclude_hidden = true)
{
    $exclude_hidden ? $hidden_stmt = "AND hidden IS NULL" : $hidden_stmt = null;
    $sql = "SELECT userid,username,email,firstname,lastname,commonname,middlename,house,year,quidditch,t1.idnumber,institution,country,city,locked,last_updated,t2.path
    FROM users t1
    LEFT JOIN user_profilepicture t2
    ON t1.idnumber = t2.idnumber
    WHERE username = :username $hidden_stmt;";
    $params = ["username" => $username];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function insert_new_user(
    string $username,
    string $email,
    string $hashed_password,
    string $firstname,
    string $lastname,
    ?string $middlename = null,
    ?string $commonname = null,
    string $house,
    string $year,
    string $idnumber
) {
    $table = "users";
    $data =  [
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'firstname' => $firstname,
        'middlename' => $middlename,
        'lastname' => $lastname,
        'commonname' => $commonname,
        'house' => $house,
        'year' => $year,
        'idnumber' => $idnumber,
    ];
    return ['operation' => INSERT, 'table' => $table, 'data' => $data];
}


function check_profile_picture_exists(string $idnumber)
{
    $sql = "SELECT idnumber,path
    FROM user_profilepicture
    WHERE idnumber = :idnumber;";
    $params = ["idnumber" => $idnumber];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function insert_new_profile_picture(string $idnumber, string $path)
{
    $table = "user_profilepicture";
    $data = ['idnumber' => $idnumber, 'path' => $path];
    return ['operation' => INSERT, 'table' => $table, 'data' => $data];
}

function update_profile_picture(string $idnumber, string $path)
{
    $table = "user_profilepicture";
    $data = ['path' => $path];
    $where = ['idnumber' => $idnumber];
    return ['operation' => UPDATE, 'table' => $table, 'data' => $data, 'where' => $where];
}

function toggle_user_lock_status(string $username, $action)
{
    $lock_value = (strtolower($action) == "lock") ? 1 : 0;
    $table = "users";
    $data = ['locked' => $lock_value];
    $where = ($lock_value == 1) ? ['username' => $username, 'locked' => 0] : ['username' => $username, 'locked' => 1];
    return ['operation' => UPDATE, 'table' => $table, 'data' => $data, 'where' => $where];
}

function get_user_permissions(string $userid)
{
    $sql = "SELECT t1.role_id, SUM(t2.permission_value) AS bitmask
    FROM role_permissions t1
    INNER JOIN permissions t2 ON t1.permission_id = t2.permission_id
    WHERE t1.role_id IN (
        SELECT role_id
        FROM user_role
        WHERE user_id = :userid
    )
    GROUP BY t1.role_id;";
    $params = ["userid" => $userid];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_user_role(string $userid)
{
    $sql = "SELECT t1.user_id,t2.role_id, t2.role_name 
    FROM user_role t1
    JOIN roles t2 on t1.role_id = t2.role_id
    WHERE t1.user_id = :userid;";
    $params = ["userid" => $userid];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_role_permissions(int $roleid)
{
    $sql = "SELECT t2.permission_id, t2.permission_name as permission
    FROM role_permissions t1
    INNER JOIN permissions t2 on t1.permission_id = t2.permission_id
    WHERE t1.role_id = :roleid
    ORDER BY t2.permission_id ASC;";
    $params = ["roleid" => $roleid];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_role_permissions_by_role_name(string $rolename)
{
    $sql = "SELECT t2.permission_id, t2.permission_name as permission
    FROM role_permissions t1
    INNER JOIN permissions t2 on t1.permission_id = t2.permission_id
    WHERE t1.role_id IN (
        select role_id
        from roles
        where role_name = :rolename
    )
    ORDER BY t2.permission_id ASC;";
    $params = ["rolename" => $rolename];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_attribute_exists(string $attribute, string $value)
{
    $sql = "SELECT t1.$attribute 
    FROM users t1
    WHERE t1.$attribute = :value;";
    $params = ["value" => $value];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_user_certificate(int $userid)
{
    $sql = "SELECT t1.* 
    FROM user_certificate t1
    WHERE t1.user_id = :userid;";
    $params = ["userid" => $userid];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_user_vle_info(string $username, string $idnumber)
{
    $sql = "SELECT 
        u.id,
        u.username,
        u.auth,
        -- Converting boolean values to strings
        IF(u.confirmed=0,'false','true') AS emailconfirmed,
        IF(u.policyagreed=0,'false','true') AS policyagreed,
        IF(u.suspended=0,'false','true') AS suspended, 
        u.idnumber,
        u.firstname,
        u.middlename,
        u.lastname,
        u.alternatename AS 'common_name',
        u.lastaccess,
        -- Pivoting data from mdl_user_info_data table for specific fields
        -- Using MAX and CASE to handle multiple values for each user
        MAX(CASE WHEN uid.fieldid = 1 THEN uid.data END) AS house,
        MAX(CASE WHEN uid.fieldid = 2 THEN uid.data END) AS year,
        -- Handling boolean values for quidditch field
        MAX(CASE WHEN uid.fieldid = 3 THEN IF(uid.data=0,'false','true') END) AS quidditch
    FROM mdl_user u
    -- Joining mdl_user_info_data table to retrieve additional user information
        LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid
    WHERE u.username = :username AND u.deleted = :deleted and u.idnumber = :idnumber
    -- Grouping the results by user id and username
    GROUP BY u.id, u.username;";
    $params = ["username" => $username, "deleted" => 0, "idnumber" => $idnumber];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_user_vle_cohort(string $username, string $idnumber)
{
    $sql = "SELECT u.username,
        c.name AS cohort_name,
        c.description,
        CASE
            WHEN c.component IS NULL OR c.component = '' THEN 'Core'
            WHEN c.component <> '' THEN 'Profile Attribute Based'
            ELSE 'Course'
        END AS cohort_type,
        CASE
            WHEN cx.contextlevel = 10 THEN 'System'
            WHEN cx.contextlevel = 40 THEN 'Course Category'
        END AS 'cohort_scope',
        cc.name AS 'category_name'
    FROM mdl_user u
        JOIN mdl_cohort_members cm ON u.id = cm.userid
        JOIN mdl_cohort c ON cm.cohortid = c.id
        JOIN mdl_context cx ON c.contextid = cx.id
        LEFT JOIN mdl_course_categories cc ON cx.contextlevel=40 AND cx.instanceid = cc.id
    WHERE u.username = :username AND u.idnumber = :idnumber
    ORDER BY username,cohort_name,cohort_type;";
    $params = ["username" => $username, "idnumber" => $idnumber];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

function get_user_vle_enrolments(string $username, string $idnumber)
{
    $sql = "SELECT sub.*,other_table.fullname as category_coursename
    FROM
    (
    SELECT u.id,
        u.username,
        ra.contextid,
        ra.roleid,
        ra.userid,
        ra.component,
        r.name AS 'role',
        CASE
            WHEN cx.contextlevel = 10 THEN 'System'
            WHEN cx.contextlevel = 30 THEN 'User'
            WHEN cx.contextlevel = 40 THEN 'Category'
            WHEN cx.contextlevel = 50 THEN 'Course'
            ELSE 'Unknown'
        END AS context,
        co.fullname AS 'coursename',
        cc.name AS 'categoryname',
        ccs.name AS 'course_categoryname',
        cx.instanceid,
        co.id AS courseid,        
        cc.id AS categoryid,
        us.username as parent_child_username,
        concat(us.firstname, ' ', us.lastname) as child_name
    FROM mdl_user u
    LEFT JOIN mdl_role_assignments ra ON u.id = ra.userid
    LEFT JOIN mdl_role r ON ra.roleid = r.id
    LEFT JOIN mdl_context cx ON ra.contextid = cx.id
    LEFT JOIN mdl_course co ON cx.contextlevel=50 AND co.id = cx.instanceid
    LEFT JOIN mdl_course_categories cc ON cx.contextlevel=40 AND cc.id = cx.instanceid
    LEFT JOIN mdl_course_categories ccs ON ccs.id = co.category
    LEFT JOIN mdl_user us ON cx.contextlevel=30 AND  cx.instanceid = us.id
    where u.username = :username and u.idnumber = :idnumber
    ORDER BY u.username,
            role,
            context) AS sub
    LEFT JOIN mdl_course AS other_table ON sub.categoryid IS NOT NULL
    AND other_table.category = sub.categoryid
    order by sub.coursename;";
    $params = ["username" => $username, "idnumber" => $idnumber];
    return ['operation' => SELECT, 'sql' => $sql, 'params' => $params];
}

// function run_sql(string $db_action, $database = DB_DB)
// {
//     $db =  new MysqlConnection();
//     $db->connect(DB_HOST, DB_USER, DB_PW, $database);
//     $output = $db->query($db_action);
//     if ($output) {
//         return $output;
//     }
//     $db->close_connection();
// }

/**
 * Executes common, simple SQL operations such as select, insert, update, or delete.
 * @param mixed $mode uses the function passed to string to get other values rather than applying directly
 * @param string $operation The type of SQL operation to perform (select, insert, update, delete).
 * @param string|null $sql The SQL query to execute (required for select operation only).
 * @param array $params The parameters to bind to the SQL query (required for select operation only).
 * @param string|null $table The table name for insert, update, or delete operations.
 * @param array $data The data to insert or update (required for insert and update operations only).
 * @param array $where The conditions for update or delete operations (required for update and delete operations only).
 * @param string $database The name of the database to use (optional, defaults to DB_DB).
 * @return mixed The result of the SQL operation.
 * @throws Exception If an invalid operation is supplied or if required parameters are missing.
 */
function run_sql2(
    ?array $mode = [],
    ?string $operation = null,
    ?string $sql = null,
    ?array $params = [],
    ?string $table = null,
    ?array $data = [],
    ?array $where = [],
    string $database = DB_DB
) {
    $operation = $mode["operation"] ?? $operation;
    $sql = $mode["sql"] ?? $sql;
    $params = $mode["params"] ?? $params;
    $table = $mode["table"] ?? $table;
    $data = $mode["data"] ?? $data;
    $where = $mode["where"] ?? $where;
    // Valid operations only
    try {
        $standardOperations = ['select', 'insert', 'update', 'delete'];
        $operation = strtolower($operation);
        if (!in_array($operation, $standardOperations)) {
            throw new Exception("Invalid operation supplied: '$operation");
        }

        // Create DB connection and run queries
        $db = new DatabaseConnection(DB_TYPE, DB_HOST, DB_USER, DB_PW, $database);
        if ($db->isConnected()) {
            switch ($operation) {
                case 'select':
                    if (!$sql || (isset($params) && !is_array($params))) {
                        throw new Exception("select operations must have sql defined and associated params as array");
                    } elseif (strpos(strtoupper($sql), "WHERE") && (!$params)) {
                        throw new Exception("select operations with a WHERE clause must have the named params defined in the params array");
                    } else {
                        return $db->select($sql, $params);
                    }
                case 'insert':
                    if (!$table || !$data) {
                        throw new Exception("insert operations must have a table defined and a where clause");
                    } else {
                        return $db->insert($table, $data);
                    }
                case 'update':
                    if (!$where || !$table) {
                        throw new Exception("update operations must have a table defined and a where clause");
                    } else {
                        return $db->update($table, $data, $where);
                    }
                case 'delete':
                    if (!$where || !$table) {
                        throw new Exception("delete operations must have a table defined and a where clause");
                    } else {
                        return $db->delete($table, $where);
                    }
            }
        }
    } catch (Exception $e) {
        throw new Exception("An error occured: '" . $e->getMessage() . "'");
    }
}
