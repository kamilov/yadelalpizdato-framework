<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Image.php
 */

namespace Karma;

use Karma\Image\Corner;
use Karma\Image\Exception;
use Karma\Image\Resize;

class Image
{
    /**
     * тип изображений в формате jpg, jpeg
     */
    const TYPE_JPG = 'jpg';

    /**
     * тип изображений в формате gif
     */
    const TYPE_GIF = 'gif';

    /**
     * тип изображений в формате png
     */
    const TYPE_PNG = 'png';

    /**
     * @var \resource объект изображения
     */
    private $image;

    /**
     * @var int ширина исходного изображения
     */
    private $width;

    /**
     * @var int высота исходного изображеия
     */
    private $height;

    /**
     * @var string тип генерируемого изображения
     */
    private $type;

    /**
     * @var int качество jpeg изображений
     */
    private $quality;

    /**
     * конструктор
     * @throws Image\Exception
     * @param string $image
     * @param string $type
     */
    public function __construct($image, $type = self::TYPE_JPG)
    {
        if(!is_file($image)) {
            throw new Exception('file_not_found', array($image));
        }
        else if(!is_readable($image)) {
            throw new Exception('file_not_readable', array($image));
        }

        $info = getimagesize($image);

        $this -> width  = $info[0];
        $this -> height = $info[1];

        switch($info[2]) {
            case 1 :
                $this -> image = imagecreatefromgif($image);
            break;

            case 2 :
                $this -> image = imagecreatefromjpeg($image);
            break;

            case 3 :
                $this -> image = imagecreatefrompng($image);
            break;
        }

        $this -> setType($type);
    }

    /**
     * вывод объекта
     * @return string
     */
    public function __toString()
    {
        ob_start();
        $this -> save(null);
        return ob_get_clean();
    }

    /**
     * сохраняет тип генерируемого изображения
     * @param $type
     * @return void
     */
    public function setType($type)
    {
        $this -> type = $type;
    }

    /**
     * возвращает тип генерируемого изображения
     * @return string
     */
    public function getType()
    {
        if(!in_array($this -> type, array(self::TYPE_GIF, self::TYPE_JPG, self::TYPE_PNG))) {
            return self::TYPE_JPG;
        }
        return $this -> type;
    }

    /**
     * сохраняет качество jpeg зображений
     * @param int $quality
     * @return void
     */
    public function setQuality($quality)
    {
        $this -> quality = (int) $quality;
    }

    /**
     * возвращает качество jpeg зображений
     * @return int
     */
    public function getQuality()
    {
        return $this -> quality === null ? 100 : $this -> quality;
    }

    /**
     * возвращает текущую ширину изображения
     * @return int
     */
    public function getWidth()
    {
        return $this -> width;
    }

    /**
     * возвращает текущую высоту изображения
     * @return int
     */
    public function getHeight()
    {
        return $this -> height;
    }

    /**
     * изменение размера изображения
     * @param Image\Resize $resize
     * @return bool
     */
    public function resize(Resize $resize)
    {
        $image = imagecreatetruecolor($resize -> getDestinationWidth(), $resize -> getDestinationHeight());
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagesavealpha($image, true);

        if(imagecopyresampled($image, $this -> image, $resize -> getDestinationX(), $resize -> getDestinationY(), $resize -> getSourceX(), $resize -> getSourceY(), $resize -> getDestinationWidth(), $resize -> getDestinationHeight(), $resize -> getSourceWidth(), $resize -> getSourceHeight()))
        {
            $this -> width  = $resize -> getDestinationWidth();
            $this -> height = $resize -> getDestinationHeight();

            imagedestroy($this -> image);

            $this -> image = imagecreatetruecolor($this -> width, $this -> height);
            
            imagealphablending($this -> image, false);
            imagefill($this -> image, 0, 0, imagecolorallocatealpha($this -> image, 0, 0, 0, 127));
            imagesavealpha($this -> image, true);
            imagecopy($this -> image, $image, 0, 0, 0, 0, $this -> getWidth(), $this -> getHeight());
            imagedestroy($image);

            $resize -> __construct($this);

            return true;
        }

        return false;
    }

    /**
     * закргугление углов изображения
     * @param Image\Corner $corner
     * @return Image
     */
    public function corner(Corner $corner)
    {
        $this -> image = $corner -> corner($this -> image, $this -> getWidth(), $this -> getHeight());
        return $this;
    }

    /**
     * сохранение или вывод изображения
     * @throws Image\Exception
     * @param string|null $path
     * @return void
     */
    public function save($path = null)
    {
        if($path !== null) {
            $directory = dirname($path);

            if(!is_dir($directory) and !@mkdir($directory, 0777, true)) {
                throw new Exception('directory_create_error', array($directory));
            }

            if(!is_writable($directory)) {
                throw new Exception('directory_not_writable', array($directory));
            }
        }

        switch($this -> getType()) {
            case self::TYPE_GIF :
                imagegif($this -> image, $path);
            break;

            case self::TYPE_JPG :
                imagejpeg($this -> image, $path, $this -> getQuality());
            break;

            case self::TYPE_PNG :
                imagepng($this -> image, $path);
            break;
        }
    }
}