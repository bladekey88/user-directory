<?php

function get_all_users(int $hidden = null)
{
    if (!$hidden) {
        $sql = "SELECT userid,username,email,firstname,lastname,commonname,middlename,house,year,quidditch,idnumber,institution,country,city,locked,last_updated
        FROM users 
        WHERE hidden IS null
        ORDER BY locked DESC,username ASC;";
    } else {
        $sql = "SELECT userid,username,email,firstname,lastname,commonname,middlename,house,year,quidditch,idnumber,institution,country,city,locked,last_updated,hidden
        FROM users
        ORDER BY locked DESC,username ASC;";
    }
    return $sql;
}

function get_specific_user(string $username, $exclude_hidden = true)
{
    $exclude_hidden ? $hidden_stmt = "AND hidden IS NULL" : $hidden_stmt = null;
    $sql = "SELECT userid,username,email,firstname,lastname,commonname,middlename,house,year,quidditch,t1.idnumber,institution,country,city,locked,last_updated,t2.path
    FROM users t1
    LEFT JOIN user_profilepicture t2
    ON t1.idnumber = t2.idnumber
    WHERE username = '$username' $hidden_stmt;";
    return $sql;
}

function check_profile_picture_exists(string $idnumber)
{
    $sql = "SELECT idnumber,path
    FROM user_profilepicture
    WHERE idnumber = '$idnumber';";
    return $sql;
}

function insert_new_profile_picture(string $idnumber, string $path)
{
    $sql = "INSERT into user_profilepicture(idnumber,path)
    VALUES ('$idnumber','$path');";
    return $sql;
}

function update_profile_picture(string $idnumber, string $path)
{
    $sql = "UPDATE user_profilepicture
    SET path = '$path'
    WHERE idnumber = '$idnumber';";
    return $sql;
}

function toggle_user_lock_status(string $username, $action)
{
    $lock_value = (strtolower($action) == "lock") ? 1 : 0;
    $sql = "UPDATE users
    SET locked = $lock_value
    WHERE username = '$username' and locked != $lock_value;";
    return $sql;
}

function get_user_permissions(string $userid)
{
    $sql = "SELECT t1.role_id, SUM(t2.permission_value) AS bitmask
    FROM role_permissions t1
    INNER JOIN permissions t2 ON t1.permission_id = t2.permission_id
    WHERE t1.role_id IN (
        SELECT role_id
        FROM user_role
        WHERE user_id = '$userid'
    )
    GROUP BY t1.role_id;";
    return ($sql);
}

function get_user_role(string $userid)
{
    $sql = "SELECT t1.user_id,t2.role_id, t2.role_name 
    FROM user_role t1
    JOIN roles t2 on t1.role_id = t2.role_id
    WHERE t1.user_id = '$userid';";
    return $sql;
}

function get_role_permissions(int $roleid)
{
    $sql = "SELECT t2.permission_id, t2.permission_name as permission
    FROM role_permissions t1
    INNER JOIN permissions t2 on t1.permission_id = t2.permission_id
    WHERE t1.role_id = '$roleid'
    ORDER BY t2.permission_id ASC;";
    return $sql;
}

function get_role_permissions_by_role_name(string $rolename)
{
    $sql = "SELECT t2.permission_id, t2.permission_name as permission
    FROM role_permissions t1
    INNER JOIN permissions t2 on t1.permission_id = t2.permission_id
    WHERE t1.role_id IN (
        select role_id
        from roles
        where role_name = '$rolename'        
    )
    ORDER BY t2.permission_id ASC;";
    return $sql;
}

function get_attribute_exists(string $attribute, string $value)
{
    $sql = "SELECT t1.$attribute 
    FROM users t1
    WHERE t1.$attribute = '$value';";
    return $sql;
}

function get_user_certificate(int $userid)
{
    $sql = "SELECT t1.* 
    FROM user_certificate t1
    WHERE t1.user_id = '$userid';";
    return $sql;
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
        -- Pivoting data from mdl_user_info_data table for specific fields
        -- Using MAX and CASE to handle multiple values for each user
        MAX(CASE WHEN uid.fieldid = 1 THEN uid.data END) AS house,
        MAX(CASE WHEN uid.fieldid = 2 THEN uid.data END) AS year,
        -- Handling boolean values for quidditch field
        MAX(CASE WHEN uid.fieldid = 3 THEN IF(uid.data=0,'false','true') END) AS quidditch
    FROM mdl_user u
    -- Joining mdl_user_info_data table to retrieve additional user information
        LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid
    WHERE u.username = '$username' AND u.deleted = 0 and u.idnumber = $idnumber
    -- Grouping the results by user id and username
    GROUP BY u.id, u.username;";
    return $sql;
}

function get_user_vle_cohort(string $username)
{
    $sql = "SELECT u.username,
    c.name AS cohort_name,
    CASE 
        WHEN c.idnumber IS NOT NULL THEN 'Custom'
        WHEN c.component = 'core' THEN 'System'
        ELSE 'Course'
    END AS cohort_type
    FROM 
        mdl_user u
    JOIN 
        mdl_cohort_members cm ON u.id = cm.userid
    JOIN 
        mdl_cohort c ON cm.cohortid = c.id
    WHERE u.username = '$username'
    ORDER BY username, cohort_name, cohort_type";
    return $sql;
}

function get_user_vle_enrolments(string $username, string $idnumber)
{
    $sql = "SELECT sub.*,other_table.fullname
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
            WHEN cx.contextlevel = 10 THEN 'System-wide'
            WHEN cx.contextlevel = 30 THEN 'User-wide'
            WHEN cx.contextlevel = 40 THEN CONCAT('Course category: ', cc.name)
            WHEN cx.contextlevel = 50 THEN CONCAT('Course: ', co.fullname)
            ELSE 'Unknown'
        END AS context,
        co.fullname AS 'coursename',
        ccs.name AS 'categoryname',
        cx.instanceid,
        co.id AS courseid,        
        cc.id AS categoryid
    FROM mdl_user u
    LEFT JOIN mdl_role_assignments ra ON u.id = ra.userid
    LEFT JOIN mdl_role r ON ra.roleid = r.id
    LEFT JOIN mdl_context cx ON ra.contextid = cx.id
    LEFT JOIN mdl_course co ON cx.contextlevel=50
    AND co.id = cx.instanceid
    LEFT JOIN mdl_course_categories cc ON cx.contextlevel=40
    AND cc.id = cx.instanceid
    LEFT JOIN mdl_course_categories ccs ON ccs.id = co.category
    where u.username = '$username' and u.idnumber = '$idnumber'
    ORDER BY username,
            role,
            context) AS sub
    LEFT JOIN mdl_course AS other_table ON sub.categoryid IS NOT NULL
    AND other_table.category = sub.categoryid;";

    return $sql;
}

function run_sql(string $db_action, $database = DB_DB)
{
    $db =  new MysqlConnection();
    $db->connect(DB_HOST, DB_USER, DB_PW, $database);
    $output = $db->query($db_action);
    if ($output) {
        return $output;
    }
    $db->close_connection();
}
