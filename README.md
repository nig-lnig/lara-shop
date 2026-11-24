# Lara-Shop

Application e-commerce de papeterie / bureautique réalisée avec **Laravel** pour l’exposé
« Développement Web – Langage PHP » (EST Safi, LP-ISIR 2026/2027). Le modèle de données
reprend les pages 3 à 7 du sujet (Client, Commande, Fournisseur, Produit, Ligne Commande).

## Versions utilisées

| Outil | Version |
|---|---|
| PHP | 8.2.26 (minimum 8.2) |
| Laravel | 12.73.0 |
| Base de données | SQLite (par défaut) — MySQL 8 / MariaDB possible |
| Front-end | Blade + CSS simple (`public/css/app.css`) + un peu de JavaScript (`public/js/app.js`), **sans build npm** |
| PDF | barryvdh/laravel-dompdf 3.1 (dompdf 3.1) |
| Excel | phpoffice/phpspreadsheet 5.12 |

## Installation

```bash
composer install
cp .env.example .env          # Windows : copy .env.example .env
php artisan key:generate
php artisan migrate --seed    # crée database/database.sqlite si besoin et ajoute les données de démo
php artisan storage:link      # pour les images de produits envoyées par l'admin
php artisan serve
```

Puis ouvrir <http://127.0.0.1:8000>.

Pour revenir aux données de démonstration à tout moment : `php artisan migrate:fresh --seed`.

**Utiliser MySQL (WAMP/XAMPP) au lieu de SQLite :** créer une base `lara_shop`, puis dans `.env`
remplacer `DB_CONNECTION=sqlite` par les lignes MySQL commentées juste en dessous.

## Comptes de démonstration

Mot de passe pour tous : **`password`** (comptes de démonstration créés par le seeder ; les nouveaux comptes
doivent respecter la politique de mot de passe ci-dessous).

| Rôle | E-mail | Particularité |
|---|---|---|
| Administrateur | `admin@larashop.test` | accès à `/admin` |
| Client | `client@larashop.test` | Cli-000001, remise spéciale **10 %**, 3 commandes, 3 favoris |
| Client | `youssef@larashop.test` | Cli-000002, sans remise, une commande annulée |
| Client | `sara@larashop.test` | Cli-000008, sans remise |

## Lancer les tests

```bash
php artisan test
```

62 tests (inscription/connexion, e-mail et mot de passe, recherche et filtres, commande avec remise,
stock insuffisant, prix historiques, double envoi, annulation et restitution du stock, droits d’accès,
exports CSV / Excel / PDF, visibilité des produits, images).

## Exports

| Où | Formats |
|---|---|
| Admin, au-dessus de chaque liste | « Exporter ces résultats » : CSV, Excel ou PDF avec les filtres de la liste |
| Admin › Exports | chaque table en CSV / Excel / PDF ; **Export complet** : classeur Excel (7 onglets) ou archive CSV (ZIP) |
| Client › Mes données | chaque fichier en CSV / Excel / PDF ; rapport PDF complet, classeur Excel, archive ZIP |
| Page d’une commande | récapitulatif PDF (client propriétaire ou admin) |

Excel : en-tête bleu encre figé avec filtres, lignes alternées, montants en vrais nombres au format « 1 234,50 DH ».
PDF : en-tête Lara-Shop, tableaux lisibles, pied de page « Page 1 / 2 ».

## Produits : image et visibilité

- **Image** : le formulaire admin affiche un **aperçu avant l’enregistrement** (glisser-déposer accepté).
  JPG, PNG ou WebP ; la taille maximale suit `upload_max_filesize` de PHP (2 Mo sous WAMP par défaut).
  Les images sont servies par `/storage/...` : la commande `php artisan storage:link` est obligatoire.
  L’adresse des images suit l’adresse utilisée dans le navigateur (artisan serve, localhost, dossier WAMP).
- **Visible dans la boutique** : case à cocher du formulaire produit. Décochée, le produit disparaît du
  catalogue, de la recherche, du panier et de la commande, mais reste dans l’admin et les commandes passées.

## Comptes : e-mail et mot de passe

- E-mail : format strict (RFC + contrôle PHP, domaine avec point), mis en minuscules, unique.
  `SHOP_EMAIL_DNS_CHECK=true` vérifie en plus que le domaine existe (nécessite Internet).
- Mot de passe : **10 caractères minimum, majuscule et minuscule, chiffre et symbole** (`Password::defaults()`
  dans `app/Providers/AppServiceProvider.php`). La liste des règles se coche en direct dans le formulaire.
  `SHOP_PASSWORD_LEAK_CHECK=true` (par défaut) refuse aussi les mots de passe présents dans des fuites
  publiques (haveibeenpwned.com) ; sans Internet ce contrôle est ignoré.

## Correspondance avec le sujet (PDF)

| PDF | Table | Colonnes |
|---|---|---|
| Client (Code Client#, Nom, Rue, Code Postal, Ville, Type, Remise Spéciale) | `clients` | `code` (Cli-000001), `name`, `street`, `postal_code` (texte), `city`, `type` (PU/PA/LI/AS/DI), `special_discount` (0–100) |
| Commande (Numéro#, Date, Condition, Code Client) | `orders` | `number` (Com-000001), `order_date`, `payment_terms` (LIV/T30), `client_id` |
| Fournisseur (Code#, Nom, Ville) | `suppliers` | `code` (Fr-000001), `name`, `city` |
| Produit (Code#, Désignation, Prix Unitaire, Famille, Code Fournisseur) | `products` | `code` (Pr-000001), `designation`, `unit_price` DECIMAL(10,2), `family` (PA/BU/FO/LI), `supplier_id` |
| Ligne Commande (Numéro Commande#, Code Produit#, Quantité) | `order_lines` | `order_id`, `product_id`, `quantity` + index **unique (order_id, product_id)** |

Chaque table garde un `id` numérique Laravel (clé interne et clés étrangères) ; le **code métier**
affiché partout et dans les exports est généré par le serveur à partir de cet id
(`app/Models/Concerns/HasBusinessCode.php`) et protégé par un index unique.

Colonnes ajoutées pour l’e-commerce : `orders.status` (suivi, distinct de la condition de paiement),
`orders.discount_rate / subtotal / discount_amount / total` et `order_lines.unit_price / line_total`
(montants figés à la commande), `orders.checkout_token` (anti double envoi), `orders.stock_restored_at`
(restitution unique), `products.stock / low_stock_threshold / image_path / description`,
`archived_at` (archivage), tables `users` (comptes, `role`, `client_id`) et `favorites`.

## Règle d’arrondi des montants

Tout le calcul est dans `app/Services/PriceCalculator.php` :
montants stockés en DECIMAL(10,2), calculs en **centimes entiers** ; total ligne = prix × quantité ;
remise = sous-total × taux / 100 **arrondie au centime le plus proche, demi supérieur** (0,005 → 0,01) ;
total = sous-total − remise.

## Organisation du code

- `routes/web.php` — toutes les routes (public, invités, clients, admin)
- `app/Http/Controllers` — contrôleurs client ; `Admin/` pour l’administration ; `Auth/` pour l’inscription et la connexion
- `app/Models` — modèles Eloquent et libellés français des codes (`Client::TYPES`, `Product::FAMILIES`, `Order::STATUSES`…)
- `app/Services` — `Cart` (panier en session), `OrderService` (commande en transaction, statuts), `PriceCalculator`
- `app/Support` — `CsvExport` (CSV UTF-8, anti-injection de formules, ZIP) et `ExportDatasets` (colonnes des exports)
- `app/Policies/OrderPolicy.php`, `app/Http/Middleware` — droits (propriétaire de la commande, rôles admin/client)
- `resources/views` — gabarits Blade (`layouts/`, `components/`, un dossier par écran)
- `database/migrations`, `database/seeders` — schéma et données de démo
