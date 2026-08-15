<?php

namespace App\Http\Controllers\Modules\SmokeNGrill;

use App\Http\Controllers\Admin\RestaurantSettingController;
use Illuminate\Contracts\View\View;

class RestaurantInfoController extends RestaurantSettingController
{
    public function index(): View
    {
        return $this->edit();
    }
}
