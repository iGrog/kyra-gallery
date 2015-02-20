<?php

use kyra\common\CropAsset;
use kyra\image\models\Image;
    use yii\helpers\Html;
    use yii\helpers\Url;

    $this->registerCss('.crop-image img { width: 100%; } .row.crop-image { margin-bottom: 20px; }');
    CropAsset::register($this);

?>
<h2>Управление кропами в галереи "<?=Html::encode($gallery['GalleryName']); ?>"</h2>

<?php $colSize = 12;
    switch(count($crops['sizes']))
    {
        case 2 : $colSize = 6; break;
        case 3 : $colSize = 4; break;
        case 4: $colSize = 3; break;
        case 5: $colSize = 2; break;
        case 6: $colSize = 2; break;
    }
?>
<div class="row">
    <?php foreach($crops['sizes'] as $key=>$data) : ?>
        <div class="col-md-<?=$colSize; ?>">
            <h3><?=$key; ?></h3>
        </div>
    <?php endforeach; ?>
</div>

<?php foreach($images as $img) : ?>
<div class="row crop-image">
    <?php foreach($crops['sizes'] as $key=>$data) : ?>
        <?php $imgData = \yii\helpers\ArrayHelper::merge($img, $img['image']);
        $fParam = empty($folderParam) ? '' : 'data-folderparam="'.$gallery[$folderParam].'"';
        ?>
        <div class="col-md-<?=$colSize; ?>">
            <img data-width="<?=$data[0]; ?>"
                 data-height="<?=$data[1]; ?>"
                 data-iid="<?=$img['IID']; ?>"
                 data-key="<?=$key; ?>"
                 <?=$fParam; ?>
                 data-orig="<?=Image::GetImageUrl($imgData, $this->context->module->uploadPathKey, 'o'); ?>"
                src="<?=Image::GetImageUrl($imgData, $this->context->module->uploadPathKey, $key); ?>" />
        </div>
    <?php endforeach; ?>
</div>

<?php endforeach; ?>


<div class="modal" id="cropModal" >
     <div class="modal-dialog" style="width: 90%; margin: 0 auto;">
       <div class="modal-content">
         <div class="modal-body">
           <img src="" id="imageOriginal" style="display:block; margin: 0 auto; max-width: 100%; max-height: 100%; " />
         </div>
       </div><!-- /.modal-content -->
     </div><!-- /.modal-dialog -->
   </div><!-- /.modal -->

<?php

$csrfToken = Yii::$app->request->csrfParam;
$csrfTokenValue = Yii::$app->request->csrfToken;
$cropUrl = Url::to(['/kyra.gallery/default/crop-image']);

$js = <<<JS

var origImage = $('#imageOriginal');
var cropModal = $('#cropModal');
$('.crop-image img').click(function()
{
    var img= $(this);
    var aspect = img.data('width') / img.data('height');
    origImage.attr('src', img.data('orig'));
    cropModal.modal('show');
    origImage.cropper('destroy');

    origImage.cropper(
    {
        minWidth: img.data('width'),
        minHeight: img.data('height'),
        aspectRatio : aspect,
        zoomable : false,
        rotatable : false,
        done : function(data)
        {
            data['key'] = img.data('key');
            data['iid'] = img.data('iid');
            data['folderparam'] = { $folderParam : img.data('folderparam')};
            cropModal.data('CROPDATA', data);
            cropModal.data('CROPIMG', img);
        }
    });
});

cropModal.on('hidden.bs.modal', function()
{
    var data = cropModal.data('CROPDATA');
    var obj = {
        data: JSON.stringify(data)
    };
    obj['$csrfToken'] = '$csrfTokenValue';
    $.post('$cropUrl', obj, function(json)
    {
        var img = cropModal.data('CROPIMG');
        var src = img.attr('src') + '?rand=' + Math.random();
        img.attr('src', src);
    });
});

JS;

    $this->registerJs($js);
