<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\Collection;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Role/Index', [
            'filters' => $request->all('search', 'trashed'),
            'roles'   => new Collection(
                Role::ofAccount()->orderBy('name')->filter($request->only('search', 'trashed'))->paginate()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Role/Form');
    }

    public function store(RoleRequest $request)
    {
        Role::create($request->validated());

        return redirect()->route('roles.index')->with('message', __choice('action_text', ['record' => 'Role', 'action' => 'created']));
    }

    public function edit(Role $role)
    {
        if ($role->name == 'Super Admin') {
            return redirect()->route('roles.index')->with('error', __('Super Admin role can not be modified.'));
        } 
        // elseif ($role->name == 'Admin Gudang') {
        //     return redirect()->route('roles.index')->with('error', __('Admin Gudang role can not be modified.'));
        // } elseif ($role->name == 'Bea Cukai') {
        //     return redirect()->route('roles.index')->with('error', __('Bea Cukai role can not be modified.'));
        // } elseif ($role->name == 'Manager') {
        //     return redirect()->route('roles.index')->with('error', __('Manager role can not be modified.'));
        // } elseif ($role->name == 'Direktur') {
        //     return redirect()->route('roles.index')->with('error', __('Direktur role can not be modified.'));
        // } else {
        //     return redirect()->route('roles.index')->with('error', __('Guest role can not be modified.'));
        // }

        return Inertia::render('Role/Form', ['edit' => $role]);
    }
    
    // public function edit(Role $role)
    // {
    //     if ($this->isProtectedRole($role)) {
    //         return redirect()->route('roles.index')
    //             ->with('error', __('This role can not be modified.'));
    //     }

    //     return Inertia::render('Role/Form', ['edit' => $role]);
    // }

    public function update(RoleRequest $request, Role $role)
    {
        if ($role->name == 'Super Admin') {
            return redirect()->route('roles.index')->with('error', __('Super Admin role can not be modified.'));
        } 
        // elseif ($role->name == 'Admin Gudang') {
        //     return redirect()->route('roles.index')->with('error', __('Admin Gudang role can not be modified.'));
        // } elseif ($role->name == 'Bea Cukai') {
        //     return redirect()->route('roles.index')->with('error', __('Bea Cukai role can not be modified.'));
        // } elseif ($role->name == 'Manager') {
        //     return redirect()->route('roles.index')->with('error', __('Manager role can not be modified.'));
        // } elseif ($role->name == 'Direktur') {
        //     return redirect()->route('roles.index')->with('error', __('Direktur role can not be modified.'));
        // } else {
        //     return redirect()->route('roles.index')->with('error', __('Guest role can not be modified.'));
        // }

        $role->update($request->validated());

        return back()->with('message', __choice('action_text', ['record' => 'Role', 'action' => 'updated']));
    }

    // public function update(RoleRequest $request, Role $role)
    // {
    //     if ($this->isProtectedRole($role)) {
    //         return redirect()->route('roles.index')
    //             ->with('error', __('This role can not be modified.'));
    //     }

    //     $role->update($request->validated());

    //     return back()->with('message', __choice('action_text', ['record' => 'Role', 'action' => 'updated']));
    // }

    public function destroy(Role $role)
    {
        if ($role->del()) {
            return redirect()->route('roles.index')->with('message', __choice('action_text', ['record' => 'Role', 'action' => 'deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function restore(Role $role)
    {
        $role->restore();

        return back()->with('message', __choice('action_text', ['record' => 'Role', 'action' => 'restored']));
    }

    public function destroyPermanently(Role $role)
    {
        if ($role->delP()) {
            return redirect()->route('roles.index')->with('message', __choice('action_text', ['record' => 'Role', 'action' => 'permanently deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function permissions(Request $request, Role $role)
    {
        $permissions = collect($request->all());
        $role->syncPermissions($permissions->except('_method')->flatten()->all());

        return back()->with('message', __('Role permissions has successfully saved.'));
    }

}
