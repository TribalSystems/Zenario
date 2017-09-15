<?php

// Just start the server and connect to it. It will count the number of bytes
// sent for each connection and will print the average throughput once the
// connection closes.
//
// $ php examples/03-benchmark.php 8000
// $ telnet localhost 8000
// $ echo hello world | nc -v localhost 8000
// $ dd if=/dev/zero bs=1M count=1000 | nc -v localhost 8000
//
// You can also run a secure TLS benchmarking server like this:
//
// $ php examples/03-benchmark.php 8000 examples/localhost.pem
// $ openssl s_client -connect localhost:8000
// $ echo hello world | openssl s_client -connect localhost:8000
// $ dd if=/dev/zero bs=1M count=1000 | openssl s_client -connect localhost:8000

use React\EventLoop\Factory;
use React\Socket\Server;
use React\Socket\ConnectionInterface;
use React\Socket\SecureServer;

require __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();

$server = new Server($loop);

// secure TLS mode if certificate is given as second parameter
if (isset($argv[2])) {
    $server = new SecureServer($server, $loop, array(
        'local_cert' => $argv[2]
    ));
}

$server->listen(isset($argv[1]) ? $argv[1] : 0);

$server->on('connection', function (ConnectionInterface $conn) use ($loop) {
    echo '[connected]' . PHP_EOL;

    // count the number of bytes received from this connection
    $bytes = 0;
    $conn->on('data', function ($chunk) use (&$bytes) {
        $bytes += strlen($chunk);
    });

    // report average throughput once client disconnects
    $t = microtime(true);
    $conn->on('close', function () use ($conn, $t, &$bytes) {
        $t = microtime(true) - $t;
        echo '[disconnected after receiving ' . $bytes . ' bytes in ' . round($t, 3) . 's => ' . round($bytes / $t / 1024 / 1024, 1) . ' MiB/s]' . PHP_EOL;
    });
});

$server->on('error', 'printf');

echo 'bound to ' . $server->getPort() . PHP_EOL;

$loop->run();
