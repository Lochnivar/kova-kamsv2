<?php

// Replace the original netmon with this file so it redirects correctly

$localIP = $_SERVER['SERVER_ADDR'];
?>

<html>
  <head>
    <meta http-equiv="refresh" content=".1; url='http://<?php echo $localIP ?>/netmon-ng/index.html'" />
  </head>
  <body>
    <p>Redirecting to New Net-Mon Page!</p>
  </body>
</html>

