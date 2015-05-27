<?php
/**
 * @link https://github.com/gromver/yii2-platform-basic.git#readme
 * @copyright Copyright (c) Gayazov Roman, 2014
 * @license https://github.com/gromver/yii2-platform-basic/blob/master/LICENSE
 * @package yii2-platform-basic
 * @version 1.0.0
 */

namespace gromver\platform\basic\modules\news\models;


use dosamigos\transliterator\TransliteratorHelper;
use gromver\platform\basic\behaviors\NestedSetsBehavior;
use gromver\platform\basic\behaviors\SearchBehavior;
use gromver\platform\basic\behaviors\TaggableBehavior;
use gromver\platform\basic\behaviors\upload\ThumbnailProcessor;
use gromver\platform\basic\behaviors\UploadBehavior;
use gromver\platform\basic\behaviors\VersionBehavior;
use gromver\platform\basic\components\UrlManager;
use gromver\platform\basic\interfaces\model\SearchableInterface;
use gromver\platform\basic\interfaces\model\TranslatableInterface;
use gromver\platform\basic\interfaces\model\ViewableInterface;
use gromver\platform\basic\modules\user\models\User;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * This is the model class for table "grom_category".
 * @package yii2-platform-basic
 * @author Gayazov Roman <gromver5@gmail.com>
 *
 * @property integer $id
 * @property integer $parent_id
 * @property integer $translation_id
 * @property string $language
 * @property string $title
 * @property string $alias
 * @property string $path
 * @property string $preview_text
 * @property string $preview_image
 * @property string $detail_text
 * @property string $detail_image
 * @property string $metakey
 * @property string $metadesc
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $published_at
 * @property integer $status
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $lft
 * @property integer $rgt
 * @property integer $level
 * @property integer $ordering
 * @property integer $hits
 * @property integer $lock
 *
 * @property Post[] $posts
 * @property User $user
 * @property Category $parent
 * @property Category[] $translations
 * @property \gromver\platform\basic\modules\tag\models\Tag[] $tags
 */
class Category extends \yii\db\ActiveRecord implements TranslatableInterface, ViewableInterface, SearchableInterface
{
    const STATUS_PUBLISHED = 1;
    const STATUS_UNPUBLISHED = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%grom_category}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'created_at', 'updated_at', 'status', 'created_by', 'updated_by', 'lft', 'rgt', 'level', 'ordering', 'hits', 'lock'], 'integer'],
            [['preview_text', 'detail_text'], 'string'],
            [['title'], 'string', 'max' => 1024],
            [['alias', 'metakey'], 'string', 'max' => 255],
            [['path', 'metadesc'], 'string', 'max' => 2048],

            [['published_at'], 'date', 'format' => 'dd.MM.yyyy HH:mm', 'timestampAttribute' => 'published_at', 'when' => function() {
                return is_string($this->published_at);
            }],
            [['published_at'], 'integer', 'enableClientValidation' => false],
            [['language'], 'required'],
            [['language'], 'string', 'max' => 7],
            /*[['language'], function($attribute) {
                if (($parent = self::findOne($this->parent_id)) && !$parent->isRoot() && $parent->language != $this->language) {
                    $this->addError($attribute, Yii::t('gromver.platform', 'Language has to match with the parental.'));
                }
            }],*/

            [['alias'], 'filter', 'filter' => 'trim'],
            [['alias'], 'filter', 'filter' => function($value) {
                if (empty($value)) {
                    return Inflector::slug(TransliteratorHelper::process($this->title));
                } else {
                    return Inflector::slug($value);
                }
            }],
            [['alias'], 'unique', 'filter' => function($query) {
                /** @var $query \yii\db\ActiveQuery */
                if($parent = self::findOne($this->parent_id)){
                    $query->andWhere('lft>=:lft AND rgt<=:rgt AND level=:level AND language=:language', [
                        'lft' => $parent->lft,
                        'rgt' => $parent->rgt,
                        'level' => $parent->level + 1,
                        'language' => $this->language
                    ]);
                }
            }],
            [['alias'], 'string', 'max' => 250],
            [['alias'], 'required', 'enableClientValidation' => false],
            [['translation_id'], 'unique', 'filter' => function($query) {
                /** @var $query \yii\db\ActiveQuery */
                $query->andWhere(['language' => $this->language]);
            }, 'message' => Yii::t('gromver.platform', 'Localization ({language}) for item (ID: {id}) already exists.', ['language' => $this->language, 'id' => $this->translation_id])],

            [['title', 'detail_text', 'status'], 'required'],
            [['tags', 'versionNote'], 'safe'],
            [['ordering'], 'filter', 'filter' => 'intVal'], //for proper $changedAttributes
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('gromver.platform', 'ID'),
            'parent_id' => Yii::t('gromver.platform', 'Parent'),
            'translation_id' => Yii::t('gromver.platform', 'Translation ID'),
            'language' => Yii::t('gromver.platform', 'Language'),
            'title' => Yii::t('gromver.platform', 'Title'),
            'alias' => Yii::t('gromver.platform', 'Alias'),
            'path' => Yii::t('gromver.platform', 'Path'),
            'preview_text' => Yii::t('gromver.platform', 'Preview Text'),
            'preview_image' => Yii::t('gromver.platform', 'Preview Image'),
            'detail_text' => Yii::t('gromver.platform', 'Detail Text'),
            'detail_image' => Yii::t('gromver.platform', 'Detail Image'),
            'metakey' => Yii::t('gromver.platform', 'Meta keywords'),
            'metadesc' => Yii::t('gromver.platform', 'Meta description'),
            'created_at' => Yii::t('gromver.platform', 'Created At'),
            'updated_at' => Yii::t('gromver.platform', 'Updated At'),
            'published_at' => Yii::t('gromver.platform', 'Published At'),
            'status' => Yii::t('gromver.platform', 'Status'),
            'created_by' => Yii::t('gromver.platform', 'Created By'),
            'updated_by' => Yii::t('gromver.platform', 'Updated By'),
            'lft' => Yii::t('gromver.platform', 'Lft'),
            'rgt' => Yii::t('gromver.platform', 'Rgt'),
            'level' => Yii::t('gromver.platform', 'Level'),
            'ordering' => Yii::t('gromver.platform', 'Ordering'),
            'hits' => Yii::t('gromver.platform', 'Hits'),
            'lock' => Yii::t('gromver.platform', 'Lock'),
            'tags' => Yii::t('gromver.platform', 'Tags'),
            'versionNote' => Yii::t('gromver.platform', 'Version Note')
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            BlameableBehavior::className(),
            TaggableBehavior::className(),
            NestedSetsBehavior::className(),
            SearchBehavior::className(),
            [
                'class' => VersionBehavior::className(),
                'attributes' => ['title', 'alias', 'preview_text', 'detail_text', 'metakey', 'metadesc']
            ],
            [
                'class' => UploadBehavior::className(),
                'attributes' => [
                    'detail_image'=>[
                        'fileName' => '{id}-full.#extension#'
                    ],
                    'preview_image'=>[
                        'fileName' => '{id}-thumb.#extension#',
                        'fileProcessor' => ThumbnailProcessor::className()
                    ]
                ],
                'options' => [
                    'savePath' => 'upload/categories'
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @inheritdoc
     * @return CategoryQuery
     */
    public static function find()
    {
        return new CategoryQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosts()
    {
        return $this->hasMany(Post::className(), ['category_id'=>'id'])->inverseOf('category');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return CategoryQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::className(), ['id' => 'parent_id']);
    }

    /**
     * @return bool
     */
    public function getPublished()
    {
        return $this->status == self::STATUS_PUBLISHED;
    }

    /**
     * @param bool $includeSelf
     * @return array
     */
    public function getBreadcrumbs($includeSelf = false)
    {
        if ($this->isRoot()) {
            return [];
        } else {
            $path = $this->parents()->excludeRoots()->all();
            if ($includeSelf) {
                $path[] = $this;
            }
            return array_map(function ($item) {
                /** @var self $item */
                return [
                    'label' => $item->title,
                    'url' => $item->getFrontendViewLink()
                ];
            }, $path);
        }
    }

    private static $_statuses = [
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_UNPUBLISHED => 'Unpublished',
    ];

    /**
     * @return array
     */
    public static function statusLabels()
    {
        return array_map(function($label) {
                return Yii::t('gromver.platform', $label);
            }, self::$_statuses);
    }

    /**
     * @param string|null $status
     * @return string
     */
    public function getStatusLabel($status = null)
    {
        if ($status === null) {
            return Yii::t('gromver.platform', self::$_statuses[$this->status]);
        }
        return Yii::t('gromver.platform', self::$_statuses[$status]);
    }

    /**
     * @inheritdoc
     */
    public function optimisticLock()
    {
        return 'lock';
    }

    /**
     * Увеличивает счетчик просмотров
     * @return int
     */
    public function hit()
    {
        return 1;//return $this->updateAttributes(['hits' => $this->hits + 1]);
    }

    /**
     * @param bool $runValidation
     * @param null $attributes
     * @return bool
     */
    public function saveNode($runValidation = true, $attributes = null)
    {
        if ($this->getIsNewRecord()) {
            // если parent_id не задан, то ищем корневой элемент
            if($parent = $this->parent_id ? self::findOne($this->parent_id) : self::find()->roots()->one()) {
                $this->parent_id = $parent->id;
                return $this->appendTo($parent, $runValidation, $attributes);
            } else {
                // если рутового элемента не существует, то сохраняем модель как корневую
                return $this->makeRoot($runValidation, $attributes);
            }
        }

        // модель перемещена в другую модель
        if ($this->getOldAttribute('parent_id') != $this->parent_id && $newParent = $this->parent_id ? self::findOne($this->parent_id) : self::find()->roots()->one()) {
            $this->parent_id = $newParent->id;
            return $this->appendTo($newParent, $runValidation, $attributes);
        }
        // просто апдейт
        return $this->save($runValidation, $attributes);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // устанавливаем translation_id по умолчанию
        if ($insert && $this->translation_id === null) {
            $this->updateAttributes([
                'translation_id' => $this->id
            ]);
        }

        // нормализуем пути подэлементов для текущего элемента при его перемещении, либо изменении псевдонима
        if (array_key_exists('parent_id', $changedAttributes) || array_key_exists('alias', $changedAttributes)) {
            $this->refresh();
            $this->normalizePath();
        }

        // ранжируем элементы если нужно
        if (array_key_exists('ordering', $changedAttributes)) {
            $this->ordering ? $this->parent->reorderNode('ordering') : $this->parent->reorderNode('lft');
        }
    }

    /**
     * @return string
     */
    private function calculatePath()
    {
        $aliases = $this->parents()->excludeRoots()->select('alias')->column();
        return empty($aliases) ? $this->alias : implode('/', $aliases) . '/' . $this->alias;
    }

    /**
     * @param string $parentPath
     */
    public function normalizePath($parentPath = null)
    {
        if($parentPath === null) {
            $path = $this->calculatePath();
        } else {
            $path = $parentPath . '/' . $this->alias;
        }

        $this->updateAttributes(['path' => $path]);

        $children = $this->children(1)->all();
        foreach ($children as $child) {
            /** @var self $child */
            $child->normalizePath($path);
        }
    }

    // ViewableInterface
    /**
     * @inheritdoc
     */
    public function getFrontendViewLink()
    {
        return ['/grom/news/frontend/category/view', 'id' => $this->id, UrlManager::LANGUAGE_PARAM => $this->language/*, 'alias'=>$this->alias*/];
    }

    /**
     * @inheritdoc
     */
    public static function frontendViewLink($model)
    {
        return ['/grom/news/frontend/category/view', 'id' => $model['id'], UrlManager::LANGUAGE_PARAM => $model['language']/*, 'alias'=>$model['alias']*/];
    }

    /**
     * @inheritdoc
     */
    public function getBackendViewLink()
    {
        return ['/grom/news/backend/category/view', 'id' => $this->id];
    }

    /**
     * @inheritdoc
     */
    public static function backendViewLink($model)
    {
        return ['/grom/news/backend/category/view', 'id' => $model['id']];
    }

    // TranslatableInterface
    /**
     * @inheritdoc
     */
    public function getTranslations()
    {
        return self::hasMany(self::className(), ['translation_id' => 'translation_id'])->indexBy('language');
    }

    /**
     * @inheritdoc
     */
    public function getLanguage()
    {
        return $this->language;
    }

    // SearchableInterface
    /**
     * @inheritdoc
     */
    public function getSearchTitle()
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getSearchContent()
    {
        return $this->detail_text;
    }

    /**
     * @inheritdoc
     */
    public function getSearchTags()
    {
        return ArrayHelper::map($this->tags, 'id', 'title');
    }

    // SqlSearch integration
    /**
     * @param $event \gromver\platform\basic\modules\search\modules\sql\widgets\events\SqlBeforeSearchEvent
     */
    static public function sqlBeforeFrontendSearch($event)
    {
        $event->query->leftJoin('{{%grom_category}}', [
                'AND',
                ['=', 'model_class', self::className()],
                'model_id={{%grom_category}}.id',
                ['=', '{{%grom_category}}.status', self::STATUS_PUBLISHED],
                ['NOT IN', '{{%grom_category}}.parent_id', Category::find()->unpublished()->select('{{%grom_category}}.id')->column()]
            ]
        )->addSelect('{{%grom_category}}.id')
            ->andWhere('model_class=:categoryClassName XOR {{%grom_category}}.id IS NULL', [':categoryClassName' => self::className()]);
    }

    // ElasticSearch integration
    /**
     * @param $event \gromver\platform\basic\modules\search\modules\elastic\widgets\events\ElasticBeforeSearchEvent
     */
    static public function elasticBeforeFrontendSearch($event)
    {
        $event->sender->filters[] = [
            'not' => [
                'and' => [
                    [
                        'term' => ['model_class' => self::className()]
                    ],
                    [
                        'terms' => ['model_id' => self::find()->unpublished()->select('{{%grom_category}}.id')->column()]
                    ]
                ]
            ]
        ];
    }
}
