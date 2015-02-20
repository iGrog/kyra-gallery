<?php

    namespace kyra\gallery\models;


    use kyra\image\models\Image;
    use Yii;
    use yii\db\Exception;
    use yii\helpers\ArrayHelper;

    /**
     * This is the model class for table "gallery".
     *
     * @property string $GalleryID
     * @property string $ParentID
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

        public static function GetParents($gid, $withHeaderImage=false)
        {
            if($gid == null) return [];

            $ret = [];
            $finder = Gallery::find();
            if($withHeaderImage) $finder->with(['headerImage']);
            $gallery = $finder->where(['GalleryID' => $gid])->asArray()->one();
            $ret[] = $gallery;
            if(!empty($gallery['ParentID']))
                $ret = array_merge($ret, Gallery::GetParents($gallery['ParentID'], $withHeaderImage));

            return $ret;
        }


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
                [['GalleryName', 'UrlKey'], 'required'],
                [['GalleryDescription'], 'string'],
                [['SortOrder', 'HeaderIID', 'ParentID'], 'integer'],
                [['GalleryName'], 'string', 'max' => 255],
                [['FacebookAlbumID'], 'string', 'max' => 250],
                [['UrlKey'], 'string', 'max' => 30],
            ];
        }

        /**
         * @inheritdoc
         */
        public function attributeLabels()
        {
            return [
                'GalleryID' => 'Gallery ID',
                'ParentID' => 'ParentID',
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
            $sql = 'SELECT MAX(SortOrder) FROM '.$this->tableName().' WHERE ParentID=:parent';
            $max = $this->getDb()->createCommand($sql, [':parent' => $this->ParentID])->queryScalar();
            if(empty($max)) $max = 0;
            $this->SortOrder = $max+1;
            return $this->save(false);
        }

        public function UpdateGallery()
        {
            return $this->update(false);
        }

        public function UpdateSortOrder($items)
        {
            $transaction = $this->db->beginTransaction();
            try
            {
                $i = 1;
                foreach ($items as $item)
                {
                    $sql = 'UPDATE '.$this->tableName().' SET ParentID=:parent, SortOrder=:order WHERE GalleryID=:id LIMIT 1';
                    $this->db->createCommand($sql,
                        [':parent' => $item['parent'],
                            ':order' => $i,
                            ':id' => $item['id']])->execute();
                    $i++;
                }

                $transaction->commit();
                return true;
            }
            catch (Exception $ex)
            {
                $transaction->rollback();
                return false;
            }

        }

        public function GetGalleryChildren($gid)
        {
            $galleries = Gallery::find()->where(['ParentID' => $gid])->asArray()->all();
            $ret = [];
            foreach($galleries as $gal)
            {
                $g2 = $this->GetGalleryChildren($gal['GalleryID']);
                $ret[] = $gal;
                if(!empty($g2))
                    $ret = array_merge($ret, $g2);

            }

            return $ret;
        }

        public function RemoveFullGallery($uploadParams, $gid)
        {
            $children = $this->GetGalleryChildren($gid);
            $transaction = $this->getDb()->beginTransaction();
            try
            {
                $galIDs = ArrayHelper::getColumn($children, 'GalleryID');
                array_push($galIDs, $gid);
                $gi = new GalleryImages;

                foreach($galIDs as $galID)
                {
                    $gi->RemoveAllImages($uploadParams, $galID);
                }

                $sql = 'DELETE FROM '.$this->tableName().' WHERE GalleryID IN ('.implode(',', $galIDs).')';
                $this->getDb()->createCommand($sql)->execute();

                $transaction->commit();
                return true;
            }
            catch(Exception $ex)
            {
                $transaction->rollBack();
                return false;
            }
        }
    }
