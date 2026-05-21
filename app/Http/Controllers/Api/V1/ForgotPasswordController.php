<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordMail;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    use ApiResponse;

    /**
     * Send a 6-digit OTP code to the user's email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::withoutGlobalScope('unid')->where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('We could not find a user with that email address.', null, 404);
        }

        // Generate 6-digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));

        // Save token to password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $otp,
                'created_at' => Carbon::now()
            ]
        );

        // Send Email
        try {
            Mail::to($user->email)->send(new ForgotPasswordMail($user->name, $otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Forgot password mail send error: ' . $e->getMessage());
            return $this->errorResponse('Failed to send verification code email. Please try again later.', null, 500);
        }

        return $this->successResponse(null, 'Verification code sent to your email address.');
    }

    /**
     * Reset the user's password using the OTP code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return $this->errorResponse('Invalid verification code.', null, 400);
        }

        // Check expiration (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $this->errorResponse('Verification code has expired.', null, 400);
        }

        // Find user and update password
        $user = User::withoutGlobalScope('unid')->where('email', $request->email)->first();
        if (!$user) {
            return $this->errorResponse('User not found.', null, 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->successResponse(null, 'Your password has been successfully reset. You can now login.');
    }
}
