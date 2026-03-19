# Woo Menu Kategorii Produktów (Sidebar)

Wtyczka WordPress/WooCommerce dodająca klasyczny widget z menu kategorii produktów w sidebar.

## Zasady działania (v1.3.0)

Widget wyświetla **pełne drzewo kategorii** z rozwinięciem gałęzi prowadzącej do bieżącej kategorii:

- **Zawsze** wyświetlane są wszystkie kategorie główne (top-level, parent = 0).
- **Tylko gałąź bieżącej kategorii jest rozwinięta** (ścieżka od root do bieżącej kategorii).
- **Na każdym poziomie przodka** widoczne jest całe rodzeństwo (wszyscy bracia w danym węźle), nie tylko element na ścieżce.
- **Bieżąca kategoria** jest wyróżniona pogrubionym, czarnym tekstem (`.is-current`).
- Jeśli bieżąca kategoria ma dzieci, są one pokazane 1 poziom niżej.
- Pozostałe gałęzie (poza ścieżką) nie są rozwijane.

### Przykładowy layout (bieżąca: „Rowery szosowe")

```
Akcesoria
Rowery                        ← przodek (is-ancestor)
  ├─ Rowery górskie
  ├─ Rowery miejskie
  └─ Rowery szosowe           ← bieżąca (is-current, pogrubiona)
       ├─ Endurance
       └─ Race
Ubrania
```

Link „← Cofnij do Rowery" pojawia się nad drzewem (jeśli bieżąca kategoria ma parenta).

### Ustalanie bieżącej kategorii

| Kontekst | Sposób ustalenia |
|---|---|
| Archiwum kategorii (`product_cat`) | `get_queried_object()` |
| Strona produktu (single product) | Najgłębsza (leaf) z przypisanych kategorii produktu |

## Funkcje

- Widoczny **wyłącznie** na stronach kategorii produktów (archiwa `product_cat`) i stronach produktów.
- Na stronie produktu bieżąca kategoria to **najgłębsza** (leaf) z przypisanych kategorii produktu.
- CSS ładowany **tylko wtedy**, gdy widget rzeczywiście renderuje się na stronie.

## Styl

- Jednolity rozmiar czcionki dla wszystkich poziomów drzewa.
- Bez podkreśleń (ani domyślnie, ani na hover).
- Aktywna kategoria wyróżniona wyłącznie pogrubieniem i ciemnym kolorem (`#222`).
- Drzewko z liniami (pionowe i poziome łączniki, wcięcia).
- Brak czerwieni – styl neutralny, Avada-friendly.

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
    └── wpcsm.css                      # Style sidebara
```

## Klasy CSS

Możesz nadpisać styl w motywie, używając poniższych klas:

| Klasa | Opis |
|---|---|
| `.wpcsm-menu` | Główna lista kategorii |
| `.wpcsm-sub` | Podlista (dzieci danego węzła) |
| `.wpcsm-item` | Element listy |
| `.is-current` | Bieżąca kategoria (pogrubiona, czarna) |
| `.is-ancestor` | Przodek bieżącej kategorii |
| `.wpcsm-link` | Link kategorii |
| `.wpcsm-count` | Liczba produktów |
| `.wpcsm-back` | Kontener linku „Cofnij do" |
| `.wpcsm-back__link` | Link „← Cofnij do {parent}" |

## Wymagania

- WordPress 5.0+
- WooCommerce 4.0+

## Autor

Adam Gałuszka
