<?php

namespace App\Http\Controllers\Modules\SmokeNGrill;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ViewWebsiteController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('landing');
    }
}
