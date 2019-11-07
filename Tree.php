<?php

/**
 * @class Tree
 * @version 1.0.0
 * @author LuminEe
 * @date 2019-11-07
 */
class Tree
{
    /**
     * Base Tree
     * @var array
     */
    protected $tree = [];
    /**
     * Parent Filed
     * @var string
     */
    protected $parent = 'parent';
    /**
     * Children Field
     * @var string
     */
    protected $children = 'children';
    /**
     * Parent_id Field
     * @var string
     */
    protected $parent_id = 'parent_id';
    /**
     * Tree constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        foreach (['parent', 'children', 'parent_id'] as $var) {
            if (isset($options[$var])) $this->$var = $options[$var];
        }
    }
    protected function newTree()
    {
        $this->tree = [];
    }
    /**
     * @param $array
     * @return $this
     */
    public function buildTree($array)
    {
        $this->newTree();
        if (is_object($array)) $array = $array->toArray();
        foreach ($array as $item) {
            $this->tree[$item['id']] = $item;
            $this->tree[$item['id']][$this->children] = array();
        }
        foreach ($this->tree as $k => $item) {
            $this->tree[$item[$this->parent_id]][$this->children][] = &$this->tree[$k];
        }
        return $this;
    }
    /**
     * @return array
     */
    public function getTree()
    {
        return $this->tree[0][$this->children];
    }
    /**
     * @param $value
     * @param string $key
     * @return array
     */
    public function getChildren($value, $key = 'name')
    {
        foreach ($this->tree as $k => $item) {
            if ($item[$key] == $value) return $item;
        }
        return [];
    }
    /**
     * @param $value
     * @param string $key
     * @return array
     */
    public function getParents($value, $key = 'name')
    {
        foreach ($this->tree as $k => $item) {
            if ($item[$key] == $value) {
                $item[$this->parent] = $this->findParent($item[$this->parent_id]);
                unset($item[$this->children]);
                return $item;
            }
        }
        return [];
    }
    protected function findParent($id)
    {
        $item = $this->tree[$id];
        if (!isset($item[$this->parent_id])) return [];
        $item[$this->parent] = $this->findParent($item[$this->parent_id]);
        unset($item[$this->children]);
        return $item;
    }
}
