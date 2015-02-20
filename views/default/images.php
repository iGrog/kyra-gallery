<?php

    use kyra\image\models\Image;
    use yii\helpers\Html;
    use yii\helpers\Url;

?>
<h2>Управление изображениями в галереи "<?=Html::encode($model['GalleryName']); ?>"</h2>


<button class="btn btn-large btn-primary" id="UploadButton">Загрузить фотографии</button>
или
<a href="<?=Url::to(['/kyra.gallery/default/manage-crop', 'gid' => $model['GalleryID']]); ?>">Редактировать кропы</a>
или
<a data-method="post"
   data-confirm="Удалить все изображения в этой галереи и во всех подгалереях? Это нельзя будет обратить. Удалить?"
    href="<?=Url::to(['/kyra.gallery/default/remove-gallery', 'gid' => $model['GalleryID']]); ?>" class="btn btn-danger btn-xs">Удалить галерею целиком</a>

<hr/>

<h2>Существующие изображения в этой галереи</h2>

<div class="row" id="Images">

    <div class="col-md-12">
        <ul id="ImgList" style="margin:0; padding: 0">
            <?php foreach($images as $img) : ?>
            <li data-imageid="<?=$img['IID']; ?>" class="thumbnail">
                <?php
                    $imgData = $img['image'];
                    unset($img['image']);
                    $imgData = \yii\helpers\ArrayHelper::merge($img, $imgData); ?>
                <img src="<?=Image::GetImageUrl($imgData, $this->context->module->uploadPathKey, 'sq'); ?>" class="square">
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>

<?=\kyra\common\MultisortUpload::widget([
    'objectID' => $model->GalleryID,
    'button' => 'UploadButton',
    'list' => 'ImgList',
    'jsonField' => 'sq', // json.data.Images['sq']
    'uploadUrl' => Url::to(['/kyra.image/default/upload']),
    'changeOrderUrl' => Url::to(['/kyra.gallery/default/change-order']),
    'removeImageUrl' => Url::to(['/kyra.gallery/default/remove-image']),
    'setMainUrl' => Url::to(['/kyra.gallery/default/set-main']),
    'addParams' => ['path' => $this->context->module->uploadPathKey, 'params' => ['GalleryID' => $model->GalleryID]],

]); ?>


