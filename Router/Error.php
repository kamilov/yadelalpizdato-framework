<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Error.php
 */

namespace Karma\Router;

use Karma\Application;
use Karma\Http\Request;

class Error extends Base
{
    /**
     * конструктор
     * @param \Karma\Application $application
     */
    public function __construct(Application $application)
    {
        if(($directory = $application -> getConfig() -> path('path.router.error')) and is_dir($directory)) {
            $this -> directory = rtrim($directory, '\\/');
        }
        else {
            $this -> directory = 'error';
        }

        parent::__construct($application);
    }
    
    /**
     * обработка
     * поиск в строке зпроса языковой версии если необходимо
     * @param \Karma\Http\Request $request
     * @return bool|\Karma\Http\Request
     */
    protected function check(Request $request)
    {
        return $request;
    }
    
    /**
     * поиск контроллера
     * @abstract
     * @param \Karma\Http\Request $request
     * @return Controller
     */
    protected function find(Request $request)
    {
        return $this -> getObject($request, $request -> getUrl(), '');
    }
}