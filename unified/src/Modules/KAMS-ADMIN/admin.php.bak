<?php

require(__DIR__ . "/.access.php");

$dbuser = 'kams';
$dbpass = 'kams7906';
$dbhost = 'localhost';
$dbname = 'KAMS';

$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

$sql = "SELECT * FROM settings";

$response = $mysqli->query($sql);

$results = $response->fetch_all(MYSQLI_ASSOC);

?>

<html>

<head>

</head>


<body>
    <form action="./adminWorker.php" method="POST">
        <table>
            <tr>
                <th>Setting Name</th>
                <th>Setting Value</th>
                <th>Setting Note</th>
            </tr>
            <?php
            foreach ($results as $row) {

                $k = $row['setname'];
                /*?  if (strpos($row['setvalue'], "~")) {
                $v = explode("~", $row['setvalue']);
            } else {
                */
                $v = $row['setvalue'];
                //}
                $h = $row['sethint'];

            ?>
                <tr>
                    <td>
                        <?php echo $k ?>
                    </td>
                    <td>
                        <input name="<?php echo $k ?>" value="<?php echo $v ?>" size='100'>
                        </input>
                    </td>
                    <td>
                        <span id="sethint"><?php echo $h?></span>
                    </td>
                </tr>
            <?php
            }

            ?>



        </table>
        <button type="submit" formtarget="_self">Save Settings</button>
    </form>

</body>

</html>