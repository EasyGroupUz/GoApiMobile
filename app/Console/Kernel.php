<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\DirectionHistory;
use Illuminate\Support\Facades\DB;
use App\Models\City;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->everyMinute();
        
        $schedule->call(function () {
            $cities = City::orderBy('id', 'asc')->get();

            $n = 0;
            foreach ($cities as $city) {
                $subCities = City::orderBy('id', 'asc')->get();
                foreach ($subCities as $subCity) {
                    $directionHistory = DB::table('yy_direction_histories')
                        ->where(['from_lng' => $city->lng, 'from_lat' => $city->lat, 'to_lng' => $subCity->lng, 'to_lat' => $subCity->lat])
                        ->first();

                    if (!$directionHistory) {
                        $apiUrl = 'https://api.distancematrix.ai/maps/api/distancematrix/json?origins=' . $city->lng . ', ' . $city->lat . '&destinations=' . $subCity->lng . ', ' . $subCity->lat . '&key=18g47A8g11LpjgQ1jHPghl6LK2fWm';

                        $response = file_get_contents($apiUrl);

                        if ($response !== false) {
                            $data = json_decode($response, true);

                            if ($data !== null && isset($data['rows'][0]['elements'][0]['distance']['text']) && isset($data['rows'][0]['elements'][0]['duration']['text'])) {
                                $dataElements = $data['rows'][0]['elements'][0];

                                $newDirectionHistory = new DirectionHistory();
                                $newDirectionHistory->from_lng = $city->lng;
                                $newDirectionHistory->from_lat = $city->lat;
                                $newDirectionHistory->to_lng = $subCity->lng;
                                $newDirectionHistory->to_lat = $subCity->lat;
                                $newDirectionHistory->distance_text = $dataElements['distance']['text'];
                                $newDirectionHistory->distance_value = $dataElements['distance']['value'];
                                $newDirectionHistory->duration_text = $dataElements['duration']['text'];
                                $newDirectionHistory->duration_value = $dataElements['duration']['value'];
                                $newDirectionHistory->save();

                                $newDirectionHistory = new DirectionHistory();
                                $newDirectionHistory->from_lng = $subCity->lng;
                                $newDirectionHistory->from_lat = $subCity->lat;
                                $newDirectionHistory->to_lng = $city->lng;
                                $newDirectionHistory->to_lat = $city->lat;
                                $newDirectionHistory->distance_text = $dataElements['distance']['text'];
                                $newDirectionHistory->distance_value = $dataElements['distance']['value'];
                                $newDirectionHistory->duration_text = $dataElements['duration']['text'];
                                $newDirectionHistory->duration_value = $dataElements['duration']['value'];
                                $newDirectionHistory->save();

                                
                            }
                        }
                        $n++;
                    }
                }
            }

            return $n;
        })->hourly();
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
