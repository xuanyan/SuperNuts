<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@wejello.org>
 * @version $Id: db.php 19 2009-06-24 14:59:53Z xuanyan $
 */

class mysqliWrapper extends DBAbstract implements DBWrapper
{
    // lazy loading
    private function initialization()
    {
        if (!($this->link instanceof mysqli)) {
            $this->link = call_user_func_array(array(new ReflectionClass('mysqli'), 'newInstance'), $this->config);
            $this->link->query('SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary');
            $this->link->query("SET sql_mode=''");
        }

        return $this->link;
    }

    public function query()
    {
        $params = func_get_args();
        $sql = array_shift($params);

        DB::$sql[] = $sql;
        $this->initialization();

        if (!$stmt = $this->link->prepare($sql)) {
            throw new Exception("Error sql query:$sql");
        }
        if (isset($params[0])) {
            if (is_array($params[0])) {
                $params = $params[0];
            }
            $s = str_repeat('s', count($params));
            array_unshift($params, $s);
            call_user_func_array(array($stmt, 'bind_param'), $params);
        }
        $stmt->execute();

        return $stmt;
    }

    public function exec()
    {
        $param = func_get_args();
        $stmt = call_user_func_array(array($this, 'query'), $param);

        return $stmt->affected_rows;
    }

    public function getOne()
    {
        $param = func_get_args();
        if (stripos($param[0], 'limit') === false) {
            $param[0] .= ' LIMIT 1';
        }
        $stmt = call_user_func_array(array($this, 'query'), $param);

        $stmt->bind_result($result);
        $stmt->fetch();

        return $result;
    }

    public function getCol()
    {
        $param = func_get_args();
        $stmt = call_user_func_array(array($this, 'query'), $param);

        $stmt->bind_result($result);
        $out = array();
        while ($stmt->fetch()) {
            $out[] = $result;
        }

        return $out;
    }

    public function getAll()
    {
        $param = func_get_args();
        $stmt = call_user_func_array(array($this, 'query'), $param);

        $result = array();
        while ($rt = $this->fetch($stmt)) {
            $result[] = $rt;
        }

        return $result;
    }

    public function fetch($stmt, $result_type = DB::ASSOC)
    {
        $field = $stmt->result_metadata()->fetch_fields();
        $out = array();
        $fields = array();
        foreach ($field as $val) {
            $fields[] = &$out[$val->name];
        }
        call_user_func_array(array($stmt,'bind_result'), $fields);
        if (!$stmt->fetch()) {
            return array();
        }

        if ($result_type == DB::ASSOC) {
            return $out;
        } elseif ($result_type == DB::NUM) {
            return array_values($out);
        }

        return array_merge($out, array_values($out));
    }

    public function getRow()
    {
        $param = func_get_args();
        if (stripos($param[0], 'limit') === false) {
            $param[0] .= ' LIMIT 1';
        }
        $stmt = call_user_func_array(array($this, 'query'), $param);

        return $this->fetch($stmt);
    }

    public function lastInsertId()
    {
        $this->initialization();

        return $this->link->insert_id;
    }
}

?>