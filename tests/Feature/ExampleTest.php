<?php

test('the root url redirects to the dashboard', function () {
    $this->get('/')->assertRedirect('/dashboard');
});
