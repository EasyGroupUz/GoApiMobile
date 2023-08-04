<?php

use App\Models\Translation;
use App\Models\Language;
// use Modules\ForTheBuilder\Entities\Language;
use Illuminate\Support\Facades\App;


if (!function_exists('default_language')) {
    function default_language()
    {
        return env("DEFAULT_LANGUAGE", 'ru');
    }
}
if (!function_exists('translate_api')) {
    function translate_api($key, $lang = null,)
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