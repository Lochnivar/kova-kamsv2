<?php
include  'php-mysql/connection-channels.php';
include '/usr/src/KAMS-Setting-file.php';
date_default_timezone_set($TimeZone);

?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="./css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
	<script type="text/javascript" src="./css/jquery-3.3.1.min.js"></script>

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
/*

$(document).ready(function(){
    $("#content").load("channels-check-ajax.php", function(responseTxt, statusTxt, xhr){
        if(statusTxt == "success")
            alert("External content loaded successfully!");
        if(statusTxt == "error")
            alert("Error: " + xhr.status + ": " + xhr.statusText);
    });
});
*/
	</script>
	
    <title>Channel Status</title>
  </head>
   <body style="padding: 5px 5px 5px 5px;background-color:#dcdcdc;">
	
	<!-- TOP NAV BAR FOR PAGE NAVIGATION --!>
	<div class="topnav">
	<?php if($MotorolaPage == "yes" && ($UDPMonitorPage == "yes" || $SerialMonitorPage == "yes" || $ZabbixPage  == "yes")){ ?>
	  <button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" class="active" href="../channels/index.php">Motorola-Channels</a></button>
	<?php } ?>
	<?php if($UDPMonitorPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../netmon/index.php">UDP-Monitor</a></button>
	 <?php } ?>
	 <?php if($SerialMonitorPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>
	 <?php } ?>
	<?php if($ZabbixPage == "yes"){ ?>
          <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>
         <?php } ?>

	</div>
	<hr>
  
  
	<h2><center><a href="admin.php"><?php echo $channelSiteHeader ?>  Channel Status</a></center></h2>
	<div id="content">
	</div>
	<div>
	
	</div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <link rel="stylesheet" href="./css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
	<script type="text/javascript" src="./css/jquery-3.3.1.min.js"></script>
  </body>
</html>


