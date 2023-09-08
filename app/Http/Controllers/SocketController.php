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
        
        if ($data['type'] == 'chat_detail') {
            
            $language = $data['language'];
            // dd($request->all());
            // $chat= Chat::find($data['id']);
            // dd($chat->order_id);
            $order = Order::find($data['order_id']);
            $id=$order->id;
            // dd($order);
    
            $from_to_name=table_translate($order,'city',$language);
            $array=[];
            if (DB::table('yy_chats')->where('order_id',$id)->exists()) {
                $start_dates= DB::table('yy_chats')
                ->select(DB::raw('DISTINCT DATE(created_at) as start_date'))
                ->where('order_id',$id)
                ->get();

                foreach ($start_dates as $key => $value) {
    
                    $get_chats= DB::table('yy_chats')
                    // ->select('')
                    ->where('order_id',$id)
                    ->get();
    
                    foreach ($get_chats as $key => $chat) {
                        $date=Carbon::parse($chat->created_at)->format('Y-m-d');
                        // dd($date);
                        if ($date==$value->start_date ) {
                        
                            $time=Carbon::parse($chat->created_at)->format('H:i');
                            $user_from=User::find($chat->user_from_id);
                            $user_to=User::find($chat->user_to_id);
                            if ($chat->user_from_id==$data['user_id']) {
                                $is_your=true;
                            } else {
                                $is_your=false;
                            }
                            
                            $array[$value->start_date][]=[
                                'is_your'=>$is_your,
                                'text'=>$chat->text,
                                'time'=>$time
                            ];
                        }
                        
                        
                    }
    
                }

            }
            else {
                $array=json_decode ("{}");
                
            }
            // $from->send(json_encode($order , JSON_UNESCAPED_UNICODE));
            
           

            $list=[
                'user_id'=>$data['user_id'],
                'order_id'=>$id,
                'start_date'=>$order->start_date,
                'from_name'=>$from_to_name['from_name'],
                'to_name'=>$from_to_name['to_name'],
                'data'=>$array
            ];
    
    
            $from->send(json_encode($list , JSON_UNESCAPED_UNICODE));



        }
        if ($data['type'] == 'send_message') {

            $user_id=$data['user_id'];
            $order_id=$data['order_id'];
            $text=$data['text'];

            $user_from=User::find($user_id);
            // dd($user_from);
            // $from->send(json_encode($user_from));
    
                // $order=Order::where('id',$order_id)->first();
               
                if ($chat=Chat::where('order_id',$order_id)->first()) {
                    if ($chat->user_from_id==$user_from->id) {
                        $user_to_id=$chat->user_to_id;
                    } else {
                        $user_to_id=$chat->user_from_id;
                    }
                    
                } else {
                    $order=Order::find($order_id);
                    $user_to_id=$order->driver_id;
                //    dd($user_to_id);
                }
                
                // $from->send(json_encode($user_to_id));


                $new_chat = [
                    'order_id' => $order_id,
                    'user_from_id' => $user_from->id,
                    'user_to_id' =>$user_to_id,
                    'text' => $text
                ];
                
                $new_chat = Chat::create($new_chat);
                
                // Send Notification start
                    $userSend = User::find($user_to_id);
                    
                    $device = ($userSend) ? json_decode($userSend->device_type) : [];
                    $title = translate_api("You've got mail", $userSend->language);
                    $message = $text;
                    $largeIcon = ($userSend && $userSend->personalInfo && ($userSend->personalInfo->avatar != NULL)) ? asset('storage/user/' . $userSend->personalInfo->avatar) : '';

                    $this->sendNotification($device, $user_to_id, "Chat", $title, $message, $largeIcon);
                // Send Notification end

                $time=Carbon::parse($new_chat->created_at)->format('H:i');
                $is_your=true;

                $list=[
                    'is_your'=>$is_your,
                    'text'=>$new_chat->text,
                    'time'=>$time
                ];

                $response=[
                   'message'=>'new chat created',
                   'status'=>true,
                   'data'=>$list
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


    public function chatDetails(Request $request)
    {
        // id ->>>>> $request chat_id
        $language = $request->header('language');
        // dd($request->all());
        $id=$request->id;
        $chat= Chat::find($id);
        // dd($chat->order_id);
        $order = Order::find($chat->order_id);
        // dd($order);

        $from_to_name=table_translate($order,'city',$language);
        $start_dates= DB::table('yy_chats')
        ->select(DB::raw('DISTINCT DATE(created_at) as start_date'))
        ->where('id',$id)
        ->get();
        $data=[];
       foreach ($start_dates as $key => $value) {

            $get_chats= DB::table('yy_chats')
            // ->select('')
            ->where('id',$id)
            ->get();

            foreach ($get_chats as $key => $chat) {
                $date=Carbon::parse($chat->created_at)->format('Y-m-d');
                // dd($date);
                if ($date==$value->start_date ) {
                   
                    $time=Carbon::parse($chat->created_at)->format('H:i');

                    $data[$value->start_date][]=[
                        'from_id'=>$chat->user_from_id,
                        'to_id'=>$chat->user_to_id,
                        'text'=>$chat->text,
                        'time'=>$time
                    ];
                }
                
                
            }

       }

        $list=[
            'start_date'=>$order->start_date,
            'from_name'=>$from_to_name['from_name'],
            'to_name'=>$from_to_name['to_name'],
            'data'=>$data
        ];


        return response()->json([
            'data' => $list,
            'status' => true,
            'message' => 'fesfsef',
        ], 200);


    }

}