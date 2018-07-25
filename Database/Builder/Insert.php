<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Insert.php
 */

namespace Karma\Database\Builder;

use Karma\Database;
use Karma\Database\Builder;

class Insert extends Builder
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

    /**
     * компиляция строки запроса
     * @return string
     */
    public function compile()
    {
        $table = reset($this -> tables);
        
        return 'insert into'
                . "\n" . str_repeat(' ', 4)
                . $table -> compile(false)
                . "\n"
                . 'set'
                . "\n" . str_repeat(' ', 4)
                . implode(
                    ', ',
                    array_map(
                        function($name, $value) {
                            return $name . ' = ' . $value;
                        },
                        array_keys($this -> data),
                        array_values($this -> data)
                    )
                );
    }
}