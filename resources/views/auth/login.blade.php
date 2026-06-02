@extends('layouts.app')

@section('title', 'Login')
@section('body_class', 'auth-page')

@section('content')
    <section class="auth-shell">
        <div class="auth-art">
            <img src="{{ asset('assets/hero-farm-market.png') }}" alt="FarmBridge marketplace" decoding="async">
            <div>
                <span class="eyebrow">Welcome back</span>
                <h1>Manage listings, messages, rentals, and harvest orders.</h1>
            </div>
        </div>

        <form class="auth-form" method="POST" action="{{ route('login.store') }}" data-loading-form>
            @csrf
            <div class="form-heading">
                <span class="eyebrow">Login</span>
                <h2>Open your FarmBridge account</h2>
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
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <label class="check-line">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
            </label>

            <button class="button primary full" type="submit" data-loading-label="Opening...">Login</button>
            <p class="form-alt">Forgot your password? <a href="{{ route('password.request') }}">Reset it</a></p>
            <p class="form-alt">New to FarmBridge? <a href="{{ route('signup') }}">Create an account</a></p>
        </form>
    </section>
@endsection
