<?php

require_once 'Image/GraphViz.php';

$graph = new Image_GraphViz();

$graph->addNode('Router', array('label' => 'Router', 'shape' => 'box'));

$graph->addNode('Master', array('label' => 'Master', 'shape' => 'box'));

$graph->addNode('Slave 1', array('label' => 'Slave 1', 'shape' => 'box', 'fillcolor' => 'green', 'style' => 'filled')));
$graph->addNode('Slave 2', array('label' => 'Slave 2', 'shape' => 'box', 'color' => 'green')));
$graph->addNode('Slave 3', array('label' => 'Slave 3', 'shape' => 'box', 'color' => 'green')));
$graph->addNode('Slave 4', array('label' => 'Slave 4', 'shape' => 'box', 'color' => 'green'));


$graph->addEdge(array('Slave 1' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 2' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 3' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 4' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Master' => 'Router'), array('color' => 'green'));

$graph->image();

?>

