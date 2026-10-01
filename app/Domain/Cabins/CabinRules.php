<?php
namespace App\Domain\Cabins;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\{Rule, ValidationException};

final class CabinRules
{
    public const PARTS = ['frame'=>'Tubes récupérés auprès des démolisseurs', 'connectors'=>'Raccords d’échafaudage · pièce maîtresse', 'roof'=>'Support de toiture en plessis', 'roof_cover'=>'Surcouverture en paille', 'cladding'=>'Bardage en plessis', 'decoration'=>'Décoration', 'fixings'=>'Visserie', 'lashings'=>'Ligatures en sisal / fibre naturelle adaptée'];
    public const MATERIALS = ['metal'=>'Métal', 'mimosa'=>'Mimosa issu du chantier de contrôle', 'wood'=>'Bois de récupération', 'natural'=>'Fibre naturelle / autre matériau naturel de récupération', 'cane'=>'Cannes identifiées', 'straw'=>'Paille propre issue de récupération agricole'];
    public const SOURCES = ['recovered'=>'Récupération documentée', 'controlled'=>'Végétaux identifiés issus du chantier de contrôle', 'new_hardware'=>'Connecteurs / visserie / ligatures neuves'];
    public const STATUSES = ['concept'=>'À concevoir', 'building'=>'En fabrication', 'ready'=>'À réceptionner sur site', 'received'=>'Réception enregistrée', 'withdrawn'=>'Retiré'];

    public static function validateMaterials(array $rows): void
    {
        Validator::make(['materials'=>$rows], [
            'materials'=>'array|max:80',
            'materials.*.name'=>'required|string|max:200',
            'materials.*.part'=>['required', Rule::in(array_keys(self::PARTS))],
            'materials.*.material'=>['required', Rule::in(array_keys(self::MATERIALS))],
            'materials.*.source'=>['required', Rule::in(array_keys(self::SOURCES))],
            'materials.*.quantity'=>'required|integer|between:1,100000',
            'materials.*.unit'=>'required|string|max:40',
            'materials.*.origin'=>'required|string|max:2000',
            'materials.*.max_diameter_mm'=>'nullable|integer|between:1,60',
        ])->validate();
        foreach ($rows as $i=>$row) {
            $fail = fn (string $text) => throw ValidationException::withMessages(['materials.'.$i.'.material'=>$text]);
            if (in_array($row['material'], ['mimosa','cane'], true) && !isset($row['max_diameter_mm'])) throw ValidationException::withMessages(['materials.'.$i.'.max_diameter_mm'=>'Renseigner le diamètre maximal du lot végétal : 60 mm au maximum.']);
            if (in_array($row['part'], ['frame','connectors'], true) && $row['material']!=='metal') $fail('Ossature en tubes métalliques récupérés, réunis par des raccords d’échafaudage métalliques.');
            if (in_array($row['part'], ['roof','cladding'], true) && !in_array($row['material'], ['mimosa','cane'], true)) $fail('Couverture et bardage en plessis de mimosa et/ou cannes identifiées.');
            if ($row['part']==='roof_cover' && $row['material']!=='straw') $fail('Surcouverture optionnelle en paille propre récupérée ; le support reste en plessis.');
            if ($row['part']==='lashings' && $row['material']!=='natural') $fail('Lier les végétaux avec du sisal ou une fibre naturelle adaptée.');
            if ($row['part']==='fixings' && !in_array($row['material'], ['metal','natural'], true)) $fail('Fixations métalliques ou ligatures en fibres naturelles, dont sisal.');
            if (($row['material']==='mimosa' && $row['source']!=='controlled') || ($row['source']==='controlled' && !in_array($row['material'], ['mimosa','cane'], true))) $fail('Le mimosa doit provenir du chantier de contrôle documenté.');
            if ($row['source']==='new_hardware' && !in_array($row['part'], ['connectors','fixings','lashings'], true)) $fail('Seuls les connecteurs, la visserie et les attaches peuvent être neufs.');
            if (!in_array($row['material'], ['mimosa','cane'], true) && !in_array($row['part'], ['connectors','fixings','lashings'], true) && $row['source']!=='recovered') $fail('Matériaux de récupération uniquement.');
        }
    }
}
