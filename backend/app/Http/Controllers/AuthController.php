<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // ==================== TOKEN HELPERS ====================

    protected function storeRefreshToken(User $user, string $token)
    {
        DB::table('refresh_tokens')->insert([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(14),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeAccessToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'access',
            'role' => $user->getRoleNames()->first(),
        ])->fromUser($user);
    }

    protected function makeRefreshToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'refresh',
        ])->fromUser($user);
    }

    protected function refreshCookie(string $refreshToken)
    {
        return cookie('refresh_token', $refreshToken, 60 * 24 * 14, '/', null, false, true, false, 'lax');
    }

    protected function clearRefreshCookie()
    {
        return cookie('refresh_token', '', -1, '/', null, false, true, false, 'lax');
    }

    // ==================== REGISTRATION ====================

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $user->assignRole('user');

        $accessToken  = $this->makeAccessToken($user);
        $refreshToken = $this->makeRefreshToken($user);

        return (new UserResource($user))
            ->additional([
                'token'      => $accessToken,
                'role'       => $user->getRoleNames()->first(),
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ])
            ->response()
            ->cookie($this->refreshCookie($refreshToken));
    }

    // ==================== LOGIN ====================

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'The credentials are incorrect.'], 401);
        }

        $user = auth('api')->user();
        Cache::put("user-is-online-{$user->id}", true, now()->addMinutes(5));

        $accessToken  = $this->makeAccessToken($user);
        $refreshToken = $this->makeRefreshToken($user);
        $this->storeRefreshToken($user, $refreshToken);

        return (new UserResource($user))
            ->additional([
                'token' => $accessToken,
                'role'  => $user->getRoleNames()->first(),
            ])
            ->response()
            ->cookie($this->refreshCookie($refreshToken));
    }

    // ==================== TOKEN REFRESH ====================

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return response()->json(['message' => 'No refresh token'], 401);
        }

        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return response()->json(['message' => 'Invalid token type'], 401);
            }

            $user = User::find($payload->get('sub'));
            if (!$user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            $tokenHash = hash('sha256', $refreshToken);
            $storedToken = DB::table('refresh_tokens')
                ->where('token_hash', $tokenHash)
                ->where('revoked', false)
                ->first();

            if (!$storedToken) {
                DB::table('refresh_tokens')->where('user_id', $user->id)->update(['revoked' => true]);
                return response()->json(['message' => 'Token reuse detected'], 401);
            }

            if (now()->greaterThan($storedToken->expires_at)) {
                return response()->json(['message' => 'Token expired'], 401);
            }

            DB::table('refresh_tokens')->where('id', $storedToken->id)->update(['revoked' => true]);
            $newRefreshToken = $this->makeRefreshToken($user);
            $this->storeRefreshToken($user, $newRefreshToken);

            $accessToken = $this->makeAccessToken($user);

            return (new UserResource($user))
                ->additional(['token' => $accessToken, 'role' => $user->getRoleNames()->first()])
                ->response()
                ->cookie($this->refreshCookie($newRefreshToken));

        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }
    }

    // ==================== LOGOUT ====================

    public function logout()
    {
        $user = auth()->user();

        if ($user) {
            DB::table('refresh_tokens')->where('user_id', $user->id)->update(['revoked' => true]);
        }

        auth()->logout();

        return response()->json([
            'message' => 'Logged out successfully',
            'user'    => $user ? new UserResource($user) : null
        ])->cookie($this->clearRefreshCookie());
    }

    // ==================== PROFILE ====================

    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return new UserResource($user);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    public function profile()
    {
        return $this->me(); // same output as me()
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $data = $request->validated();

        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function destroyProfile()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    // ==================== PASSWORD RESET ====================

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        $user = User::where('email', $request->email)->first();
        $user->notify(new ResetPasswordNotification($token));

        return response()->json(['message' => 'Password reset link sent to your email']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $reset = DB::table('password_resets')
            ->where('email', $data['email'])
            ->where('token', $data['token'])
            ->first();

        if (!$reset || Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }

        $user = User::where('email', $data['email'])->first();
        $user->password = bcrypt($data['password']);
        $user->save();

        DB::table('password_resets')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Password has been reset successfully']);
    }
}
