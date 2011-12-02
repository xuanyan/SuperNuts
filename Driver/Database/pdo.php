<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class PDOWrapper extends DatabaseAbstract implements DatabaseWrapper
{
    // lazy loading
    private function initialization()
    {
        if (!($this->link instanceof PDO)) {
            $this->link = call_user_func_array(array(new ReflectionClass('PDO'), 'newInstance'), $this->config);
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

        if (!isset($params[0])) {
            if (!$sth = $this->link->query($sql)) {
                throw new Exception("Error sql query:$sql");
            }
        } else {
            if (is_array($params[0])) {
                $params = $params[0];
            }
            if (!$sth = $this->link->prepare($sql) || !$sth->execute($params)) {
                throw new Exception("Error sql prepare:$sql");
            }
        }

        return $sth;
    }

    public function exec()
    {
        $param = func_get_args();
        $sth = call_user_func_array(array($this, 'query'), $param);

        return $sth->rowCount();
    }

    public function getOne()
    {
        $param = func_get_args();
        $sth = call_user_func_array(array($this, 'query'), $param);

        return $sth->fetchColumn();
    }

    public function getCol()
    {
        $param = func_get_args();
        $sth = call_user_func_array(array($this, 'query'), $param);

        if ($out = $sth->fetchAll(PDO::FETCH_COLUMN, 0)) {
            return $out;
        }

        return array();
    }

    public function getAll()
    {
        $param = func_get_args();
        $sth = call_user_func_array(array($this, 'query'), $param);

        if ($out = $sth->fetchAll(PDO::FETCH_ASSOC)) {
            return $out;
        }

        return array();
    }

    public function fetch($sth, $result_type = Database::ASSOC)
    {
        if ($result_type == Database::ASSOC) {
            return $sth->fetch(PDO::FETCH_ASSOC);
        } elseif ($result_type == Database::NUM) {
            return $sth->fetch(PDO::FETCH_NUM);
        }

        return $sth->fetch(PDO::FETCH_BOTH);
    }

    public function getRow()
    {
        $param = func_get_args();
        $sth = call_user_func_array(array($this, 'query'), $param);

        if ($out = $sth->fetch(PDO::FETCH_ASSOC)) {
            return $out;
        }

        return array();
    }

    public function lastInsertId()
    {
        $this->initialization();

        return $this->link->lastInsertId();
    }
}
?>