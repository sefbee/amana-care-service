<form action="{{ route('record-conflict-blocks.revoke', $block) }}" method="POST" class="d-flex gap-2 align-items-center">
    @csrf
    <input type="text" name="revocation_reason" class="form-control form-control-sm" minlength="10" maxlength="500" placeholder="Reason to remove block" required aria-label="Reason for revoking conflict block">
    <button class="btn btn-sm btn-light-danger text-nowrap" type="submit">Revoke</button>
</form>
