<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Language;
use App\Models\Country;
use App\Models\Status;
use App\Models\LanguageTranslation;
use App\Models\Translation;
use App\Models\StatusTranslations;



class GenerateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate translate for tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $array=["This user was deleted", 
            "It is your id. you cannot comment to yourself",
            "Order is not exist",
            "This order does not apply to you or the person you are writing to",
            "To user id is not exist",
            "This user is not exist",
            "Fail message not sent. Try again",
            "Failed your token didn't match",
            "Your sms code expired. Resend sms code",
            "Failed phone number is not correct",
            "Your sms code expired. Resend sms code",
            "Failed enter new phone number this number exists",
            "Fail message not sent. Try again",
            "No car class",
            "No car color",
            "No car list",
            "Car production date is not entered",
            "Car wheel side is not entered",
            "Car list is not exist",
            "Color is not exist",
            "Car list is not exist",
            "Color is not exist",
            "Failed car not found",
            "No media history",
            "No media history guest",
            "User and media not found",
            "User not found",
            "Media not found",
            "No personal info",
            "A token error occurred",
            "name parameter is missing",
            "email parameter is missing",
            "message parameter is missing",
            'Sorry, you must enter a date greater than or equal to today', 
            'id parameter is missing', 
            'id parameter is not correct. OrderDetail not found', 
            'id parameter is missing', 
            'id parameter is not correct. OrderDetail not found', 
            'page parameter is missing', 
            'page parameter is missing', 
            'Order table is empty', 
            'Options table is empty', 
            'id parameter is missing', 
            'This kind of order not found', 
            'id parameter is missing', 
            'car_id parameter is not correct. Car not found', 
            'id parameter is not correct. Order not found', 
            'id parameter is missing', 
            'id parameter is not correct. Order not found', 
            'id parameter is missing', 
            'id parameter is not correct. Order not found', 
            'This order has already been canceled', 
            'Order table is empty', 
            'from_id parameter is missing', 
            'from_id parameter is not correct. cities from not found', 
            'to_id parameter is missing', 
            'to_id parameter is not correct. cities to not found', 
            'Regions not found', 
            'Notification not found', 
            'Notification not found', 
            'Notification is red already',
            'Sorry, you cannot make another offer for this order',
            'Your old offer was not accepted please wait',
            'Sorry, seats are full',
            'Order detail not found',
            'Order not found',
            'Sorry, seats are full',
            'Sorry, this  order status is not Ordered',
            'sorry we only have 1 spaces available',
            'sorry we only have 2 spaces available',
            'sorry we only have 4 spaces available',
            'Offer created',
            'order_id parameter is missing',
            'order_detail_id parameter is missing',
            'Offer accepted',
            'Sorry this offer canceled',
            'Sorry we only have',
            'Spaces available',
            'Sorry, this booking has been cancelled or accepted',
            'This is offer sttus not new',
            'offer cancelled',
            'Your trip is not available with this order',
            'A passenger has canceled a reservation on your trip'
        ];



     
        // dd($array);
        $languages = Language::get();
        foreach ($languages as $language) {
            foreach ($array as $value) {
                $translation_def = Translation::where('lang_key', $value)->where('lang', $language->code)->first();
                if ($translation_def == null) {
                    $translation_def = new Translation;
                    $translation_def->lang = $language->code;
                    $translation_def->lang_key = $value;
                    $translation_def->lang_value = $value;
                    $translation_def->save();
                } else {
                    echo 'translation exists';
                }
            }
        }
        echo $translation_def->lang_key;
        
    }
}
