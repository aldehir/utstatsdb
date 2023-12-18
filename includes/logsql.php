<?php

/*
    UTStatsDB
    Copyright (C) 2002-2011  Patrick Contreras / Paul Gallier
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

if (!isset($pageStats)) {
  $pageStats = array(
    "pagestart" => microtime(true),
    "querytime" => 0.0,
    "querycount" => 0,
    "cachehits" => 0
  );
}

function sql_connect() {
  global $SQLdb, $SQLus, $SQLpw, $dbtype;

  try {
    $link = new PDO($SQLdb, $SQLus, $SQLpw);
    return $link;
  } catch(PDOException $e) {
    print 'Database connection failure: '.$e->getMessage();
  }
}

function sql_query($query) {
  global $dbtype, $pageStats;

  $_start = microtime(true);

  try {
    $link = sql_connect();

    switch (strtolower($dbtype)) {
      case "mssql":
        $query = mssql_queryfix($query);
      case "mysql":
      case "mysqli":
      case "sqlite":
        $result = $link->query($query);
        break;
      default:
        echo "Database type error.";
        exit;
    }

    $link = NULL;

    $pageStats["querycount"]++;
    $pageStats["querytime"] += microtime(true) - $_start;

    return $result;
  } catch(PDOException $e) {
    print "Database query failure for query: \n" . $query . " \n". $e->getMessage();
  }
}

function sql_queryn($link, $query) {
  global $uselimit, $dbtype, $pageStats;

  $_start = microtime(true);

  if (!isset($uselimit) || !$uselimit) { // Remove LIMIT 1 from UPDATE queries for unsupported versions
    if (!strcmp(substr($query, 0, 6), "UPDATE") && !strcmp(substr($query, -7), "LIMIT 1"))
      $query = substr($query, 0, -7);
  }

  // NOTE previously used unbuffered queries

  try {
    switch (strtolower($dbtype)) {
      case "mssql":
        $query = mssql_queryfix($query);
        $query = str_replace("\'", "' + char(39) + '", $query);
      case "sqlite":
        $query = preg_replace("(USE INDEX \(.*\))", "", $query);
        $query = str_replace("\'", "&#39;", $query);
      case "mysql":
      case "mysqli":
        $result = $link->query($query);
        break;
      default:
        echo "Database type error.\n";
        exit;
    }

    $pageStats["querycount"]++;
    $pageStats["querytime"] += microtime(true) - $_start;

    return $result;
  } catch(PDOException $e) {
    print "Database query failure for query: \n" . $query . " \n". $e->getMessage();
  }
}

function sql_querynb($link, $query) {
  global $uselimit, $dbtype, $pageStats;

  $_start = microtime(true);

  if (!isset($uselimit) || !$uselimit) { // Remove LIMIT 1 from UPDATE queries
    if (!strcmp(substr($query, 0, 6), "UPDATE") && !strcmp(substr($query, -7), "LIMIT 1"))
      $query = substr($query, 0, -7);
  }

  // NOTE previous implementation differed from `sql_querynb` by not using buffered queries

  try {
    switch (strtolower($dbtype)) {
      case "mssql":
        $query = mssql_queryfix($query);
        $query = str_replace("\'", "' + char(39) + '", $query);
      case "sqlite":
        $query = preg_replace("(USE INDEX \(.*\))", "", $query);
        $query = str_replace("\'", "&#39;", $query);
      case "mysql":
      case "mysqli":
        $result = $link->query($query);
        break;
      default:
        echo "Database type error.\n";
        exit;
    }

    $pageStats["querycount"]++;
    $pageStats["querytime"] += microtime(true) - $_start;

    return $result;
  } catch(PDOException $e) {
    print "Database query failure for query: \n" . $query . " \n". $e->getMessage();
  }
}

function sql_fetch_row($result) {
  return $result->fetch(PDO::FETCH_NUM);
}

function sql_fetch_assoc($result) {
  return $result->fetch(PDO::FETCH_ASSOC);
}

function sql_fetch_array($result) {
  return $result->fetch(PDO::FETCH_BOTH);
}

function sql_free_result($result) {
  $result->closeCursor();
}

function sql_insert_id($link) {
  return $link->lastInsertId();
}

function sql_num_rows($result) {
  $count = $result->rowCount();

  // this is a bit inefficient, but is the most straight-forward way of counting
  // rows across all drivers if rowCount isn't supported
  if ($count == 0 && preg_match("/SELECT\s.+\sFROM/i", $result->queryString) == 1) {
    $countQuery = preg_replace("/SELECT\s(.+)\sFROM/i", "SELECT count(*) FROM", $result->queryString);
    $countResult = sql_query($countQuery);
    $count = sql_fetch_row($countResult);
  }

  return $count;
}

function sql_affected_rows($result) {
  return $result->rowCount();
}

function sql_exec_file($link, $filename) {
  global $dbtype, $dbpre;

  switch (strtolower($dbtype)) {
    case "mysql":
    case "mysqli":
      $tdir = "mysql";
      break;
    case "sqlite":
      $tdir = "sqlite";
      break;
    case "mssql":
      $tdir = "mssql";
      break;
    default:
      exit("Unknown DB type $dbtype");
  }

  $return = array(
    "errors" => 0,
    "messages" => array()
  );

  $fname = "tables/" . $tdir . "/" .$filename . ".sql";
  $return["messages"][] = "Creating table '{$dbpre}{$filename}'....";
  if (file_exists($fname)) {
    $sqldata = file($fname);
    $done = 0;
    $qstring = "";
    while(!$done && $row = current($sqldata)) {
      $qstring .= $row;
      next($sqldata);

      if (strstr($qstring, ";")) {
        $qstring = trim($qstring, "\t\n\r\0;");
        $qstring = str_replace("\n", "", $qstring);
        $done = 1;
      }
    }

    // Replace all occurances of %dbpre% with $dbpre
    $qstring = str_replace("%dbpre%", "$dbpre", $qstring);
    $result = sql_queryn($link, $qstring);
    if ($result)
      $return["messages"][] = "Successful.<br />\n";
    else {
      $return["messages"][] = "<font color=\"#e00000\">Failed: </font>".implode(": ", sql_error($link))."<br />\n";
      $return["errors"]++;
    }

    next($sqldata);
    while($row = current($sqldata)) {
      $qstring = $row;
      next($sqldata);

      if (strlen($qstring) > 10 && substr($qstring, 0, 1) != "#") {
        $qstring = trim($qstring, "\t\n\r\0;");
        $qstring = str_replace("\n", "", $qstring);

        // Replace all occurances of %dbpre% with $dbpre
        $qstring = str_replace("%dbpre%", "$dbpre", $qstring);
        $result = sql_queryn($link, $qstring);
        if (!$result) {
          if (substr($qstring, 0, 12) == "CREATE INDEX") {
            $return["messages"][] = "<font color=\"#e00000\">Error creating index: $qstring</font><br />\n";
          } else {
            $return["messages"][] = "<font color=\"#e00000\">Error loading table data: $qstring</font><br />\n";
          }
          $return["errors"]++;
        }
      }
    }
  } else {
    $return["messages"][] = "<font color=\"#e00000\"><b>File not found!</b></font><br />\n";
  }

  return $return;
}

function sql_close($link) {
  $link = NULL;
}

function sql_addslashes($str) {
  global $dbtype;

  switch (strtolower($dbtype)) {
    case "mysql":
    case "mysqli":
    case "sqlite":
      $str = addslashes($str);
      break;
    case "mssql":
      $str = str_replace("'", "''", $str);
      break;
    default:
      echo "Database type error.\n";
      exit;
      break;
  }
  return $str;
}

function sql_error($link) {
  return $link->errorInfo(); // TODO turn into string
}

function from_unixtime($unixtime) {
  return "'".date('Y-m-d H:i:s', $unixtime)."'";
}

function sql_show_tables($link) {
  global $dbtype;

  $tables = [];

  switch (strtolower($dbtype)) {
    case "mysql":
    case "mysqli":
      $result = sql_querynb($link, "SHOW TABLES");
      while ($row = sql_fetch_row($result)) $tables[] = $row[0];
      break;
    case "sqlite":
      $result = sql_querynb($link, "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
      while ($row = sql_fetch_row($result)) $tables[] = $row[0];
      break;
    case "mssql":
      $result = sql_querynb($link, "EXEC sp_tables");
      while ($row = sql_fetch_row($result)) {
        if ($row[3] == "TABLE") $tables[] = $row[2];
      }
      break;
    default:
      echo "Database type error.\n";
      exit;
      break;
  }

  return $tables;
}

// Modified from code by Jon Jensen
function sqlite_alter_table($link, $table, $alterdefs) {
  $result = sqlite_query($link, "SELECT sql,name,type FROM sqlite_master WHERE tbl_name = '".$table."' ORDER BY type DESC");

  if (sqlite_num_rows($result) > 0) {
    $row = sqlite_fetch_array($result);
    $tmpname = 't'.time();
    $origsql = trim(preg_replace("/[\s]+/"," ",str_replace(",",", ",preg_replace("/[\(]/","( ",$row['sql'],1))));
    $createtemptableSQL = 'CREATE TEMPORARY '.substr(trim(preg_replace("'".$table."'",$tmpname,$origsql,1)),6);
    $createindexsql = array();
    $i = 0;
    $defs = preg_split("/[,]+/",$alterdefs,-1,PREG_SPLIT_NO_EMPTY);
    $prevword = $table;

    // $oldcols = preg_split("/[,]+/",substr(trim($createtemptableSQL),strpos(trim($createtemptableSQL),'(')+1),-1,PREG_SPLIT_NO_EMPTY);
    $oldcols = array();
    $tmpcols = trim($origsql);
    $p = strpos($tmpcols, "(");
    $tmpcols = substr($tmpcols, $p + 1);
    $tmpcols = trim($tmpcols);
    $p = strpos($tmpcols, ",");

    while ($p != FALSE) {
      $n = 0;
      if (substr($tmpcols, $p - 2, 1) != "(" && substr($tmpcols, $p - 3, 1) != "(") {
        $oldcols[] = substr($tmpcols, 0, $p);
        $tmpcols = substr($tmpcols, $p + 1);
      }
      else
        $n = $p + 1;
      $p = strpos($tmpcols, ",", $n);
    }

    $newcols = array();

    for ($i=0;$i<sizeof($oldcols);$i++) {
      $colparts = preg_split("/[\s]+/",$oldcols[$i],-1,PREG_SPLIT_NO_EMPTY);
      $oldcols[$i] = $colparts[0];
      $newcols[$colparts[0]] = $colparts[0];
    }

    $newcolumns = '';
    $oldcolumns = '';
    reset($newcols);

    foreach ($newcols as $key => $val) {
      $newcolumns .= ($newcolumns?', ':'').$val;
      $oldcolumns .= ($oldcolumns?', ':'').$key;
    }

    $copytotempsql = 'INSERT INTO '.$tmpname.'('.$newcolumns.') SELECT '.$oldcolumns.' FROM '.$table;
    $dropoldsql = 'DROP TABLE '.$table;
    $createtesttableSQL = $createtemptableSQL;

    foreach ($defs as $def) {
      $defparts = preg_split("/[\s]+/", $def, -1, PREG_SPLIT_NO_EMPTY);
      $action = strtolower($defparts[0]);
      switch($action) {
      case 'add':
        if (sizeof($defparts) <= 2) {
          trigger_error('near "'.$defparts[0].($defparts[1]?' '.$defparts[1]:'').'": syntax error',E_USER_WARNING);
          return false;
        }
        $createtesttableSQL = substr($createtesttableSQL,0,strlen($createtesttableSQL)-1).',';
        for ($i = 1; $i < sizeof($defparts); $i++)
          $createtesttableSQL.=' '.$defparts[$i];
        $createtesttableSQL.=')';
        break;
      case 'change':
        if (sizeof($defparts) <= 3) {
          trigger_error('near "'.$defparts[0].($defparts[1]?' '.$defparts[1]:'').($defparts[2]?' '.$defparts[2]:'').'": syntax error',E_USER_WARNING);
          return false;
        }
        if ($severpos = strpos($createtesttableSQL,' '.$defparts[1].' ')) {
          if ($newcols[$defparts[1]] != $defparts[1]) {
            trigger_error('unknown column "'.$defparts[1].'" in "'.$table.'"',E_USER_WARNING);
            return false;
          }
          $newcols[$defparts[1]] = $defparts[2];
          $nextcommapos = strpos($createtesttableSQL,',',$severpos);
          $insertval = '';
          for ($i=2;$i<sizeof($defparts);$i++)
            $insertval.=' '.$defparts[$i];
          if ($nextcommapos)
            $createtesttableSQL = substr($createtesttableSQL,0,$severpos).$insertval.substr($createtesttableSQL,$nextcommapos);
          else
            $createtesttableSQL = substr($createtesttableSQL,0,$severpos-(strpos($createtesttableSQL,',')?0:1)).$insertval.')';
        }
        else {
          trigger_error('unknown column "'.$defparts[1].'" in "'.$table.'"',E_USER_WARNING);
          return false;
        }
        break;
      case 'drop':
        if (sizeof($defparts) < 2) {
          trigger_error('near "'.$defparts[0].($defparts[1]?' '.$defparts[1]:'').'": syntax error',E_USER_WARNING);
          return false;
        }
        if ($severpos = strpos($createtesttableSQL,' '.$defparts[1].' ')) {
          $nextcommapos = strpos($createtesttableSQL,',',$severpos);
          if ($nextcommapos)
            $createtesttableSQL = substr($createtesttableSQL,0,$severpos).substr($createtesttableSQL,$nextcommapos + 1);
          else
            $createtesttableSQL = substr($createtesttableSQL,0,$severpos-(strpos($createtesttableSQL,',')?0:1) - 1).')';
          unset($newcols[$defparts[1]]);
        }
        else {
          trigger_error('unknown column "'.$defparts[1].'" in "'.$table.'"',E_USER_WARNING);
          return false;
        }
        break;
      default:
        trigger_error('near "'.$prevword.'": syntax error',E_USER_WARNING);
        return false;
      }
      $prevword = $defparts[sizeof($defparts)-1];
    }

    // Generates a test table simply to verify that the columns specifed are valid in an sql statement
    $result = sqlite_query($link, $createtesttableSQL);
    if (!$result) {
      print("SQLite Error creating test table.<br>\n");
      return false;
    }
    $droptempsql = 'DROP TABLE '.$tmpname;
    $result = sqlite_query($link, $droptempsql);
    if (!$result) {
      print("SQLite Error dropping test table.<br>\n");
      return false;
    }

    $createnewtableSQL = 'CREATE '.substr(trim(preg_replace("'".$tmpname."'",$table,$createtesttableSQL,1)),17);
    $newcolumns = '';
    $oldcolumns = '';
    reset($newcols);

    foreach ($newcols as $key => $val) {
      $newcolumns .= ($newcolumns?', ':'').$val;
      $oldcolumns .= ($oldcolumns?', ':'').$key;
    }
    $copytonewsql = 'INSERT INTO '.$table.'('.$newcolumns.') SELECT '.$oldcolumns.' FROM '.$tmpname;

    $result = sqlite_query($link, $createtemptableSQL); // Create temp table
    if (!$result) {
      print("SQLite Error creating temp table.<br>\n");
      return false;
    }
    $result = sqlite_query($link, $copytotempsql); // Copy to table
    if (!$result) {
      print("SQLite Error copying to temp table.<br>\n");
      return false;
    }
    $result = sqlite_query($link, $dropoldsql); // Drop old table
    if (!$result) {
      print("SQLite Error dropping old table.<br>\n");
      return false;
    }

    $result = sqlite_query($link, $createnewtableSQL); // Recreate original table
    if (!$result) {
      print("SQLite Error creating original table.<br>\n");
      return false;
    }
    $result = sqlite_query($link, $copytonewsql); // Copy back to original table
    if (!$result) {
      print("SQLite Error copying to original table.<br>\n");
      return false;
    }
    $result = sqlite_query($link, $droptempsql); // Drop temp table
    if (!$result) {
      print("SQLite Error dropping temp table.<br>\n");
      return false;
    }
  }
  else {
    trigger_error('no such table: '.$table,E_USER_WARNING);
    return false;
  }

  return true;
}

function mssql_queryfix($query) {
  // Convert from LIMIT to TOP in SELECT queries
  if (!strcmp(substr($query, 0, 6), "SELECT") && strstr($query, " LIMIT ") !== false) {
  	if (($pl = strpos($query, " LIMIT ")) !== false) {
  	  $lim = substr($query, $pl + 7);
  	  if (($pc = strpos(substr($query, $pl + 7), ",")) === false)
        $query = "SELECT TOP $lim " . substr($query, 6, $pl - 6);
      else {
        $lim1 = intval(substr($lim, 0, $pc));
        $lim2 = intval(substr($lim, $pc + 1));
        $lim1 += $lim2;
        $query = "SELECT TOP $lim2 * FROM (SELECT TOP $lim1 " . substr($query, 6, $pl - 6) . ") AS mslimit";
      }
    }
  }

  // Convert FROM_UNIXTIME
  if (($ut = strpos($query, "FROM_UNIXTIME(")) !== false) {
  	if (($eq = strpos(substr($query, $ut + 14), ")")) !== false) {
  	  $dt = date('Y-m-d H:i:s', substr($query, $ut + 14, $eq));
  	  $query = substr($query, 0, $ut)."'".$dt."'".substr($query, $ut + $eq + 15);
    }
  }

  // Fix date queries
  if (!strcmp(substr($query, 0, 6), "SELECT") && strstr($query, "DATEPART") === FALSE) {
    $daterows = array("cn_ctime", "cn_dtime", "mp_lastmatch", "gm_init", "gm_start", "sv_lastmatch", "tl_chfragssg_date", "tl_chkillssg_date", "tl_chdeathssg_date", "tl_chsuicidessg_date", "tl_chcarjacksg_date", "tl_chroadkillssg_date", "tl_chcpcapturesg_date", "tl_chflagcapturesg_date", "tl_chflagreturnsg_date", "tl_chflagkillsg_date", "tl_chbombcarriedsg_date", "tl_chbombtossedsg_date", "tl_chbombkillsg_date", "tl_chnodeconstructedsg_date", "tl_chnodeconstdestroyedsg_date", "tl_chnodedestroyedsg_date", "wp_chkillssg_dt", "wp_chdeathssg_dt", "wp_chdeathshldsg_dt", "wp_chsuicidessg_dt");
    foreach ($daterows as $daterow) {
      if (($p = strpos($query, $daterow)) !== false && substr($query, $p + strlen($daterow), 1) != "=") {
        if (strstr($query, "MAX(") === false)
          $query = substr($query, 0, $p) . "CONVERT(char(19), " . substr($query, $p, strlen($daterow)) . ", 20) AS $daterow" . substr($query, $p + strlen($daterow));
        else
          $query = substr($query, 0, $p) . "CONVERT(char(19), " . substr($query, $p, strlen($daterow)) . ", 20)" . substr($query, $p + strlen($daterow));
      }
    }
  }

  // Strip USE INDEX
  if (($ui = strpos($query, "USE INDEX")) !== false) {
    if (($ep = strpos(substr($query, $ui + 9), ")")) !== false)
      $query = substr($query, 0, $ui) . substr($query, $ui + $ep + 10);
  }

  return $query;
}

?>
