<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FarmBridge') | FarmBridge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/argon-dashboard.css') }}?v={{ filemtime(public_path('assets/argon-dashboard.css')) }}">
</head>
<body class="@yield('body_class') theme-{{ auth()->check() ? auth()->user()->theme : 'harvest' }}">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="site-header" data-header>
        <a class="brand" href="{{ route('home') }}" aria-label="FarmBridge home">
            <span class="brand-mark">FB</span>
            <span>FarmBridge</span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-navigation" data-nav-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="site-nav" id="site-navigation" aria-label="Primary navigation" data-site-nav>
            <a href="{{ route('marketplace.index') }}" @if(request()->routeIs('marketplace.*')) aria-current="page" @endif>Marketplace</a>
            @auth
                @if (auth()->user()->isAdmin())
                    <a class="nav-pill" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.*')) aria-current="page" @endif>Admin Console</a>
                    <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*') || request()->routeIs('verification.*')) aria-current="page" @endif>Profile</a>
                @else
                    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
                    <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*') || request()->routeIs('verification.*')) aria-current="page" @endif>Profile</a>
                    @if (auth()->user()->canCreateListings())
                        <a class="nav-pill" href="{{ route('marketplace.create') }}" @if(request()->routeIs('marketplace.create')) aria-current="page" @endif>List Item</a>
                    @endif
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-link-button" type="submit">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Login</a>
                <a class="nav-pill" href="{{ route('signup') }}" @if(request()->routeIs('signup')) aria-current="page" @endif>Sign Up</a>
            @endauth
        </nav>
    </header>

    @if (session('status') || $errors->any())
        <div class="toast-stack" aria-live="polite">
            @if (session('status'))
                <div class="flash" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="flash danger" role="alert" aria-live="assertive">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <main id="main-content" tabindex="-1">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div>
            <strong>FarmBridge</strong>
            <span>Equipment, harvests, and local farm trade in one place.</span>
        </div>
        <div class="footer-links">
            <a href="{{ route('marketplace.index') }}">Browse</a>
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}">Admin</a>
                    <a href="{{ route('admin.audit.index') }}">Audit</a>
                    <a href="{{ route('admin.settings.edit') }}">Settings</a>
                @elseif (auth()->user()->canCreateListings())
                    <a href="{{ route('marketplace.create') }}">Sell or Rent</a>
                @else
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @endif
                <a href="{{ route('profile.edit') }}">Profile</a>
            @else
                <a href="{{ route('signup') }}">Join</a>
            @endauth
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
</body>
</html>
