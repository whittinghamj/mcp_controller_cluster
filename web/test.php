<?php

require_once 'Image/GraphViz.php';

$graph = new Image_GraphViz();

$graph->addNode('Slave 1', array('label' => 'Slave 1', 'shape' => 'box', 'fillcolor' => 'darkseagreen1', 'style' => 'filled'));
$graph->addNode('Slave 2', array('label' => 'Slave 2', 'shape' => 'box', 'fillcolor' => 'darkseagreen1', 'style' => 'filled'));
$graph->addNode('Slave 3', array('label' => 'Slave 3', 'shape' => 'box', 'fillcolor' => 'darkseagreen1', 'style' => 'filled'));
$graph->addNode('Slave 4', array('label' => 'Slave 4', 'shape' => 'box', 'fillcolor' => 'darkseagreen1', 'style' => 'filled'));

$graph->addNode('Master', array('label' => 'Master', 'shape' => 'box', 'fillcolor' => 'darkseagreen1', 'style' => 'filled'));

$graph->addNode('Router', array('label' => 'Router', 'shape' => 'box'));


$graph->addEdge(array('Slave 1' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 2' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 3' => 'Master'), array('color' => 'blue'));
$graph->addEdge(array('Slave 4' => 'Master'), array('color' => 'blue'));

$graph->addEdge(array('Master' => 'Router'), array('color' => 'green'));


$graph->image();

?>

