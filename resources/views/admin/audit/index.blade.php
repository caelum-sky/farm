@extends('layouts.app')

@section('title', 'Audit Trail')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Audit trail</span>
                <h1>Admin actions, exports, moderation, and settings changes.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="{{ route('admin.export', 'audit') }}">Export CSV</a>
                <a class="button primary" href="{{ route('admin.dashboard') }}">Overview</a>
            </div>
        </header>

        <x-admin.navigation />

        <form class="admin-filter compact-filter" method="GET" action="{{ route('admin.audit.index') }}">
            <label>
                <span>Search audit trail</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Action, admin, model, summary">
            </label>
            <button class="button primary" type="submit">Search</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Admin</th>
                        <th>Subject</th>
                        <th>Summary</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td data-label="Action"><span class="status-pill is-good">{{ str_replace('_', ' ', $log->action) }}</span></td>
                            <td data-label="Admin">{{ $log->user?->email ?: 'System' }}</td>
                            <td data-label="Subject">
                                <strong>{{ class_basename($log->subject_type ?: 'System') }}</strong>
                                <span>{{ $log->subject_id ? '#'.$log->subject_id : 'Global' }}</span>
                            </td>
                            <td data-label="Summary">{{ $log->summary }}</td>
                            <td data-label="When">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No audit entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $logs->links() }}</div>
    </section>
@endsection
