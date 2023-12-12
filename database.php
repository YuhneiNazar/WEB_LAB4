<?php
class Database {
    private $host = "127.0.0.1:3307";
    private $username = "root";
    private $password = "";
    private $database = "sto";
    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function insertData($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        
        $stmt = $this->conn->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
        
        $values = array_values($data);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        
        $result = $stmt->execute();
        
        if (!$result) {
            die("Error executing query: " . $this->conn->error);
        }
        
        return $result;
    }
    
    
    

    public function updateData($table, $data, $condition) {
        $setClause = "";
        foreach ($data as $key => $value) {
            $setClause .= "$key = '$value', ";
        }
        $setClause = rtrim($setClause, ", ");
        $whereClause = "";
        foreach ($condition as $key => $value) {
            $whereClause .= "$key = '$value' AND ";
        }
        $whereClause = rtrim($whereClause, "AND ");
        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        return $this->conn->query($sql);
    }

    public function deleteData($table, $condition) {
        $whereClause = "";
        foreach ($condition as $key => $value) {
            $whereClause .= "$key = '$value' AND ";
        }
        $whereClause = rtrim($whereClause, "AND ");
        $sql = "DELETE FROM $table WHERE $whereClause";
        return $this->conn->query($sql);
    }

    public function getData($table, $criteria) {
        $whereClause = '';
        if (!empty($criteria)) {
            foreach ($criteria as $key => $value) {
                $whereClause .= "$key = '$value' AND ";
            }
            $whereClause = "WHERE " . rtrim($whereClause, "AND ");
        }
    
        $sql = "SELECT * FROM $table $whereClause";
        $result = $this->conn->query($sql);
    
        if (!$result) {
            die("Error executing query: " . $this->conn->error);
        }
    
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function getDataWithCriteria($table, $column, $value) {
        $whereClause = '';
        if (!empty($column) && !empty($value)) {
            $value = $this->sanitizeInput($value);
            $whereClause = "$column = '$value'";
        }
    
        $sql = "SELECT * FROM $table";
        if (!empty($whereClause)) {
            $sql .= " WHERE $whereClause";
        }
    
        $result = $this->conn->query($sql);
    
        if (!$result) {
            die("Error executing query: " . $this->conn->error);
        }
    
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    
        return $data;
    }
    

    public function sanitizeInput($input) {
        return $this->conn->real_escape_string($input);
    }

    public function calculateServiceCostPerCar() {
        $sql = "SELECT cars.id AS car_id, cars.model AS car_model, SUM(posluhy.price) AS total_service_cost
                FROM cars
                LEFT JOIN zamovlennya ON cars.id = zamovlennya.cars_id
                LEFT JOIN posluhy ON zamovlennya.posluhy_id = posluhy.id
                GROUP BY cars.id";

        $result = $this->conn->query($sql);

        if (!$result) {
            die("Error executing query: " . $this->conn->error);
        }

        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

}
?>