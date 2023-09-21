<?php

namespace App\Console\Commands;
use App\Http\Controllers\SocketController;

use Illuminate\Console\Command;

class WebsocketRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:websocket-run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        $socketController = new SocketController();
        $socketController->index();

        // Route::get('', [SocketController::class, 'index']);
        //  dd('adfaedfa');
        // return view('index');
        // $view = view('index');

        // You can return the view instance.
        // return $view;
    }
}
