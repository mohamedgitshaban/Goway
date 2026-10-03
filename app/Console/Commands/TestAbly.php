<?php

namespace App\Console\Commands;

use Illuminate\Broadcasting\Broadcasters\AblyBroadcaster;
use Illuminate\Broadcasting\Channel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;

class TestAbly extends Command
{
    protected $signature = 'ably:test
                            {--channel=ably-test : Public channel to publish on (e.g. driver.requests.9 to reach a real app)}
                            {--event=ably.test : Event name to publish}';

    protected $description = 'Check that the server can authenticate with Ably, publish a broadcast and read it back';

    public function handle(): int
    {
        // 1) Configuration
        $default = config('broadcasting.default');
        $key = (string) config('broadcasting.connections.ably.key');

        $this->line("Default broadcast driver: <info>{$default}</info>");
        if ($default !== 'ably') {
            $this->warn('BROADCAST_DRIVER is not "ably" — app events are NOT sent to Ably (run `php artisan config:clear` if you just changed .env).');
        }
        if ($key === '' || ! str_contains($key, ':')) {
            $this->error('ABLY_KEY is missing or invalid (expected "appId.keyId:secret").');
            return self::FAILURE;
        }
        $this->line('ABLY_KEY: <info>'.Str::before($key, ':').':****</info>');

        $broadcaster = Broadcast::connection('ably');
        if (! $broadcaster instanceof AblyBroadcaster) {
            $this->error('The "ably" connection is not an AblyBroadcaster ('.get_class($broadcaster).').');
            return self::FAILURE;
        }
        $ably = $broadcaster->getAbly();

        // 2) Connectivity + authentication
        try {
            $serverTime = $ably->time();
            $this->info('✔ Connected to Ably (server time: '.date('Y-m-d H:i:s', (int) ($serverTime / 1000)).' UTC)');
            $ably->stats(['limit' => 1]); // requires a valid key
            $this->info('✔ API key authenticated');
        } catch (\Throwable $e) {
            $this->error('✘ Cannot reach/authenticate with Ably: '.$e->getMessage());
            return self::FAILURE;
        }

        // 3) Publish through the same broadcaster the app events use
        $channel = $this->option('channel');
        $event = $this->option('event');
        $testId = (string) Str::uuid();
        $payload = [
            'test_id' => $testId,
            'message' => 'Ably test from '.config('app.name').' ('.gethostname().')',
            'sent_at' => now()->toISOString(),
        ];

        try {
            $broadcaster->broadcast([new Channel($channel)], $event, $payload);
            $this->info("✔ Published event \"{$event}\" on channel \"public:{$channel}\" (test_id {$testId})");
        } catch (\Throwable $e) {
            $this->error('✘ Publish failed: '.$e->getMessage());
            return self::FAILURE;
        }

        // 4) Read it back from channel history to confirm Ably received it
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $history = $ably->channels->get("public:{$channel}")->history(['limit' => 20]);
                foreach ($history->items as $message) {
                    if (data_get($message->data, 'test_id') === $testId) {
                        $this->info('✔ Message found in channel history — Ably is working.');
                        $this->line('Clients subscribed to "public:'.$channel.'" should have received: '.json_encode($payload));
                        return self::SUCCESS;
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("History check failed (attempt {$attempt}): ".$e->getMessage());
            }
            sleep(1);
        }

        $this->warn('Published without error, but the message was not found in channel history (history may be disabled/limited on this Ably app).');
        return self::SUCCESS;
    }
}
