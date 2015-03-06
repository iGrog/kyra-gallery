<?php

    use kyra\image\models\Image;
    use yii\helpers\Url;

?>

<a href="<?=Url::to(['/kyra.gallery/default/create']);?>" class="btn btn-info">Создать новую галерею</a>

<?= yii\grid\GridView::widget(['dataProvider' => $dp,
    'columns' => [
        'HeaderIID' => [
            'format' => 'raw',
            'value' => function ($data)
            {
                if ($data->headerImage)
                {
                    $imgData = array_merge($data->attributes, $data->headerImage->attributes);
                    $img = Image::GetImageUrl($imgData, $this->context->module->uploadPathKey, 'sq');
                    return '<img src="' . $img . '" style="max-width: 200px; max-height: 150px;"  />';
                } else return '-';

            },
        ],

        'GalleryName',
        'GalleryDescription',
        'Actions' => [
          'format' => 'raw',
            'value' => function($data)
            {
                $view = Url::to(['/kyra.gallery/default/images', 'gid' => $data['GalleryID']]);
                $edit = Url::to(['/kyra.gallery/default/edit', 'gid' => $data['GalleryID']]);
                $delete = Url::to(['/kyra.gallery/default/remove-gallery', 'gid' => $data['GalleryID']]);
                $child = Url::to(['/kyra.gallery/default/create-child', 'gid' => $data['GalleryID']]);
                $ret = <<<RET
<a data-pjax="0" title="View" href="$view"><span class="glyphicon glyphicon-eye-open"></span></a>
<a data-pjax="0" title="Update" href="$edit"><span class="glyphicon glyphicon-pencil"></span></a>
<a data-pjax="0" data-method="post" data-confirm="Are you sure you want to delete this item?" title="Delete" href="$delete"><span class="glyphicon glyphicon-trash"></span></a>
RET;
                return $ret;


            }
        ],
    ],
]);