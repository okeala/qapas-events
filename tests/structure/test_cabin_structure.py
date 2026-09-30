"""Independent dimensional/scaling checks; these do not validate a real cabin."""
import importlib.util
import json
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location("cabin_structure", ROOT / "scripts/cabin_structure.py")
calc = importlib.util.module_from_spec(spec)
spec.loader.exec_module(calc)


class CabinStructureTest(unittest.TestCase):
    def setUp(self):
        self.data = json.loads((ROOT / "docs/cabin-structure-assumptions.json").read_text())

    def test_section_mass_and_dimensions(self):
        sec = calc.section(30, 2)
        self.assertAlmostEqual(sec["mass_kg_m"], 1.38104413, places=7)
        self.assertAlmostEqual(sec["inertia_mm4"], 17329.0250772, places=6)
        self.assertAlmostEqual(sec["modulus_mm3"] * 15, sec["inertia_mm4"])
        with self.assertRaises(ValueError):
            calc.section(30, 15)

    def test_classical_beam_reference_and_span_scaling(self):
        # 1 m beam, 1 kN/m, E = 200 GPa, I = 1e-6 m4 -> M=125 Nm, delta=.065104 mm.
        sec = {"inertia_mm4": 1e6, "modulus_mm3": 2e4}
        first = calc.beam(sec, 1, 1000, 200000)
        second = calc.beam(sec, 2, 1000, 200000)
        self.assertAlmostEqual(first["moment_nm"], 125)
        self.assertAlmostEqual(first["relative_bending_deflection_mm"], 0.065104166667)
        self.assertAlmostEqual(second["moment_nm"] / first["moment_nm"], 4)
        self.assertAlmostEqual(second["relative_bending_deflection_mm"] / first["relative_bending_deflection_mm"], 16)

    def test_roof_equilibrium_and_load_provenance(self):
        result = calc.calculate(self.data)
        roof, geometry = result["reference"], result["geometry"]
        # Moments about apex for a half roof: support P*b/2 - roof P*b/4 - tie*h = 0.
        p = roof["eaves_vertical_reaction_n"]
        self.assertAlmostEqual(p * 2.4 / 2 - p * 2.4 / 4 - roof["tie_tension_n"] * .4, 0)
        self.assertAlmostEqual(p, roof["vertical_load_n_m"] * geometry["rafter_m"])
        self.assertAlmostEqual(roof["moment_nm"], 47.24, places=2)
        self.assertAlmostEqual(geometry["partial_tube_mass_kg"], 46.94, places=2)
        self.assertEqual(geometry["hypothetical_frame_count"], 3)
        self.assertEqual(result["status"], "illustrative_unmeasured_not_dimensioned")
        self.assertEqual(result["wind_action_comparison"][1]["symmetric_roof_vertical_uplift_n"], 3456)

    def test_impossible_inputs_rejected(self):
        for key, value in (("wall_thickness_mm", 0), ("length_m", float("nan")), ("eaves_height_m", 2.5), ("status", "approved")):
            with self.subTest(key=key), self.assertRaises(ValueError):
                calc.calculate(dict(self.data, **{key: value}))


if __name__ == "__main__":
    unittest.main()
