<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
    {
		Schema::create('clients', function(Blueprint $table)
		{
			$table->increments('id');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
			$table->timestamps();
		});

        DB::table('clients')->insert([
            'email' => 'client@example.com',
            'username' => 'client',
            'password' => Hash::make('secret'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('clients')->insert([
            'email' => 'anotherclient@example.com',
            'username' => 'secondclient',
            'password' => Hash::make('test'),
            'remember_token' => 'a_test_remember_token',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
	}

}
