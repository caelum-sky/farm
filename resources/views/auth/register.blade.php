@extends('layouts.app')

@section('title', 'Sign Up')
@section('body_class', 'auth-page')

@section('content')
    <section class="auth-shell">
        <div class="auth-art">
            <img src="{{ asset('assets/farmer-market.png') }}" alt="Farmer preparing produce for market" decoding="async">
            <div>
                <span class="eyebrow">Join the network</span>
                <h1>Bring your equipment, goods, and local farm supply online.</h1>
            </div>
        </div>

        <form class="auth-form" method="POST" action="{{ route('signup.store') }}" data-signup-form data-loading-form>
            @csrf
            <div class="form-heading">
                <span class="eyebrow">Sign up</span>
                <h2>Create your account</h2>
            </div>

            @if ($errors->any())
                <div class="form-errors" role="alert" aria-live="assertive">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="form-grid">
                <label>
                    <span>Full name</span>
                    <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
                </label>
                <label>
                    <span>Farm or business</span>
                    <input type="text" name="farm_name" value="{{ old('farm_name') }}">
                </label>
            </div>

            <label>
                <span>Email address</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </label>

            <div class="form-grid">
                <label>
                    <span>Phone</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                </label>
                <label>
                    <span>Location</span>
                    <input type="text" name="location" value="{{ old('location') }}" required>
                </label>
            </div>

            <label>
                <span>Account type</span>
                <select name="role" required>
                    <option value="farmer" @selected(old('role') === 'farmer')>Farmer</option>
                    <option value="seller" @selected(old('role') === 'seller')>Seller</option>
                    <option value="buyer" @selected(old('role') === 'buyer')>Buyer</option>
                    <option value="cooperative" @selected(old('role') === 'cooperative')>Cooperative</option>
                </select>
            </label>

            <div class="form-grid">
                <label>
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="new-password" required data-password aria-describedby="password-hint">
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" required data-password-confirm aria-describedby="password-hint">
                </label>
            </div>

            <p class="password-hint" id="password-hint" data-password-hint>Use at least 8 characters.</p>

            <button class="button primary full" type="submit" data-loading-label="Creating...">Create Account</button>
            <p class="form-alt">Already have an account? <a href="{{ route('login') }}">Login</a></p>
        </form>
    </section>
@endsection
