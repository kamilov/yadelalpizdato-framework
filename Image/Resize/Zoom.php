<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Zoom.php
 */

namespace Karma\Image\Resize;

use Karma\Image\Resize;

class Zoom extends Center
{
    /**
     * возвращает соотношение сторон
     * @return int
     */
    protected function getRatio()
    {
        return max($this -> image -> getHeight() / $this -> destination_height, $this -> image -> getWidth() / $this -> destination_width);
    }
}