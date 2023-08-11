<?php

use App\Models\Translation;
use App\Models\Language;
// use Modules\ForTheBuilder\Entities\Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;



if (!function_exists('default_language')) {
    function default_language()
    {
        return env("DEFAULT_LANGUAGE", 'ru');
    }
}
if (!function_exists('translate_api')) {
    function translate_api($key, $lang = null)
    {
        
        if ($lang === null) {
            $lang = App::getLocale();
        }
        // dd($lang);

            $translate = Translation::where('lang_key', $key)
                ->where('lang', $lang)
                ->first();
                // dd($translate);
            if ($translate === null){
                // dd($translate);
                foreach (Language::all() as $language) {
                    if(!Translation::where('lang_key', $key)->where('lang', $language->code)->exists()){
                        Translation::create([
                            'lang'=>$language->code,
                            'lang_key'=> $key,
                            'lang_value'=>$key
                        ]);
                    }
                }
                // dd($translate);
                $data = $key;
            }else{
                $data = $translate->lang_value;
            }

            return $data;
        // };

        // return tkram(Translation::class, $app, $function);
    }
}


if (!function_exists('table_translate')) {
    function table_translate($key,$type, $lang)
    {   
        switch ($type) {
            case 'city':
                

                $from_name = DB::table('yy_cities as dt1')
                ->leftJoin('yy_city_translations as dt2', 'dt2.city_id', '=', 'dt1.id')
                ->where('dt1.id', $key->from_id)
                ->orWhere('dt2.lang', $lang)
                ->select('dt1.name as city_name', 'dt2.name as city_translation_name')
                ->first();
                // $name_from=$from_name->city_name;
                $name_from = ($from_name->city_translation_name) ? $from_name->city_translation_name : $from_name->city_name;
                
        
                $from_name = DB::table('yy_cities as dt1')
                ->leftJoin('yy_city_translations as dt2', 'dt2.city_id', '=', 'dt1.id')
                ->where('dt1.id', $key->to_id)
                ->orWhere('dt2.lang', $lang)
                ->select('dt1.name as city_name', 'dt2.name as city_translation_name')
                ->first();
                $name_to=$from_name->city_name;
                $name_to = ($from_name->city_translation_name) ? $from_name->city_translation_name : $from_name->city_name;
        
                $from_to=[
                    'from_name'=>$name_from,
                    'to_name'=>$name_to,
                ];

                return $from_to;
                break;

            case 'country':
                
                return 'dadwad';
                break;
            
            default:
                # code...
                break;
        }
        
        // dd($from_to);

    }
}