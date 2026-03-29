<?php

$descriptorspec = [
    0 => ['pipe', 'r'],  // stdin
    1 => ['pipe', 'w'],  // stdout
    2 => ['pipe', 'w'],   // stderr
];

$password = 'Temppass.0!';
$cmd = 'ssh -o StrictHostKeyChecking=no fwber@pdx1-shared-a1-33.dreamhost.com "cd ~/fwber.me/fwber-backend && git checkout . && git pull origin main && ./deploy.sh --env=production --force"';

echo "Starting remote deployment...\n";
$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    echo "Waiting for password prompt...\n";
    // We'll read from stderr/stdout to see if we get the prompt
    // but some SSH clients write prompt directly to tty.
    // Let's try writing immediately after a shorter sleep.
    sleep(3);
    echo "Sending password...\n";
    fwrite($pipes[0], $password."\n");
    fflush($pipes[0]);
    // Leave it open for a bit more output if any

    echo "Reading output...\n";
    while (! feof($pipes[1])) {
        echo fread($pipes[1], 1024);
    }
    while (! feof($pipes[2])) {
        echo fread($pipes[2], 1024);
    }

    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $return_value = proc_close($process);
    echo "\nProcess returned $return_value\n";
}
