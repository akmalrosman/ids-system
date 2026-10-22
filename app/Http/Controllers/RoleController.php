<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::withCount('users')->search($request->query('search'))->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create', ['role' => new Role(['permissions' => []])]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->roleData();

        if (Role::where('slug', $data['slug'])->exists()) {
            return back()->withInput()->withErrors(['name' => 'A role with a similar name already exists.']);
        }

        Role::create($data);

        return redirect()->route('roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', compact('role'));
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->roleData());

        return redirect()->route('roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->isSuperAdmin() || $role->users()->exists()) {
            return back()->with('error', "Role \"{$role->name}\" is in use and cannot be deleted.");
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', "Role \"{$role->name}\" deleted.");
    }
}
