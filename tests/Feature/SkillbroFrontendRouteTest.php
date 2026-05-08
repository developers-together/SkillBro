<?php

it('renders the skillbro frontend shell', function () {
    $this->get('/skillbro')
        ->assertOk()
        ->assertSee('id="skillbro-app"', false);
});
