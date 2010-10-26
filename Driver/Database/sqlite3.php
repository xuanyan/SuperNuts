<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class sqlite3Wrapper extends DatabaseAbstract implements DatabaseWrapper
{
    // lazy loading
    private function initialization()
    {
        if (!($this->link instanceof SQLite3)) {
            $this->link = call_user_func_array(
                array(new ReflectionClass('SQLite3'), 'newInstance'), $this->config);
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
            if (!$stmt = $this->link->prepare($sql)) {
                throw new Exception("Error sql query:$sql");
            }
            if (preg_match_all('/:(\w+)/i', $sql, $tmp)) {
                foreach ($tmp[1] as $key => $val) {
                    $stmt->bindValue(':'.$val, $params[$val], SQLITE3_TEXT);
                }
            } else {
                foreach ($params as $key => $val) {
                    $stmt->bindValue($key+1, $params[$key], SQLITE3_TEXT);
                }
            }
            $sth = $stmt->execute();
        }

        return $sth;
    }

    public function exec()
    {
        $param = func_get_args();
        $stmt = call_user_func_array(array($this, 'query'), $param);

        return $this->link->changes();
    }

    public function fetch($query, $result_type = Database::ASSOC)
    {
        if ($result_type == Database::ASSOC) {
            return $query->fetchArray(SQLITE3_ASSOC);
        } elseif ($result_type == Database::NUM) {
            return $query->fetchArray(SQLITE3_NUM);
        }

        return $query->fetchArray(SQLITE3_BOTH);
    }

    public function lastInsertId()
    {
        return $this->initialization()->lastInsertRowID();
    }
}

?>