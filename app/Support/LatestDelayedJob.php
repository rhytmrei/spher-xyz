<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class LatestDelayedJob
{
    public static function dispatch(ShouldQueue $job, Carbon $delay, string $unique_key = '', string $queue = 'default'): void
    {
        $jobId = app(Dispatcher::class)
            ->dispatch(
                $job->onQueue($queue)->delay($delay)
            );

        $prefix = static::getPrefix();
        $key = static::getKey($jobId, $queue, $unique_key);
        $keys = Redis::keys(static::getKey('*', $queue, $unique_key));

        if (count($keys) > 0) {
            Redis::del(array_map(function ($k) use ($prefix) {
                return str_replace($prefix, '', $k);
            }, $keys));
        }

        Redis::set($key, now()->format('Y-m-d H:i:s'));
    }

    public static function canHandle(Job $job, string $unique_key): bool
    {
        $key = static::getKey(
            $job->getJobId(),
            $job->getQueue(),
            $unique_key
        );

        if (Redis::exists($key)) {
            Redis::del($key);

            return true;
        }

        return false;
    }

    protected static function getKey($jobId, $queue = 'default', $unique_key = '*')
    {
        return "delayed:{$queue}:{$jobId}:{$unique_key}";
    }

    protected static function getPrefix()
    {
        return config('database.redis.options.prefix');
    }
}
