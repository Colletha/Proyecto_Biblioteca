<?php
namespace App\Controllers;
use App\Models\UsuarioModel;
class Login extends BaseController
{
public function index()
{
return view('login');
}
public function autenticar()
{
$usuario = $this->request->getPost('usuario');
$password = $this->request->getPost('password');
$usuarioModel = new UsuarioModel();
$datosUsuario = $usuarioModel->verificarUsuario($usuario,
$password);
if ($datosUsuario) {
session()->set([
'usuario' => $datosUsuario['usuario'],
'logged_in' => true
]);
return redirect()->to('/panel');
} else {
return redirect()->back()->with('error', 'Usuario o
contraseña incorrectos');
}
}
public function panel()
{
if (!session()->get('logged_in')) {
return redirect()->to('/');
}
return view('panel');
}
public function salir()
{
session()->destroy();
return redirect()->to('/');
}
}