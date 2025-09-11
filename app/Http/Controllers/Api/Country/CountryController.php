<?php

namespace App\Http\Controllers\Api\Country;

use App\Http\Controllers\Controller;
use App\Jobs\fetchCounties;
use App\Models\Country;
use Illuminate\Support\Facades\Artisan;

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
        fetchCounties::dispatch();

        return $this->respondWithCustomData(
            [
                'message' => 'Countries refetched successfully'
            ]
        );
    }
}
