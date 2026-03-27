# Articles Module

Module Articles: fusion de Blog et News dans un seul back-office.

## Objectif

- eliminer le doublon fonctionnel Blog/News
- garder un seul flux de production de contenu
- conserver une distinction editoriale via `content_type`

## V1

- CRUD admin unifie
- type de contenu: `article` ou `news`
- migration de reprise depuis `blog_posts` et `news_items`
- liaisons media/seo conservees
