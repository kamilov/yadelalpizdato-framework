<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Update.php
 */

namespace Karma\Database\Builder;

use Karma\Database;

class Update extends Where
{
    /**
     * @var array список полей и значений вставляемых в табицу
     */
    protected $data = array();

    /**
     * сохранение поля и значения
     * @param string|array $name
     * @param mixed|null $value
     * @return Insert
     */
    public function set($name, $value = null)
    {
        if(is_array($name)) {
            foreach($name as $name => $value) {
                $this -> set($name, $value);
            }
        }
        else {
            $this -> data[Database::quoteName((string) $name)] = Database::quoteValue($value);
        }
        return $this;
    }

    public function compile()
    {
        $condition = parent::compile();
        $result    = 'update'
                   . "\n" . str_repeat(' ', 4)
                   . implode(', ', $this -> tables)
                   . "\n"
                   . 'set'
                   . "\n" . str_repeat(' ', 4)
                   . implode(
                        ', ',
                        array_map(
                            function($key, $value) {
                                return $key . ' = ' . $value;
                            },
                            array_keys($this -> data),
                            array_values($this -> data)
                        ));

        if(strlen($condition) > 0) {
            $result .= "\n"
                    .  'where'
                    .  "\n" . str_repeat(' ', 4)
                    .  $condition;
        }

        return $result;
    }
}