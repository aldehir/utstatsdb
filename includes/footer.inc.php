<?php

$version = file_get_contents("VERSION.txt");

echo <<<EOF
<footer>
  {$version} &#8729;
  <a href="https://github.com/shrimpza/utstatsdb">GitHub Project</a>
</footer>
</body>
</html>
EOF;

?>
