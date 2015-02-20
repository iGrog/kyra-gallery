<?php
use kyra\common\NestedSortableAsset;
use yii\helpers\Url;


?>

<?php if (Yii::$app->session->hasFlash('gallery')) : ?>

    <div class="alert alert-info">
        <?= Yii::$app->session->getFlash('gallery'); ?>
    </div>

<?php endif; ?>

<a href="<?=Url::to(['/kyra.gallery/default/create']);?>" class="btn btn-info">Создать новую галерею</a>

<div id="galleryTree">

    <?php

    NestedSortableAsset::register($this);

    function printTree($tree)
        {
            if (!is_null($tree) && count($tree) > 0)
            {
                echo '<ul class="dd-list">';
                foreach ($tree as $node)
                {
                    $payload = $node['payload'];
                    echo '<li class="dd-item" data-id="' . $payload['GalleryID'] . '"><div>' . $payload['GalleryName']
                            . ((!empty($payload['GalleryID']))
                                    ? ' <a href="' .  Url::to(['/kyra.gallery/default/images', 'gid' => $payload['GalleryID']]) . '" class="btn btn-warning btn-xs" title="Перейти к изображениям"><span class="glyphicon glyphicon-eye-open"></span></a>
                                    <a href="' .  Url::to(['/kyra.gallery/default/edit', 'gid' => $payload['GalleryID']]) . '" class="btn btn-info btn-xs" title="Редактировать галерею"><span class="glyphicon glyphicon-pencil"></span></a>'

                                    : '')
                            . '</div>';
                    printTree($node['children']);
                    echo '</li>';
                }
                echo '</ul>';
            }
        }

        if (!empty($tree)) printTree($tree);
        else echo '<ul class="dd-list"></ul>';

    ?>

</div>
<?php if(!empty($tree)) : ?>
    <button id="save" class="btn btn-success" data-loading-text="Записывается...">Записать</button>
<?php endif; ?>

<?php

$sortUrl = Url::to(['/kyra.gallery/default/sort-gallery']);
$csrfToken = Yii::$app->request->csrfParam;
$csrfValue = Yii::$app->request->csrfToken;

$js = <<<JS

        var gallery = $('#galleryTree > ul').first();

        gallery.nestedSortable({
            handle: 'div',
            items: 'li',
            toleranceElement: '> div',
            placeholder: 'placeholder',
            listType: 'ul',
            isTree: true
        });

        var saveBtn = $('#save');
        saveBtn.on('click', function ()
        {
            saveBtn.button('loading');
            var obj = { Gallery: gallery.nestedSortable('toPlainArray') };
            obj['$csrfToken'] = '$csrfValue';

            $.when($.post('$sortUrl', obj, 'json')).then(function(json)
            {
                if(!json.hasError)
                {
                    saveBtn.notify('Порядок успешно записан', 'success', { gap: 50 });
                }
                else
                {
                    saveBtn.notify('Ошибка при записи', 'error', { gap: 50 });
                }
            }).always(function()
            {
                saveBtn.button('reset');
            });
        });


JS;

$this->registerJs($js);
