# Formation CRA-W — site de présentation et d'inscription

Site de présentation et de gestion des inscriptions pour une formation organisée par le
**Centre wallon de Recherches agronomiques**, du **lundi 15 au vendredi 19 mars 2027**
au bâtiment Léon Lacroix, à Gembloux.

Le sujet de la session n'étant pas encore arrêté, le site est publiable en l'état : il affiche
une mention « en cours de définition » et tout le contenu est éditable depuis le back-office.

> **Analyse fonctionnelle et technique** : [`docs/ANALYSE.md`](docs/ANALYSE.md)

---

## Ce que fait le site

**Côté public**
- présentation de la formation, programme jour par jour, informations pratiques, actualités ;
- inscription en trois étapes, **sans création de compte**, avec recueil des préférences
  alimentaires (régime, allergies, remarques) ;
- paiement par **virement bancaire** : communication structurée belge, QR code SEPA
  (norme EPC069-12) et instructions téléchargeables en PDF ;
- espace participant accessible par lien personnel : statut, paiement, diplôme ;
- page publique de vérification de l'authenticité d'un diplôme.

**Côté administration**
- tableau de bord (inscriptions, paiements, remplissage, repas) ;
- liste filtrable des inscrits, fiche détaillée, export CSV compatible Excel ;
- confirmation manuelle des paiements, exonérations, relances automatiques ;
- **pointage des présences à l'envers** : tout le monde est présumé présent, seules les
  absences sont saisies — présent ou absent, sans motif à collecter ni à arbitrer ;
- statistiques alimentaires et fiche traiteur PDF ;
- génération des diplômes PDF à partir d'un **modèle fourni par l'administrateur**, envoi par
  courriel avec le PDF en pièce jointe, et impression groupée en un seul fichier ;
- rédaction et publication d'actualités ;
- configuration complète (prix, capacité, seuil de présence, coordonnées bancaires, RGPD).

---

## Prérequis

Deux façons de lancer le projet : avec Docker (rien d'autre à installer), ou directement avec PHP.

**Avec Docker**

- [Docker](https://docs.docker.com/get-docker/) et Docker Compose (inclus dans Docker Desktop)

**Sans Docker**

- PHP **8.2 ou supérieur** (développé et testé sur 8.4)
- Extensions : `pdo`, `pdo_sqlite` ou `pdo_mysql`, `mbstring`, `gd`, `json`, `dom`
- [Composer](https://getcomposer.org/)
- MySQL/MariaDB en production (SQLite suffit en développement)

---

## Installation

### Avec Docker

```bash
git clone <dépôt> formation-craw
cd formation-craw

cp .env.example .env
# Renseigner APP_KEY et CRON_TOKEN dans .env, par exemple :
sed -i "s/^APP_KEY=$/APP_KEY=$(openssl rand -hex 32)/" .env
sed -i "s/^CRON_TOKEN=$/CRON_TOKEN=$(openssl rand -hex 16)/" .env

docker compose up -d --build
```

Le site est alors disponible sur **<http://localhost:8080>**. Au premier démarrage, le
conteneur applique les migrations et charge automatiquement un jeu de données de
démonstration (24 inscriptions, paiements variés, absences, statistiques).

Créer ensuite un compte administrateur :

```bash
docker compose exec app php bin/console.php create-admin
```

(l'e-mail, le nom et le mot de passe sont demandés interactivement, ou passés directement en
arguments : `create-admin <email> <nom> <mot-de-passe>`).

Le fichier `.env` et le dossier `storage/` sont montés depuis l'hôte : la base SQLite, les
journaux, les téléversements et les diplômes générés survivent à `docker compose down` puis
`up`. Pour repartir de zéro, supprimer `storage/database.sqlite` avant de relancer.

| Commande | Effet |
|---|---|
| `docker compose up -d --build` | Démarre (ou reconstruit) le conteneur |
| `docker compose logs -f app` | Suit les journaux du serveur |
| `docker compose exec app php bin/console.php <commande>` | Exécute une commande d'administration (voir [tableau ci-dessous](#commandes-disponibles)) |
| `docker compose exec app php tests/run.php --http` | Lance les tests dans le conteneur |
| `docker compose down` | Arrête le conteneur (les données sont conservées) |

### Sans Docker

```bash
git clone <dépôt> formation-craw
cd formation-craw

composer install

cp .env.example .env
php -r "echo 'APP_KEY=' . bin2hex(random_bytes(32)) . PHP_EOL;"   # à reporter dans .env
php -r "echo 'CRON_TOKEN=' . bin2hex(random_bytes(16)) . PHP_EOL;" # idem

php bin/console.php migrate
php bin/console.php seed
php bin/console.php create-admin
```

Puis, en développement :

```bash
composer serve       # http://localhost:8080
```

Pour disposer immédiatement d'un jeu de données réaliste (24 inscriptions, paiements variés,
absences, statistiques) :

```bash
php bin/console.php migrate:fresh --force
php bin/console.php seed --demo
```

### Mise en production

1. La racine web du serveur doit pointer sur le dossier **`public/`**, et sur lui seul.
   Les dossiers `src/`, `storage/`, `templates/` et `vendor/` ne doivent jamais être exposés.
2. Renseigner `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, la base de données
   et le serveur SMTP.
3. Donner les droits d'écriture au serveur web sur `storage/`.
4. Activer HTTPS, puis décommenter la redirection correspondante dans `public/.htaccess`.
5. **Remplacer l'IBAN de démonstration** dans l'administration, rubrique Configuration.
6. Programmer la tâche planifiée (voir ci-dessous).

---

## Tâches planifiées

Le cron envoie les relances de paiement, vide la file d'attente des courriels et purge les
inscriptions abandonnées. Une exécution toutes les dix minutes convient.

```cron
*/10 * * * * cd /chemin/vers/formation-craw && php bin/console.php cron >> storage/logs/cron.log 2>&1
```

Si l'hébergement ne propose pas de cron système, la même tâche est déclenchable par HTTP à
l'aide du jeton défini dans `.env` :

```
https://formation.cra.wallonie.be/cron/<CRON_TOKEN>
```

Avec Docker, la crontab de l'hôte appelle simplement la commande dans le conteneur :

```cron
*/10 * * * * cd /chemin/vers/formation-craw && docker compose exec -T app php bin/console.php cron >> storage/logs/cron.log 2>&1
```

---

## Commandes disponibles

Toutes ces commandes s'exécutent avec `php bin/console.php <commande>` (ou, avec Docker,
`docker compose exec app php bin/console.php <commande>`).

| Commande | Effet |
|---|---|
| `migrate` | Applique les migrations en attente |
| `migrate:fresh --force` | Recrée la base — **destructif** |
| `seed [--demo]` | Charge les données initiales |
| `create-admin` | Crée ou réinitialise un compte administrateur |
| `cron` | Relances, envois de courriels, purges |
| `mail:flush` | Vide la file d'attente des courriels |
| `routes` | Liste les routes déclarées |
| `settings` | Affiche les paramètres métier |

---

## Tests

```bash
php tests/run.php            # règles métier (90 assertions)
php tests/run.php --http     # + parcours complets de bout en bout (193 assertions)
```

Les tests HTTP démarrent leur propre serveur et leur propre base SQLite temporaire, puis
restaurent l'environnement : ils ne touchent jamais aux données de développement.

Ils couvrent le parcours d'inscription complet, le refus d'une allergie déclarée sans
consentement santé, le rejet des soumissions sans jeton CSRF, la génération du QR SEPA et des
PDF, la confirmation de paiement, la délivrance et la vérification d'un diplôme, ainsi que la
publication d'une actualité.

---

## Paramètres notables

| Paramètre | Emplacement | Défaut |
|---|---|---|
| Seuil de présence pour le diplôme | Administration → Configuration, ou `DIPLOMA_MIN_ATTENDANCE` dans `.env` | **80 %** |
| Prix de la formation | Administration → Configuration | 450,00 € |
| Capacité | Administration → Configuration | 40 places |
| Délai de paiement | Administration → Configuration | 15 jours |
| Relances | Administration → Configuration | 7 et 3 jours avant échéance |
| Conservation des données | Administration → Configuration | 3 ans |

Le seuil de présence est lu en priorité depuis la base (donc depuis le back-office) ;
`DIPLOMA_MIN_ATTENDANCE` sert de valeur de repli à la première installation.

---

## Modèle de diplôme

L'administrateur fournit son propre visuel depuis **Administration → Diplômes → Modèle**,
de deux façons au choix :

1. **Fond + calque** (recommandé) : dépôt d'une image A4 paysage — idéalement 3508 × 2480 px
   pour une impression à 300 dpi — puis positionnement de chaque information en millimètres.
   Aucune compétence technique n'est requise.
2. **Gabarit HTML** (avancé) : rédaction du diplôme en HTML/CSS avec des variables
   `{{participant.nom_complet}}`, `{{formation.sujet}}`, `{{presence.taux}}`, etc.

Un aperçu PDF avec un participant fictif est disponible avant toute génération de masse.
Sans modèle déposé, un modèle par défaut aux couleurs du CRA-W est utilisé.

Chaque diplôme porte un numéro unique (`CRAW-2027-0001`) et un QR code renvoyant vers une page
publique de vérification de son authenticité.

---

## Organisation du code

```
public/          racine web : front controller, feuilles de styles, scripts, images
src/
  Core/          routeur, base de données, vues, sessions, CSRF, authentification, courriels
  Domain/        règles métier pures et testées (communication structurée, SEPA, présences…)
  Repository/    accès aux données, une classe par agrégat
  Controller/    contrôleurs publics et d'administration
  Service/       orchestration : inscriptions, paiements, diplômes, exports, statistiques
templates/       gabarits PHP : layouts, partials, pages publiques, administration, courriels, PDF
database/        migrations et jeux de données initiaux
storage/         hors web : base SQLite, journaux, téléversements, diplômes générés
bin/console.php  commandes d'administration
tests/           tests des règles métier et tests fonctionnels HTTP
docs/ANALYSE.md  analyse fonctionnelle et technique
Dockerfile, docker-compose.yml, docker-entrypoint.sh
                 image et orchestration pour un lancement en conteneur (voir Installation)
```

---

## Charte graphique

Le thème reproduit celui de [cra.wallonie.be](https://www.cra.wallonie.be/fr) : accent vert
`#198754`, thèmes clair et sombre, en-tête à deux niveaux, pied de page sombre à quatre
colonnes, boutons en pilule, pastilles de date circulaires et séparateurs en vague.

La feuille de styles est écrite à la main (environ 20 ko) plutôt que d'embarquer Bootstrap
(environ 230 ko) : à rendu équivalent, le gain est direct sur les Core Web Vitals.
Aucune police ni aucun script distant n'est chargé, ce qui évite tout transfert de données
vers un tiers.

**Le logo officiel n'est pas repris** : un bloc-marque textuel provisoire est utilisé.
Pour le remplacer, déposez le fichier fourni par le service communication dans
`public/assets/img/` et remplacez `.brand__mark` dans `templates/partials/header.php`.

---

## Sécurité et conformité

- requêtes préparées exclusivement, échappement systématique à la sortie ;
- jeton CSRF sur toute écriture, cookies `HttpOnly` / `SameSite=Lax` ;
- limitation de débit par IP et par compte, verrouillage progressif ;
- en-têtes `Content-Security-Policy`, `X-Frame-Options`, `Referrer-Policy`, HSTS en production ;
- images téléversées systématiquement ré-encodées, stockées hors racine web ;
- journal d'audit de toutes les actions sensibles ;
- allergies traitées comme des données de santé : consentement explicite et distinct, accès
  restreint, exclusion des exports généraux ;
- aucun cookie de mesure d'audience, donc aucun bandeau de consentement nécessaire ;
- accessibilité visée : **WCAG 2.1 niveau AA**, site pleinement utilisable sans JavaScript.
