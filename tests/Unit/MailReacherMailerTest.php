<?php

use Tests\TestCase;

uses(TestCase::class);

it('configures mail reacher as an available mailer', function () {
    expect(config('mail.mailers.mailreacher.transport'))->toBe('mailreacher');
});
