<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return response(['users' => ReponseResource::collection($users), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $current_user = auth()->user();
        if ($user->id != $current_user->id) {
            response("Not Authorized", 401);
        }
        return response(['user' => new ReponseResource($user), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $current_user = auth()->user();
        if ($user->id != $current_user->id) {
            response("Not Authorized", 401);
        }
        $this->validate($request, [
            'phone' => 'phone:AUTO,US',
            'email' => 'email|required|unique:users'
        ]);

        $user->update([
            'phone' => $request->phone ?? $current_user->phone,
            'email' => $request->email ?? $current_user->email
        ]);

        return response(['user' => new ReponseResource($user), 'message' => 'Retrieved successfully'], 200);
    }

}
