<?php
// Database Constants
define('DB_HOST', 'localhost');
define('DB_USER', 'moodle');
define('DB_PW', 'GEDIb1diQIvo');
define('DB_DB', 'users');

//Site constants
define("SCHOOL_HOUSES", ["GRYFFINDOR", "HUFFLEPUFF", "RAVENCLAW", "SLYTHERIN", "HOGWARTS", "NONE"]);
define("SCHOOL_YEARS", ["FIRST YEAR", "SECOND YEAR", "THIRD YEAR", "FOURTH YEAR", "FIFTH YEAR", "SIXTH YEAR", "SEVENTH YEAR", "STAFF", "NONE"]);


//LDAP Constants
define("LDAP_HOST", DB_HOST);
define("LDAP_VERSION", 3);
define("LDAP_PORT", 389);
define("LDAP_URI", LDAP_HOST . ":" . LDAP_PORT);
define("LDAP_BASE_DN", "dc=hogwarts,dc=wiz");
define("LDAP_DN_SEARCH_BASE", "ou=people," . LDAP_BASE_DN);
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
define('ROLE_STAFF', 'STAFF');
define('ROLE_STUDENT', 'STUDENT');
define('ROLE_PARENT', 'PARENT');

// Language Constants
define('LANG_NO_ROLES', 'No Role Defined');
define('LANG_NO_PERMS', 'No Permissions Defined');
define('LANG_INSUFFICIENT_PRIVILEGES', "You do not have the required permissions or role to perform that action.");
define("LANG_BAD_REQUEST", 'Malformed request. Please check that request has the appropriate parameters for this action.');


// Errors
define("ERROR_LDAP_CONNECTION", "Error connecting to the Hogwarts Authentication Directory Service.");
