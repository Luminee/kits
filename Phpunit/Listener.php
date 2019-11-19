<?php

use Carbon\Carbon;

/**
 * @class Phpunit/Listener
 * @version 1.0.1
 * @author LuminEe
 * @date 2019-11-07
 */
class Listener
{
    /**
     * @var array $queryPond
     */
    protected $queryPond = [];

    /**
     * @var array $slowPond
     */
    protected $slowPond = [];

    /**
     * @var array $failPond
     */
    protected $failPond = [];

    /**
     * @var array $ignoreTables
     */
    protected $ignoreTables = [];

    /**
     * @param $query
     * @param $bindings
     * @param $time
     */
    public function analyzeSQL($query, $bindings, $time)
    {
        $sql = $this->prepareToSql($query, $bindings);
        $_key = str_replace('.', '', microtime(true));
        if ($time > 3000)
            $this->slowPond[$_key] = ['query' => $sql, 'time' => $time];
        if (strpos($sql, 'select ') === 0) {
            $this->checkExplain($sql, $_key);
        }
        $this->queryPond[$_key] = ['query' => $sql, 'key' => $_key];
    }

    public function getPonds()
    {
        $ponds = ['query' => $this->queryPond, 'slow' => $this->slowPond, 'fail' => $this->failPond];
        $this->queryPond = $this->slowPond = $this->failPond = [];
        return $ponds;
    }

    protected function checkExplain($sql, $key)
    {
        $query = \DB::getPdo()->query("explain " . $sql);
        foreach ($query as $k => $row) {
            if (in_array($row['table'], $this->ignoreTables)) continue;
            $this->checkType($row['type'], $key);
            $this->checkKey($row['key'], $key);
            $this->checkRows($row['rows'], $key);
        }
    }

    private $type_level = ['ALL' => 5, 'index' => 4];

    protected function checkType($type, $_k)
    {
        if (in_array($type, ['ALL', 'index']))
            $this->failPond[$_k][] = ['section' => 'type', 'value' => $type, 'level' => $this->type_level[$type]];
    }

    protected function checkKey($key, $_k)
    {
        if (is_null($key))
            $this->failPond[$_k][] = ['section' => 'key', 'level' => 4];
    }

    protected function checkRows($rows, $_k)
    {
        if ($rows > 10000)
            $this->failPond[$_k][] = ['section' => 'rows', 'value' => $rows, 'level' => $this->getRowsLevel($rows)];
    }

    private function getRowsLevel($rows)
    {
        if ($rows > 10000000) return 5;
        if ($rows > 1000000) return 4;
        if ($rows > 100000) return 3;
        return 2;
    }

    protected function prepareToSql($query, $binding)
    {
        foreach ($binding as $k => $v) {
            $start = strpos($query, '?');
            $v = $this->parseValue($v);
            $query = substr_replace($query, $v, $start, 1);
        }
        return $query;
    }

    protected function parseValue($value)
    {
        if (is_numeric($value)) return $value;
        if ($value instanceof Carbon) $value = (string)$value;
        return "'$value'";
    }

}
