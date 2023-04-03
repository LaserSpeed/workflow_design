<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once __DIR__ . '/Workflow.php';


$obj = new Workflow();

// $obj->set_values("workflow2", "description2");
// $obj->create();

// $obj->set_step_values('step', 'description', '1', 'person', '00002', 'workflow2');
// $obj->add_step();

// $obj->set_values("workflow1", "description1");
// $obj->update("workflow2");

// $obj->delete('workflow1');

// $obj->set_step_values('step1', 'description1', '1', 'Group', '4200');
// $obj->update_step(154);

// $obj->delete_step(154);

$obj->load('workflow1');
$obj->print();
