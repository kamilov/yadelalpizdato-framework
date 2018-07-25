<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Relation.php
 */

namespace Karma\Database\Orm;

use Karma\Database;
use Karma\Database\Orm;
use Karma\Database\Builder\Select;

class Relation
{
    /**
     * соотношеие склеивания таблиц базы данных
     */
    const ONE_TO_ONE_BACK = 'one-to-one-back';

    /**
     * метод склеивания таблиц по умолчанию
     */
    const DEFAULT_JOIN_TYPE = 'left';

    /**
     * зеркало имени таблицы для соотношения многие ко многим
     */
    const REFERENCE_ALIAS   = 'reference';

    /**
     * @var array список сооотношений
     */
    private $relations = array();

    /**
     * @var \Karma\Database\Orm\Mapper объект маппера
     */
    private $mapper;

    /**
     * конструктор
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this -> mapper = $mapper;
        //$this -> markOneToOneBack(); @todo
    }

    /**
     * ищет и возвращает соотношения одна запись к одной
     * @throws Exception
     * @return array
     */
    public function getOneToOne()
    {
        if(!isset($this -> relations[Mapper::ONE_TO_ONE])) {
            $this -> relations[Mapper::ONE_TO_ONE]    = array();
            $this -> relations[self::ONE_TO_ONE_BACK] = array();

            $map = $this -> mapper -> map();
            $relations = $this -> mapper -> getRelations();

            foreach($map as $field) {
                if(isset($relations[$field]) and isset($relations[$field]['type']) and $relations[$field]['type'] === Mapper::ONE_TO_ONE) {
                    $difference = array_diff(array('foreign_key', 'mapper'), array_keys($relations[$field]));

                    if(count($difference) > 0) {
                        throw new Exception('required_parameters', array(implode(', ', $difference)));
                    }

                    $type     = Mapper::ONE_TO_ONE;
                    $relation = array(
                        'foreign_key' => $relations[$field]['foreign_key'],
                        'mapper'      => $relations[$field]['mapper'],
                        'map'         => isset($relations[$field]['map']) ? $relations[$field]['map'] : null,
                        'options'     => isset($relations[$field]['options']) ? $relations[$field]['options'] : array(),
                        'relations'   => isset($relations[$field]['relations']) ? $relations[$field]['relations'] : array(),
                        'join'        => isset($relations[$field]['join']) ? $relations[$field]['join'] : self::DEFAULT_JOIN_TYPE
                    );

                    if(isset($relations[$field]['local_key']) and $relations[$field]['local_key'] != $field) {
                        $relation['local_key'] = $relations[$field]['local_key'];
                        $type = self::ONE_TO_ONE_BACK;
                    }

                    $this -> relations[$type][$field] = $relation;
                }
            }
        }

        return $this -> relations[Mapper::ONE_TO_ONE];
    }

    /**
     * ищет и возвращает соотношения одна запись к одной, с использованием псевдо поля
     * @return array
     */
    public function getOneToOneBack()
    {
        if(!isset($this -> relations[self::ONE_TO_ONE_BACK])) {
            $this -> getOneToOne();
        }
        return $this -> relations[self::ONE_TO_ONE_BACK];
    }

    /**
     * ищет и возвращает соотношения одна запись ко многим
     * @throws Exception
     * @return array
     */
    public function getOneToMany()
    {
        if(!isset($this -> relations[Mapper::ONE_TO_MANY])) {
            $this -> relations[Mapper::ONE_TO_MANY] = array();

            $map = $this -> mapper -> map();
            $relations = $this -> mapper -> getRelations();

            foreach($map as $field) {
                 if(isset($relations[$field]) and isset($relations[$field]['type']) and $relations[$field]['type'] === Mapper::ONE_TO_MANY) {
                    $difference = array_diff(array('foreign_key', 'local_key', 'mapper'), array_keys($relations[$field]));

                    if(count($difference) > 0) {
                        throw new Exception('required_parameters', array(implode(', ', $difference)));
                    }

                    $this -> relations[Mapper::ONE_TO_MANY][$field] = $relations[$field];
                }
            }
        }

        return $this -> relations[Mapper::ONE_TO_MANY];
    }

    /**
     * ищет и возвращает соотношения множество записей ко многим
     * @throws Exception
     * @return array
     */
    public function getManyToMany()
    {
        if(!isset($this -> relations[Mapper::MANY_TO_MANY])) {
            $this -> relations[Mapper::MANY_TO_MANY] = array();

            $map = $this -> mapper -> map();
            $relations = $this -> mapper -> getRelations();

            foreach($map as $field) {
                if(isset($relations[$field]) and isset($relations[$field]['type']) and $relations[$field]['type'] === Mapper::MANY_TO_MANY) {
                    $difference = array_diff(array('local_key', 'foreign_key', 'reference', 'ref_local_key', 'ref_foreign_key', 'mapper'), array_keys($relations[$field]));

                    if(count($difference) > 0) {
                        throw new Exception('required_parameters', array(implode(', ', $difference)));
                    }

                    $this -> relations[Mapper::MANY_TO_MANY][$field] = $relations[$field];
                }
            }
        }

        return $this -> relations[Mapper::MANY_TO_MANY];
    }

    /**
     * загрузка данных
     * @param string $field
     * @param array $data
     * @return \Karma\Database\Orm\Collection|\Karma\Database\Orm\Model|null
     */
    public function load($field, array $data)
    {
        $relations = $this -> mapper -> getRelations();

        if(isset($relations[$field])) {
            $type = $relations[$field]['type'];

            if($type === Mapper::ONE_TO_ONE) {
                $one_to_one = $this -> getOneToOneBack();

                if(isset($one_to_one[$field])) {
                    return $this -> getMapper($one_to_one[$field]['mapper'], (isset($one_to_one[$field]['map']) ? $one_to_one[$field]['map'] : null), (isset($one_to_one[$field]['options']) ? $one_to_one[$field]['options'] : array()), (isset($one_to_one[$field]['relations']) ? $one_to_one[$field]['relations'] : array()))
                                 -> getOneByField($relations[$field]['foreign_key'], $data[$relations[$field]['local_key']]);
                }

                $one_to_one = $this -> getOneToOne();

                if(isset($one_to_one[$field])) {
                    return $this -> getMapper($one_to_one[$field]['mapper'], (isset($one_to_one[$field]['map']) ? $one_to_one[$field]['map'] : null), (isset($one_to_one[$field]['options']) ? $one_to_one[$field]['options'] : array()), (isset($one_to_one[$field]['relations']) ? $one_to_one[$field]['relations'] : array()))
                                 -> getOneByField($relations[$field]['foreign_key'], $data[$field]);
                }
            }
            else if($type === Mapper::ONE_TO_MANY) {
                $one_to_many = $this -> getOneToMany();
                $value       = isset($data[$one_to_many[$field]['local_key']]) ? $data[$one_to_many[$field]['local_key']] : null;
                $collection  = $this -> getMapper($one_to_many[$field]['mapper'], (isset($one_to_one[$field]['map']) ? $one_to_one[$field]['map'] : null), (isset($one_to_one[$field]['options']) ? $one_to_one[$field]['options'] : array()), (isset($one_to_one[$field]['relations']) ? isset($one_to_one[$field]['relations']) : array()))
                                     -> getAllByField($one_to_many[$field]['foreign_key'], $value);
                $collection -> setParams($one_to_many[$field]['foreign_key'], $value);

                return $collection;
            }
            else if($type === Mapper::MANY_TO_MANY) {
                $many_to_many = $this -> getManyToMany();
                $value        = isset($data[$many_to_many[$field]['local_key']]) ? $data[$many_to_many[$field]['local_key']] : null;

                $mapper       = $this -> getMapper($relations[$field]['mapper'], (isset($one_to_one[$field]['map']) ? $one_to_one[$field]['map'] : null), (isset($one_to_one[$field]['options']) ? $one_to_one[$field]['options'] : array()), (isset($one_to_one[$field]['relations']) ? $one_to_one[$field]['relations'] : array()));
                $collection   = $mapper -> selectAll(
                    Database::select() -> join(
                        Database::table($mapper -> getConnection() -> getPrefix() . $relations[$field]['reference'], self::REFERENCE_ALIAS),
                        'inner'
                    ) -> where(
                        Database::field($relations[$field]['ref_foreign_key'], self::REFERENCE_ALIAS),
                        Database::field($relations[$field]['foreign_key'], $mapper -> getTableObject() -> getAlias(false))
                    ) -> where(
                        Database::field($relations[$field]['ref_local_key'], self::REFERENCE_ALIAS),
                        $value
                    )
                );

                $collection -> setManyToManyParams($relations[$field]['ref_foreign_key'], $value, $relations[$field]['ref_local_key'], $relations[$field]['reference']);

                return $collection;
            }
        }

        return null;
    }

    /**
     * добавляет в запрос информацию о склеивании таблиц
     * @param \Karma\Database\Builder\Select $select
     * @return void
     */
    public function join(Select $select)
    {
        foreach($this -> getOneToOne() + $this -> getOneToOneBack() as $field => $relation) {
            if(!isset($relation['local_key'])) {
                $relation['local_key'] = $field;
            }

            if($this -> isLazy($relation)) {
                continue;
            }

            $mapper = $this -> getMapper($relation['mapper'], (isset($relation['map']) ? $relation['map'] : null), (isset($relation['options']) ? $relation['options'] : array()), (isset($relation['relations']) ? $relation['relations'] : array()));
            $table  = $mapper -> getTableObject() -> setAlias($field);

            $mapper -> addFields($table);

            $select -> join($table, $relation['join'])
                    -> joinOn($this -> mapper -> getTableObject() -> getField($relation['local_key']), $table -> getField($relation['foreign_key']));

            $data = array($select, $field);
            $mapper -> call('join', $data);
        }
    }

    /**
     * удаление данных в промежуточной таблице для соотношения множество записей ко многим
     * @param Model $model
     * @return void
     */
    public function delete(Model $model)
    {
        foreach($this -> getManyToMany() as $relation) {
            $this -> getMapper($relation['mapper'], (isset($relation['map']) ? $relation['map'] : null), (isset($relation['options']) ? $relation['options'] : array()), (isset($relation['relations']) ? $relation['relations'] : array()))
                  -> getConnection()
                  -> query(
                Database::delete($relation['reference']) -> where($relation['ref_local_key'], Database::quoteValue($model -> get($relation['local_key'])))
            );
        }
    }

    /**
     * обработка присоединённыъ данных
     * @param array $data
     * @return array
     */
    public function retrieve(array $data)
    {
        foreach($this -> getOneToOne() + $this -> getOneToOneBack() as $field => $relation) {
            if($this -> isLazy($relation) === false) {
                $item = $this -> getMapper($relation['mapper'], (isset($relation['map']) ? $relation['map'] : null), (isset($relation['options']) ? $relation['options'] : array()), (isset($relation['relations']) ? $relation['relations'] : array()))
                              -> createItemFromRow($data[$field]);
                $data[$this -> mapper -> getTable(false)][$field] = $item;
            }
        }

        return $data;
    }

    /**
     * возвращает флаг определяющий, что поле является объектом Lazy
     * @param array $value
     * @return bool
     */
    private function isLazy(array $value)
    {
        return isset($value['options']) and in_array('lazy', $value['options']);
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

    /**
     * маркировка полей с псевдо полями
     * @return void
     */
    private function markOneToOneBack()
    {
        $map       = $this -> mapper -> map();
        $options   = $this -> mapper -> getOptions();
        $relations = $this -> mapper -> getRelations();

        foreach(array_keys($this -> getOneToOneBack()) as $field) {
            if(!isset($options[$field])) {
                $options[$field] = array();
            }
            array_push($options[$field], self::ONE_TO_ONE_BACK);
        }

        $this -> mapper -> map($map, $options, $relations);
    }
}