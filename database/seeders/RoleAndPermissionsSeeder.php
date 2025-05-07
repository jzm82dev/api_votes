<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionsSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['guard_name' => 'api','name' => 'list_league']);
        Permission::create(['guard_name' => 'api','name' => 'register_league']);
        Permission::create(['guard_name' => 'api','name' => 'edit_league']);
        Permission::create(['guard_name' => 'api','name' => 'delete_league']);

        Permission::create(['guard_name' => 'api','name' => 'list_category']);
        Permission::create(['guard_name' => 'api','name' => 'register_category']);
        Permission::create(['guard_name' => 'api','name' => 'edit_category']);
        Permission::create(['guard_name' => 'api','name' => 'delete_category']);

        Permission::create(['guard_name' => 'api','name' => 'list_team']);
        Permission::create(['guard_name' => 'api','name' => 'list_club']);
        Permission::create(['guard_name' => 'api','name' => 'register_club']);
        Permission::create(['guard_name' => 'api','name' => 'edit_club']);
        Permission::create(['guard_name' => 'api','name' => 'view_club']);
        Permission::create(['guard_name' => 'api','name' => 'delete_club']);

        Permission::create(['guard_name' => 'api','name' => 'register_rol']);
        Permission::create(['guard_name' => 'api','name' => 'list_rol']);
        Permission::create(['guard_name' => 'api','name' => 'edit_rol']);
        Permission::create(['guard_name' => 'api','name' => 'delete_rol']);

        Permission::create(['guard_name' => 'api','name' => 'list_monitor']);
        Permission::create(['guard_name' => 'api','name' => 'register_monitor']);
        Permission::create(['guard_name' => 'api','name' => 'edit_monitor']);
        Permission::create(['guard_name' => 'api','name' => 'delete_monitor']);

        Permission::create(['guard_name' => 'api','name' => 'register_staff']);
        Permission::create(['guard_name' => 'api','name' => 'list_staff']);
        Permission::create(['guard_name' => 'api','name' => 'edit_staff']);
        Permission::create(['guard_name' => 'api','name' => 'delete_staff']);

        Permission::create(['guard_name' => 'api','name' => 'list_player']);
        Permission::create(['guard_name' => 'api','name' => 'register_player']);
        Permission::create(['guard_name' => 'api','name' => 'edit_player']);
        Permission::create(['guard_name' => 'api','name' => 'delete_player']);

        Permission::create(['guard_name' => 'api','name' => 'list_journey']);
        Permission::create(['guard_name' => 'api','name' => 'register_journey']);
        Permission::create(['guard_name' => 'api','name' => 'edit_journey']);
        Permission::create(['guard_name' => 'api','name' => 'delete_journey']);


        Permission::create(['guard_name' => 'api','name' => 'view_calendar_reservation']);
        Permission::create(['guard_name' => 'api','name' => 'register_reservation']);
        Permission::create(['guard_name' => 'api','name' => 'delete_reservation']);


        Permission::create(['guard_name' => 'api','name' => 'list_recurrent_reservation']);
        Permission::create(['guard_name' => 'api','name' => 'register_recurrent_reservation']);
        Permission::create(['guard_name' => 'api','name' => 'edit_recurrent_reservation']);
        Permission::create(['guard_name' => 'api','name' => 'delete_recurrent_reservation']);

        Permission::create(['guard_name' => 'api','name' => 'list_tournament']);
        Permission::create(['guard_name' => 'api','name' => 'register_tournament']);
        Permission::create(['guard_name' => 'api','name' => 'edit_tournament']);
        Permission::create(['guard_name' => 'api','name' => 'delete_tournament']);
       

        // create roles and assign existing permissions
        
        // $role1->givePermissionTo('edit articles');
        // $role1->givePermissionTo('delete articles');

        
        // $role2->givePermissionTo('publish articles');
        // $role2->givePermissionTo('unpublish articles');

        $role1 = Role::create(['guard_name' => 'api','name' => 'Super-Admin']);
        $role2 = Role::create(['guard_name' => 'api','name' => 'Admin-Club']);
        $role3 = Role::create(['guard_name' => 'api','name' => 'Standard-User']);
        $role4 = Role::create(['guard_name' => 'api','name' => 'Monitor']);
        
        $role1->givePermissionTo('list_league');
        $role1->givePermissionTo('register_league');
        $role1->givePermissionTo('edit_league');
        $role1->givePermissionTo('delete_league');
        $role1->givePermissionTo('list_category');
        $role1->givePermissionTo('register_category');
        $role1->givePermissionTo('edit_category');
        $role1->givePermissionTo('delete_category');
        $role1->givePermissionTo('list_team');
        $role1->givePermissionTo('register_club');
        $role1->givePermissionTo('edit_club');
        $role1->givePermissionTo('delete_club');
        $role1->givePermissionTo('list_club');

        $role1->givePermissionTo('register_rol');
        $role1->givePermissionTo('list_rol');
        $role1->givePermissionTo('edit_rol');
        $role1->givePermissionTo('delete_rol');
        
        $role1->givePermissionTo('list_staff');
        $role1->givePermissionTo('register_staff');
        $role1->givePermissionTo('edit_staff');
        $role1->givePermissionTo('delete_staff');
        
        $role1->givePermissionTo('list_player');
        $role1->givePermissionTo('register_player');
        $role1->givePermissionTo('edit_player');
        $role1->givePermissionTo('delete_player');
        $role1->givePermissionTo('list_journey');
        $role1->givePermissionTo('register_journey');
        $role1->givePermissionTo('edit_journey');
        $role1->givePermissionTo('delete_journey');

        $role1->givePermissionTo('edit_club');
        $role1->givePermissionTo('view_club');
        $role1->givePermissionTo('view_calendar_reservation');
        $role1->givePermissionTo('register_reservation');
        $role1->givePermissionTo('delete_reservation');
        $role1->givePermissionTo('list_recurrent_reservation');
        $role1->givePermissionTo('register_recurrent_reservation');
        $role1->givePermissionTo('edit_recurrent_reservation');
        $role1->givePermissionTo('delete_recurrent_reservation');
        
        // MONITOR
        $role4->givePermissionTo('view_club');
        $role4->givePermissionTo('view_calendar_reservation');
        $role4->givePermissionTo('register_reservation');
        $role4->givePermissionTo('delete_reservation');
        $role4->givePermissionTo('list_recurrent_reservation');
        $role4->givePermissionTo('register_recurrent_reservation');
        $role4->givePermissionTo('edit_recurrent_reservation');
        $role4->givePermissionTo('delete_recurrent_reservation');
        
        
        // gets all permissions via Gate::before rule; see AuthServiceProvider

        // create demo users
        // $user = \App\Models\User::factory()->create([
        //     'name' => 'Example User',
        //     'email' => 'test@example.com',
        //     'password' => bcrypt('12345678')
        // ]);
        // $user->assignRole($role1);

        // $user = \App\Models\User::factory()->create([
        //     'name' => 'Example Admin User',
        //     'email' => 'admin@example.com',
        //     'password' => bcrypt('12345678')
        // ]);
        // $user->assignRole($role2);

      /*  $user = \App\Models\User::factory()->create([
            'name' => 'Super-Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('12345678')
        ]);
*/

        $user = \App\Models\User::factory()->create([
            'name' => 'Super-Admin User',
            'surname' => 'Surname example',
            'email' => 'admin@example.com',
            'password' => bcrypt('12345678')
        ]);
        $user->assignRole($role1);
    }
}