<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Stationery and office supplies. Some products are on purpose low in stock
 * or out of stock, to demonstrate the stock badges and the admin alert.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::orderBy('id')->pluck('id')->values(); // index 0..5

        // [designation, price, family, supplier index, stock, description]
        $products = [
            // PA – Papeterie
            ['Ramette papier A4 80 g (500 feuilles)', '49.90', 'PA', 0, 120, 'Papier blanc multifonction pour imprimantes laser et jet d’encre.'],
            ['Cahier 96 pages grands carreaux 17x22', '12.50', 'PA', 0, 200, 'Couverture polypro résistante, papier 90 g.'],
            ['Cahier spirale A4 180 pages', '24.90', 'PA', 5, 80, 'Petits carreaux, 4 couleurs de couverture assorties.'],
            ['Bloc-notes A5 ligné 100 feuilles', '9.90', 'PA', 0, 150, 'Feuilles détachables, reliure collée en tête.'],
            ['Enveloppes kraft C4 (lot de 50)', '39.00', 'PA', 5, 4, 'Fermeture autocollante, format 229 x 324 mm.'],
            ['Papier couleur A4 assorti (100 feuilles)', '29.50', 'PA', 5, 60, '5 couleurs vives, 80 g.'],
            ['Notes repositionnables 76x76 (lot de 6)', '34.90', 'PA', 1, 3, '6 blocs de 100 feuilles, couleurs pastel.'],
            ['Chemises cartonnées A4 (lot de 10)', '18.00', 'PA', 0, 0, 'Carton 220 g, couleurs assorties.'],

            // BU – Bureautique
            ['Classeur à levier A4 dos 80 mm', '22.00', 'BU', 1, 90, 'Contient jusqu’à 600 feuilles, étiquette de dos.'],
            ['Agrafeuse métal 25 feuilles', '45.00', 'BU', 1, 35, 'Agrafes 24/6 et 26/6, chargement par le dessus.'],
            ['Perforatrice 2 trous 30 feuilles', '39.90', 'BU', 1, 25, 'Guide de format, réservoir à confettis.'],
            ['Calculatrice de bureau 12 chiffres', '89.00', 'BU', 4, 18, 'Double alimentation solaire et pile, grand écran.'],
            ['Cartouche d’encre noire compatible', '119.00', 'BU', 4, 6, 'Rendement environ 400 pages.'],
            ['Souris optique sans fil', '75.00', 'BU', 4, 40, 'Récepteur USB nano, 1600 dpi.'],
            ['Étiqueteuse portable', '349.00', 'BU', 4, 2, 'Clavier AZERTY, rubans 6 à 12 mm.'],
            ['Corbeille à courrier empilable', '28.00', 'BU', 1, 50, 'Format A4+, plastique recyclé.'],

            // FO – Fourniture
            ['Stylos bille bleus (boîte de 50)', '65.00', 'FO', 2, 70, 'Pointe moyenne 1 mm, encre à séchage rapide.'],
            ['Crayons graphite HB (lot de 12)', '15.00', 'FO', 2, 140, 'Bois certifié, mine résistante.'],
            ['Surligneurs fluo (lot de 4)', '19.90', 'FO', 2, 100, 'Jaune, vert, rose, orange.'],
            ['Gomme blanche plastique', '2.50', 'FO', 2, 300, 'Efface sans laisser de traces.'],
            ['Règle plate 30 cm', '6.00', 'FO', 2, 180, 'Plastique transparent, graduation précise.'],
            ['Ciseaux 21 cm', '14.50', 'FO', 2, 5, 'Lames acier inoxydable, poignées ergonomiques.'],
            ['Colle en bâton 21 g', '8.90', 'FO', 2, 220, 'Sans solvant, lavable.'],
            ['Feutres de coloriage (24 couleurs)', '32.00', 'FO', 5, 45, 'Pointe moyenne, encre lavable.'],
            ['Trousse scolaire 2 compartiments', '25.00', 'FO', 2, 60, 'Toile résistante, fermeture éclair.'],

            // LI – Librairie
            ['Dictionnaire de français poche', '120.00', 'LI', 3, 30, '80 000 définitions, édition récente.'],
            ['Atlas géographique du Maroc', '180.00', 'LI', 3, 12, 'Cartes régionales et données économiques.'],
            ['Guide de la conjugaison française', '95.00', 'LI', 3, 25, 'Tous les verbes et leurs règles d’accord.'],
            ['Guide pratique de la comptabilité', '210.00', 'LI', 3, 4, 'Plan comptable, exercices corrigés.'],
            ['Initiation à PHP et MySQL', '260.00', 'LI', 3, 8, 'Manuel pas à pas pour créer des applications web.'],
        ];

        foreach ($products as $index => [$designation, $price, $family, $supplier, $stock, $description]) {
            Product::create([
                'designation' => $designation,
                'description' => $description,
                'unit_price' => $price,
                'family' => $family,
                'supplier_id' => $suppliers[$supplier],
                'stock' => $stock,
                'low_stock_threshold' => 5,
                'image_path' => $this->copyImage($index + 1),
            ]);
        }
    }

    /**
     * Copies the demo picture database/seeders/images/pr-NN-*.svg to the
     * public disk (storage/app/public/products), exactly where the admin
     * upload form stores pictures. Returns the stored path, or null.
     */
    private function copyImage(int $number): ?string
    {
        $files = glob(database_path('seeders/images/pr-'.sprintf('%02d', $number).'-*.svg'));
        if (! $files) {
            return null;
        }

        $path = 'products/'.basename($files[0]);
        Storage::disk('public')->put($path, file_get_contents($files[0]));

        return $path;
    }
}
