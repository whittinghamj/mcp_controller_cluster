<?php

class SomeThreadedClass extends Thread
{
    private $tID;
    public $data;
 
    public function __construct(int $tID)
    {
        $this->tID = $tID;
        $this->data = $tID . ":" . date('H:i:s');
    }
 
    public function run()
    {
        echo $this->tID . " started.\n";
        sleep($this->tID);
        echo $this->tID . " ended. " . date('H:i:s') . "\n";
    }
}
 
$threads = [];
 
for ($i = 1; $i < 5; $i++) {
    $threads[$i] = new SomeThreadedClass($i);
    $threads[$i]->start();          // start the job on the background
}
 
for ($i = 1; $i < 5; $i++) {
    $threads[$i]->join();           // wait until job is finished, 
    echo $threads[$i]->data . "\n"; // then we can access the data
}

$threads[$i]->start();

$threads[$i]->join();