<div class="table-wrap">

    <table>

        <thead>

            <tr>
                <th>Driver</th>

                <th>Vehicle</th>

                <th>Applied</th>

                <th>Status</th>

                <th class="actions">
                    Actions
                </th>
            </tr>

        </thead>


        <tbody id="rows">

        @forelse($drivers as $driver)

            <tr>

                {{-- Driver --}}
                <td>

                    <b>
                        {{ $driver->full_name }}
                    </b>

                    <span class="sub">

                        {{ $driver->country_code }}
                        {{ $driver->phone_number }}

                    </span>

                    <span class="sub">
                        {{ $driver->email }}
                    </span>

                </td>


                {{-- Vehicle --}}
                <td>

                    @if(
                        $driver->car_make ||
                        $driver->car_model
                    )

                        {{ $driver->car_make }}
                        {{ $driver->car_model }}

                    @else

                        -

                    @endif


                    @if(
                        $driver->license_plate_number
                    )

                        <span class="sub">

                            {{
                                $driver
                                    ->license_plate_number
                            }}

                        </span>

                    @endif

                </td>


                {{-- Applied --}}
                <td>

                    {{ \Carbon\Carbon::parse(
                        $driver->created_at
                    )->format('d M Y') }}

                </td>


                {{-- Status --}}
                <td>

                    @if(
                        $driver->status ===
                        'under_review'
                    )

                        <span
                            class="badge tone-amber"
                        >
                            Pending
                        </span>

                    @elseif(
                        $driver->status ===
                        'approved'
                    )

                        <span
                            class="badge tone-green"
                        >
                            Approved
                        </span>

                    @elseif(
                        $driver->status ===
                        'rejected'
                    )

                        <span
                            class="badge tone-red"
                        >
                            Rejected
                        </span>

                    @endif

                </td>


                {{-- Actions --}}
                <td class="actions">

                    @if(
                        $driver->status !==
                        'approved'
                    )

                        <button
                            type="button"
                            class="btn btn-sm driver-status-btn"
                            data-id="{{ $driver->id }}"
                            data-status="approved"
                        >
                            Approve
                        </button>

                    @endif


                    @if(
                        $driver->status !==
                        'rejected'
                    )

                        <button
                            type="button"
                            class="btn btn-sm btn-outline driver-status-btn"
                            data-id="{{ $driver->id }}"
                            data-status="rejected"
                        >
                            Reject
                        </button>

                    @endif

                </td>

            </tr>

        @empty

            <tr>

                <td
                    colspan="5"
                    class="empty"
                >
                    No drivers found.
                </td>

            </tr>

        @endforelse

        </tbody>

    </table>

</div>


{{-- Pagination --}}
@if($drivers->hasPages())

    <div class="pagination-wrap">

        <div class="ridude-pagination">


            {{-- Previous --}}
            @if($drivers->onFirstPage())

                <span
                    class="page-btn disabled"
                >
                    ‹
                </span>

            @else

                <a
                    href="{{ $drivers->previousPageUrl() }}"
                    class="page-btn"
                >
                    ‹
                </a>

            @endif


            @php

                $current =
                    $drivers->currentPage();

                $last =
                    $drivers->lastPage();

                $start =
                    max(
                        1,
                        $current - 2
                    );

                $end =
                    min(
                        $last,
                        $current + 2
                    );

            @endphp


            @if($start > 1)

                <a
                    href="{{ $drivers->url(1) }}"
                    class="page-btn"
                >
                    1
                </a>


                @if($start > 2)

                    <span class="page-dots">
                        ...
                    </span>

                @endif

            @endif


            @for(
                $page = $start;
                $page <= $end;
                $page++
            )

                @if(
                    $page === $current
                )

                    <span
                        class="page-btn active"
                    >
                        {{ $page }}
                    </span>

                @else

                    <a
                        href="{{ $drivers->url($page) }}"
                        class="page-btn"
                    >
                        {{ $page }}
                    </a>

                @endif

            @endfor


            @if($end < $last)

                @if(
                    $end < ($last - 1)
                )

                    <span class="page-dots">
                        ...
                    </span>

                @endif


                <a
                    href="{{
                        $drivers->url($last)
                    }}"
                    class="page-btn"
                >
                    {{ $last }}
                </a>

            @endif


            {{-- Next --}}
            @if(
                $drivers->hasMorePages()
            )

                <a
                    href="{{
                        $drivers
                            ->nextPageUrl()
                    }}"
                    class="page-btn"
                >
                    ›
                </a>

            @else

                <span
                    class="page-btn disabled"
                >
                    ›
                </span>

            @endif

        </div>

    </div>

@endif