<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignTiRoleToTiUser extends Migration
{
    /**
     * Run the migrations.
     *
     * El usuario de soporte (login "ti") necesita el rol TI para entrar a
     * Configuración, Usuarios, Roles y Permisos. Se le agrega sin quitarle
     * los roles que ya tenga; si el usuario no existe no se hace nada.
     *
     * @return void
     */
    public function up()
    {
        $user = User::where('user', 'ti')->first();
        if (! $user) {
            return;
        }

        Role::firstOrCreate(['name' => 'TI', 'guard_name' => 'web']);

        if (! $user->hasRole('TI')) {
            $user->assignRole('TI');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * No se quita el rol: el usuario podría tenerlo desde antes y quitarlo
     * lo dejaría sin acceso a la administración.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
