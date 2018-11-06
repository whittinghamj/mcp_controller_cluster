<?php

require_once 'Image/GraphViz.php';

include('/mcp_cluster/db.php');
include('/mcp_cluster/functions.php');

$graph = new Image_GraphViz();

$nodes 		= get_nodes();

foreach($nodes as $node)
{
	if($node['type'] == 'slave')
	{
		$graph->addNode('Slave '.$node['id'], array('label' => 'Slave '.$node['id'], 'shape' => 'box', 'fillcolor' => ($node['status']=='online'? 'darkseagreen1':'coral1'), 'style' => 'filled'));
	}
}

$graph->addNode('Master', array('label' => 'Master', 'shape' => 'box', 'fillcolor' => 'darkseagreen1', 'style' => 'filled'));

$graph->addNode('Router', array('label' => 'Router', 'shape' => 'box'));


foreach($nodes as $node)
{
	if($node['type'] == 'slave')
	{
		$graph->addEdge(array('Slave '.$node['id'] => 'Master'), array('color' => 'blue'));

	}
}

$graph->addEdge(array('Master' => 'Router'), array('color' => 'green'));


$graph->image();

?>

