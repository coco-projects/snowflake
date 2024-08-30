<?php

    use Coco\snowflake\Snowflake;

    require '../vendor/autoload.php';
    $datacenterId = 1;
    $workerId     = 1;

    $snowflake = new Snowflake($datacenterId, $workerId);

    echo $snowflake->id();
