<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
// use Modules\ForTheBuilder\Entities\Constants;
use Illuminate\Support\Facades\DB;
use App\Models\User;
// use App\Models\Role;
use App\Models\Chat;
use App\Models\PersonalInfo;
// use App\Models\User;
use App\Models\Order;
// use App\Models\Client;
use Auth;
use Carbon\Carbon;


class ChatController extends Controller implements MessageComponentInterface 
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

        $data = json_decode($msg);
        // dd($data);
        // from_user_id
        // order_id
        // chat_id


        if($data['type'] == 'request_connected_chat_user')
        {
            $chat = Chat::find($data['id']);
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
                
    

                foreach($this->clients as $client)
                {
                        $send_data['response_connected_chat_user'] = true;
    
                        $send_data['data'] = $list;
    
                        $client->send(json_encode($send_data));
                }
        }
        if($data->type == 'request_send_message')
        {
                //save chat message in mysql

                $chat = new Chat;

                $chat->user_from_id = $data->from_user_id;

                $chat->user_to_id = $data->to_user_id;

                $chat->text = $data->message;

                $chat->save();

                $chat_message_id = $chat->id;


                $receiver_connection_id = DB::table('yy_users as dt1')
                    ->Leftjoin('yy_personal_infos as dt2', 'dt2.id', '=', 'dt1.personal_info_id')
                    ->where('dt1.id',$data->to_user_id)
                    ->select('dt2.avatar ','dt2.last_name', 'dt1.first_name','dt2.created_at',)
                    ->get();
                // $receiver_connection_id = User::select('avatar','first_name','last_name','created_at')->where('id', $data->to_user_id)->get();
                

                $sender_connection_id = DB::table('yy_users as dt1')
                ->Leftjoin('yy_personal_infos as dt2', 'dt2.id', '=', 'dt1.personal_info_id')
                ->where('dt1.id',$data->from_user_id)
                ->select('dt2.avatar ','dt2.last_name', 'dt1.first_name','dt2.created_at',)
                ->get();

                // $sender_connection_id = User::select(,'avatar','first_name','last_name','created_at')->where('id', $data->from_user_id)->get();                


                // if(date('Y-m-d') == date('Y-m-d', strtotime($user_data->updated_at)))
                // {
                //     $last_seen = 'Last Seen At ' . date('H:i', strtotime($user_data->updated_at));
                // }
                // else
                // {
                //     $last_seen = 'Last Seen At ' . date('d/m/Y H:i', strtotime($user_data->updated_at));
                // }


                foreach($this->clients as $client)
                {
                    if($client->resourceId == $receiver_connection_id[0]->connection_id || $client->resourceId == $sender_connection_id[0]->connection_id)
                    {
                        $send_data['chat_message_id'] = $chat_message_id;
                        
                        $send_data['message'] = $data->message;

                        $send_data['from_user_id'] = $data->from_user_id;

                        $send_data['to_user_id'] = $data->to_user_id;

                        $send_data['time']=date('H:i', strtotime($chat->created_at));

                        // if($client->resourceId == $receiver_connection_id[0]->connection_id)
                        // {
                        //     Chat::where('id', $chat_message_id)->update(['message_status' =>'Send']);

                        //     $send_data['message_status'] = 'Send';
                        // }
                        // else
                        // {
                        //     $send_data['message_status'] = 'Not Send';
                        // }
                        // $send_data['message_status'] = 'Not Send';
                        $send_data['receiver_connection']=$receiver_connection_id;
                        $send_data['sender_connection']=$sender_connection_id;
                        $client->send(json_encode($send_data));
                    }
                }
        }
        if($data->type == 'request_chat_history')
        {




            // DB::table('customers as cust')
            //  ->where('cust.id',$id)
            //  ->select(DB::raw('DATE_FORMAT(cust.cust_dob, "%d-%b-%Y") as formatted_dob'))
            //  ->first();


                $connect_for=Constants::FOR_1;
                $connect_new=Constants::NEW_1;
                $chat_data = DB::table($connect_for.'.chat as dt1')
                    ->select('dt1.id', 'dt1.user_from_id', 'dt1.user_to_id', 'dt1.text', 'dt1.message_status',DB::raw('DATE_FORMAT(dt1.created_at, "%H:%i") as time'))
                                    ->where(function($query) use ($data){
                                        $query->where('user_from_id', $data->from_user_id)->where('user_to_id', $data->to_user_id);
                                    })
                                    ->orWhere(function($query) use ($data){
                                        $query->where('user_from_id', $data->to_user_id)->where('user_to_id', $data->from_user_id);
                                    })->orderBy('id', 'ASC')->get();
                


                $receiver_connection = User::select('avatar','first_name','last_name')->where('id', $data->to_user_id)->first();

                $sender_connection = User::select('avatar','first_name','last_name')->where('id', $data->from_user_id)->first();

                /*
                SELECT id, from_user_id, to_user_id, chat_message, message status 
                FROM chats 
                WHERE (from_user_id = $data->from_user_id AND to_user_id = $data->to_user_id) 
                OR (from_user_id = $data->to_user_id AND to_user_id = $data->from_user_id)
                ORDER BY id ASC
                */

                $send_data['chat_history'] = $chat_data;
                $send_data['receiver_connection'] = $receiver_connection;
                $send_data['sender_connection'] = $sender_connection;



                $receiver_connection_id = User::select('connection_id')->where('id', $data->from_user_id)->get();

                foreach($this->clients as $client)
                {
                    if($client->resourceId == $receiver_connection_id[0]->connection_id)
                    {
                        $client->send(json_encode($send_data));
                    }
                }

        }
        



    }

    public function onClose(ConnectionInterface $conn) {

        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";

    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }


    public function chatDetails(Request $request)
    {
        $language = $request->header('language');
        // dd($request->all());

        $order_id=$request->order_id;
        $order = Order::find($order_id);
        // dd($order);

        $from_to_name=table_translate($order,'city',$language);
        $start_dates= DB::table('yy_chats')
        ->select(DB::raw('DISTINCT DATE(created_at) as start_date'))
        ->where('order_id',$order_id)
        ->get();
        $data=[];
       foreach ($start_dates as $key => $value) {

            $get_chats= DB::table('yy_chats')
            // ->select('')
            ->where('order_id',$order_id)
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
        ->get();
        // dd($chats);
         $data=[];
        foreach ($chats as $key => $chat) {
            $order = Order::where('id',$chat->order_id)->first();
            // $order = Order::find();
            
            // dd($order); 
            $from_to_name=table_translate($order,'city',$language);
            $personalInfo=PersonalInfo::where('id',User::where('id',$order->driver_id)->first()->personal_info_id)->first();

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