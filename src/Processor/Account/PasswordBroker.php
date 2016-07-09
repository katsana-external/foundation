<?php

namespace Orchestra\Foundation\Processor\Account;

use Illuminate\Support\Facades\Auth;
use Orchestra\Model\User as Eloquent;
use Orchestra\Support\Facades\Foundation;
use Orchestra\Foundation\Processor\Processor;
use Orchestra\Contracts\Auth\Listener\PasswordReset;
use Orchestra\Contracts\Auth\Listener\PasswordResetLink;
use Illuminate\Contracts\Auth\PasswordBroker as Password;
use Orchestra\Contracts\Auth\Command\PasswordBroker as Command;
use Orchestra\Foundation\Validation\AuthenticateUser as Validator;

class PasswordBroker extends Processor implements Command
{
    /**
     * The password broker implementation.
     *
     * @var \Illuminate\Contracts\Auth\PasswordBroker
     */
    protected $password;

    /**
     * Create a new processor instance.
     *
     * @param \Orchestra\Foundation\Validation\AuthenticateUser  $validator
     * @param \Illuminate\Contracts\Auth\PasswordBroker  $password
     */
    public function __construct(Validator $validator, Password $password)
    {
        $this->validator = $validator;
        $this->password  = $password;
    }

    /**
     * Request to reset password.
     *
     * @param  \Orchestra\Contracts\Auth\Listener\PasswordResetLink  $listener
     * @param  array  $input
     *
     * @return mixed
     */
    public function store(PasswordResetLink $listener, array $input)
    {
        $validation = $this->validator->with($input);

        if ($validation->fails()) {
            return $listener->resetLinkFailedValidation($validation->getMessageBag());
        }

        $site = Foundation::memory()->get('site.name', 'Orchestra Platform');
        $data = ['email' => $input['email']];

        $response = $this->password->sendResetLink($data);

        if ($response != Password::RESET_LINK_SENT) {
            return $listener->resetLinkFailed($response);
        }

        return $listener->resetLinkSent($response);
    }

    /**
     * Reset the password.
     *
     * @param  \Orchestra\Contracts\Auth\Listener\PasswordReset  $listener
     * @param  array  $input
     *
     * @return mixed
     */
    public function update(PasswordReset $listener, array $input)
    {
        $response = $this->password->reset($input, function (Eloquent $user, $password) {
            // Save the new password and login the user.
            $user->setAttribute('password', $password);
            $user->save();

            Auth::login($user);
        });

        $errors = [
            Password::INVALID_PASSWORD,
            Password::INVALID_TOKEN,
            Password::INVALID_USER,
        ];

        if (in_array($response, $errors)) {
            return $listener->passwordResetHasFailed($response);
        }

        return $listener->passwordHasReset($response);
    }
}
