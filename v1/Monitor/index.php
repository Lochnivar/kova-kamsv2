<?php
include '/usr/src/KAMS-Setting-file.php';
date_default_timezone_set('America/New_York');


?>
<html>
<head>
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
        }, 5000);
    });
		
})(jQuery);

	</script>
	
<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
}
</style>


</head>
 <body style="padding: 5px 5px 5px 5px;background-color:#dcdcdc;">
	
	<!-- TOP NAV BAR FOR PAGE NAVIGATION --!>
	<div class="topnav">
	<?php if($MotorolaPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" href="../channels/index.php">Motorola-Channels</a></button>
	<?php } ?>
	<?php if($UDPMonitorPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../netmon/index.php">UDP-Monitor</a></button>
	 <?php } ?>
	 <?php if($SerialMonitorPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>
	 <?php } ?>
	<?php if($ZabbixPage == "yes" && ($MotorolaPage == "yes" || $UDPMonitorPage == "yes" || $SerialMonitorPage == "yes")){ ?>
          <button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>
         <?php } ?>
	</div>
    <hr>
<h1><center><?php echo $SiteName; ?> Servers Status</center></h1>
<hr>
<div id="content">
	</div>


<!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <link rel="stylesheet" href="./css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
  </body>
</html>

