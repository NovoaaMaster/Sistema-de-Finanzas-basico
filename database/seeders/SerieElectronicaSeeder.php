<?php

namespace Database\Seeders;

use App\Models\SerieElectronica;
use Illuminate\Database\Seeder;

/** Series iniciales: factura F001, boleta B001 y sus notas de crédito FC01 y BC01. */
class SerieElectronicaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['01', 'F001'], ['03', 'B001'], ['07', 'FC01'], ['07', 'BC01']] as [$tipo, $serie]) {
            SerieElectronica::firstOrCreate(['serie' => $serie], ['tipo_doc' => $tipo, 'correlativo' => 0]);
        }
    }
}
