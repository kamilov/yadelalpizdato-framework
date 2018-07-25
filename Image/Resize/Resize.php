<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Resize.php
 */

namespace Karma\Image;

use Karma\Image;

abstract class Resize
{
    /**
     * @var int x - координата назначения
     */
    protected $destination_x = 0;

    /**
     * @var int y - координата назначения
     */
    protected $destination_y = 0;

    /**
     * @var int исходная x - координата
     */
    protected $source_x = 0;

    /**
     * @var int исходная y - координата
     */
    protected $source_y = 0;

    /**
     * @var int назначеная ширина изображения
     */
    protected $destination_width = 0;

    /**
     * @var int назначеная высота изображения
     */
    protected $destination_height = 0;

    /**
     * @var int исходная ширина изображения
     */
    protected $source_width = 0;

    /**
     * @var int исходная высота изображения
     */
    protected $source_height = 0;

    /**
     * объект работы с изображениями
     * @var \Karma\Image
     */
    protected $image;

    /**
     * конструктор
     * @param \Karma\Image $image
     */
    public function __construct(Image $image)
    {
        $this -> image         = $image;
        $this -> source_width  = $image -> getWidth();
        $this -> source_height = $image -> getHeight();
    }

    /**
     * сохраняет x - координату назначения
     * @param int $x
     * @return Resize
     */
    public function setDestinationX($x)
    {
        $this -> destination_x = (int) $x;
        return $this;
    }

    /**
     * возвращает x - координату назначения
     * @return int
     */
    public function getDestinationX()
    {
        return $this -> destination_x;
    }

    /**
     * сохраняет y - координату назначения
     * @param int $y
     * @return Resize
     */
    public function setDestinationY($y)
    {
        $this -> destination_y = (int) $y;
        return $this;
    }

    /**
     * возвращает y - координату назначения
     * @return int
     */
    public function getDestinationY()
    {
        return $this -> destination_y;
    }

    /**
     * сохрняет исходную x - координату
     * @param int $x
     * @return Resize
     */
    public function setSourceX($x)
    {
        $this -> source_x = (int) $x;
        return $this;
    }

    /**
     * возвращает исходную x - координату
     * @return int
     */
    public function getSourceX()
    {
        return $this -> source_x;
    }

    /**
     * сохрняет исходную y - координату
     * @param int $y
     * @return Resize
     */
    public function setSourceY($y)
    {
        $this -> source_y = (int) $y;
        return $this;
    }

    /**
     * возвращает исходную y - координату
     * @return int
     */
    public function getSourceY()
    {
        return $this -> source_y;
    }

    /**
     * сохраняет указанную ширину
     * @param int $width
     * @return Resize
     */
    public function setDestinationWidth($width)
    {
        $this -> destination_width = (int) $width;
        return $this;
    }

    /**
     * возвращает указанную ширину
     * @return int
     */
    public function getDestinationWidth()
    {
        return $this -> destination_width;
    }

    /**
     * сохраняет указанную высоту
     * @param int $height
     * @return Resize
     */
    public function setDestinationHeight($height)
    {
        $this -> destination_height = (int) $height;
        return $this;
    }

    /**
     * возвращает указанную высоту
     * @return int
     */
    public function getDestinationHeight()
    {
        return $this -> destination_height;
    }

    /**
     * возвращает исходную ширину изображения
     * @return int
     */
    public function getSourceWidth()
    {
        return $this -> source_width;
    }

    /**
     * возвращает исходную высоту изображения
     * @return int
     */
    public function getSourceHeight()
    {
        return $this -> source_height;
    }

    /**
     * изменение размера изображения
     * @return bool
     */
    public function resize()
    {
        return $this -> image -> resize($this);
    }
}