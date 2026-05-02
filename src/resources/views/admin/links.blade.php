@extends('web::layouts.grids.12')

@section('title', 'Temporary Links')
@section('page_header', 'Mumble Connector')
@section('page_description', 'Temporary Connection Links')

@section('full')

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Create Temporary Link</h3>
            </div>
            <form action="{{ route('mumble::admin.links.add') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="display_name">Guest Name</label>
                        <input type="text" name="display_name" id="display_name" class="form-control" placeholder="e.g. Friendly Guest" required maxlength="50">
                        <small class="form-text text-muted">This will be used to generate their Mumble display name.</small>
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (hours)</label>
                        <input type="number" name="duration" id="duration" class="form-control" value="24" min="1" max="168" required>
                        <small class="form-text text-muted">How long the link should remain valid (1-168 hours).</small>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-link mr-1"></i>Generate Link
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-link mr-2"></i>Active Temporary Links</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Display Name</th>
                            <th>Username</th>
                            <th>Expires</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($links as $link)
                        <tr class="{{ $link->isExpired() ? 'text-muted opacity-50' : '' }}">
                            <td>
                                <strong>{{ $link->display_name }}</strong>
                                @if($link->isExpired())
                                    <span class="badge badge-secondary ml-1">Expired</span>
                                @endif
                            </td>
                            <td><code>{{ $link->mumble_username }}</code></td>
                            <td>
                                <span title="{{ $link->expires_at }}">
                                    {{ $link->expires_at->diffForHumans() }}
                                </span>
                            </td>
                            <td>{{ $link->creator->name ?? 'Unknown' }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info copy-link" 
                                            data-url="{{ route('mumble::guest.link', $link->token) }}"
                                            title="Copy link to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <a href="{{ route('mumble::guest.link', $link->token) }}" target="_blank" class="btn btn-sm btn-secondary" title="View link page">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <form action="{{ route('mumble::admin.links.delete', $link->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete link" onclick="return confirm('Delete this link and remove the guest from Mumble?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No temporary links found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($links->hasPages())
            <div class="card-footer">
                {{ $links->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@stop

@push('javascript')
<script>
    $(function() {
        $('.copy-link').click(function() {
            var url = $(this).data('url');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(url).select();
            document.execCommand("copy");
            $temp.remove();
            
            $(this).removeClass('btn-info').addClass('btn-success');
            var $icon = $(this).find('i');
            $icon.removeClass('fa-copy').addClass('fa-check');
            
            setTimeout(() => {
                $(this).removeClass('btn-success').addClass('btn-info');
                $icon.removeClass('fa-check').addClass('fa-copy');
            }, 2000);
        });
    });
</script>
@endpush
