/* Styles communs aux documents PDF.
   Dompdf ne gere qu'un sous-ensemble de CSS : on reste sur des selecteurs
   simples et on evite flexbox et grid. */

@page { margin: 18mm 16mm; }

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 10.5pt;
    line-height: 1.5;
    color: #212529;
    margin: 0;
}

h1 { font-size: 18pt; margin: 0 0 6pt; color: #212529; }
h2 { font-size: 13pt; margin: 18pt 0 6pt; color: #0f5132; }
h3 { font-size: 11pt; margin: 12pt 0 4pt; }
p  { margin: 0 0 8pt; }

.brand-bar {
    background-color: #198754;
    color: #ffffff;
    padding: 10pt 12pt;
    margin-bottom: 14pt;
}

.brand-bar .brand-name { font-size: 13pt; font-weight: bold; }
.brand-bar .brand-sub  { font-size: 8.5pt; color: #d1e7dd; }

.muted { color: #5c636a; }
.small { font-size: 9pt; }
.right { text-align: right; }
.center { text-align: center; }
.bold { font-weight: bold; }

table.data {
    width: 100%;
    border-collapse: collapse;
    margin: 10pt 0;
}

table.data th,
table.data td {
    border: 1px solid #dee2e6;
    padding: 6pt 8pt;
    text-align: left;
    vertical-align: top;
}

table.data th {
    background-color: #f5f5f5;
    font-size: 9pt;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
    color: #5c636a;
}

table.kv { width: 100%; border-collapse: collapse; margin: 10pt 0; }

table.kv td {
    border: 1px solid #dee2e6;
    padding: 7pt 9pt;
}

table.kv td.label {
    width: 42%;
    background-color: #f8f9fa;
    font-size: 9pt;
    color: #5c636a;
}

table.kv td.value {
    font-weight: bold;
    font-size: 11pt;
}

tr.highlight td.value {
    background-color: #d1e7dd;
    border-color: #198754;
}

.note {
    padding: 8pt 10pt;
    background-color: #cfe2ff;
    border: 1px solid #9ec5fe;
    color: #084298;
    font-size: 9.5pt;
    margin: 10pt 0;
}

.note.success { background-color: #d1e7dd; border-color: #a3cfbb; color: #0f5132; }
.note.warning { background-color: #fff3cd; border-color: #ffe69c; color: #664d03; }

.footer-note {
    margin-top: 16pt;
    padding-top: 8pt;
    border-top: 1px solid #dee2e6;
    font-size: 8.5pt;
    color: #5c636a;
}

.signature-cell { height: 34pt; }
