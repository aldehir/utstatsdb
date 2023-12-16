<?php

/*
    UTStatsDB
    Copyright (C) 2002-2009  Patrick Contreras / Paul Gallier
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

$servernum = -1;
check_get($servernum, "server");
if (!is_numeric($servernum))
  $servernum = -1;
if ($servernum <= 0) {
  echo "Invalid server number.<br />\n";
  echo "Run from the main index program.<br />\n";
  exit;
}

$link = sql_connect();

// Load Server Data
$result = sql_queryn($link, "SELECT * FROM {$dbpre}servers WHERE sv_num=$servernum LIMIT 1");
if (!$result) {
  echo "Server database error.<br />\n";
  exit;
}
$row = sql_fetch_assoc($result);
sql_free_result($result);
if (!$row) {
  echo "Server not found in database.<br />\n";
  exit;
}
foreach ($row as $key => $val)
  ${$key} = $val;

if ($useshortname && $sv_shortname != "") {
  $servername = stripspecialchars($sv_shortname);
} else {
  $servername = stripspecialchars($sv_name);
}

if ($sv_addr) {
  $svn = "<a href=\"$sv_addr\" class=\"grey\">$servername</a>";
} else {
  $svn = $servername;
}

$serveradmin = stripspecialchars($sv_admin);
$serveremail = stripspecialchars($sv_email);
$last = strtotime($sv_lastmatch);
$lastdate = formatdate($last, 1);
$time = displayTimeMins($sv_time / 6000.0);

echo <<<EOF
<table cellpadding="1" cellspacing="2" border="0" width="720">
  <tr>
    <td class="heading" align="center">Server Stats for $servername</td>
  </tr>
</table>
<br />
<table cellpadding="1" cellspacing="2" border="0">
  <tr>
    <td class="heading" colspan="4" align="center">Unreal Tournament Server Stats</td>
  </tr>
  <tr>
    <td class="dark" align="center" width="90">Server Name</td>
    <td class="grey" align="center" width="250">$svn</td>
    <td class="dark" align="center" width="80">Matches</td>
    <td class="grey" align="center" width="90">$sv_matches</td>
  </tr>
  <tr>
    <td class="dark" align="center">Server Admin</td>
    <td class="grey" align="center">$serveradmin</td>
    <td class="dark" align="center">Frags</td>
    <td class="grey" align="center">$sv_frags</td>
  </tr>
  <tr>
    <td class="dark" align="center">Admin Email</td>
    <td class="grey" align="center">$serveremail</td>
    <td class="dark" align="center">Score</td>
    <td class="grey" align="center">$sv_score</td>
  </tr>
  <tr>
    <td class="dark" align="center">Last Match</td>
    <td class="grey" align="center">$lastdate</td>
    <td class="dark" align="center">Game Time</td>
    <td class="grey" align="center">$time hours</td>
  </tr>
</table>

EOF;

//=============================================================================
//========== Most Recent Matches Played =======================================
//=============================================================================

echo <<<EOF
<br />
<table cellpadding="1" cellspacing="2" border="0">
  <tr>
    <td class="heading" colspan="5" align="center">Most Recent Matches Played</td>
  </tr>
  <tr>
    <td class="smheading" align="center" width="220">Date</td>
    <td class="smheading" align="center" width="150">Match Type</td>
    <td class="smheading" align="center" width="225">Map</td>
    <td class="smheading" align="center" width="50">Players</td>
    <td class="smheading" align="center" width="50">Minutes</td>
  </tr>

EOF;

$matches = 0;
$result = sql_queryn($link, "SELECT 
                               gm_num,gm_map,gm_type,gm_start,gm_timeoffset,gm_length,gm_numplayers,mp_name,tp_desc
                               FROM {$dbpre}matches USE INDEX (gm_svnum), {$dbpre}maps,{$dbpre}type
                               WHERE gm_server=$servernum 
                                     AND mp_num=gm_map
                                     AND tp_num=gm_type
                               ORDER BY gm_num DESC LIMIT 21");
if (!$result) {
  echo "Error accessing match database.<br />\n";
  exit;
}
while ($row = sql_fetch_assoc($result)) {
  if ($matches < 20) {
    foreach ($row as $key => $val) ${$key} = $val;

    $gametype = $tp_desc;
    $start = strtotime($gm_start);
    $matchdate = formatdate($start, 1);
    $length = displayTime($gm_length, $gm_timeoffset);
    $mapname = stripspecialchars($mp_name);

    echo <<<EOF
  <tr>
    <td class="dark" align="center"><a class="dark" href="matchstats.php?match=$gm_num">$matchdate</a></td>
    <td class="grey" align="center">$gametype</td>
    <td class="grey" align="center"><a class="grey" href="mapstats.php?map=$gm_map">$mapname</a></td>
    <td class="grey" align="center">$gm_numplayers</td>
    <td class="grey" align="center">$length</td>
  </tr>

EOF;
  }
  $matches++;
}
sql_free_result($result);
if ($matches > 20) {
  echo <<< EOF
  <tr>
    <td class="smheading" colspan="5" align="center"><a href="typematches.php?server=$servernum" class="smheading">[Show All Matches For Server]</a></td>
  </tr>

EOF;
}
echo <<<EOF
</table>
</td></tr></table>
EOF;

sql_close($link);

require('includes/footer.inc.php');

?>
