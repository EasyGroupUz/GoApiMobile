<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Ratchet\MessageComponentInterface;

use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\DB;

use App\Models\User;

use App\Models\Chat;

use App\Models\PersonalInfo;

use App\Models\Order;

use Carbon\Carbon;

use Auth;

class SocketController extends Controller implements MessageComponentInterface
{
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {


        $data = json_decode($msg, true); // Assuming JSON data

        if($data->type == 'request_connected_chat_user')
        {
            
            $chat = Chat::find($data->id);
            $order = Order::find($chat->order_id);

            $userID = ($chat->from_user_id == $data->from_user_id) ? $order->driver_id : $chat->from_user_id;
            $personalInfo = User::find($userID)->personalInfo;

            if ($personalInfo && isset($personalInfo->avatar)) {
                $avatarPath = storage_path('app/public/avatar/' . $personalInfo->avatar);
                if (file_exists($avatarPath)) {
                    $personalInfo->avatar = asset('storage/avatar/' . $personalInfo->avatar);
                } else {
                    $personalInfo->avatar = null;
                }
            }

            $from_to_name = table_translate($order, 'city', $language);

            $list = [
                'id' => $chat->id,
                'start_date' => $order->start_date,
                'from_name' => $from_to_name['from_name'],
                'to_name' => $from_to_name['to_name'],
                'name' => $personalInfo->first_name ?? null,
                'image' => $personalInfo->avatar ?? null
            ];

            $response = [
                'message' => 'Hello from the server!',
                'received' => $list
            ];
            $from->send(json_encode($response));   
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }


}