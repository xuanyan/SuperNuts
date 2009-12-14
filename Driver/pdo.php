<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@wejello.org>
 * @version $Id: db.php 19 2009-06-24 14:59:53Z xuanyan $
 */

class PDOWrapper extends DBAbstract implements DBWrapper
{
    // lazy loading
    private function initialization()
    {
        if (!($this->link instanceof PDO)) {
            $this->link = call_user_func_array(array(new ReflectionClass('PDO'), 'newInstance'), $this->config);
            $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->link;
    }

    public function query()
    {
        $params = func_get_args();
        $sql = array_shift($params);

        if ($sql instanceOf DBQuery) {
            if ($param = $sql->getParam()) {
                return $this->query($sql->__toString(), $param);
            } else {
                return $this->query($sql->__toString());
            }
        }

        DB::$sql[] = $sql;
        $this->initialization();

        if (!isset($params[0])) {
            if (!$sth = $this->link->query($sql)) {
                throw new Exception("Error sql query:$sql");
            }
        } else {
            if (is_array($params[0])) {
                $params = $params[0];
            }
            $sth = $this->link->prepare($sql);
            if (!$sth->execute($params)) {
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

    public function fetch($sth, $result_type = DB::ASSOC)
    {
        if ($result_type == DB::ASSOC) {
            return $sth->fetch(PDO::FETCH_ASSOC);
        } elseif ($result_type == DB::NUM) {
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