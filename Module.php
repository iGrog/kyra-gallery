<?php

namespace kyra\gallery;

use Yii;
use yii\base\Exception;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public $sizes = [];
    public $keepOriginal = true;
    public $imageModuleName = 'kyra.image';
    public $adminLayout = '//admin';
    public $viewLayout = '//main';
    public $uploadPathKey = 'gallery';
    public $accessRoles = ['admin'];

    public function init()
    {
        parent::init();
        $image = Yii::$app->getModule($this->imageModuleName);
        if(empty($image))
            throw new Exception('kyra\image\Module must be set in config file');

        if(!array_key_exists($this->uploadPathKey, $image->uploadParams))
            throw new Exception('uploadPathKey points to wrong path. Set in `image` module `uploadParams` property.');

    }
}
