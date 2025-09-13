<?php
use Barqouq\Shopfront\Location\LocationServiceClient;
use Insecta\Common\ListCountriesRequest;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/BarqouqClient.php';

class LocationService
{
    /**
     * Fetch list of countries from backend and map into simple [code, name] arrays.
     * @return array<int, array{code:string,name:string}>
     */
    public static function getCountries(): array
    {
    $client = \BarqouqClient::create(LocationServiceClient::class);
        $request = new ListCountriesRequest();
        list($reply, $status) = $client->ListCountries($request)->wait();
        if (($status->code ?? 0) !== 0) {
            return [];
        }
        $countries = [];
        $list = [];
        if (is_object($reply) && method_exists($reply, 'getCountries')) {
            try { $list = \call_user_func([$reply, 'getCountries']); } catch (\Throwable $e) { $list = []; }
        }
        foreach ($list as $country) {
            $code = null; $name = null;
            try {
                if (is_object($country)) {
                    if (method_exists($country, 'getCountryCode')) { $code = $country->getCountryCode(); }
                    if (method_exists($country, 'getCountryName')) { $name = $country->getCountryName(); }
                }
            } catch (\Throwable $e) {}
            if ($code !== null && $name !== null) {
                $countries[] = [ 'code' => $code, 'name' => $name ];
            }
        }
        return $countries;
    }
}
