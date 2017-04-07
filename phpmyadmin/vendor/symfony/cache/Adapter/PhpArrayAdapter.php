<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Adapter;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Caches items at warm up time using a PHP array that is stored in shared memory by OPCache since PHP 7.0.
 * Warmed up items are read-only and run-time discovered items are cached using a fallback adapter.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Nicolas Grekas <p@tchwork.com>
 */
class PhpArrayAdapter implements AdapterInterface
{
    private $file;
    private $values;
    private $createCacheItem;
    private $fallbackPool;

    /**
     * @param string           $file         The PHP file were values are cached
     * @param AdapterInterface $fallbackPool A pool to fallback on when an item is not hit
     */
    public function __construct($file, AdapterInterface $fallbackPool)
    {
        $this->file = $file;
        $this->fallbackPool = $fallbackPool;
        $this->createCacheItem = \Closure::bind(
            function ($key, $value, $isHit) {
                $item = new CacheItem();
                $item->key = $key;
                $item->value = $value;
                $item->isHit = $isHit;

                return $item;
            },
            null,
            CacheItem::class
        );
    }

    /**
     * This adapter should only be used on PHP 7.0+ to take advantage of how PHP
     * stores arrays in its latest versions. This factory method decorates the given
     * fallback pool with this adapter only if the current PHP version is supported.
     *
     * @param string $file The PHP file were values are cached
     *
     * @return CacheItemPoolInterface
     */
    public static function create($file, CacheItemPoolInterface $fallbackPool)
    {
        // Shared memory is available in PHP 7.0+ with OPCache enabled and in HHVM
        if ((PHP_VERSION_ID >= 70000 && ini_get('opcache.enable')) || defined('HHVM_VERSION')) {
            if (!$fallbackPool instanceof AdapterInterface) {
                $fallbackPool = new ProxyAdapter($fallbackPool);
            }

            return new static($file, $fallbackPool);
        }

        return $fallbackPool;
    }

    /**
     * Store an array of cached values.
     *
     * @param array $values The cached values
     */
    public function warmUp(array $values)
    {
        if (file_exists($this->file)) {
            if (!is_file($this->file)) {
                throw new InvalidArgumentException(sprintf('Cache path exists and is not a file: %s.', $this->file));
            }

            if (!is_writable($this->file)) {
                throw new InvalidArgumentException(sprintf('Cache file is not writable: %s.', $this->file));
            }
        } else {
            $directory = dirname($this->file);

            if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
                throw new InvalidArgumentException(sprintf('Cache directory does not exist and cannot be created: %s.', $directory));
            }

            if (!is_writable($directory)) {
                throw new InvalidArgumentException(sprintf('Cache directory is not writable: %s.', $directory));
            }
        }

        $dump = <<<'EOF'
<?php

// This file has been auto-generated by the Symfony Cache Component.

return array(


EOF;

        foreach ($values as $key => $value) {
            CacheItem::validateKey(is_int($key) ? (string) $key : $key);

            if (null === $value || is_object($value)) {
                try {
                    $value = serialize($value);
                } catch (\Exception $e) {
                    throw new InvalidArgumentException(sprintf('Cache key "%s" has non-serializable %s value.', $key, get_class($value)), 0, $e);
                }
            } elseif (is_array($value)) {
                try {
                    $serialized = serialize($value);
                    $unserialized = unserialize($serialized);
                } catch (\Exception $e) {
                    throw new InvalidArgumentException(sprintf('Cache key "%s" has non-serializable array value.', $key), 0, $e);
                }
                // Store arrays serialized if they contain any objects or references
                if ($unserialized !== $value || (false !== strpos($serialized, ';R:') && preg_match('/;R:[1-9]/', $serialized))) {
                    $value = $serialized;
                }
            } elseif (is_string($value)) {
                // Serialize strings if they could be confused with serialized objects or arrays
                if ('N;' === $value || (isset($value[2]) && ':' === $value[1])) {
                    $value = serialize($value);
                }
            } elseif (!is_scalar($value)) {
                throw new InvalidArgumentException(sprintf('Cache key "%s" has non-serializable %s value.', $key, gettype($value)));
            }

            $dump .= var_export($key, true).' => '.var_export($value, true).",\n";
        }

        $dump .= "\n);\n";
        $dump = str_replace("' . \"\\0\" . '", "\0", $dump);

        $tmpFile = uniqid($this->file, true);

        file_put_contents($tmpFile, $dump);
        @chmod($tmpFile, 0666 & ~umask());
        unset($serialized, $unserialized, $value, $dump);

        @rename($tmpFile, $this->file);

        $this->values = (include $this->file) ?: array();
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given.', is_object($key) ? get_class($key) : gettype($key)));
        }
        if (null === $this->values) {
            $this->initialize();
        }
        if (!isset($this->values[$key])) {
            return $this->fallbackPool->getItem($key);
        }

        $value = $this->values[$key];
        $isHit = true;

        if ('N;' === $value) {
            $value = null;
        } elseif (is_string($value) && isset($value[2]) && ':' === $value[1]) {
            try {
                $e = null;
                $value = unserialize($value);
            } catch (\Error $e) {
            } catch (\Exception $e) {
            }
            if (null !== $e) {
                $value = null;
                $isHit = false;
            }
        }

        $f = $this->createCacheItem;

        return $f($key, $value, $isHit);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = array())
    {
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given.', is_object($key) ? get_class($key) : gettype($key)));
            }
        }
        if (null === $this->values) {
            $this->initialize();
        }

        return $this->generateItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given.', is_object($key) ? get_class($key) : gettype($key)));
        }
        if (null === $this->values) {
            $this->initialize();
        }

        return isset($this->values[$key]) || $this->fallbackPool->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->values = array();

        $cleared = @unlink($this->file) || !file_exists($this->file);

        return $this->fallbackPool->clear() && $cleared;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given.', is_object($key) ? get_class($key) : gettype($key)));
        }
        if (null === $this->values) {
            $this->initialize();
        }

        return !isset($this->values[$key]) && $this->fallbackPool->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $deleted = true;
        $fallbackKeys = array();

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given.', is_object($key) ? get_class($key) : gettype($key)));
            }

            if (isset($this->values[$key])) {
                $deleted = false;
            } else {
                $fallbackKeys[] = $key;
            }
        }
        if (null === $this->values) {
            $this->initialize();
        }

        if ($fallbackKeys) {
            $deleted = $this->fallbackPool->deleteItems($fallbackKeys) && $deleted;
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        if (null === $this->values) {
            $this->initialize();
        }

        return !isset($this->values[$item->getKey()]) && $this->fallbackPool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if (null === $this->values) {
            $this->initialize();
        }

        return !isset($this->values[$item->getKey()]) && $this->fallbackPool->saveDeferred($item);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->fallbackPool->commit();
    }

    /**
     * Load the cache file.
     */
    private function initialize()
    {
        $this->values = file_exists($this->file) ? (include $this->file ?: array()) : array();
    }

    /**
     * Generator for items.
     *
     * @param array $keys
     *
     * @return \Generator
     */
    private function generateItems(array $keys)
    {
        $f = $this->createCacheItem;
        $fallbackKeys = array();

        foreach ($keys as $key) {
            if (isset($this->values[$key])) {
                $value = $this->values[$key];

                if ('N;' === $value) {
                    yield $key => $f($key, null, true);
                } elseif (is_string($value) && isset($value[2]) && ':' === $value[1]) {
                    try {
                        yield $key => $f($key, unserialize($value), true);
                    } catch (\Error $e) {
                        yield $key => $f($key, null, false);
                    } catch (\Exception $e) {
                        yield $key => $f($key, null, false);
                    }
                } else {
                    yield $key => $f($key, $value, true);
                }
            } else {
                $fallbackKeys[] = $key;
            }
        }

        if ($fallbackKeys) {
            foreach ($this->fallbackPool->getItems($fallbackKeys) as $key => $item) {
                yield $key => $item;
            }
        }
    }

    /**
     * @throws \ReflectionException When $class is not found and is required
     *
     * @internal
     */
    public static function throwOnRequiredClass($class)
    {
        $e = new \ReflectionException("Class $class does not exist");
        $trace = $e->getTrace();
        $autoloadFrame = array(
            'function' => 'spl_autoload_call',
            'args' => array($class),
        );
        $i = 1 + array_search($autoloadFrame, $trace, true);

        if (isset($trace[$i]['function']) && !isset($trace[$i]['class'])) {
            switch ($trace[$i]['function']) {
                case 'get_class_methods':
                case 'get_class_vars':
                case 'get_parent_class':
                case 'is_a':
                case 'is_subclass_of':
                case 'class_exists':
                case 'class_implements':
                case 'class_parents':
                case 'trait_exists':
                case 'defined':
                case 'interface_exists':
                case 'method_exists':
                case 'property_exists':
                case 'is_callable':
                    return;
            }
        }

        throw $e;
    }
}
