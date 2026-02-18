<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function show(User $user) 
    {
     $get_user=User::findOrFail($user->id);
        return response()->json($get_user);
    }
    public function update(User $user)
    {
        $user->update(request()->all());
        return response()->json('User updated successfully');
    }
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json('User deleted successfully');
    }
}
