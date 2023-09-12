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
            //  Socket spease ni ham oqiydi  masalan order va  order + spease teng emas

        $data = json_decode($msg, true); // Assuming JSON data
        
        if ($data['type'] == 'chat_detail') {
            
            $language = $data['language'];
            // $data=$request->all();
            // //    dd($data['type']);
        
            // $language = $data['language'];
            $order_id=$data['order_id'];
            $user_from_id=$data['user_from_id'];
            $user_to_id=$data['user_to_id'];

            $order=Order::find($data['order_id']);
            $id=$order->id;

            $personalInfo=User::find($user_to_id)->personalInfo;
            
            if ($personalInfo && isset($personalInfo->avatar)) {
                $avatarPath=storage_path('app/public/avatar/' . $personalInfo->avatar);
                if (file_exists($avatarPath)) {
                    $personalInfo->avatar=asset('storage/avatar/' . $personalInfo->avatar);
                } else {
                    $personalInfo->avatar=null;
                }
            }
            
            // $from->send(json_encode($personalInfo));
            
            $from_to_name=table_translate($order,'city',$language);
            $array=[];
            
            if (DB::table('yy_chats')->where('order_id',$id)->exists()) {

                $chat_data=DB::table('yy_chats as dt1')
                ->select('dt1.id', 'dt1.user_from_id', 'dt1.user_to_id', 'dt1.text', 'dt1.order_id', 'dt1.created_at')
                ->where(function ($query) use ($data) {
                    $query->where('user_from_id', $data['user_from_id'])
                          ->where('user_to_id', $data['user_to_id'])
                          ->where('order_id', $data['order_id']);
                })
                ->orWhere(function ($query) use ($data) {
                    $query->where('user_from_id', $data['user_to_id'])
                          ->where('user_to_id', $data['user_from_id'])
                          ->where('order_id', $data['order_id']);
                })
                ->orderBy('created_at', 'ASC')
                ->get();
                // dd($chat_data);
            
                $distinct_dates=$chat_data->pluck('created_at')->map(function ($item) {
                    return Carbon::parse($item)->format('Y-m-d'); // Format the date as 'YYYY-MM-DD'
                })->unique();
                // dd($distinct_dates);

                foreach ($distinct_dates as $key => $value) {
                    // dd($value);
                    foreach ($chat_data as $key => $chat) {
                        $date=Carbon::parse($chat->created_at)->format('Y-m-d');
                        // dd($date);
                        if ($date==$value) {
                        
                            $time=Carbon::parse($chat->created_at)->format('H:i');
                            $user_from=User::find($chat->user_from_id);
                            $user_to=User::find($chat->user_to_id);
                            if ($chat->user_from_id==$data['user_from_id']) {
                                $is_your=true;
                            } else {
                                $is_your=false;
                            }
                            
                            $array[$value][]=[
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

            $list=[
                'name' => $personalInfo->first_name ?? null,
                'image' => $personalInfo->avatar ?? null,
                'order_id'=>$id,
                'start_date'=>$order->start_date,
                'from_name'=>$from_to_name['from_name'],
                'to_name'=>$from_to_name['to_name'],
                'data'=>$array
            ];
    

            // return $list;
    
    
            $from->send(json_encode($list , JSON_UNESCAPED_UNICODE));



        }
        if ($data['type'] == 'send_message') {

            $user_from_id=$data['user_from_id'];
            $user_to_id=$data['user_to_id'];
            $order_id=$data['order_id'];
            $text=$data['text'];

            $user_from=User::find($user_id);

                $new_chat = [
                    'order_id' =>$order_id,
                    'user_from_id' =>$user_from->id,
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
           $data=$request->all();
            //    dd($data['type']);
        
            $language = $data['language'];
            $order_id=$data['order_id'];
            $user_from_id=$data['user_from_id'];
            $user_to_id=$data['user_to_id'];

            $order = Order::find($data['order_id']);
            $id=$order->id;

            $personalInfo = User::find($user_to_id)->personalInfo;

            if ($personalInfo && isset($personalInfo->avatar)) {
                $avatarPath = storage_path('app/public/avatar/' . $personalInfo->avatar);
                if (file_exists($avatarPath)) {
                    $personalInfo->avatar = asset('storage/avatar/' . $personalInfo->avatar);
                } else {
                    $personalInfo->avatar = null;
                }
            }


            $from_to_name=table_translate($order,'city',$language);
            $array=[];

            if (DB::table('yy_chats')->where('order_id',$id)->exists()) {

                $chat_data = DB::table('yy_chats as dt1')
                ->select('dt1.id', 'dt1.user_from_id', 'dt1.user_to_id', 'dt1.text', 'dt1.order_id', 'dt1.created_at')
                ->where(function ($query) use ($data) {
                    $query->where('user_from_id', $data['user_from_id'])
                          ->where('user_to_id', $data['user_to_id'])
                          ->where('order_id', $data['order_id']);
                })
                ->orWhere(function ($query) use ($data) {
                    $query->where('user_from_id', $data['user_to_id'])
                          ->where('user_to_id', $data['user_from_id'])
                          ->where('order_id', $data['order_id']);
                })
                ->orderBy('created_at', 'ASC')
                ->get();
                // dd($chat_data);
            
                $distinct_dates = $chat_data->pluck('created_at')->map(function ($item) {
                    return Carbon::parse($item)->format('Y-m-d'); // Format the date as 'YYYY-MM-DD'
                })->unique();
                // dd($distinct_dates);

                foreach ($distinct_dates as $key => $value) {
                    // dd($value);
                    foreach ($chat_data as $key => $chat) {
                        $date=Carbon::parse($chat->created_at)->format('Y-m-d');
                        // dd($date);
                        if ($date==$value ) {
                        
                            $time=Carbon::parse($chat->created_at)->format('H:i');
                            $user_from=User::find($chat->user_from_id);
                            $user_to=User::find($chat->user_to_id);
                            if ($chat->user_from_id==$data['user_from_id']) {
                                $is_your=true;
                            } else {
                                $is_your=false;
                            }
                            
                            $array[$value][]=[
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

            $list=[
                'name' => $personalInfo->first_name ?? null,
                'image' => $personalInfo->avatar ?? null,
                'order_id'=>$id,
                'start_date'=>$order->start_date,
                'from_name'=>$from_to_name['from_name'],
                'to_name'=>$from_to_name['to_name'],
                
                'data'=>$array
            ];
    

            return $list;
    
            // $from->send(json_encode($list , JSON_UNESCAPED_UNICODE));



    }



    public function chatList(Request $request)
    {
        $language = $request->header('language');
        // dd($request->all());

        // $id=auth()->id();
        // $order = Order::find($order_id);
        // dd($order);
        $chats= DB::table('yy_chats')
        // ->where('user_from_id', $id)
        // ->Orwhere('user_to_id', $id)
        ->distinct('order_id')
        ->orderBy('order_id')
        ->where('user_to_id', auth()->id())
        ->orWhere('user_from_id', auth()->id())
        ->get();
        // dd($chats);
         $data=[];
        foreach ($chats as $key => $chat) {
            $order = Order::where('id',$chat->order_id)->first();
            // $order = Order::find();
            
            // dd($order); 
            $from_to_name=table_translate($order,'city',$language);
            if ($chat->user_to_id==auth()->id()) {
                $personalInfo=PersonalInfo::where('id',User::where('id',$chat->user_from_id)->first()->personal_info_id)->first();
            }else{
                $personalInfo=PersonalInfo::where('id',User::where('id',$chat->user_to_id)->first()->personal_info_id)->first();
            }

            if(isset($personalInfo->avatar)){
                $avatar = storage_path('app/public/avatar/'.$personalInfo->avatar);
                if(file_exists($avatar)){
                    $personalInfo->avatar = asset('storage/avatar/'.$personalInfo->avatar);
                }
                else {
                    $personalInfo->avatar=null;
                }
            }

            $list=[
                'id'=>$chat->id,
                'order_id'=>$chat->order_id,
                'start_date'=>$order->start_date,
                'from_name'=>$from_to_name['from_name'],
                'to_name'=>$from_to_name['to_name'],
                'name'=>$personalInfo->first_name,
                'image'=>$personalInfo->avatar,

            ];
            array_push($data,$list);
        }

        // $data=[];
        
        
        // $list=[
        //     'start_date'=>$order->start_date,
        //     'from_name'=>$from_to_name['from_name'],
        //     'to_name'=>$from_to_name['to_name'],
        //     // 'data'=>$data
        // ];


        return response()->json([
            'data' => $data,
            'status' => true,
            'message' => 'success',
        ], 200);


    }



}