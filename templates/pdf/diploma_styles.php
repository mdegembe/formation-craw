/* Styles des diplomes.
   La page est en A4 paysage sans marge : le gabarit fourni par l'administrateur
   occupe toute la surface, et les blocs de texte sont positionnes en absolu. */

@page { margin: 0; }

body {
    margin: 0;
    padding: 0;
    font-family: "DejaVu Sans", sans-serif;
    color: #212529;
}

.pdf-page {
    position: relative;
    width: 297mm;
    height: 210mm;
    overflow: hidden;
}

/* --- Mode « fond + calque » --------------------------------------------- */

.diploma-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 297mm;
    height: 210mm;
}

.diploma-field {
    position: absolute;
    line-height: 1.3;
}

.diploma-qr {
    width: 26mm;
    height: 26mm;
}

/* --- Modele par defaut --------------------------------------------------- */

.diploma-default {
    position: absolute;
    top: 0;
    left: 0;
    width: 297mm;
    height: 210mm;
    background-color: #ffffff;
}

.diploma-border {
    position: absolute;
    top: 10mm;
    left: 10mm;
    width: 273mm;
    height: 186mm;
    border: 2pt solid #198754;
}

.diploma-border-inner {
    position: absolute;
    top: 13mm;
    left: 13mm;
    width: 267mm;
    height: 180mm;
    border: 0.75pt solid #a3cfbb;
}

.diploma-accent {
    position: absolute;
    top: 10mm;
    left: 10mm;
    width: 273mm;
    height: 7mm;
    background-color: #198754;
}

.diploma-inner {
    position: absolute;
    top: 26mm;
    left: 26mm;
    width: 245mm;
    text-align: center;
}

.diploma-issuer {
    font-size: 11pt;
    letter-spacing: 1.6pt;
    text-transform: uppercase;
    color: #0f5132;
    font-weight: bold;
}

.diploma-issuer-sub {
    font-size: 9pt;
    color: #5c636a;
    margin-top: 2mm;
}

.diploma-title {
    font-size: 30pt;
    font-weight: bold;
    margin-top: 10mm;
    color: #212529;
    letter-spacing: 1pt;
}

.diploma-rule {
    width: 60mm;
    height: 2pt;
    background-color: #198754;
    margin: 6mm auto;
}

.diploma-intro {
    font-size: 11.5pt;
    color: #5c636a;
}

.diploma-name {
    font-size: 26pt;
    font-weight: bold;
    margin: 5mm 0;
    color: #0f5132;
}

.diploma-body {
    font-size: 11.5pt;
    line-height: 1.7;
}

.diploma-subject {
    font-size: 14pt;
    font-weight: bold;
    margin: 3mm 0;
}

.diploma-meta {
    position: absolute;
    bottom: 22mm;
    left: 26mm;
    width: 245mm;
    font-size: 9pt;
    color: #5c636a;
}

.diploma-meta td {
    font-size: 9pt;
    color: #5c636a;
    vertical-align: bottom;
}

.diploma-signature {
    border-top: 0.75pt solid #adb5bd;
    padding-top: 2mm;
    width: 55mm;
}
