<?php

    namespace kyra\gallery\controllers;

    use kyra\common\PayloadEvent;
    use kyra\common\BaseController;
    use kyra\common\GalleryHelper;
    use kyra\gallery\models\Gallery;
    use kyra\gallery\models\GalleryImages;
    use kyra\image\models\Image;
    use Yii;
    use yii\bootstrap\ActiveForm;
    use yii\data\ActiveDataProvider;
    use yii\filters\AccessControl;
    use yii\web\Controller;
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
                            'actions' => ['delete', 'set-main', 'add-image', 'change-order', 'remove-image', 'images', 'edit', 'create', 'admin-list'],
                            'roles' => $this->module->accessRoles,
                        ],
                    ],
                ],
            ];
        }

        public function actionDelete($gid)
        {
            $g = Gallery::findOne($gid)->delete();
            Yii::$app->response->format = Response::FORMAT_JSON;
            return true;
        }


        public function actionSetMain()
        {
            $gh = new GalleryHelper;
            $ret = $gh->SetMainImage($_POST['ObjectID'], $_POST['IID'], Gallery::className(), 'HeaderIID');
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $ret;
        }

        public function actionAddImage()
        {
            $gh = new GalleryHelper();
            $ret = $gh->AddImage($_POST['ObjectID'], $_POST['IID'], GalleryImages::className(), 'GalleryID', 'IID');
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
            $gh = new GalleryHelper;
            $ret = $gh->RemoveImage($_POST['ObjectID'], $_POST['IID'], new GalleryImages, 'GalleryID', 'IID');
            if ($ret !== false)
            {
                $imgModule = Yii::$app->getModule($this->module->imageModuleName);
                $uploadParams = $imgModule->uploadParams[$this->module->uploadPathKey];

                $paths = Image::GetImageAllPaths($uploadParams, $ret);
                foreach ($paths as $key => $data)
                {
                    // в 'ABS' - абсолютный дисковый путь до конкретного файла
                    if (is_file($data['ABS'])) unlink($data['ABS']);
                }
                $ret = true;
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $ret;
        }

        public function actionImages($gid)
        {
            $model = Gallery::find()->with(['headerImage'])->where(['GalleryID' => $gid])->one();
            if (empty($model))
            {
                Yii::$app->session->setFlash('gallery', 'Нет галереи с таким ID!');
                return $this->redirect(['/kyra.gallery/default/list']);
            }

            $images = GalleryImages::find()->joinWith(['image'])->orderBy('SortOrder')->where(['GalleryID' => $gid])->asArray()->all();

            $this->layout = $this->module->adminLayout;
            $this->pageTitle = 'Добавить изображения';
            $this->breadcrumbs[] = ['label' => 'Все галереи', 'url' => ['/kyra.gallery/default/admin-list']];
            $this->breadcrumbs[] = $this->pageTitle;

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

        public function actionAdminList()
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