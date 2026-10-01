<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeTaskStatusRequest;
use App\Services\TaskStatusService;
use App\Models\Task;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');

        if (! auth()->attempt($credentials)) {
            return back()
                ->with('error', 'Invalid credentials or inactive account.')
                ->withInput();
        }

        $user = auth()->user();

        if (! $user->is_active) {
            auth()->logout();

            return back()
                ->with('error', 'Your account is inactive.');
        }

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        auth()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function me()
    {
        return response()->json(auth()->user());
    }
}