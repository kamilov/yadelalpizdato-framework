<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Center.php
 */

namespace Karma\Image\Resize;

use Karma\Image;
use Karma\Image\Resize;

class Center extends Resize
{
    /**
     * возвращает исходную x - координату
     * @return int
     */
    public function getSourceX()
    {
        return $this -> getX() / 2 * $this -> getRatio();
    }

    /**
     * возвращает исходную y - координату
     * @return int
     */
    public function getSourceY()
    {
        return $this -> getY() / 2 * $this -> getRatio();
    }

    /**
     * возвращает указанную ширину
     * @return int
     */
    public function getDestinationWidth()
    {
        return parent::getSourceWidth() / $this -> getRatio() - $this -> getX();
    }

    /**
     * возвращает указанную высоту
     * @return int
     */
    public function getDestinationHeight()
    {
        return parent::getSourceHeight() / $this -> getRatio() - $this -> getY();
    }

    /**
     * возвращает исходную ширину изображения
     * @return int
     */
    public function getSourceWidth()
    {
        return parent::getSourceWidth() - $this -> getRatio() * $this -> getX();
    }

    /**
     * возвращает исходную высоту изображения
     * @return int
     */
    public function getSourceHeight()
    {
        return parent::getSourceHeight() - $this -> getRatio() * $this -> getY();
    }

    /**
     * возвращает соотношение сторон
     * @return int
     */
    protected function getRatio()
    {
        $result = min(parent::getSourceHeight() / parent::getDestinationHeight(), parent::getSourceWidth() / parent::getDestinationWidth());

        return $result < 0 ? 1 : $result;
    }

    /**
     * возвращает промежуточную координату x
     * @return float|int
     */
    protected function getX()
    {
        return ($x = (parent::getSourceWidth() / $this -> getRatio() - parent::getDestinationWidth())) > 0 ? $x : 0;
    }

    /**
     * возвращает промежуточную координату y
     * @return float|int
     */
    protected function getY()
    {
        return ($y = (parent::getSourceHeight() / $this -> getRatio() - parent::getDestinationHeight())) > 0 ? $y : 0;
    }
}