<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordMail;
use Carbon\Carbon;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_otp_succeeds_for_registered_user()
    {
        Mail::fake();

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'unid' => 1234,
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Verification code sent to your email address.',
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'john@example.com',
        ]);

        Mail::assertSent(ForgotPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo('john@example.com') && $mail->name === 'John Doe';
        });
    }

    public function test_request_otp_fails_for_non_existent_email()
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'We could not find a user with that email address.',
        ]);

        Mail::assertNothingSent();
    }

    public function test_reset_password_succeeds_with_valid_otp()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('old_password'),
            'unid' => 1234,
        ]);

        // Insert OTP manually
        DB::table('password_reset_tokens')->insert([
            'email' => 'john@example.com',
            'token' => '123456',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => '123456',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Your password has been successfully reset. You can now login.',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'john@example.com',
        ]);

        // Refresh user and verify new password works
        $user->refresh();
        $this->assertTrue(Hash::check('new_password123', $user->password));
    }

    public function test_reset_password_fails_with_invalid_otp()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('old_password'),
            'unid' => 1234,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'john@example.com',
            'token' => '123456',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => '999999', // wrong OTP
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid verification code.',
        ]);

        // Password should remain unchanged
        $user->refresh();
        $this->assertTrue(Hash::check('old_password', $user->password));
    }

    public function test_reset_password_fails_with_expired_otp()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('old_password'),
            'unid' => 1234,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'john@example.com',
            'token' => '123456',
            'created_at' => Carbon::now()->subMinutes(61), // expired
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => '123456',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Verification code has expired.',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'john@example.com',
        ]);

        // Password should remain unchanged
        $user->refresh();
        $this->assertTrue(Hash::check('old_password', $user->password));
    }
}
