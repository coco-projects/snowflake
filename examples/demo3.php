<?php

    use Coco\snowflake\Snowflake;

    require '../vendor/autoload.php';
    $datacenterId = 1;
    $workerId     = 1;

    $snowflake = new Snowflake($datacenterId, $workerId);

    $snowflake->setStartTimeStamp(strtotime('2016-12-30') * 1000);

    echo $snowflake->id();
