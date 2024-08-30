<?php

    use Coco\snowflake\Snowflake;

    require '../vendor/autoload.php';

    $snowflake = new Snowflake();

    echo $snowflake->id();
