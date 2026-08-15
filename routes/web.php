<?php

use App\Http\Controllers\ModuleManagement\ModuleSettingsController;
use App\Http\Controllers\ModuleManagement\PageSettingsController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

require base_path('routes/restaurant.php');

Route::get('/login', function () {
    if (Auth::check()) {
        return Auth::user()->isStaff()
            ? redirect()->route('dashboard')
            : redirect()->route('landing');
    }

    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    if (Auth::check()) {
        return Auth::user()->isStaff()
            ? redirect()->route('dashboard')
            : redirect()->route('landing');
    }

    return view('auth.register');
})->name('register');

Route::post('/register', function (Request $request) {
    $request->merge([
        'phone' => filled($request->input('phone')) ? trim((string) $request->input('phone')) : null,
    ]);

    $validated = $request->validate([
        'full_name' => ['required', 'string', 'max:200'],
        'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'phone' => ['required', 'string', 'max:30'],
        'password' => [
            'required',
            'confirmed',
            Password::min(8)
                ->max(30)
                ->letters()
                ->numbers()
                ->mixedCase()
                ->symbols(),
        ],
    ]);

    $nameParts = preg_split('/\s+/', trim($validated['full_name']), 2);
    $firstName = $nameParts[0] ?? $validated['full_name'];
    $lastName = $nameParts[1] ?? '';

    $user = User::create([
        'first_name' => $firstName,
        'last_name' => $lastName ?: $firstName,
        'username' => $validated['username'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'password' => $validated['password'],
        'role_id' => Role::query()->where('slug', Role::SLUG_USER)->firstOrFail()->id,
    ]);

    // Generate and send verification code
    $verificationService = app(\App\Services\EmailVerificationService::class);
    $verificationService->generateAndSend($user);

    Auth::login($user);
    $request->session()->regenerate();

    return redirect()
        ->route('auth.verify-email')
        ->with('success', __('Account created! Check your email for the verification code.'));
})->middleware('throttle:6,1')->name('register.perform');

// Email verification routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/verify-email', function () {
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('landing');
        }
        return view('auth.verify-email');
    })->name('auth.verify-email');

    Route::post('/verify-email', function (Request $request) {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        $verificationService = app(\App\Services\EmailVerificationService::class);
        if ($verificationService->verify(auth()->user(), $validated['code'])) {
            return redirect()
                ->route('landing')
                ->with('success', __('Email verified successfully! Welcome to Smoke n\' Grill.'));
        }

        return back()
            ->withErrors(['code' => __('The verification code is invalid or has expired.')])
            ->withInput();
    })->name('auth.verify-email.perform');

    Route::post('/resend-code', function (Request $request) {
        $verificationService = app(\App\Services\EmailVerificationService::class);
        $verificationService->generateAndSend(auth()->user());

        return back()
            ->with('success', __('Verification code sent to your email.'));
    })->middleware('throttle:3,1')->name('auth.resend-code');
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        $user = Auth::user();
        $redirect = $user && $user->isStaff()
            ? route('dashboard')
            : route('landing');

        return redirect()
            ->intended($redirect)
            ->with('success', __('Signed in successfully.'));
    }

    return back()
        ->withErrors(['email' => __('These credentials do not match our records.')])
        ->onlyInput('email');
})->middleware('throttle:6,1')->name('login.perform');

Route::get('/forgot-password', function () {
    return Auth::check()
        ? redirect()->route('landing')
        : view('auth.forgot-password');
})->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'string', 'email'],
    ]);

    $user = User::query()->where('email', $validated['email'])->first();

    if (! $user) {
        return back()
            ->withErrors(['email' => __('passwords.user')])
            ->onlyInput('email');
    }

    $token = Str::random(64);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        [
            'token' => Hash::make($token),
            'created_at' => now(),
        ]
    );

    return redirect()
        ->route('password.reset', ['token' => $token, 'email' => $user->email])
        ->with('info', __('Debug mode: no email sent. Set your new password below.'));
})->middleware('throttle:6,1')->name('password.email');

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    return Auth::check()
        ? redirect()->route('landing')
        : view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'string', 'email'],
        'password' => [
            'required',
            'confirmed',
            Password::min(8)
                ->max(30)
                ->letters()
                ->numbers()
                ->mixedCase()
                ->symbols(),
        ],
    ]);

    $status = PasswordBroker::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
            ])->setRememberToken(Str::random(60));

            $user->save();
        }
    );

    return $status === PasswordBroker::PASSWORD_RESET
        ? redirect()
            ->route('login')
            ->with('success', __('passwords.reset'))
        : back()->withErrors(['email' => __($status)]);
})->middleware('throttle:6,1')->name('password.update');

Route::middleware('auth')->group(function (): void {
    Route::prefix('module-management')->name('module-management.')->group(function (): void {
        Route::get('/module-settings', [ModuleSettingsController::class, 'index'])
            ->name('module-settings');
        Route::post('/module-settings', [ModuleSettingsController::class, 'store'])
            ->name('module-settings.store');
        Route::patch('/module-settings/modules/{module}', [ModuleSettingsController::class, 'update'])
            ->name('module-settings.update');
        Route::delete('/module-settings/modules/{module}', [ModuleSettingsController::class, 'destroy'])
            ->name('module-settings.destroy');
        Route::post('/module-settings/restore-from-log', [ModuleSettingsController::class, 'restoreFromLog'])
            ->name('module-settings.restore-from-log');
        Route::patch('/module-settings/modules/{module}/sidebar', [ModuleSettingsController::class, 'updateSidebar'])
            ->name('module-settings.sidebar');

        Route::get('/page-settings', [PageSettingsController::class, 'index'])
            ->name('page-settings');
        Route::post('/page-settings', [PageSettingsController::class, 'store'])
            ->name('page-settings.store');
        Route::patch('/page-settings/pages/{page}', [PageSettingsController::class, 'update'])
            ->name('page-settings.update');
        Route::delete('/page-settings/pages/{page}', [PageSettingsController::class, 'destroy'])
            ->name('page-settings.destroy');
        Route::post('/page-settings/restore-from-log', [PageSettingsController::class, 'restoreFromLog'])
            ->name('page-settings.restore-from-log');
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('landing')
            ->with('success', __('You have been signed out.'));
    })->name('logout');
});
