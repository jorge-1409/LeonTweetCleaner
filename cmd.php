<?php
/**
 * Created by Asier Marqués.
 * User: Asier
 * Date: 16/03/13
 * Time: 19:07
 */

$loader = require __DIR__ . "/vendor/autoload.php";
$loader->add("Simettric", __DIR__ . "/src");


use Symfony\Component\Console\Application;

$console = new Application();
$console->add(new \Simettric\Leon\Leon(__DIR__ . '/config.ini',
                                       __DIR__ . '/data',
                                       __DIR__ . '/log/leon.log'));
$console->run();









