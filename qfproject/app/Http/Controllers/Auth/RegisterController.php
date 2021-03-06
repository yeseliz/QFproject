<?php

namespace qfproject\Http\Controllers\Auth;

use qfproject\User;
use qfproject\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'     => 'required|string|max:190',
            'lastname' => 'max:190|required',
            'username'    => 'required|string|max:190|unique:users',
            'email'    => 'required|string|email|max:190|unique:users',
            'password' => 'required|string|min:6|max:190|confirmed',
            'tipo'     => 'max:190|required',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \qfproject\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name'     => $data['name'],
            'lastname' => $data['lastname'],
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'tipo'     => $data['tipo'],
            'imagen'   => 'user_default.jpg'
        ]);
    }
}
