<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\DirectionHistory;
use Illuminate\Support\Facades\DB;
use App\Models\City;
use App\Models\Order;
use App\Constants;
use App\Console\Commands\WebsocketRun;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:websocket-run')->everyMinute();
        
        $schedule->call(function () 
        {
            $orders = Order::where('status_id', '!=', Constants::COMPLETED)
                ->where('status_id', '!=', Constants::CANCEL_ORDER)
                ->whereNull('deleted_at')
                ->get();

            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $distance = $this->getDistanceAndKm((($order->from) ? $order->from->lng : ''), (($order->from) ? $order->from->lat : ''), (($order->to) ? $order->to->lng : ''), (($order->to) ? $order->to->lat : ''));

                    $arrived_date = date('Y-m-d H:i:s', strtotime($order->start_date. ' +' . $distance['time']));
                    if ($arrived_date < date('Y-m-d H:i:s')) {
                        $order->status_id = Constants::COMPLETED;
                        $order->save();
                    }
                }
            }
        // })->everyMinute();
        })->twiceDaily(0, 12);

    }

    private function getDistanceAndKm($fromLng, $fromLat, $toLng, $toLat)
    {
        $directionHistory = DB::table('yy_direction_histories')
            ->where(['from_lng' => $fromLng, 'from_lat' => $fromLat, 'to_lng' => $toLng, 'to_lat' => $toLat])
            ->first();

        if ($directionHistory) {
            return [
                'new' => false,
                'km' => $directionHistory->distance_text,
                'distance_value' => $directionHistory->distance_value,
                'time' => $directionHistory->duration_text,
                'duration_value' => $directionHistory->duration_value
            ];
        } else {
            return ['new' => true, 'km' => '0', 'distance_value' => '0', 'time' => '0', 'duration_value' => '0'];
        }

    }
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
