<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Database.php
 */

namespace Karma;

use Karma\Database\Exception;

class Database
{
    /**
     * имя соединения по умолчанию
     */
    const DEFAULT_CONNECTION = 'default';

    /**
     * @var array список конфигурации соединений
     */
    protected static $configurations = array();

    /**
     * @var \Karma\Database\Schema[] список соединений с базой данных
     */
    protected static $connections = array();

    /**
     * @var string активное подкючение к базе данных
     */
    protected static $current;

    /**
     * установка конфигурации для всех соединений
     * @static
     * @param array $config
     * @return void
     */
    public static function setConfiguration(array $config)
    {
        foreach($config as $connection_name => $connection_config) {
            if(!is_string($connection_name)) {
                throw new Exception('name_invalid_format');
            }

            if(!is_array($connection_config)) {
                throw new Exception('config_invalid_format');
            }

            $difference = array_diff(array('driver', 'host', 'user', 'password', 'base'), array_keys($connection_config));

            if(count($difference) > 0) {
                throw new Exception('required_fields', array($connection_name, implode(', ', $difference)));
            }

            self::$configurations[$connection_name] = $connection_config;
        }
    }

    /**
     * установка текущего соединения
     * @static
     * @param string $name
     * @return \Karma\Database\Schema;
     */
    public static function setConnection($name)
    {
        self::getConnection($name);
        return self::$current = $name;
    }

    /**
     * возвращает объкт указаного соединения
     * @static
     * @throws Database\Exception
     * @param string $name
     * @return \Karma\Database\Schema
     */
    public static function getConnection($name)
    {
        if(!isset(self::$configurations[$name])) {
            throw new Exception('configuration_not_exists', array($name));
        }

        $config = self::$configurations[$name];

        if(!isset(self::$connections[$config['host']])) {
            self::$connections[$config['host']] = array();
        }

        if(!isset(self::$connections[$config['host']][$config['user']])) {
            $driver_name = 'Karma\Database\Schema\\' . ucfirst($config['driver']) . '\Schema';

            if(!class_exists($driver_name)) {
                throw new Exception('driver_not_found', array($name));
            }

            self::$connections[$config['host']][$config['user']] = new $driver_name($config['host'], $config['user'], $config['password'], $config['base'], (isset($config['prefix']) ? $config['prefix'] : null));

            if(isset($config['charset'])) {
                self::$connections[$config['host']][$config['user']] -> setCharset($config['charset']);
            }
        }

        $connection = self::$connections[$config['host']][$config['user']];

        if($connection -> getBase() != $config['base']) {
            $connection -> setBase($config['base'], (isset($config['prefix']) ? $config['prefix'] : null));

            if(isset($config['charset'])) {
                $connection -> setCharset($config['charset']);
            }
        }

        return $connection;
    }

    /**
     * возвращает текущее соединение с базой данных
     * @static
     * @return \Karma\Database\Schema
     */
    public static function current()
    {
        if(self::$current === null) {
            if(isset(self::$configurations[self::DEFAULT_CONNECTION])) {
                self::setConnection(self::DEFAULT_CONNECTION);
            }
            else {
                throw new Exception('define_default_connection');
            }
        }
        return self::getConnection(self::$current);
    }

    /**
     * обработка строки запроса, обрабатывает и вставляет нужные эллементы
     * @static
     * @throws Database\Exception
     * @param string $string
     * @param array $arguments
     * @return string
     */
    public static function quote($string, array $arguments)
    {
        $result = '';

        if(Kernel::arrayKeysIsInt($arguments)) {
            $position  = 0;
            $offset    = 0;

            while($position < strlen($string)) {
                switch($string[$position]) {
                    case ':' :
                        if(!isset($arguments[$offset])) {
                            $result .= ':';
                            break;
                        }

                        if(isset($string[$position + 1]) and $string[$position + 1] === ':') {
                            $result .= ':';
                            $position ++;
                            break;
                        }

                        if(is_scalar($arguments[$offset]) or $arguments[$offset] === null) {
                            $result .= self::quoteValue($arguments[$offset]);
                        }
                        else if(is_array($arguments[$offset])) {
                            $result .= implode(', ', array_map(function($value){ return Database::quoteValue($value); }, $arguments[$offset]));
                        }
                        else {
                            throw new Exception('unsupported_type', array(gettype($arguments[$offset])));
                        }

                        $offset ++;
                    break;

                    case '!' :
                        if(!isset($arguments[$offset])) {
                            $result .= '!';
                            break;
                        }

                        if(isset($string[$position + 1]) and $string[$position + 1] === '!') {
                            $result .= '!';
                            $position ++;
                            break;
                        }

                        if(is_scalar($arguments[$offset]) or $arguments[$offset] === null) {
                            $result .= self::quoteName($arguments[$offset]);
                        }
                        else if(is_array($arguments[$offset])) {
                            $result .= implode(', ', array_map(function($name){ return Database::quoteName($name); }, $arguments[$offset]));
                        }
                        else {
                            throw new Exception('unsupported_type', array(gettype($arguments[$offset])));
                        }

                        $offset ++;
                    break;

                    default :
                        $result .= $string[$position];
                    break;
                }

                $position ++;
            }
        }
        else {
            $position = 0;
            $values   = array();
            $names    = array();

            while($position < strlen($string)) {
                switch($string[$position]) {
                    case ':' :
                        if(isset($string[$position + 1]) and $string[$position + 1] === ':') {
                            $result .= ':';
                            $position ++;
                            break;
                        }

                        $name = self::getArgumentName($string, $position);

                        if(isset($arguments[$name])) {
                            if(!isset($values[$name])) {
                                if(is_scalar($arguments[$name]) or $arguments[$name] === null) {
                                    $values[$name] = self::quoteValue($arguments[$name]);
                                }
                                else if(is_array($arguments[$name])) {
                                    $values[$name] = implode(', ', array_map(function($value){ return Database::quoteValue($value); }, $arguments[$name]));
                                }
                                else {
                                    throw new Exception('unsupported_type', array(gettype($arguments[$name])));
                                }
                            }
                            $result .= $values[$name];
                        }
                        else {
                            $result .= self::quoteValue(null);
                        }
                    break;

                    case '!' :
                        if(isset($string[$position + 1]) and $string[$position + 1] === '!') {
                            $result .= '!';
                            $position ++;
                            break;
                        }

                        $name = self::getArgumentName($string, $position);

                        if(isset($arguments[$name])) {
                            if(!isset($names[$name])) {
                                if(is_scalar($arguments[$name]) or $arguments[$name] === null) {
                                    $names[$name] = self::quoteName($arguments[$name]);
                                }
                                else if(is_array($arguments[$name])) {
                                    $names[$name] = implode(', ', array_map(function($name) { return Database::quoteName($name); }, $arguments[$name]));
                                }
                                else {
                                    throw new Exception('unsupported_type', array(gettype($arguments[$name])));
                                }
                            }
                            $result .= $names[$name];
                        }
                        else {
                            $result .= self::quoteName(null);
                        }
                    break;

                    default :
                        $result .= $string[$position];
                    break;
                }
                $position ++;
            }
        }

        return $result;
    }

    /**
     * метод обрамления кавычками имени полей и таблицы
     * @static
     * @param string $name
     * @return string
     */
    public static function quoteName($name)
    {
        return self::current() -> quoteName($name);
    }

    /**
     * метод обрамления кавычками значений
     * @static
     * @param string $value
     * @return string
     */
    public static function quoteValue($value)
    {
        return self::current() -> quoteValue($value);
    }

    /**
     * возвращает объект, формирующий запрос выбора данных из субд
     * @static
     * @throws Database\Exception
     * @param string|null $table
     * @param string|array|\Karma\Database\Builder\Field[]|null $fields
     * @return \Karma\Database\Builder\Select
     */
    public static function select($table = null, $fields = null)
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }
        
        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Select';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Select')) {
            throw new Exception('builder_class_not_found', array('select'));
        }

        $select = new $class_name;

        if($table !== null) {
            $select -> setTable($table);
        }

        if($fields !== null) {
            $select -> setFields($fields);
        }

        return $select;
    }

    /**
     * возвращает объект, формирующий запрос обновления данных в субд
     * @static
     * @throws Database\Exception
     * @param string|null $table
     * @param array|null $data
     * @return \Karma\Database\Builder\Update
     */
    public static function update($table = null, array $data = null)
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }

        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Update';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Update')) {
            throw new Exception('builder_class_not_found', array('update'));
        }

        $update = new $class_name;

        if($table !== null) {
            $update -> setTable($table);
        }

        if($data !== null) {
            $update -> set($data);
        }

        return $update;
    }

    /**
     * возвращает объект, формирующий запрос добавления данных в субд
     * @static
     * @throws Database\Exception
     * @param string|null $table
     * @param array|null $data
     * @return \Karma\Database\Builder\Insert
     */
    public static function insert($table = null, array $data = null)
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }

        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Insert';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Insert')) {
            throw new Exception('builder_class_not_found', array('insert'));
        }

        $insert = new $class_name;

        if($table !== null) {
            $insert -> setTable($table);
        }

        if($data !== null) {
            $insert -> set($data);
        }

        return $insert;
    }

    /**
     * возвращает объект, формирующий запрос удаления данных из субд
     * @static
     * @throws Database\Exception
     * @param string|null $table
     * @return \Karma\Database\Builder\Delete
     */
    public static function delete($table = null)
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }
        
        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Delete';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Delete')) {
            throw new Exception('builder_class_not_found', array('delete'));
        }

        $delete = new $class_name;

        if($table !== null) {
            $delete -> setTable($table);
        }

        return $delete;
    }

    /**
     * возвращает объект построения условий запроса к базе данных
     * @static
     * @throws Database\Exception
     * @return \Karma\Database\Builder\Where
     */
    public static function where()
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }

        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Where';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Where')) {
            throw new Exception('builder_class_not_found', array('where'));
        }

        $reflection = new \ReflectionClass($class_name);

        return $reflection -> newInstanceArgs(func_get_args());
    }

    /**
     * возвращает объект таблицы базы данных
     * @static
     * @throws Database\Exception
     * @param string $name
     * @param string|null $alias
     * @return \Karma\Database\Builder\Table
     */
    public static function table($name, $alias = null)
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }

        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Table';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Table')) {
            throw new Exception('builder_class_not_found', array('table'));
        }

        return new $class_name($name, $alias);
    }

    /**
     * возвращает объект поля таблицы базы данных
     * @static
     * @throws Database\Exception
     * @param string $name
     * @param string|null $prefix
     * @return \Karma\Database\Builder\Field
     */
    public static function field($name, $prefix = null)
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }

        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Field';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Field')) {
            throw new Exception('builder_class_not_found', array('field'));
        }

        return new $class_name($name, $prefix);
    }

    /**
     * возвращает объект функций базы данных
     * @static
     * @throws Database\Exception
     * @param string $name
     * @param array $arguments
     * @return \Karma\Database\Builder\Functions
     */
    public static function functions($name, array $arguments)
    {
        if(self::$current === null) {
            throw new Exception('not_set_connection');
        }

        $class_name = 'Karma\Database\Schema\\' . ucfirst(self::$configurations[self::$current]['driver']) . '\Functions';

        if(!class_exists($class_name, true) or !is_subclass_of($class_name, 'Karma\Database\Builder\Functions')) {
            throw new Exception('builder_class_not_found', array('functions'));
        }

        return new $class_name($name, $arguments);
    }

    /**
     * поиск в строке имени аргумента
     * @static
     * @param string $string
     * @param int $position
     * @return string
     */
    protected static function getArgumentName($string, &$position)
    {
        $result = '';

        while($position < strlen($string)) {
            if(isset($string[++ $position]) and ($string[$position] === '_' or ctype_alnum($string[$position]))) {
                $result .= $string[$position];
            }
            else {
                $position --;
                break;
            }
        }

        return $result;
    }
}