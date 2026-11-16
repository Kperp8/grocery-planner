# AGENTS.md

Instrukcje dla agentów AI pracujących w repozytorium GroceryPlanner. Informacje zweryfikowano w plikach repozytorium (gałąź `frontend-views`, commit `4581d6e`). Miejsca bez potwierdzenia w kodzie oznaczono jako **BRAK**.

## 1. Stan projektu

- Wczesny etap: aplikacja PHP z czterema trasami GET (`/`, `/planner`, `/recipes`, `/pantry`) i stroną 404. Brak logiki domenowej, bazy danych i API.
- Teksty UI są po polsku; identyfikatory w kodzie po angielsku.
- README oznacza sekcje „Tech stack" i „Struktura projektu" jako WORK IN PROGRESS.

## 2. Struktura katalogów

```
public/index.php          front controller: rejestracja tras i dispatch
src/Routing.php           router (namespace App)
src/controllers/          AppController (abstrakcyjny), DefaultController
public/views/             szablony *.html.php (statyczne eksporty z Figmy, bez kodu PHP)
public/css/               jeden arkusz na widok: <nazwa>-style.css
public/assets/<widok>/    SVG/PNG z eksportu Figmy
docker/php/, docker/nginx/  Dockerfile'e; nginx.conf
vendor/                   generowany przez Composera (gitignored)
```

## 3. Technologie i zależności

- PHP 8.3 (`docker/php/Dockerfile`: `php:8.3-fpm-alpine`). Każdy plik PHP zaczyna się od `declare(strict_types=1);`. **BRAK** deklaracji wersji PHP w `composer.json`.
- Composer: wyłącznie autoload PSR-4 (`composer.json`). Brak `require`/`require-dev`, brak `composer.lock`.
- Rozszerzenia PHP w obrazie: opcache, zip, gd, bcmath, pgsql, pdo_pgsql. Żaden kod ich dziś nie używa (pgsql/pdo_pgsql są pozostałością po usuniętej usłudze bazy danych).
- Usługi w `docker-compose.yaml`: `server` (nginx) i `php` (PHP-FPM), obie w sieci `pg-network`. Nie ma innych usług; PostgreSQL, Ollama, Mailpit i pgAdmin zostały usunięte, bo żaden kod ich nie używał.
- Frontend: HTML + CSS. Brak JavaScript (widoki nie zawierają `<script>`), brak `package.json` i narzędzi buildowych.

## 4. Architektura i przepływ żądania

1. nginx (`docker/nginx/nginx.conf`): `try_files $uri $uri/ /index.php?$query_string`; PHP przekazywany FastCGI do `php:9000`.
2. `public/index.php` rejestruje trasy przez `$routing->get(...)` i wywołuje `Routing::dispatch()`.
3. Kontroler musi dziedziczyć po `App\Controller\AppController`; w przeciwnym razie `Routing` rzuca `RuntimeException`.
4. `AppController::render('<widok>', $vars)` wykonuje `require public/views/<widok>.html.php`; rzuca wyjątek, gdy plik nie istnieje.

Ograniczenia do przestrzegania:

- **Parametry tras**: segment `{nazwa}` pasuje wyłącznie do `\d+`, jest rzutowany na `int` i przekazywany jako argument nazwany. Nazwa parametru musi być identyczna z nazwą argumentu metody kontrolera.
- **Autoload**: `App\Controller\*` leży w `src/controllers/`, pozostałe `App\*` w `src/`. Classmap nie jest authoritative (`vendor/composer/ClassLoader.php`), więc nowe klasy działają bez `dump-autoload`, ale namespace musi odpowiadać ścieżce.
- **Zmienne widoku**: `render()` wykonuje `extract($variables, EXTR_SKIP)`, lecz **żaden szablon nie używa zmiennych**. `pageTitle` przekazywany w `DefaultController` nie trafia do widoków; `<title>` jest zahardkodowany jako `GroceryPlanner`.
- **Ścieżki zasobów**: widoki używają względnych `../css/…` i `../assets/…`, które działają dla adresów jednosegmentowych. `not-found.html.php` używa `css/…` bez `../`, co jest niespójne z pozostałymi widokami.
- **Metody HTTP**: `Routing::post()` istnieje, ale nie jest używany; `public/index.php` rejestruje wyłącznie GET.

## 5. Uruchamianie

Plik `.env` w katalogu głównym jest opcjonalny. Jest w `.gitignore` i nie jest śledzony przez git. Compose czyta z niego tylko `APP_PORT` (domyślnie 8080). **BRAK** `.env.example`.

```powershell
docker compose up --build -d
docker compose exec php composer dump-autoload
```

Aplikacja: http://localhost:8080. Bez `vendor/autoload.php` (którego nie ma w repo) `public/index.php` kończy się błędem `require_once`.

## 6. Testy, lint, build, walidacja

**BRAK**: testów (brak katalogu testów i PHPUnit), analizy statycznej (PHPStan/Psalm), formatera lub lintera (PHP-CS-Fixer/PHPCS), `.editorconfig`, CI (brak `.github/`), skryptów w `composer.json`, `package.json`.

Dostępne dziś (z katalogu głównego repo):

```powershell
# Składnia docker-compose.yaml (nie wymaga działającego demona Dockera)
docker compose config -q

# Walidacja composer.json (Composer nie jest zainstalowany lokalnie, więc w kontenerze)
docker compose exec php composer validate

# Lint składni wszystkich plików PHP, także widoków
docker compose exec php sh -c 'find public src -name \*.php -exec php -l {} \;'

# Smoke test tras (wymaga działającego docker compose up)
curl.exe -s -o NUL -w "%{http_code}\n" http://localhost:8080/planner
```

Oczekiwane kody: `/`, `/planner`, `/recipes`, `/pantry` → 200; nieistniejąca ścieżka (np. `/nie-ma`) → 404. Logi: `docker compose logs php server`.

Build: brak osobnego kroku; obrazy budowane przez `docker compose up --build`.

## 7. Konwencje (wyłącznie obserwowane w kodzie)

- PHP: `declare(strict_types=1);`, `namespace App…`, `final class` dla `DefaultController` i `Routing`, `abstract class` dla `AppController`, wcięcia 4 spacje, przecinek końcowy w wieloliniowych listach argumentów (`Routing::add`), funkcje `static fn` w callbackach.
- Nowa trasa: wpis w `public/index.php` + metoda w kontrolerze o tej samej nazwie co akcja trasy + `$this->render('<widok>', [...])`.
- Nowy widok: `public/views/<nazwa>.html.php` (kebab-case) + `public/css/<nazwa>-style.css`.
- Widoki są eksportami z Figmy (klasy `node-N`, `container-N`, `text-N`, `heading-N-M`). Historia commitów pokazuje, że były podmieniane w całości. **BRAK** udokumentowanej procedury ręcznej edycji.
- Commity: prefiksy `chore:` i `fix:` (np. `4581d6e chore: change recipes view from .html to .html.php`).
- Komentarze: rzadkie i po polsku; jedyny blok to komentarz nad `Routing::createPattern()`. Nie kopiuj jego wcięć tabulatorami, bo to wyjątek w projekcie.

## 8. Bezpieczeństwo

Istniejące mechanizmy:

- `Routing::createPattern()` escapuje literalne fragmenty trasy (`preg_quote`); parametry akceptują tylko cyfry.
- `Routing::dispatch()` dopasowuje metodę HTTP i odrzuca kontrolery niedziedziczące po `AppController`.
- Nazwy szablonów w `render()` są stałymi w kodzie. Jeśli kiedykolwiek trafią tam dane z wejścia, nazwę trzeba walidować, bo ścieżka jest składana przez konkatenację.
- nginx: `location ~ /\. { deny all; }` blokuje pliki ukryte; `client_max_body_size 512M`.
- `.env` nie jest śledzony i nie występuje w historii git (`git log --all -- .env` jest puste).

Brak (**BRAK**): uwierzytelniania, sesji, CSRF, walidacji i escapingu danych wejściowych (brak `htmlspecialchars`, bo nie ma danych dynamicznych), nagłówków bezpieczeństwa w nginx, HTTPS, limitów żądań.

Ryzyka do rozważenia (nie są częścią tego dokumentu do naprawy):

- Szablony `public/views/*.html.php` leżą w document root, a nginx wykonuje każdy `*.php` z `/app/public`. Są więc dostępne bezpośrednio pod `/views/<nazwa>.html.php`.
- `docker-compose.yaml` publikuje port `server` (`APP_PORT`, domyślnie 8080) na wszystkich interfejsach, bez przypisania do `127.0.0.1`.

## 9. Braki informacji (**BRAK**)

- **Dane**: brak kodu łączącego się z bazą, brak migracji i schematu. Nie wiadomo, jak modelowane są przepisy, spiżarnia i plan. Gdy pojawi się baza, trzeba będzie ponownie dodać usługę do `docker-compose.yaml` i zmienne do `.env`.
- **Logika domenowa**: brak modeli, serwisów i endpointów API; `DefaultController` zwraca wyłącznie statyczne strony.
- **Frontend interaktywny**: brak JS. Nie wiadomo, czy jest planowany i jaka technologia.
- **Wersja PHP**: brak ograniczenia w `composer.json`; jedynym źródłem jest Dockerfile.
- **Konfiguracja**: brak `.env.example`, brak opisu zmiennych w README, brak konfiguracji produkcyjnej.
- **Jakość**: brak testów, lintera i CI, więc nie ma ustalonego progu jakości.
- **README**: `cd GroceryPlanner` nie zgadza się z nazwą katalogu klonowanego z `grocery-planner.git`; brak kroku z `.env`; sekcje Tech stack i Struktura są niewypełnione.
- **Licencja**: brak pliku LICENSE.
- **Nieużywane elementy**: `public/views/nav.html.php` nie jest włączany przez żaden widok. Pusty katalog `docker/db/` pozostał na dysku po usunięciu usługi `db`; nie jest śledzony przez git.

## 10. Zasady dla agentów

- Nie commituj `.env` ani `vendor/`.
- Przed zmianą tras przeczytaj `public/index.php` i `DefaultController`. Trasy rejestruj tylko w `public/index.php`.
- Nie zmieniaj widoków poza zakresem zadania; są to eksporty z Figmy.
- Po zmianach w PHP uruchom lint i smoke test z sekcji 6.
- Zachowuj polskie teksty UI.
