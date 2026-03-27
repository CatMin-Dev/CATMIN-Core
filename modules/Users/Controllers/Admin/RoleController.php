<?php

namespace Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view()->file(base_path('modules/Users/Views/roles.blade.php'), [
            'currentPage' => 'roles',
            'roles' => Role::query()
                ->withCount('users')
                ->orderBy('priority')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
