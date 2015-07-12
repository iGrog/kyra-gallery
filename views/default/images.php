<?php

use kyra\image\models\Image;
use yii\helpers\Html;
use yii\helpers\Url;

?>

    <div class="modal fade" id="EditModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Редактировать имя и описание</h4>
                </div>
                <div class="modal-body">

                    <input type="hidden" name="IID" id="EditModalIID" value="" />

                    <label for="">Заголовок</label>
                        <input type="text" class="form-control" name="Title" id="EditModalTitle" placeholder="Заголовок картинки" />

                    <label for="">Описание</label>
                    <textarea name="Desc" id="EditModalDesc" cols="30" rows="10" class="form-control"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-primary" id="EditModalSave" data-loading-text="Записываем...">Записать</button>
                </div>
            </div>
        </div>
    </div>


    <h2>Управление изображениями в галереи "<?= Html::encode($model['GalleryName']); ?>"</h2>


    <button class="btn btn-large btn-primary" id="UploadButton">Загрузить фотографии</button>
    или
    <a href="<?= Url::to(['/kyra.gallery/default/manage-crop', 'gid' => $model['GalleryID']]); ?>">Редактировать кропы</a>
    или
    <a data-method="post"
       data-confirm="Удалить все изображения в этой галереи и во всех подгалереях? Это нельзя будет обратить. Удалить?"
       href="<?= Url::to(['/kyra.gallery/default/remove-gallery', 'gid' => $model['GalleryID']]); ?>" class="btn btn-danger btn-xs">Удалить галерею целиком</a>

    <hr/>

    <h2>Существующие изображения в этой галереи</h2>

    <div class="row" id="Images">

        <div class="col-md-12">
            <ul id="ImgList" style="margin:0; padding: 0">
                <?php foreach ($images as $img) : ?>
                    <li data-imageid="<?= $img['IID']; ?>" class="thumbnail">
                        <?php
                        $imgData = $img['image'];
                        unset($img['image']);
                        $imgData = \yii\helpers\ArrayHelper::merge($img, $imgData); ?>
                        <img src="<?= Image::GetImageUrl($imgData, $this->context->module->uploadPathKey, 'sq'); ?>" class="square">
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>

<?= \kyra\common\MultisortUpload::widget([
    'objectID' => $model->GalleryID,
    'button' => 'UploadButton',
    'list' => 'ImgList',
    'jsonField' => 'sq', // json.data.Images['sq']
    'uploadUrl' => Url::to(['/kyra.image/default/upload']),
    'changeOrderUrl' => Url::to(['/kyra.gallery/default/change-order']),
    'removeImageUrl' => Url::to(['/kyra.gallery/default/remove-image']),
    'setMainUrl' => Url::to(['/kyra.gallery/default/set-main']),
    'addParams' => ['path' => $this->context->module->uploadPathKey, 'params' => ['GalleryID' => $model->GalleryID]],
    'editInfo' => 'editImageInfo'

]);


$imageInfoUrl = Url::to(['/kyra.gallery/default/get-image-info']);
$imageInfoSaveUrl = Url::to(['/kyra.gallery/default/set-image-info']);

$csrfToken = Yii::$app->request->csrfParam;
$csrfValue = Yii::$app->request->csrfToken;

$js = <<<EOE

var editModal = $('#EditModal');
var titleModal = $('#EditModalTitle');
var descModal = $('#EditModalDesc');
var iidModal = $('#EditModalIID');
var saveModal = $('#EditModalSave');

saveModal.click(function()
{
    saveModal.button('loading')
    var obj = { IID: iidModal.val(), FileTitle: titleModal.val(), FileDesc: descModal.val() };
    obj['$csrfToken'] = '$csrfValue';

    $.when($.post('$imageInfoSaveUrl', obj, 'json'))
     .then(function(json)
     {
        if(!json.hasError)
        {
            editModal.modal('hide');
        }
     }).always(function()
     {
        saveModal.button('reset');
     });
});

function editImageInfo(iid)
{
    $.getJSON('$imageInfoUrl', { iid: iid }, function(json)
    {
        iidModal.val(json.IID);
        titleModal.val(json.FileTitle);
        descModal.val(json.FileDesc);
        editModal.modal('show');
    });
}
EOE;


$this->registerJS($js);



