<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  controller.php
 */

namespace Karma\Application;

use Karma\Application;
use Karma\Http\Response;
use Karma\Http\Request;

abstract class Controller
{
    /**
     * имя дейтсвия по умолчанию
     */
    const DEFAULT_ACTION = 'index';

    protected $application;

    /**
     * @var string|null действие
     */
    protected $action;

    /**
     * @var \Karma\Http\Request объект запроса к серверу
     */
    protected $request;

    /**
     * @var \Karma\Http\Response
     */
    protected $response;

    /**
     * конструктор
     * @param \Karma\Application $application
     * @param \Karma\Http\Request $request
     * @param string $params
     */
    public function __construct(Application $application, Request $request, $params)
    {
        $this -> application = $application;
        $this -> request     = $request;
        $this -> setParams($params);
    }

    public function __get($name)
    {
        if(($result = $this -> request -> parameters -> parameters($name)) !== null) {
            return $result;
        }
        else if(($result = $this -> request -> parameters -> get($name)) !== null) {
            return $result;
        }
        else {
            return $this -> request -> parameters -> post($name);
        }
    }

    /**
     * метод выполнения до основного вызова запроса
     * @return bool
     */
    public function before()
    {
        $this -> response = new Response('');
        return true;
    }

    /**
     * метод выполнения после основного вызова запроса
     * @return \Karma\Http\Response
     */
    public function after()
    {
        return true;
    }

    /**
     * возвращает объект зпроса к серверу
     * @return \Karma\Http\Request
     */
    public function getRequest()
    {
        return $this -> request;
    }

    /**
     * возвращает объкт ответа сервера
     * @return \Karma\Http\Response
     */
    public function getResponse()
    {
        return $this -> response;
    }

    /**
     * возвращает действие определённое при
     * @return string|null
     */
    public function getAction()
    {
        return $this -> action;
    }

    /**
     * возвращает правила обработки параметров
     * @return array
     */
    protected function getRules()
    {
        return array();
    }

    /**
     * определение параметров контроллера
     * @param string $params
     * @return void
     */
    protected function setParams($params)
    {
        $rules = $this -> getRules();

        if(strlen($params) === 0) {
            $this -> action = self::DEFAULT_ACTION;
        }
        else if(!empty($rules)) {
            foreach($rules as $action => $rule) {
                if(($result = $this -> request -> findParameters($params, $rule)) !== null) {
                    $this -> request -> parameters -> parameters($result);
                    $this -> action = $action;
                    break;
                }
            }
        }
    }
}