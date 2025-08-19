<?php 
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UsuarioModel;

class Login extends Controller {

    public function index() {
        // Muestra la primera pantalla "¿Eres...?"
        return view('rol');
    }

    public function showLogin($rol) {
        // Muestra la pantalla de login según rol elegido
        return view('login', ['rol' => $rol]);
    }

    public function auth() {
        $session = session();
        $model = new UsuarioModel();

        $rol = $this->request->getPost('rol');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $model->where('email', $email)->where('rol', $rol)->first();

        if($user) {
            if(password_verify($password, $user['password'])) {
                $session->set([
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'rol' => $user['rol'],
                    'logged_in' => true
                ]);

                // Redirección según rol
                if($rol == 'admin') {
                    return redirect()->to('/admin-index');
                } elseif($rol == 'bibliotecario') {
                    return redirect()->to('/bibliotecario-index');
                } else {
                    return redirect()->to('/alumno-index');
                }

            } else {
                return redirect()->back()->with('error','Contraseña incorrecta');
            }
        } else {
            return redirect()->back()->with('error','Usuario o rol inválido');
        }
    }

    public function logout() {
        session()->destroy();
        return redirect()->to('/login');
    }
}
