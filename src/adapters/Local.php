<?php

namespace light\filesystem\adapters;

use Yii;
use yii\base\Configurable;

/**
 * Class Local
 *
 * @package weyii\filesystem\adapters
 */
class Local extends \League\Flysystem\Adapter\Local implements Configurable
{
    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['root'])) {
            $config['root'] = Yii::getAlias($config['root']);
        }
        
        $config = array_merge([
            'root' => null,
            'writeFlags' => LOCK_EX,
            'linkHandling' => self::DISALLOW_LINKS,
            'permissions' => [],
        ], $config);
        
        call_user_func_array([$this, 'parent::__construct'], $config);
    }
    
    /**
     * @inheritdoc
     */
    protected function ensureDirectory($root)
    {
        parent::ensureDirectory(Yii::getAlias($root));
    }
}
