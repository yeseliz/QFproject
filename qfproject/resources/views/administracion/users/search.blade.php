{!! Form::open(array('url' => 'administracion/users', 'method' => 'GET', 'autocomplete' => 'off', 'role' => 'search')) !!}
    <span class="pull-left boton-margen">
        <a href="{{ route('users.create') }}" class="btn btn-success">
            <i class="fa fa-plus icono-margen"></i>
            Nuevo usuario
        </a>
    </span>
    <div class="form-group">
        <div class="input-group">
            <input type="text" class="form-control" name="searchText", placeholder="Buscar", value="{{ $searchText }}"></input>
            <span class="input-group-btn">
                <button type="submit" class="btn btn-default">
                    <i class="fa fa-search" aria-hidden="true"></i>
                </button>
            </span>
        </div>
    </div>
{!! Form::close() !!}