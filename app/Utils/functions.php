<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

function authUser($guard = WEB): User
{
    return Auth::guard($guard)->user();
}


function generateOtp($length = 6)
{
    $numbers = range(0, 9);
    shuffle($numbers);

    return implode(array_slice($numbers, 0, $length));
}

function hasEnoughBalance($amount, $guard = WEB): bool
{
    return AuthUser($guard)->wallet->balance >= $amount;
}


function getUserBalance($guard = WEB)
{
    
    return Auth::guard($guard)->user()->wallet->balance;
}

function decreaseWallet($amount, $guard = WEB)
{
    AuthUser($guard)->wallet()->decrement('balance', $amount);
}
function increaseWallet($amount, $guard = WEB)
{
    AuthUser($guard)->wallet()->increment('balance', $amount);
}

function call_api($endpoint, $params = []) {

    $parameters = '';
    if (count($params) > 0) {
        $parameters = '?'.http_build_query($params);
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://v3.football.api-sports.io/'.$endpoint.$parameters,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'x-rapidapi-key: YOUR_API_KEY_HERE'
      ),
    ));
    $response = curl_exec($curl);
    $response = json_decode($response);
    curl_close($curl);
    return $response;
}

function players_data($league, $season, $page = 1, $players_data = []) {

    $players = call_api('players', ['league' => $league, 'season' => $season, 'page' => $page]);
    $players_data = array_merge($players_data, $players->response);

    if ($players->paging->current < $players->paging->total) {

        $page = $players->paging->current + 1;
        if ($page%2 == 1) {
            sleep(1);
        }
        $players_data = players_data($league, $season, $page, $players_data);
    }
    return $players_data;
}

// Get all the teams from this competition
// $teams = call_api('teams', ['league' => 39, 'season' => 2021]);
// var_dump($teams); // To display the results if necessary

// Get all the players from this competition
// $players = players_data(39, 2021);
// var_dump($players); // To display the results if necessary