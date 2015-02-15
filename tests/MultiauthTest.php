<?php

use Orchestra\Testbench\TestCase;

class MultiauthTest extends TestCase
{

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        /*
         * create an artisan object
         */
        $artisan = $this->app->make('artisan');

        /*
         * Run migrations for test database
         */
        $artisan->call('migrate', [
            '--database' => 'testing',
            '--path'     => '../tests/migrations',
        ]);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        $app['path.base'] = realpath(__DIR__ . '/../src');

        /*
         * Database configuration
         */
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        /*
         * Multiauth configuration
         */
        $app['config']->set('auth.driver', 'multiauth');
        $app['config']->set('auth.multiauth', [
            'identifier_key' => 'identifier',
            'entities'       => [
                [
                    'type'       => 'client',
                    'table'      => 'clients',
                    'model'      => 'Client',
                    'identifier' => 'username'
                ],
                [
                    'type'       => 'admin',
                    'table'      => 'admins',
                    'model'      => 'Admin',
                    'identifier' => 'email'
                ]
            ]
        ]);
    }

    protected function getPackageProviders()
    {
        return ['Bogardo\Multiauth\MultiauthServiceProvider'];
    }

    /** @test */
    public function serviceprovider_provides_multiauthservice()
    {
        $provides = (new Bogardo\Multiauth\MultiauthServiceProvider($this->app))->provides();
        $this->assertEquals(['multiauth.service'], $provides);
    }

    /** @test */
    public function serviceprovider_registers_the_multiauth_service()
    {
        $service = $this->app->make('multiauth.service');
        $this->assertSame('Bogardo\Multiauth\Service', get_class($service));
    }

    /** @test */
    public function serviceprovider_registers_the_multiauth_auth_driver()
    {
        $provider = $this->app->make('auth')->driver()->getProvider();
        $this->assertSame('Bogardo\Multiauth\User\UserProvider', get_class($provider));
    }

    /** @test */
    public function serviceprovider_registers_a_new_validation_rule()
    {
        $validator = $this->app->make('validator');

        $validation = $validator->make(['key' => 'value'], ['key' => 'multiAuthUnique']);;
        $this->assertTrue($validation->passes());

        $validation = $validator->make(['key' => 'secondclient'], ['key' => 'multiAuthUnique:client,2']);;
        $this->assertTrue($validation->passes());

        $validation = $validator->make(['key' => 'client'], ['key' => 'multiAuthUnique']);;
        $this->assertFalse($validation->passes());
    }

    /** @test */
    public function the_service_creates_a_collection_of_all_registered_entities()
    {
        /** @var Bogardo\Multiauth\Service $service */
        $service = $this->app->make('multiauth.service');

        $collection = $service->getEntities();
        $this->assertInstanceOf('Bogardo\Multiauth\Entity\EntityCollection', $collection);

        $this->assertSame(2, $collection->count());

        $expected = [$this->getClientEntity(), $this->getAdminEntity()];

        $this->assertEquals($expected, $collection->toArray());
    }

    /** @test */
    public function the_service_gets_an_entity_by_type()
    {
        /** @var Bogardo\Multiauth\Service $service */
        $service = $this->app->make('multiauth.service');

        $expectedEntity = $this->getAdminEntity();

        $entity = $service->getEntityByType("admin");

        $this->assertEquals($expectedEntity, $entity);
    }

    /** @test */
    public function the_service_gets_a_user_by_its_credentials()
    {
        /** @var Bogardo\Multiauth\Service $service */
        $service = $this->app->make('multiauth.service');

        $client = $service->getUserByCredentials(['identifier' => 'secondclient', 'password' => 'test']);
        $this->assertInstanceOf('Client', $client);
        $this->assertEquals($client->id, 2);
    }

    /** @test */
    public function the_service_gets_a_user_by_its_credentials_with_the_default_email_identifier()
    {
        /** @var Bogardo\Multiauth\Service $service */
        $service = $this->app->make('multiauth.service');

        $client = $service->getUserByCredentials(['email' => 'admin@example.com', 'password' => 'test']);
        $this->assertInstanceOf('Admin', $client);
        $this->assertEquals($client->id, 1);
    }

    /** @test */
    public function the_service_throws_an_exception_for_an_invalid_identifier()
    {
        /** @var Bogardo\Multiauth\Service $service */
        $service = $this->app->make('multiauth.service');

        $this->setExpectedException('Exception', "Invalid user identifier");
        $service->getUserByCredentials(['invalididentifier' => 'admin@example.com', 'password' => 'test']);
    }

    /** @test */
    public function the_service_returns_null_for_invalid_credentials()
    {
        /** @var Bogardo\Multiauth\Service $service */
        $service = $this->app->make('multiauth.service');

        $result = $service->getUserByCredentials(['email' => 'nonexistent@example.com', 'password' => 'test']);
        $this->assertEquals(null, $result);
    }

    /** @test */
    public function it_throws_an_exception_when_a_configuration_key_is_missing()
    {
        $this->setExpectedException('Exception');
        new \Bogardo\Multiauth\Entity\Entity([]);
    }

    /** @test */
    public function userprovider_retrieves_a_user_by_id()
    {
        /** @var Bogardo\Multiauth\User\UserProvider $provider */
        $provider = $this->app->make('auth')->getProvider();

        $client = $provider->retrieveById('client.1');
        $this->assertInstanceOf('Client', $client);
    }

    /** @test */
    public function userprovider_retrieves_a_user_by_credentials()
    {
        /** @var Bogardo\Multiauth\User\UserProvider $provider */
        $provider = $this->app->make('auth')->getProvider();

        $client = $provider->retrieveByCredentials(['email' => 'admin@example.com', 'password' => 'test']);
        $this->assertInstanceOf('Admin', $client);
    }

    /** @test */
    public function userprovider_returns_null_when_retrieving_a_user_by_invalid_credentials()
    {
        /** @var Bogardo\Multiauth\User\UserProvider $provider */
        $provider = $this->app->make('auth')->getProvider();

        $result = $provider->retrieveByCredentials(['email' => 'invalid@invalid.com', 'password' => 'test']);
        $this->assertEquals(null, $result);
    }

    /** @test */
    public function userprovider_retrieves_a_user_by_token()
    {
        /** @var Bogardo\Multiauth\User\UserProvider $provider */
        $provider = $this->app->make('auth')->getProvider();

        $client = $provider->retrieveByToken('client.2', 'a_test_remember_token');
        $this->assertInstanceOf('Client', $client);
    }

    /** @test */
    public function userprovider_can_update_a_remember_token()
    {
        /** @var Bogardo\Multiauth\User\UserProvider $provider */
        $provider = $this->app->make('auth')->getProvider();

        $admin = $provider->retrieveById('admin.1');

        $this->assertEquals(null, $admin->getRememberToken());

        $provider->updateRememberToken($admin, 'new_remember_token');

        $this->assertEquals('new_remember_token', $admin->getRememberToken());
    }

    /** @test */
    public function it_logs_a_user_with_valid_credentials_in()
    {
        $auth = $this->app->make('auth');
        $result = $auth->attempt([
            'identifier' => 'client',
            'password' => 'secret'
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_does_not_log_a_user_in_with_invalid_credentials()
    {
        $auth = $this->app->make('auth');
        $result = $auth->attempt([
            'identifier' => 'abc@example.com',
            'password' => '123'
        ]);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_throws_an_exception_if_a_user_class_does_not_define_the_authtype_property()
    {
        $auth = $this->app->make('auth');

        $invalidClient = new InvalidClient();

        $this->setExpectedException("Exception", "Class InvalidClient must specify 'authtype' property");
        $auth->login($invalidClient);
    }



    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Helper Functions
     */

    /**
     * Get the client entity
     *
     * @return \Bogardo\Multiauth\Entity\Entity
     */
    protected function getClientEntity()
    {
        return new \Bogardo\Multiauth\Entity\Entity([
            'type'       => 'client',
            'table'      => 'clients',
            'model'      => 'Client',
            'identifier' => 'username'
        ]);
    }

    /**
     * Get the admin entity
     *
     * @return \Bogardo\Multiauth\Entity\Entity
     */
    protected function getAdminEntity()
    {
        return new \Bogardo\Multiauth\Entity\Entity([
            'type'       => 'admin',
            'table'      => 'admins',
            'model'      => 'Admin',
            'identifier' => 'email'
        ]);
    }
}

/**
 * Class Admin
 */
class Admin extends Illuminate\Database\Eloquent\Model implements Illuminate\Auth\UserInterface, Illuminate\Auth\Reminders\RemindableInterface
{

    use Bogardo\Multiauth\User\UserTrait, Illuminate\Auth\Reminders\RemindableTrait;

    public $authtype = 'admin';

    protected $table = 'admins';

    protected $fillable = ['email', 'password'];

}

/**
 * Class Client
 */
class Client extends Illuminate\Database\Eloquent\Model implements Illuminate\Auth\UserInterface, Illuminate\Auth\Reminders\RemindableInterface
{

    use Bogardo\Multiauth\User\UserTrait, Illuminate\Auth\Reminders\RemindableTrait;

    public $authtype = 'client';

    protected $table = 'clients';

    protected $fillable = ['email', 'username', 'password'];

}

/**
 * Class InvalidClient
 */
class InvalidClient extends Illuminate\Database\Eloquent\Model implements Illuminate\Auth\UserInterface, Illuminate\Auth\Reminders\RemindableInterface
{

    use Bogardo\Multiauth\User\UserTrait, Illuminate\Auth\Reminders\RemindableTrait;

    protected $table = 'clients';

    protected $fillable = ['email', 'username', 'password'];

}
