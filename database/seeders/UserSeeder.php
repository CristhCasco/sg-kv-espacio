<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 🔹 1. Crear o actualizar los usuarios base
        $tiUser = User::updateOrCreate(
            ['user' => 'ti'],
            [
                'name' => 'Soporte TI',
                'last_name' => 'Cris Info',
                'ci' => '1000000',
                'password' => bcrypt('123'),
                'email' => 'ti@crisinfo.com',
                'phone' => '0983668000',
                'address' => 'CDE',
                'status' => 'ACTIVO',
                'image' => 'https://dummyimage.com/200x150/007bff/fff'
            ]
        );

        $adminUser = User::updateOrCreate(
            ['user' => 'admin'],
            [
                'name' => 'Administrador',
                'last_name' => 'Casco',
                'ci' => '6560887',
                'password' => bcrypt('123'),
                'email' => 'admin@crisinfo.com',
                'phone' => '0983668960',
                'address' => 'CDE',
                'status' => 'ACTIVO',
                'image' => 'https://dummyimage.com/200x150/5c5756/fff'
            ]
        );

        $normalUser = User::updateOrCreate(
            ['user' => 'user'],
            [
                'name' => 'Usuario',
                'last_name' => 'Estándar',
                'ci' => '1234567',
                'password' => bcrypt('123'),
                'email' => 'user@crisinfo.com',
                'phone' => '0983111000',
                'address' => 'CDE',
                'status' => 'ACTIVO',
                'image' => 'https://dummyimage.com/200x150/aaaaaa/fff'
            ]
        );

        // 🔹 2. Crear todos los permisos del sistema
        $permissions = [
            // Categorías
            'Category_Index', 'Category_Create', 'Category_Update', 'Category_Destroy', 'Category_Search',

            // Productos
            'Product_Index', 'Product_Create', 'Product_Update', 'Product_Destroy', 'Product_Search',

            // Ventas / Compras / Créditos
            'Sale_Index', 'Purchase_Index', 'Credit_Index',

            // Gestión de usuarios / roles / permisos / asignaciones
            'User_Index', 'Role_Index', 'Permission_Index', 'Assign_Index',

            // Caja / Reportes / Inventario
            'Cashout_Index', 'Report_Index', 'Inventory_Index',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 🔹 3. Crear roles
        $tiRole = Role::firstOrCreate(['name' => 'TI', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // 🔹 4. Asignar permisos por rol

        // TI → Todos los permisos
        $tiRole->syncPermissions(Permission::all());

        // Admin → todos menos los de gestión de usuarios/roles/permisos/asignar
        $restrictedForAdmin = ['User_Index', 'Role_Index', 'Permission_Index', 'Assign_Index'];
        $adminPermissions = Permission::whereNotIn('name', $restrictedForAdmin)->get();
        $adminRole->syncPermissions($adminPermissions);

        // User → por ahora igual que Admin
        $userRole->syncPermissions($adminPermissions);

        // 🔹 5. Asignar roles a los usuarios
        $tiUser->syncRoles([$tiRole]);
        $adminUser->syncRoles([$adminRole]);
        $normalUser->syncRoles([$userRole]);

        // 🔹 6. Guardar etiqueta informativa (solo visual)
        $tiUser->profile = $tiRole->name;
        $adminUser->profile = $adminRole->name;
        $normalUser->profile = $userRole->name;

        $tiUser->save();
        $adminUser->save();
        $normalUser->save();
    }
}
