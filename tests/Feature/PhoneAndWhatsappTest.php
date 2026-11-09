<?php

namespace Tests\Feature;

use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The phone number from Settings › Identity drives the floating WhatsApp button, tel: links and structured data. */
class PhoneAndWhatsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_number_formats(): void
    {
        $this->assertSame('966537514589', Phone::international('0537514589'));
        $this->assertSame('966537514589', Phone::international('+966 53 751 4589'));
        $this->assertSame('966537514589', Phone::international('00966537514589'));
        $this->assertSame('+966 53 751 4589', Phone::display('0537514589'));
        $this->assertSame('https://wa.me/966537514589?text=Hi%20there', Phone::whatsapp('053 751 4589', 'Hi there'));
        $this->assertNull(Phone::international('abc'));
        $this->assertNull(Phone::tel(''));
    }

    public function test_floating_button_and_contact_links(): void
    {
        $this->importReference();
        $this->get('/ar')->assertOk()
            ->assertSee('class="float-wa" href="https://wa.me/966537514589?text=', false)
            ->assertSee('href="tel:+966537514589"', false)
            ->assertSee('"telephone":"+966537514589"', false);
        $this->get('/en/contact')->assertSee('+966 53 751 4589');
    }
}
