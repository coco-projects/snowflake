<?php
    namespace Coco\snowflake\sequenceResolver;

interface SequenceResolver
{
    /**
     * The snowflake.
     *
     * @param int $currentTime current timestamp: milliseconds
     *
     * @return int
     */
    public function sequence(int $currentTime);
}
