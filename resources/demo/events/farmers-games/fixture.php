<?php

// Données de référence propres à un projet. Aucun comportement du moteur ne dépend de ce fichier.
return [
    'schema_version'=>1,
    'provenance'=>['repository'=>'okeala/qapas-farmers-games','reference_commit'=>'09870bd772711420a1af35e96eab800847ece841','reviewed_at'=>'2026-10-01',
        'status'=>'demonstration','geometry'=>'Implantation illustrative ; ne constitue pas un relevé ou une implantation validée.'],
    'event'=>['name'=>'A Forqua de Ouro','slug'=>'forqua-de-ouro-demo','venue'=>'Aldeia do Souto — site à confirmer',
        'starts_at'=>'2026-12-12 09:00:00','ends_at'=>'2026-12-13 18:00:00','phase'=>'applications','decision'=>'pending','decision_days'=>21,
        'description'=>['fr'=>'Villages, saveurs, défis et rencontres : chacun y trouvera sa place. Préparez une fête des savoir-faire, de la coopération et des découvertes en famille.',
            'pt'=>'Aldeias, sabores, desafios e encontros: há lugar para toda a gente. Uma festa de saberes, cooperação e descobertas em família.'],
        'settings'=>['entry_free'=>true,'minimum_villages'=>6,'target_villages'=>12,'target_independent_stands'=>12,'team_target_people'=>24,'team_capacity_people'=>48,
            'independent_sales_commission_bps'=>0,'profit_policy'=>['remaining_per_month_cents'=>400000,'minimum_preparation_net_remuneration_cents'=>100000,'months_of_effort'=>null,'status'=>'to_complete'],
            'phase_copy'=>[
                'teaser'=>[
                    'fr'=>['title'=>'A Forqua de Ouro se prépare','message'=>'Villages, saveurs, défis et rencontres : chacun y trouvera sa place. Découvrez ce que nous imaginons et dites-nous comment vous aimeriez y participer.'],
                    'pt'=>['title'=>'A Forqua de Ouro está a preparar-se','message'=>'Aldeias, sabores, desafios e encontros: há lugar para toda a gente. Descubra o que estamos a imaginar e diga-nos como gostaria de participar.']],
                'applications'=>[
                    'fr'=>['title'=>'Les candidatures sont ouvertes','message'=>'En route pour A Forqua de Ouro. Rejoignez une équipe, proposez un stand ou contribuez à l’organisation. L’événement sera confirmé lorsque les conditions de lancement seront atteintes.'],
                    'pt'=>['title'=>'Candidaturas abertas','message'=>'A caminho de A Forqua de Ouro. Junte-se a uma equipa, proponha uma banca ou contribua para a organização. O evento será confirmado quando as condições de lançamento forem cumpridas.']],
                'official'=>[
                    'fr'=>['title'=>'A Forqua de Ouro est confirmée','message'=>'Retrouvez les dates, le lieu, les villages et les activités dans la visite guidée. Découvrez ce qui reste disponible et préparez votre venue. L’entrée est libre.'],
                    'pt'=>['title'=>'A Forqua de Ouro está confirmada','message'=>'Consulte as datas, o local, as aldeias e as atividades na visita guiada. Descubra o que ainda está disponível e prepare a sua visita. A entrada é livre.']],
            ]]],
    'scenario'=>['name'=>'Format de base proposé','is_base'=>true,'center_lat'=>40.3399,'center_lng'=>-7.3530],
    'offers'=>[
        ['key'=>'relay','name'=>'Devenir point relais','audience'=>'relays','price_cents'=>4999,'capacity'=>null,
            'description'=>'Un lieu de proximité pour faire connaître l’aventure et orienter les personnes intéressées.',
            'included'=>'250 flyers couleur, 1 affiche A3, 3 A4, 3 A5 et une présentation du relais.',
            'conditions'=>'Première édition ; livraison sous 14 jours à partir de la confirmation et du paiement reçu. La commande conditionnelle suit l’échéance commune.','target_quantity'=>0],
        ['key'=>'bare-stand','name'=>'Emplacement indépendant 2,40 × 2,40 m','audience'=>'exhibitors','price_cents'=>25000,'pool'=>'independent',
            'description'=>'Présenter ses produits, ses savoir-faire ou son activité pendant le week-end.',
            'included'=>'Emplacement de 5,76 m² ; conditions techniques et implantation à vérifier.',
            'conditions'=>'Week-end ; aucune commission QAPAS sur vos ventes. Conditions de lancement et de restitution applicables.','target_quantity'=>12],
        ['key'=>'mounted-stand','name'=>'Stand indépendant monté','audience'=>'exhibitors','price_cents'=>50000,'pool'=>'independent',
            'description'=>'Un stand monté pour se concentrer sur son activité et l’accueil des visiteurs.',
            'included'=>'Stand monté avec emplacement ; prestations et coûts complets à deviser.',
            'conditions'=>'Alternative à l’emplacement nu ; même capacité de 12 emplacements. Aucune commission sur vos ventes.','target_quantity'=>0],
        ['key'=>'structural-sponsor','name'=>'Sponsoring structurel','audience'=>'sponsors','price_cents'=>250000,'capacity'=>3,
            'description'=>'Associer sa marque à une rencontre entre villages, savoir-faire et publics locaux.',
            'included'=>'Visibilité convenue sur les supports et stands. Répartition des surfaces 2/4, 1/4, 1/4 à valider.',
            'conditions'=>'Prestations exactes et preuves de livraison à contractualiser ; même prix malgré des surfaces différentes, valeur à vérifier.','target_quantity'=>3],
    ],
    'logistics'=>[
        ['key'=>'shredder','label'=>'Broyeur REMO — une journée, 100 € indicatifs','category'=>'vehicles','subcategory'=>'shredder','indicative_cents'=>10000,'notes'=>'Prévoir une seule journée et jusqu’à 20 litres de SP98 ; prix, fiscalité et disponibilité à vérifier.'],
        ['key'=>'dyna','label'=>'Toyota Dyna — trois allers-retours Aldeia do Souto / Fundão','category'=>'vehicles','subcategory'=>'truck','notes'=>'Transport, temps, kilomètres et carburant à chiffrer.'],
        ['key'=>'cable','label'=>'Branchement de stand — longueur maximale 50 m, besoin 6 A','category'=>'electricity','subcategory'=>'cable','notes'=>'Investissement QAPAS à référencer ; usage inclus dans la prestation. Coffret et protection à définir et valider techniquement.'],
        ['key'=>'sisal','label'=>'Sisal — trois bobines par abri','category'=>'furniture','subcategory'=>'supplies','notes'=>'Quantités selon abris réellement réalisés ; achats et temps de pose à chiffrer.'],
        ['key'=>'mimosa','label'=>'Mimosas utiles au bardage — 2,50 m maximum','category'=>'structures','subcategory'=>'shelter','notes'=>'Découpe, disponibilité et transport non garantis ; prévoir une alternative.'],
    ],
    'products'=>[
        ['sku'=>'MERCH-DEMO','name'=>'Produit souvenir à définir','price_cents'=>0,'description'=>'Exemple de merchandising : produit, coût, tarif et approvisionnement restent à définir.'],
        ['sku'=>'MEAL-DEMO','name'=>'Repas à définir','price_cents'=>0,'description'=>'Cuisine collective : opérateur, offre et coûts restent ouverts. Aucune règle 90/10 activée.'],
    ],
];
