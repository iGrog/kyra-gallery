<?php

    namespace kyra\gallery\controllers;

    use kyra\common\PayloadEvent;
    use kyra\common\BaseController;
    use kyra\common\GalleryHelper;
    use kyra\gallery\models\Gallery;
    use kyra\gallery\models\GalleryImages;
    use kyra\image\models\Image;
    use kyra\image\models\ImageUploadedEvent;
    use Yii;
    use yii\bootstrap\ActiveForm;
    use yii\data\ActiveDataProvider;
    use yii\filters\AccessControl;
    use yii\filters\VerbFilter;
    use yii\helpers\ArrayHelper;
    use yii\web\Controller;
    use yii\web\NotFoundHttpException;
    use yii\web\Response;

    class DefaultController extends BaseController
    {
        public function behaviors()
        {
            return [
                'access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['delete', 'set-main', 'add-image', 'change-order', 'remove-image',
                                'create-child',
                                'images', 'edit', 'create', 'admin-list', 'manage-crop', 'crop-image', 'sort-gallery', 'remove-gallery'],
                            'roles' => $this->module->accessRoles,
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'remove-gallery' => ['post'],
                    ],
                ],

            ];
        }


        public function OnImageUploaded(ImageUploadedEvent $event)
        {
            if($event->uploadKey != 'gallery') return;

            $data = $event->payload;
            $gh = new GalleryHelper();
            $gh->AddImage($data['GalleryID'], $data['data']['IID'], GalleryImages::className(), 'GalleryID', 'IID');
        }


        public function actionRemoveGallery($gid)
        {
            $g = new Gallery;

            $imgModule = Yii::$app->getModule($this->module->imageModuleName);
            $uploadParams = $imgModule->uploadParams[$this->module->uploadPathKey];
            $ret = $g->RemoveFullGallery($uploadParams, $gid);

            $msg = $ret ? 'Галерея была успешно удалена' : 'Ошибка при удалении галереи';

            Yii::$app->session->setFlash('gallery', $msg);
            $this->redirect(['/kyra.gallery/default/admin-list']);
        }

        public function actionSortGallery()
        {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $g = new Gallery;
            $ret = $g->UpdateSortOrder(@$_POST['Gallery']);
            return ['hasError' => !$ret];
        }

        public function actionCreate()
        {
            $model = new Gallery;

            $model->load($_POST);
            if (Yii::$app->request->isAjax)
            {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if (Yii::$app->request->isPost && $model->validate())
            {
                if ($model->AddGallery())
                {
                    Yii::$app->trigger(Gallery::EVENT_GALLERY_CREATED, new PayloadEvent(['payload' => $model->attributes]));
                    Yii::$app->session->setFlash('gallery', 'Галерея была успешно добавлена!');
                    return $this->redirect(['/kyra.gallery/default/images', 'gid' => $model->GalleryID]);
                }
            }

            $this->breadcrumbs[] = ['label' => 'Все галереи', 'url' => ['/kyra.gallery/default/admin-list']];
            $this->breadcrumbs[] = 'Создать новую галерею';
            $this->layout = $this->module->adminLayout;
            return $this->render('create', ['model' => $model]);
        }


        public function actionCreateChild($gid)
        {
            $parent = Gallery::find()->where(['GalleryID' => $gid])->asArray()->one();
            if(empty($parent))
                throw new NotFoundHttpException();

            $model = new Gallery;
            $model->ParentID = $gid;

            $model->load($_POST);
            if (Yii::$app->request->isAjax)
            {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if (Yii::$app->request->isPost && $model->validate())
            {
                if ($model->AddGallery())
                {
                    Yii::$app->trigger(Gallery::EVENT_GALLERY_CREATED, new PayloadEvent(['payload' => $model->attributes]));
                    Yii::$app->session->setFlash('gallery', 'Дочерняя галерея была успешно добавлена!');
                    return $this->redirect(['/kyra.gallery/default/images', 'gid' => $model->GalleryID]);
                }
            }

            $this->breadcrumbs[] = ['label' => 'Все галереи', 'url' => ['/kyra.gallery/default/admin-list']];
            $this->breadcrumbs[] = ['label' => 'Галерея '.$parent['GalleryName'], 'url' => ['/kyra.gallery/default/edit', 'gid' => $gid]];
            $this->breadcrumbs[] = 'Добавить дочернюю галерею';
            $this->layout = $this->module->adminLayout;
            return $this->render('create', ['model' => $model]);
        }

        public function actionCropImage()
        {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = @json_decode($_POST['data'], true);
            if($data === false) return ['hasError' => true, 'error' => 'Wrong request'];

            $imgModule = Yii::$app->getModule($this->module->imageModuleName);
            $uploadParams = $imgModule->uploadParams[$this->module->uploadPathKey];

            $i = new Image;
            $ret = $i->CropImage($uploadParams, $data['folderparam'], $data['iid'], $data['x'], $data['y'], $data['width'], $data['height'], $data['key'], true);

            return $ret;
        }

        public function actionManageCrop($gid)
        {
            $gallery = Gallery::find()->where(['GalleryID' => $gid])->asArray()->one();
            if(empty($gallery))
                throw new NotFoundHttpException();

            $images = GalleryImages::find()->with('image')->where(['GalleryID' => $gid])->asArray()->all();

            $imgModule = Yii::$app->getModule($this->module->imageModuleName);
            $uploadParams = $imgModule->uploadParams[$this->module->uploadPathKey];

//            unset($uploadParams[0]);
            $folderParam = '';
            if(array_key_exists('folderParam', $uploadParams))
            {
                $folderParam = $uploadParams['folderParam'];
                unset($uploadParams['folderParam']);
            }
            if(array_key_exists('o', $uploadParams['sizes'])) unset($uploadParams['sizes']['o']);

            $this->layout = $this->module->adminLayout;
            $this->breadcrumbs[] = ['label' => 'Все галереи', 'url' => ['/kyra.gallery/default/admin-list']];
            $this->breadcrumbs[] = ['label' => 'Изображения галереи '.$gallery['GalleryName'], 'url' => ['/kyra.gallery/default/images', 'gid' => $gallery['GalleryID']]];
            $this->breadcrumbs[] = 'Управление кропами';

            return $this->render('crop', ['images' => $images, 'crops' => $uploadParams, 'gallery' => $gallery, 'folderParam' => $folderParam]);
        }

        public function actionSetMain()
        {
            $gh = new GalleryHelper;
            $ret = $gh->SetMainImage($_POST['ObjectID'], $_POST['IID'], Gallery::className(), 'HeaderIID');
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $ret;
        }

        public function actionChangeOrder()
        {
            $gh = new GalleryHelper();
            $ret = $gh->ChangeOrder($_POST['ObjectID'], $_POST['Order'], new GalleryImages, 'GalleryID', 'IID', 'SortOrder');
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $ret;
        }

        public function actionRemoveImage()
        {
            $gi = new GalleryImages();

            $imgModule = Yii::$app->getModule($this->module->imageModuleName);
            $uploadParams = $imgModule->uploadParams[$this->module->uploadPathKey];
            $uploadParams = array_merge($uploadParams, $_POST['params']);

            $ret = $gi->RemoveImage($_POST['ObjectID'], $_POST['IID'], $uploadParams);

            // Remove Gallery headers
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $ret;
        }

        public function actionImages($gid)
        {
            $parents = Gallery::GetParents($gid, true);
            $parents = array_reverse($parents);

            $model = Gallery::find()->with(['headerImage'])->where(['GalleryID' => $gid])->one();
            if (empty($model))
            {
                Yii::$app->session->setFlash('gallery', 'Нет галереи с таким ID!');
                return $this->redirect(['/kyra.gallery/default/admin-list']);
            }

            $images = GalleryImages::find()->joinWith(['image'])->orderBy('SortOrder')->where(['GalleryID' => $gid])->asArray()->all();

            $this->layout = $this->module->adminLayout;
            $this->pageTitle = 'Добавить изображения';
            $this->breadcrumbs[] = ['label' => 'Все галереи', 'url' => ['/kyra.gallery/default/admin-list']];
            foreach($parents as $parent)
            {
                if($parent['GalleryID'] != $gid)
                    $this->breadcrumbs[] = ['label' => $parent['GalleryName'], 'url' => ['/kyra.gallery/default/images', 'gid' => $parent['GalleryID']]];
                else
                    $this->breadcrumbs[] = $parent['GalleryName'];
            }

            return $this->render('images', ['model' => $model, 'images' => $images]);
        }

        public function actionEdit($gid)
        {
            $model = Gallery::find()->with('headerImage')->where(['GalleryID' => $gid])->one();
            if (empty($model))
            {
                Yii::$app->session->setFlash('gallery', 'Нет галереи с таким ID!');
                return $this->redirect(['/kyra.gallery/default/list']);
            }

            $model->load($_POST);
            if (Yii::$app->request->isAjax)
            {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if (Yii::$app->request->isPost && $model->validate())
            {
                $model->UpdateGallery();
                Yii::$app->trigger(Gallery::EVENT_GALLERY_UPDATED, new PayloadEvent(['payload' => $model->attributes]));
                Yii::$app->session->setFlash('gallery', 'Галерея была успешно отредактирована!');
                return $this->redirect(['/kyra.gallery/default/edit', 'gid' => $model->GalleryID]);
            }

            if (!empty($model->headerImage))
            {
                $imgData = array_merge($model->attributes, $model->headerImage->attributes);
                $model->image = Image::GetImageUrl($imgData, $this->module->uploadPathKey, 'sq');
            }

            $this->breadcrumbs[] = ['label' => 'Все галереи', 'url' => ['/kyra.gallery/default/admin-list']];
            $this->breadcrumbs[] = $model->GalleryName;
            $this->layout = $this->module->adminLayout;
            return $this->render('edit', ['model' => $model]);
        }

        public static function ParseTree($root, $tree, $idName, $pidName, $additionalParams = array())
        {
            $return = array();
            # Traverse the tree and search for direct children of the root
            foreach ($tree as $idx => $node)
            {
                $parent = $node[$pidName];
                # A direct child is found
                if ($parent == $root)
                {
                    # Remove item from tree (we don't need to traverse this again)
                    unset($tree[$idx]);
                    # Append the child into result array and parse it's children
                    $p = ['payload' => $node,
                        'parent' => $parent,
                        'children' => self::parseTree($node[$idName], $tree, $idName, $pidName, $additionalParams)
                    ];

                    foreach ($additionalParams as $key => $val) $p[$key] = $val;

                    $return[] = $p;
                }
            }
            return empty($return) ? array() : $return;
        }


        public function actionAdminList()
        {
            if($this->module->nested)
            {
                $flatTree = Gallery::find()->with(['headerImage'])->orderBy('ParentID, SortOrder')->asArray()->all();
                     $tree = self::ParseTree(0, $flatTree, 'GalleryID', 'ParentID');

                     $this->breadcrumbs[] = 'Все галереи';
                     $this->layout = $this->module->adminLayout;
                     return $this->render('sort-list', ['tree' => $tree]);
            }
            else
            {
                $dp = new ActiveDataProvider([
                    'query' => Gallery::find()->with(['headerImage']),
                    'sort' => [
                        'defaultOrder' => [
                            'SortOrder' => SORT_ASC,
                        ]
                    ],
                    'pagination' => [
                        'pageSize' => 20,
                    ],
                ]);

                $this->breadcrumbs[] = 'Все галереи';
                $this->layout = $this->module->adminLayout;
                return $this->render('list', ['dp' => $dp]);
            }
        }
    }