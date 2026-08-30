<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PinController extends Controller
{
    /**
     * Show PIN login page.
     */
    public function showLogin(): View|RedirectResponse
    {
        $user = User::first();

        if (! $user) {
            return redirect()->route('pin.setup');
        }

        return view('auth.pin-login');
    }

    /**
     * Authenticate with PIN.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        $user = User::first();

        if (! $user || ! Hash::check($request->pin, $user->pin)) {
            return back()->withErrors(['pin' => 'PIN salah. Coba lagi.']);
        }

        $request->session()->put('authenticated', true);
        $request->session()->put('user_name', $user->name);

        return redirect()->route('dashboard');
    }

    /**
     * Show PIN setup page (first time).
     */
    public function showSetup(): View|RedirectResponse
    {
        if (User::exists()) {
            return redirect()->route('login');
        }

        return view('auth.pin-setup');
    }

    /**
     * Create user with PIN.
     */
    public function setup(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'pin' => 'required|string|size:6|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'pin' => $request->pin,
        ]);

        $request->session()->put('authenticated', true);
        $request->session()->put('user_name', $request->name);

        return redirect()->route('dashboard');
    }

    /**
     * Logout (destroy session).
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->flush();

        return redirect()->route('login');
    }
}
