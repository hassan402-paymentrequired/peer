<?php

namespace App\Utils\Helper;

use App\Utils\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;

class HelperService
{
    use ResponseTrait;
    public static function checkBalance($amount): JsonResponse|null
    {
        $userBal = getUserBalance();
        if ($amount > $userBal) {
            return self::responseWithCustomError('insufficient balance. please deposit to continue', 400);
        }
        return null;
    }

    public static function returnWithError(string $meesage): JsonResponse
    {
        return self::responseWithCustomError($meesage, 400);
    }

    public static function getAllPlayerAndTeams(): array
    {
         $teams = self::call_api('teams', ['league' => 39, 'season' => 2023]);
        $players = self::players_data(39, 2023);
        return ['teams' => $teams, 'players' => $players];
    }

    static function call_api($endpoint, $params = [])
    {

        $parameters = '';
        if (count($params) > 0) {
            $parameters = '?' . http_build_query($params);
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://v3.football.api-sports.io/' . $endpoint . $parameters,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                  'x-rapidapi-key: ' . env('SPORT_API_KEY')
            ),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response);
        curl_close($curl);
        return $response;
    }

   static function players_data($league, $season, $page = 1, $players_data = [])
    {

        $players = self::call_api('players', ['league' => $league, 'season' => $season, 'page' => $page]);
        $players_data = array_merge($players_data, $players->response);

        if ($players->paging->current < $players->paging->total) {

            $page = $players->paging->current + 1;
            if ($page % 2 == 1) {
                sleep(1);
            }
            $players_data = self::players_data($league, $season, $page, $players_data);
        }
        return $players_data;
    }
}
