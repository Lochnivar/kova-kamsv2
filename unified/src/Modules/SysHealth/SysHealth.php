<?php

namespace Kova\Kams\Unified\Modules\SysHealth;

use Kova\Kams\Unified\Modules\Common\Communicator;

class SysHealth
{
    public function __construct() {}

    public function getSysHealth() {}

    public function getSystHealthSum()
    {

        $comms = new Communicator();

        $hosts = $comms->getZBXHosts();

        $hostids = [];
        $hostnames = [];

        foreach ($hosts as $host) {
            $hostids[] = $host['hostid'];
            $hostnames[$host['hostid']] = $host['name'];
        }


        $zbxStatus = $comms->getZBXHostHealth($hostids);

        $srvSVGs = $this->getServerSVGs();
        $cargo = "<table style='border:none;'><tbody><tr>";

        foreach ($zbxStatus as $zbx) {
            $cargo .= '<td id="' . $zbx['hostid'] . '">';

            // Fix: Use == for comparison, not = for assignment
            // Also handle inconsistent structure: 'ok' is array, 'bad' is string
            if ($zbx['lastvalue'] == '1') {
                $cargo .= $srvSVGs['ok']['svgCode'];
            } else {
                $cargo .= $srvSVGs['bad'];
            }

            $cargo .= '<br />' . $hostnames[$zbx['hostid']] . '</td>';
        }

        $cargo .= "</tr></tbody></table>";



        return $cargo;
    }


    private function getServerSVGs()
    {

        $serverSVGs = [

            'ok' => [
                "svgCode" => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="green" class="bi bi-server" viewBox="0 0 16 16">
  <path d="M1.333 2.667C1.333 1.194 4.318 0 8 0s6.667 1.194 6.667 2.667V4c0 1.473-2.985 2.667-6.667 2.667S1.333 5.473 1.333 4z"/>
  <path d="M1.333 6.334v3C1.333 10.805 4.318 12 8 12s6.667-1.194 6.667-2.667V6.334a6.5 6.5 0 0 1-1.458.79C11.81 7.684 9.967 8 8 8s-3.809-.317-5.208-.876a6.5 6.5 0 0 1-1.458-.79z"/>
  <path d="M14.667 11.668a6.5 6.5 0 0 1-1.458.789c-1.4.56-3.242.876-5.21.876-1.966 0-3.809-.316-5.208-.876a6.5 6.5 0 0 1-1.458-.79v1.666C1.333 14.806 4.318 16 8 16s6.667-1.194 6.667-2.667z"/>
</svg>'
            ],
            'bad' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="red" class="bi bi-server" viewBox="0 0 16 16">
  <path d="M1.333 2.667C1.333 1.194 4.318 0 8 0s6.667 1.194 6.667 2.667V4c0 1.473-2.985 2.667-6.667 2.667S1.333 5.473 1.333 4z"/>
  <path d="M1.333 6.334v3C1.333 10.805 4.318 12 8 12s6.667-1.194 6.667-2.667V6.334a6.5 6.5 0 0 1-1.458.79C11.81 7.684 9.967 8 8 8s-3.809-.317-5.208-.876a6.5 6.5 0 0 1-1.458-.79z"/>
  <path d="M14.667 11.668a6.5 6.5 0 0 1-1.458.789c-1.4.56-3.242.876-5.21.876-1.966 0-3.809-.316-5.208-.876a6.5 6.5 0 0 1-1.458-.79v1.666C1.333 14.806 4.318 16 8 16s6.667-1.194 6.667-2.667z"/>
</svg>'



        ];

        return $serverSVGs;
    }
}
