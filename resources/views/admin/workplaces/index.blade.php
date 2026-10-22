@extends('layouts.app')

@section('title', 'Workplaces')

@section('page-name', 'workplaces')

@section('page-title', 'Workplaces')

@section('page-subtitle')
Companies and their branch addresses
@endsection


@section('content')

<style>
.pagination-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.ridude-pagination {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.ridude-pagination .page-btn {
    min-width: 38px;
    height: 38px;
    padding: 0 11px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    border: 1px solid #dfe3e8;
    border-radius: 8px;

    background: #fff;
    color: #1f2937;

    font-size: 14px;
    font-weight: 600;

    text-decoration: none;
}

.ridude-pagination .page-btn.active {
    background: #111827;
    border-color: #111827;
    color: #fff;
}

.ridude-pagination .page-btn.disabled {
    opacity: .4;
    cursor: not-allowed;
}

.page-dots {
    padding: 0 5px;
    color: #6b7280;
}
</style>


<div class="page-head">

    <div></div>


    <button
        type="button"
        class="btn"
        id="add-workplace-btn"
        style="margin-bottom:24px"
    >
        <svg
            aria-hidden="true"
            class="icon"
            viewBox="0 0 24 24"
        >
            <path
                d="M12 5v14M5 12h14"
            ></path>
        </svg>

        Add company
    </button>

</div>


<div
    class="grid grid-2"
    id="workplace-list"
>

    @forelse($workplaces as $workplace)

        @php
            $companyBranches =
                $branches[
                    $workplace->id
                ] ?? collect();
        @endphp


        <div
            class="card reveal is-visible"
            data-workplace-id="{{
                $workplace->id
            }}"
        >

            <div class="company-head">

                <div
                    class="stat-icon tone-blue"
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

                </div>


                <div>

                    <h2>
                        {{ $workplace->company_name }}
                    </h2>

                    <span
                        class="small muted branch-count"
                    >
                        {{ $companyBranches->count() }}
                        {{
                            $companyBranches->count() === 1
                                ? 'branch'
                                : 'branches'
                        }}
                    </span>

                </div>

            </div>


            <div class="branch-list">

                @foreach(
                    $companyBranches
                    as $branch
                )

                    <div
                        class="branch"
                        data-branch-id="{{
                            $branch->id
                        }}"
                    >

                        <svg
                            aria-hidden="true"
                            class="icon"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"
                            ></path>

                            <circle
                                cx="12"
                                cy="10"
                                r="3"
                            ></circle>
                        </svg>


                        <span>
                            {{ $branch->address }}
                        </span>


                        <button
                            type="button"
                            class="delete-branch-btn"
                            data-id="{{
                                $branch->id
                            }}"
                            aria-label="Remove branch"
                        >
                            <svg
                                aria-hidden="true"
                                class="icon"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"
                                ></path>
                            </svg>
                        </button>

                    </div>

                @endforeach

            </div>


            <div
                class="inline-branch-wrapper"
                data-id="{{ $workplace->id }}"
            >

                <div
                    class="inline-branch-inputs"
                    data-id="{{ $workplace->id }}"
                >

                    <div class="inline-branch-row">

                        <input
                            class="input branch-address-input"
                            type="text"
                            placeholder="New branch address"
                            autocomplete="off"
                        />

                        <button
                            type="button"
                            class="btn btn-outline add-more-inline-branch"
                            data-id="{{ $workplace->id }}"
                        >
                            +
                        </button>

                    </div>

                </div>


                <div style="margin-top:10px;">

                    <button
                        type="button"
                        class="btn save-inline-branches"
                        data-id="{{ $workplace->id }}"
                    >
                        Add Branches
                    </button>

                </div>

            </div>

        </div>


    @empty

        <div class="card">

            <p class="muted">
                No workplaces found.
            </p>

        </div>

    @endforelse

</div>


{{-- Pagination --}}
@if($workplaces->hasPages())

    <div class="pagination-wrap">

        <div class="ridude-pagination">

            @if($workplaces->onFirstPage())

                <span
                    class="page-btn disabled"
                >
                    ‹
                </span>

            @else

                <a
                    href="{{
                        $workplaces
                            ->previousPageUrl()
                    }}"
                    class="page-btn"
                >
                    ‹
                </a>

            @endif


            @php

                $current =
                    $workplaces->currentPage();

                $last =
                    $workplaces->lastPage();

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
                    href="{{
                        $workplaces->url(1)
                    }}"
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

                @if($page == $current)

                    <span
                        class="page-btn active"
                    >
                        {{ $page }}
                    </span>

                @else

                    <a
                        href="{{
                            $workplaces
                                ->url($page)
                        }}"
                        class="page-btn"
                    >
                        {{ $page }}
                    </a>

                @endif

            @endfor


            @if($end < $last)

                @if(
                    $end <
                    ($last - 1)
                )

                    <span class="page-dots">
                        ...
                    </span>

                @endif


                <a
                    href="{{
                        $workplaces
                            ->url($last)
                    }}"
                    class="page-btn"
                >
                    {{ $last }}
                </a>

            @endif


            @if(
                $workplaces->hasMorePages()
            )

                <a
                    href="{{
                        $workplaces
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


{{-- ========================================================
    ADD COMPANY MODAL
======================================================== --}}


<div
    class="modal-backdrop"
    id="workplace-modal"
>
    <div class="modal">

        <h2>Add company</h2>

        <div class="field">
            <label for="company-name">
                Company name
            </label>

            <input
                class="input"
                id="company-name"
                autocomplete="off"
            >
        </div>

        <div class="field">
            <label for="company-branch">
                First branch address
            </label>

            <input
                class="input"
                id="company-branch"
                autocomplete="off"
            >
        </div>

        <div class="modal-actions">

            <button
                type="button"
                class="btn btn-outline"
                id="workplace-cancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn"
                id="workplace-save"
            >
                Save
            </button>

        </div>

    </div>
</div>

@endsection