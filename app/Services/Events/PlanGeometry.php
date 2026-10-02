<?php

namespace App\Services\Events;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlanGeometry
{
    public static function shapesFor(string $category): array
    {
        $shapes = ['square' => 'Carré', 'rectangle' => 'Rectangle', 'regular_polygon' => 'Polygone régulier', 'circle' => 'Cercle'];
        if (in_array($category, ['areas', 'routes_accesses'], true)) {
            $shapes['free_polygon'] = 'Espace libre';
        }
        if (in_array($category, ['routes_accesses', 'barriers', 'electricity', 'audiovisual'], true)) {
            $shapes['line'] = 'Axe ou liaison';
        }

        return $shapes;
    }

    public function make(string $shape, array $dimensions, ?array $geometry = null): array
    {
        if (in_array($shape, ['free_polygon', 'line'], true)) {
            $this->validateFree($shape, $geometry ?? []);
            $points = fn ($ring) => array_map(fn ($p) => [(float) $p[0], (float) $p[1]], $ring);
            $geometry['coordinates'] = $shape === 'line' ? $points($geometry['coordinates']) : array_map($points, $geometry['coordinates']);

            return $geometry;
        }
        Validator::make($dimensions, ['lat' => 'required|numeric|between:-85,85', 'lng' => 'required|numeric|between:-180,180',
            'width' => 'nullable|numeric|between:0.1,2000', 'height' => 'nullable|numeric|between:0.1,2000',
            'radius' => 'nullable|numeric|between:0.1,1000', 'sides' => 'nullable|integer|between:3,32', 'angle' => 'nullable|numeric|between:-360,360'])->validate();
        $lat = (float) $dimensions['lat'];
        $lng = (float) $dimensions['lng'];
        $angle = deg2rad((float) ($dimensions['angle'] ?? 0));
        $width = (float) ($dimensions['width'] ?? 2.4);
        $height = $shape === 'square' ? $width : (float) ($dimensions['height'] ?? $width);
        if (in_array($shape, ['square', 'rectangle'], true)) {
            $points = [[-$width / 2, -$height / 2], [$width / 2, -$height / 2], [$width / 2, $height / 2], [-$width / 2, $height / 2]];
        } elseif (in_array($shape, ['regular_polygon', 'circle'], true)) {
            $sides = $shape === 'circle' ? 64 : (int) ($dimensions['sides'] ?? 6);
            $radius = (float) ($dimensions['radius'] ?? $width / 2);
            $points = [];
            for ($i = 0; $i < $sides; $i++) {
                $points[] = [cos(2 * pi() * $i / $sides) * $radius, sin(2 * pi() * $i / $sides) * $radius];
            }
        } else {
            throw ValidationException::withMessages(['shape' => 'Gabarit inconnu.']);
        }
        $ring = [];
        foreach ($points as [$x,$y]) {
            $east = $x * cos($angle) - $y * sin($angle);
            $north = $x * sin($angle) + $y * cos($angle);
            $ring[] = $this->offset($lat, $lng, $east, $north);
        }
        $ring[] = $ring[0];

        return ['type' => 'Polygon', 'coordinates' => [$ring]];
    }

    public function offset(float $lat, float $lng, float $east, float $north): array
    {
        return [$lng + rad2deg($east / (6371008.8 * cos(deg2rad($lat)))), $lat + rad2deg($north / 6371008.8)];
    }

    public function measurements(array $geometry): array
    {
        $coordinates = $geometry['coordinates'];
        $rings = $geometry['type'] === 'Polygon' ? $coordinates : [$coordinates];
        $origin = $rings[0][0];
        $length = 0.;
        $area = 0.;
        foreach ($rings as $r => $ring) {
            $xy = array_map(fn ($p) => [$this->east($p[0] - $origin[0], $origin[1]), deg2rad($p[1] - $origin[1]) * 6371008.8], $ring);
            $signed = 0.;
            for ($i = 1; $i < count($xy); $i++) {
                $length += hypot($xy[$i][0] - $xy[$i - 1][0], $xy[$i][1] - $xy[$i - 1][1]);
                $signed += $xy[$i - 1][0] * $xy[$i][1] - $xy[$i][0] * $xy[$i - 1][1];
            }
            $area += ($r === 0 ? 1 : -1) * abs($signed / 2);
        }

        return ['area_m2' => $geometry['type'] === 'Polygon' ? round(max(0, $area), 4) : 0, 'length_m' => round($length, 4)];
    }

    private function east(float $delta, float $lat): float
    {
        return deg2rad($delta) * 6371008.8 * cos(deg2rad($lat));
    }

    private function validateFree(string $shape, array $geometry): void
    {
        $polygon = $shape === 'free_polygon';
        if (($geometry['type'] ?? null) !== ($polygon ? 'Polygon' : 'LineString') || ! is_array($geometry['coordinates'] ?? null)) {
            $this->invalid();
        }
        $rings = $polygon ? $geometry['coordinates'] : [$geometry['coordinates']];
        if (count($rings) < 1 || count($rings) > 20) {
            $this->invalid();
        }
        foreach ($rings as $ring) {
            if (! is_array($ring) || count($ring) < ($polygon ? 4 : 2) || count($ring) > 501) {
                $this->invalid();
            }
            foreach ($ring as $point) {
                if (! is_array($point) || count($point) !== 2 || ! is_numeric($point[0]) || ! is_numeric($point[1]) || ! is_finite((float) $point[0]) || ! is_finite((float) $point[1]) || abs($point[0]) > 180 || abs($point[1]) > 85) {
                    $this->invalid();
                }
            }
            if ($polygon && $ring[0] != $ring[count($ring) - 1]) {
                $this->invalid();
            }
            for ($i = 1; $i < count($ring); $i++) {
                if ($ring[$i] == $ring[$i - 1] || abs($ring[$i][0] - $ring[$i - 1][0]) > 180) {
                    $this->invalid();
                }
            }
            if ($polygon) {
                for ($i = 0; $i < count($ring) - 1; $i++) {
                    for ($j = $i + 2; $j < count($ring) - 1; $j++) {
                        if ($i === 0 && $j === count($ring) - 2) {
                            continue;
                        }
                        if ($this->crosses($ring[$i], $ring[$i + 1], $ring[$j], $ring[$j + 1])) {
                            $this->invalid();
                        }
                    }
                }
            }
        }
        if ($polygon) {
            foreach ($rings as $i => $hole) {
                if ($i === 0) {
                    continue;
                }
                if (! $this->inside($hole[0], $rings[0])) {
                    $this->invalid();
                }
                for ($j = 0; $j < $i; $j++) {
                    if ($j > 0 && ($this->inside($hole[0], $rings[$j]) || $this->inside($rings[$j][0], $hole))) {
                        $this->invalid();
                    }
                    for ($a = 0; $a < count($hole) - 1; $a++) {
                        for ($b = 0; $b < count($rings[$j]) - 1; $b++) {
                            if ($this->crosses($hole[$a], $hole[$a + 1], $rings[$j][$b], $rings[$j][$b + 1])) {
                                $this->invalid();
                            }
                        }
                    }
                }
            }
        }
        if ($polygon && $this->measurements($geometry)['area_m2'] <= 0) {
            $this->invalid();
        }
    }

    private function crosses(array $a, array $b, array $c, array $d): bool
    {
        $orient = fn ($p, $q, $r) => ($q[0] - $p[0]) * ($r[1] - $p[1]) - ($q[1] - $p[1]) * ($r[0] - $p[0]);
        $o1 = $orient($a, $b, $c);
        $o2 = $orient($a, $b, $d);
        $o3 = $orient($c, $d, $a);
        $o4 = $orient($c, $d, $b);
        if ($o1 * $o2 < 0 && $o3 * $o4 < 0) {
            return true;
        }
        $on = fn ($p, $q, $r) => $r[0] >= min($p[0], $q[0]) && $r[0] <= max($p[0], $q[0]) && $r[1] >= min($p[1], $q[1]) && $r[1] <= max($p[1], $q[1]);

        return (abs($o1) < 1e-15 && $on($a, $b, $c)) || (abs($o2) < 1e-15 && $on($a, $b, $d))
            || (abs($o3) < 1e-15 && $on($c, $d, $a)) || (abs($o4) < 1e-15 && $on($c, $d, $b));
    }

    private function inside(array $point, array $ring): bool
    {
        $inside = false;
        for ($i = 0,$j = count($ring) - 1; $i < count($ring); $j = $i++) {
            if (($ring[$i][1] > $point[1]) !== ($ring[$j][1] > $point[1])
                && $point[0] < ($ring[$j][0] - $ring[$i][0]) * ($point[1] - $ring[$i][1]) / ($ring[$j][1] - $ring[$i][1]) + $ring[$i][0]) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['geometry' => 'Tracé invalide : vérifier sommets, fermeture et intersections.']);
    }
}
