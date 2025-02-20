<?php
## TODO REMOVE CONSTANT VALUES NEEDED FOR PROD USE


// Path Constants
# Provide base URL e.g. www.example.com, or www.example.com/directory
# Do not include the trailing slash, do include the protocol (e.g. http://, https://)
define("WEBROOT", "https://secure.hogwarts.wiz/directory");
# This defines the root server directory locatin relative from where the constant file is
# so that this file can be required/included at any level. The config folder is always
# at the level below the root e.g root/config
define("FILEROOT", dirname(__FILE__, 2));

// Database Constants
define('DB_TYPE', "mysql");
define('DB_HOST', 'localhost');
define('DB_USER', 'moodle');
define('DB_PW', 'GEDIb1diQIvo'); #TODO REMOVE FOR PRODUCTION
define('DB_DB', 'users');
define('MOODLE_DB', "moodle");

// HMAIL Constants
define("HMAIL_ADMIN_USER", "system@hogwarts.wiz");
define("HMAIL_ADMIN_PW", "laptop"); #TODO REMOVE FOR PRODUCTION
define("HMAIL_DOMAIN", "hogwarts.wiz");

// Site constants
define("SCHOOL_HOUSES", ["GRYFFINDOR", "HUFFLEPUFF", "RAVENCLAW", "SLYTHERIN", "HOGWARTS", "NONE"]);
define("SCHOOL_YEARS", ["FIRST YEAR", "SECOND YEAR", "THIRD YEAR", "FOURTH YEAR", "FIFTH YEAR", "SIXTH YEAR", "SEVENTH YEAR", "STAFF", "NONE"]);
define('VALID_PREFECT_YEARS', ["FIFTH YEAR", "SIXTH YEAR", "SEVENTH YEAR"]);
define('VALID_QUIDDITCH_YEARS', ["SECOND YEAR", "THIRD YEAR", "FOURTH YEAR", "FIFTH YEAR", "SIXTH YEAR", "SEVENTH YEAR"]);
define('VALID_STUDENT_HOUSES', array_diff(SCHOOL_HOUSES, ["HOGWARTS", "NONE"]));
define('USER_SEX_GENDER', ['UNKNOWN' => 0, 'FEMALE' => 1, 'MALE' => 2]);

// LDAP Constants
define("LDAP_PROTOCOL", "ldap://");
define("LDAP_HOST", DB_HOST);
define("LDAP_VERSION", 3);
define("LDAP_PORT", 389);
define("LDAP_URI", LDAP_HOST . ":" . LDAP_PORT);
define("LDAP_BASE_DN", "dc=hogwarts,dc=wiz");
define("LDAP_DN_SEARCH_BASE", "ou=people," . LDAP_BASE_DN);
define("LDAP_BASE_GROUP_DN", "ou=groups," . LDAP_BASE_DN);
define("LDAP_USER_DN", "cn=directory.serviceid,ou=apps," . LDAP_BASE_DN);
define("LDAP_PW", "laptop");


// Permission Constants
define('PERMISSION_VIEW_USER', 1);
define('PERMISSION_VIEW_ALL_USERS', 2);
define('PERMISSION_EDIT_OWN_PROFILE', 4);
define('PERMISSION_EDIT_ANY_PROFILE', 8);
define('PERMISSION_DELETE_USER', 16);
define('PERMISSION_LOCK_USER', 32);
define('PERMISSION_UNLOCK_USER', 64);
define('PERMISSION_HIDE_USER', 128);
define('PERMISSION_UNHIDE_USER', 256);
define('PERMISSION_ADD_USER', 512);
define('PERMISSION_SYNC_USER', 1024);
define('PERMISSION_EXTERNAL_USER', 2048);

// User Role Constants
define('ROLE_ADMIN', 'ADMINISTRATOR');
define('ROLE_SENIOR_STAFF', 'SENIOR STAFF');
define('ROLE_STAFF', 'STAFF');
define('ROLE_STUDENT', 'STUDENT');
define('ROLE_PARENT', 'PARENT');
define('ROLE_NONE', 'NONE');
define('USER_ROLES', [ROLE_PARENT, ROLE_STUDENT, ROLE_STAFF, ROLE_SENIOR_STAFF, ROLE_ADMIN]);



// Language Constants
define('LANG_NO_ROLES', 'No Role Defined');
define('LANG_NO_PERMS', 'No Permissions Defined');
define('LANG_INSUFFICIENT_PRIVILEGES', "You do not have the required permissions or role to perform that action.");
define("LANG_BAD_REQUEST", 'Malformed request. Please check that request has the appropriate parameters for this action.');
define("LANG_SQL_UPDATED", "updated");
define("LANG_SQL_ADDED", "added");
define("LANG_SQL_DELETED", "deleted");

// Errors
define("ERROR_LDAP_CONNECTION", "Unable to connect the Hogwarts Authentication Directory Service. There may be network issues or the service may be unreachable.");
