<?php

it('renders the skillbro frontend shell at root', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="skillbro-app"', false);
});
