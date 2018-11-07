<?php

class MyTask extends Threaded 
{
    private $m_inputs;
    private $m_outputs;


    public function __construct(array $inputs)
    {
        $this->m_inputs = $inputs;
        $this->m_outputs = new Threaded(); // we will store the results in here.
    }


    public function run() 
    {
        foreach ($this->m_inputs as $input)
        {
            // casting the array to an array is not a mistake
            // but actually super important for this to work
            // https://github.com/krakjoe/pthreads/issues/813#issuecomment-361955116
            $this->m_outputs[] = (array)array(
                'property1' => $input * 2,
                'property2' => ($input + 2),
            );
        }
    }

    # Accessors
    public function getResults() { return $this->m_outputs; }
}



function main()
{
    $inputs = range(0,10000);
    $numInputsPerTask = 20;
    $inputGroups = array_chunk($inputs, $numInputsPerTask);
    $numCpus = 4; // I would nomrally dynamically fetch this and sometimes large (e.g. aws C5 instances)
    $numTasks = count($inputGroups);
    $numThreads = min($numTasks, $numCpus); // don't need to spawn more threads than tasks.
    $pool = new Pool($numThreads);
    $tasks = array(); // collection to hold all the tasks to get the results from afterwards.

    foreach ($inputGroups as $inputsForTask)
    {
        $task = new MyTask($inputsForTask);
        $tasks[] = $task;
        $pool->submit($task);
    }


    while ($pool->collect());

    # We could submit more stuff here, the Pool is still waiting for work to be submitted.

    $pool->shutdown();

    # All tasks should have been completed at this point. Get the results!
    $results = array();
    foreach ($tasks as $task)
    {
        $results[] = $task->getResults();
    }

    print "results: " . print_r($results, true);
}

main();