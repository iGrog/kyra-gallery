<?php if (Yii::$app->session->hasFlash('gallery')) : ?>

    <div class="alert alert-info">
        <?= Yii::$app->session->getFlash('gallery'); ?>
    </div>

<?php endif; ?>

<?php
    use yii\bootstrap\ActiveForm;
    use yii\helpers\Html;
    use yii\helpers\Url;

    $form = ActiveForm::begin(); ?>

<?php if (!$model->isNewRecord) echo $form->field($model, 'GalleryID')->hiddenInput()->label(false); ?>
<?=$form->field($model, 'ParentID')->hiddenInput()->label(false); ?>
<?= $form->field($model, 'GalleryName') ?>
<?= $form->field($model, 'UrlKey') ?>
<?= $form->field($model, 'GalleryDescription')->textarea(['rows' => 6]) ?>
<?= $form->field($model, 'FacebookAlbumID') ?>

<?= Html::submitButton(Yii::t('app', 'Записать'), ['class' => 'btn btn-primary']) ?>

<?php if (!$model->isNewRecord) : ?>

    <a href="<?=Url::to(['/kyra.gallery/default/images', 'gid' => $model->GalleryID]); ?>" class="btn btn-info">Управление изображениями</a>

<?php endif; ?>

<?php ActiveForm::end(); ?>
