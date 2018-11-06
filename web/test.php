<?php
require_once 'Image/GraphViz.php';

$gv = new Image_GraphViz();
$gv->addEdge(array('Router'        => 'MCP Master'));
$gv->addEdge(array('MCP Master' => 'MCP Slave 1'));
$gv->addEdge(array('MCP Master' => 'MCP Slave 2'));
$gv->addEdge(array('MCP Master' => 'MCP Slave 3'));
$gv->addEdge(array('MCP Master' => 'MCP Slave 4'));
$gv->image();
?>