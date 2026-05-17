<?php

declare(strict_types=1);

namespace Acme\Blog;

use Acme\Blog\Blocks\ArticleBlock;
use Acme\Blog\Blocks\ArticleListBlock;
use Acme\Blog\Blocks\LatestPostsBlock;
use Acme\Contracts\Cms\BlockRegistry;
use Acme\Starter\Support\PackageServiceProvider;

final class BlogServiceProvider extends PackageServiceProvider
{
    protected string $key = 'blog';

    protected bool $hasViews         = true;
    protected bool $hasRoutesWeb     = true;
    protected bool $hasRoutesAdmin   = true;
    protected bool $hasCapabilities  = true;
    protected bool $hasNavigation    = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(BlockRegistry::class, function (BlockRegistry $registry): void {
            $registry->register(ArticleBlock::class);
            $registry->register(ArticleListBlock::class);
            $registry->register(LatestPostsBlock::class);
        });
    }
}
