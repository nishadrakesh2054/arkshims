<?php

test('the homepage redirects to the admin panel', function () {
    $this->get('/')
        ->assertRedirect('/admin');
});
