<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'blog.articles',   label: 'Articles',
        area: 'admin', route: 'acme.blog.admin.articles.index',
        icon: 'edit-3', capability: 'blog.article.view',
        group: 'Blog', order: 10,
    ),
    new NavigationItem(
        key: 'blog.comments',   label: 'Comments',
        area: 'admin', route: 'acme.blog.admin.comments.index',
        icon: 'message-square', capability: 'blog.comment.moderate',
        group: 'Blog', order: 20,
    ),
    new NavigationItem(
        key: 'blog.subscribers', label: 'Subscribers',
        area: 'admin', route: 'acme.blog.admin.subscribers.index',
        icon: 'mail', capability: 'blog.subscriber.manage',
        group: 'Blog', order: 30,
    ),
];
