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
  "querycount" => 0
);

require("statsdb.inc.php"); // Set to the location of your account settings file
require("logsql.php");
$magicrt = get_magic_quotes_runtime();
load_config();
require("language/lang_{$lang}.php");

function check_get(&$store, $val)
{
  $magic = get_magic_quotes_gpc();
  if (isset($_POST["$val"])) {
    if ($magic)
      $store = stripslashes($_POST["$val"]);
    else
      $store = $_POST["$val"];
  }
  else if (isset($_GET["$val"])) {
    if ($magic)
      $store = stripslashes($_GET["$val"]);
    else
      $store = $_GET["$val"];
  }
}

function dtime($tm) // Convert Time Format
{
  $t = intval(round($tm / 100));
  $t1 = intval(floor($t / 3600));
  $t2 = intval(floor(($t - ($t1 * 3600)) / 60));
  $t3 = intval(floor($t - ($t1 * 3600) - ($t2 * 60)));
  if ($t1)
    $time = sprintf("%d:%02d:%02d", $t1, $t2, $t3);
  else
    $time = sprintf("%d:%02d", $t2, $t3);
  return $time;
}

function stripspecialchars($str)
{
  $nstr = htmlspecialchars(preg_replace("/\x1b.../", "", $str));
  return $nstr;
}

function load_config()
{
  global $dbpre, $menulinks, $menu_url, $menu_descr, $magicrt;

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
  $title_msg = $magicrt ? stripslashes($row[0]) : $row[0];
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
      $menu_url[$menulinks] = htmlspecialchars($magicrt ? stripslashes($row[0]) : $row[0]);
      $menu_descr[$menulinks++] = htmlspecialchars($magicrt ? stripslashes($row[1]) : $row[1]);
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
      case 1: return date('D, M d Y \a\t G:i:s', $dt); break;
      case 2: return date('D, d M Y \a\t G:i:s', $dt); break;
      default: return date('D, M d Y \a\t g:i:s A', $dt);
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
