<?php

namespace App\Pclap;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Modelo de la base de PCLAP. Finanzas solo lee: guardar o borrar lanza una excepción
 * (y además el usuario MySQL de la conexión "pclap" solo tiene permiso SELECT).
 */
abstract class ModeloPclap extends Model
{
    protected $connection = 'pclap';

    public $timestamps = false;

    public function save(array $options = []): bool
    {
        throw new LogicException('PCLAP es de solo lectura: Finanzas no guarda cambios en su base.');
    }

    public function delete(): ?bool
    {
        throw new LogicException('PCLAP es de solo lectura: Finanzas no borra registros de su base.');
    }
}
