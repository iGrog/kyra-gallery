<?php

    namespace kyra\gallery\models;


    use kyra\image\models\Image;
    use Yii;

    /**
     * This is the model class for table "gallery".
     *
     * @property string $GalleryID
     * @property string $GalleryName
     * @property string $GalleryDescription
     * @property string $FacebookAlbumID
     * @property string $SortOrder
     * @property string $HeaderIID
     *
     * @property Images $headerI
     */
    class Gallery extends \yii\db\ActiveRecord
    {
        const EVENT_GALLERY_CREATED = 'Kyra.Gallery.GalleryCreated';
        const EVENT_GALLERY_UPDATED = 'Kyra.Gallery.GalleryUpdated';


        public $image;

        /**
         * @inheritdoc
         */
        public static function tableName()
        {
            return 'gallery';
        }

        /**
         * @inheritdoc
         */
        public function rules()
        {
            return [
                [['GalleryName'], 'required'],
                [['GalleryDescription'], 'string'],
                [['SortOrder', 'HeaderIID'], 'integer'],
                [['GalleryName'], 'string', 'max' => 255],
                [['FacebookAlbumID'], 'string', 'max' => 250],
                [['UrlKey'], 'string', 'max' => 20],
            ];
        }

        /**
         * @inheritdoc
         */
        public function attributeLabels()
        {
            return [
                'GalleryID' => 'Gallery ID',
                'GalleryName' => 'Название галереи',
                'GalleryDescription' => 'Описаник галереи',
                'FacebookAlbumID' => 'ID галереи на Facebook',
                'SortOrder' => 'Порядковый номер',
                'HeaderIID' => 'Картинка обложки',
            ];
        }

        /**
         * @return \yii\db\ActiveQuery
         */
        public function getHeaderImage()
        {
            return $this->hasOne(Image::className(), ['IID' => 'HeaderIID']);
        }

        /**
         * @return \yii\db\ActiveQuery
         */
        public function getImages()
        {
            return $this->hasMany(GalleryImages::className(), ['GalleryID' => 'GalleryID']);
        }


        public function AddGallery()
        {
            return $this->save(false);
        }

        public function UpdateGallery()
        {
            return $this->update(false);
        }
    }
