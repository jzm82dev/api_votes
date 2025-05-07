
<?php

use Illuminate\Support\Str;
use App\Models\Tournament\TournamentMatch;

if (! function_exists('show_route')) {
    function show_route($model, $resource = null)
    {
        $resource = $resource ?? plural_from_model($model);
 
        return route("{$resource}.show", $model);
    }
}
 
if (! function_exists('plural_from_model')) {
    function plural_from_model($model)
    {
        $plural = Str::plural(class_basename($model));
 
        return Str::kebab($plural);
    }
}

if (! function_exists('get_errors')) {
    function get_errors($errors)
    {
        $errorText = collect([]);
        foreach ($errors->all() as $message) {
            $errorText->push($message);
        }
        return $errorText;
    }
}

if (! function_exists('checkResultSetCorrect')) {
    function checkResultSetCorrect($result = '')
    {
        if( $result != null && $result != '' && strlen($result) == 3 && strpos( $result, '-' ) !== false){
            return true;
        }
        return false;

    }
}

if (! function_exists('checkResultSetCorrectPickleball')) {
    function checkResultSetCorrectPickleball($result = '')
    {
        if( $result != null && $result != '' && strpos( $result, '-' ) !== false){
            $result = explode('-', $result);
            $localResult = intval($result[0]);
            $visitingResult = intval($result[1]);
            if( $localResult >= 0 && $visitingResult >= 0){
                return true;
            }
        }
        return false;

    }
}

