# Woo Menu Kategorii Produktów (Sidebar)

Wtyczka WordPress/WooCommerce dodająca klasyczny widget z prostym menu kategorii produktów w sidebar.

## Funkcje

- Wyświetla tylko **główne kategorie** produktów (parent = 0).
- Widoczny **wyłącznie** na stronach kategorii produktów (archiwa `product_cat`) i stronach produktów.
- Na stronie kategorii **rozwija gałąź** prowadzącą do bieżącej kategorii + pokazuje jej podkategorie (1 poziom niżej).
- Na stronie produktu bieżąca kategoria to **najgłębsza** (leaf) z przypisanych kategorii produktu.
- CSS ładowany **tylko wtedy**, gdy widget rzeczywiście renderuje się na stronie.
- Menu ostylowane jak **drzewo**: wcięcia, pionowa linia pnia, poziome łączniki.

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
    └── wpcsm.css                      # Style drzewa kategorii
```

## Klasy CSS

Możesz nadpisać styl w motywie, używając poniższych klas:

| Klasa | Opis |
|---|---|
| `.wpcsm-menu` | Główna lista kategorii |
| `.wpcsm-sub` | Podlista (rozwinięta gałąź) |
| `.wpcsm-item` | Element listy |
| `.wpcsm-item--top` | Element na poziomie głównym |
| `.wpcsm-item--child` | Bezpośrednia podkategoria bieżącej kategorii |
| `.is-current` | Bieżąca kategoria |
| `.is-ancestor` | Przodek bieżącej kategorii |
| `.wpcsm-link` | Link kategorii |
| `.wpcsm-count` | Liczba produktów |

## Wymagania

- WordPress 5.0+
- WooCommerce 4.0+

## Autor

Adam Gałuszka
