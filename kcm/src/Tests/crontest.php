<?php

include '/usr/src/KAMS-Setting-file.php';

//TIME ZONE NEW YORK for our email times
date_default_timezone_set('America/New_York');
$epoch = time();

$cargo = "";
$carArray = [
    "site" => "lockwood-dev",
    "timestamp" => $epoch,
    "moto" => [
        "alerts" => [
            "active" => [],
            "clear" => []
        ],
        "status" => [

            "activeChannels" => "8",
            "notMonitored" => "1",
            "issues" => "0"
        ]
    ],
    "serial" => [
        "alerts" => [
            "active" => [],
            "clear" => []
        ],
        "status" => [
            "iface1" => [
                "name" => "Serial 0",
                "thru" => "44",
                "threshold" => "30"
            ],
            "iface2" => [
                "name" => "Serial 0",
                "thru" => "44",
                "threshold" => "30"
            ]
            // May be more Interfaces following.
        ]
    ],
    "udp" => [
        "alerts" => [
            "active" => [],
            "clear" => []
        ],
        "status" => [
            "iface1" => [
                "name" => "Viper 1",
                "thru" => "181.03",
                "lastPacket" => "2025-06-26 17:27:51",
                "threshold" => "100"
            ],
            "iface2" => [
                "name" => "Viper 2",
                "thru" => "181.03",
                "lastPacket" => "2025-06-26 17:27:51",
                "threshold" => "100"
            ]
            // May be more Interfaces following.
        ]
    ],
    "zabbix" => [
        "alerts" => [
            "active" => [],
            "clear" => []
        ],
        "status" => [
            "hosts" => [
                "host1" => [
                    "name" => "Zabbix Server",
                    "active" => "1"
                ],
                "host2" => [
                    "name" => "KOVA-REC-1",
                    "active" => "1"
                ],
                "host3" => [
                    "name" => "KOVA-REC-2",
                    "active" => "1"
                ]
                //  May be more hosts following
            ],

        ]
    ],
];

$cargo = base64_encode(json_encode($carArray));

var_dump($cargo);
//system ("ssh -t ".$sshName."@40.142.26.130 '/usr/bin/echo $epoch  > /usr/KAMS/SITES/".$sshName.".txt'",$respnseMain);

system("ssh -t " . $sshName . "@192.168.100.51 '/usr/bin/php /usr/src/KCM/kcm-endpoint.php' $cargo", $respnseMain);

echo $respnseMain . PHP_EOL;
var_dump($respnseMain);
