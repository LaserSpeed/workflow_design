<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once __DIR__ . '/Workflow.php';


$obj = new Workflow();

// $obj->set_values("workflow1", "description1");
// $obj->create();

// $obj->add_step('workflow','step4', 'description4', '3', 'person', '222');

$obj->load('workflow');
$obj->print();


// $obj->set_values("workflow", "description");
// $obj->update("workflow1");

// $obj->delete('workflow');

// $obj->update_step(142, "step3", "description3", "3", "Group", "HR");
// $obj->delete_step(142);
