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

            case 'color':

                // dd($lang);
                $color= DB::table('yy_color_lists as dt1')
                ->leftJoin('yy_color_translations as dt2', 'dt2.color_list_id', '=', 'dt1.id')
                ->where('dt1.id', $key->color_id)
                ->where('dt2.lang', $lang)
                ->select('dt1.name as color_name','dt1.code as color_code', 'dt2.name as color_translation_name')
                ->first();
                // $name_to=$from_name->city_name;
                // dd($color);
                $color_name = ($color->color_translation_name) ? $color->color_translation_name : $color->color_name;
                return $color_name;
                break;
            case 'class_list':
                $class_lists = ClassList::select('id', 'name')->get();
                foreach ($class_lists as $class_list){
                    $class_list_translate = DB::table('yy_class_lists as Class')
                        ->leftJoin('yy_class_translations as ClassT', 'Class.id', '=', 'ClassT.class_list_id')
                        ->where('Class.id', $class_list->id)
                        ->where('ClassT.lang', $lang)
                        ->select('Class.id as id', 'ClassT.name as name')->get()->toArray();
                }
                return $class_list_translate;
                break;
            default:
                # code...
                break;
        }
        
        // dd($from_to);

    }
}