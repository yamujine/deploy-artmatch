#!/usr/bin/env php
<?php
define("WEBROOT_PATH", "/var/www/html");
define("BRANCH_TO_FOLLOW", "master");
define("DEFAULT_TIMEZONE", "Asia/Seoul");

date_default_timezone_set(DEFAULT_TIMEZONE);

$payload = json_decode($_POST["payload"]);
$log = '';

if ($payload !== NULL && $payload->ref === 'refs/heads/' . BRANCH_TO_FOLLOW) {
    // Modify UTC time to local time
    $dt = new DateTime($payload->head_commit->timestamp);
    $dt->setTimezone(new DateTimeZone(DEFAULT_TIMEZONE));

    $log .= $dt->format('Y-m-d H:i:s') . ' ' . DEFAULT_TIMEZONE . PHP_EOL;
    $log .= "Last Commit: " . $payload->head_commit->id . PHP_EOL;
    $log .= "Author: " . $payload->head_commit->author->name . PHP_EOL;
    $log .= $payload->head_commit->message . PHP_EOL;

    // Execute git command to sync
    $git_sync_command = '';
    $git_sync_command .= 'cd ' . WEBROOT_PATH;
    $git_sync_command .= ' && git checkout -f master';
    $git_sync_command .= ' && git reset HEAD --hard';
    $git_sync_command .= ' && git pull';
    exec($git_sync_command, $result_data, $result_code);

    // To check git command output and return value
    if ((strpos($result_data[1], "Updating") === false && strpos($result_data[1], "Already up-to-date.") === false) || $result_code !== 0) {
        $log .= PHP_EOL . "Somethings Wrong";
        // TODO: What to do when error occurs
    } else {
        $log .= PHP_EOL . "Successfully Updated";
    }

    echo $log;
}
