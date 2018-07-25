<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Select.php
 */

namespace Karma\Database\Builder;

use Karma\Database;

class Select extends Where
{
    /**
     * @var \Karma\Database\Builder\Table объект последней вставленной таблицы
     */
    protected $last_table;

    /**
     * @var \Karma\Database\Builder\Table имя последней вставленной таблицы
     */
    protected $last_join;

    /**
     * @var \Karma\Database\Builder\Table[] список таблиц присоединённых к основному запросу
     */
    protected $join_tables = array();

    /**
     * @var array ограничение выбора данных
     */
    protected $limit = array();

    /**
     * @var array список полей по которым должна идти сортировка
     */
    protected $orders = array();

    /**
     * @var array список полей по которым нужно группировать запрос
     */
    protected $groups = array();

    /**
     * @var \Karma\Database\Builder\Where объект условий выбора даных
     */
    protected $having;

    /**
     * добавляет таблицу(ы) в общий список
     * @param string $table
     * @return Select
     */
    public function setTable($table)
    {
        parent::setTable($table);
        $table = array_reverse($this -> tables);
        $this -> last_table = reset($table);
        return $this;
    }

    /**
     * добавляет список полей в последнюю вставленную таблицу
     * @param string|array|\Karma\Database\Builder\Field[] $fields
     * @return Select
     */
    public function setFields($fields)
    {
        $this -> last_table -> setFields($fields);
        return $this;
    }

    /**
     * озвращет объект последней вствленной таблицы
     * @return Table
     */
    public function getCurrentTable()
    {
        return $this -> last_table;
    }

    /**
     * добавляет в запрос присоединенную таблицу
     * @param string|\Karma\Database\Builder\Table $table
     * @param string $type
     * @param string|array|\Karma\Database\Builder\Field[]|null $fields
     * @param Where|null $on
     * @return Select
     */
    public function join($table, $type = 'left', $fields = null, Where $on = null)
    {
        parent::setTable($table);
        $table = array_reverse($this -> tables);
        $this -> last_join = reset($table);
        $this -> join_tables[$this -> last_join -> getName()] = array(
            'type' => $type,
            'on' => $on
        );

        if($fields !== null) {
            $this -> last_join -> setFields($fields);
        }

        return $this;
    }

    /**
     * сохранение полей для последней "подклееной" таблицы
     * @param string|array|\Karma\Database\Builder\Field[] $fields
     * @return Select
     */
    public function joinFields($fields)
    {
        $this -> last_join -> setFields($fields);
        return $this;
    }

    /**
     * добалвяет условеи для склеивания таблиц
     * @param string|\Karma\Database\Builder\Where $condition
     * @return Select
     */
    public function joinOn($condition)
    {
        if(($on = $this -> join_tables[$this -> last_join -> getName()]['on']) === null) {
            $on = $this -> join_tables[$this -> last_join -> getName()]['on'] = new Where();
        }

        if($condition instanceof Where) {
            $on -> addWhere($condition);
        }
        else {
            call_user_func_array(array($on, 'where'), func_get_args());
        }

        return $this;
    }

    /**
     * сохраняет информацию о ограничении выбора данных
     * @param int $limit
     * @param int|null $offset
     * @return Select
     */
    public function limit($limit, $offset = null)
    {
        $this -> limit = $offset === null ? array($limit) : array($offset, $limit);
        return $this;
    }

    /**
     * сохраняет имена полей и условие сортировки по этим полям
     * @param string $field
     * @param string $type
     * @return Select
     */
    public function order($field, $type = 'asc')
    {
        array_push($this -> orders, array(Database::quoteName($field), $type));
        return $this;
    }

    /**
     * определеят поля по которым будут группироваться данные
     * @param string $field
     * @return Select
     */
    public function group($field)
    {
        array_push($this -> groups, Database::quoteName($field));
        return $this;
    }

    /**
     * сохраняет пост-условие выбора данных
     * @param $condition
     * @return Select
     */
    public function having($condition)
    {
        if($this -> having === null) {
            $this -> having = new Where();
        }

        if($condition instanceof Where) {
            $this -> having -> addWhere($condition);
        }
        else {
            call_user_func_array(array($this -> having, 'where'), func_get_args());
        }

        return $this;
    }

    /**
     * компиляция строки запроса
     * @return string
     */
    public function compile()
    {
        $condition = parent::compile();
        $tables    = array();
        $joints    = array();
        $fields    = array();

        foreach($this -> tables as $table) {
            if($this -> last_join !== null and $table -> getAlias() === null) {
                $table -> setAlias($table -> getName());
            }

            $fields = array_merge($fields, array_values($table -> getFields()));

            if(isset($this -> join_tables[$table -> getName()])) {
                array_push($joints, $table);
            }
            else {
                array_push($tables, $table);
            }
        }

        $result = 'select'
                . "\n" . str_repeat(' ', 4)
                . implode(', ', $fields)
                . "\n"
                . 'from'
                . "\n" . str_repeat(' ', 4)
                . implode(', ', $tables);

        if(count($joints) > 0) {
            foreach($joints as $join) {
                $type = $this -> join_tables[$join -> getName()]['type'];
                $on   = $this -> join_tables[$join -> getName()]['on'];

                $result .= "\n"
                        .  (strlen($type) > 0 ? $type . ' ' : '') . 'join'
                        .  "\n" . str_repeat(' ', 4)
                        .  $join;

                if($on !== null) {
                    $result .= ' on ( ' . $on . ' )';
                }
            }
        }

        if(strlen($condition) > 0) {
            $result .= "\n"
                    .  'where'
                    .  "\n" . str_repeat(' ', 4)
                    .  $condition;
        }

        if(count($this -> groups) > 0) {
            $result .= "\n"
                    .  'group on'
                    .  "\n" . str_repeat(' ', 4)
                    .  implode(', ', $this -> groups);
        }

        if(count($this -> orders) > 0) {
            $result .= "\n"
                    .  'order by'
                    .  "\n" . str_repeat(' ', 4)
                    .  implode(
                        ', ',
                        array_map(
                            function($order) {
                                return $order[0] . ' ' . $order[1];
                            },
                            $this -> orders
                        )
                    );
        }

        if(count($this -> limit) > 0) {
            $result .= "\n"
                    .  'limit'
                    .  "\n" . str_repeat(' ', 4)
                    .  implode(', ', $this -> limit);
        }

        if($this -> having !== null) {
            $result .= "\n"
                    .  'having'
                    .  "\n" . str_repeat(' ', 4)
                    .  $this -> having;
        }

        return $result;
    }
}