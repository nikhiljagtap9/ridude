@php

    $initials = collect(
        preg_split(
            '/\s+/',
            trim($rider->full_name)
        )
    )
    ->filter()
    ->take(2)
    ->map(function ($part) {

        return strtoupper(
            substr(
                $part,
                0,
                1
            )
        );

    })
    ->implode('');


    $statusLabel = match(
        $rider->status
    ) {
        'under_review' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => ucfirst(
            $rider->status
        ),
    };


    $statusClass = match(
        $rider->status
    ) {
        'approved' => 'tone-green',
        'rejected' => 'tone-red',
        default => 'tone-amber',
    };

@endphp


{{-- ============================================================
    HEADER
============================================================ --}}

<div class="rider-detail-header">

    <div class="rider-detail-avatar">

        {{ $initials }}

    </div>


    <div>

        <div class="rider-detail-name">

            <h2>
                {{ $rider->full_name }}
            </h2>


            @if($rider->status !== 'approved')

                <span
                    class="badge {{ $statusClass }}"
                >
                    {{ $statusLabel }}
                </span>

            @endif

        </div>

    </div>

</div>


<p
    class="muted"
    style="margin-top:0;"
>
    Full registration details submitted by the rider.
</p>



<div class="rider-detail-grid">


    {{-- ======================================================
        ACCOUNT
    ======================================================= --}}

    <div class="rider-detail-section">

        <div class="rider-detail-title">
            Account
        </div>


        <div class="rider-info-grid cols-3">


            {{-- Email --}}
            <div class="rider-info-item">

                <span class="rider-info-label">

                    ✉ Email

                </span>

                <span class="rider-info-value">

                    {{ $rider->email }}

                </span>

            </div>



            {{-- Phone --}}
            <div class="rider-info-item">

                <span class="rider-info-label">

                    ☎ Phone

                </span>

                <span class="rider-info-value">

                    {{ $rider->country_code }}
                    {{ $rider->phone_number }}

                </span>

            </div>



            {{-- Joined --}}
            <div class="rider-info-item">

                <span class="rider-info-label">

                    ◫ Joined

                </span>

                <span class="rider-info-value">

                    {{ \Carbon\Carbon::parse(
                        $rider->created_at
                    )->format('Y-m-d') }}

                </span>

            </div>



            {{-- Workplace --}}
            <div class="rider-info-item">

                <span class="rider-info-label">

                    ▣ Workplace

                </span>

                <span class="rider-info-value">

                    {{
                        $rider->workplace_name
                        ?: '-'
                    }}

                </span>

            </div>


        </div>

    </div>



    {{-- ======================================================
        HOME ADDRESS / PERSONAL
    ======================================================= --}}

    <div class="rider-detail-section">

        <div class="rider-detail-title">

            Home Address

        </div>


        <div class="rider-info-grid">


            {{-- Full Name --}}
            <div class="rider-info-item">

                <span class="rider-info-label">

                    ♙ Full Name

                </span>

                <span class="rider-info-value">

                    {{ $rider->full_name }}

                </span>

            </div>



            {{-- Address --}}
            <div
                class="rider-info-item"
                style="grid-column:1/-1;"
            >

                <span class="rider-info-label">

                    ⌂ Address

                </span>

                <span class="rider-info-value">

                    {{
                        $rider->address
                        ?: '-'
                    }}

                </span>

            </div>


        </div>

    </div>



    {{-- ======================================================
        DOCUMENTS
    ======================================================= --}}

    <div class="rider-detail-section">

        <div class="rider-detail-title">
            Documents
        </div>


        <div class="rider-document-badges">


            @if(
                !empty(
                    $rider->profile_photo
                )
            )

                <span
                    class="rider-document-chip uploaded"
                >
                    ▣ Profile Photo · Uploaded
                </span>

            @else

                <span
                    class="rider-document-chip missing"
                >
                    ▣ Profile Photo · Missing
                </span>

            @endif


        </div>

    </div>



    {{-- ======================================================
        RIDE ACTIVITY
    ======================================================= --}}

    <div class="rider-detail-section">

        <div class="rider-detail-title">
            Ride Activity
        </div>


        <div class="rider-info-grid">


            <div class="rider-info-item">

                <span class="rider-info-label">

                    ◉ Trips Taken

                </span>

                <span
                    class="rider-info-value"
                    style="
                        font-size:16px;
                    "
                >

                    {{
                        number_format(
                            $rider->total_trips
                            ?? 0
                        )
                    }}

                </span>

            </div>


        </div>

    </div>


</div>