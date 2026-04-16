# BESTLAB Event Ticket (`besteventticket`) — README / stan projektu

## Cel modułu

Moduł `besteventticket` służy do obsługi potwierdzeń obecności uczestników wydarzeń sprzedawanych jako produkty w PrestaShop.

Moduł **nie opiera się operacyjnie na panelu modułu `selltickets`**, ponieważ listing / widoki `selltickets` okazały się niewiarygodne. Dane źródłowe biletów będą finalnie importowane do naszej własnej tabeli zgodnej z logiką modułu `besteventticket`.

Główny cel biznesowy modułu:
- zebrać informację, ile osób faktycznie przyjdzie na wydarzenie
- zebrać imiona i nazwiska gości
- uzyskać raport per bilet
- mieć możliwość łatwego policzenia frekwencji / liczby miejsc / „krzeseł”
- później umożliwić wysyłkę maili potwierdzających do klientów

Na obecnym etapie rdzeń modułu działa już dla:
- własnej tabeli danych
- formularza frontowego po tokenie
- zapisu potwierdzeń
- raportu w panelu administracyjnym
- statystyk nad tabelą w BO

Do dopięcia pozostaje głównie mechanizm mailingu oraz późniejszy finalny import danych.

---

## Założenia biznesowe

### 1. Bilet nie jest imienny

Bilety nie są przypisywane „na sztywno” do konkretnej osoby na etapie zakupu.  
Nie próbujemy budować systemu imiennych wejściówek.

Interesuje nas:
- ile osób przyjdzie
- jakie dane gości zostały podane
- z jakiego zamówienia pochodzą te miejsca

Jeżeli zamówienie zawiera 4 bilety, a użytkownik wpisze 2 osoby:
- uznajemy, że potwierdził 2 miejsca
- pozostałe 2 miejsca są niepotwierdzone / puste
- nie ma znaczenia, który dokładnie numer biletu przypisano do którego gościa
- przypisanie odbywa się kolejno po `ticket_position`

### 2. Logika formularza

Grupa formularza jest definiowana przez:
- `id_order`
- `id_product`

Wszystkie rekordy biletów z tym samym:
- `id_order`
- `id_product`

tworzą jeden wspólny formularz potwierdzenia.

#### Przypadek: 1 bilet w zamówieniu

Jeśli `qty_in_order = 1`, użytkownik:
- nie widzi pola tekstowego
- widzi tylko przycisk „Potwierdzam”
- dane gościa są automatycznie wypełniane z danych zamówienia:
  - `customer_firstname + customer_lastname`
- po kliknięciu zapisujemy:
  - `guest_name = imię + nazwisko klienta`
  - `confirmation = 1`

#### Przypadek: 2–4 bilety w zamówieniu

Jeśli w zamówieniu jest więcej niż 1 bilet:
- użytkownik widzi tyle pól tekstowych, ile jest biletów
- każde pole odpowiada kolejnemu rekordowi według `ticket_position ASC`

Przykład dla 4 biletów:
- Bilet 1 – pole tekstowe
- Bilet 2 – pole tekstowe
- Bilet 3 – pole tekstowe
- Bilet 4 – pole tekstowe

Reguła zapisu:
- pole wypełnione → `guest_name = wpisany tekst`, `confirmation = 1`
- pole puste → `guest_name = NULL`, `confirmation = 0`

Przycisk:
- „Potwierdzam liczbę gości według podanych danych”

### 3. Cel raportowy

Moduł ma odpowiadać na pytanie:
- ile biletów jest potwierdzonych dla wydarzenia
- ile biletów jest niepotwierdzonych
- jak wyglądają dane gości
- ile miejsc przygotować na sali

---

## Mapa wydarzeń / produktów

Finalna mapa produktów używana w module:
- `Product ID 95` = `Tour de BestLab Szczecin`
- `Product ID 96` = `Tour de BestLab Poznań`
- `Product ID 97` = `Tour de BestLab Warszawa`

Wartości te mają być stosowane w testach oraz w finalnej logice importu.

Sugestia dla `event_key`:
- `95` → `szczecin`
- `96` → `poznan`
- `97` → `warszawa`

`event_name`:
- `Tour de BestLab Szczecin`
- `Tour de BestLab Poznań`
- `Tour de BestLab Warszawa`

---

## Tabela danych modułu

Na obecnym etapie używamy **jednej tabeli**.

Nazwa tabeli:
- `ps_bestlab_event_ticket`

Jedna linia = jeden bilet.

### Struktura tabeli

```sql
CREATE TABLE `ps_bestlab_event_ticket` (
  `id_bestlab_event_ticket` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_ticket_id` INT UNSIGNED DEFAULT NULL,
  `source_cart_id` INT UNSIGNED DEFAULT NULL,
  `ticket_ref` VARCHAR(64) NOT NULL,
  `id_order` INT UNSIGNED DEFAULT NULL,
  `id_customer` INT UNSIGNED DEFAULT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `customer_phone` VARCHAR(64) DEFAULT NULL,
  `customer_firstname` VARCHAR(255) DEFAULT NULL,
  `customer_lastname` VARCHAR(255) DEFAULT NULL,
  `id_product` INT UNSIGNED NOT NULL,
  `event_key` VARCHAR(32) NOT NULL,
  `event_name` VARCHAR(255) NOT NULL,
  `ticket_position` TINYINT UNSIGNED NOT NULL,
  `qty_in_order` TINYINT UNSIGNED NOT NULL,
  `guest_name` VARCHAR(255) DEFAULT NULL,
  `confirmation` TINYINT(1) DEFAULT NULL,
  `confirmation_token` VARCHAR(128) DEFAULT NULL,
  `mail_sent_at` DATETIME DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `date_order` DATETIME DEFAULT NULL,
  `date_add` DATETIME NOT NULL,
  `date_upd` DATETIME NOT NULL,
  PRIMARY KEY (`id_bestlab_event_ticket`),
  UNIQUE KEY `uniq_ticket_ref` (`ticket_ref`),
  KEY `idx_source_cart_id` (`source_cart_id`),
  KEY `idx_id_order` (`id_order`),
  KEY `idx_customer_email` (`customer_email`),
  KEY `idx_id_product` (`id_product`),
  KEY `idx_event_key` (`event_key`),
  KEY `idx_confirmation` (`confirmation`),
  KEY `idx_confirmation_token` (`confirmation_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Znaczenie pól tabeli
Pola źródłowe
source_ticket_id — źródłowy identyfikator biletu z danych importowanych
source_cart_id — źródłowy identyfikator koszyka, pomocny przy mapowaniu cart_id -> id_order
Identyfikacja biletu
ticket_ref — numer biletu, finalnie zgodny z logiką numeracji biletów używaną historycznie
Powiązania
id_order — numer zamówienia
id_customer — ID klienta
id_product — produkt wydarzenia (95, 96, 97)
Dane klienta
customer_email
customer_phone
customer_firstname
customer_lastname
Wydarzenie
event_key — techniczny klucz wydarzenia
event_name — pełna nazwa wydarzenia
Logika miejsca / biletu
ticket_position — pozycja biletu w zamówieniu (1, 2, 3, 4)
qty_in_order — liczba biletów w danym zamówieniu dla tego wydarzenia
Potwierdzenie
guest_name — wpisane imię i nazwisko uczestnika
confirmation:
NULL = brak odpowiedzi
0 = miejsce niepotwierdzone / pole puste
1 = miejsce potwierdzone / dane wpisane
confirmation_token — wspólny token dla grupy biletów z tego samego id_order + id_product
Daty
mail_sent_at — data wysłania maila
confirmed_at — data zapisania formularza
date_order — data zamówienia
date_add, date_upd — daty techniczne tabeli
Grupowanie danych

Choć tabela jest jedna, logicznie rekordy grupujemy po:

id_order
id_product

To oznacza:

jeden mail dla jednej grupy
jeden token dla jednej grupy
jeden formularz dla jednej grupy
zapis wielu rekordów biletów w ramach jednego formularza

Wartości, które w jednej grupie się powtarzają i jest to poprawne:

id_order
customer_email
id_product
event_key
event_name
confirmation_token

To jest świadoma decyzja projektowa w MVP.

Token potwierdzenia
Założenie

Jeden token dla wszystkich biletów z:

jednego zamówienia
jednego produktu / wydarzenia
Nie używamy jawnego tokenu typu:
ORDER_ID + PRODUCT_ID w URL
Token jest hashem opartym o:
id_order
id_product
sekret sklepu (_COOKIE_KEY_)

Przykładowa logika:

hash('sha256', $idOrder . '|' . $idProduct . '|' . _COOKIE_KEY_);
Właściwości
token jest wspólny dla całej grupy
token może powtarzać się na wielu rekordach tabeli
confirmation_token nie jest unikalny
po wejściu na URL po tokenie pobierane są wszystkie rekordy tej grupy
Zachowanie formularza frontowego

Publiczny URL:

/module/besteventticket/confirm?token=...
Front controller

Plik:

controllers/front/confirm.php
Logika
odczytaj token z URL
pobierz rekordy z tabeli:
WHERE confirmation_token = token
ORDER BY ticket_position ASC
jeśli brak wyników:
pokaż komunikat o błędzie
jeśli jest 1 rekord:
pokazuj prosty przycisk „Potwierdzam”
jeśli jest więcej rekordów:
pokaż formularz z tyloma polami, ile rekordów w grupie
po zapisie:
aktualizuj guest_name
aktualizuj confirmation
ustaw confirmed_at
ustaw date_upd
Reguły zapisu
1 bilet
guest_name = customer_firstname + customer_lastname
confirmation = 1
2–4 bilety

Dla każdego rekordu wg ticket_position ASC:

pole niepuste → guest_name, confirmation = 1
pole puste → guest_name = NULL, confirmation = 0
Możliwość nadpisania

Jeśli użytkownik wejdzie ponownie z tego samego linku:

może zmienić wpisane dane
może zmienić liczbę potwierdzonych miejsc
rekordy są nadpisywane aktualnym stanem formularza

To jest zachowanie celowe i pożądane.

Widok administracyjny
Lokalizacja w menu BO

Moduł ma własny link w lewym menu panelu administracyjnego:

tytuł: Tour de Bestlab
parent id = 492

Zakładka prowadzi do kontrolera:

AdminBestEventTicket
Instalacja zakładki

Podczas instalacji modułu tworzona jest zakładka:

class_name = AdminBestEventTicket
name = Tour de Bestlab
id_parent = 492

Dodatkowo moduł ma mechanizm dopinania / aktualizacji tej zakładki przy konfiguracji, jeśli zajdzie potrzeba poprawy parenta lub nazwy.

Admin controller / raport

Plik:

controllers/admin/AdminBestEventTicketController.php

Typ:

ModuleAdminController

Tabela raportu oparta o:

ps_bestlab_event_ticket
Kolumny raportu
ID
Wydarzenie
ID produktu
Nr zamówienia
Nr biletu
E-mail
Telefon
Gość
Potwierdzenie
Poz.
Ilość w zam.
Data zamówienia
Data potwierdzenia
Mapowanie pola confirmation na etykietę w raporcie
NULL → Brak odpowiedzi
0 → Niepotwierdzone
1 → Potwierdzone
Filtrowanie i sortowanie

Raport ma umożliwiać:

filtrowanie po nazwie wydarzenia
filtrowanie po numerze biletu
filtrowanie po e-mailu
filtrowanie po telefonie
filtrowanie po gościu
filtrowanie po statusie potwierdzenia
sortowanie po standardowych kolumnach
Eksport CSV

Raport w BO ma przycisk:

Eksport CSV

Eksport obejmuje m.in.:

event_name
id_product
id_order
ticket_ref
customer_email
customer_phone
customer_firstname
customer_lastname
guest_name
confirmation
ticket_position
qty_in_order
date_order
confirmed_at
Statystyki nad tabelą

Nad tabelą w BO wyświetlane są trzy poziome boksy / sekcje statystyczne:

Szczecin [95] potwierdzono X z Y
Poznań [96] potwierdzono X z Y
Warszawa [97] potwierdzono X z Y
Znaczenie liczb
X = liczba rekordów z confirmation = 1
Y = liczba wszystkich rekordów / biletów dla danego produktu

To odpowiada bezpośrednio na potrzebę:

ile osób potwierdziło udział
ile wszystkich miejsc / biletów było sprzedanych / zaimportowanych
Template statystyk

Plik:

views/templates/admin/stats.tpl

Szablon wyświetla 3 kolumny / boksy z nazwą miasta, numerem produktu oraz wynikiem:

potwierdzono X z Y
Instalacja i odinstalowanie
Instalacja

Moduł:

tworzy tabelę ps_bestlab_event_ticket jeśli nie istnieje
tworzy zakładkę BO Tour de Bestlab pod parentem 492
Odinstalowanie

Nie wolno usuwać tabeli z danymi przy odinstalowaniu modułu.

To jest świadoma decyzja projektowa.

Powód:

dane są krytyczne operacyjnie
łatwo je stracić przez przypadek
moduł może być reinstalowany testowo
tabela ma przetrwać uninstall / reinstall

W sql/uninstall.php funkcja uninstall:

nie wykonuje DROP TABLE
zwraca po prostu true
Aktualny stan wdrożenia

Na moment sporządzenia dokumentu działają:

1. Tabela modułu
utworzona
gotowa do testów
wspiera rekordy testowe i produkcyjne
2. Front formularza
działa po tokenie
ładuje rekordy grupy
zapisuje guest_name
zapisuje confirmation
zapisuje confirmed_at
3. Generator linku testowego

W konfiguracji modułu można:

podać id_order
podać id_product
wygenerować link testowy
przypisać token do istniejących rekordów tej grupy
4. Raport w BO
działa jako osobny admin controller
ma widok listy
ma eksport CSV
5. Statystyki nad raportem
Szczecin [95]
Poznań [96]
Warszawa [97]
wynik: potwierdzono X z Y
Dane testowe / sposób testowania

Na etapie testów rekordy mogą być dodawane ręcznie do tabeli ps_bestlab_event_ticket.

Test przebiega tak:

dodać rekordy testowe dla wybranej grupy id_order + id_product
w konfiguracji modułu wygenerować token i przypisać go do tej grupy
wejść w wygenerowany link
wypełnić część pól lub wszystkie
zapisać formularz
zweryfikować wynik w raporcie BO
Założenia dotyczące importu danych

Na obecnym etapie import finalny nie jest jeszcze implementowany.

Założenie projektowe:

dane źródłowe będą finalnie zrzucane do CSV
CSV zostanie dopasowane do struktury ps_bestlab_event_ticket
import nastąpi na końcu, po zamknięciu logiki modułu

Ważne:

na razie nie skupiamy się na technice importu
import nie wpływa na obecny model danych
struktura tabeli jest już przygotowana pod późniejsze zasilenie
Założenia dotyczące źródła danych

Na etapie końcowego zasilenia źródłowego:

źródłem danych będą rekordy biletów z selltickets
w danych źródłowych może występować cart_id, a nie order_id
id_order będzie można odzyskiwać przez mapowanie:
cart_id -> ps_orders.id_cart -> ps_orders.id_order

Jednak obecnie ten etap jest odłożony na później.
Najpierw budowany jest sam moduł i logika biznesowa.

Mailing — założenia końcowe (do zrobienia)

To jest główny brakujący etap.

Cel mailingu

Wysyłamy do klienta jeden mail na grupę id_order + id_product, nie jeden mail na każdy bilet.

Treść maila

Mail ma zawierać:

nazwę wydarzenia
datę wydarzenia
lokalizację / adres wydarzenia
numer zamówienia
liczbę biletów w zamówieniu
przycisk / link:
„Chcę potwierdzić przybycie i dane gości”

Po kliknięciu:

otwiera się strona formularza modułu
Mechanizm wysyłki

Założenie:

użyć mechanizmu mailowego PrestaShop
korzystać z konfiguracji SMTP / wysyłki już ustawionej w sklepie
nie tworzyć własnego niezależnego transportu SMTP

Czyli finalnie:

moduł ma używać Mail::Send()
szablon maila ma być zgodny z systemem maili PrestaShop
Batch wysyłkowy

Planowane założenie:

nie wysyłać wszystkiego jednym requestem
rozbić wysyłkę na partie / batche
sensowny batch: około 25–50 wiadomości jednorazowo
przy około 500 mailach potrzebny będzie mechanizm bezpiecznej wysyłki partiami
Statusy związane z mailingiem

Na obecnym etapie w tabeli jest już:

mail_sent_at

W przyszłości może być potrzebne dopięcie dodatkowych statusów wysyłki, np.:

pending
sent
error
send_attempts
mail_error

Na razie nie zostało to jeszcze wdrożone.

Pliki modułu — stan aktualny / istotne elementy

Najważniejsze pliki aktualnego modułu:

besteventticket.php
sql/install.php
sql/uninstall.php
controllers/front/confirm.php
controllers/admin/AdminBestEventTicketController.php
views/templates/front/confirm_form.tpl
views/templates/front/confirm_success.tpl
views/templates/admin/stats.tpl
Nazwa modułu

Finalna nazwa modułu:

besteventticket
Najważniejsze decyzje projektowe
Decyzja 1

Nie opieramy operacyjnie modułu na selltickets.

Decyzja 2

Na obecnym etapie używamy jednej tabeli własnej.

Decyzja 3

Grupa logiczna formularza to:

id_order + id_product
Decyzja 4

Jeden token dla całej grupy biletów.

Decyzja 5

Potwierdzenia zapisujemy per bilet, nie per zamówienie.

Decyzja 6

Nie próbujemy przypisać „tożsamości” konkretnego biletu do konkretnego człowieka w sensie formalnym. Kolejność przypisania po ticket_position jest wystarczająca.

Decyzja 7

Przy uninstallu nie usuwamy tabeli.

Decyzja 8

Widok BO ma mieć link:

Tour de Bestlab
parent = 492
Decyzja 9

Nad raportem mają być 3 statystyki:

Szczecin [95]
Poznań [96]
Warszawa [97]
Co zostało do zrobienia
Najbliższy kolejny etap

Mailing:

szablon maila
podstawienie danych wydarzenia
link do formularza
wysyłka przez PrestaShop
batch wysyłkowy
Potem
finalny import danych z CSV
ewentualne dodatkowe statystyki / ulepszenia raportu
Krótkie podsumowanie stanu prac

Moduł besteventticket jest na etapie, w którym:

logika tabeli jest ustalona
formularz działa
zapis potwierdzeń działa
raport BO działa
statystyki BO działają

Został głównie:

mailing
finalny import danych

To jest aktualny punkt startowy do kontynuacji w nowym wątku.