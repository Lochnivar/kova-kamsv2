function getNavBar() {
  $.post("src/Dispatcher.php", { action: "getNavBar" }, function (data) {
    $(".topnav").html(data);
  });
  $.post("src/Dispatcher.php", { action: "getSiteName" }, function (data) {
    console.log(data);
    $("#siteName").html(data);
  });
}

function getUDPTable() {
  $.post("Workers/index-unified.php", function (data) {
    $("#mainDisplay").html(data);
    loadGauges();
  }).done(function () {
    $(".ifaceLink").each(function () {
      $(this)
        .off("click")
        .on("click", function () {
          loadIfacePage($(this));
        });
    });
  });
}

function getHome() {
  console.log("Getting Home");
  $.post("src/Dispatcher.php", { action: "getHome" }, function (data) {
    //   console.log("Home Data");
    //   console.log(data);
    displayData(data);
    loadUDPSumGauges();

    setInterval(function () { reloadHome() }, 60000);
  }).done();

}

function getAdmin() {
  console.log("Getting Admin");
  $.post("src/Dispatcher.php", { action: "getAdmin" }, function (data) {
    $("#mainDisplay").html(data);
  }).fail(function(xhr, status, error) {
    console.error("Error loading admin:", error);
    $("#mainDisplay").html("<div style='padding:20px;color:red;'>Error loading admin page: " + error + "</div>");
  });
}

function reloadHome() {
  $.post("src/Dispatcher.php", { action: "getSysHealth" }, function (data) {

    $("#sysHealthSum").html(data);

    loadUDPSumGauges();

  });
}

function displayData(data) {
  $("#mainDisplay").html(data);
}

function loadIfacePage(iface) {
  var iname = iface.attr("iface");
  var alias = iface.attr("alias");

  $.post("Workers/index-iface.php", function (data) {
    $("#mainDisplay").html(data);
  }).done(function () {
    $.post(
      "Workers/dataWorker.php",
      { action: "getIfaceChartData", iface: iname },
      function (data) {
        var ifaceChart = new Morris.Area({
          element: "graph",
          lineColors: ["blue"],
          xkey: "x",
          ykeys: ["y"],
          labels: ["Input"],
          postUnits: [" Pkts"],
          fillOpacity: ".7"
        }).on("click", function (i, row) {
          console.log(i, row);
        });

        ifaceChart.setData(JSON.parse(data));
      }
    );

    $.post(
      "Workers/dataWorker.php",
      { action: "getUDPAvgs", iface: iname, alias: alias },
      function (data) {
        var udpData = JSON.parse(data);
        console.log(udpData);
        $.each(udpData[iname], function (key, value) {
          console.log(iname + "~" + key + "~" + value);
          $("#UDPAVG-" + key).html(value);
        });
      }
    );
  });
}

function loadUDPSumGauges() {
  var opts = {
    angle: 0.15, // The span of the gauge arc
    lineWidth: 0.44, // The line thickness
    radiusScale: .9, // Relative radius
    pointer: {
      length: 0.6, // // Relative to gauge radius
      strokeWidth: 0.035, // The thickness
      color: "#000000" // Fill color
    },
    limitMax: false, // If false, max value increases automatically if value > maxValue
    limitMin: false, // If true, the min value of the gauge will be fixed
    colorStart: "#6FADCF", // Colors
    colorStop: "#8FC0DA", // just experiment with them
    strokeColor: "#E0E0E0", // to see which ones work best for you
    generateGradient: true,
    highDpiSupport: true // High resolution support
  };


  var udpEnabled = $("#udpEnabled").css("display");

  if (udpEnabled == "block") {
    console.log("Udp Enabled Value " + udpEnabled);
    $.post("src/Dispatcher.php", { action: "getIfaces", mod: "udp" }, function (data) {
      console.log(data);
      var ifaces = JSON.parse(data);

      $.post("src/Dispatcher.php", { action: "getUDPAvgs" }, function (data) {
        var udpData = JSON.parse(data);
        console.log("Getting UDP Data");
        console.log(udpData);

        $.each(ifaces, function (key, value) {
          var ifaceName = value["name"];
          var ifaceThresh = value["threshold"];
          var ifaceAlias = value["alias"];

          var ifaceData = udpData[ifaceName];

          console.log(ifaceData);

          $.each(ifaceData, function (ifacekey, ifacevalue) {
            $("#" + ifaceName + "-" + ifacekey).html(ifacevalue);
          });

          //Gauge Stuff.

          var target = document.getElementById("gauge-" + ifaceName); // your canvas element

          console.log(ifaceName);

          var gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
          gauge.maxValue = ifaceThresh * 4; // set max gauge value
          gauge.setMinValue(0); // Prefer setter over gauge.minValue = 0
          gauge.animationSpeed = 4; // set animation speed (32 is default value)
          gauge.set(ifaceData["hour"]); // set actual value

          $('#lastpacket-' + ifaceName).html(ifaceData['lastTime']);
        });
      });
    });
  }
  else {
    console.log("UDP Disabled");
  }
  console.log("Getting Serial Data");

  var serialEnabled = $("#serialEnabled").css("display");

  if (serialEnabled == "block") {

    $.post("src/Dispatcher.php", { action: "getIfaces", mod: "serial" }, function (data) {
      console.log(data);
      var ifaces = JSON.parse(data);

      $.post("src/Dispatcher.php", { action: "getSerialAvgs" }, function (data) {
        var serialData = JSON.parse(data);
        console.log("Getting UDP Data");
        console.log(serialData);

        $.each(ifaces, function (key, value) {
          var ifaceName = value["name"];
          var ifaceThresh = value["threshold"];
          var ifaceAlias = value["alias"];

          var ifaceData = serialData[ifaceName];

          console.log(ifaceData);

          $.each(ifaceData, function (ifacekey, ifacevalue) {
            $("#" + ifaceName + "-" + ifacekey).html(ifacevalue);
          });

          //Gauge Stuff.

          var target = document.getElementById("gauge-" + ifaceName); // your canvas element

          console.log(ifaceName);

          var gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
          gauge.maxValue = ifaceThresh * 4; // set max gauge value
          gauge.setMinValue(0); // Prefer setter over gauge.minValue = 0
          gauge.animationSpeed = 4; // set animation speed (32 is default value)
          gauge.set(ifaceData["hour"]); // set actual value

          $('#lastpacket-' + ifaceName).html(ifaceData['lastTime']);
        });
      });
    });

  }
  else {
    console.log("Serial Disabled");
  }

  var motoEnabled = $("#motoEnabled").css("display");

  if (motoEnabled == "block") {

    $.post("src/Dispatcher.php", { action: "getMotoData" }, function (data) {
      var motoData = JSON.parse(data);
      console.log("Getting Moto Data");
      console.log(motoData);

      $('#motoActive').html(motoData['activeChannels']);
      $('#motoWarnings').html(motoData['issues']);
      $('#motoNM').html(motoData['notMonitored']);

     });

  }

}

