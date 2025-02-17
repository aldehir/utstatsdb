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

require("includes/main.inc.php");

$matchnum = -1;
$plr = -1;
check_get($matchnum, "match");
check_get($plr, "player");
if (!is_numeric($matchnum))
  $matchnum = -1;
if (!is_numeric($plr))
  $plr = -1;
if ($matchnum <= 0 || $plr < 0) {
  echo "Run from the main index program.<br />\n";
  exit;
}

$link = sql_connect();

$result = sql_queryn($link, "SELECT m.*, 
                               tp_num,tp_desc,tp_type,tp_team,
                               mp_name,
                               sv_name,sv_shortname,sv_admin,sv_email,
                               p.*, coalesce(plr_name, 'Player ' || p.gp_num) as plr_name
                             FROM {$dbpre}matches m
                               LEFT JOIN {$dbpre}type ON tp_num=gm_type
                               LEFT JOIN {$dbpre}maps ON mp_num=gm_map
                               LEFT JOIN {$dbpre}servers ON sv_num=gm_server
                               LEFT JOIN {$dbpre}gplayers p ON p.gp_match=gm_num AND p.gp_num=$plr
                               LEFT JOIN {$dbpre}players ON pnum=gp_pnum
                             WHERE gm_num=$matchnum 
                             LIMIT 1");

if (!$result) {
  echo "Match database error.<br />\n";
  exit;
}
$row = sql_fetch_assoc($result);
sql_free_result($result);
if (!$row) {
  echo "Match not found in database.<br />\n";
  exit;
}
foreach ($row as $key => $val)
  ${$key} = $val;

$gametype = $tp_desc;
$gametval = $tp_type;
$teams = $tp_team;

$mapname = stripspecialchars($mp_name);

if ($useshortname && $sv_shortname != "") $servername = stripspecialchars($sv_shortname);
else $servername = stripspecialchars($sv_name);
$serveradmin = stripspecialchars($sv_admin);
$serveremail = stripspecialchars($sv_email);

$start = strtotime($gm_start);
$init = strtotime($gm_init);
$delay = $start - strtotime($gm_init);
$matchdate = formatdate($start, 1);
$matchinit = formatdate($init, 1);

$gp_name = stripspecialchars($plr_name);

$kills = $gp_kills0 + $gp_kills1 + $gp_kills2 + $gp_kills3;
$deaths = $gp_deaths0 + $gp_deaths1 + $gp_deaths2 + $gp_deaths3;
$suicides = $gp_suicides0 + $gp_suicides1 + $gp_suicides2 + $gp_suicides3;
$frags = $kills - $suicides;
$ptime = floatval(($gp_time0 + $gp_time1 + $gp_time2 + $gp_time3) / $gm_timeoffset);

$pw = (isset($password) && $password) ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$stats = (isset($gamestats) && $gamestats) ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$trans = $gm_translocator ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$mapvoting = $gm_mapvoting ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$kickvoting = $gm_kickvoting ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$balanceteams = $gm_balanceteams ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$playersbalance = $gm_playersbalanceteams ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$linksetup = stripspecialchars($gm_linksetup);
$gamespeed = isset($gm_gamespeed) ? (floatval($gm_gamespeed) * 100.0)."%" : "100%";
$healthforkills = $gm_healthforkills ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$allowsuperweapons = $gm_allowsuperweapons ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$camperalarm = $gm_camperalarm ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$allowpickups = $gm_allowpickups ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$allowadrenaline = $gm_allowadrenaline ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$fullammo = $gm_fullammo ? "{$LANG_ENABLED}" : "{$LANG_DISABLED}";
$tlabel = $gametval > 1 ? "{$LANG_SCORE}" : "{$LANG_FRAGS}";

if ($gm_fraglimit && $gm_timelimit)
  $limits = "$gm_fraglimit $tlabel / $gm_timelimit {$LANG_MINUTES}";
else if ($gm_fraglimit)
  $limits = "$gm_fraglimit $tlabel";
else if ($gm_timelimit)
  $limits = "$gm_timelimit {$LANG_MINUTES}";
else
  $limits = "No Limit";

switch ($gm_difficulty) {
  case 0: $difficulty = "{$LANG_NOVICE}"; break;
  case 1: $difficulty = "{$LANG_AVERAGE}"; break;
  case 2: $difficulty = "{$LANG_EXPERIENCED}"; break;
  case 3: $difficulty = "{$LANG_SKILLED}"; break;
  case 4: $difficulty = "{$LANG_ADEPT}"; break;
  case 5: $difficulty = "{$LANG_MASTERFUL}"; break;
  case 6: $difficulty = "{$LANG_INHUMAN}"; break;
  case 7: $difficulty = "{$LANG_BOTGODLIKE}"; break;
  default: $difficulty = "";
}

echo <<<EOF
<center>
<table cellpadding="1" cellspacing="2" border="0" width="720">
  <tr>
    <td class="heading" align="center">Individual Match Stats for $gp_name <a href="playerstats.php?player=$gp_pnum" class="heading">[ID=$gp_pnum]</a></td>
  </tr>
</table>
<br />
<table cellpadding="1" cellspacing="2" border="0" width="650" class="box">
  <tr>
    <td class="heading" colspan="4" align="center">Match/Player Information</td>
  </tr>
  <tr>
    <td class="dark" align="center" width="100">{$LANG_MATCHDATE}</td>
    <td class="grey" align="center" width="220" title="{$LANG_MATCHINIT}: $matchinit">$matchdate</td>
    <td class="dark" align="center" width="105">{$LANG_SERVER}</td>
    <td class="grey" align="center"><a class="grey" href="serverstats.php?server=$gm_server">$servername</a></td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_GAMETYPE}</td>
    <td class="grey" align="center">$gametype</td>
    <td class="dark" align="center">{$LANG_ADMINNAME}</td>
    <td class="grey" align="center">$serveradmin</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_MAPNAME}</td>
    <td class="grey" align="center"><a class="grey" href="mapstats.php?map=$gm_map">$mapname</a></td>
    <td class="dark" align="center">{$LANG_ADMINEMAIL}</td>
    <td class="grey" align="center">$serveremail</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_GLOBALSTATS}</td>
    <td class="grey" align="center">$stats</td>
    <td class="dark" align="center">{$LANG_GAMESPEED}</td>
    <td class="grey" align="center">$gamespeed</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_MATCHLIMIT}</td>
    <td class="grey" align="center">$limits</td>
    <td class="dark" align="center">{$LANG_NUMPLAYERS}</td>
    <td class="grey" align="center">$gm_numplayers</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_DIFFICULTY}</td>
    <td class="grey" align="center">$difficulty</td>
    <td class="dark" align="center">{$LANG_TRANSLOCATOR}</td>
    <td class="grey" align="center">$trans</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_MAPVOTING}</td>
    <td class="grey" align="center">$mapvoting</td>
    <td class="dark" align="center">{$LANG_KICKVOTING}</td>
    <td class="grey" align="center">$kickvoting</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_FULLAMMO}</td>
    <td class="grey" align="center">$fullammo</td>
    <td class="dark" align="center">{$LANG_HEALTHFORKILLS}</td>
    <td class="grey" align="center">$healthforkills</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_CAMPERALARM}</td>
    <td class="grey" align="center">$camperalarm</td>
    <td class="dark" align="center">{$LANG_SUPERWEAPONS}</td>
    <td class="grey" align="center">$allowsuperweapons</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_PICKUPS}</td>
    <td class="grey" align="center">$allowpickups</td>
    <td class="dark" align="center">{$LANG_ADRENALINE}</td>
    <td class="grey" align="center">$allowadrenaline</td>
  </tr>

EOF;

if ($teams) {
  echo <<<EOF
  <tr>
    <td class="dark" align="center">{$LANG_BALANCETEAMS}</td>
    <td class="grey" align="center">$balanceteams</td>
    <td class="dark" align="center">{$LANG_PLAYERSBALANCE}</td>
    <td class="grey" align="center">$playersbalance</td>
  </tr>

EOF;

  if ($gametval == 6) {
    $SpecialText = "{$LANG_LINKSETUP}";
    $special = $linksetup;
  }
  else if ($gametval == 9 && $gm_logger == 1) {
    $SpecialText = "{$LANG_WAVES}";
    $special = $gm_maxwave;
  }
  else if ($gametval == 18) {
  	$SpecialText = "{$LANG_OVERTIME}";
  	$special = "$gm_overtime {$LANG_MINUTES}";
  }
  else {
    $SpecialText = "&nbsp;";
    $special = "&nbsp;";
  }

  echo <<<EOF
  <tr>
    <td class="dark" align="center">{$LANG_FRIENDLYFIRE}</td>
    <td class="grey" align="center">$gm_friendlyfirescale</td>
    <td class="dark" align="center">$SpecialText</td>
    <td class="grey" align="center">$special</td>
  </tr>

EOF;
}

echo <<<EOF
  <tr>
    <td class="dark" align="center">Netspeed</td>
    <td class="grey" align="center">$gp_netspeed</td>
    <td class="dark" align="center">Avg. Ping</td>
    <td class="grey" align="center">$gp_ping ms</td>
  </tr>
  <tr>
    <td class="dark" align="center">{$LANG_MUTATORS}</td>
    <td class="grey" align="center" colspan="3">$gm_mutators</td>
  </tr>
</table>

EOF;

//=============================================================================
//========== Bot Stats ========================================================
//=============================================================================
if ($gp_bot) {
  $result = sql_queryn($link, "SELECT * FROM {$dbpre}gbots WHERE gb_match=$matchnum AND gb_bot=$plr LIMIT 1");
  if (!$result) {
    echo "Bot database error.<br />\n";
    exit;
  }
  $row = sql_fetch_assoc($result);
  sql_free_result($result);
  if ($row) {
    foreach ($row as $key => $val)
      ${$key} = $val;

    switch ($gb_skill) {
      case 0:
        $skill = "Novice";
        break;
      case 1:
        $skill = "Average";
        break;
      case 2:
        $skill = "Experienced";
        break;
      case 3:
        $skill = "Skilled";
        break;
      case 4:
        $skill = "Adept";
        break;
      case 5:
        $skill = "Masterful";
        break;
      case 6:
        $skill = "Inhuman";
        break;
      case 7:
        $skill = "Godlike";
        break;
      default:
        $skill = $gb_skill;
    }

    $alertness = sprintf("%0.2f", $gb_alertness);
    $accuracy = sprintf("%0.2f", $gb_accuracy);
    $reaction = sprintf("%0.2f", $gb_reaction);
    $style = sprintf("%0.2f", $gb_style);
    $tactics = sprintf("%0.2f", $gb_tactics);
    $strafing = sprintf("%0.2f", $gb_strafing);
    $jumpiness = sprintf("%0.2f", $gb_jumpiness);
    $transloc = sprintf("%0.2f", $gb_transloc);

    // Find Favored Wepaon
    if ($gb_favorite) {
      $result = sql_queryn($link, "SELECT wp_desc FROM {$dbpre}weapons WHERE wp_num=$gb_favorite");
      if (!$result) {
        echo "Error accessing weapons data.<br />\n";
        exit;
      }
      if ($row = sql_fetch_row($result))
        $favored = $row[0];
      else
        $favored = "Unknown";
      sql_free_result($result);
    }
    else
      $favored = "Unknown";

    echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" class="box">
  <tr>
    <td class="heading" colspan="4" align="center">Bot Stats</td>
  </tr>
  <tr>
    <td class="dark" align="center" width="120">Skill Level</td>
    <td class="grey" align="center" width="150">$skill</td>
    <td class="dark" align="center" width="100">Alertness</td>
    <td class="grey" align="center" width="50">$alertness</td>
  </tr>
  <tr>
    <td class="dark" align="center">Accuracy</td>
    <td class="grey" align="center">$accuracy</td>
    <td class="dark" align="center">Reaction</td>
    <td class="grey" align="center">$reaction</td>
  </tr>
  <tr>
    <td class="dark" align="center">Style</td>
    <td class="grey" align="center">$style</td>
    <td class="dark" align="center">Tactics</td>
    <td class="grey" align="center">$tactics</td>
  </tr>
  <tr>
    <td class="dark" align="center">Strafing</td>
    <td class="grey" align="center">$strafing</td>
    <td class="dark" align="center">Jumpiness</td>
    <td class="grey" align="center">$jumpiness</td>
  </tr>
  <tr>
    <td class="dark" align="center">Favored Weapon</td>
    <td class="grey" align="center">$favored</td>
    <td class="dark" align="center">Translocation</td>
    <td class="grey" align="center">$transloc</td>
  </tr>
</table>

EOF;
  }
}

//=============================================================================
//========== Match Summary ====================================================
//=============================================================================
if ($kills + $deaths + $suicides == 0)
  $eff = "0.0";
else
  $eff = sprintf("%0.1f", ($kills / ($kills + $deaths + $suicides)) * 100.0);

if ($ptime == 0)
  $fph = "0.0";
else
  $fph = sprintf("%0.1f", $frags * (3600 / $ptime));

$ttl = displayTimeMins($ptime / 60 / ($deaths + $suicides + 1));
$time = displayTimeMins($ptime / 60.0);

if ($gp_bot)
  $nameclass = "darkbot";
else
  $nameclass = "darkhuman";

echo <<<EOF
<br />
<table cellpadding="0" cellspacing="2" border="0" width="600">
  <tr>
    <td class="heading" colspan="15" align="center">Match Summary</td>
  </tr>
  <tr>
    <td class="smheading" align="center" rowspan="2" width="40">Rank</td>
    <td class="smheading" align="center" rowspan="2" width="40">Frags</td>
    <td class="smheading" align="center" rowspan="2" width="40">Kills</td>
    <td class="smheading" align="center" rowspan="2" width="50">Deaths</td>
    <td class="smheading" align="center" rowspan="2" width="60">Suicides</td>
    <td class="smheading" align="center" rowspan="2" width="70">Efficiency</td>
    <td class="smheading" align="center" rowspan="2" width="50">Avg. FPH</td>
    <td class="smheading" align="center" rowspan="2" width="50">Avg. TTL</td>
    <td class="smheading" align="center" rowspan="2" width="45">Time</td>
    <td class="smheading" align="center" colspan="6">Sprees</td>
  </tr>
  <tr>
    <td class="smheading" align="center">K</td>
    <td class="smheading" align="center">R</td>
    <td class="smheading" align="center">D</td>
    <td class="smheading" align="center">U</td>
    <td class="smheading" align="center">G</td>
    <td class="smheading" align="center">W</td>
  </tr>
  <tr>
    <td class="grey" align="center">$gp_rank</td>
    <td class="grey" align="center">$frags</td>
    <td class="grey" align="center">$kills</td>
    <td class="grey" align="center">$deaths</td>
    <td class="grey" align="center">$suicides</td>
    <td class="grey" align="center">$eff%</td>
    <td class="grey" align="center">$fph</td>
    <td class="grey" align="center">$ttl</td>
    <td class="grey" align="center">$time</td>
    <td class="grey" align="center">$gp_spree1</td>
    <td class="grey" align="center">$gp_spree2</td>
    <td class="grey" align="center">$gp_spree3</td>
    <td class="grey" align="center">$gp_spree4</td>
    <td class="grey" align="center">$gp_spree5</td>
    <td class="grey" align="center">$gp_spree6</td>
  </tr>
</table>

EOF;

//=============================================================================
//========== Special Events ===================================================
//=============================================================================
if ($gametval != 9) {
  $result = sql_queryn($link, "SELECT se_num,se_title,se_desc,gs_total 
                               FROM {$dbpre}special 
                                 LEFT JOIN {$dbpre}gspecials ON gs_stype=se_num 
                               WHERE gs_match=$matchnum AND gs_player=$gp_pnum 
                               ORDER BY se_num");
  if (!$result) {
    echo "Player database error.<br />\n";
    exit;
  }
  $special = array();
  $numspec = 0;
  while ($row = sql_fetch_row($result)) {
    $special[$numspec]["title"] = $row[1];
    $special[$numspec]["desc"] = $row[2];
    $special[$numspec++]["total"] = $row[3] != NULL ? $row[3] : 0;
  }
  sql_free_result($result);

  if ($gm_firstblood == $gp_num)
    $fb = "Yes";
  else
    $fb = "No";

  echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0">
  <tr>
    <td class="heading" colspan="8" align="center">Special Events</td>
  </tr>
  <tr>
    <td class="smheading" align="center" width="95">Category</td>
    <td class="smheading" align="center" width="45">Value</td>
    <td class="smheading" align="center" width="100">Category</td>
    <td class="smheading" align="center" width="45">Value</td>
    <td class="smheading" align="center" width="95">Category</td>
    <td class="smheading" align="center" width="45">Value</td>
    <td class="smheading" align="center" width="100">Category</td>
    <td class="smheading" align="center" width="45">Value</td>
  </tr>
  <tr>
    <td class="dark" align="center" style="white-space:nowrap">First Blood</td>
    <td class="grey" align="center">$fb</td>
    <td class="dark" align="center" style="white-space:nowrap">Double Kills</td>
    <td class="grey" align="center">$gp_multi1</td>
    <td class="dark" align="center" style="white-space:nowrap">Multi Kills</td>
    <td class="grey" align="center">$gp_multi2</td>
    <td class="dark" align="center" style="white-space:nowrap">Mega Kills</td>
    <td class="grey" align="center">$gp_multi3</td>
  </tr>
  <tr>
    <td class="dark" align="center" style="white-space:nowrap">Ultra Kills</td>
    <td class="grey" align="center">$gp_multi4</td>
    <td class="dark" align="center" style="white-space:nowrap">Monster Kills</td>
    <td class="grey" align="center">$gp_multi5</td>
    <td class="dark" align="center" style="white-space:nowrap">Ludicrous Kills</td>
    <td class="grey" align="center">$gp_multi6</td>
    <td class="dark" align="center" style="white-space:nowrap">Holy Shit Kills</td>
    <td class="grey" align="center">$gp_multi7</td>
  </tr>

EOF;

  $col = 0;
  for ($i = 0; $i < $numspec; $i++) {
    if ($col == 0)
      echo "  <tr>\n";

    echo <<<EOF
    <td class="dark" align="center" style="white-space:nowrap" title="{$special[$i]['desc']}">{$special[$i]['title']}</td>
    <td class="grey" align="center">{$special[$i]['total']}</td>

EOF;

    $col++;
    if ($col == 4) {
      echo "  </tr>\n";
      $col = 0;
    }
  }

  if ($col > 0) {
    while ($col < 4) {
      echo "    <td class=\"dark\" align=\"center\">&nbsp;</td>\n    <td class=\"grey\" align=\"center\">&nbsp;</td>\n";
      $col++;
    }

    echo "  </tr>\n";
  }

  echo "</table>\n";
}

//=============================================================================
//========== Combos ===========================================================
//=============================================================================
echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0">
  <tr>
    <td class="heading" colspan="8" align="center">Combos Used</td>
  </tr>
  <tr>
    <td class="dark" align="center" width="60">Speed</td>
    <td class="grey" align="center" width="35">$gp_combo1</td>
    <td class="dark" align="center" width="60">Booster</td>
    <td class="grey" align="center" width="35">$gp_combo2</td>
    <td class="dark" align="center" width="60">Invisible</td>
    <td class="grey" align="center" width="35">$gp_combo3</td>
    <td class="dark" align="center" width="60">Berzerk</td>
    <td class="grey" align="center" width="35">$gp_combo4</td>
  </tr>
</table>
EOF;

//=============================================================================
//========== Weapon Specific Information ======================================
//=============================================================================
echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="595">
  <tr>
    <td class="heading" colspan="8" align="center">Weapon Specific Information</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Weapon</td>
    <td class="smheading" align="center" width="55">Frags</td>
    <td class="smheading" align="center" width="55">Primary Kills</td>
    <td class="smheading" align="center" width="70">Secondary Kills</td>
    <td class="smheading" align="center" width="55">Deaths From</td>
    <td class="smheading" align="center" width="55">Deaths Holding</td>
    <td class="smheading" align="center" width="55">Suicides</td>
    <td class="smheading" align="center" width="55">Eff.</td>
  </tr>

EOF;

// Load Weapon Descriptions
$result = sql_queryn($link, "SELECT wp_num,wp_secondary,wp_desc,wp_weaptype FROM {$dbpre}weapons");
if (!$result) {
  echo "Error loading weapons descriptions.<br />\n";
  exit;
}
$maxweapon = 0;
$weapons = array();
while($row = sql_fetch_row($result)) {
  $num = $row[0];
  $weapons[$num][0] = $row[2];
  $weapons[$num][1] = $row[1];
  $weapons[$num][2] = $row[3];
  if ($num > $maxweapon)
    $maxweapon = $num;
}
sql_free_result($result);

$wskills = array(array());
/* wskills:
 0 = Primary Kills
 1 = Secondary Kills
 2 = Deaths From
 3 = Deaths Holding
 4 = Suicides
 5 = Weapon Description
 6 = Frags
 7 = Weapon Type
 8 = Road Kills
*/
$numweapons = 0;
// Load Weapon Kills for current match
$result = sql_queryn($link, "SELECT gk_killer,gk_victim,gk_kweapon,gk_vweapon FROM {$dbpre}gkills WHERE gk_match=$matchnum");
while ($row = sql_fetch_row($result)) {
  $killer = $row[0];
  $victim = $row[1];
  $weap = $row[2];
  $hweap = $row[3];

  if ($killer == $gp_num || $victim == $gp_num) {
    // Look for existing kill weapon in wskills description
    $weapon = -1;
    $secondary = 0;
    for ($i = 0; $i < $numweapons; $i++) {
      if ($weap > 0 && $weapon < 0 && !strcmp($wskills[5][$i], $weapons[$weap][0])) {
        $weapon = $i;
        $secondary = $weapons[$weap][1];
      }
    }
    // Add killer's weapon if not already used
    if ($weap > 0 && $weapon < 0) {
      $wskills[0][$numweapons] = $wskills[1][$numweapons] = 0; // Primary Kills / Secondary Kills
      $wskills[2][$numweapons] = $wskills[3][$numweapons] = 0; // Deaths From / Deaths Holding
      $wskills[4][$numweapons] = 0; // Suicides
      $wskills[5][$numweapons] = $weapons[$weap][0]; // Description
      $wskills[7][$numweapons] = $weapons[$weap][2]; // Type
      $wskills[8][$numweapons] = 0; // Road Kills
      $weapon = $numweapons++;
      $secondary = $weapons[$weap][1];
    }

    // Look for existing held weapon in wskills description
    $held = -1;
    for ($i = 0; $i < $numweapons; $i++) {
      if ($hweap > 0 && $held < 0 && !strcmp($wskills[5][$i], $weapons[$hweap][0]))
        $held = $i;
    }
    // Add victim's weapon if not already used
    if ($hweap > 0 && $held < 0) {
      $wskills[0][$numweapons] = $wskills[1][$numweapons] = 0; // Primary Kills / Secondary Kills
      $wskills[2][$numweapons] = $wskills[3][$numweapons] = 0; // Deaths From / Deaths Holding
      $wskills[4][$numweapons] = 0; // Suicides
      $wskills[5][$numweapons] = $weapons[$hweap][0]; // Description
      $wskills[7][$numweapons] = $weapons[$hweap][2]; // Type
      $wskills[8][$numweapons] = 0; // Road Kills
      $held = $numweapons++;
    }

    if ($killer < -1) {
      if ($victim == $gp_num) {
        if ($wskills[7][$weapon] > 0 || $gametval == 9) // Auto-turret/Monster death
          $wskills[2][$weapon]++; // Deaths From
        else
          $wskills[4][$weapon]++; // Event Suicide
        $wskills[3][$held]++;
      }
    }
    else if ($killer == $victim || ($killer == -1 && $victim >= 0)) {
      if ($killer < 0 || $killer == $gp_num)
        $wskills[4][$weapon]++; // Suicide
    }
    else {
      if ($killer == $gp_num) {
      	if ($victim == -3)
      	  $wskills[0][$held]++; // Killed Monster
        else {
          if ($secondary == 4)
            $wskills[8][$weapon]++; // Roadkill
          else if ($secondary)
            $wskills[1][$weapon]++; // Secondary Kill
          else
            $wskills[0][$weapon]++; // Primary Kill
        }
      }
      else if ($victim == $gp_num) {
        $wskills[2][$weapon]++; // Deaths From
        $wskills[3][$held]++;
      }
    }
  }
}
sql_free_result($result);

if ($numweapons > 0) {
  for ($i = 0; $i < $numweapons; $i++)
    $wskills[6][$i] = ($wskills[0][$i] + $wskills[1][$i] + $wskills[8][$i]) - $wskills[4][$i];

  // Sort by frags,kills,secondary kills,deaths holding,deaths from,suicides,description,type,road kills
  array_multisort($wskills[6], SORT_DESC, SORT_NUMERIC,
                  $wskills[0], SORT_DESC, SORT_NUMERIC,
                  $wskills[1], SORT_DESC, SORT_NUMERIC,
                  $wskills[3], SORT_ASC, SORT_NUMERIC,
                  $wskills[2], SORT_ASC, SORT_NUMERIC,
                  $wskills[4], SORT_ASC, SORT_NUMERIC,
                  $wskills[5], SORT_ASC, SORT_STRING,
                  $wskills[7], SORT_ASC, SORT_NUMERIC,
                  $wskills[8], SORT_ASC, SORT_NUMERIC);

  for ($i = 0; $i < $numweapons; $i++) {
    if ($wskills[7][$i])
      continue;
    $weapon = $wskills[5][$i];
    $kills = $wskills[0][$i];
    $skills = $wskills[1][$i];
    $deaths = $wskills[2][$i];
    $held = $wskills[3][$i];
    $suic = $wskills[4][$i];
    $frags = $wskills[6][$i];

    if (($kills || $skills || $deaths || $held) && strcmp($weapon, "None")) {
      if ($kills + $skills + $held + $suic == 0)
        $eff = "0.0";
      else
        $eff = sprintf("%0.1f", (($kills + $skills) / ($kills + $skills + $held + $suic)) * 100.0);

      echo <<< EOF
  <tr>
    <td class="dark" align="center">$weapon</td>
    <td class="grey" align="center">$frags</td>
    <td class="grey" align="center">$kills</td>
    <td class="grey" align="center">$skills</td>
    <td class="grey" align="center">$deaths</td>
    <td class="grey" align="center">$held</td>
    <td class="grey" align="center">$suic</td>
    <td class="grey" align="center">$eff%</td>
  </tr>

EOF;
    }
  }
}
else {
  echo <<< EOF
  <tr>
    <td class="grey" align="center" colspan="7">No Weapon Kills or Deaths</td>
  </tr>

EOF;
}
echo "</table>\n";

//=============================================================================
//========== Weapon Accuracy Information ======================================
//=============================================================================
$gwaweaps = array(array());
/* gwaweaps:
 0 = Fired
 1 = Hits
 2 = Damage
 3 = Description
*/
$numgwaweaps = 0;

// Load Weapon Accuracy data for current match
$result = sql_queryn($link, "SELECT gwa_weapon,gwa_fired,gwa_hits,gwa_damage FROM {$dbpre}gwaccuracy WHERE gwa_match=$matchnum AND gwa_player=$gp_pnum");
while ($row = sql_fetch_row($result)) {
  $weap = $row[0];
  $fired = $row[1];
  $hits = $row[2];
  $damage = $row[3];

  // Look for existing weapon in gwaweaps description
  $weapon = -1;
  for ($i = 0; $i < $numgwaweaps; $i++) {
    if ($weap > 0 && $weapon < 0 && !strcmp($gwaweaps[3][$i], $weapons[$weap][0]))
      $weapon = $i;
  }
  // Add weapon if not already used
  if ($weap > 0 && $weapon < 0) {
    $gwaweaps[0][$numgwaweaps] = 0;
    $gwaweaps[1][$numgwaweaps] = 0;
    $gwaweaps[2][$numgwaweaps] = 0;
    $gwaweaps[3][$numgwaweaps] = $weapons[$weap][0]; // Description
    $weapon = $numgwaweaps++;
  }

  // Update gwaweaps data
  $gwaweaps[0][$weapon] += $fired;
  $gwaweaps[1][$weapon] += $hits;
  $gwaweaps[2][$weapon] += $damage;
}
sql_free_result($result);

if ($numgwaweaps > 0) {
  echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="470">
  <tr>
    <td class="heading" colspan="5" align="center">Weapon Accuracy Information</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Weapon</td>
    <td class="smheading" align="center" width="78">Shots Fired</td>
    <td class="smheading" align="center" width="55">Hits</td>
    <td class="smheading" align="center" width="60">Damage</td>
    <td class="smheading" align="center" width="65">Accuracy</td>
  </tr>

EOF;

  // Sort by fired,hits,damage,description
  array_multisort($gwaweaps[2], SORT_DESC, SORT_NUMERIC,
                  $gwaweaps[1], SORT_DESC, SORT_NUMERIC,
                  $gwaweaps[0], SORT_DESC, SORT_NUMERIC,
                  $gwaweaps[3], SORT_ASC, SORT_STRING);

  for ($i = 0; $i < $numgwaweaps; $i++) {
    $weapon = $gwaweaps[3][$i];
    $fired = $gwaweaps[0][$i];
    $hits = $gwaweaps[1][$i];
    $damage = $gwaweaps[2][$i];
    if ($fired == 0)
      $acc = "0.0";
    else
      $acc = sprintf("%0.1f", ($hits / $fired) * 100.0);

    if ($fired || $hits || $damage) {
      echo <<< EOF
  <tr>
    <td class="dark" align="center">$weapon</td>
    <td class="grey" align="center">$fired</td>
    <td class="grey" align="center">$hits</td>
    <td class="grey" align="center">$damage</td>
    <td class="grey" align="center">$acc%</td>
  </tr>

EOF;
    }
  }
  echo "</table>\n";
}

//=============================================================================
//========== Vehicle and Turret Specific Information ==========================
//=============================================================================
if ($gm_logger == 1) {
  $wasStats = false;
  if ($numweapons > 0) {
    // Sort by frags,kills,secondary kills,road kills,deaths in,deaths from,suicides,description,type
    array_multisort($wskills[6], SORT_DESC, SORT_NUMERIC,
                    $wskills[0], SORT_DESC, SORT_NUMERIC,
                    $wskills[1], SORT_DESC, SORT_NUMERIC,
                    $wskills[8], SORT_DESC, SORT_NUMERIC,
                    $wskills[3], SORT_ASC, SORT_NUMERIC,
                    $wskills[2], SORT_ASC, SORT_NUMERIC,
                    $wskills[4], SORT_ASC, SORT_NUMERIC,
                    $wskills[5], SORT_ASC, SORT_STRING,
                    $wskills[7], SORT_ASC, SORT_NUMERIC);

    for ($i = 0; $i < $numweapons; $i++) {
      if ($wskills[7][$i] < 1 || $wskills[7][$i] > 2) continue;
      $weapon = $wskills[5][$i];
      $kills = $wskills[0][$i];
      $skills = $wskills[1][$i];
      $deaths = $wskills[2][$i];
      $held = $wskills[3][$i];
      $suic = $wskills[4][$i];
      $frags = $wskills[6][$i];
      $roadkills = $wskills[8][$i];

      if ($kills || $skills || $roadkills || $deaths || $held) {
        if ($kills + $skills + $roadkills + $held + $suic == 0) {
          $eff = "0.0";
        } else {
          $eff = sprintf("%0.1f", (($kills + $skills + $roadkills) / ($kills + $skills + $roadkills + $held + $suic)) * 100.0);
        }

        if (!$wasStats) { // print headers on first iteration
          $wasStats = true;
          echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="660">
  <tr>
    <td class="heading" colspan="9" align="center">Vehicle and Turret Specific Information</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Vehicle/Turret</td>
    <td class="smheading" align="center" width="55">Frags</td>
    <td class="smheading" align="center" width="55">Primary Kills</td>
    <td class="smheading" align="center" width="70">Secondary Kills</td>
    <td class="smheading" align="center" width="55">Road Kills</td>
    <td class="smheading" align="center" width="55">Deaths From</td>
    <td class="smheading" align="center" width="55">Deaths In</td>
    <td class="smheading" align="center" width="55">Suicides</td>
    <td class="smheading" align="center" width="55">Eff.</td>
  </tr>
EOF;
        }

        echo <<<EOF
  <tr>
    <td class="dark" align="center">$weapon</td>
    <td class="grey" align="center">$frags</td>
    <td class="grey" align="center">$kills</td>
    <td class="grey" align="center">$skills</td>
    <td class="grey" align="center">$roadkills</td>
    <td class="grey" align="center">$deaths</td>
    <td class="grey" align="center">$held</td>
    <td class="grey" align="center">$suic</td>
    <td class="grey" align="center">$eff%</td>
  </tr>
EOF;
      }
    }

    if ($wasStats) echo "</table>";
  }
} else {

  if ($numweapons > 0) {
    // Sort by frags,kills,secondary kills,deaths from,suicides,description,deaths holding,type,road kills
    array_multisort($wskills[6], SORT_DESC, SORT_NUMERIC,
                    $wskills[0], SORT_DESC, SORT_NUMERIC,
                    $wskills[1], SORT_DESC, SORT_NUMERIC,
                    $wskills[2], SORT_ASC, SORT_NUMERIC,
                    $wskills[4], SORT_ASC, SORT_NUMERIC,
                    $wskills[5], SORT_ASC, SORT_STRING,
                    $wskills[7], SORT_ASC, SORT_NUMERIC,
                    $wskills[3], SORT_ASC, SORT_NUMERIC,
                    $wskills[8], SORT_ASC, SORT_NUMERIC);

    $wasStats = false;
    for ($i = 0; $i < $numweapons; $i++) {
      if ($wskills[7][$i] < 1 || $wskills[7][$i] > 2) continue;
      $weapon = $wskills[5][$i];
      $kills = $wskills[0][$i];
      $skills = $wskills[1][$i];
      $deaths = $wskills[2][$i];
      $suic = $wskills[4][$i];
      $frags = $wskills[6][$i];
      $roadkills = $wskills[8][$i];

      if ($kills || $skills || $roadkills || $deaths) {

        if (!$wasStats) { // print headers on first iteration
          $wasStats = true;
          echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="560">
  <tr>
    <td class="heading" colspan="7" align="center">Vehicle and Turret Specific Information</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Vehicle/Turret</td>
    <td class="smheading" align="center" width="55">Frags</td>
    <td class="smheading" align="center" width="55">Primary Kills</td>
    <td class="smheading" align="center" width="70">Secondary Kills</td>
    <td class="smheading" align="center" width="55">Road Kills</td>
    <td class="smheading" align="center" width="55">Deaths From</td>
    <td class="smheading" align="center" width="55">Suicides</td>
  </tr>
EOF;
        }

        echo <<< EOF
  <tr>
    <td class="dark" align="center">$weapon</td>
    <td class="grey" align="center">$frags</td>
    <td class="grey" align="center">$kills</td>
    <td class="grey" align="center">$skills</td>
    <td class="grey" align="center">$deaths</td>
    <td class="grey" align="center">$roadkills</td>
    <td class="grey" align="center">$suic</td>
  </tr>
EOF;
      }
    }

    if ($wasStats) echo "</table>";
  }
}

//=============================================================================
//========== Invasion Monster Information =====================================
//=============================================================================
if ($gametval == 9) {
  echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="340">
  <tr>
    <td class="heading" colspan="3" align="center">Invasion Monster Information</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Monster</td>
    <td class="smheading" align="center" width="55">Killed</td>
    <td class="smheading" align="center" width="70">Died From</td>
  </tr>

EOF;

  if ($numweapons > 0) {
    // Sort by kills,deaths,description
    array_multisort($wskills[0], SORT_DESC, SORT_NUMERIC,
                    $wskills[1], SORT_ASC, SORT_NUMERIC,
                    $wskills[2], SORT_ASC, SORT_NUMERIC,
                    $wskills[5], SORT_ASC, SORT_STRING,
                    $wskills[6], SORT_DESC, SORT_NUMERIC,
                    $wskills[3], SORT_DESC, SORT_NUMERIC,
                    $wskills[4], SORT_ASC, SORT_NUMERIC,
                    $wskills[7], SORT_ASC, SORT_NUMERIC,
                    $wskills[8], SORT_ASC, SORT_NUMERIC);

    for ($i = 0; $i < $numweapons; $i++) {
      if ($wskills[7][$i] != 3)
        continue;
      $weapon = $wskills[5][$i];
      $kills = $wskills[0][$i] + $wskills[1][$i];
      $deaths = $wskills[2][$i];

      if ($kills || $deaths) {
        echo <<< EOF
  <tr>
    <td class="dark" align="center">$weapon</td>
    <td class="grey" align="center">$kills</td>
    <td class="grey" align="center">$deaths</td>
  </tr>

EOF;
      }
    }
  }
  else {
    echo <<< EOF
  <tr>
    <td class="grey" align="center" colspan="3">No Monster Kills or Deaths</td>
  </tr>

EOF;
  }
  echo "</table>\n";
}

//=============================================================================
//========== Suicides =========================================================
//=============================================================================
echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="255">
  <tr>
    <td class="heading" align="center" colspan="2">Suicides</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Type</td>
    <td class="smheading" align="center" width="55">Suicides</td>
  </tr>

EOF;

if ($suicides > 0) {
  // Sort by suicides, frags, kills, secondary kills, deaths from, deaths holding, description, weapon type
  array_multisort($wskills[4], SORT_DESC, SORT_NUMERIC,
                  $wskills[6], SORT_DESC, SORT_NUMERIC,
                  $wskills[0], SORT_DESC, SORT_NUMERIC,
                  $wskills[1], SORT_DESC, SORT_NUMERIC,
                  $wskills[2], SORT_ASC, SORT_NUMERIC,
                  $wskills[3], SORT_ASC, SORT_NUMERIC,
                  $wskills[5], SORT_ASC, SORT_STRING,
                  $wskills[7], SORT_ASC, SORT_NUMERIC);

  for ($i = 0; $i < $numweapons; $i++) {
    if ($wskills[4][$i] > 0) {
      $type = $wskills[5][$i];
      $suic = $wskills[4][$i];
      echo <<<EOF
  <tr>
    <td class="dark" align="center">$type</td>
    <td class="grey" align="center">$suic</td>
  </tr>

EOF;
    }
  }
}
else {
  echo <<<EOF
  <tr>
    <td class="grey" align="center" colspan="2">No Suicides</td>
  </tr>

EOF;
}
echo "</table>\n";

//=============================================================================
//========== Player Specific Kills and Deaths =================================
//=============================================================================
echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="580">
  <tr>
    <td class="heading" colspan="8" align="center">Player Specific Kills and Deaths</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Opponent</td>
    <td class="smheading" align="center" width="40">Kills</td>
    <td class="smheading" align="center" width="45">Deaths</td>
    <td class="smheading" align="center" width="65">Efficiency</td>
    <td class="smheading" align="center">Opponent</td>
    <td class="smheading" align="center" width="40">Kills</td>
    <td class="smheading" align="center" width="45">Deaths</td>
    <td class="smheading" align="center" width="65">Efficiency</td>
  </tr>

EOF;

// Load Player Names
$maxplayer = 0;
$result = sql_queryn($link, "SELECT gp_num,gp_pnum,gp_bot,plr_name FROM {$dbpre}gplayers,{$dbpre}players WHERE gp_match=$matchnum AND {$dbpre}players.pnum=gp_pnum");
if (!$result) {
  echo "Match player list database error.<br />\n";
  exit;
}
$numplr = 0;
while($row = sql_fetch_assoc($result)) {
  $num = $row["gp_num"];
  $gplayer[$num] = $row;
  if ($num > $maxplayer)
    $maxplayer = $num;
  $gplayer[$num]["gp_name"] = stripspecialchars($row["plr_name"]);
  $opkills[0][$num] = $num;
  $opkills[1][$num] = $opkills[2][$num] = 0;
  $numplr++;
}
sql_free_result($result);

// Read Kill Log
$result = sql_queryn($link, "SELECT gk_killer,gk_victim FROM {$dbpre}gkills WHERE gk_match=$matchnum");
if (!$result) {
  echo "Error reading gkills player data.<br />\n";
  exit;
}
while ($row = sql_fetch_row($result)) {
  $killer = $row[0];
  $victim = $row[1];
  if ($killer == $plr && $victim != $plr && isset($opkills[0][$victim]))
    $opkills[1][$victim]++; // Kills
  else if ($victim == $plr && $killer >= 0 && $killer != $plr && isset($opkills[0][$killer]))
    $opkills[2][$killer]++; // Deaths
}
sql_free_result($result);

array_multisort($opkills[1], SORT_DESC, SORT_NUMERIC,
                $opkills[2], SORT_DESC, SORT_NUMERIC,
                $opkills[0], SORT_ASC, SORT_NUMERIC);

$col = 0;
for ($i = 0; $i < $numplr; $i++) {
  if ($opkills[0][$i] != $plr) {
    $kills = $opkills[1][$i];
    $deaths = $opkills[2][$i];
    if ($kills || $deaths) {
      if ($col > 1)
        $col = 0;
      if ($col == 0)
        echo "  <tr>\n";
      $opp = $opkills[0][$i];
      if ($kills + $deaths > 0)
        $eff = sprintf("%0.1f", $kills / ($kills + $deaths) * 100);
      else
        $eff = "0.0";
      $name = $gplayer[$opp]["gp_name"];
      if ($gplayer[$opp]["gp_bot"])
        $nameclass = "darkbot";
      else
        $nameclass = "darkhuman";
      $player = "<a class=\"$nameclass\" href=\"matchplayer.php?match=$matchnum&amp;player=$opp\">$name</a>";
      echo <<<EOF
    <td class="$nameclass" align="center">$player</td>
    <td class="grey" align="center">$kills</td>
    <td class="grey" align="center">$deaths</td>
    <td class="grey" align="center">$eff%</td>

EOF;
      if ($col == 1)
        echo "  </tr>\n";
      $col++;
    }
  }
}
if ($col == 1) {
  echo <<<EOF
    <td class="dark" align="center">&nbsp;</td>
    <td class="grey" align="center">&nbsp;</td>
    <td class="grey" align="center">&nbsp;</td>
    <td class="grey" align="center">&nbsp;</td>
  </tr>

EOF;
  $col++;
}
echo "</table>\n";

//=============================================================================
//========== Killing Sprees ===================================================
//=============================================================================
$result = sql_queryn($link, "SELECT * FROM {$dbpre}gevents USE INDEX (ge_kstype) WHERE ge_event=1 AND ge_match=$matchnum AND ge_plr=$plr ORDER BY ge_time");
if (!$result) {
  echo "Error loading events.<br />\n";
  exit;
}
$sprees = $header = 0;
while ($row = sql_fetch_assoc($result)) {
  if ($row["ge_quant"] >= 5) {
    foreach ($row as $key => $val)
      ${$key} = $val;

    $time = displayTime(($ge_time - $ge_length - ($delay * $gm_timeoffset)), $gm_timeoffset);
    $length = displayTime($ge_length, $gm_timeoffset);

    $type = "";
    if ($ge_quant >= 5 && $ge_quant < 10)
      $type = "Killing Spree";
    else if ($ge_quant >= 10 && $ge_quant < 15)
      $type = "Rampage";
    else if ($ge_quant >= 15 && $ge_quant < 20)
      $type = "Dominating";
    else if ($ge_quant >= 20 && $ge_quant < 25)
      $type = "Unstoppable";
    else if ($ge_quant >= 25 && $ge_quant < 30)
      $type = "Godlike";
    else if ($ge_quant >= 30)
      $type = "Wicked Sick";

    switch ($ge_reason) {
      case 0: // Match Ended
        $reason = "Match Ended";
        break;
      case 1: // Killed by {player} with a {weapon}
        $killer = $gplayer[$ge_opponent]["gp_name"];
        $weapon = $weapons[$ge_item][0];
        if (!strcmp($weapon, "Crushed") || !strcmp($weapon, "Telefragged")  || !strcmp($weapon, "Depressurized"))
          $reason = "$weapon by $killer";
        else {
          $wfl = strtoupper($weapon[0]);
          if ($wfl == 'A' || $wfl == 'E' || $wfl == 'I' || $wfl == 'O' || $wfl == 'U' || $wfl == 'Y')
            $reason = "Killed by $killer with an $weapon";
          else
            $reason = "Killed by $killer with a $weapon";
        }
        break;
      case 2: // Suicided with {weapon}
        $weapon = $weapons[$ge_item][0];
        if (!strcmp($weapon, "Suicided") || !strcmp($weapon, "Drowned"))
          $reason = "$weapon";
        else if (!strcmp($weapon, "Corroded") || !strcmp($weapon, "Crushed") || !strcmp($weapon, "Gibbed") || !strcmp($weapon, "Depressurized"))
          $reason = "Was $weapon";
        else if (!strcmp($weapon, "Fell"))
          $reason = "Fell to their death";
        else if (!strcmp($weapon, "Fell Into Lava"))
          $reason = "Fell into Lava";
        else if (!strcmp($weapon, "Swam Too Far"))
          $reason = "Tried to Swim Too Far";
        else if (!strcmp($weapon, "Vehicle Explosion"))
          $reason = "Suicided from a Vehicle Explosion";
        else if (!strcmp($weapon, "Reckless Driving"))
          $reason = "Suicided from Reckless Driving";
        else {
          $wfl = strtoupper($weapon[0]);
          if ($wfl == 'A' || $wfl == 'E' || $wfl == 'I' || $wfl == 'O' || $wfl == 'U' || $wfl == 'Y')
            $reason = "Suicided with an $weapon";
          else
            $reason = "Suicided with a $weapon";
        }
        break;
      case 3: // Died from {weapon}
        $weapon = $weapons[$ge_item][0];
        if (!strcmp($weapon, "Suicided") || !strcmp($weapon, "Drowned"))
          $reason = "$weapon";
        else if (!strcmp($weapon, "Corroded") || !strcmp($weapon, "Crushed") || !strcmp($weapon, "Gibbed") || !strcmp($weapon, "Depressurized"))
          $reason = "Was $weapon";
        else if (!strcmp($weapon, "Fell"))
          $reason = "Fell to their death";
        else if (!strcmp($weapon, "Fell Into Lava"))
          $reason = "Fell into Lava";
        else if (!strcmp($weapon, "Swam Too Far"))
          $reason = "Tried to Swim Too Far";
        else {
          $wfl = strtoupper($weapon[0]);
          if ($wfl == 'A' || $wfl == 'E' || $wfl == 'I' || $wfl == 'O' || $wfl == 'U' || $wfl == 'Y')
            $reason = "Died from an $weapon";
          else
            $reason = "Died from a $weapon";
        }
        break;
      case 4: // Disconnected
        $reason = "Disconnected";
        break;
      case 5: // Team Killed by {player} with a {weapon}
        $killer = $gplayer[$ge_opponent]["gp_name"];
        $weapon = $weapons[$ge_item][0];
        if (!strcmp($weapon, "Crushed") || !strcmp($weapon, "Telefragged")  || !strcmp($weapon, "Depressurized"))
          $reason = "Team Killed - $weapon by $killer";
        else {
          $wfl = strtoupper($weapon[0]);
          if ($wfl == 'A' || $wfl == 'E' || $wfl == 'I' || $wfl == 'O' || $wfl == 'U' || $wfl == 'Y')
            $reason = "Team Killed by $killer with an $weapon";
          else
            $reason = "Team Killed by $killer with a $weapon";
        }
        break;
      case 6: // Team Change
        $reason = "Changed Teams";
        break;
      default:
        $reason = "Unknown";
    }

    if (!$header) {
      echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="620">
  <tr>
    <td class="heading" colspan="5" align="center">Killing Sprees</td>
  </tr>
  <tr>
    <td class="smheading" align="center" width="90">Spree Type</td>
    <td class="smheading" align="center" width="45">Start Time</td>
    <td class="smheading" align="center" width="55">Time In Spree</td>
    <td class="smheading" align="center" width="80">Kills During Spree</td>
    <td class="smheading" align="center">Reason Spree Stopped</td>
  </tr>

EOF;
      $header = 1;
    }

    echo <<<EOF
  <tr>
    <td class="dark" align="center">$type</td>
    <td class="grey" align="center">$time</td>
    <td class="grey" align="center">$length</td>
    <td class="grey" align="center">$ge_quant</td>
    <td class="grey" align="center">$reason</td>
  </tr>

EOF;
    $sprees++;
  }
}
sql_free_result($result);
if (!$sprees) {
  echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="600">
  <tr>
    <td class="heading" align="center">No Killing Sprees</td>
  </tr>

EOF;
}
echo "</table>\n";

//=============================================================================
//========== Total Items Picked Up By Type ====================================
//=============================================================================
echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0" width="600">
  <tr>
    <td class="heading" colspan="6" align="center">Total Items Picked Up By Type</td>
  </tr>
  <tr>
    <td class="smheading" align="center">Item Type</td>
    <td class="smheading" align="center" width="35">No.</td>
    <td class="smheading" align="center">Item Type</td>
    <td class="smheading" align="center" width="35">No.</td>
    <td class="smheading" align="center">Item Type.</td>
    <td class="smheading" align="center" width="35">No.</td>
  </tr>

EOF;

// Load Item Descriptions
$result = sql_queryn($link, "SELECT it_num,it_desc FROM {$dbpre}items");
if (!$result) {
  echo "{$LANG_ERRORLOADINGITEMPICKUPDESC}<br />\n";
  exit;
}
$items = array();
while ($row = sql_fetch_row($result))
  $items[$row[0]] = $row[1];
sql_free_result($result);

$result = sql_queryn($link, "SELECT gi_item,gi_pickups FROM {$dbpre}gitems WHERE gi_match=$matchnum AND gi_plr=$gp_num");
if (!$result) {
  echo "{$LANG_ERRORLOADINGITEMPICKUPS}<br />\n";
  exit;
}

$pickups = array(array());
$totpickups = 0;
while ($row = sql_fetch_row($result)) {
  for ($i = 0, $item = -1; $i < $totpickups && $item < 0; $i++) {
    if (!strcmp($pickups[0][$i], $items[$row[0]]))
      $item = $i;
  }
  if ($item < 0) {
    $pickups[0][$totpickups] = $items[$row[0]];
    $pickups[1][$totpickups++] = $row[1];
  }
  else
    $pickups[1][$item] += $row[1];
}
sql_free_result($result);

if ($totpickups > 0)
  array_multisort($pickups[1], SORT_DESC, SORT_NUMERIC,
                  $pickups[0], SORT_ASC, SORT_STRING);

$col = 0;
for ($i = 0; $i < $totpickups; $i++) {
  $item = $pickups[0][$i];
  $num = $pickups[1][$i];
  if ($num) {
    if ($col > 2)
      $col = 0;
    if ($col == 0)
      echo "  <tr>\n";
    echo <<<EOF
    <td class="dark" align="center">$item</td>
    <td class="grey" align="center">$num</td>

EOF;
    if ($col == 2)
      echo "  </tr>\n";
    $col++;
  }
}

if (!$totpickups) {
  echo <<<EOF
  <tr>
    <td class="dark" align="center" colspan="6">There Were No Pickups Used</td>
  </tr>

EOF;
}
else {
  if ($col < 3) {
    while ($col < 3) {
      echo <<<EOF
    <td class="dark" align="center">&nbsp;</td>
    <td class="grey" align="center">&nbsp;</td>

EOF;
      $col++;
    }
    echo "  </tr>\n";
  }
}
echo "</table>\n";

//=============================================================================
//========== Connection Log ===================================================
//=============================================================================
echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0">
  <tr>
    <td class="heading" colspan="3" align="center">Connection Log</td>
  </tr>
  <tr>
    <td class="smheading" align="center" width="50">Time</td>
    <td class="smheading" align="center" width="100">Status</td>
  </tr>

EOF;

$result = sql_queryn($link, "SELECT ge_event,ge_plr,ge_time,ge_reason FROM {$dbpre}gevents WHERE ge_match=$matchnum AND ge_event BETWEEN 2 AND 3 ORDER BY ge_time");
if (!$result) {
  echo "Error loading connection events.<br />\n";
  exit;
}
while ($row = sql_fetch_assoc($result)) {
  $plr = $row["ge_plr"];
  $event = $row["ge_event"];
  if ($plr == $gp_num || $event == 3) {
    $time = displayTime(($row["ge_time"] - ($delay * $gm_timeoffset)), $gm_timeoffset);
    if ($event == 3) {
      switch ($row["ge_reason"]) {
        case 0:
          $reason = "Match Start";
          $rclass = "gselog";
          break;
        case 1:
          $reason = "Match Ended";
          $rclass = "gselog";
          break;
        default:
          $reason = "Unknown";
          $rclass = "gselog";
      }
    }
    else {
      switch ($row["ge_reason"]) {
        case 0:
          $reason = "Connected";
          $rclass = "grey";
          break;
        case 1:
          $reason = "Disconnected";
          $rclass = "warn";
          break;
      }
    }
    echo <<<EOF
    <tr>
      <td class="dark" align="center">$time</td>
      <td class="$rclass" align="center">$reason</td>
    </tr>

EOF;
  }
}
sql_free_result($result);

echo <<<EOF
</table></center>

</td></tr></table>
EOF;

sql_close($link);

require('includes/footer.inc.php');

?>
