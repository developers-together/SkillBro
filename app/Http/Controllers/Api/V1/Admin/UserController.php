<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $users = User::latest('id')->paginate(25);

        return UserResource::collection($users);
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Cannot ban yourself.');

        $request->validate(['ban' => ['required', 'boolean']]);

        $user->update(['is_banned' => $request->boolean('ban')]);

        return response()->json(new UserResource($user->fresh()));
    }

    public function changeRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', new Enum(UserRole::class)],
        ]);

        $user->update(['role' => $request->string('role')]);

        return response()->json(new UserResource($user->fresh()));
    }
}
