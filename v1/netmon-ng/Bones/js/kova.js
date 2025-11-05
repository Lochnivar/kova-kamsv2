function getNavBar() {
  $.post("Workers/common.php", { action: "getNavBar" }, function (data) {
    $(".topnav").html(data);
  });
  $.post("Workers/common.php", { action: "getSiteName" }, function (data) {
    console.log(data);
    $("#siteName").html(data);
  });
}

function getUDPTable() {
  $.cookie("page", "udpUnified");
  $.cookie("iface", "all");

  $.post("Workers/index-unified.php", function (data) {
    $("#mainDisplay").html(data);
    loadGauges();
  }).done(function () {
    $(".ifaceLink").each(function () {
      $(this)
        .off("click")
        .on("click", function () {
          $.cookie("page", "ifaceUnified");
          $.cookie("iface", $(this).attr("iface"));
          $.cookie("alias", $(this).attr("alias"));
          loadIfacePage();
        });
    });
  });
}

function reloadPage() {
  var page = $.cookie("page");

  switch (page) {
    case "udpUnified":
      getUDPTable();
      break;
    case "ifaceUnified":
      loadIfacePage();
      break;
  }
}

function loadIfacePage(iface) {
  var iname = $.cookie("iface");
  var alias = $.cookie("alias");

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

function loadGauges() {
  var opts = {
    angle: 0.15, // The span of the gauge arc
    lineWidth: 0.44, // The line thickness
    radiusScale: 1, // Relative radius
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

  $.post("Workers/common.php", { action: "getIfaces" }, function (data) {
    var ifaces = JSON.parse(data);

    $.post("Workers/dataWorker.php", { action: "getUDPAvgs" }, function (data) {
      var udpData = JSON.parse(data);
      opts["colorStart"] = "#6FADCF";
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

        //        $("#gauge-number-" + ifaceName).html(ifaceData['hour']);
        //	$("#gauge-thresh-" + ifaceName).html(ifaceThresh);

        //$("#gauge-" + ifaceName).attr('title', "Threshold: " + ifaceThresh + " ppm");

        if (ifaceData["hour"] < ifaceThresh) {
          opts["colorStart"] = "#DA1102";
          $("#table-gauge-" + ifaceName).addClass("alert alert-danger fw-bold");
          $("#gauge-number-" + ifaceName).html(
            "Interface Below Threshold (" + ifaceThresh + " ppm)"
          );
          $("#gauge-thresh-" + ifaceName).html(
            "Interface Below Threshold (" + ifaceThresh + " ppm)"
          );
        } else {
          $("#table-gauge-" + ifaceName).addClass("alert alert-success");
          $("#gauge-number-" + ifaceName).html("Interface OK");
        }

        //Gauge Stuff.

        var target = document.getElementById("gauge-" + ifaceName); // your canvas element
        var gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
        gauge.maxValue = ifaceThresh * 4; // set max gauge value
        gauge.setMinValue(0); // Prefer setter over gauge.minValue = 0
        gauge.animationSpeed = 4; // set animation speed (32 is default value)
        gauge.set(ifaceData["hour"]); // set actual value
      });
    });
  });
}

function getIfaceGraph(ifaceData) {
  /* 


  
  Morris.Area({
    element: "ethgraph",
    data: [ethdata],
    lineColors: ["red", "green"],
    xkey: "x",
    ykeys: ["y", "z"],
    labels: ["Input", "Output"],
    postUnits: [" KBs"],
    fillOpacity: ".7",
    behaveLikeLine: "true"
  }).on("click", function (i, row) {
    console.log(i, row);
  });
*/
}
