<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Where.php
 */

namespace Karma\Database\Builder;

use Karma\Database;
use Karma\Database\Builder;

class Where extends Builder
{
    /**
     * @var array|\Karma\Database\Builder\Where[] список условий выбора данных
     */
    private $where = array();

    /**
     * конструктор
     */
    public function __construct()
    {
        if(func_num_args() > 1) {
            call_user_func_array(array($this, 'where'), func_get_args());
        }
    }

    /**
     * добавляет условие запроса к базе данных, в общий список
     * @param string $first
     * @param string $second
     * @return Where
     */
    public function where($first, $second)
    {
        if(func_num_args() === 2) {
            $condition = '=';
            $type      = 'and';
        }
        else {
            $condition = $second;
            $second    = func_get_arg(2);
            $type      = func_num_args() === 4 ? func_get_arg(3) : 'and';
        }

        array_push(
            $this -> where,
            array(
                $type => array(
                    $first,
                    $condition,
                    $second
                )
            )
        );

        return $this;
    }

    /**
     * добавляет объект условий запроса к базе данных в общий список
     * @param Where $where
     * @return Where
     */
    public function addWhere(Where $where)
    {
        $type = func_num_args() === 2 ? func_get_arg(1) : 'and';
        array_push(
            $this -> where,
            array(
                $type => $where
            )
        );

        return $this;
    }

    /**
     * компиляция условий в строку
     * @return string
     */
    public function compile()
    {
        $result = '';

        foreach($this -> where as $item) {
            $type = key($item);
            $list = $item[$type];

            if($list instanceof Where) {
                $result = strlen($result) > 0 ? $result . ' ' . $type . ' ( ' . $list . ' )' : $list;
            }
            else {
                $list   = implode(' ', $list);
                $result = strlen($result) > 0 ? $result . ' ' . $type . ' ' . $list : $list;
            }
        }

        return $result;
    }
}