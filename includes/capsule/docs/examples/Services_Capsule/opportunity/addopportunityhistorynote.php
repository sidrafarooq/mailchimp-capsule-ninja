<?php
set_include_path(
    get_include_path() . PATH_SEPARATOR . 
    dirname(dirname(dirname(__FILE__))));
    

require_once 'Services/Capsule.php';

include '../config.php';

$note = 'bazinga- ' . sha1(time());

try {
    $capsule = new Services_Capsule($config['appName'], $config['token']);
    $res = $capsule->opportunity->history->addNote($config['opportunityId'], $note);
} catch (Services_Capsule_Exception $e) {
    print_r($e);
    die();
}
echo 'New Note created: ' . $note . PHP_EOL;
var_dump($res); die();
