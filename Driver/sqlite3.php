<?php

/**
 * alltosun.com 文件说明
 * ============================================================================
 * 版权所有 (C) 2007-2010 北京共创阳光科技有限公司，并保留所有权利。
 * 网站地址: http://www.alltosun.com
 * ----------------------------------------------------------------------------
 * 许可声明：这是一个开源程序，未经许可不得将本软件的整体或任何部分用于商业用途及再发布。
 * ============================================================================
 * $Author: 宣言 (xuany@alltosun.com) $
 * $Date: 2010-02-17 19:22:12 +0800 $
*/

class sqlite3Wrapper extends DBAbstract implements DBWrapper
{
    private function initialization()
    {
        if (!($this->link instanceof SQLite3)) {
            $this->link = call_user_func_array(
                array(new ReflectionClass('SQLite3'), 'newInstance'), $this->config);
        }

        return $this->link;
    }

    public function query()
    {
        $params = func_get_args();
        $sql = array_shift($params);

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

    public function fetch($query, $result_type = DB::ASSOC)
    {
        if ($result_type == DB::ASSOC) {
            return $query->fetchArray(SQLITE3_ASSOC);
        } elseif ($result_type == DB::NUM) {
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