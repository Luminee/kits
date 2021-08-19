<?php

namespace App\Models;

use Illuminate\Support\Collection;

class Relation
{
    protected $foreignKey;

    protected $localKey;

    protected $type;

    /**
     * @var PartitionModel
     */
    protected $related;

    public function __construct($related, $foreignKey, $localKey, $type)
    {
        app()->singleton($related, function () use ($related) {
            return new $related;
        });
        $this->related = app($related);
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->type = $type;
    }

    public static function hasMany($related, $foreignKey, $localKey)
    {
        return new self($related, $foreignKey, $localKey, 'many');
    }

    public function whereRaw($sql, array $bindings = [], $and = 'and')
    {
        $this->related->whereRaw($sql, $bindings, $and);
        return $this;
    }

    public function addEagerConstraints($models)
    {
        $this->related->whereIn($this->foreignKey, $this->getKeys($models, $this->localKey));
    }

    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->newCollection());
        }
        return $models;
    }

    public function getEager()
    {
        return $this->related->get();
    }

    public function match(array $models, $results, $relation)
    {
        $type = $this->type;
        $dictionary = $this->buildDictionary($results);
        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            if (isset($dictionary[$key])) {
                $value = $this->getRelationValue($dictionary, $key, $type);
                $model->setRelation($relation, $value);
            }
        }
        return $models;
    }

    // Protected Functions

    protected function getKeys(array $models, $key = null)
    {
        return array_unique(array_values(array_map(function ($value) use ($key) {
            return $key ? $value->getAttribute($key) : $value->getKey();
        }, $models)));
    }

    protected function buildDictionary($results)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}][] = $result;
        }
        return $dictionary;
    }

    protected function getRelationValue(array $dictionary, $key, $type)
    {
        $value = $dictionary[$key];
        return $type == 'one' ? reset($value) : $this->newCollection($value);
    }

    protected function newCollection(array $models = [])
    {
        return new Collection($models);
    }

}
