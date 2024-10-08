<?php

    namespace Coco\snowflake;

    use Coco\snowflake\sequenceResolver\RandomSequenceResolver;
    use Coco\snowflake\sequenceResolver\SequenceResolver;
    use Exception;

class Snowflake
{
    public const MAX_TIMESTAMP_LENGTH = 41;

    public const MAX_DATACENTER_LENGTH = 5;

    public const MAX_WORKID_LENGTH = 5;

    public const MAX_SEQUENCE_LENGTH = 12;

    public const MAX_SEQUENCE_SIZE = (-1 ^ (-1 << self::MAX_SEQUENCE_LENGTH));

    public const MAX_FIRST_LENGTH = 1;

    /**
     * The data center id.
     */
    protected ?int $datacenter;

    /**
     * The worker id.
     */
    protected ?int $workerid = null;

    /**
     * The Sequence Resolver instance.
     *
     */
    protected ?SequenceResolver $sequence = null;

    /**
     * The start timestamp.
     *
     */
    protected ?int $startTime = null;

    /**
     * Default sequence resolver.
     *
     */
    protected ?SequenceResolver $defaultSequenceResolver = null;

    /**
     * Build Snowflake Instance.
     *
     * @param int|null $datacenter
     * @param int|null $workerid
     */
    public function __construct(int $datacenter = null, int $workerid = null)
    {
        $maxDataCenter = -1 ^ (-1 << self::MAX_DATACENTER_LENGTH);
        $maxWorkId     = -1 ^ (-1 << self::MAX_WORKID_LENGTH);

        // If not set datacenter or workid, we will set a default value to use.
        $this->datacenter = $datacenter > $maxDataCenter || $datacenter < 0 ? mt_rand(0, 31) : $datacenter;
        $this->workerid   = $workerid > $maxWorkId || $workerid < 0 ? mt_rand(0, 31) : $workerid;
    }

    /**
     * Get snowflake id.
     *
     * @return string
     */
    public function id()
    {
        $currentTime = $this->getCurrentMillisecond();
        while (($sequence = $this->callResolver($currentTime)) > (-1 ^ (-1 << self::MAX_SEQUENCE_LENGTH))) {
            usleep(1);
            $currentTime = $this->getCurrentMillisecond();
        }

        $workerLeftMoveLength     = self::MAX_SEQUENCE_LENGTH;
        $datacenterLeftMoveLength = self::MAX_WORKID_LENGTH + $workerLeftMoveLength;
        $timestampLeftMoveLength  = self::MAX_DATACENTER_LENGTH + $datacenterLeftMoveLength;

        return (string)((($currentTime - $this->getStartTimeStamp()) << $timestampLeftMoveLength) | ($this->datacenter << $datacenterLeftMoveLength) | ($this->workerid << $workerLeftMoveLength) | ($sequence));
    }

    /**
     * Parse snowflake id.
     */
    public function parseId(string $id, bool $transform = false): array
    {
        $id = decbin($id);

        $data = [
            'timestamp'  => substr($id, 0, -22),
            'sequence'   => substr($id, -12),
            'workerid'   => substr($id, -17, 5),
            'datacenter' => substr($id, -22, 5),
        ];

        return $transform ? array_map(function ($value) {
            return bindec($value);
        }, $data) : $data;
    }

    /**
     * Get current millisecond time.
     *
     * @return int
     * @deprecated the method name is wrong, use getCurrentMillisecond instead, will be removed in next major
     *             version.
     *
     * @codeCoverageIgnore
     *
     */
    public function getCurrentMicrotime(): int
    {
        return floor(microtime(true) * 1000) | 0;
    }

    /**
     * Get current millisecond time.
     *
     * @return int
     */
    public function getCurrentMillisecond(): int
    {
        return floor(microtime(true) * 1000) | 0;
    }

    /**
     * Set start time (millisecond).
     *
     * @throws Exception
     */
    public function setStartTimeStamp(int $millisecond): static
    {
        $missTime = $this->getCurrentMillisecond() - $millisecond;

        if ($missTime < 0) {
            throw new Exception('The start time cannot be greater than the current time');
        }

        $maxTimeDiff = -1 ^ (-1 << self::MAX_TIMESTAMP_LENGTH);

        if ($missTime > $maxTimeDiff) {
            throw new Exception(sprintf('The current microtime - starttime is not allowed to exceed -1 ^ (-1 << %d), You can reset the start time to fix this', self::MAX_TIMESTAMP_LENGTH));
        }

        $this->startTime = $millisecond;

        return $this;
    }

    /**
     * Get start timestamp (millisecond), If not set default to 2019-08-08 08:08:08.
     *
     * @return float|int|null
     */
    public function getStartTimeStamp(): float|int|null
    {
        if (!is_null($this->startTime)) {
            return $this->startTime;
        }

        // We set a default start time if you not set.
        $defaultTime = '2016-12-30';

        return strtotime($defaultTime) * 1000;
    }

    /**
     * Set Sequence Resolver.
     *
     * @param callable|SequenceResolver $sequence
     */
    public function setSequenceResolver($sequence): static
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get Sequence Resolver.
     *
     * @return SequenceResolver|null
     */
    public function getSequenceResolver()
    {
        return $this->sequence;
    }

    /**
     * Get Default Sequence Resolver.
     *
     * @return SequenceResolver
     */
    public function getDefaultSequenceResolver(): SequenceResolver
    {
        return $this->defaultSequenceResolver ? : $this->defaultSequenceResolver = new RandomSequenceResolver();
    }

    /**
     * Call resolver.
     *
     * @param mixed $currentTime
     *
     * @return int
     */
    protected function callResolver(mixed $currentTime): int
    {
        $resolver = $this->getSequenceResolver();

        if (is_callable($resolver)) {
            return $resolver($currentTime);
        }

        return !($resolver instanceof SequenceResolver) ? $this->getDefaultSequenceResolver()
            ->sequence($currentTime) : $resolver->sequence($currentTime);
    }
}
