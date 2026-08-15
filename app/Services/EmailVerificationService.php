<?php

namespace App\Services;

use App\Mail\EmailVerificationMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationService
{
    /**
     * Generate and send a 6-digit verification code to user's email.
     */
    public function generateAndSend(User $user): EmailVerificationCode
    {
        try {
            // Invalidate previous codes
            EmailVerificationCode::query()
                ->where('user_id', $user->id)
                ->whereNull('verified_at')
                ->delete();
        } catch (\Exception) {
            // Table might not exist yet during migration
        }

        // Generate 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            // Create verification code (valid for 10 minutes)
            $verification = EmailVerificationCode::create([
                'user_id' => $user->id,
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
            ]);
        } catch (\Exception) {
            // If table doesn't exist, just log the code for development
            \Log::warning('EmailVerificationCode table not found. Please run migrations.', [
                'user_id' => $user->id,
                'code' => $code,
            ]);
            
            // Return a temporary object for dev purposes
            $verification = new EmailVerificationCode([
                'user_id' => $user->id,
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
            ]);
        }

        // Send email
        $this->sendVerificationEmail($user, $code);

        return $verification;
    }

    /**
     * Verify the code and mark user's email as verified.
     */
    public function verify(User $user, string $code): bool
    {
        $verification = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('code', $code)
            ->whereNull('verified_at')
            ->first();

        if (!$verification || !$verification->isValid()) {
            return false;
        }

        $verification->verify();
        $user->update(['email_verified_at' => now()]);

        return true;
    }

    /**
     * Send verification email.
     */
    private function sendVerificationEmail(User $user, string $code): void
    {
        try {
            Mail::to($user->email)->send(new EmailVerificationMail($user, $code));
        } catch (\Exception $e) {
            // Log error but don't crash the registration flow
            \Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
