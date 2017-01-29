<?php

abstract class Database {
    protected $conn;

    public function connect() {
        global $CONFIG;
        if (is_null($this->conn)) {
            try {
                $this->conn = new PDO($CONFIG['db']['connection_string'], $CONFIG['db']['user'], $CONFIG['db']['password']);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die('database connection failed');
            }
        }
    }

    public function query_params($query, $params=null, $get_id=null) {
        if (is_null($params)) {
            $params = array();
        }
        try {
            $query = $this->preprocess($query, $get_id);
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $param) {
                if ($key == "data") {
                    $stmt->bindValue(':' . $key, $param, PDO::PARAM_LOB);
                } else if ($key == "limit") {
                    $stmt->bindValue(':' . $key, (int)trim($param), PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':' . $key, $param);
                }
            }
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            if (is_null($get_id)) {
                return $stmt;
            } else {
                return $this->get_last_id($stmt, $get_id);
            }
        } catch (PDOException $e) {
            die('database exception');
        }
    }

    protected abstract function preprocess($query, $get_id);
    protected abstract function get_last_id($stmt, $get_id);
    public abstract function random();
    public abstract function interval($s);
    public abstract function get_datetime($s);
    public abstract function decode_data($s);
    public abstract function encode_bool($s);
}

class MySqlDatabase extends Database {
    protected function preprocess($query, $get_id) {
        return $query;
    }

    protected function get_last_id($stmt, $get_id) {
        return $this->conn->lastInsertId($get_id);
    }

    public function random() {
        return 'RAND()';
    }

    public function interval($s) {
        return sprintf("INTERVAL %s", $s);
    }

    public function get_datetime($s) {
        return DateTime::createFromFormat('Y-m-d H:i:s', $s);
    }

    public function decode_data($s) {
        return $s;
    }

    public function encode_bool($s) {
        return $s;
    }
}

class PostgreSqlDatabase extends Database {
    protected function preprocess($query, $get_id) {
        if (!is_null($get_id)) {
            return $query . ' RETURNING ' . $get_id;
        }
        return $query;
    }

    protected function get_last_id($stmt, $get_id) {
        $row = $stmt->fetch();
        return $row[$get_id];
    }

    public function random() {
        return 'RANDOM()';
    }

    public function interval($s) {
        return sprintf("INTERVAL '%s'", $s);
    }

    public function get_datetime($s) {
        return DateTime::createFromFormat('Y-m-d H:i:s.u', $s);
    }

    public function decode_data($s) {
        return stream_get_contents($s);
    }

    public function encode_bool($s) {
        return $s ? 't' : 'f';
    }
}

