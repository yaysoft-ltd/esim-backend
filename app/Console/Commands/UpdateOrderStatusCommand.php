<?php

namespace App\Console\Commands;

use App\Jobs\UpdateOrderStatus;
use App\Models\EsimOrder;
use Illuminate\Console\Command;

class UpdateOrderStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-order-status-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update order Status every check';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        EsimOrder::whereNotIn('status', ['Completed', 'failed'])->where('status','created')
            ->chunk(20, function ($orders) {
                foreach ($orders as $order) {
                    UpdateOrderStatus::dispatch($order);
                }
        });
    }
}
