<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Redirect.php
 */

namespace Karma\Http\Response;

use Karma\Http\Response;

class Redirect extends Response
{
    /**
     * констуктор
     * передаёт код и заголовок для переадресации
     * @param string $url
     * @param int $code
     */
    public function __construct($url, $code = 302)
    {
        parent::__construct('Redirect...', $code, array('location' => $url));
    }
}