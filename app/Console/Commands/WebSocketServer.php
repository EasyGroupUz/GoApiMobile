<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Ratchet\Server\IoServer;

use Ratchet\Http\HttpServer;

use Ratchet\WebSocket\WsServer;

use React\EventLoop\Factory;

use App\Http\Controllers\SocketController;

use React\EventLoop\Factory as EventLoopFactory; // EventLoopFactory-ni import qiling



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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // EventLoop-ni o'rnatish
        $loop = EventLoopFactory::create();

        // WebSocket serverni tuzish va EventLoop-ni bering
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new SocketController()
                )
            ),
            8090,
            $loop // EventLoop-ni bering
        );

        // WebSocket serverni ishga tushirish
        $server->run();
    }
}