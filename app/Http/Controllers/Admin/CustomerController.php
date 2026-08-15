<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = User::query()
            ->with(['role', 'orders'])
            ->whereHas('role', fn ($q) => $q->where('slug', Role::SLUG_USER))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }
}
