<?php
// include the path for create a database connection
include_once __DIR__ . "/connection.php";


class Step
{
    private $conn;
    private $id;
    private $name;
    private $description;
    private $workflow_id;
    private $order;
    private $type;
    private $handledby;
    private $step_table;

    public function set_step_name($step_name)
    {
        $this->name = $step_name;
    }

    public function get_step_name()
    {
        return $this->name;
    }

    public function set_step_description($step_description)
    {
        $this->description = $step_description;
    }

    public function get_step_description()
    {
        return $this->description;
    }

    public function set_step_order($step_order)
    {
        $this->order = $step_order;
    }

    public function get_step_order()
    {
        return $this->order;
    }

    public function set_step_type($step_type)
    {
        $this->type = $step_type;
    }

    public function get_step_type()
    {
        return $this->type;
    }

    public function set_step_handleby($step_handledby)
    {
        $this->handledby = $step_handledby;
    }

    public function get_step_handleby()
    {
        return $this->handledby;
    }

    public function __construct()
    {
        $this->conn = connect_db();

        // get details about the database tables from the json config file
        // and assign it to the private member of the class
        $data = json_decode(file_get_contents(__DIR__ . '/config.json'), TRUE);
        $this->step_table = $data['step_table'];
    }

    public function set_value($workflow_id, $step_name, $step_description, $step_order, $step_type, $step_handledby)
    {
        $this->workflow_id = $workflow_id;
        $this->name = $step_name;
        $this->description = $step_description;
        $this->order = $step_order;
        $this->type = $step_type;
        $this->handledby = $step_handledby;
    }

    private function create()
    {
        try {
            $sql = "
            INSERT INTO " . $this->step_table . " SET workflow_id = :workflow_id, step_name = :step_name, step_description = :step_description, step_order = :step_order, step_type = :step_type, step_handleby = :step_handleby
            ";

            $stmt = $this->conn->prepare($sql);

            // clean data and bind parameter to avoid SQL injection and make sql query secure
            // clean data
            $name = htmlspecialchars(strip_tags($this->name));
            $description = htmlspecialchars(strip_tags($this->description));
            $workflow_id = htmlspecialchars(strip_tags($this->workflow_id));
            $order = htmlspecialchars(strip_tags($this->order));
            $type = htmlspecialchars(strip_tags($this->type));
            $handleby = htmlspecialchars(strip_tags($this->handledby));

            // bind data
            $stmt->bindParam("step_name", $name);
            $stmt->bindParam("step_description", $description);
            $stmt->bindParam("workflow_id", $workflow_id);
            $stmt->bindParam("step_order", $order);
            $stmt->bindParam("step_type", $type);
            $stmt->bindParam("step_handleby", $handleby);

            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    public function add_step()
    {
        $new_order = $this->order;

        $select_sql = "
            SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id
        ";

        $stmt = $this->conn->prepare($select_sql);
        $stmt->bindParam("workflow_id", $this->workflow_id);
        if ($stmt->execute()) {
            $num_of_rows = $stmt->rowCount();
            if ($num_of_rows == 0) {

                // add the very first step 
                if ($this->create()) {
                    return true;
                } else {
                    return false;
                }
            } else {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $steps[] = $row;
                }

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
                if ($this->create()) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    public function load($workflow_id)
    {
        $sql = "
            SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id ORDER BY step_order
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam('workflow_id', $workflow_id);
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0)
                return $stmt;
            else
                return null;
        } else
            return null;
    }

    public function delete($id)
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
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    public function update($id)
    {
        try {
            $select_sql = '
            SELECT * FROM ' . $this->step_table . ' WHERE step_id = :step_id;
            ';
            $select_stmt = $this->conn->prepare($select_sql);
            $select_stmt->bindParam('step_id', $id);
            $select_stmt->execute();
            $row_count = $select_stmt->rowCount();
            // var_dump($row_count);
            if ($row_count == 1) {

                // echo json_encode("inside");
                $sql = "
                UPDATE " . $this->step_table . " SET step_name = :step_name, step_description = :step_description, step_order = :step_order, step_type = :step_type, step_handleby = :step_handleby WHERE step_id = :step_id;
                ";

                $stmt = $this->conn->prepare($sql);


                // clean data
                $step_name = htmlspecialchars(strip_tags($this->name));
                $step_description = htmlspecialchars(strip_tags($this->description));
                $step_order = htmlspecialchars(strip_tags($this->order));
                $step_type = htmlspecialchars(strip_tags($this->type));
                $step_handleby = htmlspecialchars(strip_tags($this->handledby));

                // bind data
                $stmt->bindParam("step_id", $id);
                $stmt->bindParam("step_name", $step_name);
                $stmt->bindParam("step_description", $step_description);
                $stmt->bindParam("step_order", $step_order);
                $stmt->bindParam("step_type", $step_type);
                $stmt->bindParam("step_handleby", $step_handleby);

                if ($stmt->execute())
                    return true;
                else
                    return false;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    public function delete_steps($workflow_id)
    {
        try {
            $sql = "
                DELETE FROM " . $this->step_table . " WHERE workflow_id = :workflow_id;
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam('workflow_id', $workflow_id);
            if ($stmt->execute())
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }
}