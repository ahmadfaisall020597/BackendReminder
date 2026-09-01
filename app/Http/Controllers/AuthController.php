<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login User
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status'  => false,
                'message' => 'Email atau Password salah',
            ], 401);
        }
        $user = User::findOrFail(Auth::id());
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logout berhasil',
        ], 200);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'user'   => $request->user(),
        ]);
    }

    public function register(Request $request)
    {
        // Hanya admin yang boleh register
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki hak akses.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:admin,user'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User berhasil dibuat',
            'data' => $user
        ], 201);
    }

    public function index(Request $request)
    {
        // Hanya admin yang boleh melihat daftar user
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki hak akses.'
            ], 403);
        }

        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $users = User::select(
            'id',
            'name',
            'email',
            'role',
            'created_at'
        )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'List user berhasil diambil',
            'data' => $users
        ]);
    }
}
