<?php

namespace Djouamaah\AlgeriaInvoice\Tests;

use Djouamaah\AlgeriaInvoice\AlgeriaInvoiceServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AlgeriaInvoiceServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('algeria-invoice.seller', self::seller());
    }

    public static function seller(): array
    {
        return [
            'name' => 'SARL Exemple Informatique',
            'legal_form' => 'SARL',
            'capital' => 1000000,
            'address' => '12 rue Didouche Mourad, Alger',
            'phone' => '023 00 00 00',
            'rc' => '16/00-1234567B21',
            'nif' => '000116123456789',
            'nis' => '000116010203040',
            'ai' => '16012345678',
            'rib' => '00799999001234567890',
        ];
    }

    public static function companyBuyer(): array
    {
        return [
            'name' => 'EURL Client Pro',
            'legal_form' => 'EURL',
            'address' => 'Zone industrielle, Sétif',
            'rc' => '19/00-7654321B20',
            'nif' => '000219987654321',
        ];
    }
}
