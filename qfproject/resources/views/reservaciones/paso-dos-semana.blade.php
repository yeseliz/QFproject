@extends('layouts.principal')

@section('titulo', 'Reservaciones | Semanales')

@section('encabezado', 'Reservaciones')

@section('subencabezado', 'Semanales')

@section('breadcrumb')
    <li>
        <i class="fa fa-ticket icono-margen"></i>
        Reservaciones
    </li>
    <li class="active">
        Reservaciones semanales
    </li>
@endsection

@section('contenido')    
    <div>
        @if ($errors->has('local_id'))
            <div class="alert alert-error alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">
                        &times;
                    </span>
                </button>
                <h4>
                    <i class="fa fa-ban icon" aria-hidden="true"></i>
                    ¡Error en ingreso de datos!
                </h4>
                <p class="ban">
                    {{ $errors->first('local_id') }}
                </p>
            </div>
        @endif
    </div>
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">
                Paso 2: Locales disponibles
            </h3>
        </div>
        {!! Form::open(['route' => 'reservaciones.paso-tres-semana', 'method' => 'GET']) !!}
            {{ Form::token() }}
            <div class="box-body">
                @foreach ($locales_disponibles as $local)
                    <div class="clearfix">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="img-local pull-left">
                                    <a href="{{ url('images/locales/' . $local->imagen) }}" target="_blanck">
                                        <img src="{{ asset('images/locales/' . $local->imagen) }}" alt="Imagen del local">
                                    </a>
                                </div>
                                <h4 class="encabezado-notificacion">
                                    {{ $local->nombre }}
                                </h4>
                                <p>
                                    <strong>
                                        Capacidad:
                                    </strong>
                                    {{ $local->capacidad }}
                                </p>
                                <div class="seleccionar">
                                    <input type="radio" name="local_id" value="{{ $local->id }}">
                                    <span>
                                        Seleccionar
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach                
                @foreach ($fechas as $fecha)
                    {!! Form::hidden('fechas[]', $fecha) !!}
                @endforeach
                {!! Form::hidden('hora_inicio', $hora_inicio) !!}
                {!! Form::hidden('hora_fin', $hora_fin) !!}
            </div>
            <div class="box-footer">
                <div class="pull-right">
                    <a href="{{ route('reservaciones.paso-uno-semana') }}" class="btn btn-default">
                        Cancelar
                    </a>
                    {!! Form::submit('Siguiente', ['class' => 'btn btn-success']) !!}
                </div>
            </div>
        {!! Form::close() !!}
    </div>
@endsection

@section('sidebar')
    <!-- MENÚ DE HERRAMIENTAS ADICIONALES PARA LAS RESERVACIONES -->
    @include('reservaciones.partials.herramientas')
    <!-- AYUDA DE RESERVACIONES SEMANALES -->
    @include('reservaciones.partials.info-reserva-semanal')
@endsection