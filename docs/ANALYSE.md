# Analyse fonctionnelle et technique

**Projet** : Site de présentation et d'inscription — Formation CRA-W
**Dates de la formation** : du lundi 15 au vendredi 19 mars 2027
**Lieu** : Bâtiment Léon Lacroix, 9 rue de Liroux, 5030 Gembloux (paramétrable)
**Sujet** : *à définir* — le site doit fonctionner et être publiable avec un sujet non figé
**Version du document** : 2.0 — 21/09/2026
**État** : application réalisée (lots L0 à L8), site public et courriels bilingues FR/EN, lancement possible en conteneur Docker

| Version | Date | Objet |
|---|---|---|
| 1.0 | 19/09/2026 | Analyse initiale, rédigée avant la réalisation |
| 1.1 | 19/09/2026 | Présence binaire : suppression de la notion d'absence justifiée |
| 2.0 | 21/09/2026 | Alignement sur le code livré : bilinguisme FR/EN (§3.9, §6.6), exécution Docker (§6.7), modèle de données réel (§5), écarts de réalisation (§12) |

> Depuis la version 2.0, ce document décrit l'application **telle qu'elle est réalisée**.
> Les différences avec ce qu'annonçait la v1.0 sont recensées au §12, avec pour chacune une
> appréciation : écart assumé, à arbitrer ou à corriger.

---

## 1. Contexte et objectifs

Le Centre wallon de Recherches agronomiques (CRA-W) organise une formation de 5 jours
dans un de ses bâtiments. Le site a cinq objectifs :

1. **Présenter** la formation (programme, lieu, intervenants, informations pratiques) dans le
   respect strict de la charte graphique de `cra.wallonie.be`.
2. **Collecter les inscriptions** sans création de compte, en recueillant les contraintes
   alimentaires (allergies, halal, végétarien, autre) nécessaires à la logistique des repas.
3. **Gérer le paiement par virement bancaire**, avec relance et confirmation par e-mail.
4. **Outiller l'administrateur** : liste des inscrits, validation des paiements, gestion des
   présences, statistiques alimentaires, génération et envoi des diplômes, publication d'actualités.
5. **Accueillir un public non francophone** : site public, parcours d'inscription, instructions
   de paiement et courriels aux participants disponibles en français et en anglais.

### 1.1 Ce qui est hors périmètre

| Exclusion | Justification |
|---|---|
| Paiement en ligne (Bancontact, carte, Stripe/Mollie) | Le besoin exprimé est le virement bancaire. Le modèle réserve un point d'extension (`payments.method`). |
| Comptes participants | Explicitement non souhaité. Le suivi se fait par un lien personnel envoyé par e-mail. |
| Néerlandais ; back-office bilingue | Le bilinguisme FR/EN a été intégré au périmètre le 20/09/2026 (§3.9). Ajouter le NL est mécanique (fichier `lang/nl.php`, gabarits `.nl.php`, colonnes `_nl`) mais représente un coût de traduction. Le back-office reste en français. |
| Facturation comptable / note de crédit | Les instructions de paiement sont fournies en PDF ; aucune facture conforme au plan comptable n'est produite. |

### 1.2 Contrainte structurante : le sujet n'est pas défini

Le sujet, le programme détaillé, le prix et les intervenants sont **des données, pas du code**.
Tout est stocké en base et éditable depuis le back-office. Le site est livré avec un contenu
de remplacement explicite (« Sujet en cours de définition », drapeau `training.subject_pending`)
et une bannière d'information, de sorte qu'il puisse être mis en ligne immédiatement puis complété.

Le contenu anglais suit la même logique : chaque champ traduisible a une colonne `_en`
facultative ; tant qu'elle est vide, la page anglaise affiche le texte français.

---

## 2. Acteurs

| Acteur | Authentification | Rôle |
|---|---|---|
| **Visiteur** | aucune | Consulte la présentation, le programme, les actualités, dans la langue de son choix. |
| **Participant** | aucune (lien personnel à jeton aléatoire, envoyé par e-mail) | S'inscrit, consulte son récapitulatif, ses instructions de paiement, télécharge son diplôme. |
| **Administrateur** | e-mail + mot de passe | Gère le back-office. |
| **Système (cron)** | jeton secret (`CRON_TOKEN`) | Envoie les relances de paiement, vide la file des courriels, purge les brouillons expirés. |

Deux rôles administrateurs : `admin` (tout) et `gestionnaire`. Le gestionnaire ne peut pas
modifier la configuration, gérer les comptes, supprimer une inscription, une actualité ou un
modèle de diplôme, ni annuler la confirmation d'un paiement. Il peut en revanche modifier le
programme jour par jour (voir point ouvert n° 10).

---

## 3. Analyse fonctionnelle

### 3.1 Cartographie des écrans

**Site public** — chaque route publique est préfixée par la langue (`/fr` ou `/en`, noté
`/{langue}` ci-dessous). Les segments d'URL restent en français dans les deux langues
(`/en/infos-pratiques`).

| Route | Écran | Contenu |
|---|---|---|
| `/` | — | Redirection vers `/fr` ou `/en` selon l'en-tête `Accept-Language` du navigateur |
| `/{langue}` | Accueil | Hero avec dates/lieu, pitch, compteur de places, programme résumé, dernières actualités, CTA inscription |
| `/{langue}/programme` | Programme | Déroulé des 5 journées, demi-journées, thèmes, intervenants, horaires |
| `/{langue}/infos-pratiques` | Infos pratiques | Accès, parking, repas, hébergement, prix, conditions d'annulation (+ FAQ) |
| `/{langue}/actualites` | Actualités | Liste paginée |
| `/{langue}/actualites/{slug}` | Article | Article + JSON-LD `NewsArticle` |
| `/{langue}/actualites/rss` | Flux RSS | Un flux par langue |
| `/{langue}/inscription` | Inscription, étape 1 | Identité et contact (voir 3.2) |
| `/{langue}/inscription/preferences/{token}` | Inscription, étape 2 | Préférences alimentaires |
| `/{langue}/inscription/recapitulatif/{token}` | Inscription, étape 3 | Relecture et consentements, puis confirmation (`POST …/confirmer/{token}`) |
| `/{langue}/inscription/paiement/{token}` | Paiement | IBAN, communication structurée, QR code SEPA (`…/qr.png`), instructions PDF (`…/instructions.pdf`) |
| `/{langue}/mon-inscription/{token}` | Espace participant | Statut, paiement, téléchargement du diplôme (`…/diplome`) |
| `/{langue}/diplome/verification/{code}` | Vérification | Authenticité d'un diplôme (cible du QR code imprimé) |
| `/{langue}/contact` | Contact | Formulaire + coordonnées |
| `/{langue}/mentions-legales`, `/confidentialite`, `/accessibilite` | Pages légales | RGPD, cookies, accessibilité |
| `/sitemap.xml`, `/robots.txt` | SEO | Générés dynamiquement, sans préfixe de langue |
| `/media/news/{fichier}` | — | Images de couverture des actualités, servies depuis `storage/` |
| `/cron/{jeton}` | — | Déclenchement HTTP des tâches planifiées (hébergements sans cron système) |

**Back-office** (`/admin`, en français, sans préfixe de langue)

| Route | Écran |
|---|---|
| `/admin/connexion` | Connexion |
| `/admin` | Tableau de bord (KPI : inscrits, payés, en attente, taux de remplissage, repas) |
| `/admin/inscriptions` | Liste filtrable + recherche + export CSV (`…/export`) |
| `/admin/inscriptions/{id}` | Fiche détaillée, historique des courriels, actions (annuler, renvoyer la confirmation, supprimer) |
| `/admin/paiements` | File des paiements : confirmer, exonérer, annuler une confirmation, relancer |
| `/admin/presences` | Grille de pointage (voir 3.5), export CSV, feuille d'émargement PDF |
| `/admin/statistiques` | Statistiques alimentaires, export CSV, fiche traiteur PDF |
| `/admin/diplomes` | Génération, envoi unitaire ou groupé, impression groupée (`…/impression.pdf`) |
| `/admin/diplomes/modele` | Dépôt, activation et aperçu PDF des modèles de diplôme |
| `/admin/actualites` | CRUD actualités (français + anglais) |
| `/admin/configuration` | Paramètres (formation FR/EN, prix, capacité, seuil de présence, banque, relances, RGPD) et programme jour par jour |
| `/admin/utilisateurs` | Comptes administrateurs (rôle `admin` uniquement) |

### 3.2 Parcours d'inscription

Formulaire **en 3 étapes**. L'étape 1 crée l'inscription au statut `draft` et lui attribue un
jeton : un abandon n'entraîne pas de perte, et les données de santé sont isolées à l'étape 2.
La langue de la page au moment de l'inscription est enregistrée (`registrations.locale`) et
détermine la langue de tous les courriels ultérieurs (RG-11).

**Étape 1 — Identité et contact**

| Champ | Type | Obligatoire | Règle |
|---|---|---|---|
| Civilité | énum (Mme, M., Autre, Ne se prononce pas) | non | — |
| Nom, Prénom | texte 100 | oui | — |
| E-mail | e-mail | oui | format vérifié, ≤ 190 caractères, une seule inscription active par formation (RG-01) |
| Téléphone | texte 30 | oui | format libre, 8 à 15 chiffres |
| Date de naissance | date | non | utilisée pour l'homonymie sur le diplôme |
| Organisation | texte 150 | non | — |
| Fonction | texte 150 | non | — |
| Besoin d'une facture | case à cocher | non | rend l'adresse obligatoire |
| Adresse (rue, CP, ville, pays) | texte | oui si facture demandée | pays par défaut : Belgique |
| N° TVA | texte 20 | non | format européen vérifié si renseigné (code pays + 8 à 12 caractères) |

**Étape 2 — Préférences alimentaires**

C'est le point le plus délicat : ce sont des **données de santé potentielles** (allergies).
Traitement dédié (voir §7.3).

| Champ | Type | Règle |
|---|---|---|
| Régime | énum : `standard`, `vegetarien`, `vegan`, `sans_porc`, `halal`, `casher`, `autre` | un seul choix |
| Précision régime | texte libre 500 | obligatoire si `autre` ; proposé (facultatif) pour `halal` et `casher` |
| Allergies / intolérances | booléen + texte libre 500 | le commentaire est obligatoire si le booléen est coché |
| Consentement santé | case à cocher | obligatoire si des allergies sont déclarées ; horodaté (`consent_health_at`) |
| Remarques logistiques | texte libre 500 | facultatif |

> **Choix retenu** : les champs libres sont affichés tels quels à l'administrateur, mais les
> statistiques s'appuient sur l'énum (donnée structurée), ce qui évite de « compter du texte
> libre ». Les régimes et leurs aides à la saisie sont traduits (§3.9).

**Étape 3 — Récapitulatif et conditions**

- Relecture de toutes les données, modification possible.
- Case à cocher RGPD (traitement des données) + conditions d'annulation. Non pré-cochées.
- Validation → statut `pending_payment` (ou `confirmed` si la formation est gratuite, ou
  `waitlist` si elle est complète), e-mail de confirmation, redirection vers la page de paiement.

**Protections** : jeton CSRF vérifié sur toute requête non idempotente, champ piège (honeypot)
masqué, limitation à 10 soumissions par IP et par formulaire sur 15 minutes, bascule en liste
d'attente quand la formation est complète.

### 3.3 Paiement par virement

À la validation, le système attribue une **communication structurée belge** (`+++xxx/xxxx/xxxxx+++`)
calculée à partir de l'identifiant d'inscription — modulo 97, reste 0 ramené à 97, selon la norme
belge. Elle est unique, vérifiable et rapprochable.

La page de paiement (en français ou en anglais) présente :
- le bénéficiaire, l'IBAN et le BIC (paramétrables) ;
- le montant (formaté selon la langue) et la communication structurée, chacun avec un bouton « copier » ;
- un **QR code SEPA (EPC069-12)**, lisible par la majorité des applications bancaires belges et
  européennes, pré-remplissant bénéficiaire, IBAN, montant et communication ;
- un bouton « Télécharger les instructions (PDF) », le PDF existant dans les deux langues ;
- la date limite de paiement : par défaut 15 jours après l'inscription (paramétrable), ramenée à la
  veille du début de la formation si elle tombait après.

> **Note technique importante** : le standard EPC ne permet pas d'encoder une communication
> structurée belge dans le champ « référence » de façon universelle. Le QR encode donc la
> communication en **texte non structuré** (champ `Unstructured Remittance`), ce qui est la
> pratique interopérable. La communication structurée reste affichée pour les saisies manuelles.

**Statuts de paiement** : `unpaid` → `reminded` → `paid` | `partial` | `waived` (exonéré) | `cancelled`.
Le statut `refunded` existe dans le modèle mais aucun écran ne l'attribue encore (voir §12).

Seul un administrateur fait passer à `paid` (rapprochement bancaire manuel), avec date de valeur,
montant reçu et note. Un montant reçu inférieur au montant attendu donne le statut `partial` et un
avertissement visuel. Un administrateur peut aussi exonérer un participant, annuler une
confirmation erronée (rôle `admin` uniquement) ou déclencher une relance manuelle.

**Formation gratuite** : un prix à 0 court-circuite le circuit de paiement. L'inscription passe
directement à `confirmed` et la ligne de paiement est créée au statut `waived` (méthode `exempt`),
pour garder une trace homogène dans les statistiques.

### 3.4 E-mails transactionnels

| # | Déclencheur | Objet | Pièce jointe |
|---|---|---|---|
| 1 | Validation de l'inscription | Confirmation d'inscription (variantes : liste d'attente, formation gratuite) | — (liens vers la page de paiement et le PDF d'instructions) |
| 2 | Cron, J−7 puis J−3 avant échéance (paramétrable), si impayé | Rappel de paiement | — |
| 3 | Cron, échéance dépassée | Dernier rappel | — |
| 4 | Confirmation manuelle par l'admin | Paiement reçu, inscription confirmée | — |
| 5 | Envoi du diplôme par l'admin | Votre diplôme de participation | Diplôme PDF |
| 6 | Annulation par l'admin | Annulation de l'inscription | — |
| 7 | Place libérée, promotion depuis la liste d'attente | Confirmation d'inscription (réutilise le n° 1) | — |
| 8 | Formulaire de contact | Message transmis à l'adresse de contact du CRA-W | — |

Tous les envois passent par une **file d'attente** (`email_queue`) traitée par le cron : l'envoi
n'est jamais bloquant pour l'utilisateur. Un verrou optimiste empêche un double envoi si deux
exécutions du cron se chevauchent ; un échec est rejoué jusqu'à 3 tentatives, avec un délai
croissant (5 minutes × numéro de tentative). Chaque envoi abouti ou définitivement échoué est
tracé dans `email_log` (destinataire, modèle, objet, statut, erreur), consultable depuis la fiche
de l'inscription. Chaque e-mail est envoyé en **multipart (HTML + texte brut)**.

Les courriels aux participants existent en français et en anglais (gabarits HTML et texte). La
langue est figée au moment de la mise en file, à partir de celle de l'inscription : un rappel
envoyé par le cron trois semaines plus tard parle la même langue que la page d'inscription, et
tous ses liens pointent vers les pages de cette langue. **Limite actuelle** : les objets des
courriels sont encore rédigés en français pour tous les destinataires (voir §12).

Tous les courriels étant transactionnels, aucun lien de désinscription n'est nécessaire.

### 3.5 Gestion des présences — « à l'envers »

**Règle métier** : la majorité des participants est présente. Pointer les présents est donc
coûteux et source d'erreur ; on pointe les **absences**.

- La formation est découpée en **créneaux** (`training_days`) : 5 jours × 2 demi-journées = 10 créneaux
  par défaut. Chaque créneau peut être exclu du calcul (`counts_for_attendance`), par exemple
  une séance facultative.
- Chaque participant confirmé est **présumé présent** sur tous les créneaux. Aucune ligne n'est écrite
  en base tant que rien n'est signalé.
- La table `absences` ne stocke **que les exceptions** : `(registration_id, training_day_id)`.
  Le stockage est minimal et le calcul trivial.
- **La présence est binaire** : un participant est présent ou absent. Aucun motif n'est
  collecté, aucune absence n'est « justifiée ». Décision assumée : arbitrer des justificatifs
  serait un processus supplémentaire pour l'organisateur, sans bénéfice pour le participant,
  et introduirait une appréciation discrétionnaire dans une règle qui doit rester mécanique.
  Les colonnes correspondantes ont été retirées du schéma (migration 002).
- **Écran de pointage** : une grille participants × créneaux, case cochée = présent, case
  décochée = absent. Un bouton par créneau coche ou décoche toute la colonne (JavaScript).
  L'enregistrement se fait par soumission du formulaire, pour toute la grille ou un seul jour :
  l'écran fonctionne entièrement sans JavaScript.
- **Taux de présence** = `(créneaux comptabilisés − absences) / créneaux comptabilisés`.
- Export CSV de la grille et **feuille d'émargement PDF**, pour un jour ou pour toute la semaine.

### 3.6 Diplômes

**Éligibilité** : `taux_de_présence >= seuil` (par défaut `0.80`, paramètre de configuration)
**et** paiement au statut `paid` ou `waived`. La règle est centralisée dans un unique service du
domaine (`DiplomaEligibility`), testé unitairement.

**Modèle fourni par l'administrateur** — deux modes complémentaires :

1. **Mode « fond + calque »** (recommandé, le plus simple pour l'administrateur) : dépôt d'une
   image A4 (JPEG ou PNG, idéalement 3508 × 2480 px pour 300 dpi), en paysage ou en portrait.
   Pour chaque information à imprimer, l'administrateur coche le champ puis saisit sa position
   (haut, gauche) et sa largeur en millimètres, la taille de police, l'alignement, la couleur,
   la graisse et un éventuel préfixe. Un aperçu PDF permet d'ajuster les valeurs. Aucune
   compétence technique requise.
2. **Mode « HTML »** (avancé) : édition d'un gabarit HTML/CSS avec variables de substitution
   `{{participant.nom_complet}}`, `{{participant.nom}}`, `{{participant.prenom}}`,
   `{{participant.organisation}}`, `{{formation.titre}}`, `{{formation.sujet}}`,
   `{{formation.dates}}`, `{{formation.lieu}}`, `{{presence.taux}}`, `{{diplome.numero}}`,
   `{{diplome.date_emission}}`, `{{qr_verification}}`.

Plusieurs modèles peuvent être conservés ; un seul est actif. Sans modèle actif, un modèle par
défaut aux couleurs du CRA-W est utilisé. Le rendu passe dans tous les cas par **Dompdf**
(HTML → PDF), ce qui garantit un pipeline unique. Un aperçu avec un participant fictif est
disponible avant toute génération de masse.

**Fonctions** :
- génération unitaire ou en lot (uniquement les éligibles, les autres sont listés avec le motif) ;
- numérotation unique `CRAW-2027-0001` et QR code de vérification pointant vers la page publique
  d'authentification du diplôme ;
- envoi par e-mail avec le PDF en pièce jointe (via la file d'attente), unitaire ou « envoyer tous » ;
- téléchargement par le participant depuis son espace personnel ;
- **impression groupée** : un unique PDF concaténant tous les diplômes, une page par participant,
  prêt pour l'impression recto en série ;
- versionnement : toute régénération produit un nouveau fichier `{numéro}-v{n}.pdf`, l'ancien est
  conservé (RG-08) ;
- archivage des PDF sous `storage/diplomas/` (hors racine web), accessibles uniquement
  via un contrôleur qui vérifie les droits.

**Langue** : le diplôme est rédigé en français (dates au format français) quelle que soit la
langue d'inscription du participant ; seule la page de vérification est bilingue (voir point
ouvert n° 9).

### 3.7 Statistiques alimentaires

Écran dédié agrégeant :
- répartition par régime (tableau + graphique en barres dessiné en CSS, sans bibliothèque JavaScript) ;
- croisement régime × statut (inscriptions confirmées / en attente de paiement), pour ne commander
  que les repas confirmés ;
- nombre de participants avec allergies déclarées, avec la liste des commentaires (pour le traiteur) ;
- précisions de régime et remarques logistiques ;
- **export « fiche traiteur »** en PDF : effectifs par régime (valables pour chaque journée),
  liste nominative des allergies et précisions de régime, sans autre donnée personnelle
  (minimisation RGPD) ; export CSV des statistiques.

### 3.8 Actualités

CRUD complet : titre, slug automatique (modifiable), chapô (généré depuis le corps s'il est vide),
corps en Markdown restreint, image de couverture avec texte alternatif, date de publication,
statut (`draft` / `published`), auteur, métadonnées SEO.

- **Publication planifiée** : un article publié avec une date future n'apparaît qu'à cette date ;
  le filtrage se fait à la lecture, sans tâche planifiée.
- **Image de couverture** : JPEG, PNG ou WebP, 8 Mo maximum, ré-encodée en JPEG et réduite à
  1600 px au plus, stockée hors racine web.
- **Version anglaise** : titre, chapô, corps et description SEO en anglais, facultatifs ; le slug
  est commun aux deux langues.
- Flux RSS par langue et intégration au sitemap.

### 3.9 Bilinguisme français / anglais

Ajouté au périmètre le 20/09/2026.

**Ce qui est traduit**

| Élément | Traduit | Mécanisme |
|---|---|---|
| Pages publiques, en-tête, pied de page, formulaires | oui | gabarits `.en.php` à côté des gabarits français |
| Messages de validation, messages flash, pages d'erreur | oui | `lang/fr.php`, `lang/en.php` (105 clés chacun) |
| Libellés métier (régimes, statuts d'inscription et de paiement) | oui | méthodes `label()` localisées dans le domaine |
| Contenu éditorial (formation, programme, actualités) | oui, si saisi | colonnes `_en`, repli sur le français |
| Instructions de paiement PDF | oui | gabarit `pdf/payment_instructions.en.php` |
| Corps des courriels aux participants | oui | gabarits `emails/*.en.php` et `emails/text/*.en.php` |
| Objets des courriels | **non** (français) | à corriger (§12) |
| Diplômes | non (français) | à arbitrer (point ouvert n° 9) |
| Back-office, feuilles d'émargement et fiche traiteur | non | hors périmètre, usage interne |

**Choix de la langue** : la langue est portée par l'URL (`/fr/…`, `/en/…`), ce qui rend chaque
version indexable séparément et évite tout cookie. Un visiteur arrivant sur `/` est redirigé
selon son navigateur (anglais si `Accept-Language` commence par `en`, français sinon). Le
sélecteur de langue de l'en-tête renvoie vers la même page dans l'autre langue.

**Contenu éditorial** : dans le back-office, chaque champ traduisible (sujet, résumé,
description, infos pratiques, titre et description SEO de la formation ; thème et libellé de
chaque créneau ; titre, chapô, corps et description SEO des actualités) a son équivalent
anglais. Un champ anglais vide affiche le texte français : le site anglais reste complet, au
prix de passages en français tant que la traduction n'est pas saisie. Le jeu de données initial
contient une version anglaise du contenu de démonstration.

**Formats** : dates (`15 March 2027` / `15 mars 2027`), montants (`€450.00` / `450,00 €`) et
pourcentages suivent la langue affichée, sans dépendre de l'extension PHP `intl`.

---

## 4. Règles de gestion (synthèse)

| # | Règle | Mise en œuvre |
|---|---|---|
| RG-01 | Une adresse e-mail ne peut avoir qu'une inscription active par session de formation. | réalisée |
| RG-02 | Une inscription non confirmée (`draft`) est purgée après 48 h. | réalisée (cron) |
| RG-03 | Le nombre de places est limité (paramètre). Au-delà, les inscriptions basculent en liste d'attente ; quand un administrateur annule une inscription, le premier inscrit en attente est promu et prévenu par e-mail. | réalisée |
| RG-04 | La communication structurée est unique, immuable et dérivée de l'identifiant d'inscription. | réalisée |
| RG-05 | Seul un administrateur peut passer un paiement à `paid`, action tracée (qui, quand, montant). | réalisée |
| RG-06 | Un participant est présumé présent ; seules les absences sont saisies. La présence est binaire : ni motif, ni absence justifiée. | réalisée |
| RG-07 | Le diplôme exige un taux de présence ≥ seuil configurable (80 % par défaut) **et** un paiement réglé ou exonéré. | réalisée |
| RG-08 | Un diplôme généré est immuable : toute régénération crée une nouvelle version, l'ancienne est conservée. | réalisée |
| RG-09 | Les données personnelles sont anonymisées 3 ans après la formation (paramétrable), hors obligations comptables. | **partielle** : la durée est paramétrable et annoncée sur la page de confidentialité, mais l'anonymisation automatique n'est pas réalisée |
| RG-10 | Toute action administrateur sensible est journalisée (`audit_log`). | réalisée |
| RG-11 | La langue choisie à l'inscription est conservée ; tous les courriels ultérieurs (rappels, confirmation, diplôme) sont rédigés dans cette langue. | réalisée, sauf les objets des courriels |
| RG-12 | Un contenu éditorial sans traduction anglaise s'affiche en français sur le site anglais. | réalisée |

---

## 5. Modèle de données

Moteur cible **MySQL/MariaDB** en production, **SQLite** pour le développement et la démonstration.
Le DDL est écrit dans un sous-ensemble portable ; les différences (auto-incrément, types, énums)
sont absorbées par `SchemaHelper`. Trois migrations :

| Migration | Objet |
|---|---|
| `001_create_core_tables` | Schéma initial |
| `002_drop_absence_justification` | Retrait de `absences.reason`, `absences.is_justified` et du paramètre associé |
| `003_add_bilingual_support` | `registrations.locale` et colonnes `_en` du contenu traduisible |

Schéma résultant (les colonnes `_en` sont regroupées en fin de ligne) :

```
settings(id PK, setting_key UNIQUE, setting_value, setting_type, updated_at)

admin_users(id PK, email UNIQUE, password_hash, display_name, role ENUM(admin,gestionnaire),
            is_active, failed_attempts, locked_until, last_login_at, created_at)

training(id PK, slug UNIQUE, title, subject, subject_pending BOOL,
         summary, description, practical_info,
         starts_on, ends_on, location_name, location_address,
         location_latitude, location_longitude,
         price_cents, currency, capacity, registration_deadline,
         status ENUM(draft,open,closed,archived), meta_title, meta_description,
         created_at, updated_at,
         subject_en, summary_en, description_en, practical_info_en,
         meta_title_en, meta_description_en)

training_days(id PK, training_id FK, day_date, slot ENUM(am,pm,full),
              label, topic, speaker, starts_at, ends_at, position,
              counts_for_attendance BOOL,
              label_en, topic_en)                    -- les créneaux de présence

registrations(id PK, training_id FK, reference UNIQUE, token UNIQUE,
              civility, last_name, first_name, email, phone, birth_date,
              organisation, job_title,
              street, postal_code, city, country, vat_number, needs_invoice BOOL,
              diet ENUM(standard,vegetarien,vegan,sans_porc,halal,casher,autre), diet_comment,
              has_allergies BOOL, allergies_comment, logistics_comment,
              status ENUM(draft,pending_payment,confirmed,waitlist,cancelled),
              amount_due_cents, structured_communication,
              consent_gdpr_at, consent_terms_at, consent_health_at,
              confirmed_at, cancelled_at, ip_hash, user_agent,
              created_at, updated_at,
              locale)                                -- 'fr' | 'en', langue des courriels

payments(id PK, registration_id FK UNIQUE,
         status ENUM(unpaid,reminded,partial,paid,refunded,waived,cancelled),
         method ENUM(transfer,onsite,exempt),
         amount_cents, received_cents, value_date, due_date, note,
         reminders_sent, last_reminder_at,
         confirmed_by FK admin_users, confirmed_at, created_at, updated_at)

absences(id PK, registration_id FK, training_day_id FK,
         recorded_by FK admin_users, recorded_at,
         UNIQUE(registration_id, training_day_id))

diploma_templates(id PK, name, mode ENUM(background,html),
                  background_path, html_body, field_layout JSON,
                  orientation ENUM(landscape,portrait),
                  is_active, uploaded_by FK, created_at)

diplomas(id PK, registration_id FK, template_id FK NULL, number UNIQUE, version,
         attendance_rate, file_path, verification_code UNIQUE,
         generated_by FK, generated_at, sent_at)

news(id PK, slug UNIQUE, title, excerpt, body, cover_path, cover_alt,
     status ENUM(draft,published), published_at, author_id FK,
     meta_title, meta_description, created_at, updated_at,
     title_en, excerpt_en, body_en, meta_description_en)

email_queue(id PK, registration_id FK NULL, to_email, to_name, template, subject,
            payload JSON, attachments JSON,
            status ENUM(pending,sending,sent,failed), attempts, last_error,
            scheduled_at, sent_at, created_at)

email_log(id PK, registration_id FK NULL, to_email, template,
          subject, status, error, sent_at)

audit_log(id PK, admin_user_id FK, action, entity, entity_id,
          details JSON, ip_hash, created_at)

contact_messages(id PK, name, email, subject, message, ip_hash, handled_at, created_at)

rate_limits(id PK, bucket, expires_at, created_at)  -- limitation de débit, purgée par le cron
```

**Index** : `registrations(training_id, email)`, `registrations(status)`,
`registrations(structured_communication)`, `payments(registration_id)` unique, `payments(status)`,
`absences(registration_id, training_day_id)` unique, `absences(training_day_id)`,
`training_days(training_id, position)`, `diplomas(registration_id)`, `news(status, published_at)`,
`email_queue(status, scheduled_at)`, `email_log(registration_id)`, `audit_log(entity, entity_id)`,
`rate_limits(bucket, created_at)`.

---

## 6. Architecture technique

### 6.1 Choix du socle

| Décision | Retenu | Motivation |
|---|---|---|
| Langage | **PHP ≥ 8.2**, développé et testé sur 8.4 | Typage strict, enums, readonly. 8.2 est le minimum déclaré dans `composer.json`, pour élargir le choix d'hébergement. |
| Framework | **Aucun — micro-noyau maison** | Un site mono-événement de cette taille ne justifie pas Symfony/Laravel. Déploiement par simple copie de fichiers sur un hébergement mutualisé Apache/PHP, sans outillage serveur. Moins de dépendances = moins de surface d'attaque et de maintenance sur un site à cycle de vie court. |
| Base de données | **PDO** — MySQL/MariaDB (prod), SQLite (dev/démo) | Zéro configuration en développement, cible standard en production. |
| Templates | **PHP natif** avec échappement par défaut, variante par langue | Pas de compilation, pas de cache à invalider. |
| CSS | **Écrit à la main, tokens CSS** | Reproduit la charte CRA-W (Bootstrap 5.3 personnalisé) sans embarquer Bootstrap : ~42 ko non compressé, ~8 ko compressé, contre ~230 ko pour Bootstrap. |
| JavaScript | **Vanilla, progressif** (~10 ko, ~3 ko compressé) | Tout fonctionne sans JS. Le JS améliore (copie presse-papier, cocher une colonne de présences, thème). |
| Exécution locale | **Docker** en option (§6.7) | Lancement sans PHP installé sur le poste, pour la démonstration et le développement. |

### 6.2 Dépendances Composer

| Paquet | Usage |
|---|---|
| `dompdf/dompdf` ^3.1 | Génération des PDF (diplômes, instructions, émargement, fiche traiteur) |
| `endroid/qr-code` ^5.1 | QR codes SEPA et vérification de diplôme |
| `phpmailer/phpmailer` ^6.12 | Envoi SMTP fiable (multipart, pièces jointes, TLS) |

Trois dépendances, toutes matures et largement auditées. Extensions PHP requises : `pdo`
(`pdo_sqlite` ou `pdo_mysql`), `mbstring`, `gd`, `json`, `dom`.

### 6.3 Arborescence

```
public/              ← seule racine web exposée
  index.php          ← front controller unique
  assets/{css,js,img}
  .htaccess          ← réécriture + en-têtes de sécurité + cache des assets
src/
  Core/              ← Kernel, Router, Request, Response, View, Database, SchemaHelper,
                       Migrator, Config, Settings, Session, Csrf, Validator, RateLimiter,
                       Auth, Audit, Mailer, Logger, Locale, Translator
  Domain/            ← règles métier pures et testables :
                       StructuredCommunication, AttendanceCalculator, DiplomaEligibility,
                       SepaQrPayload, DietPreference, Markdown, Slugger
  Repository/        ← accès aux données (une classe par agrégat)
  Controller/
    PublicSite/      ← Home, Page, Registration, Payment, News, Contact,
                       DiplomaPublic, Seo, Media, Cron
    Admin/           ← Dashboard, RegistrationAdmin, PaymentAdmin, Attendance,
                       Statistics, Diploma, NewsAdmin, Settings, User, Auth
  Service/           ← RegistrationService, PaymentService, AttendanceService,
                       DiplomaService, StatisticsService, Notifier, MailQueue,
                       PdfService, QrService, SeoService, ExportService, ImageProcessor
  helpers.php        ← e(), url(), t(), localized_field(), formats de dates et montants
  routes.php
lang/                ← fr.php, en.php : chaînes d'interface
templates/
  layouts/ partials/ public/ admin/ emails/ emails/text/ pdf/
                     ← page.php (français, référence) + page.en.php (anglais)
database/
  migrations/ seeds/
storage/             ← hors web : base SQLite, journaux, téléversements, diplômes, cache
bin/
  console.php        ← migrate, migrate:fresh, seed, create-admin, cron, mail:flush, routes, settings
tests/               ← tests du domaine + tests fonctionnels HTTP
docs/
Dockerfile, docker-compose.yml, docker-entrypoint.sh
```

### 6.4 Front controller et routage

Un routeur par expressions régulières, table de routes explicite (`src/routes.php`), avec trois
middlewares déclarés par route : `auth` (session administrateur), `admin` (rôle `admin`) et
`throttle` (limitation de débit des formulaires). La vérification CSRF et les en-têtes de
sécurité sont appliqués par le noyau à toutes les requêtes. URLs propres et stables, pensées pour
le SEO (pas de paramètres de requête dans les URLs indexables).

Les routes publiques sont déclarées une seule fois avec un paramètre `{locale:fr|en}` en tête ;
le noyau en déduit la langue de la requête avant d'exécuter le contrôleur.

### 6.5 Configuration

Fichier `.env` (hors dépôt) lu au démarrage, avec `.env.example` documenté. Les paramètres
métier (prix, capacité, seuil de présence, IBAN, délais de paiement et de relance, durée de
conservation, textes) sont en base et éditables par l'administrateur ; les paramètres
d'infrastructure (BDD, SMTP, clés, jeton du cron) sont dans `.env`.

`DIPLOMA_MIN_ATTENDANCE=0.80` est exposé **à la fois** en variable de configuration et dans
l'écran d'administration : la valeur en base prime, la variable sert de valeur de repli à la
première installation.

Hors production, les URLs générées suivent l'hôte réellement utilisé par le navigateur (utile
avec plusieurs alias locaux sur le même projet). En production, elles reposent exclusivement sur
`APP_URL`, pour qu'un en-tête `Host` falsifié ne puisse pas influencer les liens des courriels ou
les balises `canonical`.

### 6.6 Internationalisation — mise en œuvre

| Composant | Rôle |
|---|---|
| `Locale` | Langue de la requête, déduite du préfixe d'URL ; liste des routes qui n'en portent jamais (`/admin`, `/cron/`, `/media/`, `/assets/`, `sitemap.xml`, `robots.txt`) ; URL de la page courante dans l'autre langue |
| `Translator` + `t()` | Chaînes décidées par le code, avec repli sur le français puis sur la clé |
| `View` | Charge `gabarit.en.php` s'il existe, sinon le gabarit français |
| `url()` | Ajoute automatiquement le préfixe de langue aux routes publiques ; accepte une langue forcée pour composer un courriel hors requête |
| `localized_field()` | Lit la colonne `_en` si la langue est l'anglais et qu'elle est renseignée, sinon la colonne française |
| `Notifier` / `MailQueue` | Figent la langue de l'inscription dans la charge utile du courriel ; le cron la rétablit avant le rendu |

Choix assumé : des gabarits complets par langue plutôt qu'une clé de traduction par phrase. Les
pages publiques sont surtout du texte rédigé ; un gabarit par langue se relit et se corrige comme
un document, sans aller-retour avec un fichier de clés. Contrepartie : toute modification de mise
en page doit être reportée dans les deux versions.

### 6.7 Exécution et déploiement

**Production** : hébergement Apache/PHP classique, racine web sur `public/`, base MySQL/MariaDB,
cron système toutes les dix minutes (ou appel HTTP de `/cron/{jeton}` si l'hébergement n'en
propose pas). Procédure détaillée dans le `README.md`.

**Conteneur Docker** (développement et démonstration) :
- image `php:8.4-cli` avec `pdo_mysql`, `pdo_sqlite`, `mbstring`, `gd` et Composer ;
- serveur intégré de PHP sur le port 8080 — adapté à la démonstration, **pas à la production** ;
- au démarrage, le point d'entrée applique les migrations et, au premier lancement (pas encore
  de base SQLite), charge le jeu de démonstration (24 inscriptions) ;
- `.env` et `storage/` sont montés depuis l'hôte : les données survivent à la recréation du conteneur ;
- `docker-compose.yml` rattache le service à un réseau externe `proxy`, celui du reverse proxy
  Caddy partagé qui donne au projet un alias local. Ce réseau doit exister avant
  `docker compose up`, faute de quoi le lancement échoue ; ce prérequis n'est pas encore décrit
  dans le `README.md`.

### 6.8 Tests

Deux suites, lancées par `php tests/run.php` (domaine seul) ou `php tests/run.php --http`
(domaine + parcours de bout en bout) :
- **domaine** : communication structurée, charge utile SEPA, calcul de présence, éligibilité au
  diplôme, Markdown, slugs ;
- **HTTP** : serveur et base SQLite temporaires, sans toucher aux données de développement ;
  parcours d'inscription complet, refus d'une allergie sans consentement santé, rejet sans jeton
  CSRF, QR SEPA et PDF, confirmation de paiement, délivrance et vérification d'un diplôme,
  publication d'une actualité, pages anglaises, `hreflang` du sitemap et exclusions de
  `robots.txt` dans les deux langues.

Le parcours d'inscription en anglais et la langue des courriels ne sont pas encore couverts.

---

## 7. Sécurité, RGPD, accessibilité

### 7.1 Sécurité applicative

| Risque | Contre-mesure |
|---|---|
| Injection SQL | PDO en requêtes préparées exclusivement, `ATTR_EMULATE_PREPARES=false` |
| XSS | Échappement systématique à la sortie (`e()`), Markdown assaini en liste blanche, CSP `default-src 'self'` sans aucune source distante ; scripts et styles en ligne autorisés (`'unsafe-inline'`) pour appliquer le thème avant le rendu |
| CSRF | Jeton par session, vérifié par le noyau sur toute requête non idempotente |
| Détournement de session | Cookies `HttpOnly`, `SameSite=Lax`, `Secure` en production ou sous HTTPS, régénération d'identifiant à la connexion |
| Force brute | Formulaires publics et connexion : 10 soumissions par IP sur 15 minutes. Comptes : 8 tentatives sur 15 minutes, verrouillage de 15 minutes après 5 échecs consécutifs |
| Énumération d'inscriptions | Jetons de 32 octets aléatoires (64 caractères hexadécimaux), comparaison à temps constant, pas d'identifiants séquentiels dans les URLs publiques |
| Upload malveillant | Liste blanche de types MIME réels, taille maximale, ré-encodage des images, stockage hors racine web, noms générés |
| Falsification de l'en-tête `Host` | En production, URLs générées uniquement à partir de `APP_URL` |
| En-têtes | `Content-Security-Policy`, `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`, HSTS en production |
| Mots de passe | `password_hash()` (algorithme par défaut de PHP), 12 caractères minimum |
| Cron exposé en HTTP | Jeton secret comparé à temps constant ; réponse 404 et journalisation en cas d'échec |

### 7.2 Traçabilité

Journal d'audit de toute action administrateur sensible (confirmation, exonération ou annulation
de paiement, annulation et suppression d'inscription, génération et envoi de diplôme,
modification de configuration, gestion des comptes), avec IP hachée.

### 7.3 RGPD

- **Base légale** : exécution d'une mesure précontractuelle/contractuelle (inscription).
  Les allergies relèvent de l'article 9 (données de santé) : recueillies sur **consentement
  explicite et distinct**, horodaté, avec mention de la finalité (adaptation des repas).
- **Minimisation** : les champs de santé ne sont visibles que dans l'écran de gestion des repas
  et dans la fiche du participant ; ils sont exclus des exports généraux. La fiche traiteur ne
  nomme que les participants ayant déclaré une allergie.
- **Durée de conservation** : 3 ans après la formation (paramétrable), durée annoncée sur la page
  de confidentialité. **L'anonymisation automatique prévue n'est pas encore réalisée** : à défaut,
  la suppression doit être faite manuellement depuis le back-office (voir §12).
- **Droits** : page de confidentialité détaillant les droits d'accès, rectification, effacement,
  avec point de contact DPO. La demande d'effacement passe par le formulaire de contact ; un
  administrateur supprime alors l'inscription. L'espace participant ne propose pas de demande
  de suppression en libre-service.
- **Sous-traitance** : aucune donnée n'est envoyée à un service tiers (pas de police distante,
  pas de CDN externe, pas d'outil d'analyse d'audience).
- **Cookies et stockage** : un seul cookie, celui de la session technique. La préférence de thème
  est gardée dans le stockage local du navigateur et n'est jamais transmise ; la langue est portée
  par l'URL. Aucun cookie de mesure d'audience → pas de bandeau de consentement requis.

### 7.4 Accessibilité (WCAG 2.1 AA)

Le CRA-W publie une déclaration d'accessibilité : l'obligation s'applique (directive UE 2016/2102).
- HTML sémantique, un seul `<h1>` par page, hiérarchie de titres continue ;
- attribut `lang` de la page conforme à la langue affichée ; lien de changement de langue
  annoncé dans la langue cible (`lang="en"`) ;
- lien d'évitement, navigation clavier complète, focus toujours visible ;
- libellés `<label>` associés, erreurs de formulaire annoncées (`aria-live`, `aria-describedby`) ;
- contrastes ≥ 4,5:1 vérifiés en thème clair **et** sombre ;
- pas d'information portée par la seule couleur (les statuts ont une icône et un texte) ;
- images décoratives `alt=""`, images informatives décrites (texte alternatif des couvertures d'actualités) ;
- `prefers-reduced-motion` respecté ;
- déclaration d'accessibilité publiée en français et en anglais.

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
fichiers du CRA-W. L'en-tête et le pied de page existent en version française et anglaise ; le
sélecteur FR/EN de l'en-tête, décoratif sur le site du CRA-W, est ici fonctionnel. Le logo
officiel n'est pas repris : un bloc-marque textuel est utilisé, à remplacer par le fichier
officiel fourni par le service communication (emplacement documenté dans le `README.md`).

---

## 9. SEO

| Levier | Mise en œuvre |
|---|---|
| Indexabilité | URLs propres, `canonical` sur chaque page, `robots.txt` (espaces personnels exclus dans les deux langues), `sitemap.xml` généré dynamiquement |
| Multilingue | Préfixe de langue dans l'URL ; `<link rel="alternate" hreflang="…">` vers la version française et la version anglaise sur chaque page ; sitemap listant les deux versions de chaque page avec leurs alternatives (`xhtml:link`) ; `og:locale` `fr_BE` / `en_US` ; `inLanguage` `fr-BE` / `en-US` dans le JSON-LD. Pas d'`x-default` ; segments d'URL en français dans les deux langues |
| Métadonnées | `title` et `meta description` par page, titre et description de la formation et des actualités éditables en français et en anglais depuis l'administration |
| Données structurées | JSON-LD `EducationEvent` (dates, lieu, offre, statut, organisateur) sur l'accueil, `Organization`, `BreadcrumbList`, `NewsArticle` sur les actualités, `FAQPage` sur les infos pratiques |
| Partage social | OpenGraph + Twitter Cards ; image de couverture de l'article, sinon image par défaut. L'image par défaut est un **SVG**, format que Facebook, LinkedIn et X n'affichent pas (voir §12) |
| Performance (Core Web Vitals) | Pas de police distante, CSS unique ~8 ko compressé, images redimensionnées avec `width`/`height` et `loading="lazy"`, JS différé ~3 ko compressé, cache long sur les assets versionnés |
| Sémantique | Un `<h1>` par page, hiérarchie stricte, HTML5 structurant |
| Contenu | Pages dédiées programme / infos pratiques / actualités plutôt qu'une page unique, pour couvrir plus de requêtes |
| Technique | HTTPS forcé (redirection à activer dans `.htaccess`), 404 propre, pas de contenu dupliqué entre langues grâce aux `hreflang` |
| Local | JSON-LD `Place` avec l'adresse du bâtiment et les coordonnées géographiques |

---

## 10. Plan de livraison

| Lot | Contenu | État | Commit |
|---|---|---|---|
| L0 | Analyse fonctionnelle et technique | livré (v1.0) | `5ca0949` |
| L1 | Squelette, noyau, base de données, migrations | livré | `fb492b9` |
| L2 | Thème CRA-W, layouts, composants | livré | `fb492b9` |
| L3 | Site public + SEO | livré | `fb492b9` |
| L4 | Inscription + paiement (QR SEPA, communication structurée) | livré | `fb492b9` |
| L5 | E-mails transactionnels + cron | livré | `fb492b9` |
| L6 | Back-office (inscrits, paiements, présences, statistiques, actualités, configuration) | livré | `9070c72` |
| L7 | Diplômes PDF (modèle, génération, envoi, impression groupée) | livré | `9070c72` |
| L8 | Exports, tests, documentation d'installation | livré | `9070c72` |
| — | Présence binaire, sans absence justifiée (arbitrage du 19/09) | livré | `d93881c` |
| L9 | Bilinguisme FR/EN : site public, courriels, PDF d'instructions, contenu `_en` | livré | `582c311` |
| L10 | Exécution en conteneur Docker | livré | `582c311` |
| L11 | Finitions proposées : actions « à corriger » du §12 | à planifier | — |

## 11. Points ouverts à arbitrer

> **Arbitrages rendus.**
> - **19/09/2026** — La présence est binaire : absent ou présent, sans motif ni absence
>   justifiée, et sans processus supplémentaire (retiré du code et du schéma).
> - **20/09/2026** — Le site public et les courriels aux participants sont bilingues FR/EN ;
>   le back-office reste en français.
>
> Tarif, bâtiment et capacité restent aux valeurs d'hypothèse, toutes modifiables depuis
> Administration → Configuration.

| # | Question | Hypothèse retenue à défaut de réponse |
|---|---|---|
| 1 | Sujet, programme détaillé, intervenants | Contenu de remplacement + bannière « en cours de définition », éditable en back-office |
| 2 | Prix de la formation | 450 € TTC, paramétrable ; gratuité possible (prix à 0 : pas de circuit de paiement) |
| 3 | Bâtiment exact | Léon Lacroix, 9 rue de Liroux, 5030 Gembloux, paramétrable |
| 4 | Coordonnées bancaires réelles | IBAN de démonstration en configuration, à remplacer avant mise en production |
| 5 | Capacité d'accueil | 40 places, paramétrable, liste d'attente au-delà |
| 6 | Logo officiel et visuels | Bloc-marque textuel provisoire, emplacement de remplacement documenté |
| 7 | Serveur SMTP | `mail()` en repli, SMTP recommandé, configuré dans `.env` |
| 8 | Traduction anglaise du contenu éditorial : qui la rédige et la relit ? | Contenu anglais de démonstration fourni ; à défaut de traduction, le texte français s'affiche |
| 9 | Langue du diplôme | Français pour tous. Option : diplôme dans la langue d'inscription (gabarit par défaut et variables de date à décliner) |
| 10 | Le rôle `gestionnaire` peut-il modifier le programme ? | Oui, actuellement. À réserver au rôle `admin` si le programme est considéré comme de la configuration |
| 11 | Segments d'URL en anglais (`/en/practical-information`) | Non : segments français dans les deux langues, sans impact sur l'indexation grâce aux `hreflang` |
| 12 | Pièces jointes PDF des courriels (instructions de paiement, justificatif de paiement) | Pas de pièce jointe : lien vers le PDF d'instructions ; pas de justificatif de paiement (voir §12) |

---

## 12. Écarts entre l'analyse initiale (v1.0) et la réalisation

**Appréciation** : *assumé* — l'écart est justifié, rien à faire ; *à arbitrer* — une décision
du commanditaire est attendue (§11) ; *à corriger* — travail proposé au lot L11.

| Sujet | Prévu en v1.0 | Réalisé | Appréciation |
|---|---|---|---|
| Multilingue | Hors périmètre, libellés centralisés pour un ajout ultérieur | Site public et courriels en FR/EN (§3.9) | Évolution de périmètre (arbitrage du 20/09) |
| Exécution | Hébergement mutualisé uniquement | + conteneur Docker pour le développement et la démonstration | Complément ; le prérequis du réseau `proxy` est à documenter dans le `README.md` |
| Version de PHP | 8.4 | ≥ 8.2 déclaré, 8.4 utilisé | Assumé : plus d'hébergements compatibles |
| Objets des courriels | — | En français, même pour un participant inscrit en anglais | **À corriger** : faire passer les objets par `t()` avec la langue de l'inscription |
| Anonymisation (RG-09) | Anonymisation automatique par le cron après 3 ans | Durée paramétrable et annoncée, mais aucun traitement automatique | **À corriger** avant l'échéance annoncée (mars 2030 avec la durée par défaut) : commande `privacy:anonymize` appelée par le cron |
| Image de partage par défaut | Image générée | SVG statique, ignoré par les réseaux sociaux | **À corriger** : fournir un PNG ou JPEG 1200 × 630 |
| Temporisation anti-robot | Soumission refusée en moins de 3 s | Non réalisée (honeypot et limitation de débit seulement) | À corriger si des soumissions automatisées apparaissent ; faible coût |
| Statut `refunded` | Remboursement possible | Présent dans le modèle, aucune action ne l'attribue | À arbitrer : ajouter l'action « rembourser » ou retirer le statut |
| Pièces jointes des courriels | PDF d'instructions joint aux n° 1 et 2, justificatif PDF joint au n° 4 | Lien vers le PDF d'instructions ; aucun justificatif de paiement | À arbitrer (point ouvert n° 12) |
| Demande d'effacement | Depuis l'espace participant | Via le formulaire de contact, suppression par un administrateur | À arbitrer ; conforme au RGPD en l'état |
| Diplôme | Langue non précisée | Français uniquement | À arbitrer (point ouvert n° 9) |
| Programme et rôle `gestionnaire` | Gestionnaire exclu de la configuration | Peut modifier le programme | À arbitrer (point ouvert n° 10) |
| Précision de régime | Obligatoire pour halal, casher et autre | Obligatoire pour « autre » seulement, proposée pour halal et casher | Assumé : évite de bloquer une inscription sur un choix qui se suffit souvent à lui-même |
| Vérification de l'e-mail | Format + contrôle DNS MX léger | Format seulement | Assumé : un contrôle MX ralentit la soumission et échoue sur certains réseaux d'entreprise |
| Téléphone | Normalisé E.164 si possible | Format libre, 8 à 15 chiffres | Assumé |
| N° TVA | Format belge | Format européen générique | Assumé : les participants étrangers sont désormais visés |
| Limitation de débit | 5 tentatives / IP / heure | 10 soumissions / IP / formulaire / 15 minutes | Assumé : moins pénalisant pour plusieurs inscrits derrière une même adresse IP d'entreprise |
| Verrouillage des comptes | Progressif | Durée fixe (15 min après 5 échecs) + limite par compte | Assumé |
| CSP | Stricte | `'unsafe-inline'` autorisé pour les scripts et styles | À durcir (nonces) si une exigence de sécurité le demande ; aucune donnée utilisateur n'est injectée dans un script |
| Cookie de thème | Cookie de préférence | Stockage local du navigateur | Assumé : plus favorable au RGPD |
| Grille de présences | Enregistrement AJAX au fil de l'eau | Soumission de formulaire (toute la grille ou un jour) | Assumé : un seul chemin, qui fonctionne sans JavaScript |
| Éditeur de positions du diplôme | Prévisualisation en direct | Positions saisies en mm + aperçu PDF | Assumé |
| Graphiques statistiques | SVG généré côté serveur | Barres en CSS | Équivalent |
| Fiche traiteur | Un document par jour | Un document unique, effectifs valables pour chaque journée | Assumé tant que les absences ne sont pas connues à l'avance |
| Actualités planifiées | Statut `scheduled` publié par le cron | Date de publication future, filtrée à la lecture | Équivalent, plus simple |
| Images | Converties en WebP | Ré-encodées en JPEG | Assumé : WebP n'est pas toujours disponible dans GD sur les hébergements mutualisés |
| Poids du CSS | ~15 ko | ~42 ko non compressé, ~8 ko compressé | Assumé : l'écart avec Bootstrap reste large |
| Espace de noms des contrôleurs publics | `Controller/Public` | `Controller/PublicSite` | Assumé : `public` est un mot réservé de PHP |
