<?php

namespace Orchestra\Tests\Controller\Users;

use Mockery as m;
use Orchestra\Tests\Controller\TestCase;
use Orchestra\Foundation\Testing\Installation;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Orchestra\Foundation\Processor\User as UserProcessor;

class RemoverTest extends TestCase
{
    use Installation;

    /** @test */
    public function its_not_accessible_for_user()
    {
        $user = $this->createUserAsMember();

        $second = $this->createUserAsMember();

        $this->actingAs($user)
            ->makeRequest('POST', "admin/users/{$second->id}", ['_method' => 'DELETE'])
            ->seePageIs('admin');

        $this->seeInDatabase('users', [
            'id' => $second->id,
            'email' => $second->email,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function its_can_accessible_for_admin()
    {
        $second = $this->createUserAsMember();

        $this->actingAs($this->adminUser)
            ->makeRequest('POST', "admin/users/{$second->id}", ['_method' => 'DELETE'])
            ->seeText('User has been deleted');


        $this->missingFromDatabase('users', [
            'id' => $second->id,
            'email' => $second->email,
            'deleted_at' => null,
        ]);
    }

    /**
     * @test
     * @expectedException \Laravel\BrowserKitTesting\HttpException
     */
    public function it_cant_delete_own_account()
    {
        $this->actingAs($this->adminUser)
            ->makeRequest('POST', "admin/users/{$this->adminUser->id}", ['_method' => 'DELETE'])
            ->dontSeeText('User has been deleted')
            ->seePageIs('admin/users');

        $this->seeInDatabase('users', [
            'id' => $this->adminUser->id,
            'email' => $this->adminUser->email,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_cant_delete_user_due_to_database_errors()
    {
        $this->instance(UserProcessor::class, $processor = m::mock(UserProcessor::class.'[fireEvent]', [
            $this->app->make(\Orchestra\Foundation\Http\Presenters\User::class),
            $this->app->make(\Orchestra\Foundation\Validation\User::class),
        ]))->shouldAllowMockingProtectedMethods();

        $processor->shouldReceive('fireEvent')->with('deleting', m::type('Array'))->andThrows('\Exception');

        $second = $this->createUserAsMember();

        $this->actingAs($this->adminUser)
            ->makeRequest('POST', "admin/users/{$second->id}", ['_method' => 'DELETE'])
            ->dontSeeText('User has been deleted')
            ->seePageIs('admin/users');

        $this->seeInDatabase('users', [
            'id' => $second->id,
            'email' => $second->email,
            'deleted_at' => null,
        ]);
    }
}
