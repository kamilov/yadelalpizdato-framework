<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Alignment.php
 */

namespace Karma\Image\Resize;

use Karma\Image;
use Karma\Image\Resize;

class Alignment extends Resize
{
    const WIDTH = 'width';

    const HEIGHT = 'height';

    protected $alignment;

    public function __construct(Image $image, $alignment = self::WIDTH)
    {
        parent::__construct($image);

        $this -> alignment = $alignment;
    }

    public function getDestinationWidth()
    {
        return $this -> getDestinationSize() -> width;
    }

    public function getDestinationHeight()
    {
        return $this -> getDestinationSize() -> height;
    }

    public function getSourceX()
    {
        return 0;
    }

    public function getSourceY()
    {
        return 0;
    }

    protected function getDestinationSize()
    {
        $destination_width  = parent::getDestinationWidth();
        $destination_height = parent::getDestinationHeight();
        $source_width       = parent::getSourceWidth();
        $source_height      = parent::getSourceHeight();

        $result = (object) array(
            'width' => $destination_width,
            'height' => $destination_height
        );

        if($result -> width > $source_width) {
            $destination_width = $source_width;
        }

        if($result -> height > $source_height) {
            $destination_height = $source_height;
        }

        if($source_width > $source_height and (($result -> height = $this -> getHeightByWidth($destination_width)) > $destination_height)) {
            $result -> width = $this -> getWidthByHeight($destination_height);
        }
        else if($source_width < $source_height and (($result -> width = $this -> getWidthByHeight($destination_height)) > $destination_width)) {
            $result -> height = $this -> getHeightByWidth($destination_width);
        }
        else {
            $result -> width = $result -> height = ($destination_width > $destination_height) ? $destination_width : $destination_height;
        }

        if($this -> alignment === self::WIDTH) {
            $result -> width  = $destination_width;
            $result -> height = $this -> getHeightByWidth($destination_width);
        }
        else if ($this -> alignment === self::HEIGHT){
            $result -> width  = $this -> getWidthByHeight($destination_height);
            $result -> height = $destination_height;
        }

        return $result;
    }

    protected function getHeightByWidth($width)
    {
        return $width * parent::getSourceHeight() / parent::getSourceWidth();
    }

    protected function getWidthByHeight($height)
    {
        return $height * parent::getSourceWidth() / parent::getSourceHeight();
    }
}