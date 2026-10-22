<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />

    <meta
        content="width=device-width, initial-scale=1"
        name="viewport"
    />

    <title>
        @yield('title', 'Ridude Admin') | Ridude Admin
    </title>

    <meta
        content="Ridude Admin Console"
        name="description"
    />

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    />

    <link
        href="{{ asset('assets/images/logo.svg') }}"
        rel="icon"
        type="image/svg+xml"
    />

    <link
        href="{{ asset('assets/css/styles.css') }}"
        rel="stylesheet"
    />

    @stack('styles')
</head>

<body data-page="@yield('page-name', 'dashboard')">

<div class="app">

    {{-- Sidebar --}}
    <aside class="sidebar">

        <a
            class="logo"
            href="{{ route('admin.dashboard') }}"
        >
            <img
                alt="Ridude"
                src="{{ asset('assets/images/logo.svg') }}"
            />

            <div class="logo-text">
                <p class="logo-name">
                    RID<span>U</span>DE
                </p>

                <p class="logo-tag">
                    Drive Together. Go Further.
                </p>
            </div>
        </a>


        <nav>

            <p class="nav-label">
                Overview
            </p>

            <a
                class="nav-link
                {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                href="{{ route('admin.dashboard') }}"
                title="Dashboard"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <rect
                        height="9"
                        rx="1"
                        width="7"
                        x="3"
                        y="3"
                    ></rect>

                    <rect
                        height="5"
                        rx="1"
                        width="7"
                        x="14"
                        y="3"
                    ></rect>

                    <rect
                        height="9"
                        rx="1"
                        width="7"
                        x="14"
                        y="12"
                    ></rect>

                    <rect
                        height="5"
                        rx="1"
                        width="7"
                        x="3"
                        y="16"
                    ></rect>
                </svg>

                <span>Dashboard</span>
            </a>


            <p class="nav-label">
                People
            </p>

            <a
                class="nav-link
                {{ request()->routeIs('admin.riders.*') ? 'active' : '' }}"
                href="{{ route('admin.riders.index') }}"
                title="Riders"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <circle cx="9" cy="8" r="4"></circle>

                    <path
                        d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1"
                    ></path>

                    <path d="M16 4a4 4 0 0 1 0 8"></path>

                    <path
                        d="M22 21v-1a6 6 0 0 0-4-5.6"
                    ></path>
                </svg>

                <span>Riders</span>
            </a>


            <a
                class="nav-link
                {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}"
                href="{{ route('admin.drivers.index') }}"
                title="Drivers"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M5 17H3v-5l2-5h14l2 5v5h-2"
                    ></path>

                    <circle cx="7.5" cy="17" r="2"></circle>
                    <circle cx="16.5" cy="17" r="2"></circle>

                    <path d="M9.5 17h5"></path>
                </svg>

                <span>Drivers</span>
            </a>


            <p class="nav-label">
                Setup
            </p>

            <a
                class="nav-link
                {{ request()->routeIs('admin.workplaces.*') ? 'active' : '' }}"
                href="{{ route('admin.workplaces.index') }}"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <rect
                        height="18"
                        rx="2"
                        width="16"
                        x="4"
                        y="3"
                    ></rect>

                    <path
                        d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1"
                    ></path>

                    <path
                        d="M10 21v-3h4v3"
                    ></path>
                </svg>

                <span>Workplaces</span>
            </a>


            <a
                class="nav-link
                {{ request()->routeIs('admin.payment-methods.*') ? 'active' : '' }}"
                href="{{ route('admin.payment-methods.index') }}"
                title="Payment Methods"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <rect
                        height="14"
                        rx="2"
                        width="20"
                        x="2"
                        y="5"
                    ></rect>

                    <path d="M2 10h20"></path>
                </svg>

                <span>Payment Methods</span>
            </a>


            <p class="nav-label">
                Reports
            </p>

            <a
                class="nav-link
                {{ request()->routeIs('admin.trips.*') ? 'active' : '' }}"
                href="{{ route('admin.trips.index') }}"
                title="Trips"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <circle cx="6" cy="19" r="3"></circle>
                    <circle cx="18" cy="5" r="3"></circle>

                    <path
                        d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"
                    ></path>
                </svg>

                <span>Trips</span>
            </a>


            <a
                class="nav-link"
                href="#"
                title="Driver Subscriptions"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M4 2v20l3-2 3 2 2-2 2 2 3-2 3 2V2l-3 2-3-2-2 2-2-2-3 2z"
                    ></path>

                    <path
                        d="M8 8h8M8 12h8M8 16h5"
                    ></path>
                </svg>

                <span>Driver Subscriptions</span>
            </a>


            <a
                class="nav-link
                {{ request()->routeIs('admin.trip-transactions.*') ? 'active' : '' }}"
                href="{{ route('admin.trip-transactions.index') }}"
            >
                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M4 2v20l3-2 3 2 2-2 2 2 3-2 3 2V2l-3 2-3-2-2 2-2-2-3 2z"
                    ></path>

                    <path d="M8 8h8M8 12h8M8 16h5"></path>
                </svg>

                <span>Trip Transactions</span>
            </a>

        </nav>

    </aside>


    <div class="overlay"></div>


    <div class="main">

        {{-- Topbar --}}
        <header class="topbar">

            <div class="topbar-left">

                <button
                    aria-label="Toggle sidebar"
                    class="icon-btn"
                    id="sidebar-toggle"
                >
                    <svg
                        aria-hidden="true"
                        class="icon"
                        viewBox="0 0 24 24"
                    >
                        <rect
                            height="18"
                            rx="2"
                            width="18"
                            x="3"
                            y="3"
                        ></rect>

                        <path
                            d="M9 3v18"
                        ></path>
                    </svg>
                </button>

                <span>Admin Console</span>

            </div>


            <div class="profile">

                <button
                    aria-expanded="false"
                    aria-haspopup="true"
                    class="profile-btn"
                    id="profile-btn"
                >
                    <span class="avatar">
                        {{ strtoupper(substr(auth()->user()->full_name, 0, 2)) }}
                    </span>

                    <span>
                        {{ auth()->user()->full_name }}
                    </span>

                    <svg
                        aria-hidden="true"
                        class="icon"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M6 9l6 6 6-6"
                        ></path>
                    </svg>
                </button>


                <div
                    class="dropdown"
                    id="profile-menu"
                >

                    <div class="dropdown-label">
                        {{ auth()->user()->email }}
                    </div>


                    <form
                        action="{{ route('admin.logout') }}"
                        method="POST"
                    >
                        @csrf

                        <button
                            class="dropdown-item"
                            type="submit"
                        >
                            <svg
                                aria-hidden="true"
                                class="icon"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                ></path>

                                <path
                                    d="M16 17l5-5-5-5M21 12H9"
                                ></path>
                            </svg>

                            Logout
                        </button>

                    </form>

                </div>

            </div>

        </header>


        {{-- ===================================
            PAGE AREA
        ==================================== --}}
        <main
            class="content"
            id="content"
        >

            @hasSection('page-title')

                <h1 class="page-title">
                    @yield('page-title')
                </h1>

            @endif


            @hasSection('page-subtitle')

                <p class="page-sub">
                    @yield('page-subtitle')
                </p>

            @endif


            @yield('content')

        </main>

    </div>

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


<script
    defer
    src="{{ asset('assets/js/app.js') }}"
></script>

@stack('scripts')

</body>
</html>