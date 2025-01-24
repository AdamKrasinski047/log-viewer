$(document).ready(function () {
    let table = $('#logTable').DataTable({
        ajax: {
            url: 'logs.php',
            data: function (d) {
                d.source = $('#logSource').val();
            },
            dataSrc: '',
        },
        pageLength: parseInt($('#entriesPerPage').val()),
        columns: [
            { data: 'date', title: "Dzień", width: '120px' },
            { data: 'time', title: "Czas", width: '100px' },
            { data: 'type', title: "Zdarzenie", width: '100px' },
            { data: 'objType', title: "Obiekt", width: '200px' },
            { data: 'user', title: "Użytkownik", width: '80px' },
            { data: 'details', title: "Szczegóły", className: 'details', width: '500px' }
        ],
        autoWidth: false,
    });

    // Przypisywanie klas na podstawie typu zdarzenia
    function applyRowColors() {
        $('#logTable tbody tr').each(function () {
            const rowData = table.row(this).data(); // Pobierz dane wiersza
            if (!rowData) return; // Pomijaj puste wiersze

            // Usuń istniejące klasy kolorowania
            $(this).removeClass('error-row info-row debug-row');

            // Przypisz odpowiednią klasę tylko, gdy kolorowanie jest włączone
            if ($('#colorizeRows').is(':checked')) {
                if (rowData.type === 'ERROR') {
                    $(this).addClass('error-row');
                } else if (rowData.type === 'INFO') {
                    $(this).addClass('info-row');
                } else if (rowData.type === 'DEBUG') {
                    $(this).addClass('debug-row');
                }
            }
        });
    }

    // Obsługa przełącznika kolorowania wierszy
    $('#colorizeRows').on('change', function () {
        applyRowColors();
    });

    // Obsługa odświeżania kolorów po załadowaniu danych
    $('#logTable').on('draw.dt', function () {
        applyRowColors();
    });

    // Przycisk 'Ukryj szczegóły'
    $('#toggleDetailsBtn').on('click', function () {
        const allExpanded = $('#logTable tbody td.details').hasClass('expanded');

        if (allExpanded) {
            // Zwijamy wszystkie szczegóły
            $('#logTable tbody td.details').removeClass('expanded').addClass('collapsed');
        } else {
            // Rozwijamy wszystkie szczegóły
            $('#logTable tbody td.details').removeClass('collapsed').addClass('expanded');
        }
    });

    // Obsługa kliknięcia w pojedyncze komórki szczegółów
    $('#logTable tbody').on('click', 'td.details', function () {
        const $cell = $(this);
        if ($cell.hasClass('expanded')) {
            $cell.removeClass('expanded').addClass('collapsed');
        } else {
            $cell.removeClass('collapsed').addClass('expanded');
        }
    });

    // Filtrowanie według daty
    $.fn.dataTable.ext.search.push(function (settings, data) {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        if (startDate || endDate) {
            const rowDate = new Date(`${data[0]} ${data[1]}`).getTime();
            const start = startDate ? new Date(startDate).getTime() : null;
            const end = endDate ? new Date(endDate).getTime() : null;

            if ((start && rowDate < start) || (end && rowDate > end)) {
                return false;
            }
        }
        return true;
    });

    // Aktualizacja tabeli po zmianie daty
    $('#startDate, #endDate').on('change', function () {
        table.draw();
    });

    // Filtrowanie według typu
    $('#typeFilter').on('change', function () {
        table.column(2).search(this.value).draw();
    });

    // Filtrowanie według użytkownika
    $('#userFilter').on('change', function () {
        table.column(4).search(this.value).draw();
    });

    // Przycisk 'Odśwież'
    $('#refreshBtn').on('click', function () {
        table.ajax.reload();
    });

    // Automatyczne odświeżanie
    let autoRefreshInterval;
    $('#autoRefresh').on('change', function () {
        if ($(this).is(':checked')) {
            autoRefreshInterval = setInterval(function () {
                zassejLoga();
            }, 5000);
        } else {
            clearInterval(autoRefreshInterval);
        }
    });

    // Zmiana liczby wierszy na stronę
    $('#entriesPerPage').on('change', function () {
        table.page.len($(this).val()).draw();
    });

    // Funkcja śledzenia logów
    function zassejLoga() {
        $.ajax({
            url: 'logs.php',
            method: "POST",
            data: {
                'source': $('#logSource').val(),
                'isTail': true
            },
            dataSrc: '',
        }).done(function (ret) {
            console.log(ret);
        });
    }

    // Inicjalizacja trybu ciemnego
    const themeSwitcher = document.getElementById('themeSwitcher');

    // Załaduj poprzedni stan motywu z localStorage
    const darkModeEnabled = localStorage.getItem('darkMode') === 'true';
    if (darkModeEnabled) {
        document.body.classList.add('dark-mode');
        themeSwitcher.checked = true;
    }

    // Obsługa przełączania trybu ciemnego
    themeSwitcher.addEventListener('change', () => {
        if (themeSwitcher.checked) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('darkMode', 'true');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('darkMode', 'false');
        }
    });
});
