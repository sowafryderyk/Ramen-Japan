# Ramen Japan – opis projektu

## 1. Nazwa i autorzy

**Nazwa projektu:** Ramen Japan

**Autorzy:** 
- Fryderyk Sowa
- Piotr Leś

## 2. Cel aplikacji

Celem projektu jest stworzenie strony internetowej umożliwiającej zamawianie tradycyjnego japońskiego ramenu przez Internet z dostawą na Słotwinę.

Aplikacja jest przeznaczona dla osób zainteresowanych kuchnią japońską, które chcą zamawiać różne rodzaje ramenu bez wychodzenia z domu.

Strona rozwiązuje problem braku wygodnego sposobu zamawiania japońskich potraw online. Użytkownik będzie mógł zapoznać się z menu, sprawdzić składniki i ceny dań, dodać produkty do koszyka oraz złożyć zamówienie.

**Przykład użycia:** Klient wchodzi na stronę Ramen Japan, wybiera Miso Ramen, dodaje danie do koszyka, podaje adres dostawy na Słotwinę i składa zamówienie.

## 3. Planowane funkcje

Aplikacja będzie umożliwiała użytkownikom wykonywanie następujących czynności:

1. **Przeglądanie menu** – użytkownik może przeglądać dostępne rodzaje ramenu, ich zdjęcia, opisy, składniki oraz ceny.
2. **Obsługa koszyka** – użytkownik może dodawać dania do koszyka, zmieniać ich ilość, usuwać produkty oraz sprawdzać łączną cenę.
3. **Składanie zamówień** – klient może podać swoje dane kontaktowe i adres dostawy, a następnie złożyć zamówienie.
4. **Rejestracja i logowanie** – użytkownik może utworzyć konto i zalogować się do aplikacji.
5. **Historia zamówień** – zalogowany klient może sprawdzać swoje wcześniejsze zamówienia oraz ich aktualny status.
6. **Kontakt z restauracją** – użytkownik może sprawdzić dane kontaktowe restauracji i godziny przyjmowania zamówień.
7. **Zarządzanie menu i zamówieniami** – administrator może dodawać, usuwać i edytować dania, zmieniać ceny oraz kontrolować zamówienia.

## 4. Technologie i dane

Do stworzenia aplikacji planujemy wykorzystać następujące technologie:

- **HTML** – struktura strony internetowej.
- **CSS / Tailwind CSS** – wygląd i stylowanie elementów strony.
- **JavaScript** – obsługa interakcji użytkownika.
- **React** – budowa interfejsu użytkownika.
- **PHP** – obsługa logiki po stronie serwera i komunikacja z bazą danych.
- **MySQL** – przechowywanie danych.

W bazie danych MySQL będą przechowywane:
- Dane użytkowników i ich kont.
- Informacje o daniach, składnikach i cenach.
- Zamówienia klientów.
- Szczegóły zamówień i ich statusy.

Za obsługę danych będzie odpowiadał backend napisany w PHP, który będzie komunikował się z bazą danych MySQL. Interfejs React będzie przesyłał żądania do serwera i wyświetlał otrzymane dane.

## 5. Zagrożenia i zabezpieczenia

| Zagrożenie | Zabezpieczenie |
|---|---|
| Nieuprawniona osoba może próbować wejść do panelu administratora. | Logowanie administratora, kontrola uprawnień i zabezpieczenie podstron. |
| Użytkownik może próbować zmienić cenę produktu lub całkowitą wartość zamówienia. | Obliczanie cen po stronie serwera, a nie tylko w przeglądarce. |
| Ktoś może przesłać nieprawidłowe dane podczas składania zamówienia. | Walidacja danych, sprawdzanie wymaganych pól i poprawności adresu e-mail. |
| Atakujący może próbować wykonać atak SQL Injection. | Stosowanie zapytań przygotowanych (Prepared Statements) w PHP. |
| Ktoś może próbować zalogować się na cudze konto. | Bezpieczne hashowanie haseł oraz ograniczenie liczby prób logowania. |
| Użytkownik może próbować usunąć lub edytować zamówienia innych klientów. | Sprawdzanie tożsamości użytkownika i jego uprawnień przed wykonaniem operacji. |

## 6. Plan pracy

Projekt będzie realizowany przez dwie osoby. Praca zostanie podzielona na następujące etapy:

### Etap 1 – Planowanie i przygotowanie
- Ustalenie wyglądu strony i jej funkcjonalności.
- Przygotowanie repozytorium GitHub.
- Zaplanowanie struktury bazy danych.

### Etap 2 – Tworzenie interfejsu
- **Osoba 1:** Przygotowanie strony głównej, menu dań oraz podstrony kontaktowej.
- **Osoba 2:** Przygotowanie widoku koszyka, formularza zamówienia oraz widoków logowania i rejestracji.

### Etap 3 – Backend i baza danych
- **Osoba 1:** Przygotowanie bazy danych MySQL oraz obsługi użytkowników i logowania.
- **Osoba 2:** Przygotowanie obsługi menu, koszyka i składania zamówień w PHP.

### Etap 4 – Panel administratora
- **Osoba 1:** Przygotowanie zarządzania daniami i ich cenami.
- **Osoba 2:** Przygotowanie zarządzania zamówieniami i ich statusami.

### Etap 5 – Testowanie i poprawki
- Sprawdzenie poprawności działania wszystkich funkcji.
- Testowanie rejestracji, logowania i składania zamówień.
- Sprawdzenie zabezpieczeń i poprawności danych.
- Poprawienie błędów i dopracowanie wyglądu strony.

### Podział obowiązków

**Osoba 1:**
- Strona główna i menu.
- Kontakt.
- Baza danych.
- Rejestracja i logowanie.
- Zarządzanie daniami w panelu administratora.

**Osoba 2:**
- Koszyk zakupowy.
- Formularz składania zamówień.
- Obsługa zamówień w PHP.
- Historia zamówień.
- Zarządzanie statusami zamówień.

Obie osoby będą wspólnie odpowiadać za testowanie aplikacji, poprawianie błędów oraz końcowe połączenie wszystkich elementów projektu.
