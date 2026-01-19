<?php

namespace App\Http\Controllers;

use App\Models\Country;

class CountryController extends Controller
{
    public function setCountry($code)
    {
        $country = Country::where('code', $code)->firstOrFail();
        session()->put(['country' => $country->id]);

        return redirect()->back();
    }
}
