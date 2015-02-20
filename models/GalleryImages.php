<?php

    namespace kyra\gallery\models;

use kyra\image\models\Image;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "gallery_images".
 *
 * @property string $GIID
 * @property string $GalleryID
 * @property string $IID
 * @property string $SortOrder
 *
 * @property Images $i
 * @property Gallery $gallery
 */
class GalleryImages extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'gallery_images';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['GalleryID', 'IID'], 'required'],
            [['GalleryID', 'IID', 'SortOrder'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'GIID' => 'Giid',
            'GalleryID' => 'Gallery ID',
            'IID' => 'Iid',
            'SortOrder' => 'Sort Order',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Image::className(), ['IID' => 'IID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGallery()
    {
        return $this->hasOne(Gallery::className(), ['GalleryID' => 'GalleryID']);
    }

    public function RemoveAllImages($uploadParams, $galID)
    {
        $images = GalleryImages::find()->with('image')->where(['GalleryID' => $galID])->asArray()->all();
        foreach($images as $img)
        {
            $imgData = ArrayHelper::merge($img, $img['image']);
            $paths = Image::GetImageAllPaths($uploadParams, $imgData);
            foreach($paths as $file)
            {
                if(is_file($file['ABS'])) @unlink($file['ABS']);
            }
        }

        $sql = 'DELETE FROM '.$this->tableName().' WHERE GalleryID=:gid';
        $this->getDb()->createCommand($sql, [':gid' => $galID])->execute();
    }
}
