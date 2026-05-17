<?php

declare(strict_types=1);

namespace Acme\I18n;

use Acme\I18n\Support\TranslationStore;
use Acme\Starter\Support\PackageServiceProvider;

final class I18nServiceProvider extends PackageServiceProvider
{
    protected string $key = 'i18n';

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(TranslationStore::class);
    }
}
