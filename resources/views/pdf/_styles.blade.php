{{--
    Shared CSS for the PDF documents (dompdf).
    dompdf understands simple CSS only: tables instead of flex/grid, no CSS variables.
    Fonts: Barlow Semi Condensed (headings, prices) from resources/fonts, DejaVu Sans (text, accents).
--}}
<style>
    @font-face { font-family: "Barlow"; font-weight: 600; src: url("{{ str_replace('\\', '/', resource_path('fonts/BarlowSemiCondensed-SemiBold.ttf')) }}") format("truetype"); }
    @font-face { font-family: "Barlow"; font-weight: 700; src: url("{{ str_replace('\\', '/', resource_path('fonts/BarlowSemiCondensed-Bold.ttf')) }}") format("truetype"); }

    @page { margin: 34px 36px 46px 36px; }
    * { box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8.6pt; color: #14213d; line-height: 1.35; }
    h1, h2, .brand-name, .big { font-family: "Barlow", "DejaVu Sans", sans-serif; font-weight: 700; }
    h1 { font-size: 20pt; margin: 0; line-height: 1.05; }
    h2 { font-size: 12.5pt; margin: 18px 0 6px; }
    .muted { color: #5e6880; }
    .small { font-size: 7.6pt; }

    /* Header band */
    .doc-header { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .doc-header td { vertical-align: middle; padding: 0 0 10px; border-bottom: 2px solid #1f4fd1; }
    /* Brand: a table, because dompdf centres text in a cell reliably */
    .brand { border-collapse: collapse; }
    .doc-header .brand td { border: 0; padding: 0; }
    .doc-header .brand .brand-name { padding-left: 7px; }
    .brand .brand-mark { width: 22px; height: 22px; background: #1f4fd1; color: #fff; border-radius: 5px; text-align: center;
        vertical-align: middle; font-family: "Barlow", sans-serif; font-weight: 700; font-size: 12pt; line-height: 1; }
    .brand .brand-name { font-size: 15pt; padding-left: 7px; line-height: 1; vertical-align: middle; }
    .doc-title { text-align: right; }

    /* Data tables */
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead { display: table-header-group; } /* repeated on every page */
    /* No text-transform: uppercase here, dompdf measures the text before transforming it */
    table.data th { background: #14213d; color: #fff; font-weight: bold; font-size: 7.4pt; text-align: left; padding: 5px 5px; }
    table.data td { padding: 4px 5px; border-bottom: 0.6px solid #e5e1d8; vertical-align: top; }
    table.data tbody tr:nth-child(even) td { background: #f6f4ee; }
    table.data .num { text-align: right; white-space: nowrap; }
    table.data .nowrap { white-space: nowrap; }
    table.data th.num { white-space: normal; } /* long titles of number columns may wrap */
    /* Wide tables (11 columns or more): smaller text so everything fits the page */
    table.data.dense td { font-size: 7pt; padding: 3px 3px; }
    table.data.dense th { font-size: 6.8pt; padding: 4px 3px; }
    table.data.dense .num { padding-left: 9px; }
    tr { page-break-inside: avoid; }

    .section-meta { margin: -4px 0 6px; }
    .empty { padding: 10px; background: #f6f4ee; color: #5e6880; }

    /* Info boxes (order summary) */
    .boxes { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 14px; }
    .box { border: 0.8px solid #e5e1d8; border-radius: 6px; padding: 8px 10px; vertical-align: top; width: 33%; }
    .label { font-size: 6.8pt; text-transform: uppercase; letter-spacing: .5px; color: #5e6880; margin-bottom: 3px; }
    .totals { width: 260px; margin-left: auto; border-collapse: collapse; margin-top: 10px; }
    .totals td { padding: 4px 6px; }
    .totals .num { text-align: right; }
    .totals .grand td { border-top: 1.2px solid #14213d; padding-top: 7px; }
    .big { font-size: 15pt; }
    .discount { color: #1b7a4b; }
    .badge { display: inline-block; padding: 1px 7px; border-radius: 8px; background: #eaf0fd; color: #173ea8; font-weight: bold; font-size: 7.6pt; }
</style>
