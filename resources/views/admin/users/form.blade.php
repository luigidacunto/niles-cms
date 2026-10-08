@extends('adminlte::page')

@php $editing = $admin->exists; @endphp

@section('title', $editing ? 'Modifica utente' : 'Nuovo utente')

@section('content_header')
    <h1>{{ $editing ? 'Modifica utente' : 'Nuovo utente' }}</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.users.update', $admin) : route('admin.users.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nome</label>
                        <input name="name" value="{{ old('name', $admin->name) }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input name="email" type="email" value="{{ old('email', $admin->email) }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-row align-items-center">
                    <div class="form-group col-md-6 mb-md-0">
                        <label>Ruolo</label>
                        @if ($admin->protected)
                            <input type="text" class="form-control" value="Admin (protetto)" disabled>
                            <small class="text-muted d-block">Questo è l'amministratore protetto: ruolo, permessi e categorie non sono modificabili (email e nome sì).</small>
                        @else
                            <select id="role" name="role" class="form-control">
                                <option value="editor" @selected(old('role', $admin->role) === 'editor')>Editor</option>
                                <option value="admin" @selected(old('role', $admin->role) === 'admin')>Admin (accesso completo)</option>
                            </select>
                        @endif
                    </div>
                    <div class="form-group col-md-6 mb-md-0">
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" class="custom-control-input" id="active" name="active" value="1"
                                   @checked(old('active', $admin->active ?? true))>
                            <label class="custom-control-label" for="active">Account attivo</label>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="custom-control custom-switch mb-2">
                    <input type="hidden" name="password_login_enabled" value="0">
                    <input type="checkbox" class="custom-control-input" id="password_login_enabled" name="password_login_enabled" value="1"
                           @checked(old('password_login_enabled', $admin->password_login_enabled))>
                    <label class="custom-control-label" for="password_login_enabled">
                        Abilita login con password (di norma si entra solo con codice via email)
                    </label>
                </div>
                <div class="form-group col-md-6 pl-0">
                    <label>Password {{ $editing ? '(lascia vuoto per non cambiarla)' : '(richiesta solo se il login con password è abilitato)' }}</label>
                    <input name="password" type="password" class="form-control">
                </div>

                <hr>

                <h6>Permessi <small class="text-muted">— solo per ruolo Editor, un Admin li ha tutti</small></h6>
                <table class="table table-sm w-auto">
                    <thead>
                        <tr>
                            <th>Sezione</th>
                            <th>Lettura</th>
                            <th>Scrittura</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resources as $key => $label)
                            @php $current = old("permissions.$key", $admin->permissions[$key] ?? []) @endphp
                            @if (in_array($key, \App\Models\Admin::COMBINED_PERMISSIONS, true))
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td colspan="2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input permission-check"
                                                   id="perm-{{ $key }}" name="permissions[{{ $key }}][]" value="write"
                                                   @checked(in_array('write', $current))>
                                            <label class="custom-control-label" for="perm-{{ $key }}">Lettura e scrittura</label>
                                        </div>
                                    </td>
                                </tr>
                            @else
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input permission-check"
                                               id="perm-{{ $key }}-read" name="permissions[{{ $key }}][]" value="read"
                                               @checked(in_array('read', $current))>
                                        <label class="custom-control-label" for="perm-{{ $key }}-read"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input permission-check"
                                               id="perm-{{ $key }}-write" name="permissions[{{ $key }}][]" value="write"
                                               @checked(in_array('write', $current))>
                                        <label class="custom-control-label" for="perm-{{ $key }}-write"></label>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>

                <hr>

                <h6>Categorie di competenza <small class="text-muted">— solo per ruolo Editor, un Admin le ha tutte</small></h6>
                <div class="custom-control custom-switch mb-3">
                    <input type="hidden" name="all_categories" value="0">
                    <input type="checkbox" class="custom-control-input" id="all_categories" name="all_categories" value="1"
                           @checked(old('all_categories', $admin->all_categories))>
                    <label class="custom-control-label" for="all_categories"><strong>Tutte le categorie</strong></label>
                </div>
                @if ($categories->isEmpty())
                    <p class="text-muted">Nessuna categoria creata ancora.</p>
                @else
                    @php $selected = old('categories', $admin->categories->pluck('id')->all()) @endphp
                    <div style="column-count: 3; column-gap: 1.5rem;">
                        @foreach ($categories as $category)
                            <div class="custom-control custom-switch mb-2" style="break-inside: avoid;">
                                <input type="checkbox" class="custom-control-input category-check{{ $category->slug === 'news' ? ' category-check-news' : '' }}"
                                       name="categories[]" value="{{ $category->id }}"
                                       id="category-{{ $category->id }}" @checked(in_array($category->id, $selected))>
                                <label class="custom-control-label" for="category-{{ $category->id }}">{{ $category->name }}</label>
                            </div>
                        @endforeach
                        <small class="text-muted d-block mt-2">
                            "Notizie" è sempre inclusa per un Editor: è la categoria generica minima per poter pubblicare.
                        </small>
                    </div>
                @endif
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Crea utente' }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var roleSelect = document.getElementById('role');
            var permissionInputs = document.querySelectorAll('.permission-check');
            var categoryInputs = document.querySelectorAll('.category-check');
            var newsCategoryInput = document.querySelector('.category-check-news');
            var allCategories = document.getElementById('all_categories');

            // "Notizie" è la categoria minima obbligatoria per un Editor (forzata anche lato server,
            // questo è solo per non mostrare in UI qualcosa che poi verrebbe ignorato al salvataggio).
            function forceNewsCategory() {
                if (newsCategoryInput) {
                    newsCategoryInput.checked = true;
                    newsCategoryInput.disabled = true;
                }
            }

            function lockAsAdmin() {
                permissionInputs.forEach(function (el) {
                    el.checked = true;
                    el.disabled = true;
                });
                categoryInputs.forEach(function (el) {
                    el.checked = true;
                    el.disabled = true;
                });
                allCategories.checked = true;
                allCategories.disabled = true;
            }

            function unlockAsEditor(reset) {
                permissionInputs.forEach(function (el) {
                    el.disabled = false;
                    if (reset) el.checked = false;
                });
                categoryInputs.forEach(function (el) {
                    el.disabled = false;
                    if (reset) el.checked = false;
                });
                allCategories.disabled = false;
                if (reset) allCategories.checked = false;
                forceNewsCategory();
            }

            function syncAllCategoriesSwitch() {
                if (allCategories.disabled || categoryInputs.length === 0) return;
                var allChecked = Array.prototype.every.call(categoryInputs, function (el) {
                    return el.checked;
                });
                allCategories.checked = allChecked;
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', function () {
                    if (roleSelect.value === 'admin') {
                        lockAsAdmin();
                    } else {
                        unlockAsEditor(true);
                    }
                });
            }

            categoryInputs.forEach(function (el) {
                el.addEventListener('change', syncAllCategoriesSwitch);
            });

            allCategories.addEventListener('change', function () {
                if (allCategories.disabled) return;
                categoryInputs.forEach(function (el) {
                    // "Notizie" resta sempre spuntata (obbligatoria minima), spegnendo "Tutte" si
                    // spengono solo le altre così l'editor le riseleziona una per una.
                    if (el === newsCategoryInput) return;
                    el.checked = allCategories.checked;
                });
            });

            // Utente protetto: niente <select> ruolo (sempre admin lato server), stesso blocco visivo
            // "tutti i permessi/tutte le categorie" già usato per un admin normale.
            if (!roleSelect || roleSelect.value === 'admin') {
                lockAsAdmin();
            } else {
                forceNewsCategory();
                // Se l'editor ha già "Tutte le categorie" attivo (es. in modifica), le singole caselle
                // vanno mostrate spuntate di conseguenza — altrimenti sembrano tutte spente anche se lo
                // switch generale dice il contrario (il pivot admin_category può essere vuoto quando
                // all_categories=true, dato che canManageCategory() lo ignora in quel caso).
                if (allCategories.checked) {
                    categoryInputs.forEach(function (el) { el.checked = true; });
                }
            }
        });
    </script>
@stop
