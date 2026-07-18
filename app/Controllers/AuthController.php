<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;

class AuthController extends Controller {
    public function showLogin(Request $request): void {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', [], 'auth');
    }

    public function login(Request $request): void {
        if (Auth::check()) {
            $this->redirect('/');
        }

        $data = $request->all();
        $validator = new Validator();
        $validator->validate($data, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!empty($validator->getErrors())) {
            Session::flash('errors', $validator->getErrors());
            Session::flash('old', $data);
            $this->redirect('/login');
        }

        if (Auth::attempt($data['email'], $data['password'])) {
            Session::flash('success', 'Welcome back!');
            $this->redirect('/');
        } else {
            Session::flash('error', 'Invalid email or password.');
            Session::flash('old', $data);
            $this->redirect('/login');
        }
    }

    public function logout(Request $request): void {
        Auth::logout();
        Session::flash('success', 'Logged out successfully!');
        $this->redirect('/login');
    }
}
