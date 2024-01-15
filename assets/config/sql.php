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

function get_specific_user(string $username)
{
    $sql = "SELECT userid,username,email,firstname,lastname,commonname,middlename,house,year,quidditch,t1.idnumber,institution,country,city,locked,last_updated,t2.path
    FROM users t1
    LEFT JOIN user_profilepicture t2
    ON t1.idnumber = t2.idnumber
    WHERE username = '$username';";
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


function run_sql(string $db_action)
{
    $db =  new MysqlConnection();
    $db->connect(DB_HOST, DB_USER, DB_PW, DB_DB);
    $output = $db->query($db_action);
    if ($output) {
        return $output;
    }
    $db->close_connection();
}
