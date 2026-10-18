@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-name', 'dashboard')

<!-- @section('page-title', 'Welcome back')

@section('page-subtitle')
Drive Together. Go Further. Here's what's happening today.
@endsection -->


@section('content')



    {{-- dashboard content --}}

                    <!-- <h1 class="page-title">Welcome back1</h1>
                    <p class="page-sub">
                        Drive Together. Go Further. Here's what's happening
                        today.
                    </p> -->
                    <div class="grid grid-4">
                        <div class="card reveal">
                            <div class="stat">
                                <div class="stat-icon tone-blue">
                                    <svg
                                        aria-hidden="true"
                                        class="icon"
                                        viewbox="0 0 24 24"
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
                                </div>
                                <div>
                                    <p class="stat-label">Registered Riders</p>
                                    <p class="stat-value">12</p>
                                </div>
                            </div>
                        </div>
                        <div class="card reveal">
                            <div class="stat">
                                <div class="stat-icon tone-green">
                                    <svg
                                        aria-hidden="true"
                                        class="icon"
                                        viewbox="0 0 24 24"
                                    >
                                        <path
                                            d="M5 17H3v-5l2-5h14l2 5v5h-2"
                                        ></path>
                                        <circle cx="7.5" cy="17" r="2"></circle>
                                        <circle
                                            cx="16.5"
                                            cy="17"
                                            r="2"
                                        ></circle>
                                        <path d="M9.5 17h5"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="stat-label">Registered Drivers</p>
                                    <p class="stat-value">9</p>
                                </div>
                            </div>
                            <p class="stat-hint">5 approved</p>
                        </div>
                        <div class="card reveal">
                            <div class="stat">
                                <div class="stat-icon tone-blue">
                                    <svg
                                        aria-hidden="true"
                                        class="icon"
                                        viewbox="0 0 24 24"
                                    >
                                        <circle cx="6" cy="19" r="3"></circle>
                                        <circle cx="18" cy="5" r="3"></circle>
                                        <path
                                            d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="stat-label">Trips Today</p>
                                    <p class="stat-value">10</p>
                                </div>
                            </div>
                            <p class="stat-hint">226 trips all time</p>
                        </div>
                        <div class="card reveal">
                            <div class="stat">
                                <div class="stat-icon tone-amber">
                                    <svg
                                        aria-hidden="true"
                                        class="icon"
                                        viewbox="0 0 24 24"
                                    >
                                        <path
                                            d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3v3a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V5"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="stat-label">
                                        Subscription Revenue
                                    </p>
                                    <p class="stat-value">₹36,873</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-main mt">
                        <div class="card reveal">
                            <h2 class="card-title">Trips — last 14 days</h2>
                            <div class="chart" id="chart">
                                <svg
                                    preserveaspectratio="xMidYMid meet"
                                    viewbox="0 0 640 280"
                                >
                                    <line
                                        class="grid-line"
                                        x1="44"
                                        x2="630"
                                        y1="252"
                                        y2="252"
                                    ></line>
                                    <text text-anchor="end" x="36" y="256">
                                        0
                                    </text>
                                    <line
                                        class="grid-line"
                                        x1="44"
                                        x2="630"
                                        y1="191.5"
                                        y2="191.5"
                                    ></line>
                                    <text text-anchor="end" x="36" y="195.5">
                                        3
                                    </text>
                                    <line
                                        class="grid-line"
                                        x1="44"
                                        x2="630"
                                        y1="131"
                                        y2="131"
                                    ></line>
                                    <text text-anchor="end" x="36" y="135">
                                        6
                                    </text>
                                    <line
                                        class="grid-line"
                                        x1="44"
                                        x2="630"
                                        y1="70.5"
                                        y2="70.5"
                                    ></line>
                                    <text text-anchor="end" x="36" y="74.5">
                                        9
                                    </text>
                                    <line
                                        class="grid-line"
                                        x1="44"
                                        x2="630"
                                        y1="10"
                                        y2="10"
                                    ></line>
                                    <text text-anchor="end" x="36" y="14">
                                        12
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="03 Oct: 3 trips"
                                        height="60.5"
                                        rx="5"
                                        style="animation-delay: 0ms"
                                        width="29.299999999999997"
                                        x="50.278571428571425"
                                        y="191.5"
                                    ></rect>
                                    <text
                                        text-anchor="middle"
                                        x="64.92857142857142"
                                        y="272"
                                    >
                                        03 Oct
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="04 Oct: 9 trips"
                                        height="181.5"
                                        rx="5"
                                        style="animation-delay: 30ms"
                                        width="29.299999999999997"
                                        x="92.13571428571429"
                                        y="70.5"
                                    ></rect>
                                    <rect
                                        class="bar"
                                        data-tip="05 Oct: 7 trips"
                                        height="141.16666666666666"
                                        rx="5"
                                        style="animation-delay: 60ms"
                                        width="29.299999999999997"
                                        x="133.99285714285713"
                                        y="110.83333333333334"
                                    ></rect>
                                    <text
                                        text-anchor="middle"
                                        x="148.64285714285714"
                                        y="272"
                                    >
                                        05 Oct
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="06 Oct: 6 trips"
                                        height="121"
                                        rx="5"
                                        style="animation-delay: 90ms"
                                        width="29.299999999999997"
                                        x="175.85"
                                        y="131"
                                    ></rect>
                                    <rect
                                        class="bar"
                                        data-tip="07 Oct: 7 trips"
                                        height="141.16666666666666"
                                        rx="5"
                                        style="animation-delay: 120ms"
                                        width="29.299999999999997"
                                        x="217.70714285714286"
                                        y="110.83333333333334"
                                    ></rect>
                                    <text
                                        text-anchor="middle"
                                        x="232.35714285714286"
                                        y="272"
                                    >
                                        07 Oct
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="08 Oct: 4 trips"
                                        height="80.66666666666667"
                                        rx="5"
                                        style="animation-delay: 150ms"
                                        width="29.299999999999997"
                                        x="259.5642857142857"
                                        y="171.33333333333331"
                                    ></rect>
                                    <rect
                                        class="bar"
                                        data-tip="09 Oct: 11 trips"
                                        height="221.83333333333334"
                                        rx="5"
                                        style="animation-delay: 180ms"
                                        width="29.299999999999997"
                                        x="301.4214285714285"
                                        y="30.166666666666657"
                                    ></rect>
                                    <text
                                        text-anchor="middle"
                                        x="316.0714285714285"
                                        y="272"
                                    >
                                        09 Oct
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="10 Oct: 9 trips"
                                        height="181.5"
                                        rx="5"
                                        style="animation-delay: 210ms"
                                        width="29.299999999999997"
                                        x="343.2785714285714"
                                        y="70.5"
                                    ></rect>
                                    <rect
                                        class="bar"
                                        data-tip="11 Oct: 4 trips"
                                        height="80.66666666666667"
                                        rx="5"
                                        style="animation-delay: 240ms"
                                        width="29.299999999999997"
                                        x="385.13571428571424"
                                        y="171.33333333333331"
                                    ></rect>
                                    <text
                                        text-anchor="middle"
                                        x="399.7857142857142"
                                        y="272"
                                    >
                                        11 Oct
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="12 Oct: 4 trips"
                                        height="80.66666666666667"
                                        rx="5"
                                        style="animation-delay: 270ms"
                                        width="29.299999999999997"
                                        x="426.9928571428571"
                                        y="171.33333333333331"
                                    ></rect>
                                    <rect
                                        class="bar"
                                        data-tip="13 Oct: 11 trips"
                                        height="221.83333333333334"
                                        rx="5"
                                        style="animation-delay: 300ms"
                                        width="29.299999999999997"
                                        x="468.84999999999997"
                                        y="30.166666666666657"
                                    ></rect>
                                    <text
                                        text-anchor="middle"
                                        x="483.49999999999994"
                                        y="272"
                                    >
                                        13 Oct
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="14 Oct: 12 trips"
                                        height="242"
                                        rx="5"
                                        style="animation-delay: 330ms"
                                        width="29.299999999999997"
                                        x="510.7071428571428"
                                        y="10"
                                    ></rect>
                                    <rect
                                        class="bar"
                                        data-tip="15 Oct: 12 trips"
                                        height="242"
                                        rx="5"
                                        style="animation-delay: 360ms"
                                        width="29.299999999999997"
                                        x="552.5642857142857"
                                        y="10"
                                    ></rect>
                                    <text
                                        text-anchor="middle"
                                        x="567.2142857142857"
                                        y="272"
                                    >
                                        15 Oct
                                    </text>
                                    <rect
                                        class="bar"
                                        data-tip="16 Oct: 10 trips"
                                        height="201.66666666666666"
                                        rx="5"
                                        style="animation-delay: 390ms"
                                        width="29.299999999999997"
                                        x="594.4214285714286"
                                        y="50.33333333333334"
                                    ></rect>
                                </svg>
                            </div>
                        </div>
                        <div class="card reveal">
                            <h2 class="card-title">
                                Pending driver approvals
                                <a class="link" href="drivers.php">View all</a>
                            </h2>
                            <div class="pending">
                                <svg
                                    aria-hidden="true"
                                    class="icon"
                                    viewbox="0 0 24 24"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>
                                <div>
                                    <b>Sanjay More</b
                                    ><span class="small muted"
                                        >Maruti Ertiga · MH 12 FG 7313</span
                                    >
                                </div>
                            </div>
                            <div class="pending">
                                <svg
                                    aria-hidden="true"
                                    class="icon"
                                    viewbox="0 0 24 24"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>
                                <div>
                                    <b>Nitin Bhosale</b
                                    ><span class="small muted"
                                        >Kia Carens · MH 12 GH 2709</span
                                    >
                                </div>
                            </div>
                            <div class="pending">
                                <svg
                                    aria-hidden="true"
                                    class="icon"
                                    viewbox="0 0 24 24"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>
                                <div>
                                    <b>Prakash Yadav</b
                                    ><span class="small muted"
                                        >Hyundai Xcent · MH 12 HI 7722</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                



@endsection