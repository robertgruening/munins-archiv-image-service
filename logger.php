<?php

class DummyLogg {
    public function debug($message) {
        $fp = fopen('logs/log.txt', 'a');//opens file in append mode
        fwrite($fp, $message."\n");  
        fclose($fp);  
    }
    public function info($message) {
        $fp = fopen('logs/log.txt', 'a');//opens file in append mode
        fwrite($fp, $message."\n");  
        fclose($fp);
    }
    public function warn($message) { }
    public function error($message) { }
}

$logger = new DummyLogg();