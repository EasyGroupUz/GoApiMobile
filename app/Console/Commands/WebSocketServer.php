<?php

namespace App\Console\Commands;
use App\Http\Controllers\ChatController;
use Illuminate\Console\Command;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'websocket started ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $server=IoServer::factory(
            new HttpServer(
                new WsServer(
                    new ChatController()
                )
            ),
            8090
        );
        $server->run();
    }
}
