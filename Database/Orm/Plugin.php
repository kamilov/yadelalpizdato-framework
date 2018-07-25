<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Plugin.php
 */

namespace Karma\Database\Orm;

use Karma\Database\Builder\Insert;
use Karma\Database\Builder\Select;
use Karma\Database\Builder\Update;

abstract class Plugin
{
    /**
     * @var \Karma\Database\Orm\Mapper объект маппера
     */
    protected $mapper;

    /**
     * @var array список опций
     */
    protected $options = array();

    /**
     * конструктор
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this -> options = $options;
    }

    /**
     * изменение карты
     * @param array $map
     * @param array $options
     * @param array $relations
     * @return void
     */
    protected  function updateMap(array &$map, array &$options, array &$relations) {}

    /**
     * сохраняет маммер и обновляет его данные
     * @param Mapper $mapper
     * @return void
     */
    public function setMapper(Mapper $mapper)
    {
        $this -> mapper = $mapper;
        $map            = $mapper -> map();
        $options        = $mapper -> getOptions();
        $relations      = $mapper -> getRelations();

        $this -> updateMap($map, $options, $relations);

        $mapper -> map($map, $options, $relations);
    }

    public function beforeSelectAll(Select $select) {}

    public function afterSelectAll(Collection $collection) {}

    public function beforeSelect(Select $select) {}

    public function beforeInsert(array &$data) {}

    public function afterInsert(Model $model) {}

    public function beforeSqlInsert(Insert $insert) {}

    public function afterSqlInsert(Model $model) {}

    public function beforeDelete(Model $model) {}

    public function afterDelete(Model $model) {}

    public function beforeProcessRow($row) {}

    public function afterProcessRow($row) {}

    public function join(array &$data) {}

    public function serialize(Model $model) {}

    public function unserialize(Model $model) {}

    public function create(Model $model) {}

    /**
     * возвращает имя плагина
     * @abstract
     * @return string
     */
    abstract public function getName();
}