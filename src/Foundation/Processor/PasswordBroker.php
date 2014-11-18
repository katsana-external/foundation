<?php namespace Orchestra\Foundation\Processor;

use Illuminate\Support\Facades\Auth;
use Orchestra\Support\Facades\Foundation;
use Illuminate\Contracts\Auth\PasswordBroker as Password;
use Orchestra\Foundation\Validation\AuthenticateUser as Validator;

class PasswordBroker extends Processor
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
        $this->password = $password;
    }

    /**
     * Request to reset password.
     *
     * @param  object  $listener
     * @param  array   $input
     * @return mixed
     */
    public function create($listener, array $input)
    {
        $validation = $this->validator->with($input);

        if ($validation->fails()) {
            return $listener->requestValidationFailed($validation);
        }

        $memory = Foundation::memory();
        $site  = $memory->get('site.name', 'Orchestra Platform');
        $data  = ['email' => $input['email']];

        $response = $this->password->sendResetLink($data, function ($mail) use ($site) {
            $mail->subject(trans('orchestra/foundation::email.forgot.request', ['site' => $site]));
        });

        if ($response != Password::RESET_LINK_SENT) {
            return $listener->createFailed($response);
        }

        return $listener->createSucceed($response);
    }

    /**
     * Reset the password.
     *
     * @param  object  $listener
     * @param  array   $input
     * @return mixed
     */
    public function reset($listener, array $input)
    {
        $response = $this->password->reset($input, function ($user, $password) {
            // Save the new password and login the user.
            $user->password = $password;
            $user->save();

            Auth::login($user);
        });

        $errors = [
            Password::INVALID_PASSWORD,
            Password::INVALID_TOKEN,
            Password::INVALID_USER,
        ];

        if (in_array($response, $errors)) {
            return $listener->resetFailed($response);
        }

        return $listener->resetSucceed($response);
    }
}
