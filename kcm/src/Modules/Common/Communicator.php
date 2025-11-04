<?php

namespace Kova\Kcm\Modules\Common;

use Kova\Kcm\Modules\Common\Config as Config;
use Kova\Kcm\Modules\Common\Common as Common;

class Communicator
{

    public $config;
    public $common;

    public function __construct()
    {
        $configs = new Config;
        $this->config = $configs->config;
        $this->common = new Common($configs);

        //$this->SendComms($msgLine);
    }



    public function SendComms($msgLine)
    {

        echo "comm Sent" . PHP_EOL;



        return;

        $output = system("ssh -t " . $this->config['sshName'] . "@40.142.26.130 '/usr/bin/echo " . $msgLine . "  >> /usr/KAMS/kams-messages.csv'", $return_var);
        echo $return_var . " - the value returned";

        if ($return_var !== 0) {
            $outputBK = system("ssh -t " . $this->config['sshName'] . "@41.143.128.194 '/usr/bin/echo " . $msgLine . "  >> /usr/KAMS/kams-messages.csv'", $returnBK_var);

            echo $returnBK_var . " - the value returned";
        }

        return $msgLine;
    }

    public function sendReport($report)
    {
        $cargo = base64_encode(json_encode($report));

        var_dump($cargo);
        //system ("ssh -t ".$sshName."@40.142.26.130 '/usr/bin/echo $epoch  > /usr/KAMS/SITES/".$sshName.".txt'",$respnseMain);
        //      var_dump($this->config['sshName']);
        $cmd = "ssh -t " . $this->config['sshName'] . "@40.143.128.194 '/usr/bin/php /usr/src/KCM/kcm-endpoint.php' $cargo";
        //var_dump($cmd);
        system($cmd, $respnseMain);
    }
}
