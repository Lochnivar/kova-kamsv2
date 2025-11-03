<?php

// Testing whether or not proc_open will block processes from opening.


$sports = ["ttyS4", "ttyS5"];

$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
    2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
);

$pipes = [];
$processes = [];

foreach ($sports as $sport) {

$cmd = "timeout 30 cat /dev/" . $sport;

echo $cmd . PHP_EOL;

    $proc = proc_open("timeout 30 cat /dev/" . $sport, $descriptorspec, $procPipes);



   // $cmd = "cat /dev/" . $sport;

    $processes[$sport] = $proc;

    stream_set_blocking($procPipes[1], 0);

    $pipes[$sport] = $procPipes;
}

// Run in a loop until all subprocesses finish.
while (array_filter($processes, function ($proc) {
    return proc_get_status($proc)['running'];
})) {
    foreach ($sports as $sport) {
        usleep(10 * 1000); // 100ms
        // Read all available output (unread output is buffered).
        $str = fread($pipes[$sport][1], 1024);
        if ($str) {
            printf($str);
        }
    }
}

// Close all pipes and processes.
foreach ($sports as $sport) {
    fclose($pipes[$sport][1]);
    proc_close($processes[$sport]);
}

