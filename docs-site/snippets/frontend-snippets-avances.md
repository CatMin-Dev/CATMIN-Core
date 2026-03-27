# Snippets Frontend Avances (Prompt 078)

Objectif: proposer des helpers simples pour un frontend libre sans imposer un moteur de templating complexe.

## 1) Afficher un menu

```php
@php($items = menu_items('primary'))

<nav>
  <ul>
    @foreach($items as $item)
      <li>
        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
      </li>
    @endforeach
  </ul>
</nav>
```

## 2) Afficher une liste de news

```php
@php($news = news_cards(6))

@foreach($news as $card)
  <article>
    <h3>{{ $card['title'] }}</h3>
    <p>{{ $card['excerpt'] }}</p>
  </article>
@endforeach
```

## 3) Afficher une liste de posts blog

```php
@php($posts = blog_cards(6))

@foreach($posts as $card)
  <article>
    <h3>{{ $card['title'] }}</h3>
    <p>{{ $card['excerpt'] }}</p>
  </article>
@endforeach
```

## 4) Afficher un bloc

```php
{!! render_block('homepage.hero', '<p>Bloc indisponible</p>') !!}
```

## 5) Afficher un media

```php
{!! media_img_tag(12, ['alt' => 'Illustration hero', 'class' => 'hero-image']) !!}
```

## 6) Lire un setting

```php
<h1>{{ setting_text('site.name', 'CATMIN') }}</h1>
```

## Notes

- Les helpers restent defensifs (modules/tables absents => retours vides/fallback).
- `news_cards` et `blog_cards` renvoient des payloads simples pour les templates.
- `media_img_tag` retourne une chaine HTML prete a afficher.
