<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::latest('id')->paginate(25);

        return response()->json(UserResource::collection($users));
    }

    public function ban(User $user): JsonResponse
    {
        $user->update(['is_banned' => ! $user->is_banned]);

        return response()->json(new UserResource($user->fresh()));
    }

    public function changeRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'in:student,instructor,admin'],
        ]);

        $user->update(['role' => $request->string('role')]);

        return response()->json(new UserResource($user->fresh()));
    }
}
