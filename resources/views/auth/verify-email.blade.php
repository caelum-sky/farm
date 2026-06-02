@extends('layouts.app')

@section('title', 'Verify Email')
@section('body_class', 'auth-page')

@section('content')
    <section class="auth-shell compact-auth">
        <div class="auth-art">
            <img src="{{ asset('assets/farmer-market.png') }}" alt="Farmer reviewing marketplace orders" decoding="async">
            <div>
                <span class="eyebrow">Email verification</span>
                <h1>Protect your account before high-value marketplace actions.</h1>
            </div>
        </div>

        <div class="auth-form">
            <div class="form-heading">
                <span class="eyebrow">Verify email</span>
                <h2>Check your inbox</h2>
                <p>A verification link has been sent to {{ auth()->user()->email }}. You can resend it anytime.</p>
            </div>

            <form method="POST" action="{{ route('verification.send') }}" data-loading-form>
                @csrf
                <button class="button primary full" type="submit" data-loading-label="Sending...">Resend Verification Email</button>
            </form>

            <p class="form-alt"><a href="{{ route('profile.edit') }}">Open profile settings</a></p>
        </div>
    </section>
@endsection
