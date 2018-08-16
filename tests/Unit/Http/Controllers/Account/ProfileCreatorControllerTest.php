<?php

namespace Orchestra\Tests\Unit\Http\Controllers\Account;

use Mockery as m;
use Illuminate\Support\Facades\View;
use Orchestra\Support\Facades\Messages;
use Orchestra\Support\Facades\Foundation;
use Orchestra\Testing\BrowserKit\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ProfileCreatorControllerTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->disableMiddlewareForAllTests();
    }

    /**
     * Test GET /admin/register.
     *
     * @test
     */
    public function testGetCreateAction()
    {
        $this->getProcessorMock()->shouldReceive('create')->once()
            ->with(m::type('\Orchestra\Foundation\Http\Controllers\Account\ProfileCreatorController'))
            ->andReturnUsing(function ($listener) {
                return $listener->showProfileCreator([]);
            });

        View::shouldReceive('make')->once()
            ->with('orchestra/foundation::credential.register', [], [])->andReturn('foo');

        $this->call('GET', 'admin/register');
        $this->assertResponseOk();
    }

    /**
     * Test POST /admin/register.
     *
     * @test
     */
    public function testPostStoreAction()
    {
        $input = $this->getInput();

        $this->getProcessorMock()->shouldReceive('store')->once()
            ->with(m::type('\Orchestra\Foundation\Http\Controllers\Account\ProfileCreatorController'), $input)
            ->andReturnUsing(function ($listener) {
                return $listener->profileCreated();
            });

        Foundation::shouldReceive('handles')->once()->with('orchestra::login', [])->andReturn('login');
        Messages::shouldReceive('add')->twice()->with('success', m::any())->andReturnSelf();

        $this->call('POST', 'admin/register', $input);
        $this->assertRedirectedTo('login');
    }

    /**
     * Test POST /admin/register failed to send email.
     *
     * @test
     */
    public function testPostStoreActionGivenFailedToSendEmail()
    {
        $input = $this->getInput();

        $this->getProcessorMock()->shouldReceive('store')->once()
            ->with(m::type('\Orchestra\Foundation\Http\Controllers\Account\ProfileCreatorController'), $input)
            ->andReturnUsing(function ($listener) {
                return $listener->profileCreatedWithoutNotification();
            });

        Foundation::shouldReceive('handles')->once()->with('orchestra::login', [])->andReturn('login');
        Messages::shouldReceive('add')->once()->with('success', m::any())->andReturnSelf();
        Messages::shouldReceive('add')->once()->with('error', m::any())->andReturnSelf();

        $this->call('POST', 'admin/register', $input);
        $this->assertRedirectedTo('login');
    }

    /**
     * Test POST /admin/register with database error.
     *
     * @test
     */
    public function testPostStoreActionGivenDatabaseError()
    {
        $input = $this->getInput();

        $this->getProcessorMock()->shouldReceive('store')->once()
            ->with(m::type('\Orchestra\Foundation\Http\Controllers\Account\ProfileCreatorController'), $input)
            ->andReturnUsing(function ($listener) {
                return $listener->createProfileFailed(['error' => '']);
            });

        Foundation::shouldReceive('handles')->once()->with('orchestra::register', [])->andReturn('register');
        Messages::shouldReceive('add')->once()->with('error', m::any())->andReturnSelf();

        $this->call('POST', 'admin/register', $input);
        $this->assertRedirectedTo('register');
    }

    /**
     * Test POST /admin/register with failed validation.
     *
     * @test
     */
    public function testPostStoreActionGivenFailedValidation()
    {
        $input = $this->getInput();

        $this->getProcessorMock()->shouldReceive('store')->once()
            ->with(m::type('\Orchestra\Foundation\Http\Controllers\Account\ProfileCreatorController'), $input)
            ->andReturnUsing(function ($listener) {
                return $listener->createProfileFailedValidation([]);
            });

        Foundation::shouldReceive('handles')->once()->with('orchestra::register', [])->andReturn('register');

        $this->call('POST', 'admin/register', $input);
        $this->assertRedirectedTo('register');
    }

    /**
     * Get processor mock.
     *
     * @return \Orchestra\Foundation\Processors\Account\ProfileCreator
     */
    protected function getProcessorMock()
    {
        $processor = m::mock('\Orchestra\Foundation\Processors\Account\ProfileCreator');

        $this->app->instance('Orchestra\Foundation\Processors\Account\ProfileCreator', $processor);

        return $processor;
    }

    /**
     * Get sample input.
     *
     * @return array
     */
    protected function getInput()
    {
        return [
            'email' => 'email@orchestraplatform.com',
            'fullname' => 'Administrator',
        ];
    }
}
