<?php

namespace kyra\gallery\models;

use kyra\image\models\Image;
use Yii;
use yii\db\Exception;
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
        $gi = Image::GetPathGeneratorByUploadParams($uploadParams);
        $images = GalleryImages::find()->with('image')->where(['GalleryID' => $galID])->asArray()->all();
        foreach ($images as $img)
        {
            $imgData = ArrayHelper::merge($img, $uploadParams, $img['image']);
            $paths = $gi->GeneratePaths($imgData);
            foreach ($paths as $file)
            {
                if (is_file($file['ABS'])) @unlink($file['ABS']);
            }
        }

        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE GalleryID=:gid';
        $this->getDb()->createCommand($sql, [':gid' => $galID])->execute();
    }

    public function RemoveImage($galID, $iid, $uploadParams)
    {
        $img = Image::find()->where(['IID' => $iid])->asArray()->one();
        if(empty($img))
            return false;

        $transaction = $this->getDb()->beginTransaction();

        try
        {
            // Проверить на Cover images
            $gHeaders = Gallery::find()->where(['HeaderIID' => $iid])->asArray()->all();
            foreach ($gHeaders as $gH)
            {
                $sql = 'SELECT IID FROM ' . $this->tableName() . '
             WHERE GalleryID=:galID AND IID != :iid
             ORDER BY SortOrder
             LIMIT 1
            ';

                $newIID = $this->getDb()->createCommand($sql, [':galID' => $galID, ':iid' => $iid])->queryScalar();
                if (empty($newIID)) $newIID = null;

                $sql = 'UPDATE ' . Gallery::tableName() . ' SET HeaderIID=:iid WHERE GalleryID=:galID';
                $this->getDb()->createCommand($sql, [':iid' => $newIID, ':galID' => $galID])->execute();
            }

            // Удалить из gallery_image

            $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE GalleryID=:galID AND IID=:iid';
            $this->getDb()->createCommand($sql, [':iid' => $iid, ':galID' => $galID])->execute();

            $pg = Image::GetPathGeneratorByUploadParams($uploadParams);
            $paths = $pg->GeneratePaths(array_merge($uploadParams, $img));
            foreach ($paths as $key => $data)
            {
                // в 'ABS' - абсолютный дисковый путь до конкретного файла
                if (is_file($data['ABS'])) unlink($data['ABS']);
            }

            $transaction->commit();
            return true;
        }
        catch (Exception $ex)
        {
            $transaction->rollBack();

            return false;
        }

    }
}
