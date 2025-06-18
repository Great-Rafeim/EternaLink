<x-layouts.funeral>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0 text-white">Agent Management</h2>
                <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#inviteAgentModal">
                    <i class="bi bi-envelope-plus"></i> Invite New Agent
                </a>


        </div>

        <div class="card bg-dark text-white shadow-sm">
            <div class="card-body">
                @if($agents->isEmpty())
                    <div class="alert alert-info mb-0">
                        No agents registered yet.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Assigned Clients</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agents as $agent)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $agent->name }}</td>
                                        <td>{{ $agent->email }}</td>
                                        <td>
                                            @if($agent->pivot_status === 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Placeholder, replace with actual count --}}
                                            <span class="badge bg-info">{{ $agent->clients_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <a href="#" 
                                            class="btn btn-sm btn-primary view-agent-btn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewAgentModal"
                                            data-agent-name="{{ $agent->name }}"
                                            data-agent-email="{{ $agent->email }}"
                                            data-agent-status="{{ $agent->pivot_status }}"
                                            {{-- Add other data attributes as needed --}}
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="#" 
                                            class="btn btn-sm btn-warning edit-agent-btn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editAgentModal"
                                            data-agent-id="{{ $agent->id }}"
                                            data-agent-name="{{ $agent->name }}"
                                            data-agent-status="{{ $agent->pivot_status }}">
                                                <i class="bi bi-pencil"></i>
                                            </a>


                                            <form action="{{ route('funeral.agents.destroy', $agent->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this agent?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>



<!-- Invite Agent Modal -->
<div class="modal fade" id="inviteAgentModal" tabindex="-1" aria-labelledby="inviteAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('funeral.agents.invite') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="inviteAgentModalLabel">Invite New Agent</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-dark text-white">
        <div class="mb-3">
          <label for="agent-email" class="form-label">Email address</label>
          <input type="email" class="form-control" id="agent-email" name="email" required>
        </div>
      </div>
      <div class="modal-footer bg-dark">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning">Send Invitation</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Agent Modal -->
<div class="modal fade" id="editAgentModal" tabindex="-1" aria-labelledby="editAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="editAgentForm">
      @csrf
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="editAgentModalLabel">Edit Agent</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="agent_id" id="editAgentId">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" id="editAgentName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-control" name="status" id="editAgentStatus" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Update Agent</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- View Agent Modal -->
<div class="modal fade" id="viewAgentModal" tabindex="-1" aria-labelledby="viewAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
      <div class="modal-content bg-dark text-white">
          <div class="modal-header">
              <h5 class="modal-title" id="viewAgentModalLabel">Agent Details</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <div class="mb-2">
                  <strong>Name:</strong> <span id="viewAgentName"></span>
              </div>
              <div class="mb-2">
                  <strong>Email:</strong> <span id="viewAgentEmail"></span>
              </div>
              <div class="mb-2">
                  <strong>Status:</strong> <span id="viewAgentStatus"></span>
              </div>
              {{-- Add more fields as needed --}}
          </div>
      </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.view-agent-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('viewAgentName').textContent = this.getAttribute('data-agent-name');
            document.getElementById('viewAgentEmail').textContent = this.getAttribute('data-agent-email');
            document.getElementById('viewAgentStatus').textContent = this.getAttribute('data-agent-status');
            // Add more as needed
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editAgentModal = document.getElementById('editAgentModal');
    var editAgentForm = document.getElementById('editAgentForm');

    // On click edit, fill modal with agent info and set form action
    document.querySelectorAll('.edit-agent-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            let agentId = this.getAttribute('data-agent-id');
            let agentName = this.getAttribute('data-agent-name');
            let agentStatus = this.getAttribute('data-agent-status');

            document.getElementById('editAgentId').value = agentId;
            document.getElementById('editAgentName').value = agentName;
            document.getElementById('editAgentStatus').value = agentStatus;

            // Set the form action dynamically
            editAgentForm.action = "{{ url('/funeral/agents') }}/" + agentId + "/edit";
        });
    });
});
</script>


</x-layouts.funeral>
