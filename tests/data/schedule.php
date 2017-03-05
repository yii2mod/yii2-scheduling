<?php

/* @var $schedule \yii2mod\scheduling\Schedule */
$schedule->exec('ls')->description('Show list of files')->everyMinute();
$schedule->command('migrate')->description('Execute migrations')->daily();
