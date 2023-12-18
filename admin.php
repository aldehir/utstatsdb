<?php

/*
    UTStatsDB
    Copyright (C) 2002-2010  Patrick Contreras / Paul Gallier
    Copyright (C) 2018  Kenneth Watson

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require("includes/statsdb.inc.php");
require("includes/logsql.php");

$adminver = new AdminVer;
$adminver->major = 3;
$adminver->minor = 9;
$adminver->extra = "";
$updatereq = 0;

function check_post($val)
{
  $store = "";
  if (isset($_POST["$val"]))
    $store = $_POST["$val"];
  return $store;
}

function check_get($val)
{
  $store = "";
  if (isset($_GET["$val"]))
    $store = $_GET["$val"];
  return $store;
}

$Mode = check_post("Mode");
$PostPass = check_post("Pass");
$Action = check_get("action");

$QueryUp = intval(check_post("QueryUp"));
$QueryDown = intval(check_post("QueryDown"));
if ($QueryUp > 0 || $QueryDown > 0)
  $Mode = "MoveQuery";

session_name("UTStatsDB");
session_start();

if (isset($_SESSION['UTStatsDBadmin']))
  $CPass = $_SESSION['UTStatsDBadmin'];
else
  $CPass = "";

$crypt = get_extension_funcs("mcrypt");
$cryptkey = "You can change this Key.";

function cryptpass($pass) {
  global $cryptkey;
  $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
  $cryptpass = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $cryptkey, $pass, MCRYPT_MODE_ECB, $iv);
  return $cryptpass;
}

function decryptpass($cryptpass) {
  global $cryptkey;
  $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
  $pass = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $cryptkey, $cryptpass, MCRYPT_MODE_ECB, $iv);
  return $pass;
}

class AdminVer {
  var $major = 0;
  var $minor = 0;
  var $extra = "";
}

//=============================================================================
//========== SQL Test =========================================================
//=============================================================================
$error = 0;
if (!isset($dbpre) || $dbpre == "") {
  echo "<b>Error: You must set the \$dbpre variable in your statsdb.inc.php file (default 'ut_').</b><br />\n";
  $error++;
}
if (!isset($dbtype) || $dbtype == "") {
  echo "<b>Error: You must set the \$dbtype variable in your statsdb.inc.php file to your SQL database type (MySQL/SQLite).</b><br />\n";
  $error++;
}

if (strtolower($dbtype) != "sqlite") {
  if (!isset($SQLus) || $SQLus == "") {
    echo "<b>Error: You must set the \$SQLus variable in statsdb.inc.php to your SQL database username.</b><br />\n";
    $error++;
  }
  if (!isset($SQLpw) || $SQLpw == "") {
    echo "<b>Error: You must set the \$SQLpw variable in statsdb.inc.php to your SQL database password.</b><br />\n";
    $error++;
  }
}

if (!isset($SQLdb) || $SQLdb == "") {
  echo "<b>Error: You must set the \$SQLdb variable in statsdb.inc.php to your SQL database name.</b><br />\n";
  $error++;
}

$testLink = sql_connect();
if (!$testLink) $error++;
else sql_close($testLink);

if ($error) {
  exit;
}

//=============================================================================
//========== Check for Init ===================================================
//=============================================================================
if ($Mode == "Initialize")
{
  if (intval(check_post("InitType")) == 3) {
    if (strtolower($dbtype) == "sqlite") {
      if (!file_exists("$SQLdb") || filesize("$SQLdb") == 0) {
        include_once("includes/admininit.php");
        initconfig(0);
      }
    }
    else {
      $result = sql_query("SELECT COUNT(*) FROM {$dbpre}config");
      if (!$result) {
        include_once("includes/admininit.php");
        initconfig(0);
      }
    }
    $Mode = "";
  }
}

loadpass();

//=============================================================================
//========== Security Check ===================================================
//=============================================================================
if ($PostPass == "" && $CPass == "")
  login();

$Pass = "";
if ($Mode == "Login") {
  if ($PostPass != $AdmPass)
  {
    unset($_SESSION['UTStatsDBadmin']);
    invalid_login();
  }

  $Mode = "Main";
  if ($crypt) {
    $CryptPass = cryptpass($PostPass);
    $_SESSION['UTStatsDBadmin'] = $CryptPass;
  }
  else
    $_SESSION['UTStatsDBadmin'] = $PostPass;

  $Pass = $PostPass;
}
else if ($CPass != "") {
  if ($crypt)
    $Pass = rtrim(decryptpass($CPass), "\0");
  else
    $Pass = $CPass;

  if ($Pass != $AdmPass) {
    unset($_SESSION['UTStatsDBadmin']);
  	if ($PostPass != "")
      invalid_login();
    login();
  }
}

if ($Pass != $AdmPass)
  auth_required();

if ($Mode == "" || $Mode == "Cancel")
  $Mode = "Main";

//=============================================================================
//========== Main Options =====================================================
//=============================================================================
switch($Mode) {
  case "Main":
    switch($Action) {
      case "mainconfig": // Main Configuration
        require_once("includes/adminconfig.php");
        mainconfig();
        break;
      case "logsconfig": // Logs Configuration
        require_once("includes/adminconfig.php");
        logsconfig();
        break;
      case "queryconfig": // Query Configuration
        require_once("includes/adminconfig.php");
        queryconfig();
        break;
      case "menuconfig": // Menu Configuration
        require_once("includes/adminconfig.php");
        menuconfig();
        break;
      case "mergeplayers": // Merge Players
        include_once("includes/adminmerge.php");
        mergeentry(1);
        break;
      case "mergeservers": // Merge Servers
        include_once("includes/adminmerge.php");
        mergeentry(2);
        break;
      case "trackplayer": // Track Player
        require_once("includes/admintrack.php");
        trackplayer();
        break;
      case "reinitialize": // Reinitialize
        include_once("includes/admininit.php");
        initcheck(1);
        break;
      case "cleardata": // Clear Data
        include_once("includes/admininit.php");
        initcheck(2);
        break;
      case "parselogs": // Initialize
        logparse();
        break;
      case "update": // Update
        version_check();
        if ($updatereq)
          updatedb();
        else
          mainpage();
        break;
      default:
        mainpage();
        break;
    }
    break;
  case "Save": // Save Configuration
    require_once("includes/adminconfig.php");
    saveconfig();
    mainpage();
    break;
  case "Add": // Add Configuration
    require_once("includes/adminconfig.php");
    saveconfig();
    addconfig();
    break;
  case "Merge": // Verify Merge
    include_once("includes/adminmerge.php");
    mergeverify();
    break;
  case "DoMerge": // Do Merge
    include_once("includes/adminmerge.php");
    mergeexecute();
    break;
  case "MoveQuery": // Modify Query order
    require_once("includes/adminconfig.php");
    saveconfig();
    movequery();
    break;
  case "Track": // Track Player Display
    require_once("includes/admintrack.php");
    trackplayerdisplay();
    break;
  case "Initialize": // Initialize
    include_once("includes/admininit.php");
    $reinit = check_post("InitType");
    initconfig($reinit);
    break;
  default: // Login
    login();
}
exit;

//=============================================================================
//========== Main Menu ========================================================
//=============================================================================
function mainpage() {
  menu_top();
  echo "<div class=\"header\">Select an action on the left.</div>\n";
  menu_bottom();
  exit;
}

function version_check() {
  global $dbpre, $adminver, $updatereq, $vermajor, $verminor, $verextra;

  $versioncheck = "";
  $result = sql_query("SELECT value FROM {$dbpre}config WHERE conf='Version' LIMIT 1");
  if (!$result)
    $versioncheck = "Version check error!<br />\n";
  else {
    $verextra = "";
    $vermajor = $verminor = 0;
    $row = sql_fetch_row($result);
    sql_free_result($result);
    $ver = $row[0];
    if (($pos = strpos($ver, ".")) !== FALSE)
      $vermajor = substr($ver, 0, $pos);
    if (is_numeric(substr($ver, $pos + 1, 1)) && is_numeric(substr($ver, $pos + 2, 1)))
      $verminor = substr($ver, $pos + 1, 2);
    if (strlen($ver) > $pos + 2)
      $verextra = substr($ver, $pos + 3);

    if ($adminver->major > $vermajor || ($adminver->major == $vermajor && $adminver->minor > $verminor)) {
      $versioncheck = "Old Database - update required.";
      $updatereq = 1;
    }
    else if ($adminver->major < $vermajor || ($adminver->major == $vermajor && $adminver->minor < $verminor))
      $versioncheck = "Warning: Database newer than admin utility!";
    else if ($adminver->major == $vermajor && $adminver->major == $vermajor && $adminver->extra == "" && $verextra != "")
      $versioncheck = "Warning: Pre-release database found - must reinitialize!";
    else if ($adminver->major != $vermajor || $adminver->minor != $verminor || $adminver->extra != $verextra)
      $versioncheck = "Database version mismatch!";
  }

  return($versioncheck);
}

function menu_top() {
  global $Mode, $updatereq;

  if ($Mode != "Initialize")
    $versioncheck = version_check();
  else
    $versioncheck = "";

  header('Content-Type:text/html; charset=utf-8');

  echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>UTStatsDB Admin</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <link rel="stylesheet" type="text/css" media="screen" href="resource/admin.css" />
  <meta name="MSSmartTagsPreventParsing" content="TRUE" />
  <link rel="shortcut icon" href="resource/favicon.ico" />
  <link rel="icon" href="resource/favicon.ico" />
</head>
<body>

<div id="header">
  <div id="header-logo">
    <a href="index.php"><img src="resource/utstatsdblogo.png" alt="UTStatsDB Logo" /></a>
  </div>
</div>

<div id="nav"></div>
  <div id="side-left">
    <div id="sidenav-label">Admin Menu:</div>
      <ul id="sidenav">
        <li><a href="?action=parselogs">Parse&nbsp;Logs</a></li>
        <li><a href="?action=mainconfig">Main&nbsp;Config</a></li>
        <li><a href="?action=logsconfig">Logs&nbsp;Config</a></li>
        <li><a href="?action=queryconfig">Query&nbsp;Config</a></li>
        <li><a href="?action=menuconfig">Menu&nbsp;Config</a></li>
        <li><a href="?action=mergeplayers">Merge&nbsp;Players</a></li>
        <li><a href="?action=mergeservers">Merge&nbsp;Servers</a></li>
        <li><a href="?action=trackplayer">Track&nbsp;Player</a></li>
        <li><a href="?action=reinitialize">Reinitialize</a></li>
        <li><a href="?action=cleardata">Clear&nbsp;Data</a></li>

EOF;

  if ($updatereq)
    echo "        <li><a href=\"?action=update\">Update DB</a></li>\n";

  echo <<<EOF
      </ul>
    </div>

    <div id="middle">
      <div id="content">

EOF;

  if ($versioncheck != "")
    echo "      <font color=\"#800000\"><b>$versioncheck</b></font><br /><br />\n";
}

function menu_bottom() {
  echo <<<EOF
      </div>
  </div>

EOF;

  require("includes/footer.inc.php");
}

//=============================================================================
//========== Updates ==========================================================
//=============================================================================
function updatedb() {
  menu_top();

  $ver = currentver();
  if ($ver == -1) {
    menu_bottom();
    exit;
  }
  list($vermajor, $verminor, $verextra) = $ver;
  if ($vermajor == 3 && $verminor == 6) {
    echo "Updating database to version 3.07....<br /><br />\n";
    include_once("includes/adminupdate.php");
    update307();
  }

  $ver = currentver();
  if ($ver == -1) {
    menu_bottom();
    exit;
  }
  list($vermajor, $verminor, $verextra) = $ver;
  if ($vermajor == 3 && $verminor == 7) {
    echo "Updating database to version 3.08....<br /><br />\n";
    include_once("includes/adminupdate.php");
    update308();
  }

  $ver = currentver();
  if ($ver == -1) {
    menu_bottom();
    exit;
  }
  list($vermajor, $verminor, $verextra) = $ver;
  if ($vermajor == 3 && $verminor == 8) {
    echo "Updating database to version 3.09....<br /><br />\n";
    include_once("includes/adminupdate.php");
    update309();
  }

  menu_bottom();
  exit;
}

function currentver() {
  global $dbpre;

  $result = sql_query("SELECT value FROM {$dbpre}config WHERE conf='Version' LIMIT 1");
  if (!$result)
    return -1;

  $verextra = "";
  $vermajor = $verminor = 0;
  $row = sql_fetch_row($result);
  sql_free_result($result);
  $ver = $row[0];
  if (($pos = strpos($ver, ".")) !== FALSE)
    $vermajor = substr($ver, 0, $pos);
  if (is_numeric(substr($ver, $pos + 1, 1)) && is_numeric(substr($ver, $pos + 2, 1)))
    $verminor = substr($ver, $pos + 1, 2);
  if (strlen($ver) > $pos + 2)
    $verextra = substr($ver, $pos + 3);

  return array($vermajor, $verminor, $verextra);
}

//=============================================================================
//========== Load Pass ========================================================
//=============================================================================
function loadpass() {
  global $dbpre, $AdmPass;

  $result = sql_query("SELECT value FROM {$dbpre}config WHERE conf='AdminPass' LIMIT 1");
  if ($result) {
    $row = sql_fetch_row($result);
    sql_free_result($result);
    $AdmPass = $row[0];
    return;
  }

  include_once("includes/admininit.php");
  initcheck(3);
}

//=============================================================================
//========== Parse Logs =======================================================
//=============================================================================
function logparse() {
  global $dbpre;

  $result = sql_query("SELECT value FROM {$dbpre}config WHERE conf='UpdatePass' LIMIT 1");
  if ($result) {
    $row = sql_fetch_row($result);
    sql_free_result($result);
    $UpdatePass = $row[0];
  }
  else
    return;

  menu_top();

  require("logs.php");

  menu_bottom();
  exit;
}

//=============================================================================
//========== Login Screen =====================================================
//=============================================================================
function login() {
  header('Content-Type:text/html; charset=utf-8');

  echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>UTStatsDB Admin Login</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="icon" href="resource/uicon.png" type="image/png" />
  <style type="text/css">
    .sidebox {border: 1px #000000 solid}
  </style>
  <script language="Javascript" type="text/JavaScript">
    function setFocus() {
      document.login_form.Pass.focus();
    }
  </script>
</head>

<body text="#101031" onload="setFocus()">

<form name="login_form" method="post" action="admin.php">
  <input type="hidden" name="Mode" value="Login" />
  <table bgcolor="#cccccc" cellspacing="0" cellpadding="5" class="sidebox" width="250">
    <tr>
      <td bgcolor="#000066" align="center" colspan="2">
        <b><font color="#ffffff">UTStatsDB Admin Login</font></b>
      </td>
    </tr>
    <tr>
      <td align="right">
        <b>Password:</b>
      </td>
      <td align="left">
        <input type="password" name="Pass" maxlength="25" />
      </td>
    </tr>
    <tr>
      <td align="center" colspan="2">
        <input type="submit" name="ModeS" value="Login" />
      </td>
    </tr>
  </table>
</form>

</body>
</html>

EOF;
  exit;
}

function invalid_login() {
  header('Content-Type:text/html; charset=utf-8');

  echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>Invalid Login</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="icon" href="resource/uicon.png" type="image/png" />
</head>
<body text="#ff0000">

<br />
<p><font size="+1"><center><b>Invalid Login!</b></center></font></p>

</body>
</html>

EOF;
  exit;
}

function auth_required() {
  header('Content-Type:text/html; charset=utf-8');

  echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>Authorization Required</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="icon" href="resource/uicon.png" type="image/png" />
</head>
<body text="#ff0000">

<br />
<p><font size="+1"><center><b>Authorization Required!</b></center></font></p>

</body>
</html>

EOF;
  exit;
}

?>
