<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\LoginUser;

class AuthController extends Controller
{
    // Tampilkan halaman login
    public function showLogin()
    {
        // Jika sudah login, langsung ke index
        if (session('logged_in')) {
            return redirect()->route('kunjungan.index');
        }

        return view('login');
    }

    // Proses login
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
        ]);

        $user = LoginUser::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()
                ->withInput(['username' => $request->username])
                ->withErrors(['login' => 'Username atau password salah.']);
        }

        session([
            'logged_in' => true,
            'username'  => $user->username,
        ]);

        return redirect()->route('kunjungan.index');
    }

    // Logout
    public function logout()
    {
        session()->flush();
        return redirect()->route('login');
    }
}
