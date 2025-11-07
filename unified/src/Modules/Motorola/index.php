<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../app/bootstrap.php');

use Kova\Kams\Common\Config;

$config = new Config();
$siteName = $config->getString('SiteName', 'KAMS');
$motorolaPage = $config->getBool('MotorolaPage', false);
$udpMonitorPage = $config->getBool('UDPMonitorPage', false);
$serialMonitorPage = $config->getBool('SerialMonitorPage', false);
$zabbixPage = $config->getBool('ZabbixPage', false);

?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../Bones/css/bootstrap/css/bootstrap.min.css">
    <script type="text/javascript" src="../Bones/js/jquery.min.js"></script>

    <script>
    (function($)
    {
        $(document).ready(function()
        {
            $.ajaxSetup(
            {
                cache: true,
                beforeSend: function() {
                    $('#content').show();
                    $('#loading').hide();
                },
                complete: function() {
                    $('#loading').hide();
                    $('#content').show();
                },
                success: function() {
                    $('#loading').hide();
                    $('#content').show();
                }
            });
            
            var $container = $("#content");
            $container.load("channels-check-ajax.php");
            var refreshId = setInterval(function()
            {
                $container.load('channels-check-ajax.php');
            }, 15000);
        });
        
    })(jQuery);
    </script>
    
    <title>Channel Status</title>
  </head>
  <body style="padding: 5px 5px 5px 5px;background-color:#dcdcdc;">
    
    <!-- TOP NAV BAR FOR PAGE NAVIGATION -->
    <div class="topnav">
    <?php if($motorolaPage && ($udpMonitorPage || $serialMonitorPage || $zabbixPage)){ ?>
      <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" href="index.php">Motorola-Channels</a></button>
    <?php } ?>
    <?php if($udpMonitorPage){ ?>
      <button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" href="../netmon-ng/index.html">UDP-Monitor</a></button>
     <?php } ?>
     <?php if($serialMonitorPage){ ?>
      <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>
     <?php } ?>
    <?php if($zabbixPage){ ?>
          <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>
         <?php } ?>

    </div>
    <hr>
  
    <h2><center><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?> Channel Status</center></h2>
    <div id="content">
    </div>
    <div>
    
    </div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  </body>
</html>
