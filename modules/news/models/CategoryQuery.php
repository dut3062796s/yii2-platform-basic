<?php
/**
 * @link https://github.com/gromver/yii2-platform-basic.git#readme
 * @copyright Copyright (c) Gayazov Roman, 2014
 * @license https://github.com/gromver/yii2-platform-basic/blob/master/LICENSE
 * @package yii2-platform-basic
 * @version 1.0.0
 */

namespace gromver\platform\basic\modules\news\models;


use creocoder\nestedsets\NestedSetsQueryBehavior;
use yii\db\Query;

/**
 * Class CategoryQuery
 * @package yii2-platform-basic
 * @author Gayazov Roman <gromver5@gmail.com>
 */
class CategoryQuery extends \yii\db\ActiveQuery
{
    public function behaviors() {
        return [
            [
                'class' => NestedSetsQueryBehavior::className(),
            ],
        ];
    }
    /**
     * @return CategoryQuery
     */
    public function published()
    {
        $badcatsQuery = new Query([
            'select' => ['badcats.id'],
            'from' => ['{{%grom_category}} AS unpublished'],
            'join' => [
                ['LEFT JOIN', '{{%grom_category}} AS badcats', 'unpublished.lft <= badcats.lft AND unpublished.rgt >= badcats.rgt']
            ],
            'where' => 'unpublished.status != ' . Category::STATUS_PUBLISHED,
            'groupBy' => ['badcats.id']
        ]);

        return $this->andWhere(['NOT IN', '{{%grom_category}}.id', $badcatsQuery]);
    }

    /**
     * @return CategoryQuery
     */
    public function unpublished()
    {
        return $this->innerJoin('{{%grom_category}} AS ancestors', '{{%grom_category}}.lft >= ancestors.lft AND {{%grom_category}}.rgt <= ancestors.rgt')->andWhere('ancestors.status != ' . Category::STATUS_PUBLISHED)->addGroupBy(['{{%grom_category}}.id']);
    }

    /**
     * Фильтр по категории
     * @param $id
     * @return $this
     */
    public function parent($id)
    {
        return $this->andWhere(['{{%grom_category}}.parent_id' => $id]);
    }

    /**
     * @param $language
     * @return static
     */
    public function language($language)
    {
        return $this->andFilterWhere(['{{%grom_category}}.language' => $language]);
    }

    /**
     * @return static
     */
    public function excludeRoots()
    {
        return $this->andWhere('{{%grom_category}}.lft!=1');
    }

    /**
     * Исключает из выборки категорию $category и все ее подкатегории
     * @param Category $category
     * @return static
     */
    public function excludeCategory($category)
    {
        return $this->andWhere('{{%grom_category}}.lft < :excludeLft OR {{%grom_category}}.lft > :excludeRgt', [':excludeLft' => $category->lft, ':excludeRgt' => $category->rgt]);
    }
} 