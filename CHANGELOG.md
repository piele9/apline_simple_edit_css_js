# Historia zmian

Wszystkie istotne zmiany w module **APLINE Simple Edit CSS/JS dla PrestaShop 9** są zapisywane w tym pliku. Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.1.0/), wersjonowanie zgodne z [Semantic Versioning](https://semver.org/lang/pl/).

## [1.1.0] – 2026-10-08

Spolszczenie interfejsu i duże przyciski głównych akcji. Aktualizacja z 1.0.0 zachowuje wszystkie fragmenty, ich historię wersji i ustawienia modułu.

### Zmieniono
- **Cały interfejs po polsku jako tekst źródłowy**: strona konfiguracji, lista i formularz fragmentów, nazwy kolumn, etykiety pól, podpowiedzi, komunikaty o błędach i potwierdzenia, nazwa i opis modułu, pytanie przy odinstalowaniu oraz teksty przycisków wyświetlane przez JavaScript. Nie zależy to już od pakietu językowego ani od pamięci podręcznej tłumaczeń.
- Nazwa modułu: „APLINE Simple Edit CSS/JS dla PrestaShop 9”. Nazwa ukrytej zakładki panelu to teraz „Fragmenty CSS/JS” (zmienia ją skrypt aktualizacji).
- **Duże przyciski głównych akcji** (klasa `apline-btn-duzy` w nowym pliku `views/css/admin.css`): „Zarządzaj fragmentami CSS/JS” na stronie konfiguracji, „Zapisz” w ustawieniach i w formularzu fragmentu oraz „Dodaj fragment” nad listą. Przyciski drugorzędne (np. „Wróć do konfiguracji”, „Formatuj CSS”, „Wczytaj do edytora”) pozostały standardowe.
- Dwa fragmenty startowe tworzone przy instalacji są po polsku: „Miejsce na własny CSS” (aktywny) i „Odtwarzacz YouTube w opisie produktu” (wyłączony), wraz z komentarzami w kodzie. Dotyczy to tylko nowych instalacji — istniejące fragmenty nie są zmieniane.
- `ps_versions_compliancy`: minimalna wersja zapisana jako `9.0.0`.
- Przepisany `README.md` (po polsku, według wzoru README modułów APLINE) i `docs/README.md`.

### Dodano
- `upgrade/upgrade-1.1.0.php`: ustawia polską nazwę ukrytej zakładki dla wszystkich języków i czyści pamięć podręczną Smarty. Nie zmienia fragmentów, ich historii, tabel, hooków ani ustawień.

### Bez zmian
- Tabele, hooki, klucz konfiguracji `ASEC_FORMAT_CSS_ON_SAVE`, sposób wstawiania kodu na stronę, wersjonowanie kodu i formatowanie CSS działają jak w 1.0.0.

## [1.0.0] – 2026-05-31

Pierwsze publiczne wydanie.

### Dodano
- Wstawianie własnych **fragmentów CSS i JavaScript** na stronę sklepu bez edytowania motywu, dla PrestaShop **9.0.x**. Każdym fragmentem zarządza się jak wierszem: kolejność przeciąganiem, włączanie i wyłączanie, edycja, usuwanie.
- Dwa hooki wyświetlania: CSS i JS z miejscem „head” w `displayHeader` (CSS najpierw, żeby uniknąć błysku nieostylowanej strony), JS z miejscem „przed `</body>`” w `displayBeforeBodyClosingTag`.
- Ustawienia każdego fragmentu: **typ** (CSS/JS), **miejsce wstawienia** (`<head>` / przed `</body>`) i **moment uruchomienia** (od razu / po `DOMContentLoaded`, tylko JS).
- **Wersjonowanie kodu**: każdy zapis tworzy kopię poprzedniego kodu (tylko gdy kod się zmienił), moduł trzyma 3 najnowsze wersje fragmentu, które można wczytać w formularzu przyciskiem „Wczytaj do edytora” (żeby zastosować, trzeba jeszcze zapisać).
- **Formatowanie CSS** bez zewnętrznych bibliotek: przycisk „Formatuj CSS” oraz opcjonalne automatyczne formatowanie przy zapisie (`ASEC_FORMAT_CSS_ON_SAVE`).
- **Kontrola nawiasów klamrowych w CSS**: zapis jest odrzucany, gdy liczba `{` i `}` się nie zgadza (zawartość komentarzy jest pomijana).
- Ścisła walidacja: pola wymagane, limit 255 znaków nazwy (odrzucany, nigdy po cichu obcinany), dozwolone wartości typu, miejsca i momentu uruchomienia, CSS musi być w `<head>`.
- Odporność na błędy: hooki w `try/catch` (przy błędzie pusty wynik, nigdy błąd 500), nieudana instalacja jest wycofywana do czystego stanu, a odinstalowanie jest idempotentne i usuwa obie tabele.
- Dwa fragmenty startowe tworzone przy instalacji: aktywne miejsce na własny CSS i wyłączony przykład odtwarzacza YouTube w JavaScript.
- Informacja o autorze (APLINE) na stronie konfiguracji i pod listą fragmentów oraz ramka „Podoba Ci się ten moduł?” z linkiem do https://apline.pl.
- Licencja Custom Attribution License v1.0 ([LICENSE.md](LICENSE.md)).

### Model zaufania
Kod fragmentu pisze administrator i jest wstawiany na stronę bez zmian. To zamierzone i nie jest luką XSS: fragmenty może tworzyć wyłącznie administrator panelu z pełnymi uprawnieniami, klient sklepu nie ma takiej możliwości. Zaufanie opiera się na uprawnieniach w panelu, a nie na filtrowaniu treści fragmentu.

### Poza tym wydaniem
- Wybór stron, na których fragment się ładuje (wszystkie aktywne fragmenty ładują się na każdej stronie sklepu).
- Fragmenty wielojęzyczne, import i eksport, sprawdzanie składni JavaScript.
