<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (
            Auth::check() &&
            Auth::user()->role === 'admin'
        ) {
            return redirect()
                ->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => [
                'required',
                'email'
            ],

            'password' => [
                'required',
                'string'
            ],
        ]);


        $credentials = [
            'email' => strtolower(
                trim($request->email)
            ),

            'password' => $request->password,

            'role' => 'admin',
        ];


        if (
            !Auth::attempt(
                $credentials,
                $request->boolean('remember')
            )
        ) {

            return back()
                ->withErrors([
                    'email' =>
                        'Invalid admin email or password.'
                ])
                ->onlyInput('email');
        }


        $request->session()
            ->regenerate();


        /*
         * Extra role protection
         */
        if (Auth::user()->role !== 'admin') {

            Auth::logout();

            return back()
                ->withErrors([
                    'email' =>
                        'You are not authorized to access the admin panel.'
                ]);
        }


        return redirect()
            ->route('admin.dashboard');
    }


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()
            ->invalidate();

        $request->session()
            ->regenerateToken();


        return redirect()
            ->route('admin.login')
            ->with(
                'success',
                'Logged out successfully.'
            );
    }
}