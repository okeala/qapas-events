<?php

namespace App\Services\Events;

class PlanExport
{
    public function svg(array $plan): string
    {
        $escape = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $features = $plan['plan']['features'];
        $origin = [$plan['center'][1], $plan['center'][0]];
        $xy = fn ($p) => [deg2rad($p[0] - $origin[0]) * 6371008.8 * cos(deg2rad($origin[1])), deg2rad($p[1] - $origin[1]) * 6371008.8];
        $points = [];
        foreach ($features as $feature) {
            $rings = $feature['geometry']['type'] === 'Polygon' ? $feature['geometry']['coordinates'] : [$feature['geometry']['coordinates']];
            foreach ($rings as $ring) {
                foreach ($ring as $point) {
                    $points[] = $xy($point);
                }
            }
        }
        $xs = array_column($points, 0);
        $ys = array_column($points, 1);
        $minX = $xs ? min($xs) : -10;
        $maxX = $xs ? max($xs) : 10;
        $minY = $ys ? min($ys) : -10;
        $maxY = $ys ? max($ys) : 10;
        $spanX = max(20, $maxX - $minX);
        $spanY = max(20, $maxY - $minY);
        $scale = min(1030 / $spanX, 680 / $spanY);
        $cx = ($minX + $maxX) / 2;
        $cy = ($minY + $maxY) / 2;
        $project = fn ($p) => [590 + ($xy($p)[0] - $cx) * $scale, 522.5 - ($xy($p)[1] - $cy) * $scale];
        $phase = $plan['event']['decision'] === 'not_confirmed' ? 'Édition non confirmée' : config('event_planner.phases.'.$plan['event']['phase']);
        $height = max(1000, 240 + count($features) * 23);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="'.$height.'" viewBox="0 0 1600 '.$height.'"><rect width="1600" height="'.$height.'" fill="#ffffff"/>';
        $svg .= '<g font-family="DejaVu Sans, Arial, sans-serif" fill="#183e29"><text x="55" y="65" font-size="34">'.$escape(mb_substr($plan['event']['name'], 0, 65)).'</text>';
        $svg .= '<text x="55" y="105" font-size="20">'.$escape($phase).' · Publication '.$plan['publication']['number'].'</text>';
        $svg .= '<text x="55" y="135" font-size="16">'.$escape($plan['event']['venue']).($plan['event']['is_demo'] ? ' · Implantation illustrative de démonstration' : '').'</text>';
        $svg .= '<rect x="40" y="165" width="1100" height="715" rx="15" fill="#f3f7f2" stroke="#bdccc1"/>';
        foreach ($features as $i => $feature) {
            $polygon = $feature['geometry']['type'] === 'Polygon';
            $rings = $polygon ? $feature['geometry']['coordinates'] : [$feature['geometry']['coordinates']];
            $path = '';
            foreach ($rings as $ring) {
                foreach ($ring as $j => $p) {
                    [$x,$y] = $project($p);
                    $path .= ($j ? ' L' : 'M').round($x, 3).' '.round($y, 3);
                } if ($polygon) {
                    $path .= ' Z ';
                }
            }
            $color = $feature['properties']['color'];
            if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $color = '#217846';
            }
            $svg .= '<path d="'.$path.'" fill="'.($polygon ? $color : 'none').'" fill-opacity="0.4" fill-rule="evenodd" stroke="'.$color.'" stroke-width="2"/>';
            [$x,$y] = $project($rings[0][0]);
            $svg .= '<text x="'.round($x, 3).'" y="'.round($y - 5, 3).'" font-size="14" font-weight="bold">'.($i + 1).'</text>';
            $svg .= '<rect x="1180" y="'.(204 + $i * 23).'" width="12" height="12" fill="'.$color.'"/><text x="1204" y="'.(216 + $i * 23).'" font-size="14">'.($i + 1).'. '.$escape(mb_substr($feature['properties']['name'], 0, 42)).'</text>';
        }
        $distance = $this->scaleDistance(200 / $scale);
        $bar = round($distance * $scale, 3);
        $svg .= '<path d="M70 845h'.$bar.'" stroke="#183e29" stroke-width="4"/><text x="70" y="830" font-size="16">'.$distance.' m · mesure approximative</text>';
        $svg .= '<path d="M1080 245v-50m0 0l-8 14m8-14l8 14" stroke="#183e29" stroke-width="3" fill="none"/><text x="1073" y="182" font-size="17">N</text>';
        $svg .= '<text x="1180" y="180" font-size="20" font-weight="bold">Lieux publiés</text><text x="55" y="930" font-size="16">Plan événementiel sans fond cartographique. Consulter la visite en ligne pour le statut et les disponibilités.</text>';

        return $svg.'</g></svg>';
    }

    public function pdf(array $plan): string
    {
        // Vector A3 export of our own safe SVG, using PDF's standard Helvetica font.
        // No remote imagery, external process, HTML renderer or private projection is involved.
        $xml = simplexml_load_string($this->svg($plan), \SimpleXMLElement::class, LIBXML_NONET);
        $height = (float) $xml['height'];
        $factor = min(1110.55 / 1600, 761.89 / $height);
        $stream = 'q '.$factor.' 0 0 '.$factor.' 40 '.(801.89 - $height * $factor)." cm\n";
        $color = function (string $hex, float $opacity = 1): string {
            $hex = ltrim($hex, '#');
            $rgb = array_map(fn ($i) => round((hexdec(substr($hex, $i, 2)) / 255) * $opacity + (1 - $opacity), 5), [0, 2, 4]);

            return implode(' ', $rgb);
        };
        foreach ([$xml->rect, ...iterator_to_array($xml->g->children(), false)] as $node) {
            $name = $node->getName();
            $fill = (string) ($node['fill'] ?? '#183e29');
            $stroke = (string) ($node['stroke'] ?? '');
            if ($name === 'text') {
                $text = iconv('UTF-8', 'Windows-1252//TRANSLIT', (string) $node);
                $text = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $text);
                $stream .= $color('#183e29').' rg BT /F1 '.(float) $node['font-size'].' Tf 1 0 0 1 '.(float) $node['x'].' '.($height - (float) $node['y']).' Tm ('.$text.") Tj ET\n";

                continue;
            }
            $commands = '';
            if ($name === 'rect') {
                $commands = (float) $node['x'].' '.($height - (float) $node['y'] - (float) $node['height']).' '.(float) $node['width'].' '.(float) $node['height'].' re ';
            }
            if ($name === 'path') {
                preg_match_all('/([MmLlHhVvZz])([^MmLlHhVvZz]*)/', (string) $node['d'], $parts, PREG_SET_ORDER);
                $x = 0;
                $y = 0;
                foreach ($parts as $part) {
                    $cmd = $part[1];
                    preg_match_all('/[+-]?(?:\d+(?:\.\d*)?|\.\d+)/', $part[2], $matches);
                    $numbers = $matches[0];
                    $relative = ctype_lower($cmd);
                    if (strtolower($cmd) === 'z') {
                        $commands .= 'h ';

                        continue;
                    }
                    if (in_array(strtolower($cmd), ['m', 'l'], true)) {
                        $x = ($relative ? $x : 0) + (float) $numbers[0];
                        $y = ($relative ? $y : 0) + (float) $numbers[1];
                    } elseif (strtolower($cmd) === 'h') {
                        $x = ($relative ? $x : 0) + (float) $numbers[0];
                    } elseif (strtolower($cmd) === 'v') {
                        $y = ($relative ? $y : 0) + (float) $numbers[0];
                    }
                    $commands .= $x.' '.($height - $y).' '.(strtolower($cmd) === 'm' ? 'm ' : 'l ');
                }
            }
            if (! $commands) {
                continue;
            }
            $hasFill = $fill !== 'none';
            $stream .= 'q '.($hasFill ? $color($fill, (float) ($node['fill-opacity'] ?? 1)).' rg ' : '').($stroke ? $color($stroke).' RG '.(float) ($node['stroke-width'] ?? 1).' w ' : '');
            $stream .= $commands.($hasFill ? ($stroke ? 'B*' : 'f*') : 'S')." Q\n";
        }
        $stream .= "Q\n";
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 1190.55 841.89] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($stream).">>\nstream\n".$stream.'endstream'];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    private function scaleDistance(float $maximum): float
    {
        $power = 10 ** floor(log10(max(.001, $maximum)));
        $factor = $maximum / $power;

        return ($factor >= 5 ? 5 : ($factor >= 2 ? 2 : 1)) * $power;
    }
}
