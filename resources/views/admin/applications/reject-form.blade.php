{{-- Reject, available at every open status of an application (admin.applications.show). --}}
<form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="mt-6 border-t border-line pt-6">
    @csrf
    <label for="rejection_reason" class="field-label">Reason for rejection</label>
    <textarea id="rejection_reason" name="rejection_reason" rows="2"
              class="field-input @error('rejection_reason') field-input-error @enderror"
              @error('rejection_reason') aria-invalid="true" aria-describedby="rejection_reason-error" @enderror
              placeholder="What the organization needs to correct">{{ old('rejection_reason') }}</textarea>
    @error('rejection_reason')
        <p id="rejection_reason-error" class="field-error">{{ $message }}</p>
    @enderror
    <button type="submit" class="btn-danger mt-3 w-full">Reject application</button>
</form>
