@extends('web::layouts.grids.12')

@section('title', trans('mumble::mumble.logs'))
@section('page_header', trans('mumble::mumble.title'))
@section('page_description', trans('mumble::mumble.logs'))

@section('full')

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Sync Logs</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Groups Changed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>{{ ucfirst($log->action) }}</td>
                    <td>
                        <span class="badge {{ $log->status_badge }}">{{ ucfirst($log->status) }}</span>
                    </td>
                    <td>{{ $log->message ?? '-' }}</td>
                    <td>
                        @if($log->old_groups || $log->new_groups)
                            @php
                                $added = array_diff($log->new_groups ?? [], $log->old_groups ?? []);
                                $removed = array_diff($log->old_groups ?? [], $log->new_groups ?? []);
                            @endphp
                            @if(!empty($added))
                                <span class="text-success">+{{ implode(', ', $added) }}</span>
                            @endif
                            @if(!empty($removed))
                                <span class="text-danger">-{{ implode(', ', $removed) }}</span>
                            @endif
                            @if(empty($added) && empty($removed))
                                <span class="text-muted">No changes</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No logs yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
    @endif
</div>

@stop
