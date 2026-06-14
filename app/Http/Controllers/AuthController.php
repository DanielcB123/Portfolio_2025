<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Team;
use App\Models\User;
use App\Support\AuthRedirects;
use App\Support\DemoUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin(): Response
    {
        return Inertia::render('Login', $this->loginPageProps([
            'status' => session('status'),
        ]));
    }

    /**
     * Show the Incident Command login page.
     */
    public function showIncidentLogin(): Response
    {
        return Inertia::render('Incident/Login', $this->loginPageProps());
    }

    /**
     * Show the registration page.
     */
    public function showRegister(): Response
    {
        $teams = Team::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Register', [
            'teams' => $teams,
        ]);
    }

    /**
     * Show the Incident Command registration page.
     */
    public function showIncidentRegister(): Response
    {
        return Inertia::render('Incident/Register');
    }

    /**
     * Handle login request.
     */
    public function login(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $user->api_key = Str::random(60);
        $user->save();

        $redirect = AuthRedirects::resolve(
            $request->input('redirect'),
            route('dashboard')
        );

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
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'api_key'  => Str::random(60),
            ]);

            $team = null;
            $isExistingTeam = ! empty($validated['team_id']);

            if ($isExistingTeam) {
                $team = Team::findOrFail($validated['team_id']);
            } else {
                $teamName = $user->name . "'s Team";

                $team = Team::create([
                    'name'     => $teamName,
                    'slug'     => Str::slug($teamName . '-' . $user->id . '-' . Str::random(6)),
                    'owner_id' => $user->id,
                ]);
            }

            $user->team_id = $team->id;
            $user->save();

            if (method_exists($team, 'members')) {
                $team->members()->syncWithoutDetaching([
                    $user->id => ['role' => $isExistingTeam ? 'member' : 'owner'],
                ]);
            }

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

        $redirect = AuthRedirects::resolve(
            $request->input('redirect'),
            route('dashboard')
        );

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

        if (! $user->api_key) {
            $user->api_key = Str::random(40);
        }

        $user->api_key_last_used_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'api_key' => $user->api_key,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function loginPageProps(array $extra = []): array
    {
        return array_merge([
            'demoUsers'    => DemoUsers::forLogin(),
            'demoPassword' => config('demo_users.password', 'password'),
        ], $extra);
    }
}
