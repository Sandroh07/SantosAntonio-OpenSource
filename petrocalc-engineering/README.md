# PetroCalc Engineering — Open-Source Oil & Gas Workbench

**An offline-first engineering reference calculator** for petroleum engineers, operations teams, students and small businesses. It runs locally in a browser; no account, server, proprietary API or data upload is required.

![MIT](https://img.shields.io/badge/license-MIT-blue) ![JavaScript](https://img.shields.io/badge/JavaScript-vanilla-f7df1e) ![Excel](https://img.shields.io/badge/Excel-companion-217346)

## What it calculates

| Calculator | Formula / basis |
|---|---|
| Crude API gravity | `°API = 141.5 / SG(60°F/60°F) − 131.5` |
| Oil volume | `m³ = bbl × 0.158987294928` (42 US gallons/barrel) |
| Gas reference volume | `Vref = Vactual × (Pactual/Pref) × (Tref/Tactual) × (Zref/Zactual)`; temperatures in Kelvin, pressures absolute |
| GOR | `scf/STB = gas volume in scf ÷ stock-tank oil volume in STB`, same period |
| Hydrostatic increment | `bar = density(kg/m³) × 9.80665 × vertical depth(m) / 100000` |
| Pipe internal volume | `m³ = π × (inner diameter in metres / 2)² × length(m)` |
| Gross oil value | `USD = oil barrels × assumed USD per barrel`, no deductions |
| Optional gas BOE | `BOE = oil bbl + gas Mcf / user-selected Mcf per BOE` |

The workbook companion provides prebuilt formulas and a visual dashboard; the optional **VBA `.bas` macro** exports the dashboard to PDF after the user imports it into Excel and saves the workbook as `.xlsm` (macros are **not** embedded in the distributed `.xlsx`).

## Quick start

1. Open [`index.html`](index.html) in a modern browser, or run `python -m http.server 8080` and navigate to `http://localhost:8080`.
2. Select a calculator and edit values. All calculations update instantly, without a backend.
3. Export a CSV snapshot or save a scenario in your browser. Saved scenarios stay in that browser's local storage and can be cleared.
4. Optionally open [`spreadsheet/PetroCalc_Engineering_Dashboard.xlsx`](spreadsheet/PetroCalc_Engineering_Dashboard.xlsx) in Excel.

### Verify

```bash
node tests/calc.test.cjs
node tests/ui_smoke.test.cjs
```

## Units, conditions and engineering cautions

- API gravity relates to specific gravity referenced to **60°F/60°F**; it **does not** correct a field density observed at another temperature.
- Gas volume standardisation uses **absolute** pressure and **Kelvin** temperature, and assumes that **actual and reference Z factors are supplied by a suitable PVT method**. Defaults are illustrative, *not industry-wide contractual standards*. The app does not estimate a gas compressibility factor.
- `scf` to `m³` is a geometric conversion; measurement reference conditions must match before comparing gas quantities. A nominal 6 Mcf/BOE factor is an adjustable reporting assumption, not a universal energy or commercial equivalence.
- Hydrostatic and pipe-capacity estimates omit friction, temperature gradients, compressibility, transient effects, erosion, corrosion and pressure-integrity design checks.
- Gross value excludes royalties, production-sharing terms, tax, transportation, losses and financing, and uses a **user-provided** price only.
- **Not a custody transfer, allocation, reserves certification, engineering design, HSE or fiscal billing system.** Verify calculations against current approved standards and engineering review before operational decisions.
- **No affiliation or endorsement from Sonangol** or any petroleum company.

### References

- [U.S. Energy Information Administration — API gravity definition](https://www.eia.gov/tools/glossary/index.php?id=API)
- [U.S. EIA — Conversion factors](https://www.eia.gov/totalenergy/conversion/factors/)
- Ideal-gas state relationship extended with measured/fitted compressibility factors, as explicitly stated in the interface.

## Open source and donations

This project is released under the [MIT License](LICENSE). Donations are **voluntary**, never a condition of use. See [`SUPPORT.md`](SUPPORT.md).

**Project steward:** [Santos António](https://santosantonio.it.ao/) · `santosantonio.eng@gmail.com`
