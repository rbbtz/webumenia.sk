<?php

namespace Tests\Harvest\Harvesters;

use App\Authority;
use App\Harvest\Factories\EndpointFactory;
use App\Harvest\Harvesters\ItemHarvester;
use App\Harvest\Importers\ItemImporter;
use App\Harvest\Mappers\AuthorityItemMapper;
use App\Harvest\Mappers\AuthorityMapper;
use App\Harvest\Mappers\CollectionItemMapper;
use App\Harvest\Mappers\ItemImageMapper;
use App\Harvest\Mappers\ItemMapper;
use App\Harvest\Repositories\ItemRepository;
use App\Harvest\Result;
use App\Item;
use App\ItemImage;
use App\SpiceHarvesterHarvest;
use App\SpiceHarvesterRecord;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ItemHarvesterTest extends TestCase
{
    use DatabaseMigrations;

    public function testTryHarvestNoRows()
    {
        $repositoryMock = $this->createMock(ItemRepository::class, [], [
            $this->createMock(EndpointFactory::class)
        ]);
        $importerMock = $this->createMock(ItemImporter::class, [], [
            $this->createMock(ItemMapper::class),
            $this->createMock(ItemImageMapper::class),
            $this->createMock(CollectionItemMapper::class),
            $this->createMock(AuthorityItemMapper::class),
            $this->createMock(AuthorityMapper::class),
        ]);

        $repositoryMock->expects($this->once())
            ->method('getRows')
            ->willReturn([]);

        $harvester = new ItemHarvester($repositoryMock, $importerMock);

        $harvest = factory(SpiceHarvesterHarvest::class)->make([
            'type' => 'item',
            'status' => SpiceHarvesterHarvest::STATUS_QUEUED
        ]);
        $harvester->tryHarvest($harvest);

        $this->assertEquals(SpiceHarvesterHarvest::STATUS_COMPLETED, $harvest->status);
    }

    public function testHarvestDoesNotOverwriteAuthorityAttributes()
    {
        $item = factory(Item::class)->create([
            'id' => 'SVK:SNG.G_10044',
        ]);
        $authority = factory(Authority::class)->create([
            'id' => 1922,
            'name' => 'Test Name'
        ]);

        $harvest = factory(SpiceHarvesterHarvest::class)->create();
        $record = factory(SpiceHarvesterRecord::class)->create();
        $record->harvest()->associate($harvest);

        $row = $this->getItemRow();
        $repositoryMock = $this->createMock(ItemRepository::class);
        $repositoryMock->method('getRow')->willReturn($row);

        $this->app->when(ItemHarvester::class)
            ->needs(ItemRepository::class)
            ->give(function () use ($repositoryMock) {
                return $repositoryMock;
            });

        /** @var ItemHarvester $harvester */
        $harvester = $this->app->make(ItemHarvester::class);
        $harvester->tryHarvestSingle($record, new Result());

        $authority->refresh();
        $this->assertEquals('Test Name', $authority->name);

        $item->refresh();
        $this->assertCount(1, $item->authorities);
    }

    protected function getItemRow()
    {
        return [
            'status' => [],
            'id' => ['SVK:SNG.G_10044'],
            'identifier' => [
                'SVK:SNG.G_10044',
                'http://www.webumenia.sk/oai-pmh/getimage/SVK:SNG.G_10044',
                'G 10044',
            ],
            'title_translated' => [
                [
                    'lang' => ['en'],
                    'title_translated' => ['Flemish family'],
                ],
            ],
            'type' => [
                [
                    'lang' => ['sk'],
                    'type' => ['grafika, voľná'],
                ],
                [
                    'lang' => [],
                    'type' => ['DEF'],
                ],
                [
                    'lang' => [],
                    'type' => ['originál'],
                ],
                [
                    'lang' => [],
                    'type' => ['Image'],
                ],
            ],
            'format' => [
                [
                    'lang' => ['en'],
                    'format' => ['engraving'],
                ],
                [
                    'lang' => ['sk'],
                    'format' => ['rytina'],
                ],
            ],
            'format_medium' => [
                [
                    'lang' => ['sk'],
                    'format_medium' => ['kartón, zahnedlý'],
                ]
            ],
            'subject' => [
                [
                    'lang' => ['en'],
                    'subject' => ['figurative composition'],
                ],
                [
                    'lang' => ['sk'],
                    'subject' => ['figurálna kompozícia'],
                ],
                [
                    'lang' => ['cs'],
                    'subject' => ['figurální'],
                ],
            ],
            'title' => ['Flámska rodina'],
            'subject_place' => [],
            'relation_isPartOf' => ['samostatné dielo'],
            'creator' => [
                'urn:svk:psi:per:sng:0000001922',
                'Daullé, Jean',
                'urn:svk:psi:per:sng:0000010816',
                'Teniers, David',
            ],
            'authorities' => [
                [
                    'id' => ['urn:svk:psi:per:sng:0000001922'],
                    'role' => ['autor/author'],
                ],
                [
                    'id' => ['urn:svk:psi:per:sng:0000010816'],
                    'role' => ['iné/other'],
                ],
            ],
            'rights' => [
                '1',
                'publikovať/public',
            ],
            'description' => [
                'vpravo dole gravé J.Daullé..',
                'vľavo dole peint Teniers',
            ],
            'extent' => ['šírka 50.0 cm, šírka 47.6 cm, výška 39.0 cm, výška 37.0 cm, hĺbka 5.0 cm ()'],
            'gallery' => ['Slovenská národná galéria, SNG'],
            'credit' => [
                [
                    'lang' => ['sk'],
                    'credit' => ['Dar zo Zbierky Linea'],
                ],
                [
                    'lang' => ['en'],
                    'credit' => ['Donation from the Linea Collection'],
                ],
                [
                    'lang' => ['cs'],
                    'credit' => ['Dar ze Sbírky Linea'],
                ],
            ],
            'created' => [
                '1760/1760',
                '18. storočie, polovica, 1760',
            ],
            'datestamp' => ['2017-08-28T14:00:23.769Z'],
        ];
    }
}
