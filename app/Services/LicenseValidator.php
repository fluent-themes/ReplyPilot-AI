
<?php namespace App\Services;
use App\Core\Env;

class LicenseValidator {
    public static function validate($code){
        if(!$code) return [false, null];
        // Simulated external API call
        if($code === 'INVALID'){
            return [false, null];
        }
        return [true, 'Awesome Product'];
    }
}
?>
