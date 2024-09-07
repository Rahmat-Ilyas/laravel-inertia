<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::when(request()->q, function ($roles) {
            $roles = $roles->where('name', 'like', '%' . request()->q . '%');
        })->with('permissions')->latest()->paginate(5);
        
        return Inertia::render('Apps/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $permissions = Permission::all();
        return Inertia::render('Apps/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function edit($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::all();
        return Inertia::render('Apps/Roles/Edit', [
            'role'          => $role,
            'permissions'   => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required',
            'permissions'   => 'required',
        ]);
        
        $role = Role::create(['name' => $request->name]);
        $role->givePermissionTo($request->permissions);

        return redirect()->route('apps.roles.index');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'          => 'required',
            'permissions'   => 'required',
        ]);
        
        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);
        
        return redirect()->route('apps.roles.index');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        
        return redirect()->route('apps.roles.index');
    }
}
