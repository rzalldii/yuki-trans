@extends('layouts.auth')
@section('title', '503 Service Unavailable')
@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h1 class="display-1 fw-bold text-secondary mb-0">503</h1>
                        <h4 class="mb-2">Service Unavailable</h4>
                        <p class="mb-4 text-muted">We're currently undergoing maintenance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection