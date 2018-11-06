<?php

require_once 'Image/GraphViz.php';

$graph = new Image_GraphViz();

$graph->addNode('Router', array('URL'   => 'http://link1', 'label' => 'Router', 'shape' => 'box'));

$graph->addNode('Master', array('URL'   => 'http://link1', 'label' => 'Master', 'shape' => 'box'));

$graph->addNode('Slave 1', array('URL'   => 'http://link1', 'label' => 'Slave 1', 'shape' => 'box'));
$graph->addNode('Slave 2', array('URL'   => 'http://link1', 'label' => 'Slave 2', 'shape' => 'box'));
$graph->addNode('Slave 3', array('URL'   => 'http://link1', 'label' => 'Slave 3', 'shape' => 'box'));
$graph->addNode('Slave 4', array('URL'   => 'http://link1', 'label' => 'Slave 4', 'shape' => 'box'));


$graph->addEdge(array('Slave 1' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 2' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 3' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 4' => 'Master'), array('color' => 'blue'));

$graph->addEdge(array('Master' => 'Router'), array('color' => 'green'));

$graph->addEdge(array('Master' => 'Router'), array('color' => 'green'));

$graph->image();

?>

