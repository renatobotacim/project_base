<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
   //     \App\Models\User::factory(10)->create();
        // \App\Models\Category::factory(10)->create();
   //     \App\Models\Address::factory(10)->create();
    //    \App\Models\Owner::factory(10)->create();
     //   \App\Models\Producer::factory(10)->create();
        // \App\Models\Maps::factory(10)->create();
        // \App\Models\Sector::factory(10)->create();
        // \App\Models\Event::factory(200)->create();
        // \App\Models\TicketEvent::factory(10)->create();
        // \App\Models\Batch::factory(10)->create();
        // \App\Models\Ticket::factory(10)->create();
        // \App\Models\Sale::factory(100)->create();

        DB::table('categories')->insert([
            ['name' => 'Shows', 'status' => 'active', 'image' => null],
            ['name' => 'Stand Up Comedy', 'status' => 'active', 'image' => null],
            ['name' => 'Teatros', 'status' => 'active', 'image' => null],
            ['name' => 'Infantil', 'status' => 'active', 'image' => null],
            ['name' => 'Baladas', 'status' => 'active', 'image' => null],
            ['name' => 'Gastronomia', 'status' => 'active', 'image' => null],
            ['name' => 'Cursos e Workshops', 'status' => 'active', 'image' => null],
            ['name' => 'Congressos', 'status' => 'active', 'image' => null],
            ['name' => 'Exposições', 'status' => 'active', 'image' => null],
            ['name' => 'Réveillon', 'status' => 'active', 'image' => null],
        ]);


        $roles = DB::table('roles_has_users')->get();
        $arrRoles =[];
        foreach ($roles as $role){
            $arrRoles[] = ['role_id'=>$role->role_id,'user_id'=>$role->user_id];
        }

        DB::table('roles')->delete();
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Acessar dados gerais dos eventos', 'status' => 'active', 'level' => 2],
            ['id' => 2, 'name' => 'Criar novos eventos', 'status' => 'active', 'level' => 2],
            ['id' => 3, 'name' => 'Editar eventos cadastrados', 'status' => 'active', 'level' => 2],
            ['id' => 4, 'name' => 'Acessar permissões', 'status' => 'active', 'level' => 2],
            ['id' => 5, 'name' => 'Acessar carteira digital', 'status' => 'active', 'level' => 2],
            ['id' => 6, 'name' => 'Emissão de cortesias', 'status' => 'active', 'level' => 2],
            ['id' => 7, 'name' => 'Acesso Total', 'status' => 'active', 'level' => 3],
            ['id' => 8, 'name' => 'Produtores', 'status' => 'active', 'level' => 3],
            ['id' => 9, 'name' => 'Eventos', 'status' => 'active', 'level' => 3],
            ['id' => 10, 'name' => 'Saques', 'status' => 'active', 'level' => 3],
            ['id' => 11, 'name' => 'Permissões', 'status' => 'active', 'level' => 3],
            ['id' => 12, 'name' => 'Clientes', 'status' => 'active', 'level' => 3],
            ['id' => 13, 'name' => 'Validação de Ingressos', 'status' => 'active', 'level' => 2],
            ['id' => 14, 'name' => 'Acessar Movimentação', 'status' => 'active', 'level' => 2],
            ['id' => 15, 'name' => 'Acessar Mapa', 'status' => 'active', 'level' => 2],
            ['id' => 16, 'name' => 'Acessar Local', 'status' => 'active', 'level' => 2],
            ['id' => 17, 'name' => 'Acessar Eventos', 'status' => 'active', 'level' => 2]
        ]);

        DB::table('roles_has_users')->insert($arrRoles);

    }
}
