# Log Viewer

**Log Viewer** to lekkie narzędzie webowe stworzone w PHP, HTML, JavaScript i CSS, służące do przeglądania i filtrowania logów systemowych bezpośrednio w przeglądarce.

---

## 📦 Funkcje

- Przeglądanie plików logów (.log) w przeglądarce
- Obsługa dwóch źródeł: `logs_pro.log` oraz `logs_beta.log`
- Kolorowanie wpisów na podstawie typu (`ERROR`, `INFO`, `DEBUG`)
- Zwijanie i rozwijanie długich wpisów (szczegóły)
- Tryb ciemny zapisywany w `localStorage`
- Filtrowanie po dacie, typie zdarzenia i użytkowniku
- Paginacja, sortowanie, automatyczne odświeżanie

---

## 🛠️ Technologie

- PHP (parser logów po stronie serwera)
- HTML + CSS (interfejs użytkownika)
- JavaScript + jQuery
- DataTables
- Flatpickr (kalendarze do zakresu dat)

---

## 🚀 Jak uruchomić projekt lokalnie (Windows + XAMPP)

### Krok 1: Pobierz projekt
Możesz sklonować repozytorium lub pobrać paczkę ZIP i wypakować.

### Krok 2: Skopiuj pliki do katalogu XAMPP

Umieść folder projektu (np. `log-viewer`) w katalogu:

```
C:\xampp\htdocs\log-viewer
```

Struktura katalogu powinna wyglądać tak:

```
log-viewer/
├── index.html
├── logs.php
├── script.js
├── styles.css
├── logs_pro.log
├── logs_beta.log
```

### Krok 3: Włącz Apache

Uruchom `XAMPP Control Panel` i kliknij `Start` przy `Apache`.

### Krok 4: Otwórz przeglądarkę

Przejdź do:
```
http://localhost/log-viewer
```

---

## 🖥️ Pliki i komponenty

### `index.html`
- Główny interfejs użytkownika z filtrowaniem, tabelą i przełącznikami
- Wczytuje dane z `logs.php` i renderuje je w tabeli

### `logs.php`
- Backendowa logika
- Odczytuje pliki `.log`, parsuje linie i zwraca dane jako JSON
- Obsługuje logi wieloliniowe i wyodrębnia użytkownika oraz typ komponentu

### `script.js`
- Inicjalizuje DataTables i ładuje dane z serwera
- Obsługuje kolorowanie, filtrowanie, rozwijanie szczegółów, tryb ciemny i auto-refresh

### `styles.css`
- Stylowanie interfejsu, kolorowanie błędów, tryb ciemny, efekt hover i zwijanie wpisów

---

## 🧪 Przykład wpisu logu

```
2024-11-20 07:15:09.191  INFO 3161924 --- [exec-3] SomeClass: user4 Executing task
```

Po sparsowaniu:
```json
{
  "date": "2024-11-20",
  "time": "07:15:09.191",
  "type": "INFO",
  "objType": "SomeClass",
  "user": "user4",
  "details": "Executing task"
}
```

---

## 📄 Wymagania

- PHP 7.0+
- Przeglądarka wspierająca JS (Chrome, Firefox, Edge)
- XAMPP lub inny lokalny serwer www

---

## ⚙️ Możliwe rozszerzenia

- Obsługa parametrów `startDateTime` i `isTail` w backendzie
- Podświetlanie słów kluczowych w szczegółach
- Eksport logów do CSV / PDF
- Wersja mobilna z dostosowanym UI
- Wyszukiwarka tekstowa w kolumnie `details`

---

# Log Viewer – Szczegółowe omówienie działania

Ten dokument zawiera dokładny opis, jak działa aplikacja Log Viewer – krok po kroku, wraz z wyjaśnieniem najważniejszych funkcji backendowych (PHP) i frontendowych (JavaScript/jQuery).

---

## 🔁 Jak działa aplikacja? Krok po kroku

1. **Użytkownik otwiera stronę** `index.html` w przeglądarce.
2. **JavaScript (DataTables)** inicjalizuje tabelę i wysyła zapytanie do `logs.php`.
3. **PHP (`logs.php`)**:
   - Otwiera wskazany plik logu (`logs_pro.log` lub `logs_beta.log`)
   - Parsuje każdą linię logu (INFO, DEBUG, ERROR)
   - Zwraca dane jako JSON
4. **JavaScript** odbiera dane i wyświetla je w tabeli z funkcjami:
   - filtrowania po dacie, typie, użytkowniku
   - kolorowania
   - rozwijania/zamykania szczegółów
   - trybu ciemnego
   - automatycznego odświeżania

---

## 🧠 Kluczowe funkcje PHP (`logs.php`)

### 🔹 `parseLogLine($line, &$is_error, &$is_new_error, &$maxTimestamp)`
Parsuje pojedynczą linię logu. Przykład:

```php
$tempLineEx = explode(' --- [', $line, 2);
$typeMatch = preg_match('/\s(INFO|DEBUG|ERROR)\s/', $leftPart, $typeMatches);
```

➡️ Wyodrębnia:
- datę i godzinę (`date`, `time`)
- typ logu (`INFO`, `DEBUG`, `ERROR`)
- typ obiektu (`objType`)
- nazwę użytkownika (`user`)
- szczegóły (`details`)

### 🔹 `getUserName($details)`
Wyszukuje nazwę użytkownika na podstawie słów kluczowych, np.:

```php
$userPattern = '/^(\w+)\s/';
```

➡️ Jeśli `user1 Executing` → zwróci `user1`.

---

## ⚙️ Pętla przetwarzająca logi

```php
foreach ($lines as $line) {
    $parsedLine = parseLogLine($line, $is_error, $is_new_error, $maxTimestamp);
    ...
    $logData[] = $parsedLine;
}
```

➡️ Obsługuje błędy wieloliniowe (`ERROR`) za pomocą bufora `$bufor`.

---

## 🧠 Kluczowe funkcje JavaScript (`script.js`)

### 🔹 Inicjalizacja DataTables

```js
$('#logTable').DataTable({
    ajax: { url: 'logs.php', dataSrc: '' },
    columns: [ ... ]
});
```

➡️ Wczytuje dane z backendu i rysuje tabelę.

---

### 🔹 Kolorowanie wierszy

```js
function applyRowColors() {
    if (rowData.type === 'ERROR') $(this).addClass('error-row');
}
```

➡️ Ustawia klasę CSS zależnie od typu logu.

---

### 🔹 Filtrowanie po dacie

```js
$.fn.dataTable.ext.search.push(function (settings, data) {
    const rowDate = new Date(`${data[0]} ${data[1]}`);
    ...
});
```

➡️ Wiersz zostaje wyświetlony tylko, jeśli mieści się w zakresie `startDate` – `endDate`.

---

### 🔹 Filtrowanie po typie i użytkowniku

```js
$('#typeFilter').on('change', function () {
    table.column(2).search(this.value).draw();
});
```

➡️ Filtruje po kolumnie `type` (indeks 2).

---

### 🔹 Rozwijanie i zwijanie szczegółów

```js
$('#toggleDetailsBtn').on('click', function () {
    $('.details').toggleClass('expanded collapsed');
});
```

➡️ Zmienia klasę komórki, co zmienia jej wysokość (CSS `max-height`).

---

### 🔹 Auto-refresh

```js
setInterval(() => {
    table.ajax.reload();
}, 5000);
```

➡️ Automatycznie odświeża logi co 5 sekund (gdy opcja włączona).

---

## 🌙 Tryb ciemny

```js
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}
```

➡️ Przechowuje preferencję motywu użytkownika.

---

## 📦 Podsumowanie

Aplikacja Log Viewer to kompletny interfejs do analizy logów:
- posiada backend do parsowania logów (`PHP`)
- oferuje nowoczesny interfejs frontendowy (`jQuery`, `DataTables`)
- zawiera filtry, przełączniki, kolory i UX na poziomie produkcyjnym

Może być rozwijana jako narzędzie do:
- debugowania systemów
- monitorowania aplikacji
- edukacji



