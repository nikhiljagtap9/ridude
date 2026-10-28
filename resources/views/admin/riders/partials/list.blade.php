<div class="table-wrap">

    <table>

        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Company</th>
                <th>Trips</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody id="rows">

            @forelse($riders as $rider)

                <tr>

                    <td>
                        <b>
                            {{ $rider->full_name }}
                        </b>

                        <span class="sub">
                            {{ $rider->email }}
                        </span>
                    </td>

                    <td>
                        {{ $rider->country_code }}
                        {{ $rider->phone_number }}
                    </td>

                    <td>
                        -
                    </td>

                    <td>
                        {{ $rider->total_trips }}
                    </td>

                    <td>

                        @if($rider->status === 'approved')

                            <span class="badge tone-green">
                                Approved
                            </span>

                        @elseif($rider->status === 'under_review')

                            <span class="badge tone-amber">
                                Under Review
                            </span>

                        @elseif($rider->status === 'rejected')

                            <span class="badge tone-red">
                                Rejected
                            </span>

                        @else

                            <span class="badge">
                                {{ ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $rider->status
                                    )
                                ) }}
                            </span>

                        @endif

                    </td>

                    <td>
                        {{ \Carbon\Carbon::parse(
                            $rider->created_at
                        )->format('d M Y') }}
                    </td>

                    <td>

                        <button
                            type="button"
                            class="btn btn-outline rider-view-btn"
                            data-id="{{ $rider->id }}"
                        >
                            View Details
                        </button>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7" style="text-align:center;">
                        No riders found.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>


@if($riders->hasPages())

    <div class="pagination-wrap">

        <div class="ridude-pagination">

            {{-- Previous --}}
            @if($riders->onFirstPage())

                <span
                    class="page-btn disabled"
                    aria-disabled="true"
                >
                    ‹
                </span>

            @else

                <a
                    href="{{ $riders->previousPageUrl() }}"
                    class="page-btn"
                    rel="prev"
                >
                    ‹
                </a>

            @endif


            {{-- Page Numbers --}}
            @php
                $currentPage = $riders->currentPage();
                $lastPage = $riders->lastPage();

                $startPage = max(
                    1,
                    $currentPage - 2
                );

                $endPage = min(
                    $lastPage,
                    $currentPage + 2
                );
            @endphp


            {{-- First page --}}
            @if($startPage > 1)

                <a
                    href="{{ $riders->url(1) }}"
                    class="page-btn"
                >
                    1
                </a>

                @if($startPage > 2)

                    <span class="page-dots">
                        ...
                    </span>

                @endif

            @endif


            {{-- Current range --}}
            @for(
                $page = $startPage;
                $page <= $endPage;
                $page++
            )

                @if($page == $currentPage)

                    <span
                        class="page-btn active"
                        aria-current="page"
                    >
                        {{ $page }}
                    </span>

                @else

                    <a
                        href="{{ $riders->url($page) }}"
                        class="page-btn"
                    >
                        {{ $page }}
                    </a>

                @endif

            @endfor


            {{-- Last page --}}
            @if($endPage < $lastPage)

                @if($endPage < ($lastPage - 1))

                    <span class="page-dots">
                        ...
                    </span>

                @endif

                <a
                    href="{{ $riders->url($lastPage) }}"
                    class="page-btn"
                >
                    {{ $lastPage }}
                </a>

            @endif


            {{-- Next --}}
            @if($riders->hasMorePages())

                <a
                    href="{{ $riders->nextPageUrl() }}"
                    class="page-btn"
                    rel="next"
                >
                    ›
                </a>

            @else

                <span
                    class="page-btn disabled"
                    aria-disabled="true"
                >
                    ›
                </span>

            @endif

        </div>

    </div>

@endif