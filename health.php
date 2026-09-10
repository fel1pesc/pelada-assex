<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
echo 'ok php=' . PHP_VERSION . PHP_EOL;
echo 'pdo_mysql=' . (in_array('mysql', PDO::getAvailableDrivers(), true) ? 'yes' : 'no') . PHP_EOL;
