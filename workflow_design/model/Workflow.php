<?php

// include the path for create a database connection
include_once __DIR__ . "/../connection/connection.php";

class Workflow
{
    private $conn; // connection 

    // table names
    private $workflow_table = "workflow";
    private $step_table = "workflow_step";



    // constructor to make a connection to the database
    public function __construct()
    {
        $this->conn = connect_db();
    }



    // method to get all the workflow and the steps in the form of an json format

    // It gives the list of all the information for all the workflow and related steps for
    // each workflow
    function loads()
    {
        try {
            $sql = "SELECT w.workflow_id, w.workflow_name, w.workflow_description, w.created_at, s.step_id, s.step_name, s.step_order, s.step_type, s.step_handleby FROM " . $this->workflow_table . " w LEFT JOIN " . $this->step_table . " s ON w.workflow_id = s.workflow_id ORDER BY w.workflow_id, s.step_order";

            $stmt = $this->conn->prepare($sql);

            // Execute query and fetch results as associative array
            $stmt->execute();

            $num_of_rows = $stmt->rowCount();

            if ($num_of_rows > 0) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Group results by workflow ID
                $workflows = array(); // create an empty array to store the objects

                // looping through all of the workflow object 
                foreach ($results as $row) {

                    // get the workflow id
                    $workflowId = $row['workflow_id'];

                    if (!isset($workflows[$workflowId])) {
                        $workflows[$workflowId] = array(
                            'workflow_id' => $workflowId,
                            'workflow_name' => $row['workflow_name'],
                            'workflow_description' => $row['workflow_description'],
                            'created_at' => $row['created_at'],
                            'steps' => array()
                        );
                    }

                    // it will check if there any steps available or not for the workflow
                    // if not it just skip it 
                    // or it will add the step along with the information to the associate array
                    if ($row['step_id'] === null) {
                        continue;
                    } else {
                        $workflows[$workflowId]['steps'][] = array(
                            'step_id' => $row['step_id'],
                            'step_name' => $row['step_name'],
                            'step_order' => $row['step_order'],
                            'step_type' => $row['step_type'],
                            'step_handleby' => $row['step_handleby']
                        );
                    }
                }
                // print results as JSON object
                echo json_encode(array_values($workflows));
            } else {
                echo json_encode(array(
                    'Message' => 'No workflow is created yet'
                ));
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }





    // load single workflow and the steps associate with it

    // this is similar to the privious function but it only fetch one particular workflow 
    // and the relevent information only related to that particular workflow
    public function load($id)
    {

        try {
            $sql = "SELECT w.workflow_id, w.workflow_name, w.workflow_description, w.created_at, s.step_id, s.step_name, s.step_order, s.step_type, s.step_handleby FROM " . $this->workflow_table . " w LEFT JOIN " . $this->step_table . " s ON w.workflow_id = s.workflow_id WHERE w.workflow_id = :workflow_id ORDER BY s.step_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam("workflow_id", $id);

            // Execute query and fetch results as associative array
            $stmt->execute();
            $num_of_rows = $stmt->rowCount();
            // var_dump($num_of_rows);
            if ($num_of_rows > 0) {

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Group results by workflow ID
                $workflow = null;

                foreach ($results as $row) {

                    if ($workflow === null) {
                        $workflow = array(
                            'workflow_id' => $row['workflow_id'],
                            'workflow_name' => $row['workflow_name'],
                            'workflow_description' => $row['workflow_description'],
                            'created_at' => $row['created_at'],
                            'steps' => array()
                        );
                    }

                    // it will check if there any steps available or not for the workflow
                    // if not it just skip it 
                    // or it will add the step along with the information to the associate array
                    if ($row['step_id'] === null) {
                        continue;
                    } else {
                        $workflow['steps'][] = array(
                            'step_id' => $row['step_id'],
                            'step_name' => $row['step_name'],
                            'step_order' => $row['step_order'],
                            'step_type' => $row['step_type'],
                            'step_handleby' => $row['step_handleby']
                        );
                    }
                }
                // print results as JSON object
                echo json_encode($workflow);
            } else {
                echo json_encode(array(
                    'Message' => 'No workflow is found in this id'
                ));
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }





    // create a new workflow
    public function create($name, $description)
    {
        try {
            $sql = "
                INSERT INTO " . $this->workflow_table . " SET workflow_name = :workflow_name, workflow_description = :workflow_description
            ";


            $stmt = $this->conn->prepare($sql);

            // clean data and bind parameter to avoid SQL injection and make sql query secure
            // clean data
            $name = htmlspecialchars(strip_tags($name));
            $description = htmlspecialchars(strip_tags($description));

            // bind data
            $stmt->bindParam("workflow_name", $name);
            $stmt->bindParam("workflow_description", $description);

            if ($stmt->execute()) {
                echo json_encode(array(
                    'Message' => 'Workflow created successfully'
                ));
            } else {
                echo json_encode(array(
                    'Message' => 'Workflow can not be created due to some error'
                ));
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }


    // function to update only the workflow

    // First it will search the workflow is present or not using the id 
    // If found then update, otherwise show an alert
    public function update($id, $name, $description)
    {
        try {
            $search_sql = '
                SELECT * FROM ' . $this->workflow_table . ' WHERE workflow_id = :workflow_id;
            ';
            $stmt = $this->conn->prepare($search_sql);
            $stmt->bindParam('workflow_id', $id);
            $stmt->execute();
            $row_count = $stmt->rowCount();

            if ($row_count == 1) {
                $update_sql = "
                    UPDATE " . $this->workflow_table . " SET workflow_name = :workflow_name, workflow_description = :workflow_description WHERE workflow_id = :workflow_id;
                ";

                $stmt = $this->conn->prepare($update_sql);

                // clean data
                $name = htmlspecialchars(strip_tags($name));
                $description = htmlspecialchars(strip_tags($description));

                // bind data
                $stmt->bindParam("workflow_id", $id);
                $stmt->bindParam("workflow_name", $name);
                $stmt->bindParam("workflow_description", $description);

                if ($stmt->execute()) {
                    echo json_encode(array(
                        'Message' => 'Workflow updated successfully'
                    ));
                } else {
                    echo json_encode(array(
                        'Message' => 'Workflow can not be updated due to some error'
                    ));
                }
            } else {
                echo json_encode(array(
                    'Message' => 'Workflow not found in this id'
                ));
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }



    // function will take the id of the workflow and will delete it
    // steps are also delete by default because workflow_id is working as a foreign key
    public function delete($id)
    {
        try {
            $sql = "
                DELETE FROM " . $this->step_table . " WHERE workflow_id = :workflow_id
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam("workflow_id", $id);
            if ($stmt->execute()) {
                $sql1 = "
                DELETE FROM " . $this->workflow_table . " WHERE workflow_id = :workflow_id
                ";
                $stmt1 = $this->conn->prepare($sql1);
                $stmt1->bindParam("workflow_id", $id);
                if ($stmt1->execute()) {
                    echo json_encode(array(
                        'Message' => 'Workflow deleted successfully'
                    ));
                } else {
                    echo json_encode(array(
                        'Message' => 'Workflow can not be deleted due to some error'
                    ));
                }
            } else {
                echo json_encode(array(
                    'Message' => 'Falied to delete due to some error'
                ));
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }




    // this function is responsible for create a new workflow step
    // this function is private and calling by another function "add_step"
    private function create_step($workflow_id, $step_name, $step_order, $step_type, $step_handleby)
    {
        try {
            $sql = "
            INSERT INTO " . $this->step_table . " SET step_name = :step_name, workflow_id = :workflow_id, step_order = :step_order, step_type = :step_type, step_handleby = :step_handleby
            ";

            $stmt = $this->conn->prepare($sql);

            // clean data and bind parameter to avoid SQL injection and make sql query secure
            // clean data
            $workflow_id = htmlspecialchars(strip_tags($workflow_id));
            $step_name = htmlspecialchars(strip_tags($step_name));
            $step_order = htmlspecialchars(strip_tags($step_order));
            $step_type = htmlspecialchars(strip_tags($step_type));
            $step_handleby = htmlspecialchars(strip_tags($step_handleby));

            // bind data
            $stmt->bindParam("workflow_id", $workflow_id);
            $stmt->bindParam("step_name", $step_name);
            $stmt->bindParam("step_order", $step_order);
            $stmt->bindParam("step_type", $step_type);
            $stmt->bindParam("step_handleby", $step_handleby);

            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }



    // step related function


    // add a step related to a workflow
    public function add_step($workflow_id, $step_name, $step_order, $step_type, $step_handleby)
    {
        // method
        // 1. Retrieve the existing steps for the selected workflow from the database and store them in an array.
        // 2. Determine the position where the new step needs to be added based on the user input. Let's say the new step needs to be added after step 2.
        // 3. Loop through the existing steps array and update the step_order values for the steps that come after the new step position. In our example, we would update the step_order for steps 3 and onwards.
        // 4. Insert the new step into the database with the updated step_order value.


        // take the new position to insert the step
        $new_order = $step_order;

        $select_sql = "
            SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id
        ";

        $stmt = $this->conn->prepare($select_sql);
        $stmt->bindParam("workflow_id", $workflow_id);
        if ($stmt->execute()) {
            $num_of_rows = $stmt->rowCount();
            if ($num_of_rows == 0) {

                // add the very first step 
                if ($this->create_step($workflow_id, $step_name, $step_order, $step_type, $step_handleby)) {
                    echo json_encode(array(
                        'Message' => 'Step created successfully'
                    ));
                } else {
                    echo json_encode(array(
                        'Message' => 'Step can not be created'
                    ));
                }
            } else {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $steps[] = $row;
                }

                // update the steps that comes after the new step
                foreach ($steps as $step) {
                    if ($step['step_order'] >= $new_order) {
                        $stepID = $step['step_id'];
                        $new_step_order = $step['step_order'] + 1;
                        $update_sql = "
                                UPDATE " . $this->step_table . " SET step_order = :step_order where step_id = :step_id;
                            ";
                        $stmt = $this->conn->prepare($update_sql);
                        $stmt->bindParam("step_order", $new_step_order);
                        $stmt->bindParam("step_id", $stepID);
                        if ($stmt->execute())
                            continue;
                        else
                            break;
                    }
                }

                // add the new step in the correct position
                if ($this->create_step($workflow_id, $step_name, $step_order, $step_type, $step_handleby)) {
                    echo json_encode(array(
                        'Message' => 'Step created successfully'
                    ));
                } else {
                    echo json_encode(array(
                        'Message' => 'Step can not be created'
                    ));
                }
            }
        }
    }

    // simply update the step if found
    public function update_step($id, $step_name, $step_order, $step_type, $step_handleby)
    {
        try {
            $search_sql = '
            SELECT * FROM ' . $this->step_table . ' WHERE step_id = :step_id;
            ';
            $stmt = $this->conn->prepare($search_sql);
            $stmt->bindParam('step_id', $id);
            $stmt->execute();
            $row_count = $stmt->rowCount();
            if ($row_count) {
                $sql = "
                UPDATE " . $this->step_table . " SET step_name = :step_name, step_order = :step_order, step_type = :step_type, step_handleby = :step_handleby WHERE step_id = :step_id;
                ";

                $stmt = $this->conn->prepare($sql);

                // clean data
                $step_name = htmlspecialchars(strip_tags($step_name));
                $step_order = htmlspecialchars(strip_tags($step_order));
                $step_type = htmlspecialchars(strip_tags($step_type));
                $step_handleby = htmlspecialchars(strip_tags($step_handleby));

                // bind data
                $stmt->bindParam("step_id", $id);
                $stmt->bindParam("step_name", $step_name);
                $stmt->bindParam("step_order", $step_order);
                $stmt->bindParam("step_type", $step_type);
                $stmt->bindParam("step_handleby", $step_handleby);

                if ($stmt->execute()) {
                    echo json_encode(array(
                        'Message' => 'Step updated successfully'
                    ));
                } else {
                    echo json_encode(array(
                        'Message' => 'Step can not be updated'
                    ));
                }
            } else {
                echo json_encode(array(
                    'Message' => 'Step can not be found'
                ));
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }


    // delete a step
    // First it will delete the step if found by id
    // then look for other step which have greater step_order and if found then all are decrement by 1 to maintain the order
    public function delete_step($id)
    {
        try {
            $search_sql = '
                SELECT * FROM ' . $this->step_table . ' WHERE step_id = :step_id;
            ';
            $stmt = $this->conn->prepare($search_sql);
            $stmt->bindParam('step_id', $id);
            $stmt->execute();
            $row_count = $stmt->rowCount();
            if ($row_count) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $workflow_id = $row['workflow_id'];
                    $step_order = $row['step_order'];

                    // delete the steps
                    $delete_sql = "
                        DELETE FROM " . $this->step_table . " WHERE workflow_id = :workflow_id AND step_id = :step_id;
                    ";

                    // prepare, bind and execute
                    $delete_stmt = $this->conn->prepare($delete_sql);
                    $delete_stmt->bindParam("workflow_id", $workflow_id);
                    $delete_stmt->bindParam("step_id", $id);

                    // if successfully deleted it will update further steps
                    if ($delete_stmt->execute()) {

                        // update the steps after deleted
                        $update_sql = "
                        UPDATE " . $this->step_table . " SET `step_order` = `step_order` - 1 WHERE workflow_id = :workflow_id AND step_order > :step_order
                        ";

                        $update_stmt = $this->conn->prepare($update_sql);
                        $update_stmt->bindParam("workflow_id", $workflow_id);
                        $update_stmt->bindParam("step_order", $step_order);

                        if ($update_stmt->execute()) {
                            echo json_encode(array(
                                'Message' => 'Step delete successfully'
                            ));
                        } else {
                            echo json_encode(array(
                                'Message' => 'Remaining steps are not updated'
                            ));
                        }
                    } else {
                        echo json_encode(array(
                            'Message' => 'Step can not be deleted'
                        ));
                    }
                }
            } else {
                echo json_encode(array(
                    'Message' => 'Step can not be found'
                ));
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    // load all the step in the form of json array of object
    public function load_step()
    {
        try {
            $sql = "
            SELECT s.step_id, s.workflow_id, w.workflow_name, s.step_order, s.step_name, s.step_type, s.step_handleby FROM " . $this->step_table . " s LEFT JOIN workflow w ON s.workflow_id = w.workflow_id ORDER BY s.workflow_id ASC, s.step_order ASC;
            ";

            $stmt = $this->conn->prepare($sql);
            if ($stmt->execute()) {
                $no_of_records = $stmt->rowCount();
                if ($no_of_records > 0) {
                    $step_array = array();

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                        $item = array(
                            "step_id" => $step_id,
                            "workflow_name" => $workflow_name,
                            "step_order" => $step_order,
                            "step_name" => $step_name,
                            "step_type" => $step_type,
                            "step_handleby" => $step_handleby,
                        );

                        array_push($step_array, $item);
                    }

                    echo json_encode($step_array);
                } else {
                    echo json_encode(
                        array("Message" => "No steps found")
                    );
                }
            } else {
                echo json_encode(
                    array("Message" => "Failed to execute the sql query")
                );
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }
}
