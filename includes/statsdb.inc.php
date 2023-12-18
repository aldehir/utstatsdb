<?php
$dbtype = "SQLite";     // Database type - currently supported: MySQL SQLite MsSQL
$dbpre = "ut_";         // Prefix to be prepended to all database table names.

//$SQLdb = "mysql:host=localhost;dbname=utstats";
$SQLdb = "sqlite:/var/lib/utstatsdb/utstatsdb.sqlite";

$SQLus = "utstats";     // A DB user with SELECT,INSERT,UPDATE,DELETE,CREATE,DROP,ALTER,INDEX,CREATE TEMPORARY TABLES grants.
$SQLpw = "statspass";   // The password for the above MySQL user.

$InitPass = "initpass"; // Required for initializing the database tables.

$showStats = false;     // Show SQL query and page stats in the page footer.

// Allow passing another file if set as an environment variable, e.g. within a
// container.
$additional_config_file = getenv("UTSTATSDB_CONFIG_FILE", true);
if ($additional_config_file) {
  require($additional_config_file);
}

// Optionally you can include the following line modified with the path to a file
// outside of your web path with the above information in it:
// require("/path_to_file/statsdb.inc.php");
