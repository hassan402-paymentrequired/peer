<?php

namespace App\Http\Controllers\Api\Country;

use App\Http\Controllers\Controller;
use App\Jobs\FetchCountries;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{

    public function index(Request $request)
    {
        $countries = Country::query()
        ->when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->search . '%');
        })
        ->orderBy('status', 'desc')->paginate(20);

        return $this->respondWithCustomData(
            [
                'countries' => $countries
            ]
        );
    }

    public function refetch()
    {
        FetchCountries::dispatch();

        return $this->respondWithCustomData(
            [
                'message' => 'Countries refetched successfully'
            ]
        );
    }

    public function update(Request $request, Country $country)
    {
        $request->validate([
            'status' => 'required|in:1,0'
        ]);

        $country->update(['status' => $request->status]);

        return $this->respondWithCustomData(
            [
                'message' => 'Countries refetched successfully'
            ]
        );
    }

    public function activeCountry()
    {

        $countries = Country::active()->get();

        return $this->respondWithCustomData(
            [
                'countries' => $countries
            ]
        );
    }
}
