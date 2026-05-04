# vaxx

Tema WordPress + WooCommerce da **VAXX Fitness** (Grupo Delva · Jaraguá do Sul / SC).

## Stack

- WordPress 6.5+ · PHP 8.0+
- Block theme híbrido (templates HTML + shortcodes via `wp_body_open` / `wp_footer`)
- WooCommerce com templates override em `woocommerce/`
- ACF Pro para specs de produto

## Estrutura

```
assets/         CSS, JS, fonts, SVGs, vídeos
inc/            Módulos PHP (CPTs, taxonomias, ACF, customizer, content filters)
parts/          Block template parts (header.html, footer.html — mínimos)
patterns/       Block patterns
templates/      Block templates (front-page, page, page-cart, page-checkout, index)
woocommerce/    Overrides do WooCommerce (archive-product, single-product, checkout, myaccount)
functions.php   Setup, enqueue, includes
theme.json      Tokens da marca (paleta Lima/Preto/Carvão/Cinza, Barlow + Barlow Condensed)
```

## Desenvolvimento

Ambiente local: **Local by Flywheel** · site `Vaxx` · `http://vaxx.local`.
Path do tema: `~/Local Sites/vaxx/app/public/wp-content/themes/vaxx-theme/`.

## Documentação

Notas operacionais e contratos de design estão no Obsidian (vault Jarvis) em `02-projetos/web/vaxx/`:
`site-vaxx`, `vaxx-marca`, `vaxx-design-system`, `vaxx-arquitetura`, `vaxx-componentes`, `vaxx-catalogo`.
