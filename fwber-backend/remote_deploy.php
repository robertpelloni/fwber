<?php
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin
   1 => array("pipe", "w"),  // stdout
   2 => array("pipe", "w")   // stderr
);

$password = "Temppass.0!";
$cmd = 'ssh -o StrictHostKeyChecking=no fwber@pdx1-shared-a1-33.dreamhost.com "cd ~/fwber/fwber-backend && chmod +x deploy.sh && ./deploy.sh --env=production --force"';

$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    // Standard SSH password prompt detection is tricky, we'll just wait a bit and send it.
    sleep(2);
    fwrite($pipes[0], $password . "\n");
    fclose($pipes[0]);

    echo stream_get_contents($pipes[1]);
    echo stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
}
