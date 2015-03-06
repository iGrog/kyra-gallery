<?php

namespace kyra\gallery\models;

use kyra\image\models\BasePathGenerator;
use Yii;
use yii\base\Exception;

class GalleryPathGenerator extends BasePathGenerator
{
    public function GeneratePaths($params)
    {
        if(!isset($params['sizes']) || empty($params['sizes'])) throw new Exception('No `sizes` key in params');
        if(empty($params['GalleryID'])) throw new Exception('GalleryID must be set');
        $galleryPath = '@webroot/upload/gallery/'.intVal($params['GalleryID']).'/';
        $absPath = Yii::getAlias($galleryPath);
        $relPath = '/upload/gallery/'.intVal($params['GalleryID']).'/';

        $ret = [];
        foreach($params['sizes'] as $key=>$size)
        {
            $params['Key'] = $key;
            $fileName = $this->GenFileNameByTemplate($params['nameTemplate'], $params);
            $ret[$key] = [
                'ABS' => $absPath.$fileName,
                'REL' => $relPath.$fileName,
                'ABSFOLDER' => $absPath,
                'RELFOLDER' => $relPath,
            ];
        }

        return $ret;
    }

}