<!-- MENÚ DE HERRAMIENTAS ADICIONALES PARA LAS RESERVACIONES -->
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">
            Herramientas adicionales
        </h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding">
        <ul class="nav nav-pills nav-stacked">
            <li>
                <a href="{{ route('reservaciones.exportar') }}">
                    Exportar reservaciones a Excel
                </a>
            </li>
            <li>
                <a href="{{ route('reservaciones.importar') }}">
                    Importar reservaciones desde Excel
                </a>
            </li>
            <li>
                <a href="{{ route('reservaciones.paso-uno') }}">
                    Reservación individual
                </a>
            </li>
            <li>
                <a href="{{ route('reservaciones.crear-ciclo') }}">
                    Reservación por ciclo
                </a>
            </li>
        </ul>
    </div>
</div>