<?php

namespace light\filesystem;

use Closure;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use League\Flysystem\AdapterInterface;

/**
 * Class Manager
 *
 * @method bool has(string $path)
 * @method string|false read(string $path)
 * @method resouce|false readStream(string $path)
 * @method array listContents(string $directory = '', bool $recursive = false)
 * @method array|false getMetadata(string $path)
 * @method int|false getSize(string $path)
 * @method string|false getMimetype(string $path)
 * @method string|false getTimestamp(string $path)
 * @method string|false getVisibility(string $path)
 * @method bool write(string $path, string $contents, array $config =[])
 */
class Manager extends Component
{
    /**
     * @var string The default disk ID
     */
    public $default;
    /**
     * @var array The definitions of the disks
     */
    private $_definitions = [];
    /**
     * @var array The disk instance
     */
    private $_disks = [];
    
    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if ($this->default === null) {
            throw new InvalidConfigException('The "default" property must be set.');
        }
    }
    
    /**
     * @param bool|true $returnDefinitions
     *
     * @return array
     */
    public function getDisks($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_disks;
    }
    
    /**
     * @param array $disks
     */
    public function setDisks(array $disks)
    {
        foreach ($disks as $id => $component) {
            $this->setDisk($id, $component);
        }
    }
    
    /**
     * 获取Disk
     *
     * @param string|null $id
     * @param bool|true $throwException
     *
     * @return null|FilesystemInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getDisk($id = null, $throwException = true)
    {
        if ($id === null) {
            $id = $this->default;
        }
        
        if (isset($this->_disks[$id])) {
            return $this->_disks[$id];
        }
        
        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                $adapter = $definition;
            } else {
                $adapter = Yii::createObject($definition);
            }
            
            return $this->_disks[$id] = $this->createFilesystem($adapter);
        } elseif ($throwException) {
            throw new InvalidConfigException("Unknown disk ID: $id");
        } else {
            return null;
        }
    }
    
    /**
     * @param $id
     * @param $definition
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function setDisk($id, $definition)
    {
        if ($definition === null) {
            unset($this->_disks[$id], $this->_definitions[$id]);
            
            return;
        }
        
        unset($this->_disks[$id]);
        
        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new InvalidConfigException("The disk configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new InvalidConfigException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }
    
    /**
     * @param \League\Flysystem\AdapterInterface $adapter
     * @param array|null $config
     *
     * @return Filesystem
     */
    protected function createFilesystem(AdapterInterface $adapter, array $config = null)
    {
        return new Filesystem($adapter, $config);
    }
    
    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        foreach ($this->getBehaviors() as $object) {
            if ($object->hasMethod($name)) {
                return call_user_func_array([$object, $name], $params);
            }
        }
        
        return call_user_func_array([$this->getDisk(), $name], $params);
    }
}
