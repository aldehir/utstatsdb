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

$pageStats = array(
  "pagestart" => microtime(true),
  "querytime" => 0.0,
  "querycount" => 0,
  "cachehits" => 0
);

require("statsdb.inc.php"); // Set to the location of your account settings file
require("logsql.php");
load_config();
require("language/lang_{$lang}.php");

function check_get(&$store, $val)
{
  if (isset($_POST["$val"])) {
    $store = $_POST["$val"];
  }
  else if (isset($_GET["$val"])) {
    $store = $_GET["$val"];
  }
}

// Convert Time Format
function displayTime($seconds, $offset)
{
  return displayTimeMins(floatval($seconds) / (60.0 * $offset));
}

function displayTimeMins($minutes)
{
  $neg = $minutes < 0;

  $minutes = abs($minutes);
  $h = floor($minutes / 60);
  $m = $minutes % 60;
  $s = floor(($minutes - floor($minutes)) * 60);

  if ($h) {
    return sprintf(($neg ? "-" : "") . "%dh&nbsp;%02dm", $h, $m);
  } else {
    return sprintf(($neg ? "-" : "") . "%dm&nbsp;%02ds", $m, $s);
  }
}

function stripspecialchars($str)
{
  $nstr = htmlspecialchars(preg_replace("/\x1b.../", "", $str));
  return $nstr;
}

function load_config()
{
  global $dbpre, $menulinks, $menu_url, $menu_descr;

  $result = sql_query("SELECT conf,value FROM {$dbpre}config");
  if (!$result) {
    echo "Error loading configuration.<br />\n";
    exit;
  }
  while ($row = sql_fetch_row($result))
  {
    global ${$row[0]};
    ${$row[0]} = $row[1];
  }
  sql_free_result($result);

  $result = sql_query("SELECT title_msg FROM {$dbpre}configset LIMIT 1");
  if (!$result) {
    echo "Error loading configuration.<br />\n";
    exit;
  }
  $row = sql_fetch_row($result);
  global $title_msg;
  $title_msg = $row[0];
  sql_free_result($result);

  $result = sql_query("SELECT url,descr FROM {$dbpre}configmenu");
  if (!$result) {
    echo "Error loading configuration.<br />\n";
    exit;
  }
  $menulinks = 0;
  while ($row = sql_fetch_row($result))
  {
    if (strlen($row[0]) && strlen($row[1])) {
      $menu_url[$menulinks] = htmlspecialchars($row[0]);
      $menu_descr[$menulinks++] = htmlspecialchars($row[1]);
    }
  }
  sql_free_result($result);

  if (!isset($lang) || strlen($lang) != 2) {
    global $lang;
    $lang = "en";
  }
  else
    $lang = strtolower($lang);
}

function formatdate($dt, $tm)
{
  global $dateformat;

  if ($tm) {
    switch ($dateformat) {
      case 1: return date('D, M d Y, G:i:s', $dt); break;
      case 2: return date('D, d M Y, G:i:s', $dt); break;
      default: return date('D, M d Y, g:i:s A', $dt);
    }
  } else {
    switch ($dateformat) {
      case 1: return date('D, M d Y', $dt); break;
      case 2: return date('D, d M Y', $dt); break;
      default: return date('D, M d Y', $dt);
    }
  }
}

function unhtmlspecialchars($string)
{
  $string = str_replace('&amp;', '&', $string);
  $string = str_replace('&quot;', '"', $string);
  $string = str_replace('&#039;', '\'', $string);
  $string = str_replace('&lt;', '<', $string);
  $string = str_replace('&gt;', '>', $string);
  return $string;
}

if (!isset($layout) || !$layout) {
  echo "Configuration error.<br />\n";
  exit;
}

// Team colors
$teamcolor = array("Red", "Blue", "Green", "Gold");
$teamcolorbar = array(2, 1, 3, 4);
$teamclass = array("redteam", "blueteam", "greenteam", "goldteam");
$teamchat = array("chatred", "chatblue", "chatgreen", "chatgold");
$teamscore = array("redteamscore", "blueteamscore", "greenteamscore", "goldteamscore");

header('Content-Type:text/html; charset=utf-8');

require('header.inc.php');

?>
