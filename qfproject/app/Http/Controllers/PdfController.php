<?php

namespace qfproject\Http\Controllers;

use Illuminate\Http\Request;

/**
 * ---------------------------------------------------------------------------
 * Clases agregadas.
 * ---------------------------------------------------------------------------
 */

use DB;
use Carbon\Carbon;
use PDF;
use qfproject\Actividad;
use qfproject\Asignatura;
use qfproject\Local;
use qfproject\Reservacion;
use qfproject\User;

class PdfController extends Controller
{
    /************************ COMPROBANTE DE RESERVACIÓN ************************/

    /**
     * ---------------------------------------------------------------------------
     * Genera un comprobante con todos los datos de la reservación.
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function generarComprobante($id)
    {
        $reservacion = Reservacion::find($id);

        if (!$reservacion) {
            abort(404);
        }

        $reservacion->fecha = Carbon::parse($reservacion->fecha)->format('d/m/Y');
        $reservacion->hora_inicio = Carbon::parse($reservacion->hora_inicio)->format('h:i A');
        $reservacion->hora_fin = Carbon::parse($reservacion->hora_fin)->format('h:i A');

        $hoy = Carbon::now()->format('d/m/y h:i A');

        $pdf = \PDF::loadView('reportes.comprobante', ['reservacion' => $reservacion, 'hoy' => $hoy]);

        return $pdf->download('comprobante_' . $reservacion->codigo . '.pdf');
    }

    /**
     * ---------------------------------------------------------------------------
     * Genera un comprobante con todos los datos de las reservaciones.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function descargarComprobante(Request $request)
    {
        $reservaciones = []; // Arreglo con las reservaciones a mostrar en el comprobante.

        foreach ($request->reservaciones as $id) {
            $reservacion = Reservacion::find($id);

            array_push($reservaciones, $reservacion);
        }

        $hoy = Carbon::now()->format('d/m/y h:i A');

        if (count($reservaciones) > 1) {
            $pdf = \PDF::loadView('reportes.comprobante-multiple', ['reservaciones' => $reservaciones, 'hoy' => $hoy]);
        } else {
            $pdf = \PDF::loadView('reportes.comprobante', ['reservacion' => $reservaciones[0], 'hoy' => $hoy]); 
        }

        return $pdf->download('comprobante.pdf');
    }

    /**
     * ---------------------------------------------------------------------------
     * Muestra el formulario para descargar un comprobante.
     * 
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function formularComprobante()
    {
        $asignaturas = Asignatura::orderBy('nombre')->pluck('nombre', 'id');

        $actividades = Actividad::orderBy('nombre')->pluck('nombre', 'id');

        return view('reportes.exportar-comprobante')
            ->with('asignaturas', $asignaturas)
            ->with('actividades', $actividades);
    }

    /**
     * ---------------------------------------------------------------------------
     * Genera un comprobante con todos los datos de las reservaciones.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function exportarComprobante(Request $request)
    {
        /**
         * Validando datos de entrada.
         */

        $this->validate(request(), [
            'fecha'         => 'required|date|after_or_equal:' . Carbon::now()->format('Y-m-d'),
            'hora_inicio'   => 'required|after_or_equal:07:00:00|before_or_equal:17:00:00',
            'hora_fin'      => 'required|after:hora_inicio|before_or_equal:18:00:00',
            'asignatura_id' => 'required',
            'actividad_id'  => 'required'
        ]);

        /**
         * Convirtiendo fecha al formato Y-m-d y horas al formato H:i:s.
         */
        
        $fecha = Carbon::parse($request->fecha)->format('Y-m-d');
        $hora_inicio = Carbon::parse($request->hora_inicio)->format('H:i:s');
        $hora_fin = Carbon::parse($request->hora_fin)->format('H:i:s');

        $reservaciones = Reservacion::where('user_id', '=', \Auth::user()->id)
            ->where('fecha', '=', $fecha)
            ->where('hora_inicio', '=', $hora_inicio)
            ->where('hora_fin', '=', $hora_fin)
            ->where('asignatura_id', '=', $request->asignatura_id)
            ->where('actividad_id', '=', $request->actividad_id)
            ->get();

        $hoy = Carbon::now()->format('d/m/y h:i A');

        if (count($reservaciones) == 0) {
            flash('
                <h4>
                    <i class="fa fa-exclamation-triangle icon" aria-hidden="true"></i>
                    ¡No hay ninguna reservación!
                </h4>
                <p class="exclamation-triangle">
                    Para los datos ingresados no se encontró ninguna reservación registrada.
                </p>
            ')
                ->warning()
                ->important();

            return back();
        } elseif (count($reservaciones) > 1) {
            $pdf = \PDF::loadView('reportes.comprobante-multiple', ['reservaciones' => $reservaciones, 'hoy' => $hoy]);
        } else {
            $pdf = \PDF::loadView('reportes.comprobante', ['reservacion' => $reservaciones[0], 'hoy' => $hoy]); 
        }

        return $pdf->download('comprobante.pdf');
    }

    /**
     * Reservas del día siguiente
     *
     */
   public function proximasReservas(){

    $manana = new Carbon('tomorrow');

    $reservaciones = Reservacion::where('fecha', '=', $manana)
        ->orderBy('local_id', 'asc')
        ->get();

    $pdf=PDF::loadView('reportes.reservacion-lista', ['reservaciones'=>$reservaciones, 'manana' => $manana])->setPaper('letter', 'landscape');
    return $pdf ->download('proximasReservas.pdf');

   }

   /****************************** HORARIO SEMANAL ******************************/

   /**
     * ---------------------------------------------------------------------------
     * Muestra el formulario para generar horarios.
     * 
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function exportarHorarios()
    {
        $locales = Local::orderBy('nombre')->pluck('nombre', 'id');

        return view('reportes.exportar-horarios')->with('locales', $locales);
    }

    /**
     * ---------------------------------------------------------------------------
     * Genera un horario semanal de las reservaciones programadas para un local.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function generarHorarios(Request $request)
    {
        /**
         * Validando datos de entrada.
         */

        $this->validate(request(), [
            'fecha' => 'required|date',
            'local_id' => 'required'
        ]);

        /**
         * Validando que la fecha ingresada sea un día lunes.
         */

        $fecha = Carbon::parse($request->fecha)->format('Y-m-d');

        if (date('N', strtotime($fecha)) != 1) {
            flash('
                <h4>
                    <i class="fa fa-ban icon" aria-hidden="true"></i>
                    ¡Error en ingreso de datos!
                </h4>
                <p class="ban">
                    Debes seleccionar un día lunes.
                </p>
            ')
                ->error()
                ->important();
            
            return back();
        }

        /**
         * Obteniendo fechas y horas.
         */

        $f = explode('-', $fecha);
        $f_carbon = Carbon::create($f[0], $f[1], $f[2], 0); // Fecha inicial como instancia de Carbon.

        $fechas = []; // Arreglo con todas las fechas.

        for ($i = 0; $i < 6; $i++) { 
            $f_almacenar = Carbon::parse($f_carbon)->format('Y-m-d');

            array_push($fechas, $f_almacenar);

            $f_carbon = $f_carbon->addDays(1);
        }

        $horas = ['07:00:00', '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00']; // Arreglo con todas las horas.

        /**
         * Obteniendo arreglo con las reservaciones para generar los horarios.
         */

        $tabla = []; // Arreglo con las reservaciones del horario de un local.

        foreach ($horas as $hora) {
            $fila = []; // Arreglo con las reservaciones de una determinada hora.

            foreach ($fechas as $fecha) {
                $reservacion = Reservacion::where('local_id', '=', $request->local_id)
                    ->where('fecha', '=', $fecha)
                    ->where('hora_inicio', '<=', $hora)
                    ->where('hora_fin', '>', $hora)
                    ->first();

                array_push($fila, $reservacion);
            }

            array_push($tabla, $fila);
        }

        /**
         * Generando PDF.
         */

        $local = Local::find($request->local_id);

        $fecha_inicio = Carbon::parse($fechas[0])->format('d/m/Y');

        $fecha_fin = Carbon::parse($fechas[5])->format('d/m/Y');

        $pdf = \PDF::loadView('reportes.horarios', ['tabla' => $tabla, 'local' => $local, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin])
            ->setPaper('letter', 'landscape');

        return $pdf->download('horarios.pdf');
    }

    /****************** LISTADO DE RESERVACIONES POR ACTIVIDAD ******************/

    /**
     * ---------------------------------------------------------------------------
     * Muestra el formulario para generar el listado de reservaciones por
     * actividad.
     * 
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function exportarListaActividad()
    {
        $asignaturas = Asignatura::orderBy('nombre')->pluck('nombre', 'id');

        $actividades = Actividad::orderBy('nombre')->pluck('nombre', 'id');

        return view('reportes.exportar-lista-actividad')
            ->with('asignaturas', $asignaturas)
            ->with('actividades', $actividades);
    }

    /**
     * ---------------------------------------------------------------------------
     * Genera un listado con las reservaciones de una asignatura por actividad.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function generarListaActividad(Request $request)
    {
        /**
         * Validando datos de entrada.
         */

        $this->validate(request(), [
            'fecha' => 'required',
            'asignatura_id' => 'required',
            'actividad_id' => 'required'
        ]);

        /**
         * Obteniendo datos solicitados por el usuario.
         */

        $fecha = explode(' - ', $request->fecha);

        $fecha[0] = Carbon::parse($fecha[0])->format('Y-m-d');
        $fecha[1] = Carbon::parse($fecha[1])->format('Y-m-d');

        $horas = ['07:00:00', '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00']; // Arreglo con todas las horas.

        $reservaciones = Reservacion::where('asignatura_id', '=', $request->asignatura_id)
            ->where('actividad_id', '=', $request->actividad_id)
            ->where('fecha', '>=', $fecha[0])
            ->where('fecha', '<=', $fecha[1])
            ->orderBy('fecha', 'asc')
            ->get();

        /**
         * Generando PDF.
         */

        $asignatura = Asignatura::find($request->asignatura_id);

        $actividad = Actividad::find($request->actividad_id);

        $fecha_inicio = Carbon::parse($fecha[0])->format('d/m/Y');

        $fecha_fin = Carbon::parse($fecha[1])->format('d/m/Y');

        $pdf = \PDF::loadView('reportes.lista-actividad', ['reservaciones' => $reservaciones, 'asignatura' => $asignatura, 'actividad' => $actividad, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]);

        return $pdf->download('listado_actividad.pdf');
    }

    /******************* LISTADO DE RESERVACIONES POR USUARIO *******************/

    /**
     * ---------------------------------------------------------------------------
     * Muestra el formulario para generar el listado de reservaciones por
     * usuario.
     * 
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function exportarListaUsuario()
    {
        $users = User::orderBy('name')->pluck('name', 'id');

        return view('reportes.exportar-lista-usuario')->with('users', $users);
    }

    /**
     * ---------------------------------------------------------------------------
     * Genera un listado con las reservaciones de un usuario.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function generarListaUsuario(Request $request)
    {
        /**
         * Validando datos de entrada.
         */

        $this->validate(request(), [
            'fecha' => 'required',
            'user_id' => 'required'
        ]);

        /**
         * Obteniendo datos solicitados por el usuario.
         */

        $fecha = explode(' - ', $request->fecha);

        $fecha[0] = Carbon::parse($fecha[0])->format('Y-m-d');
        $fecha[1] = Carbon::parse($fecha[1])->format('Y-m-d');

        $reservaciones = Reservacion::where('user_id', '=', $request->user_id)
            ->where('fecha', '>=', $fecha[0])
            ->where('fecha', '<=', $fecha[1])
            ->orderBy('fecha', 'asc')
            ->get();

        /**
         * Generando PDF.
         */

        $user = User::find($request->user_id);

        $fecha_inicio = Carbon::parse($fecha[0])->format('d/m/Y');

        $fecha_fin = Carbon::parse($fecha[1])->format('d/m/Y');

        $pdf = \PDF::loadView('reportes.lista-usuario', ['reservaciones' => $reservaciones, 'user' => $user, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin])->setPaper('letter', 'landscape');

        return $pdf->download('listado_usuario.pdf');
    }

    /*************************** OCUPACIÓN DE LOCALES ***************************/

    /**
     * ---------------------------------------------------------------------------
     * Muestra el formulario para generar el reporte del porcentaje de ocupación
     * de un local en cada bloque de clases.
     * 
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function exportarReporteOcupacion()
    {
        $locales = Local::orderBy('nombre')->pluck('nombre', 'id');

        return view('reportes.exportar-reporte-ocupacion')->with('locales', $locales);
    }

    /**
     * ---------------------------------------------------------------------------
     * Genera un reporte del porcentaje de ocupación de un local en cada bloque
     * de clases.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function generarReporteOcupacion(Request $request)
    {
        /**
         * Validando datos de entrada.
         */

        $this->validate(request(), [
            'fecha' => 'required',
            'local_id' => 'required'
        ]);

        /**
         * Obteniendo datos solicitados por el usuario.
         */

        $fecha = explode(' - ', $request->fecha);

        $fecha[0] = Carbon::parse($fecha[0])->format('Y-m-d');
        $fecha[1] = Carbon::parse($fecha[1])->format('Y-m-d');

        /**
         * Obteniendo diferencia de días entre los limites del rango de fechas.
         */

        $f0 = explode('-', $fecha[0]);
        $f1 = explode('-', $fecha[1]);

        $f0_carbon = Carbon::create($f0[0], $f0[1], $f0[2], 0);
        $f1_carbon = Carbon::create($f1[0], $f1[1], $f1[2], 0);

        $diferencia = $f1_carbon->diffInDays($f0_carbon) + 1;

        /**
         * Obteniendo arreglo con los porcentajes de ocupación del local por hora.
         */

        $horas = ['07:00:00', '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00']; // Arreglo con todas las horas.

        $porcentajes = []; // Arreglo con los porcentajes de ocupación del local por hora.

        foreach ($horas as $hora) {
            $reservaciones = Reservacion::where('local_id', '=', $request->local_id)
                ->where('fecha', '>=', $fecha[0])
                ->where('fecha', '<=', $fecha[1])
                ->where('hora_inicio', '<=', $hora)
                ->where('hora_fin', '>', $hora)
                ->count();

            $porcentaje = $reservaciones * 100.00 / $diferencia;

            array_push($porcentajes, $porcentaje);
        }

        /**
         * Generando PDF.
         */

        $fecha_inicio = Carbon::parse($fecha[0])->format('d/m/Y');

        $fecha_fin = Carbon::parse($fecha[1])->format('d/m/Y');

        $local = Local::find($request->local_id);

        $pdf = \PDF::loadView('reportes.reporte-ocupacion', ['horas' => $horas, 'porcentajes' => $porcentajes, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'local' => $local]);

        return $pdf->download('reporte_ocupacion_del_local.pdf');
    }
}
