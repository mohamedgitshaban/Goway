<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CleanupDrivers extends Command
{
    protected $signature = 'drivers:cleanup';
    protected $description = 'Remove offline drivers from geohash sets';

    public function handle()
    {
        $keys = Redis::keys('geohash:drivers:*');

        foreach ($keys as $key) {
            $driverIds = Redis::smembers($key);

            foreach ($driverIds as $driverId) {
                if (! Redis::exists("driver:{$driverId}:location")) {
                    Redis::srem($key, $driverId);
                }
            }
        }

        $this->info('Cleanup done.');
    }
}
