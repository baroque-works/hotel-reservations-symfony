<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationControllerTest extends WebTestCase
{
    private function createMockHttpClient(): MockHttpClient
    {
        return new MockHttpClient([
            new MockResponse('<html><form><input name="Username" value=""><input name="Password" value=""></form></html>', ['http_code' => 200]),
            new MockResponse('', ['http_code' => 200]),
            new MockResponse($this->getSampleCsvData(), ['http_code' => 200]),
        ]);
    }

    public function testListAction(): void
    {
        $client = static::createClient();
        $client->getContainer()->set('test.client.http_client', $this->createMockHttpClient());

        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestión de Reservas');
        $this->assertSelectorExists('table.table-striped');
        $this->assertSelectorTextContains('table.table-striped', 'Hotel 4');
        $this->assertSelectorExists('.pagination');
    }

    public function testListActionWithSearch(): void
    {
        $client = static::createClient();
        $client->getContainer()->set('test.client.http_client', $this->createMockHttpClient());

        $crawler = $client->request('GET', '/', ['search' => 'Nombre 1']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestión de Reservas');
        $this->assertSelectorTextContains('table.table-striped', 'Nombre 1');
        $this->assertSelectorExists('input[name="search"][value="Nombre 1"]');
    }

    public function testDownloadJsonAction(): void
    {
        $client = static::createClient();
        $client->getContainer()->set('test.client.http_client', $this->createMockHttpClient());

        $client->request('GET', '/download-json');
        $response = $client->getResponse();

        $this->assertInstanceOf(StreamedResponse::class, $response);

        $callback = $response->getCallback();

        ob_start();
        call_user_func($callback);
        $json = ob_get_clean();

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertResponseHeaderSame('Content-Disposition', 'attachment; filename="reservations.json"');

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('locator', $data[0]);
        $this->assertEquals('34637', $data[0]['locator']);
        $this->assertEquals('Nombre 1', $data[0]['guest']);
    }

    private function getSampleCsvData(): string
    {
        return <<<CSV
locator;guest;check_in_date;check_out_date;hotel;price;possible_actions
34637;Nombre 1;2018-10-04;2018-10-05;Hotel 4;112.49;cobrar
34694;Nombre 2;2018-06-15;2018-06-17;Hotel 1;427.77;cobrar
34549;Nombre 3;2018-06-22;2018-06-27;Hotel 4;1029.95;cobrar
34756;Nombre 4;2018-12-23;2019-01-10;Hotel 3;418.56;cobrar
34698;Nombre 5;2018-10-03;2018-10-04;Hotel 5;23.00;cobrar
34755;Nombre 6;2018-07-27;2018-07-31;Hotel 2;887.55;cobrar
34534;Nombre 7;2018-05-23;2018-05-24;Hotel 5;89.01;cobrar
34427;Nombre 8;2019-07-22;2019-07-22;Hotel 4;;cobrar
34515;Nombre 9;2018-06-23;2018-07-02;Hotel 6;3097.01;cobrar
34465;Nombre 10;2019-07-22;2019-07-22;Hotel 3;;cobrar
34749;Nombre 11;2018-06-28;2018-07-01;Hotel 6;952.00;cobrar
34783;Nombre 12;2018-11-02;2018-11-03;Hotel 5;57.01;cobrar
34442;Nombre 13;2019-07-22;2019-07-22;Hotel 7;;cobrar
34498;Nombre 14;2018-05-20;2018-05-21;Hotel 4;93.97;cobrar
34695;Nombre 15;2018-06-26;2018-06-30;Hotel 7;819.22;cobrar
CSV;
    }
}
