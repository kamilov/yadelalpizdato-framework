<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Table.php
 */

namespace Karma\Database\Builder;

use Karma\Database;

class Table
{
    /**
     * @var string имя таблицы
     */
    protected $name;

    /**
     * @var null|string зеркало имени таблиы
     */
    protected $alias;

    /**
     * @var \Karma\Database\Builder\Field[] список полей таблицы
     */
    protected $fields = array();

    /**
     * конструктор
     * @param string $name
     * @param string|null $alias
     */
    public function __construct($name, $alias = null)
    {
        $this -> name  = $name;
        $this -> alias = $alias;
    }

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this -> compile(true);
        }
        catch (\Exception $exception) {
            die(Exception::text($exception));
        }
    }

    /**
     * возвращает имя таблицы
     * @return string
     */
    public function getName()
    {
        return $this -> name;
    }

    /**
     * сохраняет зеркало имени таблицы
     * @param string $alias
     * @return Table
     */
    public function setAlias($alias)
    {
        $this -> alias = $alias;
        return $this;
    }

    /**
     * возвращает зеркало
     * @param bool $quote
     * @return null|string
     */
    public function getAlias($quote = true)
    {
        return $quote ? Database::quoteName($this -> alias) : $this -> alias;
    }

    /**
     * сохраняет поля таблицы
     * @throws Exception
     * @param string|array|\Karma\Database\Builder\Field[] $fields
     * @return Table
     */
    public function setFields($fields)
    {
        if(is_string($fields)) {
            $fields = preg_split('/\s*,\s*/', $fields);
            $fields = array_map(function($field) { return explode('.', $field); }, $fields);

            $this -> setFields($fields);
        }
        else if(is_array($fields)) {
            foreach($fields as $field) {
                if($field instanceof Field) {
                    $this -> setField($field);
                }
                else if(is_array($field)) {
                    $field_name   = trim($field[0]);
                    $field_object = new Field($field_name);

                    if(isset($field[1])) {
                        $field_object -> setAlias(trim($field[1]));
                    }

                    $this -> setField($field_object);
                }
                else {
                    throw new Exception('unsupported_type', array(gettype($field)));
                }
            }
        }
        else {
            throw new Exception('unsupported_type', array(gettype($fields)));
        }

        return $this;
    }

    /**
     * возвращает списокполей таблицы
     * @return array
     */
    public function getFields()
    {
        if($this -> alias !== null) {
            foreach($this -> fields as $field) {
                $field -> setPrefix($this -> alias);
            }
        }

        return array_map(
            function(Field $field) {
                return $field -> compile(true);
            },
            $this -> fields
        );
    }

    /**
     * сохраняет объект поля таблицы
     * @param Field $field
     * @return Table
     */
    public function setField(Field $field)
    {
        $this -> fields[$field -> getName()] = $field;
        return $this;
    }

    /**
     * возвращает объект поля
     * @param $name
     * @return Field
     */
    public function getField($name)
    {
        if(isset($this -> fields[$name])) {
            if($this -> alias !== null) {
                $this -> fields[$name] -> setPrefix($this -> alias);
            }
            return $this -> fields[$name];
        }
        else {
            return new Field($name, $this -> alias);
        }
    }

    /**
     * компиляция строки
     * @param bool $alias
     * @return string
     */
    public function compile($alias = false)
    {
        $result = Database::quoteName($this -> name);

        if($alias === true and $this -> alias !== null) {
            $result .= ' as ' . Database::quoteName($this -> alias);
        }

        return $result;
    }
}