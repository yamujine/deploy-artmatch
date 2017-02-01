<?php
define('WEBROOT_PATH', '/home/pickartyou/webroot');
define('BRANCH_TO_FOLLOW', 'master');
define('DEFAULT_TIMEZONE', 'Asia/Seoul');
date_default_timezone_set(DEFAULT_TIMEZONE);

$payload = NULL;
$log = '================================================================' . PHP_EOL;
if (isset($_POST['payload']))
    $payload = json_decode($_POST['payload']);

if ($payload === NULL)
    exit('Not supported!' . PHP_EOL);

if ($payload !== NULL) {
    if ($payload->ref === 'refs/heads/' . BRANCH_TO_FOLLOW) {
        // Modify UTC time to local time
        $dt = new DateTime($payload->head_commit->timestamp);
        $dt->setTimezone(new DateTimeZone(DEFAULT_TIMEZONE));

        $log .= $dt->format('Y-m-d H:i:s') . ' ' . DEFAULT_TIMEZONE . PHP_EOL;
        $log .= 'Last Commit: ' . $payload->head_commit->id . PHP_EOL;
        $log .= 'Author: ' . $payload->head_commit->author->name . PHP_EOL;
        $log .= $payload->head_commit->message . PHP_EOL;
    } else {
        exit('Not supported branch' . PHP_EOL);
    }
}

// Execute git command to sync
$git_sync_command = '';
$git_sync_command .= 'cd ' . WEBROOT_PATH;
$git_sync_command .= ' && git checkout -f ' . BRANCH_TO_FOLLOW;
$git_sync_command .= ' && git reset HEAD --hard';
$git_sync_command .= ' && git pull';
exec($git_sync_command, $result_data, $result_code);

$result = array_filter($result_data, function($data) {
    return (strpos($data, 'Updating') !== FALSE || strpos($data, 'up-to-date') !== FALSE);
});

// To check git command output and return value
if ($result_code !== 0 || count($result) === 0) {
    $log .= PHP_EOL . var_dump($result_data);
    $log .= PHP_EOL . 'Response code: ' . $result_code . PHP_EOL;
    $log .= PHP_EOL . 'Somethings Wrong'. PHP_EOL;
    // TODO: What to do when error occurs
} else {
    $log .= PHP_EOL . 'Successfully Updated' . PHP_EOL;
}

file_put_contents('./deploy_log', $log, FILE_APPEND | LOCK_EX);
