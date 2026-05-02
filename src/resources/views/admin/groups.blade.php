@extends('web::layouts.grids.12')

@section('title', trans('mumble::mumble.groups'))
@section('page_header', trans('mumble::mumble.title'))
@section('page_description', trans('mumble::mumble.groups'))

@section('full')

<div class="row">
    <!-- Add Mapping -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Group Mapping</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('mumble::admin.groups.add') }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <label for="seat_type">Mapping Type</label>
                        <select class="form-control" name="seat_type" id="seat_type" onchange="updateIdentifierField()">
                            <option value="squad">Squad</option>
                            <option value="role">Role</option>
                            <option value="corporation">Corporation</option>
                            <option value="alliance">Alliance</option>
                        </select>
                    </div>

                    <div class="form-group" id="squad_selector">
                        <label for="squad_id">Select Squad</label>
                        <select class="form-control" name="seat_identifier" id="squad_id">
                            @foreach($squads as $squad)
                                <option value="{{ $squad->id }}">{{ $squad->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group d-none" id="text_identifier">
                        <label for="seat_identifier_text">Identifier</label>
                        <input type="text" class="form-control" name="seat_identifier_text" id="seat_identifier_text"
                            placeholder="Role name, Corp ID, or Alliance ID">
                        <small class="form-text text-muted">
                            For roles: enter the role name<br>
                            For corps/alliances: enter the ID number
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="mumble_group">Mumble Group Name</label>
                        <input type="text" class="form-control" name="mumble_group" id="mumble_group" required
                            placeholder="e.g., leadership, fc, directors">
                    </div>

                    <div class="form-group">
                        <label for="name_tag">Name Tag <small class="text-muted">(optional)</small></label>
                        <input type="text" class="form-control" name="name_tag" id="name_tag"
                            placeholder="e.g., [FC] or  | Director">
                        <small class="form-text text-muted">
                            Appended to the username for matching users.<br>
                            E.g. tag <code> [FC]</code> → <em>[TRD] Pilot Name [FC]</em>
                        </small>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-plus mr-2"></i>Add Mapping
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current Mappings -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Current Mappings</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>SeAT Source</th>
                            <th>Mumble Group</th>
                            <th>Name Tag</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings as $mapping)
                        <tr>
                            <td>
                                <span class="badge badge-{{ $mapping->seat_type == 'role' ? 'primary' : ($mapping->seat_type == 'squad' ? 'success' : ($mapping->seat_type == 'corporation' ? 'info' : 'warning')) }}">
                                    {{ ucfirst($mapping->seat_type) }}
                                </span>
                            </td>
                            <td>{{ $mapping->seat_name ?? $mapping->seat_identifier }}</td>
                            <td><code>{{ $mapping->mumble_group }}</code></td>
                            <td>
                                @if($mapping->name_tag)
                                    <code class="text-success">{{ $mapping->name_tag }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($mapping->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('mumble::admin.groups.delete', $mapping->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No group mappings configured</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@stop

@push('javascript')
<script>
function updateIdentifierField() {
    const type = document.getElementById('seat_type').value;
    const squadSelector = document.getElementById('squad_selector');
    const textIdentifier = document.getElementById('text_identifier');
    
    if (type === 'squad') {
        squadSelector.classList.remove('d-none');
        textIdentifier.classList.add('d-none');
        document.getElementById('squad_id').name = 'seat_identifier';
        document.getElementById('seat_identifier_text').name = '_seat_identifier_text';
    } else {
        squadSelector.classList.add('d-none');
        textIdentifier.classList.remove('d-none');
        document.getElementById('squad_id').name = '_squad_id';
        document.getElementById('seat_identifier_text').name = 'seat_identifier';
    }
}
</script>
@endpush
