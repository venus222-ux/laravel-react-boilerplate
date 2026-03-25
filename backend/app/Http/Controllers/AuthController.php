<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ==================== TOKEN HELPERS ====================

  protected function storeRefreshToken(User $user, string $token)
{
    DB::table('refresh_tokens')->insert([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(14),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
    /**
     * Generate an access token for the user.
     */
    protected function makeAccessToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'access',
            'role' => $user->getRoleNames()->first(),
        ])->fromUser($user);
    }

    /**
     * Generate a refresh token for the user.
     */
    protected function makeRefreshToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'refresh',
        ])->fromUser($user);
    }

    /**
     * Create a cookie for the refresh token.
     */
    protected function refreshCookie(string $refreshToken)
    {
        return cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 14, // 14 days
            '/',
            null,
            false, // secure false for localhost
            true,  // httpOnly
            false,
            'lax'
        );
    }

    /**
     * Clear the refresh token cookie.
     */
    protected function clearRefreshCookie()
    {
        return cookie(
            'refresh_token',
            '',
            -1,
            '/',
            null,
            false,
            true,
            false,
            'lax'
        );
    }

    // ==================== REGISTRATION ====================

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $user->assignRole('user');

        $accessToken  = $this->makeAccessToken($user);
        $refreshToken = $this->makeRefreshToken($user);

        return response()->json([
            'token'      => $accessToken,
            'role'       => $user->getRoleNames()->first(),
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ])->cookie($this->refreshCookie($refreshToken));
    }

    // ==================== LOGIN ====================

    /**
     * Authenticate a user and return tokens.
     */
    public function login(Request $request)
   {
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    if (!auth('api')->attempt($credentials)) {
        return response()->json([
            'message' => 'The credentials are incorrect.'
        ], 401);
    }

    $user = auth('api')->user();
    Cache::put("user-is-online-{$user->id}", true, now()->addMinutes(5));

$accessToken  = $this->makeAccessToken($user);
$refreshToken = $this->makeRefreshToken($user);

$this->storeRefreshToken($user, $refreshToken);

return response()->json([
    'token' => $accessToken,
    'role' => $user->getRoleNames()->first(),
])->cookie($this->refreshCookie($refreshToken));
   }


    // ==================== TOKEN REFRESH ====================

    /**
     * Refresh the access token using a valid refresh token from cookie.
     */
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

        // ❌ TOKEN REUSE DETECTED
        if (!$storedToken) {
            // revoke ALL tokens for safety
            DB::table('refresh_tokens')
                ->where('user_id', $user->id)
                ->update(['revoked' => true]);

            return response()->json(['message' => 'Token reuse detected'], 401);
        }

        // ❌ EXPIRED
        if (now()->greaterThan($storedToken->expires_at)) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        // ✅ ROTATE TOKEN
        DB::table('refresh_tokens')
            ->where('id', $storedToken->id)
            ->update(['revoked' => true]);

        $newRefreshToken = $this->makeRefreshToken($user);
        $this->storeRefreshToken($user, $newRefreshToken);

        $accessToken = $this->makeAccessToken($user);

        return response()->json([
            'token' => $accessToken,
            'role' => $user->getRoleNames()->first(),
        ])->cookie($this->refreshCookie($newRefreshToken));

     } catch (\Exception $e) {
        return response()->json(['message' => 'Invalid refresh token'], 401);
     }
   }
    // ==================== LOGOUT ====================

    /**
     * Log out the authenticated user.
     */

    public function logout()
{
    $user = auth()->user();

    if ($user) {
        DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->update(['revoked' => true]);
    }

    auth()->logout();

    return response()->json([
        'message' => 'Logged out successfully',
    ])->cookie($this->clearRefreshCookie());
}


    // ==================== PROFILE MANAGEMENT ====================

    /**
     * Get the authenticated user's profile.
     */
    public function profile()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return response()->json([
                'name'       => $user->name,
                'email'      => $user->email,
                'created_at' => $user->created_at,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
        ]);

        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }

        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

    /**
     * Delete the authenticated user's account.
     */
    public function destroyProfile()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    // ==================== PASSWORD RESET ====================

    /**
     * Send a password reset link to the user's email.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()->toDateTimeString()]
        );

        $user = User::where('email', $request->email)->first();
        $user->notify(new ResetPasswordNotification($token));

        return response()->json(['message' => 'Password reset link sent to your email']);
    }

    /**
     * Reset the user's password using a valid token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        $reset = DB::table('password_resets')
          ->where('email', $request->email)
          ->where('token', $request->token)
          ->first();

        if (!$reset || Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }



        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully']);
    }

    // ==================== CURRENT USER (ALIAS) ====================

    /**
     * Return the authenticated user's full data.
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return response()->json($user);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }
}
