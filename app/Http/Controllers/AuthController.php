<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin(): Response
    {
        return Inertia::render('Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Show the registration page.
     */
    public function showRegister(): Response
    {
        $teams = \App\Models\Team::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Register', [
            'teams' => $teams,
        ]);
    }


    /**
     * Handle login request.
     */
    public function login(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Generate API key
        $user = $request->user();
        $user->api_key = \Illuminate\Support\Str::random(60);
        $user->save();

        // Look for a redirect path from the form or query string
        $redirect = $request->input('redirect', route('dashboard'));

        // Use Laravel's intended mechanism, but fall back to the given redirect
        return redirect()->intended($redirect);
    }

    /**
     * Handle registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'team_id'  => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        DB::beginTransaction();

        try {
            // 1. Create user (no team yet)
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'api_key'  => Str::random(60),
            ]);

            // 2. Decide which team to use: existing from form or new personal team
            $team = null;
            $isExistingTeam = !empty($validated['team_id']);

            if ($isExistingTeam) {
                // User chose an existing team in the select
                $team = Team::findOrFail($validated['team_id']);
            } else {
                // Create a personal team for this user
                $teamName = $user->name . "'s Team";

                $team = Team::create([
                    'name'     => $teamName,
                    'slug'     => Str::slug($teamName . '-' . $user->id . '-' . Str::random(6)),
                    'owner_id' => $user->id,
                ]);
            }

            // 3. Attach user to that team
            $user->team_id = $team->id;
            $user->save();

            if (method_exists($team, 'members')) {
                $team->members()->syncWithoutDetaching([
                    $user->id => ['role' => $isExistingTeam ? 'member' : 'owner'],
                ]);
            }

            // 4. Optionally support current_team_id if you want
            if (Schema::hasColumn('users', 'current_team_id')) {
                $user->current_team_id = $team->id;
                $user->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = $request->input('redirect', route('dashboard'));

        return redirect()->intended($redirect);
    }




    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }

    public function apiToken(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error'   => 'Not authenticated.',
            ]);
        }

        // Create or refresh API key if missing
        if (! $user->api_key) {
            $user->api_key = Str::random(40);
        }

        $user->api_key_last_used_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'api_key' => $user->api_key,
            // 'expires_at' => $user->api_key_expires_at,
        ]);
    }
}
