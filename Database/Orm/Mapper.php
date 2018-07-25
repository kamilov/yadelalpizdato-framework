<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Mapper.php
 */

namespace Karma\Database\Orm;

use Karma\Database;
use Karma\Database\Orm;
use Karma\Database\Schema;
use Karma\Database\Builder\Select;
use Karma\Database\Builder\Table;

abstract class Mapper
{
    /**
     * разделитель имени поля и таблицы
     */
    const KEY_SEPARATOR = '__';

    /**
     * соостношение склеивания таблиц: одна запись к одной
     */
    const ONE_TO_ONE = 'one-to-one';

    /**
     * соостношение склеивания таблиц: одна запись ко многим
     */
    const ONE_TO_MANY = 'one-to-many';

    /**
     * соостношение склеивания таблиц: множество записей ко многим
     */
    const MANY_TO_MANY = 'many-to-many';

    /**
     * @var array список имён полей таблицы
     */
    protected $map = array();

    /**
     * @var array опции таблицы
     */
    protected $options = array();

    /**
     * @var array соотношение таблиц
     */
    protected $relations = array();

    /**
     * @var string имя первичного ключа
     */
    private $primary_key;

    /**
     * @var \Karma\Database\Orm\Relation объект соотношения таблиц
     */
    private $relation;

    /**
     * @var \Karma\Database\Schema объкт соединения с базой данных
     */
    private $connection;

    /**
     * @var string имя таблицы
     */
    protected $table_name;

    /**
     * @var null|string префикс таблицы
     */
    protected $table_prefix;

    /**
     * @var \Karma\Database\Builder\Table объект таблицы
     */
    private $table_object;

    /**
     * @var string имя модели данных
     */
    protected $model;

    /**
     * @var \Karma\Database\Orm\Plugin[] список плагинов
     */
    private $plugins = array();

    /**
     * конструктор
     * @throws Exception
     * @param array|null $map
     * @param array $options
     * @param array $relations
     */
    public function __construct(array $map = null, array $options = array(), array $relations = array())
    {
        if($this -> connection === null) {
            $this -> connection = Database::current();
        }
        else if(is_string($this -> connection)) {
            $this -> connection = Database::getConnection($this -> connection);
        }

        if(!$this -> connection instanceof Schema) {
            throw new Exception('connection_not_valid');
        }

        if($this -> table_name === null) {
            throw new Exception('not_specified_table');
        }

        if($this -> table_prefix === null) {
            $this -> table_prefix = $this -> connection -> getPrefix();
        }

        $this -> table_object = Database::table($this -> getTable());
        $this -> table_object -> setAlias($this -> table_name);

        if($this -> model === null) {
            $this -> model = $this -> table_name;
        }

        if($map === null) {
            $map = $this -> map;
        }

        $this -> map($map, $options, $relations);
    }

    /**
     * обрабатывает карту полей таблицы и возвращает старую версию
     * @throws Exception
     * @param array|null $map
     * @param array $options
     * @param array $relations
     * @return array
     */
    public function map(array $map = null, array $options = array(), array $relations = array())
    {
        if($map !== null) {
            $old = $this -> map;
            $this -> map = $map;

            $this -> options     = array_merge($this -> options, $options);
            $this -> relations   = array_merge($this -> relations, $relations);
            $this -> primary_key = null;

            foreach($this -> map as $field) {
                if($this -> hasOption($field, 'pk')) {
                    $this -> primary_key = $field;
                    break;
                }
            }

            if($this -> primary_key === null) {
                throw new Exception('primary_key_no_defined');
            }

            $this -> relation = new Relation($this);

            return $old;
        }

        return $this -> map;
    }

    /**
     * возвращает список опций
     * @return array
     */
    public function getOptions()
    {
        return $this -> options;
    }

    /**
     * проверка наличия опции для указанного поля
     * @param string $field
     * @param string $name
     * @return bool
     */
    public function hasOption($field, $name)
    {
        return isset($this -> options[$field]) and in_array($name, $this -> options[$field]);
    }

    /**
     * возвращает список соотношений
     * @return array
     */
    public function getRelations()
    {
        return $this -> relations;
    }

    /**
     * возвращает первичный ключ
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this -> primary_key;
    }

    /**
     * возвращает имя таблицы с префиксом и без
     * @param bool $prefix
     * @return string
     */
    public function getTable($prefix = true)
    {
        return ($prefix ? $this -> table_prefix : '') . $this -> table_name;
    }

    /**
     * возвращает объект таблицы
     * @return \Karma\Database\Builder\Table
     */
    public function getTableObject()
    {
        return clone $this -> table_object;
    }

    /**
     * возвращает имя модели данных
     * @return string
     */
    public function getModel()
    {
        return $this -> model;
    }

    /**
     * сохраняет объект соединения с базой данных
     * @param \Karma\Database\Schema $connection
     * @return Mapper
     */
    public function setConnection(Schema $connection)
    {
        $this -> connection = $connection;
        return $this;
    }

    /**
     * возвращает объект соединения с базой данных
     * @return \Karma\Database\Schema
     */
    public function getConnection()
    {
        return $this -> connection;
    }

    /**
     * добавляет плагин в общий список
     * @param Plugin $plugin
     * @return Mapper
     */
    public function addPlugin(Plugin $plugin)
    {
        $this -> plugins[$plugin -> getName()] = $plugin;
        $plugin -> setMapper($this);
        return $this;
    }

    /**
     * проверяет наличие плагина
     * @param $name
     * @return bool
     */
    public function hasPlugin($name)
    {
        return isset($this -> plugins[$name]);
    }

    /**
     * удаление плагина из списка
     * @param string $name
     * @return Mapper
     */
    public function deletePlugin($name)
    {
        unset($this -> plugins[$name]);
        return $this;
    }

    /**
     * вызов методов для обработки плагинами
     * @param string $name
     * @param mixed $data
     * @return bool
     */
    public function call($name, &$data)
    {
        $result = false;
        $name   = preg_replace_callback(
            '/(\.[a-z])/i',
            function($matches) {
                return strtoupper(trim($matches[1], '.'));
            },
            $name
        );
        
        foreach($this -> plugins as $plugin) {
            if(is_callable(array($plugin, $name))) {
                $result |= $plugin -> $name($data);
            }
        }

        return $result;
    }

    /**
     * создание объекта модели
     * @return Model
     */
    public function create()
    {
        $model = Orm::getModel($this);
        $this -> call('create', $model);
        return $model;
    }

    /**
     * возвращает колличство записей в таблие
     * @return int|null
     */
    public function getCount(Select $select = null)
    {
        if($select === null) {
            $select = Database::select();
        }

        $select -> setTable($this -> table_name)
                -> setFields(Database::functions('count', array(Database::quoteName($this -> primary_key))) -> setAlias('count') -> compile(true));

        Database::current() -> query($select);
        
        return Database::current()  -> first();
    }

    /**
     * возвращает все данные из таблицы
     * @return Collection
     */
    public function getAll()
    {
        return $this -> selectAll(Database::select());
    }

    /**
     * выбор данных по первичному ключу
     * @param int|mixed $key
     * @return Collection|Model|null
     */
    public function getByPrimaryKey($key)
    {
        if(is_array($key)) {
            return $this -> selectAll(Database::select() -> where($this -> getTableObject() -> getField($this -> primary_key), 'in', Database::quote('( ? )', array($key))));
        }
        else {
            return $this -> getOneByField($this -> primary_key, $key);
        }
    }

    /**
     * выбор одной записи по указаному полю
     * @param string $field
     * @param mixed $value
     * @return Model|null
     */
    public function getOneByField($field, $value)
    {
        return $this -> selectOne(Database::select() -> where($this -> getTableObject() -> getField($field), Database::quoteValue($value)));
    }

    /**
     * выбор всех данных по указаному полю
     * @param string $field
     * @param mixed $value
     * @return Collection
     */
    public function getAllByField($field, $value)
    {
        return $this -> selectAll(Database::select() -> where($this -> getTableObject() -> getField($field), Database::quoteValue($value)));
    }

    /**
     * выбор одной записи
     * @param \Karma\Database\Builder\Select $select
     * @return Model|null
     */
    public function selectOne(Select $select)
    {
        $select -> limit(1);
        $this -> select($select);

        if(($row = $this -> getConnection() -> value()) !== null) {
            return $this -> createItemFromRow($this -> parseRow($row));
        }

        return null;
    }

    /**
     * выбор всех записей
     * @param \Karma\Database\Builder\Select $select
     * @return Collection
     */
    public function selectAll(Select $select)
    {
        $this -> select($select);
        $rows = array();

        $this -> call('before.select.all', $select);

        foreach($this -> getConnection() -> values() as $row) {
            $item = $this -> createItemFromRow($this -> parseRow($row));
            $rows[$item -> get($this -> primary_key)] = $item;
        }

        $collection = new Collection($this, $rows);

        $this -> call('after.select.all', $collection);

        return $collection;
    }

    /**
     * загрузка данных
     * @param $field
     * @param array $data
     * @param array $arguments
     * @return Collection|Model|null
     */
    public function load($field, array $data, array $arguments = array())
    {
        if($data[$field] instanceof Lazy) {
            return $data[$field] -> load($arguments);
        }
        return $this -> relation -> load($field, $data);
    }

    /**
     * сохранение данных
     * @param Model $model
     * @return void
     */
    public function save(Model $model)
    {
        if($model -> state() === Model::STATE_NEW) {
            $this -> insert($model);
        }
        else if($model -> state() === Model::STATE_DIRTY) {
            $this -> update($model);
        }
    }

    /**
     * удаление объекта
     * @param id|\Karma\Database\Orm\Model $item
     * @return void
     */
    public function delete($item)
    {
        if(!$item instanceof Model) {
            $item = $this -> getByPrimaryKey($item);
        }

        if($item instanceof Model) {
            $this -> call('before.delete', $item);

            $this -> getConnection()
                  -> query(
                Database::delete($this -> getTableObject()) -> where($this -> getTableObject() -> getField($this -> primary_key), Database::quoteValue($item -> get($this -> primary_key)))
            );

            $item -> import(array($this -> primary_key => null));
            $item -> state(Model::STATE_NEW);

            $this -> call('after.delete', $item);
        }
    }

    /**
     * добавляет поля маппера в список
     * @param \Karma\Database\Builder\Table $table
     * @return void
     */
    public function addFields(Table $table)
    {
        if($table -> getAlias(false) === null) {
            $table -> setAlias($table -> getName());
        }

        $not_selected = array_merge(
            array_keys($this -> relation -> getOneToOne()),
            array_keys($this -> relation -> getOneToOneBack()),
            array_keys($this -> relation -> getOneToMany()),
            array_keys($this -> relation -> getManyToMany())
        );

        foreach($this -> map() as $field) {
            if($this -> hasOption($field, 'fake')) {
                $not_selected = array_merge($not_selected, array($field));
            }
        }

        $fields = array_diff($this -> map(), $not_selected);

        foreach($fields as $field) {
            $alias = $table -> getAlias(false) . self::KEY_SEPARATOR . $field;
            $field = Database::field($field) -> setAlias($alias);
            $table -> setField($field);
        }
    }

    /**
     * добавляет в запрос условие сортировки данных
     * @param \Karma\Database\Builder\Select $select
     * @return void
     */
    public function addOrder(Select $select)
    {
        foreach($this -> options as $field => $options) {
            if($this -> hasOption($field, 'asc')) {
                $select -> order($this -> getTableObject() -> getField($field), 'asc');
            }
            else if($this -> hasOption($field, 'desc')) {
                $select -> order($this -> getTableObject() -> getField($field), 'desc');
            }
        }
    }

    /**
     * обработка изменённых данных
     * @param array|\Karma\Database\Orm\Model[]|\Karma\Database\Orm\Collection[] $data_changed
     * @param Model $model
     * @return void
     */
    public function replaceRelated(&$data_changed, Model $model)
    {
        if($this -> relation !== null) {
            $data       = $model -> export();
            $one_to_one = $this -> relation -> getOneToOne();

            foreach($one_to_one as $field => $relation) {
                if(isset($data[$field]) and $data[$field] instanceof Model) {
                    if($data[$field] -> state() !== Model::STATE_CLEAN) {
                        $model -> state(Model::STATE_DIRTY);
                        $this -> getMapper($relation['mapper'], (isset($relation['map']) ? $relation['map'] : null), (isset($relation['options']) ? $relation['options'] : array()), (isset($relation['relations']) ? $relation['relations'] : array()))
                              -> save($data[$field]);
                        $data_changed[$field] = $data[$field];
                    }
                }

                if(isset($data_changed[$field]) and $data_changed[$field] instanceof Model) {
                    if($data_changed[$field] -> state() !== Model::STATE_CLEAN) {
                        $this -> getMapper($relation['mapper'], (isset($relation['map']) ? $relation['map'] : null), (isset($relation['options']) ? $relation['options'] : array()), (isset($relation['relations']) ? $relation['relations'] : array()))
                              -> save($data_changed[$field]);
                    }
                    $data_changed[$field] = $data_changed[$field] -> get($relation['foreign_key']);
                }
            }

            if($model -> state() !== Model::STATE_NEW) {
                foreach($this -> relation -> getOneToOneBack() as $field => $relation) {
                    if(isset($data_changed[$field])) {
                        if($data_changed[$field] -> get($relation['foreign_key']) != $data[$relation['local_key']]) {
                            $data_changed[$field] -> set($relation['foreign_key'], $data[$relation['local_key']]);
                            $this -> getMapper($relation['mapper'], (isset($relation['map']) ? $relation['map'] : null), (isset($relation['options']) ? $relation['options'] : array()), (isset($relation['relations']) ? $relation['relations'] : array()))
                                  -> save($data_changed[$field]);
                            $data[$field] = $data_changed[$field];
                            unset($data_changed[$field]);
                        }
                    }
                }
            }

            $one_to_many = $this -> relation -> getOneToMany();

            foreach($one_to_many as $field => $relation) {
                if(isset($data[$field]) and $data[$field] instanceof Collection) {
                    $data[$field] -> save();
                }
            }

            $many_to_many = $this -> relation -> getManyToMany();

            foreach($many_to_many as $field => $relation) {
                if(isset($data[$field]) and $data[$field] instanceof Collection) {
                    $data[$field] -> save();
                }
            }

            $model -> import($data);
        }
    }

    /**
     * обработка данных и создание модели
     * @param array $row
     * @return Model|null
     */
    public function createItemFromRow($row)
    {
        if($this -> call('before.process.row', $row)) {
            return $row;
        }

        if(!isset($row[$this -> primary_key]) or $row[$this -> primary_key] === null) {
            return null;
        }

        $model = $this -> create();
        $model -> merge($row);
        $model -> state(Model::STATE_CLEAN);

        $this -> call('after.process.row', $model);

        return $model;
    }

    /**
     * обработка имён полей
     * @param array $row
     * @return array
     */
    protected final function parseRow($row)
    {
        $result = array();

        foreach($row as $key => $value) {
            list($table, $field) = explode(self::KEY_SEPARATOR, $key, 2);

            if(!isset($field) or $field === null) {
                continue;
            }

            $result[$table][$field] = $value;
        }

        $result = $this -> relation -> retrieve($result);

        return $result[$this -> getTable(false)];
    }

    /**
     * выборка данных
     * @param \Karma\Database\Builder\Select $select
     * @return void
     */
    protected function select(Select $select)
    {
        $this -> call('before.select', $select);

        $table = $this -> getTableObject();

        $select -> setTable($table);

        $this -> addFields($table);
        $this -> addOrder($select);

        $this -> relation -> join($select);

        $this -> getConnection() -> query($select);

        $this -> call('after.select', $this -> getConnection());
    }

    /**
     * добалвяет объект в базу данных
     * @param Model $model
     * @return void
     */
    private function insert(Model $model)
    {
        $data = $model -> getChanged();
        $this -> replaceRelated($data, $model);

        $this -> call('before.insert', $data);

        $insert = Database::insert($this -> getTable());

        $this -> call('before.sql.insert', $insert);

        $this -> getConnection() -> query($insert -> set($data));

        if(($id = $this -> getConnection() -> getId()) === 0) {
            $id = $data[$this -> primary_key];
        }

        $this -> call('after.sql.insert', $model);

        $model -> import($this -> getByPrimaryKey($id) -> export());
        $model -> state(Model::STATE_CLEAN);

        $this -> call('after.insert', $model);
    }

    /**
     * обновление объекта в базе данных
     * @param Model $model
     * @return void
     */
    private function update(Model $model)
    {
        $data = $model -> getChanged();
        $this -> replaceRelated($data, $model);

        $this -> call('before.update', $data);

        if(count($data) > 0) {
            $update = Database::update($this -> getTable(), $data)
                      -> where($this -> getTableObject() -> getField($this -> primary_key), $model -> get($this -> primary_key));;


            $this -> call('before.sql.update', $update);

            $this -> getConnection() -> query($update);

            $this -> call('before.sql.update', $model);
        }

        $model -> import($this -> getByPrimaryKey($model -> get($this -> primary_key)) -> export());
        $model -> state(Model::STATE_CLEAN);

        $this -> call('after.update', $model);
    }

    /**
     * возвращает объект маппера
     * @param string|\Karma\Database\Orm\Mapper $mapper
     * @param array|null $map
     * @param array $options
     * @param array $relations
     * @return \Karma\Database\Orm\Mapper
     */
    private function getMapper($mapper, array $map = null, array $options = array(), array $relations = array())
    {
        if($mapper instanceof Mapper) {
            return $mapper;
        }
        return Orm::getMapper($mapper, $map, $options, $relations);
    }
}