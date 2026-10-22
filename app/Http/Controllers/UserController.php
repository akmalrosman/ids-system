<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('role')
            ->search($request->query('search'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User, 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->userData());

        return redirect()->route('users.show', $user)->with('status', 'User created.');
    }

    public function show(User $user): View
    {
        $user->load('role');
        $flights = $user->flightsAsPilot()->withListRelations()->latest('flown_at')->limit(20)->get();

        return view('users.show', compact('user', 'flights'));
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->userData());

        return redirect()->route('users.show', $user)->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', "User {$user->name} deleted.");
    }
}
