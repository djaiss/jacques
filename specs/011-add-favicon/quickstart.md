# Quickstart: Add Favicon

## Automated

```bash
php artisan test --compact --filter=FaviconTest
```

Expected: every test passes.

## Manual

1. `file public/favicon.ico` reports an icon resource of one 32 by 32 image.
2. `curl -s http://jacques.test/favicon.ico | cmp - public/favicon.ico` reports no difference. Herd
   answers root files of `public/` with status `404` but the right body, `robots.txt` included.
3. Open the sign in screen, the root page and any back office screen, in a light and a dark
   browser theme. Each tab shows the white portrait on its black disc.
4. Bookmark one screen. The bookmark shows the same portrait.
