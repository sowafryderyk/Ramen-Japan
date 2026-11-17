# Ramen Japan

Mały, działający prototyp sklepu z ramenem i dostawą na Słotwinę. Aplikacja używa PHP, MySQL, sesji i klasycznego HTML/CSS.

## Funkcje

- Rejestracja, logowanie i wylogowanie; hasła są zapisywane przez `password_hash()`, a sprawdzane przez `password_verify()`.
- Menu dostępne po zalogowaniu; zamówienie dania z podaniem ilości, adresu i numeru telefonu.
- Historia zamówień i status widoczne wyłącznie dla zalogowanego właściciela.
- Walidacja formularzy w przeglądarce i na serwerze, tokeny CSRF, zapytania PDO z parametrami oraz sesje z ochroną `HttpOnly` i `SameSite`.
- Cena i suma zamówienia są pobierane/obliczane po stronie serwera. Zapis zamówienia i jego pozycji odbywa się w transakcji.

## Uruchomienie lokalne (XAMPP)

1. Skopiuj folder `PiotrFrderykRamen` do `C:\xampp\htdocs\`.
2. Uruchom Apache i MySQL w XAMPP.
3. Otwórz phpMyAdmin (`http://localhost/phpmyadmin`), wybierz **Import** i zaimportuj plik `database.sql`. Tworzy on bazę `ramen_japan`, tabele i przykładowe menu.
4. Otwórz `http://localhost/PiotrFrderykRamen/`.
5. Załóż konto przez formularz. Hasło musi mieć co najmniej 10 znaków.

Domyślne połączenie jest przeznaczone dla lokalnego XAMPP (`root`, puste hasło). Można je zmienić zmiennymi środowiskowymi `RAMEN_DB_DSN`, `RAMEN_DB_USER` i `RAMEN_DB_PASSWORD`. Na serwerze produkcyjnym należy skonfigurować osobne konto bazy z ograniczonymi uprawnieniami i HTTPS.

## Kontrole bezpieczeństwa

- Żadna trasa menu ani historii nie renderuje danych bez zalogowanej sesji.
- Dane użytkownika są przypisywane do zamówienia z sesji, a zapytanie historii filtruje po `user_id`.
- Kwota pochodzi z aktualnej ceny w bazie, nie z formularza przeglądarki.
- Nieprawidłowe logowanie zwraca ogólny komunikat; zmiana identyfikatora zamówienia nie ujawnia zamówień innych klientów.
- Token CSRF jest wymagany przy każdym żądaniu zmieniającym stan (także wylogowaniu).

## Sprawdzenie przed prezentacją

1. Zarejestruj konto i sprawdź w phpMyAdmin, że `users.password_hash` nie zawiera hasła w postaci jawnej.
2. Spróbuj zarejestrować się ponownie tym samym e-mailem oraz zalogować z błędnym hasłem — formularz powinien pokazać komunikat błędu.
3. Wyloguj się i otwórz bezpośrednio `index.php?page=orders` — aplikacja powinna przekierować do logowania.
4. Zaloguj się, zamów ramen, a następnie potwierdź w historii nazwę dania, kwotę i status. Sprawdź też, że druga sesja użytkownika nie widzi tego zamówienia.

## Materiały do oddania

Do sprawozdania w Teams dołącz link do zdalnego repozytorium i własne zrzuty ekranu: formularze rejestracji/logowania z błędem, tabelę `users` z zahashowanym hasłem, menu/historię po zalogowaniu oraz historię commitów. Zrzuty wykonaj w uruchomionej lokalnie aplikacji i phpMyAdmin; nie umieszczaj na nich prawdziwych danych osobowych.

Folder nie był połączony ze zdalnym repozytorium. Po utworzeniu pustego repozytorium na GitHubie ustaw jego adres i wypchnij gałąź `main`:

```powershell
git remote add origin https://github.com/ponczek505/ramenSlotwina.git
git push -u origin main
```

Przed tworzeniem commitów ustaw własną nazwę i e-mail autora (`git config user.name ...` oraz `git config user.email ...`).
