#!/usr/bin/env python3
"""Internal, first-order comparison. No allowable load or structural approval.

SI mechanics internally; section geometry exposed in mm for workshop use.
Roof model: two equal rafters, pinned apex/eaves, continuous eaves-level tie,
uniform vertical load along each slope, laterally restrained eaves. No joint
slip, eccentricity, second-order effects, or frame/wind capacity is modelled.
"""
import argparse
import json
import math
from pathlib import Path


def positive(value, name):
    if isinstance(value, bool) or not isinstance(value, (int, float)) or not math.isfinite(value) or value <= 0:
        raise ValueError(f"{name}: nombre fini strictement positif requis")
    return float(value)


def section(diameter_mm, thickness_mm, density_kg_m3=7850):
    d = positive(diameter_mm, "diamètre")
    t = positive(thickness_mm, "épaisseur")
    rho = positive(density_kg_m3, "masse volumique")
    if 2 * t >= d:
        raise ValueError("Le diamètre intérieur doit être positif")
    inside = d - 2 * t
    area = math.pi * (d ** 2 - inside ** 2) / 4
    inertia = math.pi * (d ** 4 - inside ** 4) / 64
    return {"thickness_mm": t, "area_mm2": area, "inertia_mm4": inertia,
            "modulus_mm3": 2 * inertia / d, "mass_kg_m": area * 1e-6 * rho}


def beam(sec, span_m, load_n_m, young_mpa):
    """Pinned-pinned straight member under uniform TRANSVERSE load."""
    length = positive(span_m, "portée")
    load = positive(load_n_m, "charge répartie")
    ei = positive(young_mpa, "module E") * 1e6 * sec["inertia_mm4"] * 1e-12
    moment = load * length ** 2 / 8
    return {"moment_nm": moment,
            "bending_stress_mpa": moment * 1000 / sec["modulus_mm3"],
            "relative_bending_deflection_mm": 5 * load * length ** 4 / (384 * ei) * 1000}


def roof_case(data, spacing_m, roof_mass_kg_m2):
    """Gravity-only, symmetric tied roof. NOT a freestanding portal analysis."""
    sec = section(data["outside_diameter_mm"], data["wall_thickness_mm"], data["steel_density_kg_m3"])
    width = data["width_m"]
    rise = data["ridge_height_m"] - data["eaves_height_m"]
    rafter = math.hypot(width / 2, rise)
    cosine, sine = width / (2 * rafter), rise / rafter
    spacing = positive(spacing_m, "largeur tributaire")
    roof_mass = positive(roof_mass_kg_m2, "masse surfacique de toiture")
    # Roof mass uses ACTUAL SLOPED area, not horizontal projection.
    vertical_load = (roof_mass * spacing + sec["mass_kg_m"]) * data["gravity_m_s2"]
    reaction = vertical_load * rafter  # one eaves support; apex vertical reaction = 0 by symmetry
    tie = reaction * width / (4 * rise)
    axial_max = tie * cosine + reaction * sine
    result = beam(sec, rafter, vertical_load * cosine, data["young_modulus_mpa"])
    result.update({"spacing_m": spacing, "roof_mass_kg_m2": roof_mass,
                   "vertical_load_n_m": vertical_load, "rafter_m": rafter,
                   "eaves_vertical_reaction_n": reaction, "tie_tension_n": tie,
                   "rafter_axial_compression_max_n": axial_max,
                   "elastic_stress_envelope_mpa": result["bending_stress_mpa"] + axial_max / sec["area_mm2"]})
    return result


def validate(data):
    if data.get("status") != "illustrative_unmeasured":
        raise ValueError("Ce modèle conserve le statut illustrative_unmeasured ; il ne délivre pas de validation")
    keys = ("outside_diameter_mm", "wall_thickness_mm", "young_modulus_mpa", "steel_density_kg_m3",
            "gravity_m_s2", "width_m", "ridge_height_m", "eaves_height_m", "length_m",
            "reference_spacing_m", "roof_mass_kg_per_sloped_m2")
    for key in keys:
        positive(data[key], key)
    if data["ridge_height_m"] <= data["eaves_height_m"]:
        raise ValueError("Le faîtage doit être au-dessus des égouts")
    for key in ("comparison_thicknesses_mm", "comparison_spacings_m", "comparison_roof_masses_kg_m2", "comparison_net_pressures_kn_m2"):
        if not isinstance(data[key], list) or not data[key] or len(data[key]) > 20:
            raise ValueError(f"{key}: de 1 à 20 hypothèses requises")
        for value in data[key]:
            positive(value, key)
    section(data["outside_diameter_mm"], data["wall_thickness_mm"], data["steel_density_kg_m3"])


def calculate(data):
    validate(data)
    sec = section(data["outside_diameter_mm"], data["wall_thickness_mm"], data["steel_density_kg_m3"])
    width, length, eaves = data["width_m"], data["length_m"], data["eaves_height_m"]
    rise = data["ridge_height_m"] - eaves
    rafter = math.hypot(width / 2, rise)
    frames = math.ceil(length / data["reference_spacing_m"]) + 1
    # 2 posts + 2 rafters + one eaves-level tie per frame; 3 longitudinal rails.
    tube_length = frames * (2 * eaves + 2 * rafter + width) + 3 * length
    roof_area = 2 * rafter * length
    ei = data["young_modulus_mpa"] * 1e6 * sec["inertia_mm4"] * 1e-12
    winds = []
    for pressure in data["comparison_net_pressures_kn_m2"]:
        force = pressure * 1000 * length * eaves
        winds.append({"net_pressure_kn_m2": pressure, "rectangular_wall_force_n": force,
                      "rectangular_wall_overturning_nm": force * eaves / 2,
                      "symmetric_roof_vertical_uplift_n": pressure * 1000 * width * length})
    return {"status": "illustrative_unmeasured_not_dimensioned", "inputs": data, "section": sec,
            "section_comparison": [section(data["outside_diameter_mm"], t, data["steel_density_kg_m3"]) for t in data["comparison_thicknesses_mm"]],
            "geometry": {"rise_m": rise, "slope_deg": math.degrees(math.atan2(rise, width / 2)),
                         "rafter_m": rafter, "sloped_roof_area_m2": roof_area,
                         "hypothetical_frame_count": frames, "distributed_spacing_m": length / (frames - 1),
                         "partial_tube_length_m": tube_length, "partial_tube_mass_kg": tube_length * sec["mass_kg_m"],
                         "roof_mass_kg": roof_area * data["roof_mass_kg_per_sloped_m2"]},
            "reference": roof_case(data, data["reference_spacing_m"], data["roof_mass_kg_per_sloped_m2"]),
            "spacing_comparison": [roof_case(data, s, data["roof_mass_kg_per_sloped_m2"]) for s in data["comparison_spacings_m"]],
            "roof_mass_comparison": [roof_case(data, data["reference_spacing_m"], m) for m in data["comparison_roof_masses_kg_m2"]],
            "ideal_euler": [{"effective_length_factor": k, "critical_load_n": math.pi ** 2 * ei / (k * eaves) ** 2} for k in (1, 2)],
            "wind_action_comparison": winds,
            "not_verified": data["unknowns"]}


def markdown(result):
    d, g, s, r = (result[key] for key in ("inputs", "geometry", "section", "reference"))
    def n(value, digits=2):
        return f"{value:.{digits}f}".replace(".", ",")
    lines = ["<!-- BEGIN GENERATED CALCULATIONS -->", "## Résultats recalculables · hypothèses non mesurées", "",
             "Ces tableaux décrivent les cas théoriques ci-dessous, sans charge admissible, entraxe recommandé ou validation du stand. Les charges sont non majorées.", "",
             f"Tube acier idéal : **{n(d['outside_diameter_mm'])} × {n(d['wall_thickness_mm'])} mm** ; E = {n(d['young_modulus_mpa'], 0)} MPa ; masse volumique = {n(d['steel_density_kg_m3'], 0)} kg/m³ ; g = {n(d['gravity_m_s2'])} m/s².", "",
             f"Largeur {n(d['width_m'])} m ; faîtage {n(d['ridge_height_m'])} m ; égouts {n(d['eaves_height_m'])} m ; longueur {n(d['length_m'])} m. Cela donne une montée de {n(g['rise_m'])} m, une pente de **{n(g['slope_deg'])}°**, un rampant de **{n(g['rafter_m'], 3)} m** et {n(g['sloped_roof_area_m2'])} m² de toiture réelle. Ces cotes d’axe sont un modèle : l’épaisseur de couverture est encore à déduire du gabarit extérieur de 2,40 m.", "",
             "### Section et poids du tube de 30 mm", "", "| Épaisseur (mm) | Masse (kg/m) | A (mm²) | I (mm⁴) | W (mm³) |", "|---:|---:|---:|---:|---:|"]
    for item in result["section_comparison"]:
        lines.append("| " + " | ".join(n(item[key], 3 if key == "mass_kg_m" else 2) for key in ("thickness_mm", "mass_kg_m", "area_mm2", "inertia_mm4", "modulus_mm3")) + " |")
    lines += ["", f"La seule barre de {n(d['width_m'])} m en {n(d['outside_diameter_mm'])} × {n(d['wall_thickness_mm'])} mm pèse **{n(s['mass_kg_m'] * d['width_m'])} kg**.", "",
              "### Espacement des portiques et flexion des rampants", "",
              f"Charge de toiture hypothétique : **{n(d['roof_mass_kg_per_sloped_m2'])} kg par m² de pente**, plus poids du tube rampant. L’espacement est ici la largeur de toiture reprise par un portique intérieur. Les portiques d’extrémité reprennent en général une demi-travée, hors débord. Aucun débord dans ce modèle.", "",
              "| Largeur reprise (m) | Moment (N·m) | Contrainte de flexion (MPa) | Flèche locale (mm) | Traction du tirant (N) | Réaction verticale à chaque égout (N) |",
              "|---:|---:|---:|---:|---:|---:|"]
    for item in result["spacing_comparison"]:
        lines.append("| " + " | ".join(n(item[key]) for key in ("spacing_m", "moment_nm", "bending_stress_mpa", "relative_bending_deflection_mm", "tie_tension_n", "eaves_vertical_reaction_n")) + " |")
    lines += ["", "La flèche est la flexion locale du rampant par rapport à sa corde. Elle n’inclut ni déplacement des appuis, ni allongement du tirant, ni glissement des raccords, ni amplification par compression. Ce n’est pas le déplacement global du toit.", "",
              f"Dans le cas de référence à {n(d['reference_spacing_m'])} m : charge verticale {n(r['vertical_load_n_m'])} N/m ; compression maximale du rampant **{n(r['rafter_axial_compression_max_n'])} N** ; enveloppe élastique Nmax/A + Mmax/W = **{n(r['elastic_stress_envelope_mpa'])} MPa**. Cette enveloppe au premier ordre n’est pas une vérification de résistance ou de flambement ; aucune nuance d’acier réelle n’est supposée certifiée.", "",
              "### Sensibilité au poids sec ou mouillé", "",
              f"Même tube, même géométrie, même largeur reprise de {n(d['reference_spacing_m'])} m. Les trois masses sont des hypothèses de sensibilité ; aucune ne constitue une mesure sèche ou mouillée du plessis.", "",
              "| Toiture (kg/m² de pente) | Moment (N·m) | Flèche locale (mm) | Traction du tirant (N) |", "|---:|---:|---:|---:|"]
    for item in result["roof_mass_comparison"]:
        lines.append("| " + " | ".join(n(item[key]) for key in ("roof_mass_kg_m2", "moment_nm", "relative_bending_deflection_mm", "tie_tension_n")) + " |")
    lines += ["", "### Masse partielle du stand", "",
              f"Pour la longueur de {n(d['length_m'])} m avec {g['hypothetical_frame_count']} portiques répartis à {n(g['distributed_spacing_m'])} m : deux poteaux, deux rampants et un tirant par portique ; trois lisses longitudinales. Soit **{n(g['partial_tube_length_m'])} m de tube = {n(g['partial_tube_mass_kg'])} kg**, et **{n(g['roof_mass_kg'])} kg** pour la toiture hypothétique.", "",
              "Inventaire partiel : ne comprend pas les raccords, diagonales, ancrages, bardages, fixations, lisses supplémentaires ni décors. Il ne faut pas le traiter comme le poids total ni comme un lest mobilisable. Les charges de ces éléments restent à ajouter à leur véritable chemin de reprise.", "",
              "### Flambement idéal d’un poteau", "", "| Facteur de longueur efficace K | Charge critique d’Euler (N) |", "|---:|---:|"]
    for item in result["ideal_euler"]:
        lines.append(f"| {n(item['effective_length_factor'])} | {n(item['critical_load_n'])} |")
    lines += ["", f"Longueur physique {n(d['eaves_height_m'])} m. K = 1 illustre des extrémités articulées effectivement maintenues latéralement ; K = 2 illustre un encastrement parfait en pied avec sommet libre. Ces conditions ne sont pas acquises pour le stand. Euler décrit un poteau idéal : **ces nombres ne sont pas des charges autorisées**, et un portique articulé sans contreventement peut être un mécanisme. Corrosion, faux aplomb, excentricités et interaction flexion/compression ne sont pas couverts.", "",
              "### Actions de pression : ce que doivent reprendre les liaisons", "",
              "Pressions nettes uniformes choisies pour comparer les efforts, pas un vent local calculé. Mur rectangulaire latéral seul, sans triangle de pignon ; soulèvement vertical symétrique des deux pans. Les lignes sont des cas séparés, pas des combinaisons de dimensionnement.", "",
              "| Pression nette (kN/m²) | Force sur le mur (N) | Moment au sol (N·m) | Soulèvement brut du toit (N) |", "|---:|---:|---:|---:|"]
    for item in result["wind_action_comparison"]:
        lines.append("| " + " | ".join(n(item[key]) for key in ("net_pressure_kn_m2", "rectangular_wall_force_n", "rectangular_wall_overturning_nm", "symmetric_roof_vertical_uplift_n")) + " |")
    lines += ["", "Aucune déduction de poids ni répartition automatique entre ancrages. Les ouvertures, pressions internes, succions de rive, rugosité, relief et rafales restent à définir pour le site. Aucun seuil de vent d’exploitation n’est déduit de ce tableau.", "", "<!-- END GENERATED CALCULATIONS -->"]
    return "\n".join(lines)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--input", type=Path, default=Path(__file__).resolve().parents[1] / "docs/cabin-structure-assumptions.json")
    parser.add_argument("--format", choices=("json", "markdown"), default="markdown")
    args = parser.parse_args()
    try:
        result = calculate(json.loads(args.input.read_text()))
    except (ValueError, KeyError, OSError) as error:
        parser.error(str(error))
    print(json.dumps(result, ensure_ascii=False, indent=2, allow_nan=False) if args.format == "json" else markdown(result))
