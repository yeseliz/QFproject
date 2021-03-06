
@extends('layouts.principal')

@section('titulo', 'Inicio')

@section('encabezado', 'Inicio')

@section('breadcrumb')
    <li class="active">
        <i class="fa fa-home icono-margen"></i>
        Inicio
    </li>
@endsection

@section('contenido')
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">
                Mis reservaciones
            </h3>
        </div>
   
        <div class="box-body">
            {!! Form::open(array('url' => 'home', 'method' => 'GET', 'autocomplete' => 'off', 'role' => 'search')) !!}
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
            @if ($reservaciones->count() > 0)
                @foreach ($reservaciones as $reservacion)
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="btn-group pull-left icono-notificacion">
                                <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-caret-down fa-2x" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li>
                                        <a href="{{ route('reservacion.comprobante', $reservacion->id) }}" target="_blanck">
                                            Comprobante
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('reservaciones.edit', $reservacion->id) }}">
                                            Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('reservaciones.destroy', $reservacion->id) }}" onclick="return confirm('¿Deseas eliminar la reservación?')">
                                            Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="pull-left">
                                <a href="{{ url('images/locales/' . $reservacion->img) }}" target="_blanck">
                                    <img src="{{ asset('images/locales/' . $reservacion->img) }}" class="img-circle img-miniatura" alt="Imagen del local">
                                </a>
                            </div>
                            <span class="text-muted pull-right">
                                <small>
                                    <i class="fa fa-clock-o icono-margen" aria-hidden="true"></i>
                                    {{ $reservacion->created_at }}
                                </small>
                            </span>
                            <h4 class="encabezado-notificacion">
                                Reservación {{ $reservacion->tipo }}
                            </h4>
                            <p>
                                <small>
                                    Código: {{ $reservacion->codigo }}
                                </small>
                            </p>
                            <div class="well well-sm well-panel well-parrafo">
                                @if (Auth::user()->visitante())
                                    <p>
                                        <strong>
                                            Responsable:
                                        </strong>
                                        {{ $reservacion->responsable }}
                                    </p>
                                @endif
                                <p>
                                    <strong>
                                        Local:
                                    </strong>
                                    {{ $reservacion->local }}
                                </p>
                                <p>
                                    <strong>
                                        Fecha y hora:
                                    </strong>
                                    {{ \Carbon\Carbon::parse($reservacion->fecha)->format('d/m/Y') }} &nbsp;&#8226;&nbsp; {{ \Carbon\Carbon::parse($reservacion->hora_inicio)->format('h:i A') }} - {{ \Carbon\Carbon::parse($reservacion->hora_fin)->format('h:i A') }}
                                </p>
                                <p>
                                    <strong>
                                        Asignatura:
                                    </strong>
                                    {{ $reservacion->asignatura }} &nbsp;-&nbsp; ({{ $reservacion->cod }})
                                </p>
                                <p>
                                    <strong>
                                        Actividad:
                                    </strong>
                                    {{ $reservacion->actividad }}
                                    @if ($reservacion->tema != null)
                                        &nbsp;-&nbsp; {{ $reservacion->tema }}
                                    @endif
                                </p>
                            </div>
                            
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center">
                    <i class="fa fa-frown-o fa-5x verde-claro" aria-hidden="true"></i>
                    <h4 class="verde-claro">
                        No se encontraron reservaciones
                    </h4>
                </div>
            @endif
        </div>
        <div class="box-footer">
            <div class="text-center">
                {!! $reservaciones->render() !!}
            </div>
        </div>
    </div>
@endsection

@section('sidebar')
    <!-- PANEL DEL PERFIL DE USUARIO -->
    @include('layouts.perfil')
    <!-- AYUDA DE INICIO -->
    @include('layouts.partials.info-inicio')
@endsection