# Analyse fonctionnelle et technique

**Projet** : Site de présentation et d'inscription — Formation CRA-W
**Dates de la formation** : du lundi 15 au vendredi 19 mars 2027
**Lieu** : Bâtiment Léon Lacroix, 9 rue de Liroux, 5030 Gembloux (paramétrable)
**Sujet** : *à définir* — le site doit fonctionner et être publiable avec un sujet non figé
**Version du document** : 1.0 — 19/09/2026

---

## 1. Contexte et objectifs

Le Centre wallon de Recherches agronomiques (CRA-W) organise une formation de 5 jours
dans un de ses bâtiments. Le site a quatre objectifs :

1. **Présenter** la formation (programme, lieu, intervenants, informations pratiques) dans le
   respect strict de la charte graphique de `cra.wallonie.be`.
2. **Collecter les inscriptions** sans création de compte, en recueillant les contraintes
   alimentaires (allergies, halal, végétarien, autre) nécessaires à la logistique des repas.
3. **Gérer le paiement par virement bancaire**, avec relance et confirmation par e-mail.
4. **Outiller l'administrateur** : liste des inscrits, validation des paiements, gestion des
   présences, statistiques alimentaires, génération et envoi des diplômes, publication d'actualités.

### 1.1 Ce qui est hors périmètre (V1)

| Exclusion | Justification |
|---|---|
| Paiement en ligne (Bancontact, carte, Stripe/Mollie) | Le besoin exprimé est le virement bancaire. L'architecture réserve toutefois un point d'extension (`payment_method`). |
| Comptes participants | Explicitement non souhaité. Le suivi se fait par lien signé envoyé par e-mail. |
| Multilingue FR/EN | Le CRA-W est bilingue, mais la demande porte sur une formation. Les libellés sont centralisés pour rendre l'ajout du NL/EN peu coûteux. |
| Facturation comptable / note de crédit | Un justificatif de paiement PDF est produit, pas une facture conforme au plan comptable. |

### 1.2 Contrainte structurante : le sujet n'est pas défini

Le sujet, le programme détaillé, le prix et les intervenants sont **des données, pas du code**.
Tout est stocké en base et éditable depuis le back-office. Le site est livré avec un contenu
de remplacement explicite (« Sujet en cours de définition ») et une bannière d'information, de
sorte qu'il puisse être mis en ligne immédiatement puis complété.

---

## 2. Acteurs

| Acteur | Authentification | Rôle |
|---|---|---|
| **Visiteur** | aucune | Consulte la présentation, le programme, les actualités. |
| **Participant** | aucune (lien signé par e-mail) | S'inscrit, consulte son récapitulatif, ses instructions de paiement, télécharge son diplôme. |
| **Administrateur** | e-mail + mot de passe | Gère tout le back-office. |
| **Système (cron)** | jeton interne | Envoie les relances de paiement, purge les brouillons expirés. |

Deux rôles administrateurs sont prévus :
`admin` (tout) et `gestionnaire` (tout sauf configuration, gestion des comptes et suppression).

---

## 3. Analyse fonctionnelle

### 3.1 Cartographie des écrans

**Site public**

| Route | Écran | Contenu |
|---|---|---|
| `/` | Accueil | Hero avec dates/lieu, pitch, compteur de places, programme résumé, dernières actualités, CTA inscription |
| `/programme` | Programme | Déroulé des 5 journées, demi-journées, intervenants |
| `/infos-pratiques` | Infos pratiques | Accès, parking, repas, hébergement, prix, conditions d'annulation |
| `/actualites` | Actualités | Liste paginée |
| `/actualites/{slug}` | Article | Article + JSON-LD `NewsArticle` |
| `/inscription` | Inscription | Formulaire (voir 3.2) |
| `/inscription/recapitulatif/{token}` | Récapitulatif | Relecture avant confirmation |
| `/inscription/paiement/{token}` | Paiement | IBAN, communication structurée, QR code SEPA, bouton PDF |
| `/mon-inscription/{token}` | Espace participant | Statut, paiement, diplôme |
| `/contact` | Contact | Formulaire + coordonnées |
| `/mentions-legales`, `/confidentialite`, `/accessibilite` | Pages légales | RGPD, cookies, accessibilité |
| `/sitemap.xml`, `/robots.txt`, `/actualites/rss` | SEO | Générés dynamiquement |

**Back-office** (`/admin`)

| Route | Écran |
|---|---|
| `/admin/connexion` | Connexion |
| `/admin` | Tableau de bord (KPI : inscrits, payés, en attente, taux de remplissage) |
| `/admin/inscriptions` | Liste filtrable + recherche + export |
| `/admin/inscriptions/{id}` | Fiche détaillée, historique, actions |
| `/admin/paiements` | File des paiements à confirmer |
| `/admin/presences` | Grille de pointage (voir 3.5) |
| `/admin/statistiques` | Statistiques alimentaires |
| `/admin/diplomes` | Génération, envoi, impression groupée |
| `/admin/diplomes/template` | Dépôt et prévisualisation du template |
| `/admin/actualites` | CRUD actualités |
| `/admin/configuration` | Paramètres (formation, prix, seuil de présence, SMTP, banque) |
| `/admin/utilisateurs` | Comptes administrateurs |

### 3.2 Parcours d'inscription

Formulaire **en 3 étapes** avec sauvegarde intermédiaire (statut `draft`), afin qu'un abandon
n'entraîne pas de perte et que les champs sensibles soient regroupés.

**Étape 1 — Identité et contact**

| Champ | Type | Obligatoire | Règle |
|---|---|---|---|
| Civilité | énum (Mme, M., Autre, Ne se prononce pas) | non | — |
| Nom, Prénom | texte 100 | oui | ≥ 2 caractères |
| E-mail | e-mail | oui | RFC + vérification DNS MX légère, unicité par session de formation |
| Téléphone | texte 30 | oui | format libre, normalisé E.164 si possible |
| Date de naissance | date | non | utilisée pour l'homonymie sur le diplôme |
| Organisation | texte 150 | non | — |
| Fonction | texte 150 | non | — |
| Adresse (rue, CP, ville, pays) | texte | oui si facture demandée | pays par défaut : Belgique |
| N° TVA | texte 20 | non | validé si renseigné (format BE) |

**Étape 2 — Préférences alimentaires**

C'est le point le plus délicat : ce sont des **données de santé potentielles** (allergies).
Traitement dédié (voir §7.3).

| Champ | Type | Règle |
|---|---|---|
| Régime | énum : `standard`, `vegetarien`, `vegan`, `sans_porc`, `halal`, `casher`, `autre` | un seul choix |
| Précision régime | texte libre 500 | obligatoire si `autre` |
| Allergies / intolérances | booléen + texte libre 500 | le commentaire est obligatoire si le booléen est coché |
| Remarques logistiques | texte libre 500 | facultatif |

> **Choix retenu** : demande explicite d'un commentaire libre pour halal/casher/autre **et**
> pour les allergies, conformément à la demande. Les champs libres sont affichés tels quels
> à l'administrateur, mais les statistiques s'appuient sur l'énum (donnée structurée), ce qui
> évite de « compter du texte libre ».

**Étape 3 — Récapitulatif et conditions**

- Relecture de toutes les données, modification possible.
- Case à cocher RGPD (traitement des données) + conditions d'annulation. Non pré-cochées.
- Validation → statut `pending_payment`, e-mail de confirmation, redirection vers la page de paiement.

**Protections** : jeton CSRF, champ honeypot, temporisation minimale de 3 s, limitation à
5 tentatives / IP / heure, verrouillage si la formation est complète (liste d'attente).

### 3.3 Paiement par virement

À la validation, le système attribue une **communication structurée belge** (`+++xxx/xxxx/xxxxx+++`)
calculée à partir de l'identifiant d'inscription — modulo 97, reste 0 ramené à 97, selon la norme
belge. Elle est unique, vérifiable et rapprochable.

La page de paiement présente :
- le bénéficiaire, l'IBAN et le BIC (paramétrables) ;
- le montant et la communication structurée, chacun avec un bouton « copier » ;
- un **QR code SEPA (EPC069-12)**, lisible par la majorité des applications bancaires belges et
  européennes, pré-remplissant bénéficiaire, IBAN, montant et communication ;
- un bouton « Télécharger les instructions (PDF) » ;
- la date limite de paiement (paramétrable, par défaut 15 jours, et jamais après le début de la formation).

> **Note technique importante** : le standard EPC ne permet pas d'encoder une communication
> structurée belge dans le champ « référence » de façon universelle. Le QR encode donc la
> communication en **texte non structuré** (champ `Unstructured Remittance`), ce qui est la
> pratique interopérable. La communication structurée reste affichée pour les saisies manuelles.

**Statuts de paiement** : `unpaid` → `reminded` → `paid` | `cancelled` | `refunded` | `waived` (exonéré).
Seul l'administrateur fait passer à `paid` (rapprochement bancaire manuel), avec date de valeur,
montant reçu et note. Un montant reçu différent du montant attendu déclenche un avertissement
visuel et le statut `partial`.

### 3.4 E-mails transactionnels

| # | Déclencheur | Objet | Pièce jointe |
|---|---|---|---|
| 1 | Validation de l'inscription | Confirmation d'inscription + instructions de paiement | PDF instructions |
| 2 | Cron, à J+7 puis J+3 avant échéance, si impayé | Rappel de paiement | PDF instructions |
| 3 | Cron, échéance dépassée | Dernier rappel / annulation imminente | — |
| 4 | Confirmation manuelle par l'admin | Paiement bien reçu | Justificatif PDF |
| 5 | Génération du diplôme | Votre diplôme | Diplôme PDF |
| 6 | Annulation | Annulation de l'inscription | — |
| 7 | Formulaire de contact | Copie à l'administrateur | — |

Tous les envois passent par une **file d'attente** (`email_queue`) traitée par le cron : l'envoi
n'est jamais bloquant pour l'utilisateur, les échecs sont rejoués (3 tentatives, back-off), et
chaque envoi est tracé dans `email_log` (destinataire, modèle, statut, erreur). Chaque e-mail est
envoyé en **multipart (HTML + texte brut)** et contient un lien de désinscription pour les envois
non transactionnels.

### 3.5 Gestion des présences — « à l'envers »

**Règle métier** : la majorité des participants est présente. Pointer les présents est donc
coûteux et source d'erreur ; on pointe les **absences**.

- La formation est découpée en **créneaux** (`training_days`) : 5 jours × 2 demi-journées = 10 créneaux
  par défaut, configurable (nombre de jours, demi-journées ou journées entières).
- Chaque participant payé est **présumé présent** sur tous les créneaux. Aucune ligne n'est écrite
  en base tant que rien n'est signalé.
- La table `absences` ne stocke **que les exceptions** : `(registration_id, day_id)`.
  Le stockage est minimal et le calcul trivial.
- **La présence est binaire** : un participant est présent ou absent. Aucun motif n'est
  collecté, aucune absence n'est « justifiée ». Décision assumée : arbitrer des justificatifs
  serait un processus supplémentaire pour l'organisateur, sans bénéfice pour le participant,
  et introduirait une appréciation discrétionnaire dans une règle qui doit rester mécanique.
- **Écran de pointage** : une grille participants × créneaux, cases décochées = absent. Un bouton
  « Tout présent » par créneau, sélection au clavier, enregistrement AJAX au fil de l'eau et
  bouton d'enregistrement global (fonctionne sans JavaScript).
- **Taux de présence** = `(créneaux_total − absences) / créneaux_total`.
- Export d'une **feuille d'émargement PDF** par jour, pour la signature papier.

### 3.6 Diplômes

**Éligibilité** : `taux_de_présence >= DIPLOMA_MIN_ATTENDANCE` (par défaut `0.80`, paramètre de
configuration) **et** paiement au statut `paid` ou `waived`. La règle est centralisée dans un
unique service du domaine, testé unitairement.

**Template fourni par l'administrateur** — deux modes complémentaires :

1. **Mode « fond + calque »** (recommandé, le plus simple pour l'administrateur) : dépôt d'une
   image A4 paysage (JPG/PNG, 300 dpi) ou d'un PDF converti en image. L'administrateur positionne
   ensuite les blocs de texte (nom, intitulé, dates, signature) via un éditeur de positions avec
   prévisualisation en direct. Aucune compétence technique requise.
2. **Mode « HTML »** (avancé) : édition d'un gabarit HTML/CSS avec variables de substitution
   `{{participant.nom_complet}}`, `{{formation.intitule}}`, `{{formation.dates}}`,
   `{{presence.taux}}`, `{{diplome.numero}}`, `{{diplome.date_emission}}`, `{{qr_verification}}`.

Le rendu passe dans les deux cas par **Dompdf** (HTML → PDF), ce qui garantit un pipeline unique.
Une prévisualisation avec un participant fictif est disponible avant toute génération de masse.

**Fonctions** :
- génération unitaire ou en lot (uniquement les éligibles, les autres sont listés avec le motif) ;
- numérotation unique `CRAW-2027-0001` et QR code de vérification pointant vers une page publique
  d'authentification du diplôme ;
- envoi par e-mail avec le PDF en pièce jointe (via la file d'attente) ;
- **impression groupée** : un unique PDF concaténant tous les diplômes, une page par participant,
  prêt pour l'impression recto en série ;
- archivage des PDF générés sous `storage/diplomas/` (hors racine web), accessibles uniquement
  via un contrôleur qui vérifie les droits.

### 3.7 Statistiques alimentaires

Écran dédié agrégeant :
- répartition par régime (table + graphique en barres, pas de dépendance JS lourde : SVG généré côté serveur) ;
- nombre de participants avec allergies déclarées, avec la liste des commentaires (pour le traiteur) ;
- croisement régime × statut de paiement (pour ne commander que les repas confirmés) ;
- **export « fiche traiteur »** : un PDF/CSV par jour, listant les effectifs par régime et le détail
  des allergies, sans données inutiles (minimisation RGPD).

### 3.8 Actualités

CRUD complet : titre, slug automatique (modifiable), chapô, corps en Markdown restreint, image
de couverture (redimensionnement automatique, WebP), date de publication, statut
(`draft`/`published`/`scheduled`), auteur, métadonnées SEO. Publication planifiée gérée par le cron.
Flux RSS et intégration au sitemap.

---

## 4. Règles de gestion (synthèse)

| # | Règle |
|---|---|
| RG-01 | Une adresse e-mail ne peut avoir qu'une inscription active par session de formation. |
| RG-02 | Une inscription non confirmée (`draft`) est purgée après 48 h. |
| RG-03 | Le nombre de places est limité (paramètre). Au-delà, les inscriptions basculent en liste d'attente. |
| RG-04 | La communication structurée est unique, immuable et dérivée de l'identifiant d'inscription. |
| RG-05 | Seul un administrateur peut passer un paiement à `paid`, action tracée (qui, quand, montant). |
| RG-06 | Un participant est présumé présent ; seules les absences sont saisies. La présence est binaire : ni motif, ni absence justifiée. |
| RG-07 | Le diplôme exige un taux de présence ≥ seuil configurable (80 % par défaut) **et** un paiement réglé. |
| RG-08 | Un diplôme généré est immuable : toute régénération crée une nouvelle version, l'ancienne est conservée. |
| RG-09 | Les données personnelles sont purgées 3 ans après la formation (paramétrable), hors obligations comptables. |
| RG-10 | Toute action administrateur sensible est journalisée (`audit_log`). |

---

## 5. Modèle de données

Moteur cible **MySQL/MariaDB** en production, **SQLite** pour le développement et la démonstration.
Le DDL est écrit dans un sous-ensemble portable ; les différences (auto-incrément, types) sont
gérées par la couche de migration.

```
settings(key PK, value, type, updated_at)

admin_users(id PK, email UNIQUE, password_hash, display_name, role,
            is_active, last_login_at, failed_attempts, locked_until, created_at)

training(id PK, slug, title, subject, summary, description,
         starts_on, ends_on, location_name, location_address,
         price_cents, currency, capacity, registration_deadline,
         status, created_at, updated_at)

training_days(id PK, training_id FK, day_date, slot ENUM(am,pm,full),
              label, position)            -- les créneaux de présence

registrations(id PK, training_id FK, reference UNIQUE, token UNIQUE,
              civility, last_name, first_name, email, phone, birth_date,
              organisation, job_title,
              street, postal_code, city, country, vat_number,
              diet ENUM(...), diet_comment,
              has_allergies BOOL, allergies_comment, logistics_comment,
              status ENUM(draft,pending_payment,confirmed,waitlist,cancelled),
              consent_gdpr_at, consent_terms_at,
              amount_due_cents, structured_communication,
              created_at, updated_at, ip_hash, user_agent)

payments(id PK, registration_id FK, status ENUM(unpaid,reminded,partial,paid,refunded,waived,cancelled),
         amount_cents, received_cents, method, value_date, note,
         confirmed_by FK admin_users, confirmed_at, reminders_sent, last_reminder_at)

absences(id PK, registration_id FK, training_day_id FK,
         recorded_by FK, recorded_at,
         UNIQUE(registration_id, training_day_id))

diplomas(id PK, registration_id FK, number UNIQUE, version,
         attendance_rate, file_path, verification_code UNIQUE,
         generated_by FK, generated_at, sent_at)

diploma_templates(id PK, name, mode ENUM(background,html),
                  background_path, html_body, field_layout JSON,
                  is_active, uploaded_by FK, created_at)

news(id PK, slug UNIQUE, title, excerpt, body, cover_path,
     status, published_at, author_id FK, meta_title, meta_description,
     created_at, updated_at)

email_queue(id PK, to_email, to_name, template, payload JSON,
            attachments JSON, status, attempts, last_error,
            scheduled_at, sent_at, created_at)

email_log(id PK, registration_id FK NULL, to_email, template,
          subject, status, error, sent_at)

audit_log(id PK, admin_user_id FK, action, entity, entity_id,
          details JSON, ip_hash, created_at)

contact_messages(id PK, name, email, subject, message, created_at, handled_at)
```

**Index** : `registrations(email, training_id)`, `registrations(status)`,
`registrations(structured_communication)`, `payments(status)`, `absences(training_day_id)`,
`news(status, published_at)`, `email_queue(status, scheduled_at)`.

---

## 6. Architecture technique

### 6.1 Choix du socle

| Décision | Retenu | Motivation |
|---|---|---|
| Langage | **PHP 8.4** | Demandé. Typage strict, enums, readonly. |
| Framework | **Aucun — micro-noyau maison** | Un site mono-événement de cette taille ne justifie pas Symfony/Laravel. Déploiement par simple copie de fichiers sur un hébergement mutualisé Apache/PHP, sans outillage serveur. Moins de dépendances = moins de surface d'attaque et de maintenance sur un site à cycle de vie court. |
| Base de données | **PDO** — MySQL/MariaDB (prod), SQLite (dev/démo) | Zéro configuration en développement, cible standard en production. |
| Templates | **PHP natif** avec échappement par défaut | Pas de compilation, pas de cache à invalider. |
| CSS | **Écrit à la main, tokens CSS** | Reproduit la charte CRA-W (Bootstrap 5.3 personnalisé) sans embarquer tout Bootstrap : ~15 ko au lieu de ~230 ko. Gain SEO/performance direct. |
| JavaScript | **Vanilla, progressif** | Tout fonctionne sans JS. Le JS améliore (copie presse-papier, grille de présences, thème). |

### 6.2 Dépendances Composer

| Paquet | Usage |
|---|---|
| `dompdf/dompdf` | Génération des PDF (diplômes, instructions, émargement) |
| `endroid/qr-code` | QR codes SEPA et vérification de diplôme |
| `phpmailer/phpmailer` | Envoi SMTP fiable (multipart, pièces jointes, TLS) |

Trois dépendances, toutes matures et largement auditées.

### 6.3 Arborescence

```
public/              ← seule racine web exposée
  index.php          ← front controller unique
  assets/{css,js,img}
  .htaccess          ← réécriture + en-têtes de sécurité
src/
  Core/              ← Router, Request, Response, View, Db, Config,
                       Csrf, Session, Validator, RateLimiter, Mailer, Logger, Auth
  Domain/            ← règles métier pures et testables :
                       StructuredCommunication, AttendanceCalculator,
                       DiplomaEligibility, SepaQrPayload, Slugger
  Repository/        ← accès aux données (une classe par agrégat)
  Controller/
    Public/          ← Home, Programme, Registration, Payment, News, Contact, Seo
    Admin/           ← Dashboard, Registrations, Payments, Attendance,
                       Statistics, Diplomas, News, Settings, Users, Auth
  Service/           ← RegistrationService, PaymentService, DiplomaService,
                       MailQueue, ExportService, ImageProcessor
templates/
  layouts/ partials/ public/ admin/ emails/ pdf/
database/
  migrations/ seeds/
storage/             ← hors web : db SQLite, logs, uploads, diplômes, cache
bin/
  console.php        ← migrate, seed, create-admin, cron
tests/               ← tests du domaine + smoke tests HTTP
docs/
```

### 6.4 Front controller et routage

Un routeur par expressions régulières, table de routes explicite, avec middlewares
(`auth`, `csrf`, `throttle`, `security-headers`). URLs propres et stables, pensées pour le SEO
(pas de paramètres de requête dans les URLs indexables).

### 6.5 Configuration

Fichier `.env` (hors dépôt) lu au démarrage, avec `.env.example` documenté. Les paramètres
métier (prix, capacité, seuil de présence, IBAN, textes) sont en base et éditables par
l'administrateur ; les paramètres d'infrastructure (BDD, SMTP, clés) sont dans `.env`.

`DIPLOMA_MIN_ATTENDANCE=0.80` est exposé **à la fois** en variable de configuration et dans
l'écran d'administration, conformément à la demande.

---

## 7. Sécurité, RGPD, accessibilité

### 7.1 Sécurité applicative

| Risque | Contre-mesure |
|---|---|
| Injection SQL | PDO en requêtes préparées exclusivement, `ATTR_EMULATE_PREPARES=false` |
| XSS | Échappement systématique à la sortie (`e()`), Markdown assaini en liste blanche, CSP stricte |
| CSRF | Jeton par session, vérifié sur toute requête non idempotente |
| Détournement de session | Cookies `HttpOnly`, `Secure`, `SameSite=Lax`, régénération d'identifiant à la connexion |
| Force brute | Limitation par IP et par compte, verrouillage temporaire progressif |
| Énumération d'inscriptions | Jetons de 32 octets aléatoires, comparaison à temps constant, pas d'identifiants séquentiels dans les URLs publiques |
| Upload malveillant | Liste blanche d'extensions et de types MIME réels, ré-encodage des images, stockage hors racine web, noms générés |
| En-têtes | `Content-Security-Policy`, `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, HSTS |
| Mots de passe | `password_hash()` (bcrypt/argon2id), politique de longueur minimale |

### 7.2 Traçabilité

Journal d'audit de toute action administrateur sensible (confirmation de paiement, suppression,
génération de diplôme, modification de configuration), avec IP hachée.

### 7.3 RGPD

- **Base légale** : exécution d'une mesure précontractuelle/contractuelle (inscription).
  Les allergies relèvent de l'article 9 (données de santé) : recueillies sur **consentement
  explicite et distinct**, avec mention de la finalité (adaptation des repas) et de la durée.
- **Minimisation** : les champs de santé ne sont visibles que dans l'écran de gestion des repas
  et dans la fiche du participant ; ils sont exclus des exports généraux et de la fiche traiteur
  nominative sauf nécessité.
- **Durée de conservation** : 3 ans après la formation (paramétrable), puis anonymisation
  automatique par le cron. Les diplômes et les preuves de paiement suivent les durées légales.
- **Droits** : page de confidentialité détaillant les droits d'accès, rectification, effacement,
  avec point de contact DPO. Le participant peut demander la suppression depuis son espace.
- **Sous-traitance** : aucune donnée n'est envoyée à un service tiers (pas de Google Fonts
  distantes, pas de CDN externe, pas d'outil d'analyse tiers par défaut).
- **Cookies** : uniquement un cookie de session technique et un cookie de préférence de thème.
  Aucun cookie de mesure d'audience par défaut → pas de bandeau de consentement requis.

### 7.4 Accessibilité (WCAG 2.1 AA)

Le CRA-W publie une déclaration d'accessibilité : l'obligation s'applique (directive UE 2016/2102).
- HTML sémantique, un seul `<h1>` par page, hiérarchie de titres continue ;
- lien d'évitement, navigation clavier complète, focus toujours visible ;
- libellés `<label>` associés, erreurs de formulaire annoncées (`aria-live`, `aria-describedby`) ;
- contrastes ≥ 4,5:1 vérifiés en thème clair **et** sombre ;
- pas d'information portée par la seule couleur (les statuts ont une icône et un texte) ;
- images décoratives `alt=""`, images informatives décrites ;
- `prefers-reduced-motion` respecté ;
- déclaration d'accessibilité publiée.

---

## 8. Charte graphique CRA-W — relevé technique

Relevé effectué sur `https://www.cra.wallonie.be/fr` et sa feuille de styles
`assets/cache/internet_styles.css`.

| Élément | Valeur relevée |
|---|---|
| Socle | Bootstrap 5.3, thèmes `light` / `dark` / `auto` via `data-bs-theme` |
| Couleur d'accent | **`#198754`** (vert `success`) — boutons, badges, pastilles de date. Variante sombre `#75b798` |
| Fond / texte clair | `#ffffff` / `#212529` |
| Fond / texte sombre | `#212529` / `#adb5bd` |
| Gris clair | `#f5f5f5` — sections alternées |
| Bordures | `#dee2e6` (clair) / `#495057` (sombre) |
| Liens | `#009ef7`, survol `#0a58ca` |
| Typographie | pile système : `system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif` — **aucune police distante** |
| Rayons | `0.375rem` standard, `50rem` pour les boutons pilule |
| Boutons CTA | `btn-success rounded-pill py-3 px-4` — pilule verte pleine |
| Logo | monochrome, deux variantes (sombre sur fond clair, blanche sur fond sombre) |
| En-tête | `sticky-top`, deux niveaux : barre utilitaire (Wallonie / Agriculture / Environnement, puis Annuaire / Emplois / Presse) + barre principale (logo, navigation, FR/EN, sélecteur de thème, recherche) |
| Pied de page | appel newsletter en pilule verte + réseaux sociaux, puis bande `bg-dark text-white small` avec logo blanc, 4 colonnes de liens et ligne légale (Confidentialité, Cookies, Accessibilité, Signalement) |
| Cartes | image en tête, pastille de date circulaire verte, badge pilule flottant, méta avec séparateurs |
| Séparateurs | vagues SVG entre les sections |

**Reproduction** : la charte est reproduite par des tokens CSS écrits à la main, sans copier les
fichiers du CRA-W. Le logo officiel n'est pas repris : un bloc-marque textuel est utilisé, à
remplacer par le fichier officiel fourni par le service communication (emplacement documenté).

---

## 9. SEO

| Levier | Mise en œuvre |
|---|---|
| Indexabilité | URLs propres, `canonical` sur chaque page, `robots.txt`, `sitemap.xml` généré dynamiquement |
| Métadonnées | `title` unique ≤ 60 car., `meta description` ≤ 155 car., éditables par page depuis l'administration |
| Données structurées | JSON-LD `EducationEvent` (dates, lieu, offre, statut, organisateur) sur l'accueil, `Organization`, `BreadcrumbList`, `NewsArticle` sur les actualités, `FAQPage` sur les infos pratiques |
| Partage social | OpenGraph + Twitter Cards, image de partage générée |
| Performance (Core Web Vitals) | CSS critique en ligne, pas de police distante, images en WebP avec `width`/`height` et `loading="lazy"`, JS différé ~4 ko, compression et cache long sur les assets versionnés |
| Sémantique | Un `<h1>` par page, hiérarchie stricte, HTML5 structurant |
| Contenu | Pages dédiées programme / infos pratiques / actualités plutôt qu'une page unique, pour couvrir plus de requêtes |
| Technique | HTTPS forcé, 404 propre, pas de contenu dupliqué, `hreflang` prêt pour le NL/EN |
| Local | JSON-LD `Place` avec l'adresse du bâtiment et les coordonnées géographiques |

---

## 10. Plan de livraison

| Lot | Contenu | État |
|---|---|---|
| L0 | Analyse fonctionnelle et technique | ce document |
| L1 | Squelette, noyau, base de données, migrations | à faire |
| L2 | Thème CRA-W, layouts, composants | à faire |
| L3 | Site public + SEO | à faire |
| L4 | Inscription + paiement (QR SEPA, communication structurée) | à faire |
| L5 | E-mails transactionnels + cron | à faire |
| L6 | Back-office (inscrits, paiements, présences, statistiques, actualités, configuration) | à faire |
| L7 | Diplômes PDF (template, génération, envoi, impression groupée) | à faire |
| L8 | Exports, tests, documentation d'installation | à faire |

## 11. Points ouverts à arbitrer

> **Arbitrages rendus le 19/09/2026.** La présence est binaire : absent ou présent, sans
> motif ni absence justifiée, et sans processus supplémentaire (point 6, désormais clos et
> retiré du code). Tarif, bâtiment et capacité restent aux valeurs d'hypothèse, toutes
> modifiables depuis Administration → Configuration.


| # | Question | Hypothèse retenue à défaut de réponse |
|---|---|---|
| 1 | Sujet, programme détaillé, intervenants | Contenu de remplacement + bannière « en cours de définition », éditable en back-office |
| 2 | Prix de la formation | 450 € TTC, paramétrable ; gratuité possible (`price = 0` masque tout le circuit de paiement) |
| 3 | Bâtiment exact | Léon Lacroix, 9 rue de Liroux, 5030 Gembloux, paramétrable |
| 4 | Coordonnées bancaires réelles | IBAN de démonstration en configuration, à remplacer avant mise en production |
| 5 | Capacité d'accueil | 40 places, paramétrable, liste d'attente au-delà |
| 6 | Logo officiel et visuels | Bloc-marque textuel provisoire, emplacement de remplacement documenté |
| 7 | Serveur SMTP | `mail()` en repli, SMTP recommandé, configuré dans `.env` |
