<?php

namespace App\Http\Controllers\Api\Country;

use App\Http\Controllers\Controller;
use App\Jobs\FetchCounties;
use App\Models\Country;

class CountryController extends Controller
{

    public function index()
    {
        $countries = Country::all();

        return $this->respondWithCustomData(
            [
                'countries' => $countries
            ]
        );
    }

    public function refetch()
    {
        FetchCounties::dispatch();

        return $this->respondWithCustomData(
            [
                'message' => 'Countries refetched successfully'
            ]
        );
    }
}
