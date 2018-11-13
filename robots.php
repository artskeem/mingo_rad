<?php require_once 'app/env.php'; ?>
<?php header('Content-Type: text/plain'); ?>
# robotstxt.org/

User-agent: *

# Paths (clean URLs)
Disallow: /index.php/
Disallow: /catalog/product_compare/
Disallow: /catalog/category/view/
Disallow: /catalog/product/view/
Disallow: /catalog/product/gallery/
Disallow: /catalogsearch/
Disallow: /checkout/
Disallow: /control/
Disallow: /contacts/
Disallow: /customer/
Disallow: /customize/
Disallow: /newsletter/
Disallow: /poll/
Disallow: /review/
Disallow: /sendfriend/
Disallow: /tag/
Disallow: /wishlist/

# Paths (no clean URLs)
Disallow: /*.php$
Disallow: /*?SID=
Disallow: /rss*
Disallow: /*PHPSESSID

# Sitemap
Sitemap: <?= PROTOCOL ?>://<?= getenv('HTTP_HOST') ?>/sitemap.xml

<?php if (ENVIRONMENT !== 'production'): ?>
# Disallow ALL when NOT production
Disallow: /
<?php endif; ?>
