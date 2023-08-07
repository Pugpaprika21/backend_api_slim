<?php

/**
 * @author Pug_DEV <!>
 * 
 * @override <mysqli>
 */

if (!function_exists('db_connect')) {

    /**
     * #db_connect();
     *
     * @return mysqli|bool
     */
    function db_connect()
    {
        global $database;

        if (!empty($database)) {

            $db = $database['connection']['mysql'];

            $conn = mysqli_connect($db['host'], $db['user'], $db['pass'], $db['name']);
            mysqli_set_charset($conn, $db['char_set']);

            return $conn;
        }
        throw new Exception("db config not found");
    }
}

if (!function_exists('db_select')) {

    /**
     * #db_select('USER_TB');
     *
     * @param string $fullSelect
     * @param bool $fetchOutArray
     * @return array
     */
    function db_select($fullSelect, $fetchOutArray = true)
    {
        $items = array();
        $conn = db_connect();
        $d = now('d');

        $query_ = mysqli_query($conn, $fullSelect);
        write_log($fullSelect,  __DIR__ . "/../../logs/process/query_select_{$d}.txt");

        while ($rows = mysqli_fetch_assoc($query_)) {
            array_push($items, $rows);
        }

        mysqli_close($conn);

        if ($fetchOutArray) {
            return $items;
        }

        return !empty($items[0]) ? $items[0] : $items;
    }
}

if (!function_exists('db_insert')) {
    /**
     * #db_insert('USER_TB', ['NAME' => 'alex', 'PASS' => '1234']);
     *
     * @param string $tbl
     * @param array $data
     * @param string $primary
     * @return array|bool
     */
    function db_insert($tbl, $data, $primary = '?')
    {
        $conn = db_connect();
        $fields = implode(", ", array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";

        $d = now('d');
        $sql = "INSERT INTO {$tbl}({$fields}) VALUES({$values})";

        $query_ = mysqli_query($conn, $sql);
        write_log($sql,  __DIR__ . "/../../logs/process/query_insert_{$d}.txt");

        mysqli_close($conn);
        if ($query_) {
            if ($primary != '?') {
                return db_select("SELECT * FROM {$tbl} ORDER BY {$primary} DESC LIMIT 1", false);
            }

            return true;
        }
        return false;
    }
}

if (!function_exists('db_update')) {

    /**
     * #db_update('USER_TB', ['NAME' => 'alex', 'PASS' => '1234'], "USR_ID = '1'");
     *
     * @param string $tbl
     * @param array $data
     * @param array|string $condi
     * @return bool
     */
    function db_update($tbl, $data, $condi)
    {
        $fields = '';
        foreach ($data as $fields_ => $values_) {
            $fields .= !empty($fields) ? ', ' : '';
            $fields .= "{$fields_} = '{$values_}'";
        }

        $conn = db_connect();
        $d = now('d');
        $sql = "UPDATE {$tbl} SET {$fields} WHERE {$condi}";
        write_log($sql,  __DIR__ . "/../../logs/process/query_update_{$d}.txt");

        $query_upd = mysqli_query($conn, $sql);

        mysqli_close($conn);
        return ($query_upd) ? true : false;
    }
}

if (!function_exists('db_delete')) {
    /**
     * #db_delete('USER_TB', ['USR_ID' => '1']);
     * #db_delete('USER_TB', "USR_ID = '1'");
     *
     * @param string $tbl
     * @param array|string $where
     * @return bool
     */
    function db_delete($tbl, $where)
    {
        $d = now('d');
        $conn = db_connect();

        if (is_array($where)) {
            $whereClause = '';
            foreach ($where as $column => $value) {
                $whereClause .= "{$column} = '{$value}' AND ";
            }
            $whereClause = rtrim($whereClause, ' AND ');
        } else {
            $whereClause = $where;
        }

        $sql = "DELETE FROM {$tbl} WHERE {$whereClause}";
        write_log($sql,  __DIR__ . "/../../logs/process/query_delete_{$d}.txt");

        $query_ = mysqli_query($conn, $sql);
        mysqli_close($conn);

        return ($query_) ? true : false;
    }
}
