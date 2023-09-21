<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\DirectionHistory;
use App\Models\SendNotif;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      x={
 *          "logo": {
 *              "url": "https://via.placeholder.com/190x90.png?text=L5-Swagger"
 *          }
 *      },
 *      title="L5 OpenApi",
 *      description="L5 Swagger OpenApi description",
 *      @OA\Contact(
 *          email="darius@matulionis.lt"
 *      ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="https://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 *  @OA\Server(
 *     description="Pitak",
 *     url="http://127.0.0.1:8000"
 * )
 */

class Controller extends BaseController
{


    use AuthorizesRequests, ValidatesRequests;

    public function error(string $message, int $error_type, array $data = null)
    {
        return response()->json([
            'data' => $data ?? NULL,
            'status' => false,
            'message' => $message ?? 'error occured'
        ], $error_type);
    }
    public function success(string $message, int $error_type, array $data = null)
    {
        return response()->json([
            'data' => $data ?? NULL,
            'status' => true,
            'message' => $message ?? 'success'
        ], 200); // $error_type
    }

    public function validateByToken($request)
    {
        $token = $request->header()['token'];
        $user = User::where('token', $token)->first();

        return $user;
    }

    public function getDistanceAndKm($fromLng, $fromLat, $toLng, $toLat)
    {
        // return ['new' => true, 'km' => '0', 'distance_value' => '0', 'time' => '0', 'duration_value' => '0'];
        
        $directionHistory = DB::table('yy_direction_histories')
            ->where(['from_lng' => $fromLng, 'from_lat' => $fromLat, 'to_lng' => $toLng, 'to_lat' => $toLat])
            ->first();

        if ($directionHistory) {
            return [
                'new' => false,
                'km' => $directionHistory->distance_text,
                'distance_value' => $directionHistory->distance_value,
                'time' => $directionHistory->duration_text,
                'duration_value' => $directionHistory->duration_value
            ];
        } else {
            // $apiUrl = 'https://api.distancematrix.ai/maps/api/distancematrix/json?origins=' . $fromLng . ', ' . $fromLat . '&destinations=' . $toLng . ', ' . $toLat . '&key=7Q0lMsRgFBBSTgcFtBvQAMk3Qfe5O';

            // $response = file_get_contents($apiUrl);

            // if ($response !== false) {
            //     $data = json_decode($response, true);

            //     if ($data !== null && isset($data['rows'][0]['elements'][0]['distance']['text']) && isset($data['rows'][0]['elements'][0]['duration']['text'])) {
            //         $dataElements = $data['rows'][0]['elements'][0];

            //         $newDirectionHistory = new DirectionHistory();
            //         $newDirectionHistory->from_lng = $fromLng;
            //         $newDirectionHistory->from_lat = $fromLat;
            //         $newDirectionHistory->to_lng = $toLng;
            //         $newDirectionHistory->to_lat = $toLat;
            //         $newDirectionHistory->distance_text = $dataElements['distance']['text'];
            //         $newDirectionHistory->distance_value = $dataElements['distance']['value'];
            //         $newDirectionHistory->duration_text = $dataElements['duration']['text'];
            //         $newDirectionHistory->duration_value = $dataElements['duration']['value'];
            //         $newDirectionHistory->save();

            //         $newDirectionHistory = new DirectionHistory();
            //         $newDirectionHistory->from_lng = $toLng;
            //         $newDirectionHistory->from_lat = $toLat;
            //         $newDirectionHistory->to_lng = $fromLng;
            //         $newDirectionHistory->to_lat = $fromLat;
            //         $newDirectionHistory->distance_text = $dataElements['distance']['text'];
            //         $newDirectionHistory->distance_value = $dataElements['distance']['value'];
            //         $newDirectionHistory->duration_text = $dataElements['duration']['text'];
            //         $newDirectionHistory->duration_value = $dataElements['duration']['value'];
            //         $newDirectionHistory->save();

            //         return [
            //             'new' => true,
            //             'km' => $dataElements['distance']['text'],
            //             'distance_value' => $dataElements['distance']['value'],
            //             'time' => $dataElements['duration']['text'],
            //             'duration_value' => $dataElements['duration']['value']
            //         ];
            //     }
            //     return ['new' => true, 'km' => '0', 'distance_value' => '0', 'time' => '0', 'duration_value' => '0'];
            // } else {
                return ['new' => true, 'km' => '0', 'distance_value' => '0', 'time' => '0', 'duration_value' => '0'];
            // }
        }

    }


    public function sendNotificationOrder($device, $user_id, $entity_id, $title = 'GoEasy', $message = 'Hello GoEasy')
    {
        $largeIcon = 'https://cdn.vectorstock.com/i/preview-1x/10/38/avatar-man-with-special-offer-message-vector-28301038.webp';
        $action = 'order';

        // $lastSendNotif = SendNotif::orderBy('id', 'desc')->first();
        // $inc = ($lastSendNotif) ? $lastSendNotif->entity_id + 1 : 1;
        
        $newSendNotif = new SendNotif();
        $newSendNotif->user_id = $user_id;
        $newSendNotif->entity_id = $entity_id;
        $newSendNotif->entity_type = $action;
        $newSendNotif->title = $title;
        $newSendNotif->body = $message;
        $newSendNotif->largeIcon = $largeIcon;
        $newSendNotif->registration_ids = json_encode($device);
        $newSendNotif->save();

        $firebaseServerKey = 'AAAALY3M0oo:APA91bGJJDSZvBSBEiebiZ5aCI_17Z8UqJy8OjcnljqnALtl3ocdeelYGwGn9lFpqx9dj3KK8tC3zcUDa814jNAjpYB83vmTXlFs4u5diz3BAJa4YOeg7xq8m_c63xPL_LRbLUw-YZ3u'; // Replace with your Firebas>
        $fcmEndpoint = 'https://fcm.googleapis.com/fcm/send';

        $data = [
            'data' => [
                'entity_id' => $entity_id,
                'entity_type' => $action,
                'title' => $title,
                'body' => $message,
                'bigPicture' => NULL, //'https://thumbs.dreamstime.com/z/beautiful-rain-forest-ang-ka-nature-trail-doi-inthanon-national-park-thailand-36703721.jpg',
                'largeIcon' => $largeIcon,
                // 'largeIcon' => 'https://i.pinimg.com/originals/cd/87/f1/cd87f1de80c88d68812cf311b4e682e5.jpg',
                'channelKey' => 'basic_channel',
                'notificationLayout' => 'BigPicture',
                'showWhen' => true,
                'autoDismissible' => true,
                'privacy' => 'Private',
            ],
            'mutable_content' => true,
            'content_available' => true,
            'priority' => 'high',
            'click_action' => 'FLUTTER_NOTIFICVATION_CLICK',
            'registration_ids' => $device
        ];

        $headers = [
            'Authorization: key=' . $firebaseServerKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        // Handle the response, e.g., log it or return it as a JSON response
        return response()->json(['message' => 'Notification sent', 'response' => json_decode($response)]);
    }





    public function sendNotificationChat($device, $user_id, $chat_id, $title = 'GoEasy', $message = 'Hello GoEasy', $largeIcon = '')
    {
        // $largeIcon = 'https://cdn.vectorstock.com/i/1000x1000/19/45/user-avatar-icon-sign-symbol-vector-4001945.webp';
        $action = 'chat';

        // $lastSendNotif = SendNotif::orderBy('id', 'desc')->first();
        // $inc = ($lastSendNotif) ? $lastSendNotif->entity_id + 1 : 1;
        
        $newSendNotif = new SendNotif();
        $newSendNotif->user_id = $user_id;
        $newSendNotif->entity_id = $chat_id;
        $newSendNotif->entity_type = $action;
        $newSendNotif->title = $title;
        $newSendNotif->body = $message;
        $newSendNotif->largeIcon = $largeIcon;
        $newSendNotif->registration_ids = json_encode($device);
        $newSendNotif->save();

        $firebaseServerKey = 'AAAALY3M0oo:APA91bGJJDSZvBSBEiebiZ5aCI_17Z8UqJy8OjcnljqnALtl3ocdeelYGwGn9lFpqx9dj3KK8tC3zcUDa814jNAjpYB83vmTXlFs4u5diz3BAJa4YOeg7xq8m_c63xPL_LRbLUw-YZ3u'; // Replace with your Firebas>
        $fcmEndpoint = 'https://fcm.googleapis.com/fcm/send';

        $data = [
            'data' => [
                'entity_id' => $chat_id,
                'entity_type' => $action,
                'title' => $title,
                'body' => $message,
                'bigPicture' => NULL, //'https://thumbs.dreamstime.com/z/beautiful-rain-forest-ang-ka-nature-trail-doi-inthanon-national-park-thailand-36703721.jpg',
                'largeIcon' => $largeIcon,
                // 'largeIcon' => 'https://i.pinimg.com/originals/cd/87/f1/cd87f1de80c88d68812cf311b4e682e5.jpg',
                'channelKey' => 'basic_channel',
                'notificationLayout' => 'BigPicture',
                'showWhen' => true,
                'autoDismissible' => true,
                'privacy' => 'Private',
            ],
            'mutable_content' => true,
            'content_available' => true,
            'priority' => 'high',
            'click_action' => 'FLUTTER_NOTIFICVATION_CLICK',
            'registration_ids' => $device
        ];

        $headers = [
            'Authorization: key=' . $firebaseServerKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        // Handle the response, e.g., log it or return it as a JSON response
        return response()->json(['message' => 'Notification sent', 'response' => json_decode($response)]);
    }

    // public function sendNotification($device, $user_id, $action, $title = 'GoEasy', $message = 'Hello GoEasy', $largeIcon = '')
    // {
    //     if ($action == 'Offer')
    //         $largeIcon = 'https://cdn.vectorstock.com/i/preview-1x/10/38/avatar-man-with-special-offer-message-vector-28301038.webp';
    //     else if ($action == 'Chat' && $largeIcon == '')
    //         $largeIcon = 'https://cdn.vectorstock.com/i/1000x1000/19/45/user-avatar-icon-sign-symbol-vector-4001945.webp';

    //     $lastSendNotif = SendNotif::orderBy('id', 'desc')->first();
    //     $inc = ($lastSendNotif) ? $lastSendNotif->entity_id + 1 : 1;
        
    //     $newSendNotif = new SendNotif();
    //     $newSendNotif->user_id = $user_id;
    //     $newSendNotif->entity_id = $inc;
    //     $newSendNotif->entity_type = $action;
    //     $newSendNotif->title = $title;
    //     $newSendNotif->body = $message;
    //     $newSendNotif->largeIcon = $largeIcon;
    //     $newSendNotif->registration_ids = json_encode($device);
    //     $newSendNotif->save();

    //     $firebaseServerKey = 'AAAALY3M0oo:APA91bGJJDSZvBSBEiebiZ5aCI_17Z8UqJy8OjcnljqnALtl3ocdeelYGwGn9lFpqx9dj3KK8tC3zcUDa814jNAjpYB83vmTXlFs4u5diz3BAJa4YOeg7xq8m_c63xPL_LRbLUw-YZ3u'; // Replace with your Firebas>
    //     $fcmEndpoint = 'https://fcm.googleapis.com/fcm/send';

    //     $data = [
    //         'data' => [
    //             'entity_id' => $inc,
    //             'entity_type' => $action,
    //             'title' => $title,
    //             'body' => $message,
    //             'bigPicture' => NULL, //'https://thumbs.dreamstime.com/z/beautiful-rain-forest-ang-ka-nature-trail-doi-inthanon-national-park-thailand-36703721.jpg',
    //             'largeIcon' => $largeIcon,
    //             // 'largeIcon' => 'https://i.pinimg.com/originals/cd/87/f1/cd87f1de80c88d68812cf311b4e682e5.jpg',
    //             'channelKey' => 'basic_channel',
    //             'notificationLayout' => 'BigPicture',
    //             'showWhen' => true,
    //             'autoDismissible' => true,
    //             'privacy' => 'Private',
    //         ],
    //         'mutable_content' => true,
    //         'content_available' => true,
    //         'priority' => 'high',
    //         'click_action' => 'FLUTTER_NOTIFICVATION_CLICK',
    //         'registration_ids' => $device
    //     ];

    //     $headers = [
    //         'Authorization: key=' . $firebaseServerKey,
    //         'Content-Type: application/json',
    //     ];

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $fcmEndpoint);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    //     $response = curl_exec($ch);
    //     curl_close($ch);

    //     // Handle the response, e.g., log it or return it as a JSON response
    //     return response()->json(['message' => 'Notification sent', 'response' => json_decode($response)]);
    // }
}
