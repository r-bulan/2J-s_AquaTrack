<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function root()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('dashboard');
        }
        if ($user->isRider()) {
            return redirect()->route('deliveries.index');
        }
        return redirect()->route('portal.index');
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->root();
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = (bool) $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            $this->activityLogService->log(
                action: 'User Logged In',
                entityType: 'User',
                entityId: $user->id,
                description: sprintf('User %s (%s) logged in', $user->name, $user->role)
            );

            return $this->root();
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->root();
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'barangay' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'customer',
                'email_verified_at' => null, // Explicitly unverified: customer must verify via email link
            ]);

            // Automatically create Customer record and ledgers
            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['address'],
                'barangay' => $validated['barangay'] ?? null,
                'area' => $validated['area'] ?? null,
                'status' => 'Active',
                'avg_reorder_days' => 7,
            ]);

            $customer->jugLedger()->create([
                'customer_name' => $customer->name,
                'jugs_held' => 0,
                'deposit_status' => 'Unpaid',
                'deposit_amount' => 0,
            ]);

            $customer->creditLedger()->create([
                'customer_name' => $customer->name,
                'amount_owed' => 0,
                'status' => 'Settled',
            ]);

            $customer->loyaltyRecord()->create([
                'customer_name' => $customer->name,
                'refills_count' => 0,
                'refills_needed' => 10,
                'free_jugs_earned' => 0,
            ]);

            return $user;
        });

        // Trigger email verification notification
        event(new Registered($user));

        Auth::login($user);

        $this->activityLogService->log(
            action: 'Customer Registered',
            entityType: 'User',
            entityId: $user->id,
            description: sprintf('New customer %s registered online (verification link dispatched)', $user->name)
        );

        return redirect()->route('portal.index')->with('success', 'Welcome to Two J\'s AquaTrack! A verification link has been sent to your email.');
    }

    public function showVerifyNotice()
    {
        if (Auth::user()?->hasVerifiedEmail()) {
            return redirect()->route('portal.index');
        }

        return view('auth.verify-email');
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = $request->user();

        if (!$user || !hash_equals((string) $id, (string) $user->getKey())) {
            abort(403, 'Unauthorized user verification attempt.');
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid or expired verification signature.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('portal.index')->with('status', 'Your email is already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            $this->activityLogService->log(
                action: 'Email Verified',
                entityType: 'User',
                entityId: $user->id,
                description: sprintf('User %s (%s) successfully verified their email address', $user->name, $user->email)
            );
        }

        return redirect()->route('portal.index')->with('success', 'Your email has been successfully verified! You now have full access to place refill orders.');
    }

    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('portal.index');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $this->activityLogService->log(
                action: 'User Logged Out',
                entityType: 'User',
                entityId: $user->id,
                description: sprintf('User %s logged out', $user->name)
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Dispatch token and notification via Laravel's password broker
        PasswordBroker::sendResetLink($request->only('email'));

        // Always return generic response to prevent user enumeration
        return back()->with('status', 'If an account exists with that email address, a password reset link has been dispatched.');
    }

    public function showResetPassword(Request $request, ?string $token = null)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $this->activityLogService->log(
                    action: 'Password Reset',
                    entityType: 'User',
                    entityId: $user->id,
                    description: sprintf('Password was reset successfully for %s (%s)', $user->name, $user->email)
                );
            }

            return redirect()->route('login')->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)])->onlyInput('email');
    }
}
