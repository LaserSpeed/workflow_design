<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once __DIR__ . '/Workflow.php';


$obj = new Workflow();

// $obj->set_value("workflow1", "description1");
// $obj->create();

// $obj->add_step('workflow1','step3', 'description', '3', 'person', '22222');

// $obj->load('workflow1');
// $obj->print();


// $obj->set_value("workflow2", "description2");
// $obj->update("workflow");

// $obj->delete('workflow2');

// $obj->delete_step(135);

$obj->update_step(13, "step1", "description1", "1", "Groups", "HRD");