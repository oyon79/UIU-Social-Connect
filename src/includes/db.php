<?php
require_once 'config.php';

// Database Connection Class
class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function query($sql, $params = [])
    {
        // If parameters are provided, use a prepared statement
        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                return false;
            }

            // Only bind parameters if the statement expects them
            if ($stmt->param_count > 0 && count($params) > 0) {
                // Build types string
                $types = '';
                foreach ($params as $p) {
                    if (is_int($p)) $types .= 'i';
                    elseif (is_double($p) || is_float($p)) $types .= 'd';
                    elseif (is_null($p)) $types .= 's';
                    else $types .= 's';
                }

                // Bind parameters dynamically
                $bindNames = [];
                $bindNames[] = &$types;
                for ($i = 0; $i < count($params); $i++) {
                    $bindNames[] = &$params[$i];
                }
                call_user_func_array([$stmt, 'bind_param'], $bindNames);
            }

            if (!$stmt->execute()) {
                $stmt->close();
                return false;
            }

            // If get_result is available (mysqlnd), use it
            if (method_exists($stmt, 'get_result')) {
                $result = $stmt->get_result();
                if ($result instanceof mysqli_result) {
                    $rows = $result->fetch_all(MYSQLI_ASSOC);
                    $result->free();
                    $stmt->close();
                    return $rows;
                }
            } else {
                // Fallback when get_result isn't available: bind result variables
                $meta = $stmt->result_metadata();
                if ($meta) {
                    $fields = [];
                    $row = [];
                    $bindVars = [];
                    while ($field = $meta->fetch_field()) {
                        $bindVars[] = &$row[$field->name];
                    }
                    if (!empty($bindVars)) {
                        call_user_func_array([$stmt, 'bind_result'], $bindVars);
                        $rows = [];
                        while ($stmt->fetch()) {
                            $r = [];
                            foreach ($row as $key => $val) {
                                // Copy values because references will change
                                $r[$key] = $val;
                            }
                            $rows[] = $r;
                        }
                        $meta->free();
                        $stmt->close();
                        return $rows;
                    }
                }
            }

            // Non-select (update/insert/delete)
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected !== false ? true : false;
        }

        // No params: execute directly
        $result = $this->conn->query($sql);
        if ($result === false) {
            return false;
        }

        if ($result instanceof mysqli_result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $rows;
        }

        // Non-select queries
        return true;
    }

    public function prepare($sql)
    {
        return $this->conn->prepare($sql);
    }

    public function escape($string)
    {
        return $this->conn->real_escape_string($string);
    }

    public function lastInsertId()
    {
        return $this->conn->insert_id;
    }
}

// Get database instance
function getDB()
{
    return Database::getInstance()->getConnection();
}
