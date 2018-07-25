<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Collection.php
 */

namespace Karma\Database\Orm;

use Karma\ArrayObject;
use Karma\Database;
use Karma\Database\Orm;

class Collection extends ArrayObject
{
    /**
     * @var array список объектов которые были добавлены в коллекцию
     */
    private $inserted = array();

    /**
     * @var array список объектов которые были удалены из коллекции
     */
    private $deleted  = array();

    /**
     * @var bool флаг определяющий, что объект был модифицирован
     */
    private $modified = false;

    /**
     * @var string имя поля
     */
    private $name;

    /**
     * @var \Karma\Database\Orm\Mapper объкт маппера
     */
    private $mapper;

    /**
     * @var string имя локального первичного ключа
     */
    private $local_key;

    /**
     * @var string имя локального ключа
     */
    private $local_field_name;

    /**
     * @var mixed значение стороннего ключа
     */
    private $foreign_value;

    /**
     * @var string имя стороннего ключа
     */
    private $foreign_field_name;

    /**
     * @var string мя связующей таблицы
     */
    private $reference_name;

    /**
     * конструктор
     * @param Mapper $mapper
     * @param array $data
     */
    public function __construct(Mapper $mapper, array $data)
    {
        parent::__construct($data);
        $this -> mapper = $mapper;
        $this -> local_key = $mapper -> getPrimaryKey();
    }

    /**
     * возвращает флаг определяющий модификацию объекта
     * @return bool
     */
    public function isModified()
    {
        return $this -> modified;
    }

    /**
     * сохранение параметров
     * @param string $name
     * @param mixed $value
     * @return \Karma\Database\Orm\Collection
     */
    public function setParams($name, $value)
    {
        $this -> name = $name;
        $this -> foreign_value = $value;
        return $this;
    }

    /**
     * сохранение параметров для соотношения выбора записей многие ко многим
     * @param string $name
     * @param mixed $value
     * @param string $local_name
     * @param string $reference_name
     * @return \Karma\Database\Orm\Collection
     */
    public function setManyToManyParams($name, $value, $local_name, $reference_name)
    {
        $this -> foreign_field_name = $name;
        $this -> foreign_value      = $value;
        $this -> local_field_name   = $local_name;
        $this -> reference_name     = $reference_name;
        return $this;
    }

    /**
     * удаление записи из коллекции
     * @param int $id
     * @return Collection
     */
    public function delete($id)
    {
        $this -> modified = true;

        if($this -> has($id)) {
            array_push($this -> deleted, $id);
        }

        return $this;
    }

    /**
     * возвращает запись
     * @param int $id
     * @param null $default
     * @return \Karma\Database\Orm\Model|mixed
     */
    public function get($id, $default = null)
    {
        if(!in_array($id, $this -> deleted)) {
            return parent::get($id, $default);
        }
        return $default;
    }

    /**
     * обёртка для предупреждения
     * @throws Exception
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value)
    {
        throw new Exception('instead_set_add');
    }

    /**
     * обавляет данные в коллекцию
     * @param model $model
     * @return Collection
     */
    public function add(Model $model)
    {
        $this -> modified = true;
        array_push($this -> inserted, $model);
        return $this;
    }

    /**
     * возвращает следующий объект коллекции
     * @return \Karma\Database\Orm\Model
     */
    public function next()
    {
        do {
            $result = parent::next();
        }
        while(in_array($this -> key(), $this -> deleted));

        return $result;
    }

    /**
     * сохранение данных
     * @return Collection
     */
    public function save()
    {
        $this -> modified = false;

        foreach($this as $model) {
            $this -> mapper -> save($model);
        }

        foreach($this -> deleted as $id) {
            $this -> deleteItem($id);
            parent::delete($id);
        }

        $this -> deleted = array();

        foreach($this -> inserted as $model) {
            $this -> insertItem($model);
            parent::set($model -> get($this -> local_key), $model);
        }

        $this -> inserted = array();

        return $this;
    }

    /**
     * удаление записи
     * @param int $id
     * @return void
     */
    private function deleteItem($id)
    {
        if($this -> reference_name !== null) {
            $this -> mapper
                  -> getConnection()
                  -> query(
                Database::delete($this -> reference_name) -> where($this -> local_field_name, $id)
                                                          -> where($this -> foreign_field_name, $this -> foreign_value)
            );
        }
        $this -> mapper -> delete($this -> get($id));
    }

    /**
     * сохранение записи
     * @param Model $model
     * @return void
     */
    private function insertItem(Model $model)
    {
        if($this -> reference_name !== null) {
            $this -> mapper
                  -> getConnection()
                  -> query(
                Database::insert($this -> reference_name) -> set($this -> foreign_field_name, $this -> foreign_value)
                                                          -> set($this -> local_field_name, $model -> get($this -> mapper -> getPrimaryKey()))
            );
        }
        $this -> mapper -> save($model);
    }

    public function serialize()
    {
        $result = array_intersect_key(get_object_vars($this), array_flip($this -> getSerializedVariables()));

        $result['mapper'] = get_class($this -> mapper);

        return serialize($result);
    }

    public function unserialize($string)
    {
        $array = unserialize($string);

        $array['mapper'] = Orm::getMapper($array['mapper']);

        foreach($array as $name => $value) {
            $this -> {$name} = $value;
        }
    }

    protected function getSerializedVariables()
    {
        return array('inserted', 'deleted', 'modified', 'name', 'local_key', 'local_field_name', 'foreign_value', 'foreign_field_name', 'reference_name', 'data');
    }
}