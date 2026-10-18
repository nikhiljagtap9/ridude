<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Sign in — Ridude Admin
    </title>

    <meta
        name="description"
        content="Sign in to the Ridude admin console."
    >


    <link
        href="{{ asset('assets/images/logo.svg') }}"
        rel="icon"
        type="image/svg+xml"
    >


    <link
        href="{{ asset('assets/css/styles.css') }}"
        rel="stylesheet"
    >

</head>


<body data-page="login">


<div class="login-page">

    <form
        action="{{ route('admin.login.submit') }}"
        method="POST"
        class="card login-card"
        id="login-form"
    >

        @csrf


        <div class="login-head">

            <img
                alt="Ridude"
                src="{{ asset('assets/images/logo.svg') }}"
            >

            <h1>
                Welcome back
            </h1>

            <p class="muted">
                Sign in to the admin console
            </p>

        </div>


        @if(session('success'))

            <div class="alert alert-success">
                {{ session('success') }}
            </div>

        @endif


        @if($errors->any())

            <div class="alert alert-danger">

                {{ $errors->first() }}

            </div>

        @endif


        <div class="field">

            <label for="email">
                Email
            </label>

            <input
                class="input"
                id="email"
                name="email"
                type="email"
                value="{{ old('email', 'admin@ridude.in') }}"
                required
                autocomplete="email"
            >

            @error('email')

                <span class="field-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        <div class="field">

            <label for="password">
                Password
            </label>

            <input
                class="input"
                id="password"
                name="password"
                placeholder="••••••••"
                type="password"
                required
                autocomplete="current-password"
            >

            @error('password')

                <span class="field-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        <div class="field">

            <label>

                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >

                Remember me

            </label>

        </div>


        <button
            class="btn btn-block"
            type="submit"
        >
            Sign in
        </button>

    </form>

</div>


<div
    aria-live="polite"
    class="toast"
    role="status"
></div>


<div
    class="chart-tip"
    role="tooltip"
></div>


<!-- <script
    defer
    src="{{ asset('assets/js/app.js') }}"
></script> -->


</body>

</html>