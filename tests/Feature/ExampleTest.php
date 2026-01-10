<?php

it('redirects home to today', function () {
    $response = $this->get('/');

    $response->assertRedirect('/today');
});
