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
use Laracasts\Flash\Flash;
use qfproject\Http\Requests\UserRequest;
use qfproject\Reservacion;
use qfproject\User;

class UserController extends Controller
{
    /**
     * ---------------------------------------------------------------------------
     * Crea una nueva instancia de controlador.
     *
     * @return void
     * ---------------------------------------------------------------------------
     */

    public function __construct()
    {
        Carbon::setLocale('es');
    }

    /**
     * ---------------------------------------------------------------------------
     * Muestra una lista de usuarios.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function index(Request $request)
    {
        if ($request) {
            $query = trim($request->get('searchText'));

            $users = User::where('name', 'like', '%' . $query . '%')
                ->orWhere('lastname', 'like', '%' . $query . '%')
                ->orWhere('username', 'like', '%' . $query . '%')
                ->orWhere('tipo', 'like', '%' . $query . '%')
                ->orderBy('name', 'asc')
                ->paginate(25);

            return view('administracion.users.index')
                ->with('users', $users)
                ->with('searchText', $query);
        }
    }

    /**
     * ---------------------------------------------------------------------------
     * Muestra el formulario para crear un nuevo usuario.
     * 
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function create()
    {
        return view('administracion.users.create');
    }

    /**
     * ---------------------------------------------------------------------------
     * Almacena un usuario recién creado en la base de datos.
     * 
     * @param  \qfproject\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function store(UserRequest $request)
    {
        /**
         * Almacenando imagen.
         */

        if ($request->file('imagen')) {
            $file = $request->file('imagen');

            $nombre = 'user_' . time() . '.' . $file->getClientOriginalExtension();

            $path = public_path() . '/images/users/';

            $file->move($path, $nombre);
        }

        $user = new User($request->all());


        if ($user->imagen) {
            $user->imagen = $nombre;
        }

        $user->save();

        flash('
            <h4>
                <i class="fa fa-check icon" aria-hidden="true"></i>
                ¡Bien hecho!
            </h4>
            <p class="check">
                El usuario "' . $user->name . '" se ha guardado correctamente.
            </p>
        ')
            ->success()
            ->important();

        return redirect()->route('users.index');
    }

    /**
     * ---------------------------------------------------------------------------
     * Muestra el usuario especificado.
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            abort(404);
        }

        $hoy = Carbon::now();

        $reservaciones = Reservacion::where('user_id', '=', $user->id)
            ->where('fecha', '>=', Carbon::parse($hoy)->format('Y-m-d'))
            ->orderBy('fecha', 'desc')
            ->paginate(5);

        $reservaciones->each(function($reservaciones) {
            $reservaciones->user;
            $reservaciones->local;
            $reservaciones->asignatura;
            $reservaciones->actividad;
        });

        return view('administracion.users.show')
            ->with('user', $user)
            ->with('reservaciones', $reservaciones);
    }

    /**
     * ---------------------------------------------------------------------------
     * Muestra el formulario para editar el usuario especificado.
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function edit($id)
    {
        $user = User::find($id);

        if (!$user) {
            abort(404);
        }

        return view('administracion.users.edit')->with('user', $user);
    }

    /**
     * ---------------------------------------------------------------------------
     * Actualiza el usuario especificado en la base de datos.
     * 
     * @param  \qfproject\Http\Requests\UserRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function update(UserRequest $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            abort(404);
        }

        /**
         * Almacenando nueva imagen.
         */

        if ($request->file('imagen')) {
            $file = $request->file('imagen');

            $nombre = 'user_' . time() . '.' . $file->getClientOriginalExtension();

            $path = public_path() . '/images/users/';

            $file->move($path, $nombre);

            /**
             * Eliminando imagen anterior.
             */

            if (\File::exists($path . $user->imagen) && $user->imagen != 'user_default.jpg') {
                \File::delete($path . $user->imagen);
            }

            /**
             * Guardando nueva imagen.
             */

            $user->imagen = $nombre;
        }

        $user->name = $request->get('name');
        $user->lastname = $request->get('lastname');
        $user->username = $request->get('username');
        $user->email = $request->get('email');
        $user->password = $request->get('password');
        $user->tipo = $request->get('tipo');

        $user->save();

        flash('
            <h4>
                <i class="fa fa-check icon" aria-hidden="true"></i>
                ¡Bien hecho!
            </h4>
            <p class="check">
                El usuario "' . $user->name . '" se ha editado correctamente.
            </p>
        ')
            ->success()
            ->important();

        return redirect()->route('users.index');
    }

    /**
     * ---------------------------------------------------------------------------
     * Elimina la actividad especificada de la base de datos.
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * ---------------------------------------------------------------------------
     */

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            abort(404);
        }

        $user->delete();

        /**
         * Eliminando imagen.
         */

        $path = public_path() . '/images/users/';

        if (\File::exists($path . $user->imagen) && $user->imagen != 'user_default.jpg') {
            \File::delete($path . $user->imagen);
        }

        flash('
            <h4>
                <i class="fa fa-check icon" aria-hidden="true"></i>
                ¡Bien hecho!
            </h4>
            <p class="check">
                El usuario ha sido eliminado correctamente.
            </p>
        ')
            ->success()
            ->important();

        return redirect()->route('users.index');
    }
}
