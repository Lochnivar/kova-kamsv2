<?php

namespace Kova\Unified;

require kova_path('app/bootstrap.php');

use Kova\Unified\Modules\Common\Config;
use Kova\Unified\Modules\Home\Home;
use Kova\Unified\Modules\Common\Common;
use Kova\Unified\Modules\UDP\Workers\Dataworker as udpDataworker;
use Kova\Unified\Modules\Serial\Workers\Dataworker as serialDataworker;
use Kova\Unified\Modules\Motorola\Workers\Dataworker as motoDataworker;
use Kova\Unified\Modules\SysHealth\SysHealth;

foreach ($_POST as $k => $v) {
    $$k = $v;
}
$config = new Config();
$configs = $config->config;
$cargo = "";

switch ($action) {
    case "getHome":

        $cargo = new Home($configs);
        echo $cargo->buildHome();
        break;

    case "getNavBar":
        $cargo = new Common($configs);
        echo $cargo->getNavBar();

        break;
    case "getSiteName":
        $cargo = new Common($configs);
        echo $cargo->getSiteName();
        break;

    case "getIfaces":
        $cargo = new Common($configs);
        echo json_encode($cargo->getIfaces($mod));
        break;

    default;

    case "getUDPAvgs":
        $cargo = new udpDataworker($configs);

        echo json_encode($cargo->getUDPAvgs());

        break;

    case "getSerialAvgs":
        $cargo = new serialDataworker($configs);

        echo json_encode($cargo->getSerialAvgs());

        break;

    case "getSysHealth":
        $cargo = new SysHealth;
        echo $cargo->getSystHealthSum();

        break;

    case "getMotoData":
        $cargo = new motoDataworker($configs);

        echo json_encode($cargo->getMotoData());

        break;
}
