<?php

$version = file_get_contents("VERSION.txt");

$stats = "";
if ($showStats) {
  $stats = " &#8729; Total SQL Query Time: " . round($pageStats["querytime"], 4)
          . "s; SQL Query Count: " . $pageStats["querycount"]
          . "; Total Page Time: " . round(microtime(true) - $pageStats["pagestart"], 4) . "s";
}

echo <<<EOF
<footer>
  {$version} &#8729;
  <a href="https://github.com/shrimpza/utstatsdb">UTStatsDB on GitHub</a>
  {$stats}
</footer>
</body>
</html>
EOF;

?>
