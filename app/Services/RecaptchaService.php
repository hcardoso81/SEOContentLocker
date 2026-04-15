<?php
if (!defined('ABSPATH')) exit;

class RecaptchaService
{
    public function validate($token)
    {
        validateRecaptcha($token);
    }
}
