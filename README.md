# Woo Menu Kategorii Produktów (Sidebar)

Wtyczka WordPress/WooCommerce dodająca klasyczny widget z menu kategorii produktów w sidebar.

## Nowe zasady działania (v1.1.0)

Widget wyświetla zwięzłe, kontekstowe menu dopasowane do bieżącej strony:

- **Jeśli bieżąca kategoria X ma podkategorie:**
  - wyświetla wyłącznie X (wyróżnioną jako aktywną) oraz jej bezpośrednie podkategorie (1 poziom niżej).
- **Jeśli bieżąca kategoria X nie ma podkategorii:**
  - wyświetla rodzeństwo X (dzieci parenta X); jeśli X jest kategorią główną (parent = 0), pokazuje wszystkie kategorie główne.
  - X jest wyróżnione jako aktywne wśród rodzeństwa.
- Nigdy nie wyświetla całego drzewa ani kategorii spoza powyższych reguł.

### Ustalanie bieżącej kategorii

| Kontekst | Sposób ustalenia |
|---|---|
| Archiwum kategorii (`product_cat`) | `get_queried_object()` |
| Strona produktu (single product) | Najgłębsza (leaf) z przypisanych kategorii produktu |

## Funkcje

- Widoczny **wyłącznie** na stronach kategorii produktów (archiwa `product_cat`) i stronach produktów.
- Na stronie produktu bieżąca kategoria to **najgłębsza** (leaf) z przypisanych kategorii produktu.
- CSS ładowany **tylko wtedy**, gdy widget rzeczywiście renderuje się na stronie.

## Styl (Avada-friendly)

Widget używa kolorów motywu Avada poprzez CSS variables z fallbackami:

```
--awb-color1 → --awb-color-primary → --primary_color → --primary → currentColor
```

Aktywna kategoria jest wyróżniona lewym paskiem i delikatnym tłem w kolorze primary motywu.
Brak czerwonego koloru — styl dostosowuje się automatycznie do palety motywu.

## Instalacja

1. Skopiuj katalog `woo-product-cat-sidebar-menu/` do `wp-content/plugins/`.
2. W panelu WordPress: **Wtyczki → Zainstalowane wtyczki** → Włącz **Woo Menu Kategorii Produktów (Sidebar)**.
3. Przejdź do **Wygląd → Widgety** i dodaj widget **Woo: Menu kategorii (sidebar)** do wybranego sidebara.

## Opcje widgetu

| Opcja | Opis |
|---|---|
| Tytuł | Nagłówek wyświetlany nad menu. |
| Ukrywaj puste kategorie | Nie pokazuje kategorii bez produktów. |
| Pokazuj liczbę produktów | Wyświetla liczbę produktów w nawiasie przy nazwie kategorii. |

## Struktura plików

```
woo-product-cat-sidebar-menu/
├── woo-product-cat-sidebar-menu.php   # Główny plik wtyczki
└── assets/
    └── wpcsm.css                      # Style sidebara (Avada-friendly)
```

## Klasy CSS

Możesz nadpisać styl w motywie, używając poniższych klas:

| Klasa | Opis |
|---|---|
| `.wpcsm-menu` | Główna lista kategorii |
| `.wpcsm-sub` | Podlista (podkategorie bieżącej kategorii) |
| `.wpcsm-item` | Element listy |
| `.wpcsm-item--current` | Bieżąca kategoria (wariant z dziećmi) |
| `.wpcsm-item--child` | Bezpośrednia podkategoria bieżącej kategorii |
| `.is-current` | Wyróżnienie bieżącej kategorii |
| `.wpcsm-link` | Link kategorii |
| `.wpcsm-count` | Liczba produktów |

## Wymagania

- WordPress 5.0+
- WooCommerce 4.0+

## Autor

Adam Gałuszka
