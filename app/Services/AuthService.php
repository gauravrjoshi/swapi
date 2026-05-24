<?php

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\MemberWelcomeMail;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $plainPassword = $data['password'];
        $data['password'] = Hash::make($data['password']);

        // Calculate unique sequential UNID starting from 2510
        $max = User::max('unid');
        $unid = $max ? max($max + 1, 2510) : 2510;

        $data['unid'] = $unid;
        $data['is_admin'] = true;

        $user = $this->userRepository->create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Send welcome email with credentials
        try {
            Mail::to($user->email)->send(new MemberWelcomeMail($user, $plainPassword));
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email to member ' . $user->email . ': ' . $e->getMessage());
        }

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Login user.
     *
     * @param array $credentials
     * @return array
     * @throws ValidationException
     */
    public function login(array $data): array
    {
        $fcmToken = $data['fcm_token'] ?? null;
        unset($data['fcm_token']);

        if (!Auth::attempt($data)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        if ($fcmToken) {
            $user->update(['fcm_token' => $fcmToken]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
