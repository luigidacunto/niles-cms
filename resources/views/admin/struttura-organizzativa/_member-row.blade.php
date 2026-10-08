<div class="member-row border rounded p-3 mb-3">
    <input type="hidden" name="members[{{ $i }}][id]" value="{{ $member?->id }}">
    <input type="hidden" name="members[{{ $i }}][remove]" value="">

    <div class="form-row">
        <div class="form-group col-md-4">
            <label>Ruolo</label>
            <input name="members[{{ $i }}][role_label]" value="{{ $member?->role_label }}" class="form-control"
                   placeholder="es. Consigliere, Consigliere Giovani, Referente…">
        </div>
        <div class="form-group col-md-6">
            <label>Nome</label>
            <input name="members[{{ $i }}][name]" value="{{ $member?->name }}" class="form-control">
        </div>
        <div class="form-group col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger btn-block remove-member-row">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>

    <div class="form-group">
        <label>Presentazione</label>
        <textarea name="members[{{ $i }}][bio]" class="form-control bio-field" rows="5" data-max="1000">{{ $member?->bio }}</textarea>
        <small class="text-muted"><span class="bio-count">0</span>/1000 circa</small>
    </div>

    <div class="form-group mb-0">
        <label>Foto <small class="text-muted">(facoltativa)</small></label>
        @if ($member?->photo_path)
            <div class="mb-1">
                <img src="{{ $member->photoUrl }}" alt="" style="height:60px" class="rounded">
                <label class="ml-2 mb-0"><input type="checkbox" name="members[{{ $i }}][remove_photo]" value="1"> togli foto</label>
            </div>
        @endif
        <input type="file" name="members[{{ $i }}][photo]" accept="image/*" class="form-control-file">
    </div>
</div>
