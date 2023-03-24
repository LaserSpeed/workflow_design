<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once __DIR__ . '/model/Workflow.php';

$obj = new Workflow();


// $obj->load(86);

// $obj->create('workflow_3', 'description_3');

// $obj->update(86, "worflow_3", "description_3");


// $obj->delete(86);

// $obj->add_step(87, 'step', 1, 'Person', '50012');

// $obj->update_step(115, 'step1', 1, 'Person', '50012');

$obj->delete_step(117);

// $obj->load_step();
$obj->loads();
