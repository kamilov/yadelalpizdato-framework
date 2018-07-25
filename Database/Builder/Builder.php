<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Builder.php
 */

namespace Karma\Database;

use Karma\Database\Builder\Exception;
use Karma\Database\Builder\Table;

abstract class Builder
{
    /**
     * @var \Karma\Database\Builder\Table[] список таблиц
     */
    protected $tables = array();

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this -> compile();
        }
        catch (\Exception $exception) {
            die(Exception::text($exception));
        }
    }

    /**
     * сохраняет объект таблицы в общем списке
     * @throws Builder\Exception
     * @param string|Table $table
     * @return Builder
     */
    public function setTable($table)
    {
        if(is_string($table)) {
            $tables = preg_split('/\s*,\s*/', $table);
            $tables = array_map(function($table) { return explode('.', $table); }, $tables);

            foreach($tables as $table) {
                $table_name   = trim($table[0]);
                $table_object = new Table($table_name);

                if(isset($table[1])) {
                    $table_object -> setAlias(trim($table[1]));
                }

                $this -> setTable($table_object);
            }
        }
        else if($table instanceof Table) {
            $this -> tables[$table -> getName()] = $table;
        }
        else {
            throw new Exception('unsupported_type', array(gettype($table)));
        }

        return $this;
    }

    /**
     * возвращает объект таблицы
     * @param string $name
     * @return \Karma\Database\Builder\Table|null
     */
    public function getTable($name)
    {
        if(isset($this -> tables[$name])) {
            return $this -> tables[$name];
        }
        else {
            return null;
        }
    }

    /**
     * компиляция строки запроса
     * @abstract
     * @return string
     */
    abstract public function compile();
}