<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Delete.php
 */

namespace Karma\Database\Builder;

class Delete extends Where
{
    public function compile()
    {
        $table     = reset($this -> tables);
        $condition = parent::compile();

        if(strlen($condition) > 0) {
            return 'delete from '
                    . "\n" . str_repeat(' ', 4)
                    . $table -> compile(false)
                    . "\n"
                    . 'where'
                    . "\n" . str_repeat(' ', 4)
                    . $condition;
        }
        else {
            return 'truncate table ' . reset($this -> tables);
        }
    }
}