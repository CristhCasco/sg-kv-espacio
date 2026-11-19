<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateTiRole extends Migration
{
    /**
     * Run the migrations.
     *
     * Usuarios, Roles, Permisos, Asignar, Denominaciones y Configuración
     * exigen el rol TI, pero algunas bases no lo tienen y nadie podía
     * entrar. Se crea solo si falta; asignarlo a un usuario sigue siendo
     * manual porque el login cambia en cada sistema.
     *
     * @return void
     */
    public function up()
    {
        Role::firstOrCreate(['name' => 'TI', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * No se borra el rol: puede tener usuarios asignados y quitarlo los
     * dejaría sin acceso a la administración.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
