<?php

require_once 'Image/GraphViz.php';

$graph = new Image_GraphViz();

$graph->addNode('Router', array('URL'   => 'http://link1', 'label' => 'Router', 'shape' => 'box'));

$graph->addNode('Master', array('URL'   => 'http://link1', 'label' => 'Master', 'shape' => 'box'));

// $graph->addEdge(array('Node1' => 'Node2'), array('label' => 'Edge Label'));

$graph->addEdge(array('Router' => 'Master'), array('color' => 'green'));

$graph->image();

?>

