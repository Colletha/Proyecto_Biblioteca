<?php
namespace App\Models;
use CodeIgniter\Model;
class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $allowedFields = ['usuario', 'password'];
    public function verificarUsuario($usuario, $password)
    {
        return $this->where('usuario', $usuario)
        ->where('password', md5($password))

->first();

}
}