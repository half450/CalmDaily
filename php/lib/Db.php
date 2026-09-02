<?php

/**
 * 使用 mysqli（须启用 mysqli 扩展）。
 * 对外保持与 PDO 相同的用法，供配置读写调用。
 */
class Db
{
    /** @var DbMysqli|null */
    private static $db = null;

    public static function get($cfg)
    {
        if (self::$db instanceof DbMysqli) {
            return self::$db;
        }

        if (!class_exists('mysqli', false)) {
            throw new Exception('mysqli extension not loaded');
        }

        global $mysqli;
        if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
            throw new Exception('global $mysqli not set; require php/db.php from bootstrap.php');
        }
        if ($mysqli->connect_errno) {
            throw new Exception('db connect failed: ' . $mysqli->connect_error);
        }

        self::$db = new DbMysqli($mysqli);
        self::$db->exec('SET NAMES utf8mb4');

        $dbTz = isset($cfg['db_time_zone']) ? $cfg['db_time_zone'] : '+08:00';
        self::$db->exec('SET time_zone = ' . self::$db->quote($dbTz));

        return self::$db;
    }
}

class DbMysqli
{
    /** @var mysqli */
    private $conn;
    /** @var bool */
    private $inTx = false;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function exec($sql)
    {
        if ($sql === null || trim((string) $sql) === '') {
            return 0;
        }
        $ok = $this->conn->query($sql);
        if ($ok === false) {
            throw new Exception('db exec failed: ' . $this->conn->error);
        }
        return $this->conn->affected_rows;
    }

    public function query($sql)
    {
        $res = $this->conn->query($sql);
        if ($res === false) {
            throw new Exception('db query failed: ' . $this->conn->error);
        }
        if ($res === true) {
            return new DbMysqliResult(null);
        }

        return new DbMysqliResult($res);
    }

    public function prepare($sql)
    {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('db prepare failed: ' . $this->conn->error);
        }

        return new DbMysqliStmt($this->conn, $stmt);
    }

    public function beginTransaction()
    {
        if ($this->inTx) {
            return true;
        }
        $ok = false;
        if (method_exists($this->conn, 'begin_transaction')) {
            $ok = $this->conn->begin_transaction();
        } else {
            $ok = $this->conn->query('START TRANSACTION');
        }
        if (!$ok) {
            throw new Exception('db beginTransaction failed: ' . $this->conn->error);
        }
        $this->inTx = true;

        return true;
    }

    public function commit()
    {
        $ok = $this->conn->commit();
        if (!$ok) {
            throw new Exception('db commit failed: ' . $this->conn->error);
        }
        $this->inTx = false;

        return true;
    }

    public function rollBack()
    {
        $ok = $this->conn->rollback();
        if (!$ok) {
            throw new Exception('db rollBack failed: ' . $this->conn->error);
        }
        $this->inTx = false;

        return true;
    }

    public function inTransaction()
    {
        return $this->inTx;
    }

    public function lastInsertId()
    {
        return (string) $this->conn->insert_id;
    }

    public function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'" . $this->conn->real_escape_string((string) $value) . "'";
    }
}

class DbMysqliResult
{
    /** @var mysqli_result|null */
    private $res;

    public function __construct($res)
    {
        $this->res = $res;
    }

    public function fetch()
    {
        if (!$this->res) {
            return false;
        }
        $row = $this->res->fetch_assoc();

        return $row ? $row : false;
    }

    public function fetchAll()
    {
        $rows = array();
        if (!$this->res) {
            return $rows;
        }
        while ($row = $this->res->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
}

class DbMysqliStmt
{
    /** @var mysqli */
    private $conn;
    /** @var mysqli_stmt */
    private $stmt;
    /** @var array|null */
    private $lastResultRows = null;

    public function __construct(mysqli $conn, mysqli_stmt $stmt)
    {
        $this->conn = $conn;
        $this->stmt = $stmt;
    }

    public function execute($params)
    {
        if (!is_array($params)) {
            $params = array();
        }
        $this->lastResultRows = null;

        if (!empty($params)) {
            $this->bindParams($params);
        }

        $ok = $this->stmt->execute();
        if (!$ok) {
            throw new Exception('db execute failed: ' . $this->stmt->error);
        }

        return true;
    }

    public function fetch()
    {
        $rows = $this->fetchAll();
        if (empty($rows)) {
            return false;
        }

        return $rows[0];
    }

    public function fetchAll()
    {
        if ($this->lastResultRows !== null) {
            return $this->lastResultRows;
        }
        $this->lastResultRows = $this->fetchAllInternal();

        return $this->lastResultRows;
    }

    private function bindParams($params)
    {
        $types = '';
        $bind = array();
        foreach ($params as $p) {
            if (is_int($p) || is_bool($p)) {
                $types .= 'i';
                $bind[] = (int) $p;
            } elseif (is_float($p)) {
                $types .= 'd';
                $bind[] = (double) $p;
            } elseif ($p === null) {
                $types .= 's';
                $bind[] = null;
            } else {
                $types .= 's';
                $bind[] = (string) $p;
            }
        }

        $refs = array();
        $refs[] = $types;
        for ($i = 0; $i < count($bind); $i++) {
            $refs[] = &$bind[$i];
        }

        $ok = call_user_func_array(array($this->stmt, 'bind_param'), $refs);
        if (!$ok) {
            throw new Exception('db bind_param failed: ' . $this->stmt->error);
        }
    }

    private function fetchAllInternal()
    {
        if (method_exists($this->stmt, 'get_result')) {
            $gr = $this->stmt->get_result();
            if (!$gr) {
                return array();
            }

            $rows = array();
            while ($row = $gr->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        $meta = $this->stmt->result_metadata();
        if (!$meta) {
            return array();
        }

        $row = array();
        $bind = array();
        while ($field = $meta->fetch_field()) {
            $row[$field->name] = null;
            $bind[] = &$row[$field->name];
        }
        $meta->free();

        $ok = call_user_func_array(array($this->stmt, 'bind_result'), $bind);
        if (!$ok) {
            throw new Exception('db bind_result failed: ' . $this->stmt->error);
        }

        $rows = array();
        while ($this->stmt->fetch()) {
            $copy = array();
            foreach ($row as $key => $value) {
                $copy[$key] = $value;
            }
            $rows[] = $copy;
        }

        return $rows;
    }
}
