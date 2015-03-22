<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 22.03.15
 * Time: 10:01
 */

namespace gromver\platform\basic\widgets;


use yii\caching\Cache;
use yii\di\Instance;

trait WidgetCacheTrait {
    /**
     * @var Cache|string
     * @field list
     * @items caches
     * @before <h3>Caching</h3>
     * @label Cache Component
     * @translation gromver.platform
     */
    public $cache = 'cache';
    /**
     * @var integer
     * @label Cache Duration
     * @translation gromver.platform
     */
    public $cacheDuration = 3600;

    protected function ensureCache()
    {
        if (isset($this->cache)) {
            $this->cache = $this->cache ? Instance::ensure($this->cache, Cache::className()) : null;
        }

        return $this->cache;
    }

    public static function caches()
    {
        return [
            \Yii::t('gromver.platform', 'No cache'),
            'cache' => 'cache'
        ];
    }
} 