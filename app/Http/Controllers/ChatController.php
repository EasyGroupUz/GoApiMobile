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
use App\Models\Role;
use App\Models\Chat;
use App\Models\Client;
use Auth;

class ChatController extends Controller implements MessageComponentInterface 
{
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        $querystring = $conn->httpRequest->getUri()->getQuery();

        parse_str($querystring, $queryarray);

        if(isset($queryarray['token']))
        {
            User::where('token', $queryarray['token'])->update([ 'connection_id' => $conn->resourceId]);

            $user_id = User::select('id')->where('token', $queryarray['token'])->get();

            $data['id'] = $user_id[0]->id;

            // $data['status'] = 'Online';

            foreach($this->clients as $client)
            {
                if($client->resourceId != $conn->resourceId)
                {
                    $client->send(json_encode($data));
                }
            }

        }

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        $data = json_decode($msg);
        // dd($data);
        if($data->type == 'request_connected_chat_user')
        {
                $users = User::where('id','!=', $data->from_user_id)->get();
                $sub_data = array();
                foreach ($users as $user_data) {
                    $user_role_name=Role::where('id',$user_data->role_id)->first()->name;
        
                    $sub_data[] = array(
                        'id'    =>  $user_data->id,
                        'name'  =>  $user_data->first_name." ". $user_data->last_name,
                        'user_role_name'=>$user_role_name,
                        'first_name'=>$user_data->first_name,
                        'user_image'    =>  $user_data->avatar,
                    );

                }

                foreach($this->clients as $client)
                {
                        $send_data['response_connected_chat_user'] = true;
    
                        $send_data['data'] = $sub_data;
    
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

                $receiver_connection_id = User::select('connection_id','avatar','first_name','last_name','created_at')->where('id', $data->to_user_id)->get();

                $sender_connection_id = User::select('connection_id','avatar','first_name','last_name','created_at')->where('id', $data->from_user_id)->get();                


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

                        if($client->resourceId == $receiver_connection_id[0]->connection_id)
                        {
                            Chat::where('id', $chat_message_id)->update(['message_status' =>'Send']);

                            $send_data['message_status'] = 'Send';
                        }
                        else
                        {
                            $send_data['message_status'] = 'Not Send';
                        }
                        $send_data['message_status'] = 'Not Send';
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
        if ($data->type=='request_send_group_chat_message') {



            // $receiver_connection_id = User::select('connection_id','avatar','first_name','last_name','created_at')->where('id', $data->to_user_id)->get();

            $sender_connection = User::select('avatar','first_name','last_name','created_at')->where('id', $data->from_user_id)->first();
            
            $chat = new Chat;

            $chat->user_from_id = $data->from_user_id;
            
            $chat->text = $data->message;

            $chat->save();

            $chat_message_id = $chat->id;

            foreach($this->clients as $client)
            {

                    $send_data['chat_message_id'] = $chat_message_id;
                    
                    $send_data['group_message'] = $data->message;

                    $send_data['from_user_id'] = $data->from_user_id;

                    $send_data['sender_connection'] = $sender_connection;

                    $send_data['time'] = date('H:i', strtotime($chat->created_at));


                    $client->send(json_encode($send_data));
            }
        }
        if ($data->type=='request_connected_group_chat_user_history') {

            // $chat_data = Chat::select('id', 'user_from_id', 'user_to_id', 'text')
            //                         ->where(function($query) use ($data){
            //                             $query->where('user_from_id','!=', null)->where('user_to_id',null);
            //                         })->orderBy('id', 'ASC')->get();
            

            
                $connect_for=Constants::FOR_1;
                $connect_new=Constants::NEW_1;

                $chat_data = DB::table($connect_for.'.chat as dt1')
                ->join('icstroyc_newhouse.users as dt2', 'dt2.id', '=', 'dt1.user_from_id')
                ->where('dt1.user_to_id',null)
                ->where('dt1.deal_id',null)
                ->select('dt1.id as chat_id','dt1.user_from_id','dt1.text','dt2.first_name','dt2.last_name','dt2.avatar',DB::raw('DATE_FORMAT(dt1.created_at, "%H:%i") as time'))
                ->orderBy('dt1.id', 'ASC')->get();

                $send_data['group_chat_history'] = $chat_data;

                $receiver_connection_id = User::select('connection_id')->where('id', $data->from_user_id)->get();

                foreach($this->clients as $client)
                {
                    if($client->resourceId == $receiver_connection_id[0]->connection_id)
                    {
                        $client->send(json_encode($send_data));
                    }
                }


        }

        if($data->type == 'deal_request_send_message')
        {
            
            $sender_connection = User::select('avatar','first_name','last_name','created_at')->where('id', $data->from_user_id)->first();
            
            $chat = new Chat;

            $chat->user_from_id = $data->from_user_id;
            
            $chat->text = $data->message;

            $chat->deal_id = $data->deal_id;

            $chat->save();

            $chat_message_id = $chat->id;

            foreach($this->clients as $client)
            {

                    $send_data['chat_message_id'] = $chat_message_id;
                    
                    $send_data['deal_chat_message'] = $data->message;

                    $send_data['from_user_id'] = $data->from_user_id;

                    $send_data['from_user_id'] = $data->from_user_id;


                    $send_data['sender_connection'] = $sender_connection;

                    $send_data['time'] = date('Y-m-d H:i:s', strtotime($chat->created_at));


                    $client->send(json_encode($send_data));
            }


        }



    }

    public function onClose(ConnectionInterface $conn) {

        $this->clients->detach($conn);

        $querystring = $conn->httpRequest->getUri()->getQuery();

        parse_str($querystring, $queryarray);

        if(isset($queryarray['token']))
        {
            User::where('token', $queryarray['token'])->update([ 'connection_id' => 0, 'user_status' => 'Offline' ]);

            $user_id = User::select('id', 'updated_at')->where('token', $queryarray['token'])->get();

            $data['id'] = $user_id[0]->id;

            $updated_at = $user_id[0]->updated_at;

            // if(date('Y-m-d') == date('Y-m-d', strtotime($updated_at))) //Same Date, so display only Time
            // {
            //     $data['last_seen'] = 'Last Seen at ' . date('H:i');
            // }
            // else
            // {
            //     $data['last_seen'] = 'Last Seen at ' . date('d/m/Y H:i');
            // }

            foreach($this->clients as $client)
            {
                if($client->resourceId != $conn->resourceId)
                {
                    $client->send(json_encode($data));
                }
            }
        }

    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

}