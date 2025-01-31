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
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

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
            // $array=[];
            
            $array=json_decode ("{}");
            
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
            // $from->send(json_encode($data));
            // $user_from=User::find($user_id);

                $new_chat = [
                    'order_id' =>$order_id,
                    'user_from_id' =>$user_from_id,
                    'user_to_id' =>$user_to_id,
                    'text' => $text
                ];

                $new_chat = Chat::create($new_chat);
                
                // Send Notification start
                    $order=Order::find($data['order_id']);
                    $userSend = User::find($user_to_id);
                    // $userSender = User::find($user_from_id);
                    
                    $device = ($userSend) ? json_decode($userSend->device_id) : [];
                    $title = translate_api("You've got mail", $userSend->language);
                    $message = $text;
                    $largeIcon = ($userSend && $userSend->personalInfo && ($userSend->personalInfo->avatar != NULL)) ? asset('storage/user/' . $userSend->personalInfo->avatar) : asset('public/assets/images/default_avatar.png');
                    // $order_data = [
                    //     'order_id' => $order->id,
                    //     'start_date' => $order->start_date,
                    //     'from_name' => ($order->from) ? $order->from->name : '',
                    //     'to_name' => ($order->to) ? $order->to->name : '',
                    //     'user_from_id' => $user_from_id,
                    //     'user_to_id' => $user_to_id,
                    //     'name' => ($userSender->personalInfo) ? $userSender->personalInfo->first_name : '',
                    //     'image' => ($userSender->personalInfo) ? asset('storage/avatar/' . $userSender->personalInfo->avatar) : '',
                    // ];

                    $chat_id = $new_chat->id;

                    // $this->sendNotification($device, $user_to_id, "Chat", $title, $message, $largeIcon);
                    $this->sendNotificationChat($device, $user_to_id, $chat_id, $title, $message, $largeIcon);
                // Send Notification end

                $time=Carbon::parse($new_chat->created_at)->format('H:i');

                $is_your=true;
                // $from->send(json_encode($chat));
                if ($user_from_id==$new_chat->user_from_id) {
                    $is_your=true;
                } else {
                    $is_your=false;
                }
                

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

                $from->send(json_encode($response , JSON_UNESCAPED_UNICODE));
                // $from->send(json_encode($list , JSON_UNESCAPED_UNICODE));
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
            //    dd($data['order_id']);
            $language = $request->header('language');
            // $language = $data['language'];
            $order_id = $data['order_id'];

            $user_from_id = $data['user_from_id'];
            $toPersonalInfo = User::find($user_from_id);
            if (!isset($toPersonalInfo)) {
                return $this->error(translate_api('From user not found', $language), 400);
            }

            if ($user_from_id == null) {
                $user_from_id = auth()->id();
            }

            $user_to_id = $data['user_to_id'];

            $order = Order::find($data['order_id']);
            if (!isset($order)) {
                return $this->error(translate_api('Order not found', $language), 400);
            }
            $id = $order->id;

            $personalInfo = User::find($user_to_id);
            if (!isset($personalInfo)) {
                return $this->error(translate_api('To user not found', $language), 400);
            }
            $personalInfo = $personalInfo->personalInfo;

            if ($personalInfo && isset($personalInfo->avatar)) {
                $avatarPath = storage_path('app/public/avatar/' . $personalInfo->avatar);
                if (file_exists($avatarPath)) {
                    $personalInfo->avatar = asset('storage/avatar/' . $personalInfo->avatar);
                } else {
                    $personalInfo->avatar = null;
                }
            }


            $from_to_name = table_translate($order,'city',$language);
            $array=[];

            $chat_data = DB::table('yy_chats as dt1')
                ->select('dt1.id', 'dt1.user_from_id', 'dt1.user_to_id', 'dt1.text', 'dt1.order_id','dt1.firebase_id', 'dt1.created_at')
                ->where(function ($query) use ($data) {
                    $query->where('user_from_id', $data['user_from_id'])->where('user_to_id', $data['user_to_id'])->where('order_id', $data['order_id']);
                })
                ->orWhere(function ($query) use ($data) {
                    $query->where('user_from_id', $data['user_to_id'])
                          ->where('user_to_id', $data['user_from_id'])
                          ->where('order_id', $data['order_id']);
                })
                // ->orderBy('created_at', 'ASC')
                ->first();

        //    $chat_data = DB::table('yy_chats')
        //     ->where('user_from_id', $data['user_from_id'])
        //     ->where('user_to_id', $data['user_to_id'])
        //     ->where('order_id', $data['order_id'])->first();

            if (!$chat_data) {
                $chat_data = [
                    'user_from_id' => $user_from_id,
                    'user_to_id' => $user_to_id,
                    'order_id' => $order_id,
                ];
                $chat_data = Chat::create($chat_data);
            }
            
            //    dd($chat_data);
            if ($chat_data) {

                $list=[
                    'chat_id' => $chat_data->id ?? null,
                    'firebase_id' => $chat_data->firebase_id ?? null,
                    'name' => $personalInfo->first_name ?? null,
                    'image' => $personalInfo->avatar ?? null,
                    'order_id' => $id,
                    'start_date' => date('d.m.Y H:i', strtotime($order->start_date)),
                    'from_name' => $from_to_name['from_name'],
                    'to_name' => $from_to_name['to_name'],
                    // 'data'=>$array
                ];    

                return $this->success('success', 200, $list);
            }
            else {
                return $this->error(translate_api('offer not found', $language), 400);
            }
            
    

            // return $list;



            // if (DB::table('yy_chats')->where('order_id',$id)->exists()) {

            //     $chat_data = DB::table('yy_chats as dt1')
            //     ->select('dt1.id', 'dt1.user_from_id', 'dt1.user_to_id', 'dt1.text', 'dt1.order_id', 'dt1.created_at')
            //     ->where(function ($query) use ($data) {
            //         $query->where('user_from_id', $data['user_from_id'])->where('user_to_id', $data['user_to_id'])->where('order_id', $data['order_id']);
            //     })
            //     ->orWhere(function ($query) use ($data) {
            //         $query->where('user_from_id', $data['user_to_id'])
            //               ->where('user_to_id', $data['user_from_id'])
            //               ->where('order_id', $data['order_id']);
            //     })
            //     ->orderBy('created_at', 'ASC')
            //     ->get();
            //     dd($chat_data);
            
            //     $distinct_dates = $chat_data->pluck('created_at')->map(function ($item) {
                    
            //         return Carbon::parse($item)->format('Y-m-d'); // Format the date as 'YYYY-MM-DD'
            //     })->unique();
            //     $chat_id = $chat_data->pluck('id')->map(function ($item) {
            //         return $item; // Format the date as 'YYYY-MM-DD'
            //     })->first();
            //     // dd($distinct_dates);

            //     // foreach ($distinct_dates as $key => $value) {
            //     //     // dd($value);
            //     //     foreach ($chat_data as $key => $chat) {
            //     //         $date=Carbon::parse($chat->created_at)->format('Y-m-d');
            //     //         // dd($date);
            //     //         // if ($date==$value ) {
                        
            //     //         //     $time=Carbon::parse($chat->created_at)->format('H:i');
            //     //         //     $user_from=User::find($chat->user_from_id);
            //     //         //     $user_to=User::find($chat->user_to_id);
            //     //         //     if ($chat->user_from_id==$data['user_from_id']) {
            //     //         //         $is_your=true;
            //     //         //     } else {
            //     //         //         $is_your=false;
            //     //         //     }
                            
            //     //         //     $array[$value][]=[
            //     //         //         'is_your'=>$is_your,
            //     //         //         'text'=>$chat->text,
            //     //         //         'time'=>$time
            //     //         //     ];
            //     //         // }
                        
                        
            //     //     }
    
            //     // }

            // }

            // if (->where('user_from_id', $data['user_from_id'])->where('user_to_id', $data['user_to_id'])->where('order_id', $data['order_id'])->first()) {
                
            // }
            // else {
            //     $array=json_decode ("{}");
                
            // }

            // $list=[
            //     'chat_id'=>$chat_id ?? null,
            //     'name' => $personalInfo->first_name ?? null,
            //     'image' => $personalInfo->avatar ?? null,
            //     'order_id'=>$id,
            //     'start_date'=>$order->start_date,
            //     'from_name'=>$from_to_name['from_name'],
            //     'to_name'=>$from_to_name['to_name'],
            //     // 'data'=>$array
            // ];
    

            // // return $list;
            // return $this->success('success', 200, $list);
    
            // $from->send(json_encode($list , JSON_UNESCAPED_UNICODE));



    }

    public function sendMessage(Request $request)
    {
        $data = $request->all();
        $language = $request->header('language');
        
        $user_from_id = $data['user_from_id'];
        $fromPersonalInfo = User::find($user_from_id);
        if (!isset($fromPersonalInfo)) {
            return $this->error(translate_api('From user not found', $language), 400);
        }

        $user_to_id = $data['user_to_id'];
        $personalInfo = User::find($user_to_id);
        if (!isset($personalInfo)) {
            return $this->error(translate_api('To user not found', $language), 400);
        }

        $order_id = $data['order_id'];
        $order = Order::find($order_id);
        if (!isset($order)) {
            return $this->error(translate_api('Order not found', $language), 400);
        }

        $text = $data['text'] ?? '';

        $new_chat = [
            'order_id' => $order_id,
            'user_from_id' => $user_from_id,
            'user_to_id' => $user_to_id,
            'text' => $text
        ];

        $new_chat = Chat::create($new_chat);
            
        // Send Notification start
            $order = Order::find($data['order_id']);
            $userSend = User::find($user_to_id);
            // $userSender = User::find($user_from_id);
                    
            $device = ($userSend) ? json_decode($userSend->device_id) : [];
            $title = translate_api("You've got mail", $userSend->language);
            $message = $text;
            $largeIcon = ($userSend && $userSend->personalInfo && ($userSend->personalInfo->avatar != NULL)) ? asset('storage/user/' . $userSend->personalInfo->avatar) : '';

            $chat_id = $new_chat->id;

            $this->sendNotificationChat($device, $user_to_id, $chat_id, $title, $message, $largeIcon);
        // Send Notification end

        $time=Carbon::parse($new_chat->created_at)->format('H:i');

        $is_your=true;
        if ($new_chat->user_from_id == auth()->id()) {
            $is_your=true;
        } else {
            $is_your=false;
        }

        $list = [
            'is_your' => $is_your,
            'text' => $new_chat->text,
            'time' => $time
        ];

        return $this->success(translate_api('new chat created', $language), 200, $list);
    }

    public function update(Request $request)
    {
        $data = $request->all();
        $language = $request->header('language');
        $chat_id = (int)$data['chat_id'];
        $firebase_id = $data['firebase_id'];
       
        $chat = Chat::where('id', $chat_id)->first();
        if ($chat) {
            $chat->update([
                'firebase_id' => $firebase_id
            ]);
            return $this->success(translate_api("success", $language), 200);
        }

        return $this->error(translate_api("chat not found", $language), 400);
    }




    public function chatList(Request $request)
    {
        $language = $request->header('language');
        $chats = DB::table('yy_chats')
            ->distinct('order_id')
            ->orderBy('order_id')
            ->where('user_to_id', auth()->id())
            ->orWhere('user_from_id', auth()->id())
            ->get();

        $data=[];
        $n = 0;
        foreach ($chats as $key => $chat) {
            $order = Order::where('id',$chat->order_id)->first();
            if (isset($order)) {
                $from_to_name=table_translate($order,'city',$language);
                
                if ($chat->user_to_id==auth()->id()) {
                    $personalInfo=PersonalInfo::where('id',User::where('id',$chat->user_from_id)->first()->personal_info_id)->first();
                } else {
                    $personalInfo=PersonalInfo::where('id',User::where('id',$chat->user_to_id)->first()->personal_info_id)->first();
                }

                if (isset($personalInfo->avatar)) {
                    $avatar = storage_path('app/public/avatar/'.$personalInfo->avatar);
                    if (file_exists($avatar)) {
                        $personalInfo->avatar = asset('storage/avatar/'.$personalInfo->avatar);
                    } else {
                        $personalInfo->avatar=null;
                    }
                }

                if (DB::table('yy_send_notifications')->where('entity_type','chat')->where('entity_id',$chat->id)->exists()) {
                    $data[$n] = [
                        'id'=>$chat->id,
                        'order_id'=>$chat->order_id,
                        'firebase_id'=>strval($chat->firebase_id) ?? null,
                        'start_date'=>$order->start_date,
                        'from_name'=>$from_to_name['from_name'],
                        'to_name'=>$from_to_name['to_name'],
                        'user_from_id'=>$chat->user_from_id,
                        'user_to_id'=>$chat->user_to_id,
                        'name'=>$personalInfo->first_name,
                        'image'=>$personalInfo->avatar,
                    ];
                    $n++;
                }
            }
        }

        if (empty($data)) {
            $data = json_decode("{}");
        } else {
            $col = array_column( $data, "start_date" );
            array_multisort( $col, SORT_DESC, $data );
        }

        return response()->json([
            'data' => $data,
            'status' => true,
            'message' => 'success',
        ], 200);
    }

    public function chatInformation(Request $request)
    {
        $language = $request->header('language');
        $chat_id=$request->chat_id;

        $chat=Chat::find($chat_id);
        $order = Order::find($chat->order_id);
        //   $id=$order->id;
   
        // $personalInfo = User::find($chat->user_to_id)->personalInfo;

        // if ($personalInfo && isset($personalInfo->avatar)) {
        //     $avatarPath = storage_path('app/public/avatar/' . $personalInfo->avatar);
        //     if (file_exists($avatarPath)) {
        //         $personalInfo->avatar = asset('storage/avatar/' . $personalInfo->avatar);
        //     } else {
        //         $personalInfo->avatar = null;
        //     }
        // }

        $from_to_name=table_translate($order,'city',$language);

        $user_from_id = auth()->id();
        if ($chat->user_from_id == auth()->id()) {
            $user_to_id=$chat->user_to_id;    
        } elseif ($chat->user_to_id == auth()->id()) {
            $user_to_id = $chat->user_from_id;
            // $user_from_id=$chat->user_from_id;
        }else {
            return response()->json([
                'status' => false,
                'message' => 'There are no chats related to you',
            ], 400);
        }


        $personalInfo = User::find($user_to_id)->personalInfo;

        if ($personalInfo && isset($personalInfo->avatar)) {
            $avatarPath = storage_path('app/public/avatar/' . $personalInfo->avatar);
            if (file_exists($avatarPath)) {
                $personalInfo->avatar = asset('storage/avatar/' . $personalInfo->avatar);
            } else {
                $personalInfo->avatar = null;
            }
        }

        $list=[
            'order_id'=>$order->id,
            'start_date'=>$order->start_date,
            'from_name'=>$from_to_name['from_name'],
            'to_name'=>$from_to_name['to_name'],
            'user_from_id'=>$user_from_id,
            'user_to_id'=>$user_to_id,
            'name' => $personalInfo->first_name ?? null,
            'image' => $personalInfo->avatar ?? null,
        ];

        return response()->json([
            'data' => $list,
            'status' => true,
            'message' => 'success',
        ], 200);

    }

    public function chatNotification(Request $request)
    {
        $language = $request->header('language');
        $data = $request->all();

        $validator = Validator::make($data, [
            'firebase_id' => 'required',
            'message' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $chat = Chat::where('firebase_id', $data['firebase_id'])->first();
        if (!isset($chat)) {
            return $this->error(translate_api('Chat not found', $language), 400);
        }

        $order = Order::find($chat->order_id);
        if (!isset($order)) {
            return $this->error(translate_api('Order not found', $language), 400);
        }

        if ($chat->user_from_id == auth()->id()) {
            $user_to_id = $chat->user_to_id;    
        } elseif ($chat->user_to_id == auth()->id()) {
            $user_to_id = $chat->user_from_id;
        } else {
            return $this->error(translate_api('There are no chats related to you', $language), 400);
        }

        // Send Notification start
            $userSend = User::find($user_to_id);            
            $device = ($userSend) ? json_decode($userSend->device_id) : [];
            $title = translate_api("You've got mail", $userSend->language);
            $message = $data['message'];
            $largeIcon = ($userSend && $userSend->personalInfo && ($userSend->personalInfo->avatar != NULL)) ? asset('storage/user/' . $userSend->personalInfo->avatar) : asset('public/assets/images/default_avatar.png');

            $chat_id = $chat->id;

            $this->sendNotificationChat($device, $user_to_id, $chat_id, $title, $message, $largeIcon);
        // Send Notification end
        return $this->success(translate_api('Success', $language), 200);

        // return response()->json([
        //     'status' => true,
        //     'message' => 'success',
        // ], 200);

    }
    public function index()
    {
        return view('index');
    }




}