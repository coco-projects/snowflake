<?php

    namespace Coco\snowflake\sequenceResolver;

    use Illuminate\Contracts\Cache\Repository;

class LaravelSequenceResolver implements SequenceResolver
{
    /**
     * The laravel cache instance.
     *
     * @var Repository
     */
    protected $cache;

    /**
     * The cache prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Init resolve instance, must connectioned.
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     *  {@inheritdoc}
     */
    public function sequence(int $currentTime)
    {
        $key = $this->prefix . $currentTime;

        if ($this->cache->add($key, 1, 10)) {
            return 0;
        }

        return $this->cache->increment($key, 1);
    }

    /**
     * Set cache prefix.
     */
    public function setCachePrefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }
}
