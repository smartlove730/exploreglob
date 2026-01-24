<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function contact()
    {
        return view('pages.contact');
    }

    public function policy()
    {
        return view('pages.policy');
    }
}
