<?php

namespace App\Models;

use DB;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

abstract class PartitionModel
{
    protected $columns;

    protected $_model = null;

    protected $table;

    protected $parKey;

    protected $parRule;

    protected $softDeleted = false;

    protected $paginate = [];

    protected $onWriteConnection = false;

    protected $trashed = 'normal';

    protected $wheres = [];

    protected $whereIds = [];

    protected $whereParKeys = [];

    protected $relations = ['before' => [], 'after' => []];

    protected $others = ['before' => [], 'after' => []];

    protected $orderBy = [];

    public function __construct(array $attributes = [])
    {

    }

    public function select($columns = ['*'])
    {
        $this->columns = ['type' => 'array',
            'select' => is_array($columns) ? $columns : func_get_args()];
        return $this;
    }

    public function selectRaw($raw)
    {
        $this->columns = ['type' => 'raw', 'select' => $raw];
        return $this;
    }

    public function onWriteConnection()
    {
        $this->onWriteConnection = true;
        return $this;
    }

    public function with($relations)
    {
        $this->relations['after'][] = ['type' => 'with', 'relations' => $relations];
        return $this;
    }

    public function withTrashed()
    {
        $this->trashed = 'withTrashed';
        return $this;
    }

    public function onlyTrashed()
    {
        $this->trashed = 'onlyTrashed';
        return $this;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $and
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $and = 'and')
    {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->wheres[] = ['type' => 'where', 'column' => $key,
                    'ope' => '=', 'value' => $value, 'and' => $and];
            }
            return $this;
        }

        if ($column instanceof Closure) {
            $this->wheres[] = ['type' => 'closure', 'column' => $column, 'and' => $and];
            return $this;
        }

        if (func_num_args() == 2) {
            list($value, $operator) = [$operator, '='];
        }
        $this->wheres[] = ['type' => 'where', 'column' => $column,
            'ope' => $operator, 'value' => $value, 'and' => $and];

        return $this;
    }

    public function whereRaw($sql, array $bindings = [], $and = 'and')
    {
        $this->wheres[] = ['type' => 'raw', 'column' => '', 'sql' => $sql,
            'binding' => $bindings, 'and' => $and];
        return $this;
    }

    /**
     * @param $column
     * @param $array
     * @param bool $not
     * @param string $and
     * @return $this
     */
    public function whereIn($column, $array, $not = false, $and = 'and')
    {
        $this->wheres[] = ['type' => 'in', 'column' => $column,
            'ope' => $not, 'value' => $array, 'and' => $and];
        return $this;
    }

    /**
     * @param $column
     * @param $array
     * @param string $and
     * @return $this
     */
    public function whereNotIn($column, $array, $and = 'and')
    {
        return $this->whereIn($column, $array, true, $and);
    }

    /**
     * @param $column
     * @param bool $not
     * @param string $and
     * @return $this
     */
    public function whereNull($column, $not = false, $and = 'and')
    {
        $this->wheres[] = ['type' => 'null', 'column' => $column,
            'ope' => $not, 'and' => $and];
        return $this;
    }

    /**
     * @param $column
     * @param string $and
     * @return $this
     */
    public function whereNotNull($column, $and = 'and')
    {
        return $this->whereNull($column, true, $and);
    }

    public function has($relation, $operator = '>=', $count = 1)
    {
        $this->relations['before'][] = ['type' => 'has', 'object' => $relation,
            'ope' => $operator, 'count' => $count];
        return $this;
    }

    public function limit($value)
    {
        $this->others['before'][] = ['type' => 'limit', 'value' => $value];
        return $this;
    }

    public function offset($value)
    {
        $this->others['before'][] = ['type' => 'offset', 'value' => $value];
        return $this;
    }

    public function take($value)
    {
        return $this->limit($value);
    }

    public function skip($value)
    {
        return $this->offset($value);
    }

    public function groupBy()
    {
        $this->others['after'][] = ['type' => 'groupBy', 'args' => func_get_args()];
        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->orderBy[] = ['type' => 'orderBy', 'column' => $column, 'dir' => $direction];
        return $this;
    }

    public function orderByRaw($raw)
    {
        $this->orderBy[] = ['type' => 'raw', 'raw' => $raw];
        return $this;
    }

    public function toSql()
    {
        $this->fetchModelForUpdate();
        return $this->_model->toSql();
    }

    public function find($id)
    {
        return $this->where('id', '=', $id)->first();
    }

    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);
        return $results->first();
    }

    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->paginate = ['perPage' => $perPage, 'columns' => $columns,
            'pageName' => $pageName, 'page' => $page];
        return $this->get();
    }

    /**
     * @param array $columns
     * @return Collection
     */
    public function get($columns = ['*'])
    {
        if (empty($this->columns)) {
            $this->select($columns);
        }

        if ($this->checkWhere()) {
            $models = $this->fetchModel();
        } else {
            $models = $this->fetchAllModel();
        }
        return $this->fetchRows($models);
    }

    /**
     * @param array $attributes
     * @return BaseModel
     */
    public function create(array $attributes = [])
    {
        $rule = $this->parRule;
        if ($rule['ope'] !== '%') dd($rule);
        $v = $attributes[$this->parKey] % $rule['v'];
        $p = in_array($v, $rule['effect']) ? 'p' . $v : 'ori';
        $id = DB::table($this->getIdMap())->insertGetId(['partition' => $p]);
        $model = $this->setTable($this->newBaseModel(), $p);
        $model->fillable(array_merge(['id'], $model->getFillable()));
        $model->fill(array_merge(['id' => $id], $attributes));
        $model->save();
        return $model;
    }

    /**
     * @param array $values
     * @return bool
     */
    public function insert(array $values)
    {
        $items = $this->handleInsertMap($values);
        $check = true;
        foreach ($items as $p => $item) {
            $model = $this->setTable($this->newBaseModel(), $p);
            $check = $check && ($model->insert($item));
        }
        return $check;
    }

    public function update(array $values)
    {
        $this->fetchModelForUpdate();
        $ids = $this->fetchIdsForUpdate();
        $count = 0;
        foreach ($ids as $p => $_ids) {
            $count += $this->setTable($this->newBaseModel(), $p)
                ->whereIn('id', $_ids)->update($values);
        }
        return $count;
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        $update = array_merge([$column => DB::raw($column . ' + ' . $amount)], $extra);
        return $this->update($update);
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        $update = array_merge([$column => DB::raw($column . ' - ' . $amount)], $extra);
        return $this->update($update);
    }

    public function destroy($id)
    {
        if (!is_numeric($id))
            return null;
        $p = DB::table($this->getIdMap())->find($id)->partition;
        if (!$this->softDeleted)
            DB::table($this->getIdMap())->delete($id);
        return $this->setTable($this->newBaseModel(), $p)->where('id', $id)->delete();
    }

    public function delete()
    {
        $ids = $this->fetchIdsForUpdate();
        if (!$this->softDeleted)
            DB::table($this->getIdMap())->whereIn('id', $ids)->delete();
        $count = 0;
        foreach ($ids as $p => $_ids) {
            $count += $this->setTable($this->newBaseModel(), $p)
                ->whereIn('id', $_ids)->delete();
        }
        return $count;
    }

    // Protected Functions

    /**
     * @param array $attribute = []
     * @return BaseModel
     */
    protected function newBaseModel(array $attribute = [])
    {
        $baseModel = $this->getBaseModel();
        return new $baseModel($attribute);
    }

    /**
     * @return bool
     */
    protected function checkWhere()
    {
        $parKey = $this->parKey;
        foreach ($this->wheres as $key => $where) {
            if ($where['column'] == 'id') {
                $this->fetchWhereIds($where);
                unset($this->wheres[$key]);
            }
            if ($where['column'] == $parKey) {
                $this->fetchWhereParKey($where);
                unset($this->wheres[$key]);
            }
        }
        return !empty($this->whereIds) || !empty($this->whereParKeys);
    }

    /**
     * @param $where
     */
    protected function fetchWhereIds($where)
    {
        if ($where['and'] == 'or') dd($this->wheres);
        switch ($where['type']) {
            case 'in':
                if (!$where['ope']) $this->whereIds[] = $where['value'];
                break;
            case 'where':
                if ($where['ope'] == '=') $this->whereIds[] = [$where['value']];
                break;
        }
    }

    protected function fetchWhereParKey($where)
    {
        if ($where['and'] == 'or') dd($this->wheres);
        switch ($where['type']) {
            case 'in':
                if (!$where['ope']) $this->whereParKeys[] = $where['value'];
                break;
            case 'where':
                if ($where['ope'] == '=') $this->whereParKeys[] = [$where['value']];
                break;
        }
    }

    /**
     * @return array
     */
    protected function fetchModel()
    {
        $ids = [];
        foreach ($this->whereIds as $_ids) {
            $ids = empty($ids) ? $_ids : array_intersect($ids, $_ids);
        }
        $parKeys = [];
        foreach ($this->whereParKeys as $_parKey) {
            $parKeys = empty($parKeys) ? $_parKey : array_intersect($parKeys, $_parKey);
        }
        $partitions = [];
        if (!empty($this->whereIds))
            $partitions['id'] = $this->fetchPartitionByIds($ids);
        if (!empty($this->whereParKeys))
            $partitions['pKey'] = $this->fetchPartitionByParKeys($parKeys);
        return $this->getPartitionsIfBoth($partitions);
    }

    protected function fetchAllModel()
    {
        $partitions = ['ori' => ['model' => $this->newBaseModel()]];
        foreach ($this->parRule['effect'] as $_p) {
            $p = 'p' . $_p;
            $self = $this->setTable($this->newBaseModel(), $p);
            $partitions[$p] = ['model' => $self];
        }
        return $partitions;
    }

    protected function getPartitionsIfBoth($partitions)
    {
        if (count($partitions) == 1)
            return reset($partitions);
        $_partitions = [];
        foreach ($partitions['id'] as $p => $partition) {
            if (isset($partitions['pKey'][$p])) {
                $partition['keys'] = $partitions['pKey'][$p]['keys'];
            }
            $_partitions[$p] = $partition;
        }
        foreach ($partitions['pKey'] as $p => $partition) {
            if (isset($_partitions[$p])) continue;
            if (isset($partitions['id'][$p])) {
                $partition['ids'] = $partitions['id'][$p]['ids'];
            }
            $_partitions[$p] = $partition;
        }
        return $_partitions;
    }

    /**
     * @param $ids
     * @return array
     */
    protected function fetchPartitionByIds($ids)
    {
        $partitions = DB::table($this->getIdMap())->whereIn('id', $ids)
            ->selectRaw('`partition`, group_concat(id) as _ids')->groupBy(['partition'])->get();
        $_partitions = [];
        foreach ($partitions as $partition) {
            $p = $partition->partition;
            $self = $this->setTable($this->newBaseModel(), $p);
            $_partitions[$p] = ['model' => $self, 'ids' => $partition->_ids];
        }
        return $_partitions;
    }

    protected function fetchPartitionByParKeys($parKeys)
    {
        $rule = $this->parRule;
        if ($rule['ope'] !== '%') dd($rule);
        $parts = [];
        foreach ($parKeys as $parKey) {
            $v = $parKey % $rule['v'];
            if (in_array($v, $rule['effect'])) {
                $parts['p' . $v][] = $parKey;
            } else {
                $parts['ori'][] = $parKey;
            }
        }
        $partitions = [];
        foreach ($parts as $p => $partition) {
            $self = $this->setTable($this->newBaseModel(), $p);
            $partitions[$p] = ['model' => $self, 'keys' => $partition];
        }
        return $partitions;
    }

    /**
     * @param $models
     * @param boolean $getModel
     * @return Collection | string
     */
    protected function fetchRows($models, $getModel = false)
    {
        $parKey = $this->parKey;
        if (empty($models)) return collect();
        $unions = [];
        foreach ($models as $item) {
            $model = $item['model'];
            if ($this->onWriteConnection)
                $model = $model->useWritePdo();
            $model = $this->handleTrashed($model, $this->trashed);
            foreach ($this->wheres as $where) {
                $model = $this->handleWheres($model, $where);
            }
            foreach ($this->relations['before'] as $relation) {
                $model = $this->handleBeforeRelations($model, $relation);
            }
            foreach ($this->others['before'] as $other) {
                $model = $this->handleBeforeOthers($model, $other);
            }
            $model = $this->handleColumns($model, $this->columns);
            if (array_key_exists('ids', $item))
                $model = $model->whereIn('id', explode(',', $item['ids']));
            if (array_key_exists('keys', $item))
                $model = $model->whereIn($parKey, $item['keys']);
            $unions[] = $model;
        }
        $collect = array_shift($unions);
        foreach ($unions as $union) {
            $collect = $collect->union($union);
        }
        foreach ($this->relations['after'] as $relation) {
            $collect = $this->handleAfterRelations($collect, $relation);
        }
        foreach ($this->others['after'] as $other) {
            $collect = $this->handleAfterOthers($collect, $other);
        }
        foreach ($this->orderBy as $orderBy) {
            $collect = $this->handleOrderBy($collect, $orderBy);
        }
        if ($getModel)
            return $collect;
        if (empty($this->paginate))
            return $collect->get();
        $p = $this->paginate;
        return $collect->paginate($p['perPage'], $p['columns'], $p['pageName'], $p['page']);
    }

    protected function fetchModelForUpdate()
    {
        if (empty($this->columns)) {
            $this->select(['*']);
        }
        if ($this->checkWhere()) {
            $models = $this->fetchModel();
        } else {
            $models = $this->fetchAllModel();
        }
        $this->_model = $this->fetchRows($models, true);
    }

    protected function fetchIdsForUpdate()
    {
        $_ids = $this->_model->lists('id');
        $map = DB::table($this->getIdMap())->whereIn('id', $_ids)->get();
        $ids = [];
        foreach ($map as $item) {
            $ids[$item->partition][] = $item->id;
        }
        return $ids;
    }

    protected function handleInsertMap(array $values)
    {
        $insert = [];
        $key = $this->parKey;
        foreach ($values as $value) {
            $p = $this->getPartition($value[$key]);
            $insert[] = ['partition' => $p];
        }
        DB::table($this->getIdMap())->insert($insert);
        $id = DB::getPdo()->lastInsertId();
        $items = [];
        foreach ($values as $k => $value) {
            $p = $this->getPartition($value[$key]);
            $items[$p][] = array_merge(['id' => $id++], $value);
        }
        return $items;
    }

    /**
     * @param mixed $model
     * @param $trashed
     * @return mixed
     */
    protected function handleTrashed($model, $trashed)
    {
        if ($trashed == 'normal')
            return $model;
        if ($trashed == 'withTrashed')
            return $model->newQueryWithoutScope(new SoftDeletingScope);
        $column = $model->getQualifiedDeletedAtColumn();
        return $model->newQueryWithoutScope(new SoftDeletingScope)->whereNotNull($column);
    }

    protected function handleColumns($model, $column)
    {
        $func = $column['type'] == 'raw' ? 'selectRaw' : 'select';
        return $model->$func($column['select']);
    }

    protected function handleWheres($model, $where)
    {
        switch ($where['type']) {
            case 'where':
                return $model->where($where['column'], $where['ope'], $where['value']);
            case 'in':
                return $model->whereIn($where['column'], $where['value'],
                    $where['and'], $where['ope']);
            case 'null':
                return $model->whereNull($where['column'], $where['and'], $where['ope']);
            case 'raw':
                return $model->whereRaw($where['sql'], $where['binding'], $where['and']);
        }
        return $model;
    }

    protected function handleBeforeOthers($model, $other)
    {
        switch ($other['type']) {
            case 'limit':
                return $model->limit($other['value']);
        }
        return $model;
    }

    protected function handleAfterOthers($model, $other)
    {
        switch ($other['type']) {
            case 'groupBy':
                return $model->groupBy($other['args']);
        }
        return $model;
    }

    protected function handleOrderBy($model, $orderBy)
    {
        switch ($orderBy['type']) {
            case 'orderBy':
                return $model->orderBy($orderBy['column'], $orderBy['dir']);
            case 'raw':
                return $model->orderByRaw($orderBy['raw']);
        }
        return $model;
    }

    protected function handleBeforeRelations($model, $relation)
    {
        switch ($relation['type']) {
            case 'has':
                return $model->has($relation['object'], $relation['ope'], $relation['count']);
        }
        return $model;
    }

    protected function handleAfterRelations($model, $relation)
    {
        switch ($relation['type']) {
            case 'with':
                return $model->with($relation['relations']);
        }
        return $model;
    }

    // Private Functions

    /**
     * @param $key
     * @return string
     */
    private function getPartition($key)
    {
        $rule = $this->parRule;
        if ($rule['ope'] !== '%') dd($rule);
        $v = $key % $rule['v'];
        return in_array($v, $rule['effect']) ? 'p' . $v : 'ori';
    }

    private function getIdMap()
    {
        return '_' . $this->table . '_id_map';
    }

    private function setTable($model, $partition)
    {
        if ($partition !== 'ori')
            $model->setTable($this->table . '_' . $partition);
        return $model;
    }

    private function getBaseModel()
    {
        return str_replace("\_", "\\", get_class($this));
    }

}
