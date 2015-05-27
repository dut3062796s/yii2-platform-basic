<?php
/**
 * @link https://github.com/gromver/yii2-platform-basic.git#readme
 * @copyright Copyright (c) Gayazov Roman, 2014
 * @license https://github.com/gromver/yii2-platform-basic/blob/master/LICENSE
 * @package yii2-platform-basic
 * @version 1.0.0
 */

namespace gromver\platform\basic\widgets;


use gromver\platform\basic\modules\widget\widgets\Widget;

/**
 * Class Carousel
 * @package yii2-platform-basic
 * @author Gayazov Roman <gromver5@gmail.com>
 */
class Carousel extends Widget
{
    /**
     * @field multiple
     * @multyField object
     * @object \gromver\platform\basic\widgets\CarouselItem
     * @label Slides
     * @translation gromver.platform
     */
    public $items;

    protected function launch()
    {
        echo \yii\bootstrap\Carousel::widget([
            'items' => $this->items
        ]);
    }
}

class CarouselItem {
    /**
     * @field editor
     * @translation gromver.platform
     */
    public $content;
    /**
     * @translation gromver.platform
     */
    public $caption;
}