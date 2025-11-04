<?php

namespace Kova\Kcm\Modules\Crons;

use Kova\Kcm\Modules\Common\Common as Common;
use Kova\Kcm\Modules\Common\Database as DB;

class CronTemplate
{
    public $common;
    public $config;
    public $dbConn;
    public $mod;
    public $fileName;
    public $ifaces;
    public $modEnabled;
    public $processor;

    public function __construct($config, $mod)
    {
        $this->mod = $mod;
        $this->common = new Common($config);
        $this->dbConn = new DB('kams');
        $this->config = $config->config;

        switch ($mod) {
            case "moto":
                $this->ifaces = $this->config['MotorolaInterfaceName'];
                $this->modEnabled = $this->config['MotorolaPage'];
                $this->fileName = __DIR__ . "/motodump";
                $this->processor = new \Kova\Kcm\Modules\Motorola\MotoCron($this->config);
                break;
            case "serial":
                $this->ifaces = $this->config['SerialInterfaceName'];
                $this->modEnabled = $this->config['SerialMonitorPage'];
                $this->fileName = __DIR__ . "/serialdump";
                $this->processor = new \Kova\Kcm\Modules\Serial\SerialCron($this->config);
                break;
            case "udp":
                $this->ifaces = $this->config['UDPInterfaceName'];
                $this->modEnabled = $this->config['UDPMonitorPage'];
                $this->fileName = __DIR__ . "/udpdump";
                $this->processor = new \Kova\Kcm\Modules\Udp\UdpCron($this->config);
                break;
            default:

                break;
        }
    }
    public function CheckCron()
    {
        $pids = [];
        echo "In " . $this->mod . " Check Cron" . PHP_EOL;

        if ($this->modEnabled == "yes") {
            echo "Starting " . $this->mod . " Cron" . PHP_EOL;
            $pids = $this->StartCrons();
        } else {
            echo $this->mod . " Cron Disabled" . PHP_EOL;
        }

        return $pids;
    }

    public function stopCron()
    {
        $udpIFaces = [];

        var_dump($this->ifaces);

        $ifaceArray = explode("~", $this->ifaces);

        foreach ($ifaceArray as $iface) {
            $iArray = explode("|", $iface);
            $ifArray[$iArray[0]] = [];
            $ifaces[] = $iArray[0];

            echo "Getting PID for " . $iArray[0] . PHP_EOL;
            $pid = $this->dbConn->getProcID($this->mod, $iArray[0]);

            //   exec("kill -9 $pid");
        }
    }

    public function StartCrons()
    {

        $pids = [];
        $mods = ["moto", "serial", "udp"];


        foreach ($mods as $mod) {
            switch ($mod) {
                case "moto":
                    $cronStart = new \Kova\Kcm\Modules\Motorola\MotoCron($this->config);
                    break;
                case "serial":
                    $cronStart = new \Kova\Kcm\Modules\Serial\SerialCron($this->config);
                    break;
                case "udp":
                    $cronStart = new \Kova\Kcm\Modules\Udp\UdpCron($this->config);
                    break;
                default:

                    break;
            }
            $pid = $cronStart->startCron();
            $pids += $pid;
        }

        $pids = [];
        $x = 0;
        $udpIFaces = [];

        $ifaceArray = explode("~", $this->ifaces);

        foreach ($ifaceArray as $iface) {

            $iArray = explode("|", $iface);
            $ifArray[$iArray[0]] = [];
            $udpIFaces[] = $iArray[0];

            switch ($this->mod) {
                case "serial":
                    $command = "timeout 30 cat " . $iArray[0] . " >> " . $this->fileName . "-" . $iArray[0] . " & echo $!";

                    break;
                case "udp":
                    //test command
                    $command = "timeout 600 /usr/bin/tcpdump -tt -i " . $iArray[0] . ">>" . $this->fileName . "-" . $iArray[0] . "  & echo $!";

                    //actual command.
                    //$command = "timeout 30 /usr/bin/tcpdump udp -vvvvv -tt -l -i " . $iArray[0] . ">>" . $this->fileName . "-" . $iArray[0] . "  & echo $!";
                    break;

                case "moto":

                    break;
            }

            var_dump($command);

            exec($command, $output);
            echo "Start Cron DB vars" . PHP_EOL;
            var_dump($this->mod, $output[$x], $iArray[0]);


            $this->dbConn->putProcID($this->mod, $output[$x], $iArray[0]);
            $pids[] = $output[$x];
            $x++;
        }

        var_dump($pids);
        return $pids;
    }

    public function processCron()
    {
        echo "Starting CronTemplate->processCron" . PHP_EOL;

        if ($this->modEnabled == "yes") {
            echo "Module " . $this->mod . " Enabled" . PHP_EOL;
            $ifaces = $this->dbConn->getIfaces($this->mod);
            var_dump($ifaces);
            foreach ($ifaces as $iface) {


                var_dump($iface['ifaceid']);

                $rawFile = file($this->fileName . "-" . $iface['ifaceid']);

                $file = file_get_contents($this->fileName . "-" . $iface['ifaceid']);
                //    var_dump($file);
                // $this->processor->processCron($file);
                $this->processor->processCron($rawFile, $iface);

                //Clean up file

                //    file_put_contents($this->fileName . "-" . $iface['ifaceid'], "");
            }
        } else {
            echo $this->mod . " Cron Disabled" . PHP_EOL;
            return;
        }
    }
}
