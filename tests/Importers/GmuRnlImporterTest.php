<?php

namespace Tests\Importers;

use App\Import;
use App\Importers\GmuRnlImporter;
use App\Repositories\CsvRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GmuRnlImporterTest extends TestCase
{
    use DatabaseMigrations;

    public function testImport()
    {
        $item = $this->importSingle([
            'Řada' => 'G',
            'Inventární ' => 'G0166',
            'Titul' => 'Dresden',
            'Autor' => 'Kuehl Gotthard',
            'Datace' => '1902',
            'Od' => '1900',
            'Do' => '1905',
            'Materiál' => 'papír',
            'Technika' => 'litografie',
            'Rozměr' => 'v=52cm; s=84cm',
            'Námět/téma' => 'Město',
            'Signatura (aversu)' => 'vpravo dole v ploše tisku: Gotthard Kuehl/Dresden 02',
            'Datum nabytí' => '',
        ]);

        $this->assertEquals('CZE:GMU-RNL.G0166', $item->id);
        $this->assertEquals('Dresden', $item->title);
        $this->assertEquals('Kuehl Gotthard', $item->author);
        $this->assertEquals('1902', $item->dating);
        $this->assertEquals(1900, $item->date_earliest);
        $this->assertEquals(1905, $item->date_latest);
        $this->assertEquals('papír', $item->medium);
        $this->assertEquals('litografie', $item->technique);
        $this->assertEquals('celková výška/dĺžka 52 cm; šírka 84 cm', $item->measurement);
        $this->assertEquals('Město', $item->topic);
        $this->assertEquals('vpravo dole v ploše tisku: Gotthard Kuehl/Dresden 02', $item->inscription);
        $this->assertEquals('grafika', $item->work_type);
        $this->assertSame(null, $item->acquisition_date);
    }

    protected function importSingle(array $data)
    {
        $records = new \ArrayIterator([$data]);
        $repositoryMock = $this->createMock(CsvRepository::class);
        $repositoryMock->method('getFiltered')->willReturn($records);

        $importer = new GmuRnlImporter($repositoryMock, $this->app->get(Translator::class));
        $import = Import::create();
        $file = ['basename' => '', 'path' => ''];
        $items = $importer->import($import, $file);

        $this->assertEquals('', $import->records()->first()->error_message);
        $this->assertCount(1, $items);

        return $items[0];
    }
}