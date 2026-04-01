<?php
namespace App\Services;

use Redis;

class QueueService
{
    /** @var Redis */
    private $redis;
    private $queueKey = 'score_queue';

    public function __construct()
    {
        $this->redis = new Redis();
        // Adjust host/port if needed
        $this->redis->connect('127.0.0.1', 6379);
    }

    /** Push a job payload (array) onto the queue */
    public function enqueue(array $payload): void
    {
        $this->redis->rPush($this->queueKey, json_encode($payload));
    }

    /** Pop a job payload from the queue (blocking up to $timeout seconds) */
    public function dequeue(int $timeout = 5): ?array
    {
        $result = $this->redis->blPop([$this->queueKey], $timeout);
        if ($result && isset($result[1])) {
            return json_decode($result[1], true);
        }
        return null;
    }

    /** Get current queue length */
    public function length(): int
    {
        return $this->redis->lLen($this->queueKey);
    }
}
?>
