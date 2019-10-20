<?php
/**
 * Created by PhpStorm.
 * User: gabrielecarbonai
 * Date: 02/10/17
 * Time: 22:07
 */

namespace gomonkey\uploader;

use yii;
use yii\imagine\Image;
use yii\base\Component;
use yii\helpers\FileHelper;

class Uploader extends Component
{
    /* integers */
    public $random = 10;

    /* Strings */
    public $baseFrontendUrl = "";
    public $baseBackendUrl = "";
    private $_baseUrl;

    /* Booleans */
    public $rename = false;
    public $remove = false;

    /* arrays */
    public $folders = [];

    public function __construct($base = "frontend")
    {
        $this->_baseUrl = $base;
    }

    public function init(){
        parent::init();
        $this->_baseUrl = 'frontend';
    }

    public function getBaseUrl(){
        if($this->_baseUrl == 'frontend'){
            $this->_baseUrl = Yii::getAlias('@frontend').'/web/images';
        }else{
            $this->_baseUrl = Yii::getAlias('@backend').'/web/images';
        }
        return $this->_baseUrl;
    }

    /**
     * @param $image
     * @param $folder
     *
     * @return string
     * @throws
     */
    public function upload($image, $folder)
    {
        if (!$image) {
            return false;
        }
        
        $this->folders($folder);

        if (Yii::$app->uploaders->rename) {
            $ext = substr($image->name, strrpos($image->name, '.') + 1);
            $image->name = Yii::$app->security->generateRandomString(Yii::$app->uploaders->random) . ".{$ext}";
        }

        $image->saveAs($imageLocation = $this->baseUrl . "/" . $folder . "/" . $image->name);

        foreach (Yii::$app->uploaders->folders as $f) {
            // Check if there are new folder in array
            $this->isFolderExist($this->baseUrl . "/" . $folder . "/" . $f['name'] . "/");

            $this->doResize($imageLocation, $this->baseUrl . "/" . $folder . "/" . $f['name'] . "/" . $image->name,
                [
                    'quality' => $f["quality"],
                    'width' => $f["width"],
                ]);
        }

        if (Yii::$app->uploaders->remove) {
            unlink($this->baseUrl . "/" . $folder . "/" . $image->name);
        }

        return $image->name;
    }

    /**
     * @param $image
     * @param $folder
     */
    public function delete($image, $folder)
    {
        $this->baseUrl == "frontend" ? Yii::$app->uploaders->baseFrontendUrl : Yii::$app->uploaders->baseBackendUrl;

        if (!empty(Yii::$app->uploaders)) {
            foreach (Yii::$app->uploaders->folders as $f) {
                unlink($this->baseUrl . $folder . "/" . $f["name"] . "/" . $image);
            }
        }
    }

    /**
     * @param $folder
     *
     * Create folders if not exists
     */
    private function folders($folder)
    {
        
        if (!file_exists($this->baseUrl . "/" . $folder))
        {
            $path = $this->baseUrl . "/" . $folder;
            if (FileHelper::createDirectory($path, $mode = 0775, $recursive = true))
            {
                //$file->saveAs(Yii::getAlias('@frontend') . '/web/uploads/img/' . $date . $file);
                //}
                //mkdir($this->baseUrl . "/" . $folder, 0664, true);
                foreach (Yii::$app->uploaders->folders as $f)
                {
                    $path = $this->baseUrl . "/" . $folder . "/". $f['name'];
                    FileHelper::createDirectory($path, $mode = 0775, $recursive = true);
                }
            }
        }
    }

    /**
     * @param $folder
     *
     * In that case array folders is changed
     */
    private function isFolderExist($folder)
    {
        if (!file_exists($folder)) {
            mkdir($folder, 0664, true);
        }
    }

    public function doResize($imageLocation, $imageDestination, Array $options = null)
    {
        list($width, $height) = @getimagesize($imageLocation);

        if (!$width) {
            return false;
        }

        if (isset($options['width']) || isset($options['height'])) {
            if (isset($options['width']) && isset($options['height'])) {
                $newWidth = $options['width'];
                $newHeight = $options['width'];
            } else if (isset($options['width'])) {
                $deviationPercentage = (($width - $options['width']) / (0.01 * $width)) / 100;
                $newWidth = $options['width'];
                $newHeight = $height - ($height * $deviationPercentage);
            } else {
                $deviationPercentage = (($height - $options['height']) / (0.01 * $height)) / 100;
                $newWidth = $width - ($width * $deviationPercentage);
                $newHeight = $options['height'];
            }
        } else {
            // reduce image size up to 20% by default
            $reduceRatio = isset($options['reduceRatio']) ? $options['reduceRatio'] : 20;
            $newWidth = $width * ((100 - $reduceRatio) / 100);
            $newHeight = $height * ((100 - $reduceRatio) / 100);
        }

        return Image::thumbnail($imageLocation, (int)$newWidth, (int)$newHeight)->save($imageDestination,
            [
                'quality' => isset($options['quality']) ? $options['quality'] : 100,
            ]
        );
    }
}
