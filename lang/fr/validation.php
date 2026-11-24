<?php

/*
 * French validation messages (only the rules used by Lara-Shop).
 * :attribute is replaced by the French name from the "attributes" array below.
 */
return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'between' => [
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe actuel est incorrect.',
    'date' => 'Le champ :attribute n’est pas une date valide.',
    'different' => 'Le nouveau :attribute doit être différent de l’actuel.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide (exemple : prenom.nom@domaine.ma).',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'gt' => [
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
    ],
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'max' => [
        'file' => 'Le fichier :attribute ne doit pas dépasser :max kilo-octets.',
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'mimes' => 'Le fichier :attribute doit être de type : :values.',
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Ce :attribute figure dans une fuite de données publique. Choisissez-en un autre.',
    ],
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être du texte.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'uploaded' => 'Le fichier :attribute n’a pas pu être envoyé.',
    'uuid' => 'Le champ :attribute est invalide.',

    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'current_password' => 'mot de passe actuel',
        'street' => 'rue',
        'postal_code' => 'code postal',
        'city' => 'ville',
        'type' => 'type client',
        'special_discount' => 'remise spéciale',
        'designation' => 'désignation',
        'description' => 'description',
        'unit_price' => 'prix unitaire',
        'family' => 'famille',
        'supplier_id' => 'fournisseur',
        'stock' => 'stock',
        'low_stock_threshold' => 'seuil d’alerte',
        'image' => 'image',
        'quantity' => 'quantité',
        'product_id' => 'produit',
        'payment_terms' => 'condition de paiement',
        'checkout_token' => 'jeton de commande',
        'status' => 'statut',
    ],
];
