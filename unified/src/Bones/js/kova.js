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
  // Clear existing gauges to prevent stale references
  if (typeof homeGauges !== 'undefined') {
    // Destroy all existing gauges before clearing
    for (var key in homeGauges) {
      if (homeGauges.hasOwnProperty(key)) {
        try {
          if (typeof homeGauges[key].destroy === 'function') {
            homeGauges[key].destroy();
          }
        } catch (e) {
          console.warn("Error destroying gauge during cleanup:", key, e);
        }
      }
    }
    // Reset the object
    homeGauges = {};
  }
  
  $.post("src/Dispatcher.php", { action: "getHome" }, function (data) {
    //   console.log("Home Data");
    //   console.log(data);
    displayData(data);
    // Wait for DOM to be ready before loading gauges
    setTimeout(function() {
      loadUDPSumGauges();
    }, 100);
    // Hide navbar on home page
    $(".topnav").hide();

    setInterval(function () { reloadHome() }, 60000);
  }).fail(function(xhr, status, error) {
    console.error("Error loading home:", error);
    console.error("Response:", xhr.responseText);
    $("#mainDisplay").html("<div style='padding:20px;color:red;'>Error loading home: " + error + "</div>");
  });

}

function getAdmin() {
  console.log("Getting Admin");
  $.post("src/Dispatcher.php", { action: "getAdmin" }, function (data) {
    $("#mainDisplay").html(data);
    // Show navbar when viewing modules
    $(".topnav").show();
  }).fail(function(xhr, status, error) {
    console.error("Error loading admin:", error);
    $("#mainDisplay").html("<div style='padding:20px;color:red;'>Error loading admin page: " + error + "</div>");
  });
}

function getMotorolaChannels() {
  console.log("Getting Motorola Channels");
  $.post("src/Dispatcher.php", { action: "getMotorolaChannels" }, function (data) {
    $("#mainDisplay").html(data);
    // Show navbar when viewing modules
    $(".topnav").show();
  }).fail(function(xhr, status, error) {
    console.error("Error loading Motorola channels:", error);
    $("#mainDisplay").html("<div style='padding:20px;color:red;'>Error loading Motorola channels: " + error + "</div>");
  });
}

function getSerialMonitor() {
  console.log("Getting Serial Monitor");
  $.post("src/Dispatcher.php", { action: "getSerialMonitor" }, function (data) {
    $("#mainDisplay").html(data);
    // Show navbar when viewing modules
    $(".topnav").show();
  }).fail(function(xhr, status, error) {
    console.error("Error loading Serial monitor:", error);
    $("#mainDisplay").html("<div style='padding:20px;color:red;'>Error loading Serial monitor: " + error + "</div>");
  });
}

function getUdpMonitor() {
  console.log("Getting UDP Monitor");
  $.post("src/Dispatcher.php", { action: "getUdpMonitor" }, function (data) {
    $("#mainDisplay").html(data);
    // Show navbar when viewing modules
    $(".topnav").show();
  }).fail(function(xhr, status, error) {
    console.error("Error loading UDP monitor:", error);
    $("#mainDisplay").html("<div style='padding:20px;color:red;'>Error loading UDP monitor: " + error + "</div>");
  });
}

function getSystemMonitor() {
  console.log("Getting System Monitor");
  $.post("src/Dispatcher.php", { action: "getSystemMonitor" }, function (data) {
    $("#mainDisplay").html(data);
    // Show navbar when viewing modules
    $(".topnav").show();
  }).fail(function(xhr, status, error) {
    console.error("Error loading System monitor:", error);
    $("#mainDisplay").html("<div style='padding:20px;color:red;'>Error loading System monitor: " + error + "</div>");
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

// Store gauge instances to prevent conflicts
var homeGauges = {};

/**
 * Common Gauge.js options for all home page gauges
 */
function getGaugeOptions() {
  return {
    angle: 0.15, // The span of the gauge arc
    lineWidth: 0.44, // The line thickness
    radiusScale: .9, // Relative radius
    pointer: {
      length: 0.6, // Relative to gauge radius
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
}

/**
 * Initialize a gauge for a single interface
 * @param {string} moduleName - Module name ('udp' or 'serial')
 * @param {string} ifaceName - Interface name
 * @param {HTMLElement} gaugeElement - Canvas element
 * @param {number} threshold - Threshold value
 * @param {number} hourValue - Current hour value to display
 * @param {object} opts - Gauge.js options
 * @returns {object} Gauge instance
 */
function initializeHomeGauge(moduleName, ifaceName, gaugeElement, threshold, hourValue, opts) {
  if (!gaugeElement) {
    console.error("Gauge element not found for", moduleName, ifaceName);
    return null;
  }

  // Destroy existing gauge if it exists and is valid
  var gaugeKey = moduleName + '-' + ifaceName;
  if (homeGauges[gaugeKey]) {
    try {
      // Check if it's a valid Gauge instance with a destroy method
      if (typeof homeGauges[gaugeKey].destroy === 'function') {
        homeGauges[gaugeKey].destroy();
      } else {
        // If it's not a valid gauge, just remove it from the object
        console.warn("Invalid gauge instance found for", gaugeKey, "- removing it");
      }
      // Clear the reference
      delete homeGauges[gaugeKey];
    } catch (e) {
      console.warn("Error destroying existing gauge:", e);
      // Remove invalid reference
      delete homeGauges[gaugeKey];
    }
  }

  // Create new gauge
  var gauge = new Gauge(gaugeElement).setOptions(opts);
  gauge.maxValue = Math.max((threshold > 0 ? threshold * 4 : 1000), 1000); // Minimum 1000
  gauge.setMinValue(0);
  gauge.animationSpeed = 4;
  gauge.set(hourValue || 0);

  // Store gauge reference
  homeGauges[gaugeKey] = gauge;

  return gauge;
}

function loadUDPSumGauges() {
  var opts = getGaugeOptions();


  var udpEnabled = $("#udpCard").css("display");

  if (udpEnabled == "block") {
    console.log("Udp Enabled Value " + udpEnabled);
    $.post("src/Dispatcher.php", { action: "getIfaces", mod: "udp" }, function (data) {
      console.log("UDP Interfaces:", data);
      try {
        // jQuery auto-parses JSON when Content-Type is application/json
        // If data is already an object, use it directly; otherwise parse it
        var ifaces = (typeof data === 'string') ? JSON.parse(data) : data;
        
        // Handle empty interfaces array
        if (!ifaces || (Array.isArray(ifaces) && ifaces.length === 0) || (typeof ifaces === 'object' && Object.keys(ifaces).length === 0)) {
          console.log("No UDP interfaces configured");
          return;
        }
      } catch (e) {
        console.error("Error parsing UDP interfaces:", e, data);
        return;
      }

      $.post("src/Dispatcher.php", { action: "getUDPAvgs" }, function (data) {
        try {
          // jQuery auto-parses JSON when Content-Type is application/json
          // If data is already an object, use it directly; otherwise parse it
          var udpData = (typeof data === 'string') ? JSON.parse(data) : data;

          // getIfaces returns an object keyed by interface name: { "enp6s18": {interface: "...", label: "...", threshold: ...} }
          // Convert to array of interface objects to iterate
          var ifaceArray = Array.isArray(ifaces) ? ifaces : Object.values(ifaces);
          
          $.each(ifaceArray, function (index, value) {
            // Each interface object has: interface, label, threshold
            var ifaceName = value["interface"] || value["name"] || '';
            var ifaceThresh = parseFloat(value["threshold"]) || 1000; // Default to 1000 if threshold is empty
            var ifaceLabel = value["label"] || value["alias"] || ifaceName;

            if (!ifaceName) {
              console.warn("Interface missing 'interface' field:", value);
              return;
            }

            var ifaceData = udpData[ifaceName] || {};

            //Gauge Stuff - the gauge displays the 'hour' value
            // Use module-prefixed ID to avoid conflicts with Serial gauges
            var target = document.getElementById("gauge-udp-" + ifaceName); // your canvas element

            if (!target) {
              console.warn("Gauge canvas not found for:", ifaceName);
              return;
            }

            var hourValue = parseFloat(ifaceData["hour"]) || 0;
            var gauge = initializeHomeGauge('udp', ifaceName, target, ifaceThresh, hourValue, opts);
            
            if (!gauge) {
              console.error("Failed to create UDP gauge for", ifaceName);
            }

            // Update last packet timestamp - use module-prefixed ID
            var lastPacketEl = $('#lastpacket-udp-' + ifaceName);
            if (lastPacketEl.length) {
              if (ifaceData['lastTime']) {
                lastPacketEl.html(ifaceData['lastTime']);
              } else {
                lastPacketEl.html('No data');
              }
            } else {
              console.warn("Last packet element not found: #lastpacket-udp-" + ifaceName);
            }
          });
        } catch (e) {
          console.error("Error parsing UDP data:", e, data);
        }
      }).fail(function(xhr, status, error) {
        console.error("Error loading UDP averages:", error, xhr.responseText);
      });
    }).fail(function(xhr, status, error) {
      console.error("Error loading UDP interfaces:", error, xhr.responseText);
    });
  }
  else {
    console.log("UDP Disabled");
  }
  console.log("Getting Serial Data");

  var serialEnabled = $("#serialCard").css("display");

  if (serialEnabled == "block") {
    var opts = getGaugeOptions();

    $.post("src/Dispatcher.php", { action: "getIfaces", mod: "serial" }, function (data) {
      console.log("Serial Interfaces:", data);
      try {
        // jQuery auto-parses JSON when Content-Type is application/json
        // If data is already an object, use it directly; otherwise parse it
        var ifaces = (typeof data === 'string') ? JSON.parse(data) : data;
        
        // Handle empty interfaces array
        if (!ifaces || (Array.isArray(ifaces) && ifaces.length === 0) || (typeof ifaces === 'object' && Object.keys(ifaces).length === 0)) {
          console.log("No Serial interfaces configured");
          return;
        }
      } catch (e) {
        console.error("Error parsing Serial interfaces:", e, data);
        return;
      }

      $.post("src/Dispatcher.php", { action: "getSerialAvgs" }, function (data) {
        try {
          // jQuery auto-parses JSON when Content-Type is application/json
          // If data is already an object, use it directly; otherwise parse it
          var serialData = (typeof data === 'string') ? JSON.parse(data) : data;

          // getIfaces returns an object keyed by interface name: { "enp6s18": {interface: "...", label: "...", threshold: ...} }
          // Convert to array of interface objects to iterate
          var ifaceArray = Array.isArray(ifaces) ? ifaces : Object.values(ifaces);
          
          // Simple, clean approach - process each interface
          ifaceArray.forEach(function(value, index) {
            // Each interface object has: interface, label, threshold
            var ifaceName = value["interface"] || value["name"] || '';
            var ifaceThresh = parseFloat(value["threshold"]) || 1000;
            var ifaceLabel = value["label"] || value["alias"] || ifaceName;

            if (!ifaceName) {
              console.warn("Serial interface missing 'interface' field:", value);
              return;
            }

            var ifaceData = serialData[ifaceName] || {};
            
            // Use module-prefixed ID to avoid conflicts with UDP gauges
            var gaugeElement = document.getElementById('gauge-serial-' + ifaceName);
            
            if (!gaugeElement) {
              console.error("Serial gauge canvas not found for:", ifaceName, "- Skipping");
              return;
            }

            // Use common gauge initialization function
            var hourValue = parseFloat(ifaceData["hour"]) || 0;
            var gauge = initializeHomeGauge('serial', ifaceName, gaugeElement, ifaceThresh, hourValue, opts);
            
            if (!gauge) {
              console.error("Failed to create Serial gauge for", ifaceName, "at index", index);
            }
            
            // Update last packet timestamp - use module-prefixed ID
            var lastPacketEl = $('#lastpacket-serial-' + ifaceName);
            if (lastPacketEl.length && ifaceData['lastTime']) {
              lastPacketEl.html(ifaceData['lastTime']);
            } else if (lastPacketEl.length && !ifaceData['lastTime']) {
              lastPacketEl.html('No data');
            }
          });
        } catch (e) {
          console.error("Error parsing Serial data:", e, data);
        }
      }).fail(function(xhr, status, error) {
        console.error("Error loading Serial averages:", error, xhr.responseText);
      });
    }).fail(function(xhr, status, error) {
      console.error("Error loading Serial interfaces:", error, xhr.responseText);
    });

  }
  else {
    console.log("Serial Disabled");
  }

  var motoEnabled = $("#motoCard").css("display");

  if (motoEnabled == "block") {

    $.post("src/Dispatcher.php", { action: "getMotoData" }, function (data) {
      console.log("Moto Data Response:", data);
      try {
        // jQuery auto-parses JSON when Content-Type is application/json
        // If data is already an object, use it directly; otherwise parse it
        var motoData = (typeof data === 'string') ? JSON.parse(data) : data;
        console.log("Getting Moto Data");
        console.log(motoData);

        if ($('#motoActive').length) {
          $('#motoActive').html(motoData['activeChannels'] || 0);
        } else {
          console.warn("Element #motoActive not found");
        }
        
        if ($('#motoWarnings').length) {
          $('#motoWarnings').html(motoData['issues'] || 0);
        } else {
          console.warn("Element #motoWarnings not found");
        }
        
        if ($('#motoNM').length) {
          $('#motoNM').html(motoData['notMonitored'] || 0);
        } else {
          console.warn("Element #motoNM not found");
        }
      } catch (e) {
        console.error("Error parsing Moto data:", e, data);
      }
    }).fail(function(xhr, status, error) {
      console.error("Error loading Moto data:", error, xhr.responseText);
    });

  }

}

