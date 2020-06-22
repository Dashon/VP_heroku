<?php

namespace App\Http\Controllers\API;

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
        return response(['user' => new ReponseResource($user), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $current_user = auth()->user();
        $this->validate($request, [
            'type' => ['string', 'in:' , ['admin', 'moderator', 'donator']],
            'phone' => 'phone:AUTO,US',
            'email' => 'email|required|unique:users'
        ]);

        if (request()->has('active') || request()->has('type')) {
            if ($current_user->type != 'admin' || $current_user->type != 'moderator') {
                return response(['message' => 'only an admin can edit accounts'], 405);
            }
            if ($this->type == 'admin' &&  $current_user->type != 'admin') {
                return response(['message' => 'only an admin can edit an admin account'], 405);
            }
        }
        $user->update(['status' => $request->status,
                        'type' => $request->type || $current_user->type,
                        'phone' => $request->phone || $current_user->phone,
                        'email' => $request->email || $current_user->email]);

        return response(['user' => new ReponseResource($user), 'message' => 'Retrieved successfully'], 200);
    }

        /**
     * View all user Payment sources
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function paymentSources(Request $request, User $user)
    {
        return response(['paymentSources' => ReponseResource::collection($user->paymentSources()), 'message' => 'Retrieved successfully'], 200);
    }


        /**
     * View all user Donations
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function donations(Request $request, User $user)
    {
        return response(['donations' => ReponseResource::collection($user->donations()), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\User $user
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(User $user)
    {
        $current_user = auth()->user();
        if ($current_user->type != 'admin' || $current_user->type != 'moderator') {
            return response(['message' => 'only an admin can edit accounts'], 405);
        }
        if ($this->type == 'admin' &&  $current_user->type != 'admin') {
            return response(['message' => 'only an admin can edit an admin account'], 405);
        }
        $this->active = 0;
        $this->save();

        return response(['message' => 'DeActivated']);
    }
}
