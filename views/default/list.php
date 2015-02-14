<?php

    use kyra\image\models\Image;
    use yii\helpers\Url;

?>

<a href="<?=Url::to(['/kyra. gallery/default/create']);?>" class="btn btn-info">Создать новую галерею</a>

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
        ['class' => \yii\grid\ActionColumn::className(),
            'urlCreator' => function ($type, $data)
            {
                if ($type == 'view') return Url::to(['/kyra.gallery/default/images', 'gid' => $data['GalleryID']]);
                else if ($type == 'update') return Url::to(['/kyra.gallery/default/edit', 'gid' => $data['GalleryID']]);
                else if ($type == 'delete') return Url::to(['/kyra.gallery/default/delete', 'gid' => $data['GalleryID']]);
            }
        ]
    ],
]);