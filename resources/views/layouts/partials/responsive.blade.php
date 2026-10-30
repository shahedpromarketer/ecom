{{-- Shared by the admin and super-admin layouts: no sideways page scrolling, and list tables as cards on phones. --}}
<style>
        /* Grid / flex children may shrink below their content (charts, long text, tables) instead of pushing the page wider. */
        main .grid > * { min-width: 0; }
        main canvas { max-width: 100%; }

        /* List tables become cards on phones: one card per row, column name beside each value
           (labels are filled in from the table header by epStackTables() below). */
        @media (max-width: 767px) {
            table.ep-stack, table.ep-stack > tbody { display: block; width: 100%; min-width: 0 !important; }
            table.ep-stack > thead { display: none; }
            table.ep-stack > tbody > tr { display: block; padding: .75rem 1rem; border-bottom: 1px solid rgb(0 0 0 / .07); }
            /* label on the left, everything in the cell stacked on the right */
            table.ep-stack > tbody > tr > td { display: grid; grid-template-columns: minmax(0, 40%) minmax(0, 1fr); column-gap: .75rem; row-gap: .15rem;
                align-items: start; justify-items: end; padding: .25rem 0 !important; border: 0 !important;
                width: auto !important; max-width: none !important; text-align: right; }
            table.ep-stack > tbody > tr > td > * { min-width: 0; max-width: 100%; grid-column: 2; }
            table.ep-stack > tbody > tr > td::before { content: attr(data-label); grid-column: 1; grid-row: 1; justify-self: start;
                text-align: left; font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: #6b7280; padding-top: .2rem; }
            .dark table.ep-stack > tbody > tr > td::before { color: #9ca3af; }
            table.ep-stack > tbody > tr > td:first-child,
            table.ep-stack > tbody > tr > td[data-label=""] { grid-template-columns: minmax(0, 1fr); justify-items: start; text-align: left; }
            table.ep-stack > tbody > tr > td:first-child > *,
            table.ep-stack > tbody > tr > td[data-label=""] > * { grid-column: 1; }
            table.ep-stack > tbody > tr > td:first-child::before,
            table.ep-stack > tbody > tr > td[data-label=""]::before { display: none; }
            table.ep-stack > tbody > tr > td[colspan] { display: block; text-align: center; }
            table.ep-stack > tbody > tr > td[colspan]::before { display: none; }
            table.ep-stack > tbody > tr > td:empty { display: none; }
        }

</style>
<script>
// Phone card tables: label every cell with its column name (also rows added later by Alpine / AJAX).
window.epStackTables = function (root = document) {
    root.querySelectorAll('table.ep-stack').forEach(table => {
        const heads = [...table.querySelectorAll(':scope > thead th')].map(th => th.innerText.replace(/\s+/g, ' ').trim());
        table.querySelectorAll(':scope > tbody > tr').forEach(tr => {
            let i = 0;
            [...tr.children].forEach(td => {
                if (! td.hasAttribute('data-label')) td.setAttribute('data-label', heads[i] || '');
                i += parseInt(td.getAttribute('colspan') || '1', 10);
            });
        });
        if (! table.__epWatch) {
            table.__epWatch = new MutationObserver(() => window.epStackTables(table.parentElement));
            table.__epWatch.observe(table, { childList: true, subtree: true });
        }
    });
};
document.addEventListener('DOMContentLoaded', () => window.epStackTables());
</script>
