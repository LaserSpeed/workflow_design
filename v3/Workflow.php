<?php
// include the path for create a database connection
include_once __DIR__ . "/connection.php";
include_once __DIR__ . "/Step.php";


class Workflow extends Step
{
    private $conn;
    private $id;
    private $name;
    private $description;
    private $created_at;
    private $steps = array();
    private $step_count = 0;
    private $workflow_table;

    public function __construct()
    {
        parent::__construct();

        $this->conn = connect_db();
        $data = json_decode(file_get_contents(__DIR__ . '/config.json'), TRUE);
        $this->workflow_table = $data['workflow_table'];
    }

    public function set_name($workflow_name)
    {
        $this->name = $workflow_name;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_description($workflow_description)
    {
        $this->description = $workflow_description;
    }

    public function get_description()
    {
        return $this->description;
    }

    public function set_values($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function create()
    {
        try {
            $sql = "
                INSERT INTO " . $this->workflow_table . " SET workflow_name = :workflow_name, workflow_description = :workflow_description
            ";


            $stmt = $this->conn->prepare($sql);

            $name = htmlspecialchars(strip_tags($this->name));
            $description = htmlspecialchars(strip_tags($this->description));


            $stmt->bindParam("workflow_name", $name);
            $stmt->bindParam("workflow_description", $description);

            if ($stmt->execute()) {
                $this->show_status(true);
                return true;
            } else {
                $this->show_status(false);
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    public function load($name)
    {
        $get_workflow = "
            SELECT * FROM " . $this->workflow_table . " WHERE workflow_name = :workflow_name
        ";
        $stmt = $this->conn->prepare($get_workflow);
        $stmt->bindParam(':workflow_name', $name);
        if ($stmt->execute()) {
            $num_of_rows = $stmt->rowCount();
            if ($num_of_rows > 0) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($results as $r) {
                    $this->id = $r['workflow_id'];
                    $this->name = $r['workflow_name'];
                    $this->description = $r['workflow_description'];
                    $this->created_at = $r['created_at'];
                }

                // get the steps
                $step_stmt = Step::load_step($this->id);
                if ($step_stmt != null) {
                    $result = $step_stmt->fetchAll(PDO::FETCH_ASSOC);

                    // get each step and insert into the step array
                    foreach ($result as $step) {
                        $step_array = array(
                            "step_id" => $step['step_id'],
                            "step_name" => $step['step_name'],
                            "step_description" => $step['step_description'],
                            "step_order" => $step['step_order'],
                            "step_type" => $step['step_type'],
                            "step_handleby" => $step['step_handleby'],
                        );

                        $this->steps[] = $step_array;
                        $this->step_count++;
                    }
                }
            } else {
                return false;
            }
        }
    }

    public function print()
    {
        echo "\nWorkflow Details";
        echo "\nId   : " . $this->id;
        echo "\nName : " . $this->name;
        echo "\nDescription : " . $this->description;
        echo "\nCreation Time : " . $this->created_at;
        echo "\n\nSteps details:";
        echo "\nTotal steps: " . $this->step_count;
        foreach ($this->steps as $step) {
            echo "\n\nID: " . $step['step_id'];
            echo "\nName: " . $step['step_name'];
            echo "\nDescription: " . $step['step_description'];
            echo "\nOrder: " . $step['step_order'];
            echo "\nType: " . $step['step_type'];
            echo "\nHandleby: " . $step['step_handleby'];
        }
    }


    public function update($workflow_name)
    {
        try {
            $this->get_id_by_name($workflow_name);

            $update_sql = "
                UPDATE " . $this->workflow_table . " SET workflow_name = :workflow_name, workflow_description = :workflow_description WHERE workflow_id = :workflow_id;
            ";

            $stmt = $this->conn->prepare($update_sql);

            // clean data
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->description = htmlspecialchars(strip_tags($this->description));

            // bind data
            $stmt->bindParam("workflow_id", $this->id);
            $stmt->bindParam("workflow_name", $this->name);
            $stmt->bindParam("workflow_description", $this->description);

            if ($stmt->execute()) {
                $this->show_status(true);
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    public function delete($workflow_name)
    {
        try {
            $this->get_id_by_name($workflow_name);
            if (Step::delete_steps($this->id)) {
                $sql1 = "
                DELETE FROM " . $this->workflow_table . " WHERE workflow_name = :workflow_name
                ";
                $stmt1 = $this->conn->prepare($sql1);
                $stmt1->bindParam("workflow_name", $workflow_name);
                if ($stmt1->execute()) {
                    $this->show_status(true);
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    private function get_id_by_name($workflow_name)
    {
        $search_sql = '
        SELECT workflow_id FROM ' . $this->workflow_table . ' WHERE workflow_name = :workflow_name;
        ';
        $stmt = $this->conn->prepare($search_sql);
        $stmt->bindParam('workflow_name', $workflow_name);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $row['workflow_id'];
            }
        }
    }

    public function set_step_values($step_name, $step_description, $step_order, $step_type, $step_handleby, $workflow_name = null)
    {
        if (is_null($workflow_name)) {
            $this->id = null;
        } else {
            //since the workflow id is the foreign key 
            //so first have to fetch workflow id by using given workflow_name
            $this->get_id_by_name($workflow_name);
        }

        // call function to add the step 
        Step::set_step_values($this->id, $step_name, $step_description, $step_order, $step_type, $step_handleby);
    }

    public function add_step()
    {
        if (Step::add_steps()) {
            $this->show_status(true);
            return true;
        } else {
            $this->show_status(false);
            return false;
        }
    }

    public function delete_step($step_id)
    {
        if (Step::delete($step_id)) {
            return true;
        } else
            return false;
    }

    public function update_step($step_id)
    {
        // update the values by the id
        if (Step::update($step_id)) {
            $this->show_status(true);
            return true;
        } else
            return false;
    }

    private function show_status($status)
    {
        if ($status)
            echo "Success";
        else
            echo "Failed";
    }
}
