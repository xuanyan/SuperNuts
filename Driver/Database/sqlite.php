<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class sqliteWrapper extends DatabaseAbstract implements DatabaseWrapper
{
    // lazy loading
    private function initialization()
    {
        if (!($this->link instanceof SQLiteDatabase)) {
            $this->link = call_user_func_array(
                array(new ReflectionClass('SQLiteDatabase'), 'newInstance'), $this->config);
            foreach ($this->initialization as $val) {
                $this->link->query($val);
            }
        }

        return $this->link;
    }

    public function query()
    {
        $params = func_get_args();
        $sql = array_shift($params);

        Database::$debug && Database::$sql[] = $sql;

        $this->initialization();

        if (isset($params[0])) {
            if (is_array($params[0])) {
                $params = $params[0];
            }
            foreach ($params as $key => $val) {
                $params[$key] = sqlite_escape_string($val);
            }
            if (preg_match_all('/:(\w+)/i', $sql, $tmp)) {
                $p = array();
                foreach ($tmp[1] as $key => $val) {
                    $p[] = $params[$val];
                }
                $params = $p;
                $sql = str_replace($tmp[0], '?', $sql);
            }
            $sql = str_replace('?', "'%s'", $sql);
            array_unshift($params, $sql);
            $sql = call_user_func_array('sprintf', $params);
        }

        $query = $this->link->query($sql);
        if ($query === false) {
            throw new Exception("Error sql query:$sql");
        }

        return $query;
    }

    public function exec()
    {
        $param = func_get_args();
        call_user_func_array(array($this, 'query'), $param);

        return $this->link->changes();
    }

    public function getOne()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);

        return $query->fetchSingle();
    }

    public function fetch($query, $result_type = Database::ASSOC)
    {
        if ($result_type == Database::ASSOC) {
            return $query->fetch(SQLITE_ASSOC);
        } elseif ($result_type == Database::NUM) {
            return $query->fetch(SQLITE_NUM);
        }

        return $query->fetch(SQLITE_BOTH);
    }

    public function getAll()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);

        return $query->fetchAll(SQLITE_ASSOC);
    }

    public function lastInsertId()
    {
        return $this->initialization()->lastInsertRowid();
    }
}

?>