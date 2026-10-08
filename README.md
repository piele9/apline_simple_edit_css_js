# APLINE Simple Edit CSS/JS — własny CSS i JavaScript w sklepie PrestaShop 9

![PrestaShop 9](https://img.shields.io/badge/PrestaShop-9.x-DF0067) ![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4) ![Wersja](https://img.shields.io/badge/wersja-1.1.0-2ea44f) ![Licencja](https://img.shields.io/badge/licencja-Custom%20Attribution%20v1.0-blue)

Moduł pozwala wstawiać na stronę sklepu własne fragmenty CSS i JavaScript bez edytowania motywu i bez grzebania w plikach. Każdy fragment to osobny wiersz w panelu: kolejność zmieniasz przeciąganiem, fragment włączasz i wyłączasz jednym kliknięciem, a kod edytujesz w formularzu, z historią ostatnich wersji i formatowaniem CSS. Dla właścicieli sklepów i wdrożeniowców, którzy chcą szybko poprawić wygląd albo dodać skrypt i nie stracić tych zmian przy aktualizacji motywu.

## Funkcje

- fragmenty **CSS** i **JavaScript** wstawiane bezpośrednio w kod stron sklepu, zarządzane jak wiersze: przeciąganie kolejności, przełącznik „Aktywny”, edycja, usuwanie (także grupowe);
- dla każdego fragmentu: **typ** (CSS lub JS), **miejsce wstawienia** (w `<head>` albo tuż przed `</body>`, to drugie tylko dla JS) i **moment uruchomienia** JavaScriptu (od razu albo po zdarzeniu `DOMContentLoaded`);
- **historia wersji**: przy każdym zapisie ze zmienionym kodem moduł zachowuje poprzednią wersję (3 najnowsze dla fragmentu) i pozwala ją wczytać do edytora;
- **formatowanie CSS** przyciskiem „Formatuj CSS” oraz opcjonalnie automatycznie przy zapisie; zapis CSS z niezgodną liczbą nawiasów `{` `}` jest odrzucany;
- walidacja z komunikatami po polsku: pola wymagane, limit 255 znaków nazwy (nigdy po cichu obcinany), dozwolone wartości typu, miejsca i momentu uruchomienia;
- odporność na błędy: awaria przy wyświetlaniu fragmentów daje pusty wynik zamiast błędu 500, a nieudana instalacja jest wycofywana do czystego stanu;
- dwa fragmenty startowe po instalacji: aktywne miejsce na własny CSS i wyłączony przykład odtwarzacza YouTube (JavaScript) do podejrzenia i włączenia;
- bez zewnętrznych bibliotek, bez telemetrii, bez budowania zasobów po stronie front-endu.

## Wymagania

- PrestaShop 9.x, PHP 8.1+ (moduł nie instaluje się na PrestaShop 1.7 ani 8.x);
- motyw, który wywołuje hooki `displayHeader` i `displayBeforeBodyClosingTag` (standardowe motywy PrestaShop to robią).

## Instalacja

1. Pobierz `apline_simple_edit_css_js.zip` z zakładki [Releases](../../releases/latest).
2. Panel PrestaShop → Moduły → Menedżer modułów → „Załaduj moduł” → wskaż ZIP.
3. Kliknij „Konfiguruj”.

Instalacja z Gita lub przez FTP: skopiuj katalog modułu do `modules/apline_simple_edit_css_js` (nazwa folderu musi być równa nazwie modułu), a potem zainstaluj moduł w Menedżerze modułów.

Przy instalacji powstają dwa fragmenty startowe: aktywny „Miejsce na własny CSS” i wyłączony „Odtwarzacz YouTube w opisie produktu”.

## Konfiguracja

1. Panel → Moduły → Menedżer modułów → „APLINE Simple Edit CSS/JS dla PrestaShop 9” → „Konfiguruj”.
2. Strona konfiguracji ma dwa bloki:
   - **Fragmenty CSS/JS** — duży przycisk **Zarządzaj fragmentami CSS/JS** prowadzi do listy fragmentów;
   - **Ustawienia** — przełącznik **Automatycznie formatuj CSS przy zapisie** (domyślnie „Tak”) i przycisk **Zapisz**. Gdy jest włączony, każdy zapisany fragment CSS jest przeformatowany; wyłącz go, jeśli samodzielnie dbasz o układ kodu.
3. Lista fragmentów pokazuje kolumny: ID, Nazwa, Typ, Miejsce wstawienia, Kiedy uruchomić, Podgląd kodu, Aktywny i Pozycja. Przyciski nad listą: **Dodaj fragment** i **Wróć do konfiguracji**. Wiersz można edytować lub usunąć, a przełącznik w kolumnie „Aktywny” włącza i wyłącza fragment bez wchodzenia do formularza.
4. W formularzu fragmentu (**Dodaj fragment** albo edycja wiersza) wypełnij pola:
   - **Nazwa** — twoja etykieta, wymagana, do 255 znaków;
   - **Typ** — „JavaScript” albo „CSS”;
   - **Kod** — treść fragmentu, wymagana; trafia na stronę bez zmian;
   - **Miejsce wstawienia** — „Bezpośrednio w `<head>`” albo „Tuż przed `</body>` (tylko JS)”; fragmenty CSS zawsze trafiają do `<head>`;
   - **Kiedy uruchomić** (tylko JS) — „Uruchom natychmiast” albo „Po załadowaniu struktury strony (DOMContentLoaded)”; tej drugiej opcji użyj, gdy skrypt działa na elementach strony;
   - **Aktywny** — „Tak” wstawia fragment na stronę, „Nie” go wyłącza.
5. Kliknij duży przycisk **Zapisz**. Dla fragmentu CSS dostępny jest dodatkowo przycisk **Formatuj CSS**.
6. Kolejność ustawiasz na liście, przeciągając wiersze (kolumna „Pozycja”). Kolejność decyduje o kolejności w kodzie strony — np. ogólne style na górze, nadpisania na dole.

### Historia wersji

Przy zapisie fragmentu, w którym zmienił się kod, poprzednia wersja jest zachowywana. W formularzu edycji panel **Poprzednie wersje (ostatnie 3)** pokazuje datę każdej z nich, a przycisk **Wczytaj do edytora** wstawia jej kod do pola „Kod”. Wczytanie niczego nie zapisuje — żeby wersja stała się aktualna, kliknij jeszcze **Zapisz**. Zapis bez zmiany kodu nie tworzy nowej wersji.

### Formatowanie CSS

Przycisk **Formatuj CSS** (i opcja automatycznego formatowania przy zapisie) układa każdą deklarację w osobnym wierszu, wcina zagnieżdżone bloki (np. `@media`) i zostawia komentarze bez zmian. To prosty formater, a nie pełny parser CSS — przy nietypowym kodzie wyłącz automatyczne formatowanie i formatuj go w swoim edytorze.

## Aktualizacja

Wgraj ZIP nowej wersji tak jak przy instalacji — PrestaShop uruchomi skrypty z `upgrade/` i zachowa ustawienia. Aktualizacja do wersji 1.1.0 nie zmienia fragmentów, ich historii wersji ani ustawień modułu; zmienia tylko nazwę ukrytej zakładki panelu na „Fragmenty CSS/JS” i czyści pamięć podręczną Smarty. Fragmenty startowe istniejących instalacji zostają takie, jakie były. Jeśli po aktualizacji panel nadal pokazuje stare teksty, wyczyść pamięć podręczną (Parametry zaawansowane → Wydajność).

## Odinstalowanie

Panel → Moduły → Menedżer modułów → „APLINE Simple Edit CSS/JS dla PrestaShop 9” → „Odinstaluj”. Odinstalowanie jest **nieodwracalne**:

- znikają tabele `asec_snippet` i `asec_snippet_version` (z prefiksem tabel sklepu) — wszystkie fragmenty i ich historia wersji;
- znika ustawienie `ASEC_FORMAT_CSS_ON_SAVE`, ukryta zakładka panelu i rejestracje hooków.

Moduł nie ma eksportu, więc jeśli chcesz zachować fragmenty, **zrób kopię tabeli `asec_snippet` przed odinstalowaniem**.

## Jak to działa

- **Hooki.** `displayHeader` wstawia w `<head>` najpierw wszystkie aktywne fragmenty CSS (jako `<style>`, żeby uniknąć błysku nieostylowanej strony), potem aktywny JavaScript z miejscem „w `<head>`”. `displayBeforeBodyClosingTag` wstawia JavaScript z miejscem „przed `</body>`”. Wewnątrz każdej grupy obowiązuje kolejność z kolumny „Pozycja”.
- **Moment uruchomienia.** Dla „Po załadowaniu struktury strony” kod jest opakowywany w nasłuch `DOMContentLoaded`; „Uruchom natychmiast” wstawia go bez opakowania.
- **Kod bez zmian.** Fragmenty trafiają na stronę dokładnie tak, jak zostały wpisane. To zamierzone: fragmenty tworzy wyłącznie administrator panelu z pełnymi uprawnieniami, klient sklepu nie ma takiej możliwości, więc nie jest to luka XSS. Błędny kod może zepsuć wygląd lub działanie sklepu, dlatego najpierw sprawdzaj go na kopii testowej.
- **Znaczniki.** Każdy wstawiony blok ma atrybut `data-asec-id` z numerem fragmentu, więc w narzędziach przeglądarki łatwo znaleźć, który fragment go wygenerował.
- **Zasięg.** Aktywne fragmenty ładują się na wszystkich stronach sklepu — moduł nie ma wyboru stron, języków ani sklepów w trybie multistore.
- **Dane.** Fragmenty są w tabeli `asec_snippet`, historia w `asec_snippet_version` (z kluczem obcym i kasowaniem kaskadowym), ustawienie modułu w konfiguracji PrestaShop (`ASEC_FORMAT_CSS_ON_SAVE`).
- **Prywatność.** Moduł niczego nie zbiera i nigdzie nie wysyła. Wyłączony przykład YouTube po włączeniu używa miniatury z opisu produktu (albo z `img.youtube.com`), a po kliknięciu ładuje odtwarzacz z `youtube.com`.

## Rozwiązywanie problemów

- **PrestaShop odrzuca ZIP.** Nazwa folderu, nazwa głównego pliku `.php` i nazwa klasy muszą być takie same (`apline_simple_edit_css_js`), a archiwum musi mieć ten folder w katalogu głównym i ścieżki z ukośnikami `/`. Pobierz oficjalny ZIP z Releases; nie pakuj źródeł Eksploratorem Windows, bo bywa, że zapisuje ukośniki `\`.
- **Fragment się nie pojawia albo psuje stronę.** Sprawdź, czy przełącznik „Aktywny” jest włączony. Dla JavaScriptu działającego na elementach strony wybierz „Po załadowaniu struktury strony (DOMContentLoaded)”. Fragment CSS musi być w `<head>`. Znajdź blok po atrybucie `data-asec-id` w narzędziach przeglądarki i wyczyść pamięć podręczną (Parametry zaawansowane → Wydajność).
- **„Niedomknięte nawiasy klamrowe w CSS”.** Liczba `{` i `}` w kodzie CSS jest różna (zawartość komentarzy jest pomijana). Zwykle brakuje zamykającego `}`.

## Zmiany

Historia wersji: [CHANGELOG.md](CHANGELOG.md).

## Licencja i autor

Custom Attribution License v1.0 — pełny tekst w [LICENSE.md](LICENSE.md). Moduł możesz używać komercyjnie, modyfikować, rozpowszechniać i dołączać do projektów klientów. Nie wolno usuwać ani ukrywać informacji o autorze (APLINE) ze strony konfiguracji modułu: ma być widoczna, prowadzić do <https://apline.pl> i mieć czytelną czcionkę (co najmniej 12 px).

APLINE Arkadiusz Pielechowski · [apline.pl](https://apline.pl)
