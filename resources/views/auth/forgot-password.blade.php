@extends('layouts.app')

@section('title', 'Forgot Password')
@section('body_class', 'auth-page')

@section('content')
    <section class="auth-shell compact-auth">
        <div class="auth-art">
            <img src="{{ asset('assets/produce-crates.png') }}" alt="Fresh produce ready for delivery" decoding="async">
            <div>
                <span class="eyebrow">Account recovery</span>
                <h1>Reset access without losing marketplace history.</h1>
            </div>
        </div>

        <form class="auth-form" method="POST" action="{{ route('password.email') }}" data-loading-form>
            @csrf
            <div class="form-heading">
                <span class="eyebrow">Forgot password</span>
                <h2>Send a reset link</h2>
                <p>Enter the email on your FarmBridge account. We will send a secure reset link.</p>
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

            <button class="button primary full" type="submit" data-loading-label="Sending...">Send Reset Link</button>
            <p class="form-alt"><a href="{{ route('login') }}">Back to login</a></p>
        </form>
    </section>
@endsection
