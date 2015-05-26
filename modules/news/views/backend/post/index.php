<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel gromver\platform\basic\modules\news\models\PostSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('gromver.platform', 'Posts');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="post-index">

    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

<?php /*<p>
        <?= Html::a(Yii::t('gromver.platform', 'Create {modelClass}', [
    'modelClass' => 'Post',
]), ['create'], ['class' => 'btn btn-success']) ?>
    </p> */?>

    <?= GridView::widget([
        'id' => 'table-grid',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pjax' => true,
        'pjaxSettings' => [
            'neverTimeout' => true,
        ],
        'columns' => [
            ['class' => '\kartik\grid\CheckboxColumn'],
            [
                'attribute' => 'id',
                'hAlign' => GridView::ALIGN_CENTER,
                'vAlign' => GridView::ALIGN_MIDDLE,
                'width' => '60px'
            ],
            [
                'attribute' => 'language',
                'hAlign' => GridView::ALIGN_CENTER,
                'vAlign' => GridView::ALIGN_MIDDLE,
                'width' => '80px',
                'value' => function($model) {
                        /** @var $model \gromver\platform\basic\modules\news\models\Post */
                        return \gromver\platform\basic\modules\main\widgets\TranslationsBackend::widget(['model' => $model]);
                    },
                'format' => 'raw',
                'filter' => Yii::$app->getAcceptedLanguagesList()
            ],
            [
                'attribute' => 'category_id',
                'vAlign' => GridView::ALIGN_MIDDLE,
                'width' => '80px',
                'value' => function($model){
                        /** @var $model \gromver\platform\basic\modules\news\models\Post */
                        return @$model->category->title;
                    },
                'filter' => \yii\helpers\ArrayHelper::map(\gromver\platform\basic\modules\news\models\Category::find()->excludeRoots()->orderBy('lft')->all(), 'id', function($model){
                    /** @var $model \gromver\platform\basic\modules\news\models\Category */
                        return str_repeat(" • ", max($model->level-2, 0)) . $model->title;
                    })
            ],
            [
                'attribute' => 'title',
                'vAlign' => GridView::ALIGN_MIDDLE,
                'value' => function($model) {
                    /** @var \gromver\platform\basic\modules\news\models\Post $model */
                    return $model->title . '<br/>' . Html::tag('small', ' — ' . $model->alias, ['class' => 'text-muted']);
                },
                'format' => 'html'
            ],
            /*[
                'attribute' => 'alias',
                'vAlign' => GridView::ALIGN_MIDDLE,
            ],*/
            [
                'attribute' => 'status',
                'hAlign' => GridView::ALIGN_CENTER,
                'vAlign' => GridView::ALIGN_MIDDLE,
                'value' => function ($model) {
                        /** @var $model \gromver\platform\basic\modules\news\models\Post */
                        return $model->status === \gromver\platform\basic\modules\news\models\Post::STATUS_PUBLISHED ? Html::a('<i class="glyphicon glyphicon-ok-circle"></i>', \yii\helpers\Url::to(['unpublish', 'id' => $model->id]), ['class' => 'btn btn-default btn-xs', 'data-pjax' => '0', 'data-method' => 'post']) : Html::a('<i class="glyphicon glyphicon-remove-circle"></i>', \yii\helpers\Url::to(['publish', 'id' => $model->id]), ['class' => 'btn btn-danger btn-xs', 'data-pjax' => '0', 'data-method' => 'post']);
                    },
                'filter' => \gromver\platform\basic\modules\news\models\Post::statusLabels(),
                'format' => 'raw',
                'width'=>'80px'
            ],
            [
                'attribute' => 'published_at',
                'vAlign' => GridView::ALIGN_MIDDLE,
                'format' => ['date', 'd MMM Y H:mm'],
                'width' => '160px',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'pluginOptions' => [
                        'format' => 'dd.mm.yyyy'
                    ],
                    'type' => \kartik\date\DatePicker::TYPE_RANGE,
                    'attribute2' => 'published_at_to',
                ]
            ],
            [
                'attribute' => 'tags',
                'vAlign' => GridView::ALIGN_MIDDLE,
                'width' => '100px',
                'value' => function($model){
                        /** @var $model \gromver\platform\basic\modules\news\models\Post */
                        return implode(', ', \yii\helpers\ArrayHelper::map($model->tags, 'id', 'title'));
                    },
                'filterType' => dosamigos\selectize\SelectizeDropDownList::className(),
                'filterWidgetOptions' => [
                    'items' => \yii\helpers\ArrayHelper::map(\gromver\platform\basic\modules\tag\models\Tag::find()->where(['id' => $searchModel->tags])->all(), 'id', 'title', 'group'),
                    'clientOptions' => [
                        'maxItems' => 1
                    ],
                    'loadUrl' => ['/grom/tag/backend/default/tag-list']
                ]
            ],
            [
                'attribute' => 'ordering',
                'hAlign' => GridView::ALIGN_CENTER,
                'vAlign' => GridView::ALIGN_MIDDLE,
                'value' => function($model) {
                        /** @var $model \gromver\platform\basic\modules\news\models\Post */
                        return Html::input('text', 'order', $model->ordering, ['class'=>'form-control']);
                    },
                'format' => 'raw',
                'width' => '80px'
            ],
            [
                'class' => 'kartik\grid\ActionColumn',
                'deleteOptions' => ['data-method' => 'delete']
            ],
        ],
        'responsive' => true,
        'hover' => true,
        'condensed' => true,
        'floatHeader' => true,
        'bordered' => false,
        'panel' => [
            'heading' => '<h3 class="panel-title"><i class="glyphicon glyphicon-th-list"></i> ' . Html::encode($this->title) . ' </h3>',
            'type' => 'info',
            'before' => Html::a('<i class="glyphicon glyphicon-plus"></i> ' . Yii::t('gromver.platform', 'Add'), ['create', 'category_id' => $searchModel->category_id], ['class' => 'btn btn-success', 'data-pjax' => '0']),
            'after' =>
                Html::a('<i class="glyphicon glyphicon-sort-by-attributes"></i> ' . Yii::t('gromver.platform', 'Ordering'), ['ordering'], ['class' => 'btn btn-default', 'data-pjax' => '0', 'onclick' => 'processOrdering(this); return false']).' '.
                Html::a('<i class="glyphicon glyphicon-trash"></i> ' . Yii::t('gromver.platform', 'Delete'), ['bulk-delete'], ['class' => 'btn btn-danger', 'data-pjax' => '0', 'onclick' => 'processAction(this); return false']) . ' ' .
                Html::a('<i class="glyphicon glyphicon-repeat"></i> ' . Yii::t('gromver.platform', 'Reset List'), ['index'], ['class' => 'btn btn-info']),
            'showFooter' => false
        ],
    ]) ?>

</div>

<script>
    function processOrdering(el) {
        var $el = $(el),
            $grid = $('#table-grid'),
            selection = $grid.yiiGridView('getSelectedRows'),
            data = {}
        if(!selection.length) {
            alert(<?= json_encode(Yii::t('gromver.platform', 'Select items.')) ?>)
            return
        }
        $.each(selection, function(index, value){
            data[value] = $grid.find('tr[data-key="'+value+'"] input[name="order"]').val()
        })

        $.post($el.attr('href'), {data:data}, function(response){
            $grid.yiiGridView('applyFilter')
        })
    }
    function processAction(el) {
        var $el = $(el),
            $grid = $('#table-grid'),
            selection = $grid.yiiGridView('getSelectedRows')
        if(!selection.length) {
            alert(<?= json_encode(Yii::t('gromver.platform', 'Select items.')) ?>)
            return
        }

        $.post($el.attr('href'), {data:selection}, function(response){
            $grid.yiiGridView('applyFilter')
        })
    }
</script>