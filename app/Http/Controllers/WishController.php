<?php

namespace App\Http\Controllers;

use App\Models\Wish;
use Illuminate\Http\Request;

class WishController extends Controller
{
    public function store(Request $request)
    {
        if (!isset($request['name']) || $request['name'] == '')
            return $this->error('name parameter is missing', 400);

        if (!isset($request['email']) || $request['email'] == '')
            return $this->error('email parameter is missing', 400);

        if (!isset($request['message']) || $request['message'] == '')
            return $this->error('message parameter is missing', 400);

        $newWish = new Wish();
        $newWish->create($request->all());

        return $this->success('success', 200);
    }

    public function testNotif()
    {
        $device = ["ewv7EW_sQoGFWYzJA3h6Rs:APA91bFvYlLqw6KMWWDfCALNJmJzLGMJGuAouftn7JbE9EJnMs7SGY8xLggfAkgrCQxxpeVsgQeeKIEtctPpbLzcM1RRyJSf-tIE5Y-ckDSt5N27SC6R-cGqPdi-DkwqQ54mL6E_iW3N","dVsggUt_Qb2zxh1pygl2QG:APA91bF3nj-PIqssJz20bPySMTq8az841rlztcizGFYCjZeTk2jScrgaOZaYFirpk5GmB3mJk5q9J1bwz-fq3EbN_Q8i67gO5PBnWJtOFlN3l93SKpO6CH9V81jSRjpVAGy94iaZGO5a"];
        $title = 'Your request has been accepted';
        $message = 'Route';
        $user_id = 25;
        $entity_id = 104;

        $this->sendNotificationOrder($device, $user_id, $entity_id, $title = 'GoEasy', $message = 'Hello GoEasy');

        return 'success';
    }
}
