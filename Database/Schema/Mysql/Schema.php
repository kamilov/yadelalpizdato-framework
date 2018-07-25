<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Schema.php
 */

namespace Karma\Database\Schema\Mysql;

use Karma\Database\Schema as Base;

class Schema extends Base
{
    /**
     * метод соединеия с базой данных
     * @abstract
     * @param string $host
     * @param string $user
     * @param string $password
     * @return resource
     */
    protected function connect($host, $user, $password)
    {
        return @mysql_connect($host, $user, $password);
    }

    /**
     * метод выбора базы данных
     * @abstract
     * @param string $base
     * @return bool
     */
    protected function select($base)
    {
        return @mysql_select_db($base, $this -> link);
    }

    /**
     * выполняет запрос к базе данных
     * @abstract
     * @return resource|bool
     */
    public function exec()
    {
        return @mysql_query($this -> query, $this -> link);
    }

    /**
     * извлекает ассоциативный массив из результата запроса и возвращает его
     * @abstract
     * @return array
     */
    public function fetchArray()
    {
        if($this -> result === null) {
            return false;
        }
        return mysql_fetch_assoc($this -> result);
    }

    /**
     * извлекает данные обработанного ряда из результата запроса и возвращает его
     * @abstract
     * @return array
     */
    public function fetchNumeric()
    {
        if($this -> result === null) {
            return false;
        }
        return mysql_fetch_row($this -> result);
    }

    /**
     * возвращает объект с данными извлечёнными из результата запроса
     * @abstract
     * @return object
     */
    public function fetchObject()
    {
        if($this -> result === null) {
            return false;
        }
        return mysql_fetch_object($this -> result, 'Karma\ArrayObject');
    }

    /**
     * возвращает колличство строк в результате запроса
     * @abstract
     * @return int
     */
    public function num()
    {
        if($this -> result === null) {
            return 0;
        }
        return mysql_num_rows($this -> result);
    }

    /**
     * возвращает последний вставленный индекс
     * @abstract
     * @return int|bool
     */
    public function getId()
    {
        return mysql_insert_id($this -> link);
    }

    /**
     * возвращает количество затронутых полей
     * @abstract
     * @return int|bool
     */
    public function getAffectedRows()
    {
        return mysql_affected_rows($this -> link);
    }

    /**
     * освобождает память от результата запроса
     * @return bool
     */
    public function free()
    {
        if($this -> result === null) {
            return false;
        }
        $result = mysql_free_result($this -> result);
        parent::free();
        return $result;
    }

    /**
     * перемещает внутренний указатель в результате запроса к начальному ряду
     * @abstract
     * @return bool
     */
    public function seek()
    {
        if($this -> result === null) {
            return false;
        }
        return @mysql_data_seek($this -> result, 0);
    }

    /**
     * устанавливает кодировку соединения
     * @abstract
     * @param $charset
     * @return bool
     */
    public function setCharset($charset)
    {
        return mysql_set_charset($charset, $this -> link);
    }

    /**
     * возвращает список полей таблицы
     * @abstract
     * @param string $table_name
     * @return array
     */
    public function listFields($table_name)
    {
        $this -> query('show columns from !', $table_name);
        return $this -> values('Field');
    }

    /**
     * возвращает код ошибки
     * @abstract
     * @return int
     */
    public function getErrno()
    {
        return mysql_errno($this -> link);
    }

    /**
     * возвращает сообщение об ошибке
     * @abstract
     * @return string
     */
    public function getError()
    {
        return mysql_error($this -> link);
    }

    /**
     * метод обрамления кавычками имени полей и таблицы
     * @abstract
     * @param string $name
     * @return string
     */
    public function quoteName($name)
    {
        return preg_match('/^[a-z_0-9]+$/i', $name) ? '`' . $name . '`' : $name;
    }

    /**
     * метод обрамления кавычками значений
     * @abstract
     * @param string $value
     * @return string
     */
    public function quoteValue($value)
    {
        return is_int($value) ? $value : '\'' . mysql_escape_string($value) . '\'';
    }
}