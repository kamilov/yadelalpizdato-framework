<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Corner.php
 */

namespace Karma\Image;

class Corner
{
    /**
     * @var int радиус закруления угла
     */
    protected $radius;

    /**
     * @var int качество закругления угла
     */
    protected $rate;

    /**
     * конструктор
     * @param int $radius
     * @param int $rate
     */
    public function __construct($radius = 5, $rate = 3)
    {
        $this -> radius = (int) $radius;
        $this -> rate   = (int) $rate;
    }

    /**
     * закргугление углов изображения
     * @param resource $resource
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function corner($resource, $width, $height)
    {
        if(!is_resource($resource)) {
            return false;
        }

        imagealphablending($resource, false);
        imagesavealpha($resource, true);

        $radius = $this -> radius * $this -> rate;
        $size   = $radius * 2;
        $corner = imagecreatetruecolor($size, $size);

        imagealphablending($corner, false);

        $image  = imagecolorallocatealpha($corner, 255, 255, 255, 127);

        imagefill($corner, 0, 0, $image);

        $positions = array(
            array(0, 0, 0, 0),
            array($radius, 0, ($width - $this -> radius), 0),
            array($radius, $radius, ($width - $this -> radius), ($height - $this -> radius)),
            array(0, $radius, 0, ($height - $this -> radius))
        );

        foreach($positions as $position) {
            imagecopyresampled($corner, $resource, $position[0], $position[1], $position[2], $position[3], $radius, $radius, $this -> radius, $this -> radius);
        }

        for($i = -$radius; $i <= $radius; $i ++) {
            $y = $i;
            $x = sqrt((($radius * $radius) - ($y * $y)));

            $y += $radius;
            $x += $radius;

            imageline($corner, $x, $y, $size, $y, $image);
            imageline($corner, 0, $y, ($size - $x), $y, $image);
        }

        foreach($positions as $position) {
            imagecopyresampled($resource, $corner, $position[2], $position[3], $position[0], $position[1], $this -> radius, $this -> radius, $radius, $radius);
        }

        return $resource;
    }
}