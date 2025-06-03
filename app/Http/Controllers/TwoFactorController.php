<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{

    public function showVerifyForm()
    {
        return view('auth.2fa-verify'); // Blade file you will create
    }

    public function verify(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $user = auth()->user();

        if ($google2fa->verifyKey($user->google2fa_secret, $request->input('one_time_password'))) {
            session(['2fa_passed' => true]);
            return redirect()->intended('/dashboard');
        }
        \Log::info('2FA verification passed for user: ' . auth()->user()->email);

        return back()->withErrors(['one_time_password' => 'Invalid code.']);
    }




    public function setup()
    {
        $google2fa = app('pragmarx.google2fa');
        $user = Auth::user();

        if ($user->google2fa_secret) {
            return redirect()->route('dashboard')->with('info', '2FA is already enabled.');
        }

        // Only generate a new secret if one is not already in session
        $secret = session('2fa_secret') ?? $google2fa->generateSecretKey();
        session(['2fa_secret' => $secret]);

        $qrUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            )
        );
        $qrCodeSvg = base64_encode($writer->writeString($qrUrl));

        \Log::info('2FA Setup Generated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'secret_shown' => $secret
        ]);

        return view('auth.2fa_setup', compact('qrCodeSvg', 'secret'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = Auth::user();
        $secret = session('2fa_secret');

        if (!$secret) {
            return redirect()->route('2fa.setup')->with('error', 'Secret key missing. Please try setting up 2FA again.');
        }

        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey($secret, $request->otp);

        \Log::info('2FA Verification Attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'provided_otp' => $request->otp,
            'expected_otp' => $google2fa->getCurrentOtp($secret),
            'secret_used' => $secret,
        ]);

        if ($valid) {
            $user->google2fa_secret = $secret;
            $user->save();

            session()->forget('2fa_secret');

            return redirect()->route('dashboard')->with('success', 'Two-Factor Authentication has been enabled!');
        }

        return back()->with('error', 'Invalid OTP, please try again.');
    }


    public function showDisableForm()
    {
        $user = Auth::user();

        if (!$user->google2fa_secret) {
            return redirect()->route('dashboard')->with('info', '2FA is not enabled.');
        }

        return view('auth.2fa_disable');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = Auth::user();

        if (!$user->google2fa_secret) {
            return redirect()->route('dashboard')->with('info', '2FA is not enabled.');
        }

        $google2fa = app('pragmarx.google2fa');

        $otpValid = $google2fa->verifyKey($user->google2fa_secret, $request->input('otp'));

        if (!$otpValid) {
            return back()->with('error', 'Invalid OTP. Please try again.');
        }

        $user->google2fa_secret = null;
        $user->save();

        session()->forget('google2fa_passed');

        return redirect()->route('dashboard')->with('success', '2FA has been disabled.');
    }
}