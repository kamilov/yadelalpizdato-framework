<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Model.php
 */

namespace Karma\Database\Orm;

use Karma\Database\Orm;

abstract class Model implements \Serializable
{
    /**
     * положеие объекта относительно загруженых данных
     */
    const STATE_DIRTY = 1;

    /**
     * положеие объекта относительно загруженых данных
     */
    const STATE_CLEAN = 2;

    /**
     * положеие объекта относительно загруженых данных
     */
    const STATE_NEW   = 3;

    /**
     * @var \Karma\Database\Orm\Mapper объек маппера
     */
    protected $mapper;

    /**
     * @var array список полей
     */
    protected $map;

    /**
     * @var array список зависимостей
     */
    protected $relations;

    /**
     * @var array|\Karma\Database\Orm\Collection|\Karma\Database\Orm\Model список данных
     */
    protected $data = array();

    /**
     * @var array|\Karma\Database\Orm\Collection|\Karma\Database\Orm\Model список загруженых данных
     */
    protected $data_loaded = array();

    /**
     * @var array|\Karma\Database\Orm\Collection|\Karma\Database\Orm\Model список изменённых данных
     */
    protected $data_changed = array();

    /**
     * @var int текущая позиция объекта
     */
    protected $state = self::STATE_NEW;

    /**
     * конструктор
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this -> mapper    = $mapper;
        $this -> map       = $mapper -> map();
        $this -> relations = $mapper -> getRelations();
    }

    /**
     * сохранение значения поля через объект
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this -> set($name, $value);
    }

    /**
     * доступ к полю через объккт
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this -> get($name);
    }

    /**
     * проверка наличия переменной
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this -> has($name);
    }

    /**
     * охраняет значение поля
     * @throws Exception
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value)
    {
        if($this -> has($name) === false) {
            throw new Exception('field_not_found', array($name));
        }
        
        if($this -> mapper -> hasOption($name, 'ro')) {
            throw new Exception('read_only', array($name));
        }
        else if($this -> mapper -> hasOption($name, 'once') and isset($this -> data[$name]) and $this -> data[$name] !== null) {
            throw new Exception('not_changed_again', array($name));
        }
        else if($this -> state() === self::STATE_NEW and $this -> mapper -> hasOption($name, Relation::ONE_TO_ONE_BACK)) {
            throw new Exception('cannot_object_creation', array($name));
        }

        if($this -> state() !== self::STATE_NEW) {
            $this -> state(self::STATE_DIRTY);
        }

        $this -> data[$name] = $this -> data_changed[$name] = $value;
    }

    /**
     * возвращает значение поля
     * @throws Exception
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function get($name, array $arguments = array())
    {
        if($this -> has($name) === false) {
            throw new Exception('field_not_found', array($name));
        }

        if(!array_key_exists($name, $this -> data)) {
            $this -> data[$name] = null;
        }

        if($this -> isLazy($name)) {
            $result = $this -> mapper -> load($name, $this -> data, $arguments);
            if($this -> mapper -> hasOption($name, 'no_cache')) {
                return $result;
            }
            $this -> data[$name] = $result;
            array_push($this ->data_loaded, $result);
        }

        return $this -> data[$name];
    }

    /**
     * проверка наличия переменной
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return in_array($name, $this -> map);
    }

    /**
     * импорт данных в объект
     * @param array $data
     * @return Model
     */
    public function import(array $data)
    {
        $this -> data = $data;

        return $this;
    }

    /**
     * экспорт данных из объекта
     * @return array|\Karma\Database\Orm\Collection[]|\Karma\Database\Orm\Model[]
     */
    public function export()
    {
        return $this -> data;
    }

    /**
     * сохранение необходимых данных
     * @param array $data
     * @return void
     */
    public function merge(array $data)
    {
        foreach($data as $name => $value) {
            if(in_array($name, $this -> map)) {
                $this -> data[$name] = $value;

                if($value === null) {
                    array_push($this -> data_loaded, $name);
                }
            }
        }
    }

    /**
     * возвращает список изменённых данных
     * @return array|Collection|Model
     */
    public function getChanged()
    {
        return $this -> data_changed;
    }

    /**
     * сохраняет и возвращает позицию объекта
     * @param int|null $state
     * @return int
     */
    public function state($state = null)
    {
        if($state !== null) {
            if($state !== self::STATE_DIRTY) {
                $this -> data_changed = array();
            }
            $result = $this -> state;
            $this -> state = $state;
            return $result;
        }
        return $this -> state;
    }

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function serialize()
    {
        $this -> mapper -> call('serialize', $this);

        $result = array_intersect_key(get_object_vars($this), array_flip($this -> getSerializedVariables()));
        $result['mapper'] = get_class($this -> mapper);

        foreach($this -> data as $name => $value) {
            if($value instanceof Lazy) {
                $result['data'][$name] = $value -> load();
                unset($result['data'][$name]); // @todo
            }
            else if(is_object($value)) {
                $result['data'][$name] = serialize($value);
            }
        }

        foreach($this -> data_changed as $name => $value) {
            if($value instanceof Lazy) {
                $result['data_changed'][$name] = $value -> load();
            }
            else if(is_object($value)) {
                $result['data_changed'][$name] = serialize($value);
            }
        }

        return serialize($result);
    }

    /**
     * формирование объекта из строки
     * @param $string
     * @return void
     */
    public function unserialize($string)
    {
        $array     = unserialize($string);
        $mapper    = Orm::getMapper($array['mapper']);
        $relations = $mapper -> map();

        foreach($array as $name => $value) {
            if($name == 'data' or $name == 'data_changed') {
                foreach($value as $field => $field_value) {
                    if(isset($relations[$field]) and $mapper -> hasOptions($field, 'lazy') === false) {
                        $field_value = unserialize($field_value);
                    }
                    $this -> {$name}[$field] = $field_value;
                }
            }
            else {
                $this -> {$name} = $value;
            }
        }

        $this -> __construct($mapper);

        $mapper -> call('unserialize', $this);
        $mapper -> call('create', $this);
    }

    protected function getSerializedVariables()
    {
        return array('data', 'data_loaded', 'data_changed', 'state');
    }

    /**
     * проверка поля на принадлежность к объектам
     * @param string $field
     * @return bool
     */
    private function isLazy($field)
    {
        if($this -> data[$field] instanceof Lazy) {
            return true;
        }

        if($this -> data[$field] !== null) {
            return false;
        }

        if(is_array($this -> data[$field])) {
            return false;
        }

        if(isset($this -> relations[$field])) {
            return true;
        }
    }
}