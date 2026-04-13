<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Wallet;

class CreateWalletsForActiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --force : recreate wallets even if they exist
     */
    protected $signature = 'wallets:create-for-active {--force : recreate wallets if already exist}';

    /**
     * The console command description.
     *
     * Create wallets for every active user.
     */
    protected $description = 'Create wallets for every active user (status = active). Use --force to recreate existing wallets.';

    public function handle()
    {
        $this->info('Starting wallet creation for active users...');

        if (! class_exists(User::class)) {
            $this->error('User model not found (App\Models\User). Aborting.');
            return 1;
        }

        $hasWalletModel = class_exists(Wallet::class);
        $hasWalletsTable = Schema::hasTable('wallets');

        if (! $hasWalletModel && ! $hasWalletsTable) {
            $this->error('No Wallet model and no wallets table found. Aborting.');
            return 1;
        }

        $query = User::where('status', 'active');
        $total = $query->count();
        $this->info("Found {$total} active users.");

        $created = 0;
        $skipped = 0;
        $recreated = 0;

        DB::beginTransaction();
        try {
            $query->chunk(200, function ($users) use (&$created, &$skipped, &$recreated) {
                foreach ($users as $user) {
                    // Check existing wallet
                    $exists = false;
                    if (class_exists(Wallet::class)) {
                        $exists = Wallet::where('user_id', $user->id)->exists();
                    } elseif (Schema::hasTable('wallets')) {
                        $exists = DB::table('wallets')->where('user_id', $user->id)->exists();
                    }

                    if ($exists && ! $this->option('force')) {
                        $skipped++;
                        continue;
                    }

                    if ($exists && $this->option('force')) {
                        // remove existing record before creating new one
                        if (class_exists(Wallet::class)) {
                            Wallet::where('user_id', $user->id)->delete();
                        } else {
                            DB::table('wallets')->where('user_id', $user->id)->delete();
                        }
                        $recreated++;
                    }

                    // Insert wallet with default balance 0 (adjust columns if your schema differs)
                    if (class_exists(Wallet::class)) {
                        Wallet::create([
                            'user_id' => $user->id,
                            'balance' => 0,
                        ]);
                    } else {
                        // fallback using DB - ensure columns exist
                        $cols = Schema::getColumnListing('wallets');
                        $insert = ['user_id' => $user->id];
                        if (in_array('balance', $cols)) {
                            $insert['balance'] = 0;
                        }
                        // attempt insert
                        DB::table('wallets')->insert($insert);
                    }

                    $created++;
                }
            });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error while creating wallets: ' . $e->getMessage());
            return 1;
        }

        $this->info("Done. Created: {$created}. Recreated: {$recreated}. Skipped: {$skipped}.");
        return 0;
    }
}
