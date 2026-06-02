@extends('layouts.app')

@section('title', 'Reset Password')
@section('body_class', 'auth-page')

@section('content')
    <section class="auth-shell compact-auth">
        <div class="auth-art">
            <img src="{{ asset('assets/hero-farm-market.png') }}" alt="FarmBridge marketplace" decoding="async">
            <div>
                <span class="eyebrow">Secure reset</span>
                <h1>Create a stronger password for your account.</h1>
            </div>
        </div>

        <form class="auth-form" method="POST" action="{{ route('password.update') }}" data-signup-form data-loading-form>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-heading">
                <span class="eyebrow">Reset password</span>
                <h2>Choose a new password</h2>
            </div>

            @if ($errors->any())
                <div class="form-errors" role="alert" aria-live="assertive">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <label>
                <span>Email address</span>
                <input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            </label>

            <div class="form-grid">
                <label>
                    <span>New password</span>
                    <input type="password" name="password" autocomplete="new-password" required data-password aria-describedby="password-hint">
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" required data-password-confirm aria-describedby="password-hint">
                </label>
            </div>

            <p class="password-hint" id="password-hint" data-password-hint>Use at least 8 characters.</p>
            <button class="button primary full" type="submit" data-loading-label="Saving...">Reset Password</button>
        </form>
    </section>
@endsection
