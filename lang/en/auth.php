<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    /*
    |--------------------------------------------------------------------------
    | Authentication screens
    |--------------------------------------------------------------------------
    */

    'login' => [
        'head' => 'Log in',
        'title' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in',
        'forgot_password' => 'Forgot your password?',
        'remember' => 'Remember me',
        'submit' => 'Log in',
        'no_account' => "Don't have an account?",
        'sign_up' => 'Sign up',
    ],

    'register' => [
        'head' => 'Register',
        'title' => 'Create an account',
        'description' => 'Enter your details below to create your account',
        'submit' => 'Create account',
        'has_account' => 'Already have an account?',
        'log_in' => 'Log in',
    ],

    'forgot_password' => [
        'title' => 'Forgot password',
        'description' => 'Enter your email to receive a password reset link',
        'submit' => 'Email password reset link',
        'return_to' => 'Or, return to',
        'log_in' => 'log in',
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'description' => 'Please enter your new password below',
        'submit' => 'Reset password',
    ],

    'confirm_password' => [
        'title' => 'Confirm password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'submit' => 'Confirm password',
        'passkey' => 'Confirm with passkey',
        'passkey_loading' => 'Confirming...',
        'separator' => 'Or confirm with password',
    ],

    'verify_email' => [
        'title' => 'Email verification',
        'description' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'link_sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend verification email',
        'log_out' => 'Log out',
    ],

    'two_factor_challenge' => [
        'head' => 'Two-factor authentication',
        'code_title' => 'Authentication code',
        'code_description' => 'Enter the authentication code provided by your authenticator application.',
        'recovery_title' => 'Recovery code',
        'recovery_description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
        'recovery_placeholder' => 'Enter recovery code',
        'submit' => 'Continue',
        'or_you_can' => 'or you can',
        'use_recovery_code' => 'login using a recovery code',
        'use_code' => 'login using an authentication code',
    ],

    'passkey' => [
        'sign_in' => 'Sign in with a passkey',
        'loading' => 'Authenticating...',
        'separator' => 'Or continue with email',
    ],

];
