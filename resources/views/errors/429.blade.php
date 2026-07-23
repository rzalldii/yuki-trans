@extends('layouts.auth')
@section('title', '429 Too Many Requests')
@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h1 class="display-1 fw-bold text-warning mb-0">429</h1>
                        <h4 class="mb-2">Too Many Requests</h4>
                        <p class="mb-4 text-muted">You've made too many requests.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection